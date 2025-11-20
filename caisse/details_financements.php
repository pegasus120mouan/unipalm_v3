<?php
require_once '../inc/functions/connexion.php';
require_once '../inc/functions/requete/requete_agents.php';
include 'header.php';

// Récupération de l'ID agent
$id_agent = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_agent <= 0) {
    die('Agent invalide.');
}

// Récupérer les infos de l'agent
$stmt = $conn->prepare("SELECT a.*, CONCAT(a.nom, ' ', a.prenom) AS nom_complet,
                               ce.nom AS chef_nom, ce.prenoms AS chef_prenoms
                        FROM agents a
                        LEFT JOIN chef_equipe ce ON a.id_chef = ce.id_chef
                        WHERE a.id_agent = :id_agent AND a.date_suppression IS NULL");
$stmt->bindValue(':id_agent', $id_agent, PDO::PARAM_INT);
$stmt->execute();
$agent = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$agent) {
    die('Agent introuvable.');
}

// Récupération de tous les financements de l'agent
$sql_financements = "SELECT f.*, 
                            CASE 
                                WHEN f.montant > 0 THEN 'Financement accordé'
                                WHEN f.montant < 0 THEN 'Remboursement'
                                ELSE 'Neutre'
                            END as type_operation
                     FROM financement f 
                     WHERE f.id_agent = :id_agent 
                     ORDER BY f.date_financement DESC, f.Numero_financement DESC";

$stmt_fin = $conn->prepare($sql_financements);
$stmt_fin->bindValue(':id_agent', $id_agent, PDO::PARAM_INT);
$stmt_fin->execute();
$financements = $stmt_fin->fetchAll(PDO::FETCH_ASSOC);

// Filtres de recherche
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type_filter = isset($_GET['type_filter']) ? trim($_GET['type_filter']) : '';
$date_debut = isset($_GET['date_debut']) ? trim($_GET['date_debut']) : '';
$date_fin = isset($_GET['date_fin']) ? trim($_GET['date_fin']) : '';

// Appliquer les filtres
$financements_filtered = $financements;

if (!empty($search)) {
    $financements_filtered = array_filter($financements_filtered, function($f) use ($search) {
        return stripos($f['Numero_financement'], $search) !== false || 
               stripos($f['motif'], $search) !== false;
    });
}

if (!empty($type_filter)) {
    $financements_filtered = array_filter($financements_filtered, function($f) use ($type_filter) {
        if ($type_filter === 'financement' && $f['montant'] > 0) return true;
        if ($type_filter === 'remboursement' && $f['montant'] < 0) return true;
        return false;
    });
}

if (!empty($date_debut)) {
    $financements_filtered = array_filter($financements_filtered, function($f) use ($date_debut) {
        return date('Y-m-d', strtotime($f['date_financement'])) >= $date_debut;
    });
}

if (!empty($date_fin)) {
    $financements_filtered = array_filter($financements_filtered, function($f) use ($date_fin) {
        return date('Y-m-d', strtotime($f['date_financement'])) <= $date_fin;
    });
}

