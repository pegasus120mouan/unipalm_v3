<?php
require_once '../inc/functions/connexion.php';
require_once '../inc/functions/requete/requete_tickets.php';
require_once '../inc/functions/requete/requete_usines.php';
require_once '../inc/functions/requete/requete_chef_equipes.php';
require_once '../inc/functions/requete/requete_vehicules.php';
require_once '../inc/functions/requete/requete_agents.php';
include('header.php');

$id_user = $_SESSION['user_id'];

$limit = $_GET['limit'] ?? 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Récupérer les données
$tickets = getTickets($conn); 
$usines = getUsines($conn);
$chefs_equipes = getChefEquipes($conn);
$vehicules = getVehicules($conn);
$agents = getAgents($conn);


// Récupérer la liste des chefs d'équipe (pour compatibilité avec le modal)
$stmt = $conn->prepare(
    "SELECT id_chef, CONCAT(nom, ' ', prenoms) as chef_nom_complet 
     FROM chef_equipe 
     ORDER BY nom"
);
$stmt->execute();
$chefs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Gestion des filtres de recherche - Nettoyer les espaces
$search_nom = trim($_GET['search_nom'] ?? '');
$search_prenom = trim($_GET['search_prenom'] ?? '');
$search_contact = trim($_GET['search_contact'] ?? '');
$search_chef = trim($_GET['search_chef'] ?? '');

// Filtrer les agents selon les critères de recherche
$agents_filtered = $agents;
if (!empty($search_nom) || !empty($search_prenom) || !empty($search_contact) || !empty($search_chef)) {
    $agents_filtered = array_filter($agents, function($agent) use ($search_nom, $search_prenom, $search_contact, $search_chef) {
        // Nettoyer et normaliser les données pour la comparaison
        $agent_nom = preg_replace('/\s+/', ' ', trim($agent['nom_agent'] ?? ''));
        $agent_prenom = preg_replace('/\s+/', ' ', trim($agent['prenom_agent'] ?? ''));
        $agent_contact = preg_replace('/\s+/', ' ', trim($agent['contact'] ?? ''));
        $agent_chef = preg_replace('/\s+/', ' ', trim($agent['chef_equipe'] ?? ''));
        
        // Normaliser aussi les termes de recherche
        $search_nom_clean = preg_replace('/\s+/', ' ', $search_nom);
        $search_prenom_clean = preg_replace('/\s+/', ' ', $search_prenom);
        $search_contact_clean = preg_replace('/\s+/', ' ', $search_contact);
        $search_chef_clean = preg_replace('/\s+/', ' ', $search_chef);
        
        // Recherche insensible à la casse et aux espaces multiples
        $match_nom = empty($search_nom_clean) || stripos($agent_nom, $search_nom_clean) !== false;
        $match_prenom = empty($search_prenom_clean) || stripos($agent_prenom, $search_prenom_clean) !== false;
        $match_contact = empty($search_contact_clean) || stripos($agent_contact, $search_contact_clean) !== false;
        $match_chef = empty($search_chef_clean) || stripos($agent_chef, $search_chef_clean) !== false;
        
        return $match_nom && $match_prenom && $match_contact && $match_chef;
    });
}

// Pagination des agents filtrés
$agent_pages = array_chunk($agents_filtered, $limit);
$agents_list = $agent_pages[$page - 1] ?? [];
$total_agents_filtered = count($agents_filtered);

// Calculer les statistiques
$total_agents = count($agents);
$total_chefs = count($chefs);

