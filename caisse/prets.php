<?php
require_once '../inc/functions/connexion.php';
include('header_caisse.php');

$id_user = $_SESSION['user_id'];

// Filtres de recherche
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$agent_id = isset($_GET['agent_id']) ? trim($_GET['agent_id']) : '';
$date_debut = isset($_GET['date_debut']) ? trim($_GET['date_debut']) : '';
$date_fin = isset($_GET['date_fin']) ? trim($_GET['date_fin']) : '';
$statut = isset($_GET['statut']) ? trim($_GET['statut']) : '';

// Liste complète des agents
$sql_agents_all = "SELECT id_agent, CONCAT(nom, ' ', prenom) AS nom_agent FROM agents ORDER BY nom, prenom";
$stmt_agents_all = $conn->prepare($sql_agents_all);
$stmt_agents_all->execute();
$agents_all = $stmt_agents_all->fetchAll(PDO::FETCH_ASSOC);

// Résumé des prêts par agent avec filtres
$sql_agents_prets = "SELECT 
    a.id_agent,
    CONCAT(a.nom, ' ', a.prenom) AS nom_agent,
    SUM(p.montant_initial) AS montant_initial,
    SUM(p.montant_initial - p.montant_restant) AS montant_rembourse,
    SUM(p.montant_restant) AS solde_restant,
    COUNT(p.id_pret) AS nombre_prets
FROM agents a
INNER JOIN prets p ON a.id_agent = p.id_agent";

$params_totaux = [];
$conditions_totaux = [];

// Filtre agent pour le résumé
if ($agent_id !== '') {
    $conditions_totaux[] = 'a.id_agent = :agent_id_tot';
    $params_totaux[':agent_id_tot'] = $agent_id;
}

// Filtres de période sur date_octroi pour le résumé
if ($date_debut !== '' && $date_fin !== '') {
    $conditions_totaux[] = 'DATE(p.date_octroi) BETWEEN :date_debut_tot AND :date_fin_tot';
    $params_totaux[':date_debut_tot'] = $date_debut;
    $params_totaux[':date_fin_tot'] = $date_fin;
} elseif ($date_debut !== '') {
    $conditions_totaux[] = 'DATE(p.date_octroi) >= :date_debut_tot';
    $params_totaux[':date_debut_tot'] = $date_debut;
} elseif ($date_fin !== '') {
    $conditions_totaux[] = 'DATE(p.date_octroi) <= :date_fin_tot';
    $params_totaux[':date_fin_tot'] = $date_fin;
}

// Filtre statut
if ($statut !== '') {
    $conditions_totaux[] = 'p.statut = :statut_tot';
    $params_totaux[':statut_tot'] = $statut;
}

if (!empty($conditions_totaux)) {
    $sql_agents_prets .= ' WHERE ' . implode(' AND ', $conditions_totaux);
}

$sql_agents_prets .= ' GROUP BY a.id_agent, a.nom, a.prenom ORDER BY solde_restant DESC, a.nom, a.prenom';

$stmt_agents_prets = $conn->prepare($sql_agents_prets);
$stmt_agents_prets->execute($params_totaux);
$agents_prets_all = $stmt_agents_prets->fetchAll(PDO::FETCH_ASSOC);

// Pagination pour le tableau des agents
$elements_per_page = 15;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$total_agents = count($agents_prets_all);
$total_pages = ceil($total_agents / $elements_per_page);
$offset = ($page - 1) * $elements_per_page;

// Agents pour la page actuelle
$agents_prets = array_slice($agents_prets_all, $offset, $elements_per_page);

// Liste détaillée de tous les prêts avec filtres
$sql_prets = "SELECT p.*, CONCAT(a.nom, ' ', a.prenom) AS nom_agent
              FROM prets p
              INNER JOIN agents a ON p.id_agent = a.id_agent";

$params = [];
$conditions = [];

// Filtre texte global (ID prêt, agent, motif)
if ($search !== '') {
    $conditions[] = "(p.id_pret LIKE :search1 
                    OR CONCAT(a.nom, ' ', a.prenom) LIKE :search2 
                    OR p.motif LIKE :search3)";
    $params[':search1'] = '%' . $search . '%';
    $params[':search2'] = '%' . $search . '%';
    $params[':search3'] = '%' . $search . '%';
}