// Pagination
$per_page = 15;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$total_financements = count($financements_filtered);
$total_pages = max(1, ceil($total_financements / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;
$financements_paginated = array_slice($financements_filtered, $offset, $per_page);

// Statistiques financières
$montant_initial = 0;
$montant_rembourse = 0;
$solde_financement = 0;

foreach ($financements as $f) {
    if ($f['montant'] > 0) {
        $montant_initial += $f['montant'];
    } else {
        $montant_rembourse += abs($f['montant']);
    }
    $solde_financement += $f['montant'];
}

$solde_financement = max(0, $solde_financement);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails Financements - <?= htmlspecialchars($agent['nom_complet']) ?></title>

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
        }

        .page-title {
            font-size: 2.2rem;
            font-weight: 700;
            color: #000;
            margin: 0;
        }

        .page-subtitle {
            color: #000;
            margin-top: 0.5rem;
            font-size: 1.1rem;
        }

        .stats-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(16px);
            border-radius: 18px;
            padding: 1.5rem;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.2);
        }

        .stats-value {
            font-size: 2rem;
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
        }

        .table-responsive {
            border-radius: 15px;
            overflow: hidden;
            max-height: 600px;
            overflow-y: auto;
        }

        .table-responsive::-webkit-scrollbar {
            width: 8px;
        }

        .table-responsive::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }

        .table-responsive::-webkit-scrollbar-thumb {
            background: rgba(102, 126, 234, 0.6);
            border-radius: 4px;
        }

        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: rgba(102, 126, 234, 0.8);
        }

        thead tr {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
        }

        thead th {
            border: none;
            font-weight: 500;
            padding: 1rem;
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        tbody tr {
            background: rgba(255, 255, 255, 0.92);
            transition: transform .15s ease, box-shadow .15s ease, background .15s ease;
        }

        tbody tr:nth-child(even) {
            background: rgba(255, 255, 255, 0.98);
        }

        tbody tr:hover {
            background: #f5f7ff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        tbody td {
            border-top: none;
            padding: 1rem;
            vertical-align: middle;
        }

        .badge-financement {
            background: linear-gradient(135deg, #4facfe, #00d2ff);
            color: #fff;
            border-radius: 999px;
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .badge-remboursement {
            background: linear-gradient(135deg, #ff6b6b, #ffa500);
            color: #fff;
            border-radius: 999px;
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .filter-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(18px);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
        }

        .btn-unipalm {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-unipalm:hover {
            background: linear-gradient(135deg, #5a6fd8, #6a4190);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
            color: white;
        }

        .pagination {
            --bs-pagination-padding-x: 0.75rem;
            --bs-pagination-padding-y: 0.375rem;
            --bs-pagination-font-size: 0.875rem;
            --bs-pagination-color: #495057;
            --bs-pagination-bg: rgba(255, 255, 255, 0.9);
            --bs-pagination-border-width: 1px;
            --bs-pagination-border-color: rgba(255, 255, 255, 0.3);
            --bs-pagination-border-radius: 8px;
            --bs-pagination-hover-color: #fff;
            --bs-pagination-hover-bg: rgba(102, 126, 234, 0.8);
            --bs-pagination-hover-border-color: rgba(102, 126, 234, 0.8);
            --bs-pagination-active-color: #fff;
            --bs-pagination-active-bg: linear-gradient(135deg, #667eea, #764ba2);
            --bs-pagination-active-border-color: rgba(102, 126, 234, 0.8);
            --bs-pagination-disabled-color: #6c757d;
            --bs-pagination-disabled-bg: rgba(255, 255, 255, 0.5);
            --bs-pagination-disabled-border-color: rgba(255, 255, 255, 0.3);
        }

        .pagination .page-link {
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-color: rgba(102, 126, 234, 0.8);
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #2c3e50;
        }

        .empty-state i {
            color: rgba(255,255,255,0.6);
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }

            .page-header {
                padding: 1.5rem;
            }

            .page-title {
                font-size: 1.8rem;
            }

            .stats-value {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>

<div class="main-container">
    <!-- Messages de notification -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- En-tête -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title">
                    <i class="fas fa-leaf me-2"></i>
                    Détails Financements
                </h1>
                <p class="page-subtitle">
                    Agent: <?= htmlspecialchars($agent['nom_complet']) ?>
                    <?php if (!empty($agent['chef_nom'])): ?>
                        • Chef d'équipe: <?= htmlspecialchars($agent['chef_nom'] . ' ' . $agent['chef_prenoms']) ?>
                    <?php endif; ?>
                </p>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addFinancementModal">
                    <i class="fas fa-plus me-2"></i>Nouveau Financement
                </button>
                <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#historiqueModal">
                    <i class="fas fa-file-pdf me-2"></i>Voir historique de financement
                </button>
                <a href="financements.php" class="btn btn-unipalm">
                    <i class="fas fa-arrow-left me-2"></i>Retour aux financements
                </a>
            </div>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card text-center">
                <div class="stats-value text-primary">
                    <?= number_format($montant_initial, 0, ',', ' ') ?>
                </div>
                <div class="stats-label">Montant Initial (FCFA)</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card text-center">
                <div class="stats-value text-success">
                    <?= number_format($montant_rembourse, 0, ',', ' ') ?>
                </div>
                <div class="stats-label">Montant Remboursé (FCFA)</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card text-center">
                <div class="stats-value text-warning">
                    <?= number_format($solde_financement, 0, ',', ' ') ?>
                </div>
                <div class="stats-label">Solde Financement (FCFA)</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card text-center">
                <div class="stats-value text-info">
                    <?= count($financements) ?>
                </div>
                <div class="stats-label">Total Opérations</div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="filter-card">
        <h5 class="mb-3">
            <i class="fas fa-filter me-2"></i>Filtres de recherche
        </h5>
        <form method="get" class="row g-3">
            <input type="hidden" name="id" value="<?= $id_agent ?>">
            
            <div class="col-md-3">
                <label class="form-label">Recherche</label>
                <input type="text" name="search" class="form-control" placeholder="Numéro ou motif..." value="<?= htmlspecialchars($search) ?>">
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Type</label>
                <select name="type_filter" class="form-select">
                    <option value="">Tous</option>
                    <option value="financement" <?= $type_filter === 'financement' ? 'selected' : '' ?>>Financements</option>
                    <option value="remboursement" <?= $type_filter === 'remboursement' ? 'selected' : '' ?>>Remboursements</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Date début</label>
                <input type="date" name="date_debut" class="form-control" value="<?= htmlspecialchars($date_debut) ?>">
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Date fin</label>
                <input type="date" name="date_fin" class="form-control" value="<?= htmlspecialchars($date_fin) ?>">
            </div>
            
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-unipalm">
                    <i class="fas fa-search me-1"></i>Filtrer
                </button>
                <a href="details_financements.php?id=<?= $id_agent ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-1"></i>Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Tableau des financements -->
    <div class="content-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>Historique des financements
            </h5>
            <div class="text-muted small">
                Affichage de <?= $offset + 1 ?> à <?= min($offset + $per_page, $total_financements) ?> sur <?= $total_financements ?> opération(s)
            </div>
        </div>

        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Numéro</th>
                        <th>Type</th>
                        <th>Montant</th>
                        <th>Motif</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($financements_paginated)): ?>
                        <?php foreach ($financements_paginated as $financement): ?>
                        <tr>
                            <td>
                                <strong><?= date('d/m/Y', strtotime($financement['date_financement'])) ?></strong>
                            </td>
                            <td>
                                <code><?= htmlspecialchars($financement['Numero_financement']) ?></code>
                            </td>
                            <td>
                                <?php if ($financement['montant'] > 0): ?>
                                    <span class="badge-financement">
                                        <i class="fas fa-plus-circle me-1"></i>Financement
                                    </span>
                                <?php else: ?>
                                    <span class="badge-remboursement">
                                        <i class="fas fa-minus-circle me-1"></i>Remboursement
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong class="<?= $financement['montant'] > 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= $financement['montant'] > 0 ? '+' : '' ?><?= number_format($financement['montant'], 0, ',', ' ') ?> FCFA
                                </strong>
                            </td>
                            <td>
                                <?= !empty($financement['motif']) ? htmlspecialchars($financement['motif']) : '<em class="text-muted">Aucun motif</em>' ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="empty-state">
                                <i class="fas fa-money-bill-wave fa-3x"></i>
                                <p class="mb-0">Aucun financement trouvé pour cet agent.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="d-flex justify-content-between align-items-center mt-4">
            <div class="text-muted small">
                Page <?= $page ?> sur <?= $total_pages ?>
            </div>
            <nav aria-label="Pagination des financements">
                <ul class="pagination pagination-sm mb-0">
                    <!-- Bouton Précédent -->
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                            <i class="fas fa-chevron-left"></i> Précédent
                        </a>
                    </li>

                    <!-- Numéros de pages -->
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    if ($start_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">1</a>
                        </li>
                        <?php if ($start_page > 2): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>"><?= $total_pages ?></a>
                        </li>
                    <?php endif; ?>

                    <!-- Bouton Suivant -->
                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                            Suivant <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Nouveau Financement -->
<div class="modal fade" id="addFinancementModal" tabindex="-1" aria-labelledby="addFinancementModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="addFinancementModalLabel">
                    <i class="fas fa-plus me-2"></i>Nouveau Financement
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="add_financements.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="redirect_to" value="details_financements.php?id=<?= $id_agent ?>">
                    
                    <div class="mb-3">
                        <label for="id_agent" class="form-label">
                            <i class="fas fa-user me-1"></i>Agent
                        </label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($agent['nom_complet']) ?>" readonly>
                        <input type="hidden" name="id_agent" value="<?= $id_agent ?>">
                        <small class="text-muted">Agent pré-sélectionné automatiquement</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="montant" class="form-label">
                            <i class="fas fa-money-bill me-1"></i>Montant (FCFA)
                        </label>
                        <input type="text" class="form-control" id="montant_display" placeholder="Entrez le montant..." oninput="formatMontant(this)">
                        <input type="hidden" id="montant" name="montant" required>
                        <div class="form-text">Entrez un montant positif pour un financement (ex: 10 000 000)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="motif" class="form-label">
                            <i class="fas fa-comment me-1"></i>Motif
                        </label>
                        <textarea class="form-control" id="motif" name="motif" rows="3" placeholder="Décrivez le motif du financement..."></textarea>
                        <div class="form-text">Optionnel : précisez la raison du financement</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i>Enregistrer le financement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Historique de Financement -->
<div class="modal fade" id="historiqueModal" tabindex="-1" aria-labelledby="historiqueModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="historiqueModalLabel">
                    <i class="fas fa-file-pdf me-2"></i>Générer l'historique de financement
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="view_financement.php" method="GET" target="_blank">
                <div class="modal-body">
                    <input type="hidden" name="id" value="<?= $id_agent ?>">
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Agent sélectionné :</strong> <?= htmlspecialchars($agent['nom_complet']) ?>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="date_debut" class="form-label">
                                    <i class="fas fa-calendar-alt me-1"></i>Date de début
                                </label>
                                <input type="date" class="form-control" id="date_debut" name="date_debut" required 
                                       value="<?= date('Y-m-d', strtotime('-30 days')) ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="date_fin" class="form-label">
                                    <i class="fas fa-calendar-alt me-1"></i>Date de fin
                                </label>
                                <input type="date" class="form-control" id="date_fin" name="date_fin" required 
                                       value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <small>Le PDF sera généré et téléchargé automatiquement avec tous les financements de l'agent sur la période sélectionnée.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-download me-1"></i>Générer le PDF
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
function formatMontant(input) {
    // Supprimer tous les caractères non numériques
    let value = input.value.replace(/[^\d]/g, '');
    
    // Si vide, vider les deux champs
    if (value === '') {
        input.value = '';
        document.getElementById('montant').value = '';
        return;
    }
    
    // Convertir en nombre pour validation
    let number = parseInt(value);
    
    // Mettre à jour le champ caché avec la valeur numérique
    document.getElementById('montant').value = number;
    
    // Formater avec des espaces comme séparateurs de milliers
    let formatted = number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    
    // Mettre à jour le champ d'affichage
    input.value = formatted;
}

// Validation du formulaire avant soumission
document.querySelector('#addFinancementModal form').addEventListener('submit', function(e) {
    const montantValue = document.getElementById('montant').value;
    
    if (!montantValue || parseInt(montantValue) < 1) {
        e.preventDefault();
        alert('Veuillez entrer un montant valide (minimum 1 FCFA)');
        document.getElementById('montant_display').focus();
        return false;
    }
});

// Réinitialiser le formulaire quand le modal se ferme
document.getElementById('addFinancementModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('montant_display').value = '';
    document.getElementById('montant').value = '';
    document.getElementById('motif').value = '';
});
</script>
</body>
</html>
