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

// Récupération de tous les prêts de l'agent
$sql_prets = "SELECT p.*, 
                     CASE 
                         WHEN p.statut = 'en_cours' THEN 'En cours'
                         WHEN p.statut = 'termine' OR p.statut = 'solde' THEN 'Terminé'
                         WHEN p.statut = 'annule' THEN 'Annulé'
                         ELSE p.statut
                     END as statut_libelle
              FROM prets p 
              WHERE p.id_agent = :id_agent 
              ORDER BY p.date_octroi DESC, p.id_pret DESC";

$stmt_prets = $conn->prepare($sql_prets);
$stmt_prets->bindValue(':id_agent', $id_agent, PDO::PARAM_INT);
$stmt_prets->execute();
$prets = $stmt_prets->fetchAll(PDO::FETCH_ASSOC);

// Filtres de recherche
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$statut_filter = isset($_GET['statut_filter']) ? trim($_GET['statut_filter']) : '';
$date_debut = isset($_GET['date_debut']) ? trim($_GET['date_debut']) : '';
$date_fin = isset($_GET['date_fin']) ? trim($_GET['date_fin']) : '';

// Appliquer les filtres
$prets_filtered = $prets;

if (!empty($search)) {
    $prets_filtered = array_filter($prets_filtered, function($pret) use ($search) {
        return stripos($pret['id_pret'], $search) !== false ||
               stripos($pret['motif'], $search) !== false ||
               stripos($pret['montant_initial'], $search) !== false;
    });
}

if (!empty($statut_filter)) {
    $prets_filtered = array_filter($prets_filtered, function($pret) use ($statut_filter) {
        return $pret['statut'] === $statut_filter;
    });
}

if (!empty($date_debut)) {
    $prets_filtered = array_filter($prets_filtered, function($pret) use ($date_debut) {
        return !empty($pret['date_octroi']) && $pret['date_octroi'] >= $date_debut;
    });
}

if (!empty($date_fin)) {
    $prets_filtered = array_filter($prets_filtered, function($pret) use ($date_fin) {
        return !empty($pret['date_octroi']) && $pret['date_octroi'] <= $date_fin;
    });
}

// Pagination
$items_per_page = 15;
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$total_items = count($prets_filtered);
$total_pages = ceil($total_items / $items_per_page);
$offset = ($current_page - 1) * $items_per_page;
$prets_paginated = array_slice($prets_filtered, $offset, $items_per_page);

// Calculs des statistiques
$total_prets = 0;
$total_rembourse = 0;
$total_restant = 0;