// Filtre agent explicite
if ($agent_id !== '') {
    $conditions[] = "a.id_agent = :agent_id";
    $params[':agent_id'] = $agent_id;
}

// Filtres de période sur date_octroi
if ($date_debut !== '' && $date_fin !== '') {
    $conditions[] = "DATE(p.date_octroi) BETWEEN :date_debut AND :date_fin";
    $params[':date_debut'] = $date_debut;
    $params[':date_fin'] = $date_fin;
} elseif ($date_debut !== '') {
    $conditions[] = "DATE(p.date_octroi) >= :date_debut";
    $params[':date_debut'] = $date_debut;
} elseif ($date_fin !== '') {
    $conditions[] = "DATE(p.date_octroi) <= :date_fin";
    $params[':date_fin'] = $date_fin;
}

// Filtre statut
if ($statut !== '') {
    $conditions[] = "p.statut = :statut";
    $params[':statut'] = $statut;
}

if (!empty($conditions)) {
    $sql_prets .= ' WHERE ' . implode(' AND ', $conditions);
}

$sql_prets .= " ORDER BY p.id_pret DESC";

$stmt_prets = $conn->prepare($sql_prets);
$stmt_prets->execute($params);
$prets = $stmt_prets->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Prêts - UniPalm</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --unipalm-primary: #667eea;
            --unipalm-secondary: #764ba2;
            --unipalm-success: #4facfe;
            --unipalm-warning: #f093fb;
            --unipalm-danger: #ff6b6b;
            --unipalm-info: #00d2ff;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(-45deg, #667eea, #764ba2, #f093fb, #4facfe);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            min-height: 100vh;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .main-container {
            padding: 2rem;
        }

        .page-header {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(18px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #fff;
            margin: 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .page-subtitle {
            color: rgba(255, 255, 255, 0.9);
            margin-top: 0.5rem;
            font-size: 1.1rem;
        }

        .stats-row {
            margin-bottom: 2rem;
        }

        .stats-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(16px);
            border-radius: 18px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
            height: 100%;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.2);
        }

        .stats-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .stats-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stats-label {
            font-size: 0.9rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .content-card {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(18px);
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        .filter-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(18px);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        .btn-unipalm {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-unipalm:hover {
            background: linear-gradient(135deg, #5a6fd8, #6a4190);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-success-modern {
            background: linear-gradient(135deg, #4facfe, #00d2ff);
            border: none;
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(79, 172, 254, 0.3);
        }

        .btn-success-modern:hover {
            background: linear-gradient(135deg, #3d8bfe, #00b8e6);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(79, 172, 254, 0.4);
            color: white;
        }

        .btn-outline-light {
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: #333;
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-outline-light:hover {
            background: rgba(255, 255, 255, 1);
            border-color: rgba(255, 255, 255, 0.8);
            color: #333;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 255, 255, 0.4);
        }

        /* Styles professionnels pour le tableau */
        .table-container {
            max-height: 600px;
            overflow-y: auto;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid #e9ecef;
            width: 100%;
        }

        .table-container::-webkit-scrollbar {
            width: 8px;
        }

        .table-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .table-container::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 4px;
        }

        .table-container::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #5a6fd8, #6a4190);
        }

        #tablePrets {
            margin-bottom: 0;
            border: none;
            width: 100%;
            table-layout: auto;
        }

        #tablePrets thead {
            position: sticky;
            top: 0;
            z-index: 10;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        #tablePrets thead th {
            border: none;
            padding: 18px 15px;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        #tablePrets tbody tr {
            transition: all 0.3s ease;
            border-bottom: 1px solid #f1f3f4;
        }

        #tablePrets tbody tr:hover {
            background-color: #f8f9ff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        }

        #tablePrets tbody td {
            padding: 16px 15px;
            border: none;
            vertical-align: middle;
            font-size: 14px;
        }

        /* Styles pour les boutons d'action */
        #tablePrets tbody td:last-child {
            white-space: nowrap;
            width: 140px;
            min-width: 140px;
        }

        #tablePrets .btn-sm {
            padding: 4px 8px;
            font-size: 12px;
            border-radius: 4px;
            min-width: 32px;
        }

        .badge-pret {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 12px;
        }

        .badge-en-cours {
            background: linear-gradient(135deg, #f093fb, #f5576c);
        }

        .badge-termine {
            background: linear-gradient(135deg, #4facfe, #00f2fe);
        }

        .badge-annule {
            background: linear-gradient(135deg, #ff6b6b, #ffa500);
        }

        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--unipalm-primary);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            background: rgba(255, 255, 255, 0.95);
        }

        .card-title {
            color: #fff;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .text-amount-positive {
            color: #28a745;
            font-weight: 600;
        }

        .text-amount-negative {
            color: #dc3545;
            font-weight: 600;
        }

        .text-amount-warning {
            color: #ffc107;
            font-weight: 600;
        }

        /* Styles pour la pagination */
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding: 15px 0;
        }

        .pagination-info {
            color: #6c757d;
            font-size: 14px;
        }

        .pagination {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 5px;
        }

        .pagination .page-item {
            display: inline-block;
        }

        .pagination .page-link {
            display: block;
            padding: 8px 12px;
            text-decoration: none;
            color: #667eea;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .pagination .page-link:hover {
            background-color: #667eea;
            color: white;
            border-color: #667eea;
            transform: translateY(-1px);
        }

        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-color: #667eea;
        }

        .pagination .page-item.disabled .page-link {
            color: #6c757d;
            background-color: #f8f9fa;
            border-color: #dee2e6;
            cursor: not-allowed;
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }

            .page-header {
                padding: 1.5rem;
            }

            .page-title {
                font-size: 2rem;
            }

            .stats-value {
                font-size: 1.5rem;
            }
            
            .pagination-container {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .pagination .page-link {
                padding: 6px 10px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>

<div class="main-container">
    <!-- En-tête -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title">
                    <i class="fas fa-hand-holding-usd me-3"></i>
                    Gestion des Prêts
                </h1>
                <p class="page-subtitle">
                    Suivi et gestion des prêts accordés aux agents
                </p>
            </div>
            <div>
                <button type="button" class="btn btn-success-modern" data-bs-toggle="modal" data-bs-target="#addPretModal">
                    <i class="fas fa-plus me-2"></i>Nouveau Prêt
                </button>
            </div>
        </div>
    </div>

    <!-- Messages de notification -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['warning'])): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?= htmlspecialchars($_SESSION['warning']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['warning']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-times-circle me-2"></i>
            <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Statistiques globales -->
    <?php
    $total_prets_global = 0;
    $total_remboursements_global = 0;
    $solde_global = 0;
    $nb_agents_avec_pret = 0;
    
    foreach ($agents_prets_all as $agent) {
        if ($agent['montant_initial'] > 0 || $agent['montant_rembourse'] > 0) {
            $nb_agents_avec_pret++;
        }
        $total_prets_global += $agent['montant_initial'];
        $total_remboursements_global += $agent['montant_rembourse'];
        $solde_global += $agent['solde_restant'];
    }
    ?>
    
    <div class="row stats-row">
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon text-primary">
                    <i class="fas fa-hand-holding-usd"></i>
                </div>
                <div class="stats-value text-primary">
                    <?= number_format($total_prets_global, 0, ',', ' ') ?>
                </div>
                <div class="stats-label">Total Prêts Accordés (FCFA)</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon text-success">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stats-value text-success">
                    <?= number_format($total_remboursements_global, 0, ',', ' ') ?>
                </div>
                <div class="stats-label">Total Remboursements (FCFA)</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon text-warning">
                    <i class="fas fa-balance-scale"></i>
                </div>
                <div class="stats-value text-warning">
                    <?= number_format($solde_global, 0, ',', ' ') ?>
                </div>
                <div class="stats-label">Solde Restant (FCFA)</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon text-info">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stats-value text-info">
                    <?= $nb_agents_avec_pret ?>
                </div>
                <div class="stats-label">Agents avec Prêt</div>
            </div>
        </div>
    </div>

    <!-- Filtres de recherche -->
    <div class="filter-card">
        <h5 class="card-title">
            <i class="fas fa-filter me-2"></i>Filtres de recherche
        </h5>
        <form method="get" class="row g-3">
            <div class="col-md-12 mb-3">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label text-white">Recherche générale</label>
                        <input type="text" name="search" class="form-control" placeholder="ID prêt, agent, motif..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label text-white">Agent spécifique</label>
                        <select name="agent_id" class="form-select">
                            <option value="">Tous les agents</option>
                            <?php foreach ($agents_all as $agent): ?>
                                <option value="<?= $agent['id_agent'] ?>" <?= $agent_id == $agent['id_agent'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($agent['nom_agent']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label text-white">Statut</label>
                        <select name="statut" class="form-select">
                            <option value="">Tous les statuts</option>
                            <option value="en_cours" <?= $statut == 'en_cours' ? 'selected' : '' ?>>En cours</option>
                            <option value="termine" <?= $statut == 'termine' ? 'selected' : '' ?>>Terminé</option>
                            <option value="annule" <?= $statut == 'annule' ? 'selected' : '' ?>>Annulé</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label text-white">Date début</label>
                        <input type="date" name="date_debut" class="form-control" value="<?= htmlspecialchars($date_debut) ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label text-white">Date fin</label>
                        <input type="date" name="date_fin" class="form-control" value="<?= htmlspecialchars($date_fin) ?>">
                    </div>
                </div>
            </div>
            
            <div class="col-md-12 d-flex justify-content-center gap-2">
                <button type="submit" class="btn btn-unipalm">
                    <i class="fas fa-search me-1"></i>Filtrer
                </button>
                <a href="prets.php" class="btn btn-outline-light">
                    <i class="fas fa-undo me-1"></i>Réinitialiser
                </a>
                <button type="button" class="btn btn-success-modern" data-bs-toggle="modal" data-bs-target="#addPretModal">
                    <i class="fas fa-plus me-1"></i>Ajouter un prêt
                </button>
            </div>
        </form>
    </div>

    <!-- Résumé par agent -->
    <div class="content-card">
        <h5 class="card-title">
            <i class="fas fa-chart-pie me-2"></i>Résumé des prêts par agent
        </h5>
        
        <div class="table-container">
            <table id="tableResume" class="table table-striped">
                <thead>
                    <tr>
                        <th><i class="fas fa-user me-2"></i>Agent</th>
                        <th class="text-center"><i class="fas fa-hashtag me-2"></i>Nombre de prêts</th>
                        <th class="text-end"><i class="fas fa-plus-circle me-2"></i>Montant accordé</th>
                        <th class="text-end"><i class="fas fa-minus-circle me-2"></i>Déjà remboursé</th>
                        <th class="text-end"><i class="fas fa-balance-scale me-2"></i>Solde restant</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($agents_prets)): ?>
                        <?php foreach ($agents_prets as $agent): ?>
                        <tr>
                            <td>
                                <a href="details_prets.php?id=<?= $agent['id_agent'] ?>" class="text-decoration-none">
                                    <i class="fas fa-user me-2 text-primary"></i>
                                    <strong><?= htmlspecialchars($agent['nom_agent']) ?></strong>
                                </a>
                            </td>
                            <td class="text-center">
                                <span class="badge-pret"><?= $agent['nombre_prets'] ?></span>
                            </td>
                            <td class="text-end text-amount-positive">
                                <?= number_format($agent['montant_initial'], 0, ',', ' ') ?> FCFA
                            </td>
                            <td class="text-end text-amount-negative">
                                <?= number_format($agent['montant_rembourse'], 0, ',', ' ') ?> FCFA
                            </td>
                            <td class="text-end">
                                <strong class="<?= $agent['solde_restant'] > 0 ? 'text-amount-warning' : 'text-amount-positive' ?>">
                                    <?= number_format($agent['solde_restant'], 0, ',', ' ') ?> FCFA
                                </strong>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                <i class="fas fa-hand-holding-usd fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">Aucun prêt trouvé avec les critères sélectionnés.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination-container">
            <div class="pagination-info">
                Affichage de <?= $offset + 1 ?> à <?= min($offset + $elements_per_page, $total_agents) ?> sur <?= $total_agents ?> agent(s)
            </div>
            
            <nav aria-label="Navigation des pages">
                <ul class="pagination">
                    <!-- Bouton Précédent -->
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => max(1, $page - 1)])) ?>" aria-label="Précédent">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    
                    <?php
                    // Calcul des pages à afficher
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    // Première page
                    if ($start_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">1</a>
                        </li>
                        <?php if ($start_page > 2): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif;
                    endif;
                    
                    // Pages autour de la page actuelle
                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor;
                    
                    // Dernière page
                    if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>"><?= $total_pages ?></a>
                        </li>
                    <?php endif; ?>
                    
                    <!-- Bouton Suivant -->
                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => min($total_pages, $page + 1)])) ?>" aria-label="Suivant">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>

    <!-- Liste détaillée des prêts -->
    <div class="content-card">
        <h5 class="card-title">
            <i class="fas fa-list me-2"></i>Liste détaillée des prêts
        </h5>
        
        <div class="table-container">
            <table id="tablePrets" class="table table-striped">
                <thead>
                    <tr>
                        <th><i class="fas fa-hashtag me-2"></i>ID Prêt</th>
                        <th><i class="fas fa-user me-2"></i>Agent</th>
                        <th><i class="fas fa-calendar me-2"></i>Date octroi</th>
                        <th class="text-end"><i class="fas fa-money-bill me-2"></i>Montant initial</th>
                        <th class="text-end"><i class="fas fa-coins me-2"></i>Montant payé</th>
                        <th class="text-end"><i class="fas fa-balance-scale me-2"></i>Montant restant</th>
                        <th><i class="fas fa-calendar-check me-2"></i>Échéance</th>
                        <th><i class="fas fa-info-circle me-2"></i>Statut</th>
                        <th><i class="fas fa-comment me-2"></i>Motif</th>
                        <th class="text-center"><i class="fas fa-cogs me-2"></i>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($prets)): ?>
                        <?php foreach ($prets as $pret): ?>
                        <?php
                            $montantInitial = (float)$pret['montant_initial'];
                            $montantRestant = (float)($pret['montant_restant'] ?? 0);
                            $montantPaye = max($montantInitial - $montantRestant, 0);
                            
                            $statut = (string)$pret['statut'];
                            $badgeClass = 'badge-pret';
                            if ($statut === 'en_cours') {
                                $badgeClass = 'badge-en-cours';
                            } elseif ($statut === 'termine' || $statut === 'solde' || $statut === 'soldé') {
                                $badgeClass = 'badge-termine';
                            } elseif ($statut === 'annule' || $statut === 'annulé') {
                                $badgeClass = 'badge-annule';
                            }
                        ?>
                        <tr>
                            <td>
                                <code><?= (int)$pret['id_pret'] ?></code>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($pret['nom_agent']) ?></strong>
                            </td>
                            <td>
                                <?= !empty($pret['date_octroi'])
                                    ? date('d/m/Y', strtotime($pret['date_octroi']))
                                    : '<em class="text-muted">Non définie</em>' ?>
                            </td>
                            <td class="text-end text-amount-positive">
                                <?= number_format($montantInitial, 0, ',', ' ') ?> FCFA
                            </td>
                            <td class="text-end text-amount-negative">
                                <?= number_format($montantPaye, 0, ',', ' ') ?> FCFA
                            </td>
                            <td class="text-end text-amount-warning">
                                <?= number_format($montantRestant, 0, ',', ' ') ?> FCFA
                            </td>
                            <td>
                                <?= !empty($pret['date_echeance'])
                                    ? date('d/m/Y', strtotime($pret['date_echeance']))
                                    : '<em class="text-muted">Non définie</em>' ?>
                            </td>
                            <td>
                                <span class="badge <?= $badgeClass ?>">
                                    <?= htmlspecialchars($statut) ?>
                                </span>
                            </td>
                            <td>
                                <?= !empty($pret['motif']) ? htmlspecialchars($pret['motif']) : '<em class="text-muted">Aucun motif</em>' ?>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-warning btn-sm me-1 mb-1" title="Modifier le prêt" data-bs-toggle="modal" data-bs-target="#editPretModal<?= $pret['id_pret'] ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-danger btn-sm me-1 mb-1" title="Supprimer le prêt" data-bs-toggle="modal" data-bs-target="#deletePretModal<?= $pret['id_pret'] ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <button type="button" class="btn btn-success btn-sm mb-1" title="Rembourser le prêt" data-bs-toggle="modal" data-bs-target="#remboursementPretModal<?= $pret['id_pret'] ?>">
                                    <i class="fas fa-money-bill-wave"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center py-4">
                                <i class="fas fa-hand-holding-usd fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">Aucun prêt trouvé avec les critères sélectionnés.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Nouveau Prêt -->
