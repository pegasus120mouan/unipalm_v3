<?php
require_once '../inc/functions/connexion.php';
require_once '../inc/functions/requete/requete_agents.php';
require_once '../inc/functions/requete/requete_bordereaux.php';
include 'header.php';

// Récupération de tous les agents
$agents = getAgents($conn);
$total_agents = count($agents);

// Filtres de recherche
$search_nom = trim($_GET['search_nom'] ?? '');
$search_prenom = trim($_GET['search_prenom'] ?? '');
$search_contact = trim($_GET['search_contact'] ?? '');
$search_chef = trim($_GET['search_chef'] ?? '');

// Appliquer les filtres côté PHP sur la liste complète
$agents_filtered = $agents;
if (!empty($search_nom) || !empty($search_prenom) || !empty($search_contact) || !empty($search_chef)) {
    $agents_filtered = array_filter($agents, function($agent) use ($search_nom, $search_prenom, $search_contact, $search_chef) {
        $nom = trim($agent['nom_agent'] ?? $agent['nom'] ?? '');
        $prenom = trim($agent['prenom_agent'] ?? $agent['prenom'] ?? '');
        $contact = trim($agent['contact'] ?? '');
        $chef = trim($agent['chef_equipe'] ?? '');

        $match_nom = $search_nom === '' || stripos($nom, $search_nom) !== false;
        $match_prenom = $search_prenom === '' || stripos($prenom, $search_prenom) !== false;
        $match_contact = $search_contact === '' || stripos($contact, $search_contact) !== false;
        $match_chef = $search_chef === '' || stripos($chef, $search_chef) !== false;

        return $match_nom && $match_prenom && $match_contact && $match_chef;
    });
}

// Pagination
$elements_per_page = 15;
$current_page = max(1, intval($_GET['page'] ?? 1));
$total_filtered = count($agents_filtered);
$total_pages = max(1, ceil($total_filtered / $elements_per_page));
$current_page = min($current_page, $total_pages);
$offset = ($current_page - 1) * $elements_per_page;

// Appliquer la pagination
$agents_paginated = array_slice($agents_filtered, $offset, $elements_per_page);
$showing_from = $total_filtered > 0 ? $offset + 1 : 0;
$showing_to = min($offset + $elements_per_page, $total_filtered);

// Statistiques financières par agent basées sur les bordereaux
$stats_agents = [];
$bordereaux_result = getBordereaux($conn, 1, 10000, []);
$bordereaux_all = $bordereaux_result && isset($bordereaux_result['data']) ? $bordereaux_result['data'] : [];

