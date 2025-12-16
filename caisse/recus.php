<?php
require_once '../inc/functions/connexion.php';
require_once '../inc/functions/requete/requete_agents.php';
include('header_caisse.php');

// Créer une mémoire pour le design UniPalm appliqué
$page_title = "Gestion des Reçus de Paiement";

// Récupérer la liste des agents
$agents = getAgents($conn);

// Paramètres de pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Paramètres de filtrage
$type = isset($_GET['type']) ? $_GET['type'] : 'all';
$date_debut = isset($_GET['date_debut']) ? $_GET['date_debut'] : '';
$date_fin = isset($_GET['date_fin']) ? $_GET['date_fin'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$agent_id = isset($_GET['agent_id']) ? $_GET['agent_id'] : '';

// Construction de la requête SQL
$where_conditions = [];
$params = [];

if ($type !== 'all') {
    $where_conditions[] = "r.type_document = ?";
    $params[] = $type;
}

if ($date_debut) {
    $where_conditions[] = "DATE(r.date_creation) >= ?";
    $params[] = $date_debut;
}

if ($date_fin) {
    $where_conditions[] = "DATE(r.date_creation) <= ?";
    $params[] = $date_fin;
}

if ($search) {
    $where_conditions[] = "(r.numero_recu LIKE ? OR r.numero_document LIKE ? OR r.nom_agent LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($agent_id) {
    $where_conditions[] = "r.id_agent = ?";
    $params[] = $agent_id;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Requête pour le nombre total de reçus
$count_query = "SELECT COUNT(*) as total FROM recus_paiements r $where_clause";
$stmt = $conn->prepare($count_query);
$stmt->execute($params);
$total_rows = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_rows / $limit);

// Requête pour les reçus avec LIMIT et OFFSET directement dans la requête
$query = "
    SELECT r.* 
    FROM recus_paiements r 
    $where_clause 
    ORDER BY r.date_creation DESC 
    LIMIT $limit OFFSET $offset
";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$recus = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Modal pour ticket en doublon -->
<div class="modal fade" id="ticketExistModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle"></i> Attention !
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <i class="fas fa-times-circle text-danger fa-4x mb-3"></i>
                <h4 class="text-danger">Numéro de ticket en double</h4>
                <p class="mb-0">Le ticket numéro <strong id="duplicateTicketNumber"></strong> existe déjà.</p>
                <p>Veuillez utiliser un autre numéro.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Message d'erreur/succès -->
<?php if (isset($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?= $_SESSION['error'] ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?= $_SESSION['success'] ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php unset($_SESSION['success']); ?>
<?php endif; ?>

<!-- Reste du code HTML -->

<script>
$(document).ready(function() {
    // Vérification lors de la saisie
    /*
    $('input[name="numero_ticket"]').on('change', function() {
        var numero_ticket = $(this).val().trim();
        if (numero_ticket) {
            $.ajax({
                url: 'check_ticket.php',
                method: 'POST',
                data: { numero_ticket: numero_ticket },
                dataType: 'json',
                success: function(response) {
                    if (response.exists) {
                        $('#duplicateTicketNumber').text(numero_ticket);
                        $('#ticketExistModal').modal('show');
                        $('input[name="numero_ticket"]').val('');
                    }
                }
            });
        }
    });
    */
    // Focus sur le champ après fermeture du modal
    $('#ticketExistModal').on('hidden.bs.modal', function() {
        $('input[name="numero_ticket"]').focus();
    });
});
</script>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - UniPalm</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --danger-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --dark-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --glass-bg: rgba(255, 255, 255, 0.25);
            --glass-border: rgba(255, 255, 255, 0.18);
            --shadow-light: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            --shadow-dark: 0 4px 15px 0 rgba(31, 38, 135, 0.2);
            --text-primary: #2d3748;
            --text-secondary: #718096;
            --border-radius: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            min-height: 100vh;
            color: var(--text-primary);
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translate3d(0, -100%, 0);
            }
            to {
                opacity: 1;
                transform: translate3d(0, 0, 0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translate3d(0, 100%, 0);
            }
            to {
                opacity: 1;
                transform: translate3d(0, 0, 0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translate3d(100%, 0, 0);
            }
            to {
                opacity: 1;
                transform: translate3d(0, 0, 0);
            }
        }

        .header-container {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-light);
            animation: fadeInDown 0.8s ease-out;
        }

        .unipalm-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .unipalm-logo i {
            font-size: 3rem;
            background: var(--success-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-right: 1rem;
            animation: pulse 2s infinite;
        }

        .unipalm-logo h1 {
            font-size: 2.5rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0;
        }

        .page-subtitle {
            text-align: center;
            color: var(--text-secondary);
            font-size: 1.1rem;
            margin-top: 0.5rem;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--shadow-light);
            transition: var(--transition);
            animation: fadeInUp 0.8s ease-out;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-dark);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            display: block;
        }

        .stat-card:nth-child(1) .stat-icon {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-card:nth-child(2) .stat-icon {
            background: var(--success-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-card:nth-child(3) .stat-icon {
            background: var(--warning-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-card:nth-child(4) .stat-icon {
            background: var(--danger-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .filter-container {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-light);
            animation: slideInRight 0.8s ease-out;
        }

        .filter-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
            display: flex;
            align-items: center;
        }

        .filter-title i {
            margin-right: 0.5rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .form-control {
            border: 2px solid transparent;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: var(--transition);
            background: rgba(255, 255, 255, 0.9);
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            background: white;
        }

        .btn-gradient {
            background: var(--primary-gradient);
            border: none;
            border-radius: 12px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            color: white;
            transition: var(--transition);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .table-container {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow-light);
            animation: fadeInUp 1s ease-out;
        }

        .table {
            background: transparent;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .table thead th {
            background: var(--primary-gradient);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1rem;
            border: none;
            font-size: 0.85rem;
        }

        .table tbody tr {
            background: rgba(255, 255, 255, 0.9);
            transition: var(--transition);
        }

        .table tbody tr:hover {
            background: rgba(255, 255, 255, 1);
            transform: scale(1.01);
        }

        .table tbody td {
            padding: 1rem;
            border: none;
            vertical-align: middle;
        }

        .badge-custom {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.8rem;
        }

        .badge-ticket {
            background: var(--primary-gradient);
            color: white;
        }

        .badge-bordereau {
            background: var(--success-gradient);
            color: white;
        }

        .btn-action {
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            font-weight: 500;
            transition: var(--transition);
            margin: 0.2rem;
        }

        .btn-print {
            background: var(--success-gradient);
            border: none;
            color: white;
        }

        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(67, 233, 123, 0.4);
            color: white;
        }

        .btn-delete {
            background: var(--danger-gradient);
            border: none;
            color: white;
        }

        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(250, 112, 154, 0.4);
            color: white;
        }

        .modal-content {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--shadow-light);
        }

        .modal-header {
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            border-bottom: none;
        }

        .modal-footer {
            border-top: none;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(67, 233, 123, 0.1) 0%, rgba(56, 249, 215, 0.1) 100%);
            color: #22543d;
            border-left: 4px solid #38f9d7;
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(250, 112, 154, 0.1) 0%, rgba(254, 225, 64, 0.1) 100%);
            color: #742a2a;
            border-left: 4px solid #fa709a;
        }

        .alert-warning {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.1) 0%, rgba(255, 235, 59, 0.1) 100%);
            color: #744210;
            border-left: 4px solid #ffc107;
        }

        @media (max-width: 768px) {
            .unipalm-logo h1 {
                font-size: 2rem;
            }
            
            .unipalm-logo i {
                font-size: 2.5rem;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .table-responsive {
                border-radius: 12px;
            }
            
            .filter-container .row {
                flex-direction: column;
            }
            
            .filter-container .col-md-2,
            .filter-container .col-md-3 {
                margin-bottom: 1rem;
            }
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .print-button {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--primary-gradient);
            border: none;
            color: white;
            font-size: 1.5rem;
            box-shadow: var(--shadow-dark);
            transition: var(--transition);
            z-index: 1000;
        }

        .print-button:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>


<div class="container-fluid py-4">
    <!-- Header UniPalm -->
    <div class="header-container">
        <div class="unipalm-logo">
            <i class="fas fa-leaf"></i>
            <h1>UniPalm</h1>
        </div>
        <p class="page-subtitle"><?= $page_title ?></p>
    </div>

    <!-- Messages d'alerte -->
    <?php if (isset($_SESSION['warning'])): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?= $_SESSION['warning'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['warning']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['popup'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            Ticket enregistré avec succès
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['popup']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['delete_pop'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-times-circle me-2"></i>
            Une erreur s'est produite
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['delete_pop']); ?>
    <?php endif; ?>

    <!-- Statistiques -->
    <div class="stats-container">
        <div class="stat-card">
            <i class="fas fa-receipt stat-icon"></i>
            <div class="stat-number"><?= $total_rows ?></div>
            <div class="stat-label">Total Reçus</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-ticket-alt stat-icon"></i>
            <div class="stat-number"><?php
                $stmt = $conn->prepare("SELECT COUNT(*) as total FROM recus_paiements WHERE type_document = 'ticket'");
                $stmt->execute();
                echo $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            ?></div>
            <div class="stat-label">Reçus Tickets</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-file-alt stat-icon"></i>
            <div class="stat-number"><?php
                $stmt = $conn->prepare("SELECT COUNT(*) as total FROM recus_paiements WHERE type_document = 'bordereau'");
                $stmt->execute();
                echo $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            ?></div>
            <div class="stat-label">Reçus Bordereaux</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-coins stat-icon"></i>
            <div class="stat-number"><?php
                $stmt = $conn->prepare("SELECT SUM(montant_paye) as total FROM recus_paiements");
                $stmt->execute();
                $total_montant = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                echo number_format($total_montant, 0, ',', ' ');
            ?></div>
            <div class="stat-label">Total Payé (FCFA)</div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="filter-container">
        <h3 class="filter-title">
            <i class="fas fa-filter"></i>
            Filtres de recherche
        </h3>
        <form method="get" class="row g-3">
            <div class="col-md-2">
                <select name="type" class="form-control">
                    <option value="all" <?= $type === 'all' ? 'selected' : '' ?>>Tous les types</option>
                    <option value="ticket" <?= $type === 'ticket' ? 'selected' : '' ?>>Tickets</option>
                    <option value="bordereau" <?= $type === 'bordereau' ? 'selected' : '' ?>>Bordereaux</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="agent_id" class="form-control">
                    <option value="">Tous les agents</option>
                    <?php foreach ($agents as $agent): ?>
                        <option value="<?= $agent['id_agent'] ?>" <?= $agent_id == $agent['id_agent'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($agent['nom_complet_agent']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_debut" class="form-control" value="<?= htmlspecialchars($date_debut) ?>" placeholder="Date début">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_fin" class="form-control" value="<?= htmlspecialchars($date_fin) ?>" placeholder="Date fin">
            </div>
            <div class="col-md-2">
                <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Rechercher...">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-gradient w-100">
                    <i class="fas fa-search me-1"></i>
                    Filtrer
                </button>
            </div>
        </form>
    </div>

    <!-- Tableau des reçus -->
    <div class="table-container">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>N° Reçu</th>
                        <th>Type</th>
                        <th>N° Document</th>
                        <th>Agent</th>
                        <th>Montant Payé</th>
                        <th>Reste à Payer</th>
                        <th>Caissier</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recus)) : ?>
                        <?php foreach ($recus as $recu) : ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($recu['date_creation'])) ?></td>
                                <td><strong><?= htmlspecialchars($recu['numero_recu']) ?></strong></td>
                                <td>
                                    <span class="badge-custom <?= $recu['type_document'] === 'ticket' ? 'badge-ticket' : 'badge-bordereau' ?>">
                                        <?= ucfirst(htmlspecialchars($recu['type_document'])) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($recu['numero_document']) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($recu['nom_agent']) ?></strong>
                                    <?php if ($recu['contact_agent']) : ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($recu['contact_agent']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= number_format($recu['montant_paye'], 0, ',', ' ') ?> FCFA</strong></td>
                                <td><?= number_format($recu['reste_a_payer'], 0, ',', ' ') ?> FCFA</td>
                                <td><?= htmlspecialchars($recu['nom_caissier']) ?></td>
                                <td>
                                    <?php if ($recu['type_document'] === 'ticket') : ?>
                                        <a href="recu_paiement_pdf.php?id_ticket=<?= htmlspecialchars($recu['id_document']) ?>&reimprimer=1" 
                                           class="btn btn-print btn-action" target="_blank">
                                            <i class="fas fa-print"></i> Imprimer
                                        </a>
                                    <?php else : ?>
                                        <a href="recu_paiement_pdf.php?id_bordereau=<?= htmlspecialchars($recu['id_document']) ?>&reimprimer=1" 
                                           class="btn btn-print btn-action" target="_blank">
                                            <i class="fas fa-print"></i> Imprimer
                                        </a>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-delete btn-action" data-bs-toggle="modal" 
                                        data-bs-target="#supprimer_paiement_<?= htmlspecialchars($recu['id_recu']) ?>">
                                        <i class="fas fa-trash"></i> Supprimer
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="9" class="text-center">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Aucun reçu trouvé</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bouton d'impression flottant -->
    <button class="print-button" onclick="window.print()" title="Imprimer la page">
        <i class="fas fa-print"></i>
    </button>
</div>
</body>

</html>

<script src="../../plugins/jquery/jquery.min.js"></script>
<!-- jQuery UI 1.11.4 -->
<script src="../../plugins/jquery-ui/jquery-ui.min.js"></script>
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
<!-- <script>
  $.widget.bridge('uibutton', $.ui.button)
</script>-->
<!-- Bootstrap 4 -->
<script src="../../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- ChartJS -->
<script src="../../plugins/chart.js/Chart.min.js"></script>
<!-- Sparkline -->
<script src="../../plugins/sparklines/sparkline.js"></script>
<!-- JQVMap -->
<script src="../../plugins/jqvmap/jquery.vmap.min.js"></script>
<script src="../../plugins/jqvmap/maps/jquery.vmap.usa.js"></script>
<!-- jQuery Knob Chart -->
<script src="../../plugins/jquery-knob/jquery.knob.min.js"></script>
<!-- daterangepicker -->
<script src="../../plugins/moment/moment.min.js"></script>
<script src="../../plugins/daterangepicker/daterangepicker.js"></script>
<!-- Tempusdominus Bootstrap 4 -->
<script src="../../plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
<!-- Summernote -->
<script src="../../plugins/summernote/summernote-bs4.min.js"></script>
<!-- overlayScrollbars -->
<script src="../../plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<!-- AdminLTE App -->
<script src="../../dist/js/adminlte.js"></script>
<?php

if (isset($_SESSION['popup']) && $_SESSION['popup'] ==  true) {
  ?>
    <script>
      var audio = new Audio("../inc/sons/notification.mp3");
      audio.volume = 1.0; // Assurez-vous que le volume n'est pas à zéro
      audio.play().then(() => {
        // Lecture réussie
        var Toast = Swal.mixin({
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 3000
        });
  
        Toast.fire({
          icon: 'success',
          title: 'Action effectuée avec succès.'
        });
      }).catch((error) => {
        console.error('Erreur de lecture audio :', error);
      });
    </script>
  <?php
    $_SESSION['popup'] = false;
  }
  ?>



<!------- Delete Pop--->
<?php

if (isset($_SESSION['delete_pop']) && $_SESSION['delete_pop'] ==  true) {
?>
  <script>
    var Toast = Swal.mixin({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000
    });

    Toast.fire({
      icon: 'error',
      title: 'Action échouée.'
    })
  </script>

<?php
  $_SESSION['delete_pop'] = false;
}
?>
<!-- AdminLTE dashboard demo (This is only for demo purposes) -->
<!--<script src="dist/js/pages/dashboard.js"></script>-->
<script>
function showSearchModal(modalId) {
  // Hide all modals
  document.querySelectorAll('.modal').forEach(modal => {
    $(modal).modal('hide');
  });

  // Show the selected modal
  $('#' + modalId).modal('show');
}
</script>
<script>
// Afficher le modal si le ticket existe
<?php if (isset($_SESSION['ticket_error']) && $_SESSION['ticket_error']): ?>
    $(document).ready(function() {
        $('#existingTicketNumber').text('<?= $_SESSION['numero_ticket'] ?>');
        $('#ticketExistModal').modal('show');
    });
    <?php 
    unset($_SESSION['ticket_error']);
    unset($_SESSION['numero_ticket']);
    ?>
<?php endif; ?>

// Validation du formulaire

/*
$(document).ready(function() {
    $('form').on('submit', function(e) {
        var numeroTicket = $('#numero_ticket').val();
        
        // Vérification AJAX du numéro de ticket
        $.ajax({
            url: 'check_ticket.php',
            method: 'POST',
            data: { numero_ticket: numeroTicket },
            success: function(response) {
                if (response.exists) {
                    e.preventDefault();
                    $('#existingTicketNumber').text(numeroTicket);
                    $('#ticketExistModal').modal('show');
                }
            }
        });
    });
});
*/
</script>

<!-- Modal pour ticket existant -->
<div class="modal fade" id="ticketExistModal" tabindex="-1" role="dialog" aria-labelledby="ticketExistModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="ticketExistModalLabel">
                    <i class="fas fa-exclamation-triangle"></i> Ticket déjà existant
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Le ticket numéro <strong id="existingTicketNumber"></strong> existe déjà dans la base de données.</p>
                <p>Veuillez utiliser un autre numéro de ticket.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modals de suppression -->
<?php foreach ($recus as $recu) : ?>
    <div class="modal fade" id="supprimer_paiement_<?= htmlspecialchars($recu['id_recu']) ?>">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h4 class="modal-title">Confirmer l'annulation</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir annuler ce paiement ?</p>
                    <p><strong>N° Reçu :</strong> <?= htmlspecialchars($recu['numero_recu']) ?></p>
                    <p><strong>Montant du paiement :</strong> <?= number_format($recu['montant_paye'], 0, ',', ' ') ?> FCFA</p>
                    <p>Cette action va :</p>
                    <ul>
                        <li>Supprimer le reçu de paiement</li>
                        <li>Créer une transaction d'annulation</li>
                        <li>Mettre à jour le montant payé du <?= htmlspecialchars($recu['type_document']) ?></li>
                    </ul>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> Cette action est irréversible !
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="fas fa-times"></i> Fermer
                    </button>
                    <form action="delete_recus_paiement.php" method="POST" style="display: inline;">
                        <input type="hidden" name="id_recu" value="<?= htmlspecialchars($recu['id_recu']) ?>">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-check"></i> Confirmer l'annulation
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>