<div class="modal fade" id="addPretModal" tabindex="-1" aria-labelledby="addPretModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="addPretModalLabel">
                    <i class="fas fa-plus me-2"></i>Nouveau Prêt
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="add_pret.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="id_agent" class="form-label">
                            <i class="fas fa-user me-1"></i>Agent bénéficiaire
                        </label>
                        <select class="form-select" id="id_agent" name="id_agent" required>
                            <option value="">Sélectionner un agent</option>
                            <?php foreach ($agents_all as $agent): ?>
                                <option value="<?= $agent['id_agent'] ?>"><?= htmlspecialchars($agent['nom_agent']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="montant_initial" class="form-label">
                            <i class="fas fa-money-bill me-1"></i>Montant du prêt (FCFA)
                        </label>
                        <input type="text" class="form-control" id="montant_display" placeholder="Entrez le montant du prêt..." oninput="formatMontantPret(this)">
                        <input type="hidden" id="montant_initial" name="montant_initial" required>
                        <div class="form-text">Entrez un montant positif (ex: 100 000)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="motif" class="form-label">
                            <i class="fas fa-comment me-1"></i>Motif du prêt
                        </label>
                        <textarea class="form-control" id="motif" name="motif" rows="3" placeholder="Décrivez le motif du prêt..."></textarea>
                        <div class="form-text">Optionnel : précisez la raison du prêt</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i>Enregistrer le prêt
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modals pour les actions sur les prêts -->
<?php foreach ($prets as $pret): ?>
    <!-- Modal Modifier Prêt -->
    <div class="modal fade" id="editPretModal<?= $pret['id_pret'] ?>" tabindex="-1" aria-labelledby="editPretModalLabel<?= $pret['id_pret'] ?>" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="editPretModalLabel<?= $pret['id_pret'] ?>">
                        <i class="fas fa-edit me-2"></i>Modifier le Prêt #<?= $pret['id_pret'] ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="edit_pret.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="id_pret" value="<?= $pret['id_pret'] ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Agent</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($pret['nom_agent']) ?>" disabled>
                        </div>
                        
                        <div class="mb-3">
                            <label for="montant_initial_edit_<?= $pret['id_pret'] ?>" class="form-label">Montant initial (FCFA)</label>
                            <input type="text" class="form-control" id="montant_display_edit_<?= $pret['id_pret'] ?>" value="<?= number_format($pret['montant_initial'], 0, ',', ' ') ?>" oninput="formatMontantEdit(this, <?= $pret['id_pret'] ?>)">
                            <input type="hidden" id="montant_initial_edit_<?= $pret['id_pret'] ?>" name="montant_initial" value="<?= $pret['montant_initial'] ?>" required>
                            <div class="form-text">Entrez un montant positif (ex: 10 000 000)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="motif_edit_<?= $pret['id_pret'] ?>" class="form-label">Motif</label>
                            <textarea class="form-control" id="motif_edit_<?= $pret['id_pret'] ?>" name="motif" rows="3"><?= htmlspecialchars($pret['motif']) ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Annuler
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save me-1"></i>Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Supprimer Prêt -->
    <div class="modal fade" id="deletePretModal<?= $pret['id_pret'] ?>" tabindex="-1" aria-labelledby="deletePretModalLabel<?= $pret['id_pret'] ?>" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deletePretModalLabel<?= $pret['id_pret'] ?>">
                        <i class="fas fa-trash me-2"></i>Supprimer le Prêt #<?= $pret['id_pret'] ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="delete_pret.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="id_pret" value="<?= $pret['id_pret'] ?>">
                        
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Attention !</strong> Cette action est irréversible.
                        </div>
                        
                        <p>Voulez-vous vraiment supprimer ce prêt ?</p>
                        
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title">Détails du prêt :</h6>
                                <ul class="list-unstyled mb-0">
                                    <li><strong>Agent :</strong> <?= htmlspecialchars($pret['nom_agent']) ?></li>
                                    <li><strong>Montant initial :</strong> <?= number_format($pret['montant_initial'], 0, ',', ' ') ?> FCFA</li>
                                    <li><strong>Date d'octroi :</strong> <?= !empty($pret['date_octroi']) ? date('d/m/Y', strtotime($pret['date_octroi'])) : 'Non définie' ?></li>
                                    <li><strong>Statut :</strong> <?= htmlspecialchars($pret['statut']) ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Annuler
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i>Supprimer définitivement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Remboursement Prêt -->
    <div class="modal fade" id="remboursementPretModal<?= $pret['id_pret'] ?>" tabindex="-1" aria-labelledby="remboursementPretModalLabel<?= $pret['id_pret'] ?>" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="remboursementPretModalLabel<?= $pret['id_pret'] ?>">
                        <i class="fas fa-money-bill-wave me-2"></i>Remboursement - Prêt #<?= $pret['id_pret'] ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="remboursement_pret.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="id_pret" value="<?= $pret['id_pret'] ?>">
                        
                        <?php
                            $montantInitial = (float)$pret['montant_initial'];
                            $montantRestant = (float)($pret['montant_restant'] ?? 0);
                            $montantPaye = max($montantInitial - $montantRestant, 0);
                        ?>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Montant du prêt</label>
                                <input type="text" class="form-control" value="<?= number_format($montantInitial, 0, ',', ' ') ?> FCFA" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Déjà payé</label>
                                <input type="text" class="form-control" value="<?= number_format($montantPaye, 0, ',', ' ') ?> FCFA" disabled>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Montant restant</label>
                            <input type="text" class="form-control bg-warning" value="<?= number_format($montantRestant, 0, ',', ' ') ?> FCFA" disabled>
                        </div>
                        
                        <div class="mb-3">
                            <label for="montant_paiement_<?= $pret['id_pret'] ?>" class="form-label">
                                <i class="fas fa-money-bill me-1"></i>Montant à payer (FCFA)
                            </label>
                            <input type="text" class="form-control" id="montant_paiement_display_<?= $pret['id_pret'] ?>" placeholder="Entrez le montant à payer..." oninput="formatMontantRemboursement(this, <?= $pret['id_pret'] ?>)">
                            <input type="hidden" id="montant_paiement_<?= $pret['id_pret'] ?>" name="montant_paiement" required>
                            <div class="form-text">Maximum : <?= number_format($montantRestant, 0, ',', ' ') ?> FCFA</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Annuler
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check me-1"></i>Valider le remboursement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
function formatMontantPret(input) {
    // Supprimer tous les caractères non numériques
    let value = input.value.replace(/[^\d]/g, '');
    
    // Si vide, vider les deux champs
    if (value === '') {
        input.value = '';
        document.getElementById('montant_initial').value = '';
        return;
    }
    
    // Convertir en nombre pour validation
    let number = parseInt(value);
    
    // Mettre à jour le champ caché avec la valeur numérique
    document.getElementById('montant_initial').value = number;
    
    // Formater avec des espaces comme séparateurs de milliers
    let formatted = number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    
    // Mettre à jour le champ d'affichage
    input.value = formatted;
}

function formatMontantEdit(input, pretId) {
    // Supprimer tous les caractères non numériques
    let value = input.value.replace(/[^\d]/g, '');
    
    // Si vide, vider les deux champs
    if (value === '') {
        input.value = '';
        document.getElementById('montant_initial_edit_' + pretId).value = '';
        return;
    }
    
    // Convertir en nombre pour validation
    let number = parseInt(value);
    
    // Mettre à jour le champ caché avec la valeur numérique
    document.getElementById('montant_initial_edit_' + pretId).value = number;
    
    // Formater avec des espaces comme séparateurs de milliers
    let formatted = number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    
    // Mettre à jour le champ d'affichage
    input.value = formatted;
}

function formatMontantRemboursement(input, pretId) {
    // Supprimer tous les caractères non numériques
    let value = input.value.replace(/[^\d]/g, '');
    
    // Si vide, vider les deux champs
    if (value === '') {
        input.value = '';
        document.getElementById('montant_paiement_' + pretId).value = '';
        return;
    }
    
    // Convertir en nombre pour validation
    let number = parseInt(value);
    
    // Mettre à jour le champ caché avec la valeur numérique
    document.getElementById('montant_paiement_' + pretId).value = number;
    
    // Formater avec des espaces comme séparateurs de milliers
    let formatted = number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    
    // Mettre à jour le champ d'affichage
    input.value = formatted;
}

// Validation du formulaire avant soumission
document.querySelector('#addPretModal form').addEventListener('submit', function(e) {
    const montantValue = document.getElementById('montant_initial').value;
    const agentValue = document.getElementById('id_agent').value;
    
    if (!agentValue) {
        e.preventDefault();
        alert('Veuillez sélectionner un agent');
        document.getElementById('id_agent').focus();
        return false;
    }
    
    if (!montantValue || parseInt(montantValue) < 1) {
        e.preventDefault();
        alert('Veuillez entrer un montant valide (minimum 1 FCFA)');
        document.getElementById('montant_display').focus();
        return false;
    }
});

// Validation des formulaires d'édition et de remboursement
document.addEventListener('DOMContentLoaded', function() {
    // Ajouter la validation pour tous les formulaires d'édition
    const editForms = document.querySelectorAll('[id^="editPretModal"] form');
    editForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const pretId = form.querySelector('input[name="id_pret"]').value;
            const montantValue = document.getElementById('montant_initial_edit_' + pretId).value;
            
            if (!montantValue || parseInt(montantValue) < 1) {
                e.preventDefault();
                alert('Veuillez entrer un montant valide (minimum 1 FCFA)');
                document.getElementById('montant_display_edit_' + pretId).focus();
                return false;
            }
        });
    });

    // Ajouter la validation pour tous les formulaires de remboursement
    const remboursementForms = document.querySelectorAll('[id^="remboursementPretModal"] form');
    remboursementForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const pretId = form.querySelector('input[name="id_pret"]').value;
            const montantValue = document.getElementById('montant_paiement_' + pretId).value;
            
            if (!montantValue || parseInt(montantValue) < 1) {
                e.preventDefault();
                alert('Veuillez entrer un montant de remboursement valide (minimum 1 FCFA)');
                document.getElementById('montant_paiement_display_' + pretId).focus();
                return false;
            }
        });
    });

    // Réinitialiser les champs de remboursement quand les modals se ferment
    const remboursementModals = document.querySelectorAll('[id^="remboursementPretModal"]');
    remboursementModals.forEach(function(modal) {
        modal.addEventListener('hidden.bs.modal', function() {
            const pretId = modal.id.replace('remboursementPretModal', '');
            const displayField = document.getElementById('montant_paiement_display_' + pretId);
            const hiddenField = document.getElementById('montant_paiement_' + pretId);
            if (displayField) displayField.value = '';
            if (hiddenField) hiddenField.value = '';
        });
    });
});

// Réinitialiser le formulaire quand le modal se ferme
document.getElementById('addPretModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('montant_display').value = '';
    document.getElementById('montant_initial').value = '';
    document.getElementById('motif').value = '';
    document.getElementById('id_agent').value = '';
});
</script>

</body>
</html>