// Fonction pour générer les paramètres URL avec filtres
function buildUrlParams($page, $limit, $search_nom, $search_prenom, $search_contact, $search_chef) {
    $params = [
        'page' => $page,
        'limit' => $limit
    ];
    
    if (!empty($search_nom)) $params['search_nom'] = $search_nom;
    if (!empty($search_prenom)) $params['search_prenom'] = $search_prenom;
    if (!empty($search_contact)) $params['search_contact'] = $search_contact;
    if (!empty($search_chef)) $params['search_chef'] = $search_chef;
    
    return http_build_query($params);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Agents - UniPalm</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --accent-color: #f093fb;
            --success-color: #4facfe;
            --warning-color: #f6d365;
            --danger-color: #ff6b6b;
            --dark-color: #2c3e50;
            --light-color: #ffffff;
            --glass-bg: rgba(255, 255, 255, 0.25);
            --glass-border: rgba(255, 255, 255, 0.18);
            --shadow-light: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            --shadow-dark: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(-45deg, #667eea, #764ba2, #f093fb, #4facfe);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            min-height: 100vh;
            overflow-x: hidden;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Floating particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        /* Main container */
        .main-container {
            position: relative;
            z-index: 2;
            padding: 2rem;
            animation: fadeInUp 0.8s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Header section */
        .page-header {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-light);
            animation: slideInDown 0.6s ease-out;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .page-title {
            font-family: 'Poppins', sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
            text-align: center;
        }

        .agent-count {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            color: white;
            border-radius: 25px;
            padding: 0.3rem 0.8rem;
            font-size: 0.7em;
            font-weight: 600;
            margin-left: 0.5rem;
            display: inline-block;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .page-subtitle {
            text-align: center;
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
        }

        /* Breadcrumb */
        .breadcrumb-modern {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .breadcrumb-modern a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .breadcrumb-modern a:hover {
            color: white;
        }

        /* Action buttons */
        .actions-container {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-light);
            animation: slideInLeft 0.6s ease-out;
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .btn-modern {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 15px;
            padding: 0.75rem 1.5rem;
            color: white;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            margin: 0.25rem;
            position: relative;
            overflow: hidden;
        }

        .btn-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-modern:hover::before {
            left: 100%;
        }

        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            color: white;
        }

        .btn-success-modern {
            background: linear-gradient(135deg, var(--success-color), #00d2ff);
        }

        .btn-danger-modern {
            background: linear-gradient(135deg, var(--danger-color), #ff8a80);
        }

        .btn-warning-modern {
            background: linear-gradient(135deg, var(--warning-color), #ffa726);
        }

        /* Table container */
        .table-container {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 2rem;
            margin: 2rem 0;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            animation: slideInUp 0.6s ease-out;
            max-height: 350px; /* Hauteur maximale du conteneur encore réduite */
            overflow: hidden;
        }

        .table-responsive {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            max-height: 250px; /* Hauteur maximale du tableau encore réduite */
            overflow-y: auto; /* Scroll vertical */
            scrollbar-width: thin; /* Pour Firefox */
            scrollbar-color: var(--primary-color) rgba(255, 255, 255, 0.1); /* Pour Firefox */
        }
        
        /* Styles pour WebKit (Chrome, Safari, Edge) */
        .table-responsive::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        .table-responsive::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }
        
        .table-responsive::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 4px;
            transition: background 0.3s ease;
        }
        
        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: var(--secondary-color);
        }

        .table-modern {
            background: transparent;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: none;
        }

.table-responsive::-webkit-scrollbar-track {
background: rgba(255, 255, 255, 0.1);
border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb {
background: var(--primary-color);
border-radius: 4px;
transition: background 0.3s ease;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
background: var(--secondary-color);
}
        .table-modern tbody tr:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.01);
        }

        .table-modern tbody td {
            border: none;
            padding: 1rem;
            color: #2c3e50;
            text-align: center;
            vertical-align: middle;
        }

        /* Action buttons in table */
        .action-btn {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 0.25rem;
            transition: all 0.3s ease;
            color: white;
        }

        .action-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .btn-edit {
            background: linear-gradient(135deg, #4facfe, #00f2fe);
        }

        .btn-delete {
            background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
        }

        /* Pagination */
        .pagination-container {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: var(--shadow-light);
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .pagination-modern {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .pagination-btn {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 10px;
            padding: 0.5rem 1rem;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .pagination-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            color: white;
        }

        .pagination-info {
            color: white;
            font-weight: 500;
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }

        /* Modal improvements */
        .modal-content {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            box-shadow: var(--shadow-dark);
        }

        .modal-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px 20px 0 0;
        }

        .modal-title {
            color: white;
            font-weight: 600;
        }

        .modal-body {
            color: white;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            color: #2c3e50;
            padding: 0.75rem;
        }

        /* Assurer que les selects restent des selects */
        select.form-control {
            appearance: auto;
            -webkit-appearance: menulist;
            -moz-appearance: menulist;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.95);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            color: #2c3e50;
        }

        .form-control::placeholder {
            color: rgba(0, 0, 0, 0.5);
        }

        .form-label {
            color: white;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }


        /* Alert styling */
        .alert {
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }

        /* Search container styling */
        .search-container {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 1.5rem; /* Réduit de 2rem à 1.5rem */
            margin: 1.5rem 0; /* Réduit de 2rem à 1.5rem */
            box-shadow: var(--shadow-light);
            animation: slideInUp 0.6s ease-out;
        }

        .search-form {
            margin-bottom: 1rem; /* Réduit de 1.5rem à 1rem */
        }

        .search-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem; /* Réduit de 1.5rem à 1rem */
            margin-bottom: 1.5rem; /* Réduit de 2rem à 1.5rem */
        }

        .search-field {
            display: flex;
            flex-direction: column;
        }

        .search-label {
            color: #2c3e50;
            font-weight: 500;
            margin-bottom: 0.5rem;
            font-family: 'Poppins', sans-serif;
        }

        .search-input {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            color: #2c3e50;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            background: rgba(255, 255, 255, 0.95);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            outline: none;
            transform: translateY(-2px);
        }

        .search-input::placeholder {
            color: rgba(0, 0, 0, 0.5);
        }

        .search-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-search {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
        }

        .btn-search:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-reset {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
        }

        .btn-reset:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(108, 117, 125, 0.4);
            color: white;
        }

        .search-results {
            background: rgba(79, 172, 254, 0.1);
            border: 1px solid rgba(79, 172, 254, 0.3);
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 1.5rem;
        }

        .results-info {
            color: #2c3e50;
            font-size: 1.1rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }

        .active-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
        }

        .filter-label {
            color: #2c3e50;
            font-weight: 600;
            margin-right: 0.5rem;
        }

        .filter-badge {
            background: linear-gradient(135deg, var(--accent-color), #ff9a9e);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            
            .table-responsive {
                border-radius: 15px;
            }
            
            .actions-container {
                text-align: center;
            }
            
            .btn-modern {
                width: 100%;
                margin: 0.25rem 0;
                justify-content: center;
            }
        }

        /* Loading animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Ripple effect */
        .ripple {
            position: relative;
            overflow: hidden;
        }

        .ripple::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .ripple:active::before {
            width: 300px;
            height: 300px;
        }

        /* Avatar circle */
        .avatar-circle {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* Badge styles */
        .badge {
            padding: 0.5rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
        }

        .bg-info {
            background: linear-gradient(135deg, var(--success-color), #00d2ff) !important;
        }
    </style>
</head>
<body>
    <!-- Floating particles -->
    <div class="particles"></div>

    <!-- Main container -->
    <div class="main-container">
        <!-- Page header -->
        <div class="page-header">
            <div class="breadcrumb-modern">
                <a href="dashboard.php"><i class="fas fa-home"></i> Accueil</a>
                <i class="fas fa-chevron-right"></i>
                <span>Gestion des Agents</span>
            </div>
            
            <h1 class="page-title">
                <i class="fas fa-users"></i> Gestion des Agents 
                <span class="agent-count">(<?= $total_agents ?>)</span>
            </h1>
            <p class="page-subtitle">
                Gérez efficacement vos agents et leurs informations
            </p>
        </div>


        <!-- Action buttons -->
        <div class="actions-container">
            <button type="button" class="btn-modern ripple" data-bs-toggle="modal" data-bs-target="#add-agent">
                <i class="fas fa-user-plus"></i>
                Enregistrer un agent
            </button>

            <button type="button" class="btn-modern btn-danger-modern ripple" data-bs-toggle="modal" data-bs-target="#add-point">
                <i class="fas fa-print"></i>
                Imprimer un agent
            </button>

            <button type="button" class="btn-modern btn-success-modern ripple" data-bs-toggle="modal" data-bs-target="#search-commande">
                <i class="fas fa-search"></i>
                Rechercher un agent
            </button>

            <button type="button" class="btn-modern btn-warning-modern ripple" onclick="window.location.href='export_commandes.php'">
                <i class="fas fa-file-export"></i>
                Exporter la liste
            </button>
        </div>

        <!-- Filtres de recherche -->
        <div class="search-container">
            <h3 style="color: #2c3e50; margin-bottom: 1rem; font-family: 'Poppins', sans-serif;">
                <i class="fas fa-filter"></i> Filtres de Recherche
            </h3>
            
            <form method="GET" class="search-form">
                <!-- Préserver les paramètres de pagination -->
                <input type="hidden" name="page" value="1">
                <input type="hidden" name="limit" value="<?= $limit ?>">
                
                <div class="search-grid">
                    <div class="search-field">
                        <label for="search_nom" class="search-label">
                            <i class="fas fa-user me-2"></i>Nom
                        </label>
                        <input type="text" 
                               id="search_nom" 
                               name="search_nom" 
                               class="search-input" 
                               placeholder="Rechercher par nom..."
                               value="<?= htmlspecialchars($search_nom) ?>">
                    </div>
                    
                    <div class="search-field">
                        <label for="search_prenom" class="search-label">
                            <i class="fas fa-user me-2"></i>Prénom
                        </label>
                        <input type="text" 
                               id="search_prenom" 
                               name="search_prenom" 
                               class="search-input" 
                               placeholder="Rechercher par prénom..."
                               value="<?= htmlspecialchars($search_prenom) ?>">
                    </div>
                    
                    <div class="search-field">
                        <label for="search_contact" class="search-label">
                            <i class="fas fa-phone me-2"></i>Contact
                        </label>
                        <input type="text" 
                               id="search_contact" 
                               name="search_contact" 
                               class="search-input" 
                               placeholder="Rechercher par contact..."
                               value="<?= htmlspecialchars($search_contact) ?>">
                    </div>
                    
                    <div class="search-field">
                        <label for="search_chef" class="search-label">
                            <i class="fas fa-user-tie me-2"></i>Chef d'Équipe
                        </label>
                        <input type="text" 
                               id="search_chef" 
                               name="search_chef" 
                               class="search-input" 
                               placeholder="Rechercher par chef..."
                               value="<?= htmlspecialchars($search_chef) ?>">
                    </div>
                </div>
                
                <div class="search-actions">
                    <button type="submit" class="btn-search ripple">
                        <i class="fas fa-search me-2"></i>Rechercher
                    </button>
                    <a href="agents.php" class="btn-reset ripple">
                        <i class="fas fa-times me-2"></i>Réinitialiser
                    </a>
                </div>
            </form>
            
            <!-- Affichage des résultats -->
            <?php if (!empty($search_nom) || !empty($search_prenom) || !empty($search_contact) || !empty($search_chef)): ?>
                <div class="search-results">
                    <div class="results-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong><?= $total_agents_filtered ?></strong> agent(s) trouvé(s) sur <?= $total_agents ?> au total
                    </div>
                    
                    <div class="active-filters">
                        <span class="filter-label">Filtres actifs :</span>
                        <?php if (!empty($search_nom)): ?>
                            <span class="filter-badge">Nom: <?= htmlspecialchars($search_nom) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($search_prenom)): ?>
                            <span class="filter-badge">Prénom: <?= htmlspecialchars($search_prenom) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($search_contact)): ?>
                            <span class="filter-badge">Contact: <?= htmlspecialchars($search_contact) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($search_chef)): ?>
                            <span class="filter-badge">Chef: <?= htmlspecialchars($search_chef) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Agents table -->
        <div class="table-container">
            <h3 style="color: #2c3e50; margin-bottom: 1.5rem; font-family: 'Poppins', sans-serif;">
                <i class="fas fa-list"></i> Liste des Agents
            </h3>
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th><i class="fas fa-id-card"></i> N° Agent</th>
                            <th><i class="fas fa-user"></i> Nom</th>
                            <th><i class="fas fa-user"></i> Prénom</th>
                            <th><i class="fas fa-phone"></i> Contact</th>
                            <th><i class="fas fa-user-tie"></i> Chef d'Équipe</th>
                            <th><i class="fas fa-calendar"></i> Date de Création</th>
                            <th><i class="fas fa-user-plus"></i> Ajouté par</th>
                            <th><i class="fas fa-cogs"></i> Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($agents_list)): ?>
                            <?php foreach ($agents_list as $agent) : ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-primary">
                                            <i class="fas fa-id-card me-1"></i>
                                            <?= htmlspecialchars($agent['numero_agent'] ?? 'N/A') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle me-2">
                                                <?= strtoupper(substr($agent['nom_agent'], 0, 1)) ?>
                                            </div>
                                            <?= htmlspecialchars($agent['nom_agent']) ?>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($agent['prenom_agent']) ?></td>
                                    <td>
                                        <span class="badge bg-info">
                                            <i class="fas fa-phone me-1"></i>
                                            <?= htmlspecialchars($agent['contact']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($agent['chef_equipe']) ?></td>
                                    <td>
                                        <small class="text-dark">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?= date('d/m/Y', strtotime($agent['date_ajout'])) ?>
                                        </small>
                                    </td>
                                    <td><?= htmlspecialchars($agent['utilisateur_createur']) ?></td>
                                    <td>
                                        <button class="action-btn btn-edit" data-bs-toggle="modal" data-bs-target="#modifier<?= $agent['id_agent'] ?>" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="action-btn btn-warning" data-bs-toggle="modal" data-bs-target="#changeChef<?= $agent['id_agent'] ?>" title="Changer Chef d'Équipe" style="background: linear-gradient(135deg, #f6d365, #ffa726);">
                                            <i class="fas fa-user-tie"></i>
                                        </button>
                                        <button class="action-btn btn-delete" onclick="confirmDelete(<?= $agent['id_agent'] ?>)" title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">
                                    <div class="py-4">
                                        <i class="fas fa-users fa-3x mb-3" style="color: rgba(255,255,255,0.3);"></i>
                                        <p class="mb-0">Aucun agent trouvé</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if (!empty($agent_pages) && count($agent_pages) > 1): ?>
        <div class="pagination-container">
            <div class="pagination-modern">
                <?php if($page > 1): ?>
                    <a href="?<?= buildUrlParams($page - 1, $limit, $search_nom, $search_prenom, $search_contact, $search_chef) ?>" class="pagination-btn">
                        <i class="fas fa-chevron-left"></i> Précédent
                    </a>
                <?php endif; ?>
                
                <div class="pagination-info">
                    Page <?= $page ?> sur <?= count($agent_pages) ?>
                </div>

                <?php if($page < count($agent_pages)): ?>
                    <a href="?<?= buildUrlParams($page + 1, $limit, $search_nom, $search_prenom, $search_contact, $search_chef) ?>" class="pagination-btn">
                        Suivant <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
                
                <form method="get" class="d-flex align-items-center gap-2">
                    <!-- Préserver les filtres de recherche -->
                    <input type="hidden" name="page" value="1">
                    <?php if (!empty($search_nom)): ?>
                        <input type="hidden" name="search_nom" value="<?= htmlspecialchars($search_nom) ?>">
                    <?php endif; ?>
                    <?php if (!empty($search_prenom)): ?>
                        <input type="hidden" name="search_prenom" value="<?= htmlspecialchars($search_prenom) ?>">
                    <?php endif; ?>
                    <?php if (!empty($search_contact)): ?>
                        <input type="hidden" name="search_contact" value="<?= htmlspecialchars($search_contact) ?>">
                    <?php endif; ?>
                    <?php if (!empty($search_chef)): ?>
                        <input type="hidden" name="search_chef" value="<?= htmlspecialchars($search_chef) ?>">
                    <?php endif; ?>
                    
                    <label for="limit" class="text-white">Afficher :</label>
                    <select name="limit" id="limit" class="form-control" style="width: auto;" onchange="this.form.submit()">
                        <option value="5" <?= $limit == 5 ? 'selected' : '' ?>>5</option>
                        <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10</option>
                        <option value="15" <?= $limit == 15 ? 'selected' : '' ?>>15</option>
                        <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25</option>
                    </select>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modales de modification pour chaque agent -->
    <?php foreach ($agents_list as $agent) : ?>
        <div class="modal fade" id="modifier<?= $agent['id_agent'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $agent['id_agent'] ?>" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel<?= $agent['id_agent'] ?>">
                            <i class="fas fa-user-edit me-2"></i>
                            Modification Agent <?= htmlspecialchars($agent['nom_agent']) ?> <?= htmlspecialchars($agent['prenom_agent']) ?>
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form class="forms-sample" method="post" action="traitement_agents.php">
                            <input type="hidden" name="id_agent" value="<?= $agent['id_agent'] ?>">
                            <input type="hidden" name="update_agent" value="1">
                            
                            <div class="mb-3">
                                <label for="nom<?= $agent['id_agent'] ?>" class="form-label">
                                    <i class="fas fa-user me-2"></i>Nom
                                </label>
                                <input type="text" class="form-control" id="nom<?= $agent['id_agent'] ?>" name="nom" 
                                       value="<?= htmlspecialchars($agent['nom_agent']) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="prenoms<?= $agent['id_agent'] ?>" class="form-label">
                                    <i class="fas fa-user me-2"></i>Prénoms
                                </label>
                                <input type="text" class="form-control" id="prenoms<?= $agent['id_agent'] ?>" name="prenoms" 
                                       value="<?= htmlspecialchars($agent['prenom_agent']) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="contact<?= $agent['id_agent'] ?>" class="form-label">
                                    <i class="fas fa-phone me-2"></i>Contact
                                </label>
                                <input type="text" class="form-control" id="contact<?= $agent['id_agent'] ?>" name="contact" 
                                       value="<?= htmlspecialchars($agent['contact']) ?>" required>
                            </div>
                            
                            <div class="modal-footer border-0">
                                <button type="submit" class="btn-modern ripple">
                                    <i class="fas fa-save me-2"></i>Enregistrer
                                </button>
                                <button type="button" class="btn-modern btn-danger-modern ripple" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-2"></i>Annuler
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Modales de changement de chef d'équipe pour chaque agent -->
    <?php foreach ($agents_list as $agent) : ?>
        <div class="modal fade" id="changeChef<?= $agent['id_agent'] ?>" tabindex="-1" aria-labelledby="changeChefModalLabel<?= $agent['id_agent'] ?>" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="changeChefModalLabel<?= $agent['id_agent'] ?>">
                            <i class="fas fa-user-tie me-2"></i>
                            Changer Chef d'Équipe - <?= htmlspecialchars($agent['nom_agent']) ?> <?= htmlspecialchars($agent['prenom_agent']) ?>
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info d-flex align-items-center mb-3" style="background: rgba(79, 172, 254, 0.1); border: 1px solid rgba(79, 172, 254, 0.3); color: white;">
                            <i class="fas fa-info-circle me-2"></i>
                            <div>
                                <strong>Chef actuel :</strong> <?= htmlspecialchars($agent['chef_equipe']) ?>
                            </div>
                        </div>
                        
                        <form class="forms-sample" method="post" action="traitement_agents.php">
                            <input type="hidden" name="id_agent" value="<?= $agent['id_agent'] ?>">
                            <input type="hidden" name="change_chef" value="1">
                            
                            <div class="mb-3">
                                <label for="nouveau_chef<?= $agent['id_agent'] ?>" class="form-label">
                                    <i class="fas fa-user-tie me-2"></i>Nouveau Chef d'Équipe
                                </label>
                                <select id="nouveau_chef<?= $agent['id_agent'] ?>" name="nouveau_chef" class="form-control" required>
                                    <option value="">Sélectionner un nouveau chef d'équipe</option>
                                    <?php if (!empty($chefs)): ?>
                                        <?php foreach ($chefs as $chef): ?>
                                            <option value="<?= htmlspecialchars($chef['id_chef']) ?>">
                                                <?= htmlspecialchars($chef['chef_nom_complet']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="">Aucun chef d'équipe disponible</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            
                            <div class="modal-footer border-0">
                                <button type="submit" class="btn-modern ripple" style="background: linear-gradient(135deg, #f6d365, #ffa726);">
                                    <i class="fas fa-exchange-alt me-2"></i>Changer Chef
                                </button>
                                <button type="button" class="btn-modern btn-danger-modern ripple" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-2"></i>Annuler
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    
    <!-- Modale d'ajout d'agent - STYLE ORIGINAL UNIPALM -->
    <div class="modal fade" id="add-agent" tabindex="-1" aria-labelledby="addAgentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAgentModalLabel">
                        <i class="fas fa-user-plus me-2"></i>Enregistrer un nouvel agent
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form class="forms-sample" method="post" action="traitement_agents.php">
                        <div class="mb-3">
                            <label for="nom" class="form-label">
                                <i class="fas fa-user me-2"></i>Nom
                            </label>
                            <input type="text" class="form-control" id="nom" name="nom" placeholder="Nom de l'agent" required 
                                   style="background: rgba(255, 255, 255, 0.9) !important; color: #2c3e50 !important;">
                        </div>

                        <div class="mb-3">
                            <label for="prenom" class="form-label">
                                <i class="fas fa-user me-2"></i>Prénoms
                            </label>
                            <input type="text" class="form-control" id="prenom" name="prenom" placeholder="Prénoms de l'agent" required
                                   style="background: rgba(255, 255, 255, 0.9) !important; color: #2c3e50 !important;">
                        </div>

                        <div class="mb-3">
                            <label for="contact" class="form-label">
                                <i class="fas fa-phone me-2"></i>Contact
                            </label>
                            <input type="text" class="form-control" id="contact" name="contact" placeholder="Numéro de téléphone" required
                                   style="background: rgba(255, 255, 255, 0.9) !important; color: #2c3e50 !important;">
                        </div>

                        <div class="mb-3">
                            <label for="id_chef" class="form-label">
                                <i class="fas fa-user-tie me-2"></i>Chef d'Équipe
                            </label>
                            <select id="id_chef" name="id_chef" class="form-control" required 
                                    style="background: rgba(255, 255, 255, 0.9) !important; color: #2c3e50 !important; appearance: auto !important; -webkit-appearance: menulist !important;">
                                <option value="">Sélectionner un chef d'équipe</option>
                                <?php if (!empty($chefs)): ?>
                                    <?php foreach ($chefs as $chef): ?>
                                        <option value="<?= htmlspecialchars($chef['id_chef']) ?>">
                                            <?= htmlspecialchars($chef['chef_nom_complet']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="">Aucun chef d'équipe disponible</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- Champ caché pour s'assurer que add_agent est envoyé -->
                        <input type="hidden" name="add_agent" value="1">

                        <div class="modal-footer border-0">
                            <button type="submit" class="btn-modern ripple" name="add_agent" value="1">
                                <i class="fas fa-save me-2"></i>Enregistrer
                            </button>
                            <button type="button" class="btn-modern btn-danger-modern ripple" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Annuler
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Create floating particles
        function createParticles() {
            const particles = document.querySelector('.particles');
            const particleCount = 50;

            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.top = Math.random() * 100 + '%';
                particle.style.width = Math.random() * 4 + 2 + 'px';
                particle.style.height = particle.style.width;
                particle.style.animationDelay = Math.random() * 6 + 's';
                particle.style.animationDuration = (Math.random() * 3 + 3) + 's';
                particles.appendChild(particle);
            }
        }

        // Initialize particles on page load
        document.addEventListener('DOMContentLoaded', function() {
            createParticles();
            
            // Add entrance animations with stagger
            const elements = document.querySelectorAll('.actions-container, .table-container');
            elements.forEach((el, index) => {
                el.style.animationDelay = (index * 0.1) + 's';
            });

            // Add ripple effect to buttons
            document.querySelectorAll('.ripple').forEach(button => {
                button.addEventListener('click', function(e) {
                    const ripple = document.createElement('span');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;
                    
                    ripple.style.width = ripple.style.height = size + 'px';
                    ripple.style.left = x + 'px';
                    ripple.style.top = y + 'px';
                    ripple.classList.add('ripple-effect');
                    
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });

            // Add scroll animations
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            document.querySelectorAll('.table-container').forEach(el => {
                observer.observe(el);
            });
        });

        // Enhanced delete confirmation
        function confirmDelete(id) {
            Swal.fire({
                title: 'Êtes-vous sûr ?',
                text: "Cette action est irréversible !",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff6b6b',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-trash me-2"></i>Oui, supprimer',
                cancelButtonText: '<i class="fas fa-times me-2"></i>Annuler',
                background: 'rgba(255, 255, 255, 0.95)',
                backdrop: 'rgba(0, 0, 0, 0.8)',
                customClass: {
                    popup: 'border-0 shadow-lg',
                    title: 'text-dark fw-bold',
                    content: 'text-dark'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Créer et soumettre un formulaire caché
                    console.log('Suppression de l\'agent ID:', id);
                    
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'traitement_agents.php';
                    
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'delete';
                    
                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'id_agent';
                    idInput.value = id;
                    
                    form.appendChild(actionInput);
                    form.appendChild(idInput);
                    document.body.appendChild(form);
                    
                    console.log('Soumission du formulaire de suppression');
                    form.submit();
                }
            });
        }

        // Form validation enhancement
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.innerHTML = '<div class="loading me-2"></div>Traitement...';
                    submitBtn.disabled = true;
                }
            });
        });


        // Add CSS for ripple effect
        const style = document.createElement('style');
        style.textContent = `
            .ripple-effect {
                position: absolute;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.6);
                transform: scale(0);
                animation: ripple-animation 0.6s linear;
                pointer-events: none;
            }
            
            @keyframes ripple-animation {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
    <?php if (isset($_SESSION['popup']) && $_SESSION['popup'] == true): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Play notification sound
            const audio = new Audio("../inc/sons/notification.mp3");
            audio.volume = 1.0;
            audio.play().catch(error => console.log('Audio play failed:', error));

            // Show modern success toast
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: '✅ Action effectuée avec succès !',
                showConfirmButton: false,
                timer: 4000,
                timerProgressBar: true,
                background: 'linear-gradient(135deg, #4facfe, #00f2fe)',
                color: 'white',
                customClass: {
                    popup: 'border-0 shadow-lg'
                }
            });
        });
    </script>
    <?php $_SESSION['popup'] = false; endif; ?>

    <?php if (isset($_SESSION['delete_pop']) && $_SESSION['delete_pop'] == true): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: '❌ Action échouée !',
                showConfirmButton: false,
                timer: 4000,
                timerProgressBar: true,
                background: 'linear-gradient(135deg, #ff6b6b, #ff8e8e)',
                color: 'white',
                customClass: {
                    popup: 'border-0 shadow-lg'
                }
            });
        });
    </script>
    <?php $_SESSION['delete_pop'] = false; endif; ?>


</body>
</html>