foreach ($prets as $pret) {
    $montant_initial = (float)$pret['montant_initial'];
    $montant_restant = (float)($pret['montant_restant'] ?? 0);
    $montant_rembourse = max($montant_initial - $montant_restant, 0);
    
    $total_prets += $montant_initial;
    $total_rembourse += $montant_rembourse;
    $total_restant += $montant_restant;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails des Prêts - <?= htmlspecialchars($agent['nom_complet']) ?> | UniPalm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #06d6a0;
            --warning-color: #ffd60a;
            --danger-color: #ef476f;
            --info-color: #118ab2;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 20px 0;
        }

        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .page-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .content-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .stats-card {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(79, 172, 254, 0.3);
            transition: transform 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .table-container {
            max-height: 600px;
            overflow-y: auto;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid #e9ecef;
        }

        .table {
            margin-bottom: 0;
            border: none;
        }

        .table thead {
            position: sticky;
            top: 0;
            z-index: 10;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .table thead th {
            border: none;
            padding: 15px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 1px;
        }

        .table tbody td {
            padding: 16px 15px;
            border: none;
            vertical-align: middle;
            font-size: 14px;
        }

        .table tbody tr:hover {
            background-color: rgba(102, 126, 234, 0.05);
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
            background: linear-gradient(135deg, #ffd60a 0%, #ff8500 100%);
        }

        .badge-termine {
            background: linear-gradient(135deg, #06d6a0 0%, #00b894 100%);
        }

        .badge-annule {
            background: linear-gradient(135deg, #ef476f 0%, #e74c3c 100%);
        }

        .btn-unipalm {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-unipalm:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .filter-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.9);
            padding: 12px 15px;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .pagination {
            justify-content: center;
            margin-top: 20px;
        }

        .page-link {
            border: none;
            background: rgba(255, 255, 255, 0.9);
            color: var(--primary-color);
            margin: 0 2px;
            border-radius: 8px;
            padding: 8px 12px;
        }

        .page-link:hover {
            background: var(--primary-color);
            color: white;
        }

        .page-item.active .page-link {
            background: var(--primary-color);
            color: white;
        }

        /* Styles pour les boutons d'action */
        .table tbody td:last-child {
            white-space: nowrap;
            width: 140px;
            min-width: 140px;
        }

        .table .btn-sm {
            padding: 4px 8px;
            font-size: 12px;
            border-radius: 4px;
            min-width: 32px;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- En-tête de la page -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="page-title mb-2">
                        <i class="fas fa-hand-holding-usd me-3 text-primary"></i>
                        Détails des Prêts
                    </h1>
                    <p class="page-subtitle mb-3">
                        Historique complet des prêts de <strong><?= htmlspecialchars($agent['nom_complet']) ?></strong>
                    </p>
                    <div class="agent-info">
                        <span class="badge bg-primary me-2">
                            <i class="fas fa-id-badge me-1"></i>ID: <?= $agent['id_agent'] ?>
                        </span>
                        <?php if (!empty($agent['chef_nom'])): ?>
                            <span class="badge bg-secondary">
                                <i class="fas fa-user-tie me-1"></i>Chef: <?= htmlspecialchars($agent['chef_nom'] . ' ' . $agent['chef_prenoms']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <a href="prets.php" class="btn btn-outline-primary me-2">
                        <i class="fas fa-arrow-left me-2"></i>Retour aux prêts
                    </a>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addPretModal">
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

        <!-- Statistiques -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stats-card">
                    <h3 class="mb-2"><?= number_format($total_prets, 0, ',', ' ') ?> FCFA</h3>
                    <p class="mb-0"><i class="fas fa-plus-circle me-2"></i>Total Prêts Accordés</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <h3 class="mb-2"><?= number_format($total_rembourse, 0, ',', ' ') ?> FCFA</h3>
                    <p class="mb-0"><i class="fas fa-minus-circle me-2"></i>Total Remboursé</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <h3 class="mb-2"><?= number_format($total_restant, 0, ',', ' ') ?> FCFA</h3>
                    <p class="mb-0"><i class="fas fa-balance-scale me-2"></i>Solde Restant</p>
                </div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="filter-card">
            <h5 class="card-title text-white mb-3">
                <i class="fas fa-filter me-2"></i>Filtres de recherche
            </h5>
            <form method="get" class="row g-3">
                <input type="hidden" name="id" value="<?= $id_agent ?>">
                
                <div class="col-md-3">
                    <label class="form-label text-white">Recherche</label>
                    <input type="text" name="search" class="form-control" placeholder="ID, motif, montant..." value="<?= htmlspecialchars($search) ?>">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label text-white">Statut</label>
                    <select name="statut_filter" class="form-select">
                        <option value="">Tous les statuts</option>
                        <option value="en_cours" <?= $statut_filter == 'en_cours' ? 'selected' : '' ?>>En cours</option>
                        <option value="termine" <?= $statut_filter == 'termine' ? 'selected' : '' ?>>Terminé</option>
                        <option value="annule" <?= $statut_filter == 'annule' ? 'selected' : '' ?>>Annulé</option>
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
                
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-unipalm">
                        <i class="fas fa-search me-1"></i>Filtrer
                    </button>
                    <a href="details_prets.php?id=<?= $id_agent ?>" class="btn btn-outline-light">
                        <i class="fas fa-undo me-1"></i>Réinitialiser
                    </a>
                </div>
            </form>
        </div>

        <!-- Liste des prêts -->
        <div class="content-card">
            <h5 class="card-title">
                <i class="fas fa-list me-2"></i>Historique des Prêts (<?= count($prets_filtered) ?> résultats)
            </h5>
            
            <div class="table-container">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag me-2"></i>ID</th>
                            <th><i class="fas fa-calendar me-2"></i>Date octroi</th>
                            <th class="text-end"><i class="fas fa-money-bill me-2"></i>Montant</th>
                            <th class="text-end"><i class="fas fa-coins me-2"></i>Remboursé</th>
                            <th class="text-end"><i class="fas fa-balance-scale me-2"></i>Restant</th>
                            <th><i class="fas fa-calendar-check me-2"></i>Échéance</th>
                            <th><i class="fas fa-info-circle me-2"></i>Statut</th>
                            <th><i class="fas fa-comment me-2"></i>Motif</th>
                            <th class="text-center"><i class="fas fa-cogs me-2"></i>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($prets_paginated)): ?>
                            <?php foreach ($prets_paginated as $pret): ?>
                            <?php
                                $montantInitial = (float)$pret['montant_initial'];
                                $montantRestant = (float)($pret['montant_restant'] ?? 0);
                                $montantRembourse = max($montantInitial - $montantRestant, 0);
                                
                                $statut = (string)$pret['statut'];
                                $badgeClass = 'badge-pret';
                                if ($statut === 'en_cours') {
                                    $badgeClass = 'badge-en-cours';
                                } elseif ($statut === 'termine' || $statut === 'solde') {
                                    $badgeClass = 'badge-termine';
                                } elseif ($statut === 'annule') {
                                    $badgeClass = 'badge-annule';
                                }
                            ?>
                            <tr>
                                <td><strong>#<?= $pret['id_pret'] ?></strong></td>
                                <td>
                                    <?= !empty($pret['date_octroi'])
                                        ? date('d/m/Y', strtotime($pret['date_octroi']))
                                        : '<em class="text-muted">Non définie</em>' ?>
                                </td>
                                <td class="text-end">
                                    <strong class="text-primary"><?= number_format($montantInitial, 0, ',', ' ') ?> FCFA</strong>
                                </td>
                                <td class="text-end">
                                    <span class="text-success"><?= number_format($montantRembourse, 0, ',', ' ') ?> FCFA</span>
                                </td>
                                <td class="text-end">
                                    <span class="text-warning"><?= number_format($montantRestant, 0, ',', ' ') ?> FCFA</span>
                                </td>
                                <td>
                                    <?= !empty($pret['date_echeance'])
                                        ? date('d/m/Y', strtotime($pret['date_echeance']))
                                        : '<em class="text-muted">Non définie</em>' ?>
                                </td>
                                <td>
                                    <span class="badge <?= $badgeClass ?>">
                                        <?= htmlspecialchars($pret['statut_libelle']) ?>
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
                                <td colspan="9" class="text-center py-4">
                                    <i class="fas fa-hand-holding-usd fa-2x text-muted mb-2"></i>
                                    <p class="text-muted mb-0">Aucun prêt trouvé pour cet agent.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Navigation des pages">
                    <ul class="pagination">
                        <?php if ($current_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?id=<?= $id_agent ?>&page=<?= $current_page - 1 ?>&search=<?= urlencode($search) ?>&statut_filter=<?= urlencode($statut_filter) ?>&date_debut=<?= urlencode($date_debut) ?>&date_fin=<?= urlencode($date_fin) ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                <a class="page-link" href="?id=<?= $id_agent ?>&page=<?= $i ?>&search=<?= urlencode($search) ?>&statut_filter=<?= urlencode($statut_filter) ?>&date_debut=<?= urlencode($date_debut) ?>&date_fin=<?= urlencode($date_fin) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($current_page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?id=<?= $id_agent ?>&page=<?= $current_page + 1 ?>&search=<?= urlencode($search) ?>&statut_filter=<?= urlencode($statut_filter) ?>&date_debut=<?= urlencode($date_debut) ?>&date_fin=<?= urlencode($date_fin) ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Nouveau Prêt -->
    <div class="modal fade" id="addPretModal" tabindex="-1" aria-labelledby="addPretModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="addPretModalLabel">
                        <i class="fas fa-plus me-2"></i>Nouveau Prêt pour <?= htmlspecialchars($agent['nom_complet']) ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="add_pret.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="id_agent" value="<?= $id_agent ?>">
                        <input type="hidden" name="redirect_to" value="details_prets.php?id=<?= $id_agent ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Agent bénéficiaire</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($agent['nom_complet']) ?>" disabled>
                        </div>
                        
                        <div class="mb-3">
                            <label for="montant_initial" class="form-label">Montant du prêt (FCFA)</label>
                            <input type="text" class="form-control" id="montant_display" placeholder="Entrez le montant du prêt..." oninput="formatMontant(this)">
                            <input type="hidden" id="montant_initial" name="montant_initial" required>
                            <div class="form-text">Entrez un montant positif (ex: 100 000)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="motif" class="form-label">Motif du prêt</label>
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
    <?php foreach ($prets_paginated as $pret): ?>
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
                            <input type="hidden" name="redirect_to" value="details_prets.php?id=<?= $id_agent ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Agent</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($agent['nom_complet']) ?>" disabled>
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
                            <input type="hidden" name="redirect_to" value="details_prets.php?id=<?= $id_agent ?>">
                            
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Attention !</strong> Cette action est irréversible.
                            </div>
                            
                            <p>Voulez-vous vraiment supprimer ce prêt ?</p>
                            
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">Détails du prêt :</h6>
                                    <ul class="list-unstyled mb-0">
                                        <li><strong>Agent :</strong> <?= htmlspecialchars($agent['nom_complet']) ?></li>
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
                            <input type="hidden" name="redirect_to" value="details_prets.php?id=<?= $id_agent ?>">
                            
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
        function formatMontant(input) {
            let value = input.value.replace(/[^\d]/g, '');
            
            if (value === '') {
                input.value = '';
                document.getElementById('montant_initial').value = '';
                return;
            }
            
            let number = parseInt(value);
            document.getElementById('montant_initial').value = number;
            
            let formatted = number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
            input.value = formatted;
        }

        function formatMontantEdit(input, pretId) {
            let value = input.value.replace(/[^\d]/g, '');
            
            if (value === '') {
                input.value = '';
                document.getElementById('montant_initial_edit_' + pretId).value = '';
                return;
            }
            
            let number = parseInt(value);
            document.getElementById('montant_initial_edit_' + pretId).value = number;
            
            let formatted = number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
            input.value = formatted;
        }

        function formatMontantRemboursement(input, pretId) {
            let value = input.value.replace(/[^\d]/g, '');
            
            if (value === '') {
                input.value = '';
                document.getElementById('montant_paiement_' + pretId).value = '';
                return;
            }
            
            let number = parseInt(value);
            document.getElementById('montant_paiement_' + pretId).value = number;
            
            let formatted = number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
            input.value = formatted;
        }

        // Validation du formulaire
        document.querySelector('#addPretModal form').addEventListener('submit', function(e) {
            const montantValue = document.getElementById('montant_initial').value;
            
            if (!montantValue || parseInt(montantValue) < 1) {
                e.preventDefault();
                alert('Veuillez entrer un montant valide (minimum 1 FCFA)');
                document.getElementById('montant_display').focus();
                return false;
            }
        });

        // Réinitialiser le formulaire
        document.getElementById('addPretModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('montant_display').value = '';
            document.getElementById('montant_initial').value = '';
            document.getElementById('motif').value = '';
        });
    </script>
</body>
</html>