foreach ($bordereaux_all as $b) {
    $id_agent_b = isset($b['id_agent']) ? (int)$b['id_agent'] : 0;
    if ($id_agent_b <= 0) {
        continue;
    }

    if (!isset($stats_agents[$id_agent_b])) {
        $stats_agents[$id_agent_b] = [
            'total' => 0.0,
            'paye'  => 0.0,
            'reste' => 0.0,
        ];
    }

    $total = isset($b['montant_total']) ? (float)$b['montant_total'] : 0.0;
    $paye  = isset($b['montant_payer']) ? (float)$b['montant_payer'] : 0.0;
    if (isset($b['montant_reste'])) {
        $reste = (float)$b['montant_reste'];
    } else {
        $reste = max($total - $paye, 0.0);
    }

    $stats_agents[$id_agent_b]['total'] += $total;
    $stats_agents[$id_agent_b]['paye']  += $paye;
    $stats_agents[$id_agent_b]['reste'] += $reste;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comptes Agents - UniPalm</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
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
            padding: 1.75rem 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #000;
            margin: 0;
        }

        .page-subtitle {
            color: #000;
            margin-top: .35rem;
        }

        .agent-count-badge {
            display: inline-block;
            margin-left: .75rem;
            padding: .2rem .75rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.8);
            color: #000;
            font-size: .8rem;
            font-weight: 600;
        }

        .table-wrapper {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(18px);
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }

        .stats-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(16px);
            border-radius: 18px;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            margin-bottom: 1.5rem;
        }

        .stats-card h6 {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #6c757d;
            margin-bottom: 0.75rem;
        }

        .stats-label {
            display: flex;
            justify-content: space-between;
            font-size: .8rem;
            margin-bottom: .15rem;
        }

        .stats-label span:first-child {
            color: #495057;
            font-weight: 500;
        }

        .stats-label span:last-child {
            color: #212529;
            font-weight: 600;
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
            padding: 0.75rem 1rem;
            white-space: nowrap;
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
            padding: 0.7rem 1rem;
            vertical-align: middle;
            font-size: 0.95rem;
        }

        .avatar-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .badge-contact {
            background: linear-gradient(135deg, #4facfe, #00d2ff);
            color: #fff;
            border-radius: 999px;
            padding: 0.25rem 0.7rem;
            font-size: 0.8rem;
        }

        .agent-stats {
            min-width: 220px;
        }

        .agent-stats-label {
            display: flex;
            justify-content: space-between;
            font-size: 0.7rem;
            margin-bottom: 0.1rem;
            color: #495057;
        }

        .agent-stats-bar {
            height: 6px;
            background-color: #e9ecef;
            border-radius: 999px;
            overflow: hidden;
            margin-bottom: 0.3rem;
        }

        .agent-stats-bar-inner {
            height: 100%;
            border-radius: 999px;
        }

        .empty-state {
            text-align: center;
            padding: 2.5rem 1rem;
            color: #2c3e50;
        }

        .empty-state i {
            color: rgba(255,255,255,0.6);
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

        .table thead th {
            position: sticky;
            top: 0;
            z-index: 10;
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }

            .page-header {
                padding: 1.25rem 1.5rem;
            }

            .page-title {
                font-size: 1.5rem;
            }

            thead {
                display: none;
            }

            table tbody tr {
                display: block;
                margin-bottom: 0.75rem;
                border-radius: 10px;
            }

            table tbody td {
                display: flex;
                justify-content: space-between;
                padding: 0.45rem 0.75rem;
                font-size: 0.85rem;
            }

            table tbody td::before {
                content: attr(data-label);
                font-weight: 600;
                margin-right: 0.75rem;
                color: #37474f;
            }
        }
    </style>
</head>
<body>

<div class="main-container">
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-user me-2"></i>
            Comptes des agents
            <span class="agent-count-badge"><?= $total_agents ?> agent(s)</span>
        </h1>
        <p class="page-subtitle">
            Vue d'ensemble de tous les agents enregistrés dans le système.
        </p>
    </div>

    <div class="stats-card">
        <h6><i class="fas fa-chart-bar me-1"></i> Statistiques générales</h6>
        <div class="mb-3">
            <div class="stats-label">
                <span>Total montant</span>
                <span>100%</span>
            </div>
            <div class="progress" style="height: 8px;">
                <div class="progress-bar bg-warning" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>
        <div class="mb-3">
            <div class="stats-label">
                <span>Montant payé</span>
                <span>75%</span>
            </div>
            <div class="progress" style="height: 8px;">
                <div class="progress-bar bg-success" role="progressbar" style="width: 75%;" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>
        <div>
            <div class="stats-label">
                <span>Reste à payer</span>
                <span>25%</span>
            </div>
            <div class="progress" style="height: 8px;">
                <div class="progress-bar bg-danger" role="progressbar" style="width: 25%;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>
    </div>

    <div class="table-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 text-dark">
                <i class="fas fa-list me-2"></i>Liste des agents
            </h5>
            <div class="text-muted small">
                Affichage de <?= $showing_from ?> à <?= $showing_to ?> sur <?= $total_filtered ?> agent(s)
            </div>
        </div>

        <!-- Filtres de recherche -->
        <form method="get" class="mb-3">
            <div class="row g-2">
                <div class="col-md-3">
                    <input type="text" name="search_nom" class="form-control" placeholder="Nom" value="<?= htmlspecialchars($search_nom) ?>">
                </div>
                <div class="col-md-3">
                    <input type="text" name="search_prenom" class="form-control" placeholder="Prénom" value="<?= htmlspecialchars($search_prenom) ?>">
                </div>
                <div class="col-md-3">
                    <input type="text" name="search_contact" class="form-control" placeholder="Contact" value="<?= htmlspecialchars($search_contact) ?>">
                </div>
                <div class="col-md-3">
                    <input type="text" name="search_chef" class="form-control" placeholder="Chef d'équipe" value="<?= htmlspecialchars($search_chef) ?>">
                </div>
            </div>
            <div class="mt-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="fas fa-search me-1"></i>Rechercher
                </button>
                <a href="comptes_agents.php" class="btn btn-sm btn-secondary">
                    <i class="fas fa-times me-1"></i>Réinitialiser
                </a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Agent</th>
                        <th>Contact</th>
                        <th>Chef d'équipe</th>
                        <th>Statistiques</th>
                        <th>Date création</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($agents_paginated)) : ?>
                    <?php foreach ($agents_paginated as $agent) : ?>
                        <tr>
                            <td data-label="Agent">
                                <a href="compte_agent_detail.php?id=<?= (int)$agent['id_agent'] ?>" class="text-decoration-none text-dark">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-circle">
                                            <?= strtoupper(substr($agent['nom_agent'] ?? $agent['nom'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-semibold">
                                                <?= htmlspecialchars($agent['nom_agent'] ?? $agent['nom']) ?>
                                                <?= htmlspecialchars($agent['prenom_agent'] ?? $agent['prenom']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </td>
                            <td data-label="Contact">
                                <span class="badge-contact">
                                    <?= htmlspecialchars($agent['contact'] ?? '') ?>
                                </span>
                            </td>
                            <td data-label="Chef d'équipe">
                                <?= htmlspecialchars($agent['chef_equipe'] ?? '') ?>
                            </td>
                            <td data-label="Statistiques">
                                <?php
                                $id_agent_row = (int)$agent['id_agent'];
                                $stats = $stats_agents[$id_agent_row] ?? null;
                                if ($stats && $stats['total'] > 0) {
                                    $total = $stats['total'];
                                    $paye = $stats['paye'];
                                    $reste = $stats['reste'];
                                    $pct_paye = max(0, min(100, round(($paye / $total) * 100)));
                                    $pct_reste = max(0, min(100, round(($reste / $total) * 100)));
                                ?>
                                <div class="agent-stats">
                                    <div class="agent-stats-label">
                                        <span>Total montant</span>
                                        <span>100%</span>
                                    </div>
                                    <div class="agent-stats-bar">
                                        <div class="agent-stats-bar-inner" style="width:100%;background-color:#ffc107;"></div>
                                    </div>

                                    <div class="agent-stats-label">
                                        <span>Montant payé</span>
                                        <span><?= $pct_paye ?>%</span>
                                    </div>
                                    <div class="agent-stats-bar">
                                        <div class="agent-stats-bar-inner" style="width:<?= $pct_paye ?>%;background-color:#28a745;"></div>
                                    </div>

                                    <div class="agent-stats-label">
                                        <span>Reste à payer</span>
                                        <span><?= $pct_reste ?>%</span>
                                    </div>
                                    <div class="agent-stats-bar mb-0">
                                        <div class="agent-stats-bar-inner" style="width:<?= $pct_reste ?>%;background-color:#dc3545;"></div>
                                    </div>
                                </div>
                                <?php } else { ?>
                                    <span class="text-muted" style="font-size:0.8rem;">Aucune donnée</span>
                                <?php } ?>
                            </td>
                            <td data-label="Date création">
                                <?php if (!empty($agent['date_ajout'])): ?>
                                    <?= date('d/m/Y', strtotime($agent['date_ajout'])) ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="empty-state">
                            <i class="fas fa-users fa-3x mb-3"></i>
                            <p class="mb-0">Aucun agent trouvé pour le moment.</p>
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
                Page <?= $current_page ?> sur <?= $total_pages ?>
            </div>
            <nav aria-label="Pagination des agents">
                <ul class="pagination pagination-sm mb-0">
                    <!-- Bouton Précédent -->
                    <li class="page-item <?= $current_page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $current_page - 1])) ?>">
                            <i class="fas fa-chevron-left"></i> Précédent
                        </a>
                    </li>

                    <!-- Numéros de pages -->
                    <?php
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);
                    
                    if ($start_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">1</a>
                        </li>
                        <?php if ($start_page > 2): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
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
                    <li class="page-item <?= $current_page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $current_page + 1])) ?>">
                            Suivant <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
