<?php
require_once '../inc/functions/connexion.php';
require_once '../inc/functions/requete/requete_agents.php';
require_once '../inc/functions/requete/requete_tickets.php';
require_once '../inc/functions/requete/requete_bordereaux.php';
require_once '../inc/functions/get_solde.php';
include 'header.php';

function getFinancementAgent($conn, $id_agent) {
    $stmt = $conn->prepare("SELECT COALESCE(SUM(montant), 0) as montant_total FROM financement WHERE id_agent = ? AND montant > 0");
    $stmt->execute([$id_agent]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

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

// Récupérer le solde de caisse actuel
$solde_caisse = getSoldeCaisse();

// Tickets de l'agent
$tickets = getTickets($conn, ['agent' => $id_agent]);

// Bordereaux de l'agent
$bordereaux_result = getBordereaux($conn, 1, 1000, ['agent' => $id_agent]);
$bordereaux = $bordereaux_result && isset($bordereaux_result['data']) ? $bordereaux_result['data'] : [];

// Filtres de statut via GET
$statut_ticket_filter = $_GET['statut_ticket'] ?? '';
$statut_bordereau_filter = $_GET['statut_bordereau'] ?? '';

// Filtrage des tickets selon le statut
$tickets_filtered = $tickets;
if ($statut_ticket_filter !== '') {
    $tickets_filtered = array_filter($tickets, function($t) use ($statut_ticket_filter) {
        $montant_paye = isset($t['montant_payer']) ? (float)$t['montant_payer'] : 0;
        $montant_reste = isset($t['montant_reste']) ? (float)$t['montant_reste'] : null;

        if ($montant_reste !== null) {
            if ($montant_reste <= 0 && $montant_paye > 0) {
                $statut_ticket = 'solde';
            } elseif ($montant_paye > 0 && $montant_reste > 0) {
                $statut_ticket = 'en_cours';
            } else {
                $statut_ticket = 'non_paye';
            }
        } else {
            if (!empty($t['date_paie'])) {
                $statut_ticket = 'solde';
            } else {
                $statut_ticket = 'non_paye';
            }
        }
        return $statut_ticket_filter === $statut_ticket;
    });
}

// Pagination Tickets
$per_page_tickets = 20;
$page_tickets = isset($_GET['page_tickets']) ? max(1, (int)$_GET['page_tickets']) : 1;
$total_tickets = count($tickets_filtered);
$total_pages_tickets = max(1, (int)ceil($total_tickets / $per_page_tickets));
if ($page_tickets > $total_pages_tickets) {
    $page_tickets = $total_pages_tickets;
}
$offset_tickets = ($page_tickets - 1) * $per_page_tickets;
$tickets_paginated = array_slice($tickets_filtered, $offset_tickets, $per_page_tickets);

// Filtrage des bordereaux selon le statut
$bordereaux_filtered = $bordereaux;
if ($statut_bordereau_filter !== '') {
    $bordereaux_filtered = array_filter($bordereaux, function($b) use ($statut_bordereau_filter) {
        $montant_total = isset($b['montant_total']) ? (float)$b['montant_total'] : 0;
        $montant_paye_b = isset($b['montant_payer']) ? (float)$b['montant_payer'] : 0;
        $montant_reste_b = isset($b['montant_reste']) ? (float)$b['montant_reste'] : null;

        if ($montant_reste_b !== null) {
            if ($montant_reste_b <= 0 && $montant_total > 0) {
                $statut_b = 'solde';
            } elseif ($montant_paye_b > 0 && $montant_reste_b > 0) {
                $statut_b = 'en_cours';
            } else {
                $statut_b = 'non_paye';
            }
        } else {
            if (!empty($b['statut_bordereau']) && strtolower($b['statut_bordereau']) === 'soldé') {
                $statut_b = 'solde';
            } else {
                $statut_b = 'non_paye';
            }
        }
        return $statut_bordereau_filter === $statut_b;
    });
}

// Pagination Bordereaux
$per_page_bord = 20;
$page_bord = isset($_GET['page_bord']) ? max(1, (int)$_GET['page_bord']) : 1;
$total_bord = count($bordereaux_filtered);
$total_pages_bord = max(1, (int)ceil($total_bord / $per_page_bord));
if ($page_bord > $total_pages_bord) {
    $page_bord = $total_pages_bord;
}
$offset_bord = ($page_bord - 1) * $per_page_bord;
$bordereaux_paginated = array_slice($bordereaux_filtered, $offset_bord, $per_page_bord);

// Totaux financiers sur l'agent
//  - uniquement à partir des bordereaux validés (les tickets ne contribuent pas directement)
$total_montant_bordereaux = 0; // total théorique dû
$total_montant_paye = 0;       // somme des montants déjà payés
$total_montant_reste = 0;      // somme des montants restants

// Bordereaux : on ajoute leurs montants seulement s'ils sont validés
foreach ($bordereaux as $b) {
    // Ne prendre en compte que les bordereaux validés
    if (empty($b['date_validation_boss'])) {
        continue;
    }

    $montant_total_b = isset($b['montant_total']) ? (float)$b['montant_total'] : 0;
    $montant_paye_b  = isset($b['montant_payer']) ? (float)$b['montant_payer'] : 0;

    if (isset($b['montant_reste']) && $b['montant_reste'] !== null) {
        $montant_reste_b = (float)$b['montant_reste'];
    } else {
        $montant_reste_b = max($montant_total_b - $montant_paye_b, 0);
    }

    $total_montant_bordereaux += $montant_total_b;
    $total_montant_paye       += $montant_paye_b;
    $total_montant_reste      += $montant_reste_b;
}

// Financements de l'agent (table financement)
$sql_financement = "SELECT 
    COALESCE(SUM(CASE WHEN montant > 0 THEN montant ELSE 0 END), 0) AS montant_initial,
    COALESCE(-SUM(CASE WHEN montant < 0 THEN montant ELSE 0 END), 0) AS montant_rembourse,
    GREATEST(COALESCE(SUM(montant), 0), 0) AS solde_financement
FROM financement
WHERE id_agent = :id_agent_fin";

$stmt_fin = $conn->prepare($sql_financement);
$stmt_fin->bindValue(':id_agent_fin', $id_agent, PDO::PARAM_INT);
$stmt_fin->execute();
$financement_stats = $stmt_fin->fetch(PDO::FETCH_ASSOC) ?: ['montant_initial' => 0, 'montant_rembourse' => 0, 'solde_financement' => 0];

// Prêts de l'agent (table prets)
$sql_prets = "SELECT 
    COALESCE(SUM(montant_initial), 0) AS montant_initial,
    COALESCE(SUM(montant_initial - COALESCE(montant_restant,0)), 0) AS montant_rembourse,
    COALESCE(SUM(COALESCE(montant_restant,0)), 0) AS solde_pret
FROM prets
WHERE id_agent = :id_agent_pret";

$stmt_pret = $conn->prepare($sql_prets);
$stmt_pret->bindValue(':id_agent_pret', $id_agent, PDO::PARAM_INT);
$stmt_pret->execute();
$prets_stats = $stmt_pret->fetch(PDO::FETCH_ASSOC) ?: ['montant_initial' => 0, 'montant_rembourse' => 0, 'solde_pret' => 0];

// Historique des paiements de l'agent (table recus_paiements)
$sql_recus = "SELECT numero_recu, type_document, numero_document, montant_total, montant_paye, montant_precedent, reste_a_payer, nom_usine, matricule_vehicule, nom_caissier, source_paiement
              FROM recus_paiements
              WHERE id_agent = :id_agent_recu
              ORDER BY numero_recu DESC";
$stmt_recus = $conn->prepare($sql_recus);
$stmt_recus->bindValue(':id_agent_recu', $id_agent, PDO::PARAM_INT);
$stmt_recus->execute();
$recus_paiements_agent = $stmt_recus->fetchAll(PDO::FETCH_ASSOC) ?: [];

// Solde global = reste à payer - solde de financement
$solde_financement_val = isset($financement_stats['solde_financement']) ? (float)$financement_stats['solde_financement'] : 0;
$solde_global = (float)$total_montant_reste - $solde_financement_val;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compte Agent - <?= htmlspecialchars($agent['nom_complet']) ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        .main-container { padding: 2rem; }
        .page-header {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(18px);
            border-radius: 20px;
            padding: 1.5rem 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
        }
        .page-title { font-size: 2rem; font-weight: 700; color: #000; margin: 0; }
        .badge-agent {
            margin-left: .75rem;
            padding: .2rem .75rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.8);
            color: #000;
            font-size: .8rem;
            font-weight: 600;
        }
        .card-glass {
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(16px);
            border-radius: 18px;
            border: none;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            margin-bottom: 1.5rem;
        }
        .table-responsive { border-radius: 12px; overflow: hidden; }
        .table-scroll {
            max-height: 420px;
            overflow-y: auto;
        }
        thead tr { background: #f5f7ff; }
        thead th { border: none; font-weight: 600; }
        tbody tr:hover { background: #f8f9ff; }
        .avatar-lg {
            width: 52px; height: 52px; border-radius: 50%;
            background: linear-gradient(135deg,#667eea,#764ba2);
            display:flex;align-items:center;justify-content:center;
            color:#fff;font-size:1.4rem;font-weight:700;
        }
        .toggle-buttons {
            display: inline-flex;
            gap: .5rem;
        }
        .toggle-btn {
            border-radius: 999px;
            padding: .4rem 1rem;
            font-size: .85rem;
        }
        .toggle-btn.active {
            background: linear-gradient(135deg,#667eea,#764ba2);
            color:#fff;
            border-color: transparent;
        }
        .filter-bar {
            background: #f7f8fc;
            border-radius: 999px;
            padding: .45rem .9rem;
            display: flex;
            align-items: center;
            gap: .9rem;
            flex-wrap: wrap;
            border: 1px solid rgba(102,126,234,0.12);
            box-shadow: 0 4px 10px rgba(15, 23, 42, 0.06);
        }
        .filter-label {
            font-size: .75rem;
            font-weight: 600;
            color: #445;
            text-transform: uppercase;
            letter-spacing: .1em;
            background: linear-gradient(135deg, rgba(102,126,234,0.08), rgba(118,75,162,0.12));
            border-radius: 999px;
            padding: .25rem .8rem;
            border: 1px solid rgba(102,126,234,0.35);
        }
        .filter-bar .form-select-sm {
            min-width: 180px;
        }
    </style>
</head>
<body>
<div class="main-container">
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="avatar-lg">
                <?= strtoupper(substr($agent['nom'], 0, 1)) ?>
            </div>
            <div>
                <h1 class="page-title mb-1">
                    <?= htmlspecialchars($agent['nom_complet']) ?>
                    <span class="badge-agent">Agent #<?= $agent['id_agent'] ?></span>
                </h1>
                <div class="text-muted">
                    Chef d'équipe : <?= htmlspecialchars(trim(($agent['chef_nom'] ?? '') . ' ' . ($agent['chef_prenoms'] ?? ''))) ?: '—' ?>
                </div>
                <div class="text-muted">
                    Contact : <?= htmlspecialchars($agent['contact'] ?? '') ?: '—' ?>
                </div>
            </div>
        </div>
        <div>
            <a href="comptes_agents.php" class="btn btn-outline-dark btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Retour à la liste
            </a>
        </div>
    </div>

    <!-- Messages d'erreur et de succès -->
    <?php if (isset($_GET['paiement_error']) && !empty($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Erreur de paiement :</strong> <?= htmlspecialchars($_SESSION['error_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <?php if (isset($_GET['paiement_success']) && !empty($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <strong>Succès :</strong> <?= htmlspecialchars($_SESSION['success_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <div class="row g-3 mb-3 align-items-stretch">
        <div class="col-md-4">
            <div class="card card-glass h-100">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="text-uppercase text-muted mb-0" style="letter-spacing:.08em;">Synthèse</h6>
                            <span class="badge bg-light text-dark border">Agent</span>
                        </div>
                        <div class="mt-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted small">Tickets</span>
                                <span class="fw-semibold"><?= count($tickets) ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted small">Bordereaux</span>
                                <span class="fw-semibold"><?= count($bordereaux) ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 pt-2 border-top">
                        <div class="toggle-buttons d-flex gap-2 flex-wrap">
                            <button type="button" class="btn btn-outline-primary toggle-btn active" data-target="tickets-section">
                                <i class="fas fa-ticket-alt me-1"></i> Tickets
                            </button>
                            <button type="button" class="btn btn-outline-primary toggle-btn" data-target="bordereaux-section">
                                <i class="fas fa-file-invoice-dollar me-1"></i> Bordereaux
                            </button>
                            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalFiltreTransactions">
                                <i class="fas fa-history me-1"></i> Historique des transactions
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card card-glass h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <h6 class="text-uppercase text-muted mb-0" style="letter-spacing:.08em;">Synthèse financière</h6>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4 col-12">
                            <div class="border rounded-4 px-3 py-3 h-100 bg-light" style="cursor:pointer;" data-bs-toggle="modal" data-bs-target="#modalTotalMontant">
                                <div class="text-muted small mb-1">Total montant</div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-success"><i class="fas fa-money-bill-wave"></i></span>
                                    <div>
                                        <div class="text-muted small">Total</div>
                                        <div class="fw-bold text-success" style="font-size:0.95rem;">
                                            <?= number_format($total_montant_bordereaux, 0, ',', ' ') ?> FCFA
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-12">
                            <div class="border rounded-4 px-3 py-3 h-100 bg-light" style="cursor:pointer;" data-bs-toggle="modal" data-bs-target="#modalHistoriquePaiements">
                                <div class="text-muted small mb-1">Montant payé</div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-success"><i class="fas fa-money-bill-wave"></i></span>
                                    <div>
                                        <div class="text-muted small">Montant payé</div>
                                        <div class="fw-bold text-success" style="font-size:0.95rem;">
                                            <?= number_format($total_montant_paye, 0, ',', ' ') ?> FCFA
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-12">
                            <div class="border rounded-4 px-3 py-3 h-100 bg-light">
                                <div class="text-muted small mb-1">Reste à payer</div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-success"><i class="fas fa-money-bill-wave"></i></span>
                                    <div>
                                        <div class="text-muted small">Reste à payer</div>
                                        <div class="fw-bold text-success" style="font-size:0.95rem;">
                                            <?= number_format($total_montant_reste, 0, ',', ' ') ?> FCFA
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 col-12">
                            <div class="border rounded-4 px-3 py-3 h-100 bg-light">
                                <div class="text-muted small mb-1">Financement</div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-success"><i class="fas fa-hand-holding-usd"></i></span>
                                    <div>
                                        <div class="text-muted small">Solde financement</div>
                                        <div class="fw-bold text-success" style="font-size:0.95rem;">
                                            <?php
                                            $solde_financement_aff = isset($financement_stats['solde_financement'])
                                                ? (float)$financement_stats['solde_financement']
                                                : 0;
                                            echo ($solde_financement_aff > 0 ? '- ' : '') .
                                                number_format(abs($solde_financement_aff), 0, ',', ' ') . ' FCFA';
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 col-12">
                            <div class="border rounded-4 px-3 py-3 h-100 bg-light">
                                <div class="text-muted small mb-1">Prêts</div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-success"><i class="fas fa-piggy-bank"></i></span>
                                    <div>
                                        <div class="text-muted small">Solde prêt</div>
                                        <div class="fw-bold text-success" style="font-size:0.95rem;">
                                            <?= number_format($prets_stats['solde_pret'] ?? 0, 0, ',', ' ') ?> FCFA
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php
                        $solde_positive = $solde_global >= 0;
                        $solde_card_bg = $solde_positive ? 'bg-success text-white' : 'bg-danger text-white';
                        $solde_icon_class = $solde_positive ? 'fa-arrow-up' : 'fa-arrow-down';
                        ?>
                        <div class="col-md-4 col-12">
                            <div class="border rounded-4 px-3 py-3 h-100 <?= $solde_card_bg ?>">
                                <div class="text-white-50 small mb-1">Solde</div>
                                <div class="d-flex align-items-center gap-2">
                                    <span><i class="fas <?= $solde_icon_class; ?>"></i></span>
                                    <div>
                                        <div class="text-white-50 small">Reste à payer - Financement</div>
                                        <div class="fw-bold" style="font-size:0.95rem;">
                                            <?= number_format($solde_global, 0, ',', ' ') ?> FCFA
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="tickets-section" class="card card-glass">
        <div class="card-body">
            <h5 class="mb-3"><i class="fas fa-ticket-alt me-2"></i>Tickets de l'agent</h5>

            <form method="get" class="mb-3">
                <input type="hidden" name="id" value="<?= (int)$id_agent ?>">
                <div class="filter-bar">
                    <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2 me-auto">
                        <span class="filter-label me-sm-2">Statut du ticket</span>
                        <select name="statut_ticket" class="form-select form-select-sm">
                            <option value="">Tous</option>
                            <option value="solde" <?= $statut_ticket_filter === 'solde' ? 'selected' : '' ?>>Soldé</option>
                            <option value="en_cours" <?= $statut_ticket_filter === 'en_cours' ? 'selected' : '' ?>>En cours de paiement</option>
                            <option value="non_paye" <?= $statut_ticket_filter === 'non_paye' ? 'selected' : '' ?>>Non payé</option>
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-search me-1"></i> Filtrer
                        </button>
                        <a href="compte_agent_detail.php?id=<?= (int)$id_agent ?>" class="btn btn-outline-secondary btn-sm">
                            Réinitialiser
                        </a>
                    </div>
                </div>
            </form>

            <div class="table-responsive table-scroll">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Date ticket</th>
                            <th>N° Ticket</th>
                            <th>Usine</th>
                            <th>Poids</th>
                            <th>Montant</th>
                            <th>Payé</th>
                            <th>Reste à payer</th>
                            <th>Bordereau</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($tickets_paginated)): ?>
                        <?php foreach ($tickets_paginated as $t): ?>
                            <tr>
                                <td><?= !empty($t['date_ticket']) ? date('d/m/Y', strtotime($t['date_ticket'])) : '-' ?></td>
                                <td><?= htmlspecialchars($t['numero_ticket'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($t['nom_usine'] ?? $t['usine_nom'] ?? '-') ?></td>
                                <td><?= isset($t['poids']) ? number_format($t['poids'], 0, ',', ' ') : '-' ?></td>
                                <td>
                                    <?php
                                    $montant_ticket = null;
                                    if (isset($t['montant_paie']) && $t['montant_paie'] !== null) {
                                        $montant_ticket = (float)$t['montant_paie'];
                                    } elseif (isset($t['prix_unitaire'], $t['poids'])) {
                                        $montant_ticket = (float)$t['prix_unitaire'] * (float)$t['poids'];
                                    }
                                    echo $montant_ticket !== null
                                        ? number_format($montant_ticket, 0, ',', ' ') . ' FCFA'
                                        : '-';
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    // Montant payé affiché : montant_payer comme dans paiements.php
                                    $montant_paye_aff = isset($t['montant_payer']) ? (float)$t['montant_payer'] : 0;
                                    echo $montant_paye_aff > 0
                                        ? number_format($montant_paye_aff, 0, ',', ' ') . ' FCFA'
                                        : '0 FCFA';
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    // Reste à payer : champ montant_reste ou total - payé
                                    $montant_reste_aff = isset($t['montant_reste']) ? (float)$t['montant_reste'] : null;
                                    if ($montant_reste_aff === null && $montant_ticket !== null) {
                                        $montant_reste_aff = max($montant_ticket - $montant_paye_aff, 0);
                                    }
                                    echo $montant_reste_aff !== null
                                        ? number_format($montant_reste_aff, 0, ',', ' ') . ' FCFA'
                                        : '-';
                                    ?>
                                </td>
                                <td>
                                    <?php if (!empty($t['numero_bordereau'])): ?>
                                        <a href="view_bordereau.php?numero=<?= urlencode($t['numero_bordereau']) ?>" class="text-decoration-none">
                                            <?= htmlspecialchars($t['numero_bordereau']) ?>
                                        </a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $montant_paye = isset($t['montant_payer']) ? (float)$t['montant_payer'] : 0;
                                    $montant_reste = isset($t['montant_reste']) ? (float)$t['montant_reste'] : null;

                                    if ($montant_reste !== null) {
                                        if ($montant_reste <= 0 && $montant_paye > 0) {
                                            $statut_ticket = 'Soldé';
                                        } elseif ($montant_paye > 0 && $montant_reste > 0) {
                                            $statut_ticket = 'En cours de paiement';
                                        } else {
                                            $statut_ticket = 'Non payé';
                                        }
                                    } else {
                                        if (!empty($t['date_paie'])) {
                                            $statut_ticket = 'Soldé';
                                        } else {
                                            $statut_ticket = 'Non payé';
                                        }
                                    }

                                    echo htmlspecialchars($statut_ticket);
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    // Même logique d'activation que dans paiements.php, avec désactivation si ticket dans un bordereau
                                    $montant_total_ticket_btn = isset($t['montant_paie']) && $t['montant_paie'] !== null
                                        ? (float)$t['montant_paie']
                                        : $montant_ticket;
                                    $montant_paye_btn = $montant_paye;
                                    $montant_reste_btn = $montant_total_ticket_btn - $montant_paye_btn;
                                    ?>
                                    <?php if (!empty($t['numero_bordereau'])): ?>
                                        <button type="button" class="btn btn-secondary btn-sm" disabled>
                                            <i class="fas fa-money-bill-wave"></i> Effectuer un paiement
                                        </button>
                                    <?php elseif ($montant_reste_btn <= 0): ?>
                                        <button type="button" class="btn btn-success btn-sm" disabled>
                                            <i class="fas fa-check-circle"></i> Ticket soldé
                                        </button>
                                    <?php elseif (isset($t['prix_unitaire']) && $t['prix_unitaire'] <= 0): ?>
                                        <button type="button" class="btn btn-warning btn-sm" disabled>
                                            <i class="fas fa-exclamation-circle"></i> Prix unitaire non défini
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#payer_ticket<?= (int)$t['id_ticket'] ?>">
                                            <i class="fas fa-money-bill-wave"></i> Effectuer un paiement
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="10" class="text-center py-3 text-muted">Aucun ticket trouvé pour cet agent.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($total_pages_tickets > 1): ?>
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <div class="text-muted small">
                        Page <?= $page_tickets ?> / <?= $total_pages_tickets ?>
                    </div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <?php
                            $baseUrlTickets = 'compte_agent_detail.php?id=' . (int)$id_agent . '&statut_ticket=' . urlencode($statut_ticket_filter) . '&statut_bordereau=' . urlencode($statut_bordereau_filter);
                            ?>
                            <li class="page-item <?= $page_tickets <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= $page_tickets > 1 ? $baseUrlTickets . '&page_tickets=' . ($page_tickets - 1) : '#' ?>">Précédent</a>
                            </li>
                            <li class="page-item <?= $page_tickets >= $total_pages_tickets ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= $page_tickets < $total_pages_tickets ? $baseUrlTickets . '&page_tickets=' . ($page_tickets + 1) : '#' ?>">Suivant</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="bordereaux-section" class="card card-glass mt-3" style="display:none;">
        <div class="card-body">
            <h5 class="mb-3"><i class="fas fa-file-invoice-dollar me-2"></i>Bordereaux de l'agent</h5>
            
            <div class="alert alert-info d-flex align-items-center mb-3">
                <i class="fas fa-info-circle me-2"></i>
                <div>
                    <strong>Important :</strong> Un bordereau doit être approuvé par un superviseur avant de pouvoir effectuer des paiements.
                    Les bordereaux en attente d'approbation ne peuvent pas être payés.
                </div>
            </div>

            <form method="get" class="mb-3">
                <input type="hidden" name="id" value="<?= (int)$id_agent ?>">
                <div class="filter-bar">
                    <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2 me-auto">
                        <span class="filter-label me-sm-2">Statut du bordereau</span>
                        <select name="statut_bordereau" class="form-select form-select-sm">
                            <option value="">Tous</option>
                            <option value="solde" <?= $statut_bordereau_filter === 'solde' ? 'selected' : '' ?>>Soldé</option>
                            <option value="en_cours" <?= $statut_bordereau_filter === 'en_cours' ? 'selected' : '' ?>>En cours de paiement</option>
                            <option value="non_paye" <?= $statut_bordereau_filter === 'non_paye' ? 'selected' : '' ?>>Non payé</option>
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-search me-1"></i> Filtrer
                        </button>
                        <a href="compte_agent_detail.php?id=<?= (int)$id_agent ?>" class="btn btn-outline-secondary btn-sm">
                            Réinitialiser
                        </a>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>N° Bordereau</th>
                            <th>Date début</th>
                            <th>Date fin</th>
                            <th>Nombre de tickets</th>
                            <th>Montant total</th>
                            <th>Payé</th>
                            <th>Reste à payer</th>
                            <th>Approbation</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($bordereaux_paginated)): ?>
                        <?php foreach ($bordereaux_paginated as $b): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($b['numero_bordereau'])): ?>
                                        <a href="view_bordereau.php?numero=<?= urlencode($b['numero_bordereau']) ?>" class="text-decoration-none">
                                            <?= htmlspecialchars($b['numero_bordereau']) ?>
                                        </a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?= !empty($b['date_debut']) ? date('d/m/Y', strtotime($b['date_debut'])) : '-' ?></td>
                                <td><?= !empty($b['date_fin']) ? date('d/m/Y', strtotime($b['date_fin'])) : '-' ?></td>
                                <td><?= isset($b['nombre_tickets']) ? (int)$b['nombre_tickets'] : '-' ?></td>
                                <td><?= isset($b['montant_total']) ? number_format($b['montant_total'], 0, ',', ' ') . ' FCFA' : '-' ?></td>
                                <td>
                                    <?php
                                    $montant_paye_b_aff = isset($b['montant_payer']) ? (float)$b['montant_payer'] : 0;
                                    echo $montant_paye_b_aff > 0
                                        ? number_format($montant_paye_b_aff, 0, ',', ' ') . ' FCFA'
                                        : '0 FCFA';
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $montant_reste_b_aff = isset($b['montant_reste']) ? (float)$b['montant_reste'] : null;
                                    if ($montant_reste_b_aff === null) {
                                        $montant_reste_b_aff = max(($b['montant_total'] ?? 0) - $montant_paye_b_aff, 0);
                                    }
                                    echo number_format($montant_reste_b_aff, 0, ',', ' ') . ' FCFA';
                                    ?>
                                </td>
                                <td>
                                    <?php if (!empty($b['date_validation_boss'])): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check"></i> Approuvé
                                        </span>
                                        <div class="text-muted small">
                                            <?= date('d/m/Y H:i', strtotime($b['date_validation_boss'])) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-warning">
                                            <i class="fas fa-clock"></i> En attente
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $montant_total = isset($b['montant_total']) ? (float)$b['montant_total'] : 0;
                                    $montant_paye_b = isset($b['montant_payer']) ? (float)$b['montant_payer'] : 0;
                                    $montant_reste_b = isset($b['montant_reste']) ? (float)$b['montant_reste'] : null;

                                    if ($montant_reste_b !== null) {
                                        if ($montant_reste_b <= 0 && $montant_total > 0) {
                                            $statut_b = 'Soldé';
                                        } elseif ($montant_paye_b > 0 && $montant_reste_b > 0) {
                                            $statut_b = 'En cours de paiement';
                                        } else {
                                            $statut_b = 'Non payé';
                                        }
                                    } else {
                                        $statut_b = !empty($b['statut_bordereau']) ? $b['statut_bordereau'] : 'Non payé';
                                    }

                                    echo htmlspecialchars($statut_b);
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    // Même logique que paiements.php pour les bordereaux
                                    $montant_total_btn = isset($b['montant_total']) ? (float)$b['montant_total'] : 0;
                                    $montant_paye_btn  = isset($b['montant_payer']) ? (float)$b['montant_payer'] : 0;
                                    $montant_reste_btn = isset($b['montant_reste']) && $b['montant_reste'] !== null
                                        ? (float)$b['montant_reste']
                                        : max($montant_total_btn - $montant_paye_btn, 0);
                                    ?>
                                    <?php if ($montant_reste_btn <= 0): ?>
                                        <button type="button" class="btn btn-success btn-sm" disabled>
                                            <i class="fas fa-check-circle"></i> Bordereau soldé
                                        </button>
                                    <?php elseif (empty($b['date_validation_boss'])): ?>
                                        <button type="button" class="btn btn-warning btn-sm" disabled title="Le bordereau doit être approuvé avant de pouvoir effectuer un paiement">
                                            <i class="fas fa-clock"></i> En attente d'approbation
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#payer_bordereau<?= (int)$b['id_bordereau'] ?>">
                                            <i class="fas fa-money-bill-wave"></i> Effectuer un paiement
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="10" class="text-center py-3 text-muted">Aucun bordereau trouvé pour cet agent.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($total_pages_bord > 1): ?>
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <div class="text-muted small">
                        Page <?= $page_bord ?> / <?= $total_pages_bord ?>
                    </div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <?php
                            $baseUrlBord = 'compte_agent_detail.php?id=' . (int)$id_agent . '&statut_ticket=' . urlencode($statut_ticket_filter) . '&statut_bordereau=' . urlencode($statut_bordereau_filter);
                            ?>
                            <li class="page-item <?= $page_bord <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= $page_bord > 1 ? $baseUrlBord . '&page_bord=' . ($page_bord - 1) : '#' ?>">Précédent</a>
                            </li>
                            <li class="page-item <?= $page_bord >= $total_pages_bord ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= $page_bord < $total_pages_bord ? $baseUrlBord . '&page_bord=' . ($page_bord + 1) : '#' ?>">Suivant</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Modal récapitulatif pour le Total montant (liste des bordereaux validés)
?>
<div class="modal fade" id="modalTotalMontant" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-semibold">Liste des bordereaux</h5>
                    <div class="text-muted small">Bordereaux validés pris en compte dans le total</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <div class="table-responsive rounded-3 border">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width:55%">N° Bordereau</th>
                                <th class="text-end" style="width:45%">Montant total</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $total_modal_montant = 0;
                        foreach ($bordereaux as $b) {
                            if (empty($b['date_validation_boss'])) {
                                continue; // uniquement les bordereaux validés
                            }

                            $montant_total_b = isset($b['montant_total']) ? (float)$b['montant_total'] : 0;
                            $total_modal_montant += $montant_total_b;
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($b['numero_bordereau'] ?? '-') ?></td>
                                <td class="text-end fw-semibold"><?= number_format($montant_total_b, 0, ',', ' ') ?> FCFA</td>
                            </tr>
                        <?php } ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr class="fw-bold">
                                <td class="text-end">Total</td>
                                <td class="text-end text-success"><?= number_format($total_modal_montant, 0, ',', ' ') ?> FCFA</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<?php
// Modal de filtre pour l'historique des transactions (choix date début / date fin)
?>
<div class="modal fade" id="modalFiltreTransactions" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-semibold">Historique des transactions</h5>
                    <div class="text-muted small">Sélectionnez une période pour consulter les paiements</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <form id="formFiltreTransactions">
                    <div class="mb-3">
                        <label class="form-label">Date début</label>
                        <input type="date" class="form-control" name="date_debut" id="date_debut_transactions">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date fin</label>
                        <input type="date" class="form-control" name="date_fin" id="date_fin_transactions">
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="btnVoirHistorique">Voir l'historique</button>
            </div>
        </div>
    </div>
</div>

<?php
// Modals de paiement pour les tickets affichés
if (!empty($tickets_paginated)):
    foreach ($tickets_paginated as $t):
        $financement_solde_modal = isset($financement_stats['solde_financement']) ? (float)$financement_stats['solde_financement'] : 0;
        $montant_total_ticket_modal = isset($t['montant_paie']) && $t['montant_paie'] !== null
            ? (float)$t['montant_paie']
            : 0;
        $montant_payer_modal = isset($t['montant_payer']) && $t['montant_payer'] !== null
            ? (float)$t['montant_payer']
            : 0;
        $montant_reste_modal = $montant_total_ticket_modal - $montant_payer_modal;
        if ($montant_reste_modal < 0) { $montant_reste_modal = 0; }
        
        // Calculer le montant maximum payable selon la source
        if ($financement_solde_modal > 0) {
            // Si financement disponible, limiter au financement
            $montant_max_paiement_ticket = min($montant_reste_modal, $financement_solde_modal);
        } else {
            // Si pas de financement, limiter au solde de caisse
            $montant_max_paiement_ticket = min($montant_reste_modal, $solde_caisse);
        }
        $redirect_page = 'compte_agent_detail.php?id=' . urlencode($id_agent);
?>
<div class="modal fade" id="payer_ticket<?= (int)$t['id_ticket'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Paiement du ticket #<?= htmlspecialchars($t['numero_ticket'] ?? '') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="save_paiement_agent.php">
                    <input type="hidden" name="save_paiement" value="1">
                    <input type="hidden" name="id_ticket" value="<?= (int)$t['id_ticket'] ?>">
                    <input type="hidden" name="numero_ticket" value="<?= htmlspecialchars($t['numero_ticket'] ?? '') ?>">
                    <input type="hidden" name="type" value="tickets">
                    <input type="hidden" name="status" value="non_soldes">
                    <input type="hidden" name="montant_reste" value="<?= $montant_reste_modal ?>">
                    <input type="hidden" name="redirect_page" value="<?= htmlspecialchars($redirect_page) ?>">

                    <div class="mb-3">
                        <label class="form-label">Montant total à payer</label>
                        <input type="text" class="form-control" value="<?= number_format($montant_total_ticket_modal, 0, ',', ' ') ?> FCFA" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Montant déjà payé</label>
                        <input type="text" class="form-control" value="<?= number_format($montant_payer_modal, 0, ',', ' ') ?> FCFA" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reste à payer</label>
                        <input type="text" class="form-control" value="<?= number_format($montant_reste_modal, 0, ',', ' ') ?> FCFA" readonly>
                    </div>

                    <!-- Affichage du solde de caisse -->
                    <div class="mb-3">
                        <div class="alert alert-info d-flex align-items-center">
                            <i class="fas fa-wallet me-2"></i>
                            <div>
                                <strong>Solde Caisse :</strong> <?= number_format($solde_caisse, 0, ',', ' ') ?> FCFA
                                <?php if ($solde_caisse < $montant_reste_modal): ?>
                                    <br><small class="text-warning">⚠️ Solde insuffisant pour payer la totalité</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Source de paiement</label>
                        <select class="form-select" name="source_paiement" required onchange="updateMaxAmountTicketWithCheque(this, <?= (int)$t['id_ticket'] ?>, <?= $montant_reste_modal ?>, <?= $financement_solde_modal ?>, <?= $solde_caisse ?>)">
                            <?php if ($financement_solde_modal > 0): ?>
                                <option value="financement" selected>
                                    Financement (Solde: <?= number_format($financement_solde_modal, 0, ',', ' ') ?> FCFA)
                                </option>
                                <option value="transactions" <?= $solde_caisse <= 0 ? 'disabled style="color: #999; background-color: #f4f4f4;"' : '' ?>>
                                    Sortie de caisse (Solde: <?= number_format($solde_caisse, 0, ',', ' ') ?> FCFA)
                                </option>
                            <?php else: ?>
                                <option value="transactions" <?= $solde_caisse <= 0 ? 'disabled style="color: #999; background-color: #f4f4f4;"' : '' ?>>
                                    Sortie de caisse (Solde: <?= number_format($solde_caisse, 0, ',', ' ') ?> FCFA)
                                </option>
                                <option value="financement" disabled style="color: #999; background-color: #f4f4f4;">
                                    Financement (Solde: <?= number_format(0, 0, ',', ' ') ?> FCFA)
                                </option>
                            <?php endif; ?>
                            <option value="cheque">
                                <i class="fas fa-money-check me-2"></i>Paiement par chèque
                            </option>
                        </select>
                    </div>

                    <!-- Champ numéro de chèque (masqué par défaut) -->
                    <div class="mb-3" id="cheque_field_ticket_<?= (int)$t['id_ticket'] ?>" style="display: none;">
                        <label class="form-label">
                            <i class="fas fa-money-check me-2"></i>Numéro de chèque <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            class="form-control"
                            name="numero_cheque"
                            id="numero_cheque_ticket_<?= (int)$t['id_ticket'] ?>"
                            placeholder="Saisissez le numéro de chèque"
                            maxlength="50">
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Le numéro de chèque est obligatoire pour les paiements par chèque
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" id="label_montant_ticket_<?= (int)$t['id_ticket'] ?>">
                            Montant à payer (Max: <span id="max_amount_ticket_<?= (int)$t['id_ticket'] ?>"><?= number_format($montant_max_paiement_ticket, 0, ',', ' ') ?></span> FCFA)
                        </label>
                        <input
                            type="text"
                            class="form-control montant-input"
                            name="montant_affiche"
                            id="input_montant_ticket_<?= (int)$t['id_ticket'] ?>"
                            required
                            data-max="<?= $montant_max_paiement_ticket ?>"
                            data-target="montant_ticket_<?= (int)$t['id_ticket'] ?>"
                            data-reste="<?= $montant_reste_modal ?>"
                            data-financement="<?= $financement_solde_modal ?>"
                            data-caisse="<?= $solde_caisse ?>"
                            placeholder="Entrez le montant à payer">
                        <input type="hidden" name="montant" id="montant_ticket_<?= (int)$t['id_ticket'] ?>" value="">
                        
                        <!-- Message d'avertissement si solde insuffisant -->
                        <?php if ($solde_caisse < $montant_reste_modal && $financement_solde_modal <= 0): ?>
                            <div class="alert alert-warning mt-2 mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Attention :</strong> Le solde de caisse (<?= number_format($solde_caisse, 0, ',', ' ') ?> FCFA) 
                                est insuffisant pour payer la totalité (<?= number_format($montant_reste_modal, 0, ',', ' ') ?> FCFA).
                                Vous ne pouvez payer que <?= number_format($solde_caisse, 0, ',', ' ') ?> FCFA maximum.
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        <button type="submit" class="btn btn-primary">Enregistrer le paiement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
    endforeach;
endif;

// Modals de paiement pour les bordereaux affichés
if (!empty($bordereaux_paginated)):
    foreach ($bordereaux_paginated as $b):
        $financement_solde_modal = isset($financement_stats['solde_financement']) ? (float)$financement_stats['solde_financement'] : 0;
        $montant_total_bord_modal = isset($b['montant_total']) ? (float)$b['montant_total'] : 0;
        $montant_payer_bord_modal = isset($b['montant_payer']) ? (float)$b['montant_payer'] : 0;
        $montant_reste_bord_modal = isset($b['montant_reste']) && $b['montant_reste'] !== null
            ? (float)$b['montant_reste']
            : max($montant_total_bord_modal - $montant_payer_bord_modal, 0);
        if ($montant_reste_bord_modal < 0) { $montant_reste_bord_modal = 0; }
        
        // Calculer le montant maximum payable selon la source
        if ($financement_solde_modal > 0) {
            // Si financement disponible, limiter au financement
            $montant_max_paiement_bordereau = min($montant_reste_bord_modal, $financement_solde_modal);
        } else {
            // Si pas de financement, limiter au solde de caisse
            $montant_max_paiement_bordereau = min($montant_reste_bord_modal, $solde_caisse);
        }
        $redirect_page = 'compte_agent_detail.php?id=' . urlencode($id_agent);
?>
<div class="modal fade" id="payer_bordereau<?= (int)$b['id_bordereau'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Paiement du bordereau #<?= htmlspecialchars($b['numero_bordereau'] ?? '') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="save_paiement_agent.php">
                    <input type="hidden" name="save_paiement" value="1">
                    <input type="hidden" name="id_bordereau" value="<?= (int)$b['id_bordereau'] ?>">
                    <input type="hidden" name="numero_bordereau" value="<?= htmlspecialchars($b['numero_bordereau'] ?? '') ?>">
                    <input type="hidden" name="type" value="bordereaux">
                    <input type="hidden" name="status" value="non_soldes">
                    <input type="hidden" name="montant_reste" value="<?= $montant_reste_bord_modal ?>">
                    <input type="hidden" name="redirect_page" value="<?= htmlspecialchars($redirect_page) ?>">

                    <div class="mb-3">
                        <label class="form-label">Montant total à payer</label>
                        <input type="text" class="form-control" value="<?= number_format($montant_total_bord_modal, 0, ',', ' ') ?> FCFA" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Montant déjà payé</label>
                        <input type="text" class="form-control" value="<?= number_format($montant_payer_bord_modal, 0, ',', ' ') ?> FCFA" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reste à payer</label>
                        <input type="text" class="form-control" value="<?= number_format($montant_reste_bord_modal, 0, ',', ' ') ?> FCFA" readonly>
                    </div>

                    <!-- Affichage du solde de caisse -->
                    <div class="mb-3">
                        <div class="alert alert-info d-flex align-items-center">
                            <i class="fas fa-wallet me-2"></i>
                            <div>
                                <strong>Solde Caisse :</strong> <?= number_format($solde_caisse, 0, ',', ' ') ?> FCFA
                                <?php if ($solde_caisse < $montant_reste_bord_modal): ?>
                                    <br><small class="text-warning">⚠️ Solde insuffisant pour payer la totalité</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Source de paiement</label>
                        <select class="form-select" name="source_paiement" required onchange="updateMaxAmountWithCheque(this, <?= (int)$b['id_bordereau'] ?>, <?= $montant_reste_bord_modal ?>, <?= $financement_solde_modal ?>, <?= $solde_caisse ?>)">
                            <?php if ($financement_solde_modal > 0): ?>
                                <option value="financement" selected>
                                    Financement (Solde: <?= number_format($financement_solde_modal, 0, ',', ' ') ?> FCFA)
                                </option>
                                <option value="transactions" <?= $solde_caisse <= 0 ? 'disabled style="color: #999; background-color: #f4f4f4;"' : '' ?>>
                                    Sortie de caisse (Solde: <?= number_format($solde_caisse, 0, ',', ' ') ?> FCFA)
                                </option>
                            <?php else: ?>
                                <option value="transactions" <?= $solde_caisse <= 0 ? 'disabled style="color: #999; background-color: #f4f4f4;"' : '' ?>>
                                    Sortie de caisse (Solde: <?= number_format($solde_caisse, 0, ',', ' ') ?> FCFA)
                                </option>
                                <option value="financement" disabled style="color: #999; background-color: #f4f4f4;">
                                    Financement (Solde: <?= number_format(0, 0, ',', ' ') ?> FCFA)
                                </option>
                            <?php endif; ?>
                            <option value="cheque">
                                <i class="fas fa-money-check me-2"></i>Paiement par chèque
                            </option>
                        </select>
                    </div>

                    <!-- Champ numéro de chèque (masqué par défaut) -->
                    <div class="mb-3" id="cheque_field_bordereau_<?= (int)$b['id_bordereau'] ?>" style="display: none;">
                        <label class="form-label">
                            <i class="fas fa-money-check me-2"></i>Numéro de chèque <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            class="form-control"
                            name="numero_cheque"
                            id="numero_cheque_bordereau_<?= (int)$b['id_bordereau'] ?>"
                            placeholder="Saisissez le numéro de chèque"
                            maxlength="50">
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Le numéro de chèque est obligatoire pour les paiements par chèque
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" id="label_montant_bordereau_<?= (int)$b['id_bordereau'] ?>">
                            Montant à payer (Max: <span id="max_amount_bordereau_<?= (int)$b['id_bordereau'] ?>"><?= number_format($montant_max_paiement_bordereau, 0, ',', ' ') ?></span> FCFA)
                        </label>
                        <input
                            type="text"
                            class="form-control montant-input"
                            name="montant_affiche"
                            id="input_montant_bordereau_<?= (int)$b['id_bordereau'] ?>"
                            required
                            data-max="<?= $montant_max_paiement_bordereau ?>"
                            data-target="montant_bordereau_<?= (int)$b['id_bordereau'] ?>"
                            data-reste="<?= $montant_reste_bord_modal ?>"
                            data-financement="<?= $financement_solde_modal ?>"
                            data-caisse="<?= $solde_caisse ?>"
                            placeholder="Entrez le montant à payer">
                        <input type="hidden" name="montant" id="montant_bordereau_<?= (int)$b['id_bordereau'] ?>" value="">
                        
                        <!-- Message d'avertissement si solde insuffisant -->
                        <?php if ($solde_caisse < $montant_reste_bord_modal && $financement_solde_modal <= 0): ?>
                            <div class="alert alert-warning mt-2 mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Attention :</strong> Le solde de caisse (<?= number_format($solde_caisse, 0, ',', ' ') ?> FCFA) 
                                est insuffisant pour payer la totalité (<?= number_format($montant_reste_bord_modal, 0, ',', ' ') ?> FCFA).
                                Vous ne pouvez payer que <?= number_format($solde_caisse, 0, ',', ' ') ?> FCFA maximum.
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        <button type="submit" class="btn btn-primary">Enregistrer le paiement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
    endforeach;
endif;
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const buttons = document.querySelectorAll('.toggle-btn');
    const sections = {
        'tickets-section': document.getElementById('tickets-section'),
        'bordereaux-section': document.getElementById('bordereaux-section')
    };

    buttons.forEach(btn => {
        btn.addEventListener('click', function () {
            const target = this.getAttribute('data-target');

            // activer / désactiver les boutons
            buttons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            // afficher / masquer les sections
            Object.keys(sections).forEach(key => {
                sections[key].style.display = (key === target) ? 'block' : 'none';
            });
        });
    });

    function formatNumber(number) {
        number = number.replace(/[^\d]/g, '');
        if (!number) return '';
        let value = parseInt(number, 10);
        if (isNaN(value)) return '';
        return value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    }

    function unformatNumber(formattedNumber) {
        return formattedNumber.replace(/\s/g, '');
    }

    document.querySelectorAll('.montant-input').forEach(function(input) {
        input.addEventListener('input', function () {
            const max = parseInt(this.dataset.max, 10) || 0;
            const raw = unformatNumber(this.value);
            let value = parseInt(raw, 10);
            if (isNaN(value)) {
                value = 0;
            }
            if (value > max) {
                alert('Le montant ne peut pas dépasser ' + formatNumber(max.toString()) + ' FCFA');
                value = max;
            }
            this.value = value ? formatNumber(value.toString()) : '';
            const targetId = this.dataset.target;
            if (targetId) {
                const hidden = document.getElementById(targetId);
                if (hidden) hidden.value = value || '';
            }
        });
    });

    const btnVoirHistorique = document.getElementById('btnVoirHistorique');
    if (btnVoirHistorique) {
        btnVoirHistorique.addEventListener('click', function () {
            const dateDebutInput = document.getElementById('date_debut_transactions');
            const dateFinInput   = document.getElementById('date_fin_transactions');
            const dateDebut = dateDebutInput ? dateDebutInput.value : '';
            const dateFin   = dateFinInput ? dateFinInput.value : '';

            if (!dateDebut || !dateFin) {
                alert('Veuillez sélectionner une date début et une date fin.');
                return;
            }

            const baseUrl = 'view_historique.php';
            const params = new URLSearchParams();
            params.set('id', '<?= (int)$id_agent ?>');
            params.set('date_debut', dateDebut);
            params.set('date_fin', dateFin);

            const url = baseUrl + '?' + params.toString();
            window.open(url, '_blank');
        });
    }

    // Fonction pour mettre à jour le montant maximum selon la source de paiement (avec chèque)
    window.updateMaxAmountWithCheque = function(selectElement, bordereauId, montantReste, financementSolde, soldeCaisse) {
        const sourceValue = selectElement.value;
        const inputElement = document.getElementById('input_montant_bordereau_' + bordereauId);
        const maxAmountSpan = document.getElementById('max_amount_bordereau_' + bordereauId);
        const chequeField = document.getElementById('cheque_field_bordereau_' + bordereauId);
        const chequeInput = document.getElementById('numero_cheque_bordereau_' + bordereauId);
        
        // Afficher/masquer le champ numéro de chèque
        if (sourceValue === 'cheque') {
            chequeField.style.display = 'block';
            chequeInput.setAttribute('required', 'required');
        } else {
            chequeField.style.display = 'none';
            chequeInput.removeAttribute('required');
            chequeInput.value = '';
        }
        
        let maxAmount;
        if (sourceValue === 'financement') {
            maxAmount = Math.min(montantReste, financementSolde);
        } else if (sourceValue === 'cheque') {
            // Pour les chèques, pas de limitation par le solde
            maxAmount = montantReste;
        } else {
            maxAmount = Math.min(montantReste, soldeCaisse);
        }
        
        // Mettre à jour l'attribut data-max et l'affichage
        inputElement.setAttribute('data-max', maxAmount);
        maxAmountSpan.textContent = formatNumber(maxAmount.toString());
        
        // Vider le champ si la valeur actuelle dépasse le nouveau maximum
        const currentValue = parseInt(unformatNumber(inputElement.value) || '0');
        if (currentValue > maxAmount) {
            inputElement.value = '';
            const hiddenInput = document.getElementById('montant_bordereau_' + bordereauId);
            if (hiddenInput) hiddenInput.value = '';
        }
        
        // Afficher un message si le solde est insuffisant (sauf pour les chèques)
        const warningDiv = inputElement.parentNode.querySelector('.alert-warning');
        if (sourceValue === 'transactions' && soldeCaisse < montantReste) {
            if (!warningDiv) {
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-warning mt-2 mb-0';
                alertDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>' +
                    '<strong>Attention :</strong> Le solde de caisse (' + formatNumber(soldeCaisse.toString()) + ' FCFA) ' +
                    'est insuffisant pour payer la totalité (' + formatNumber(montantReste.toString()) + ' FCFA). ' +
                    'Vous ne pouvez payer que ' + formatNumber(soldeCaisse.toString()) + ' FCFA maximum.';
                inputElement.parentNode.appendChild(alertDiv);
            }
        } else if (warningDiv) {
            warningDiv.remove();
        }
    };

    // Fonction pour mettre à jour le montant maximum selon la source de paiement (tickets avec chèque)
    window.updateMaxAmountTicketWithCheque = function(selectElement, ticketId, montantReste, financementSolde, soldeCaisse) {
        const sourceValue = selectElement.value;
        const inputElement = document.getElementById('input_montant_ticket_' + ticketId);
        const maxAmountSpan = document.getElementById('max_amount_ticket_' + ticketId);
        const chequeField = document.getElementById('cheque_field_ticket_' + ticketId);
        const chequeInput = document.getElementById('numero_cheque_ticket_' + ticketId);
        
        // Afficher/masquer le champ numéro de chèque
        if (sourceValue === 'cheque') {
            chequeField.style.display = 'block';
            chequeInput.setAttribute('required', 'required');
        } else {
            chequeField.style.display = 'none';
            chequeInput.removeAttribute('required');
            chequeInput.value = '';
        }
        
        let maxAmount;
        if (sourceValue === 'financement') {
            maxAmount = Math.min(montantReste, financementSolde);
        } else if (sourceValue === 'cheque') {
            // Pour les chèques, pas de limitation par le solde
            maxAmount = montantReste;
        } else {
            maxAmount = Math.min(montantReste, soldeCaisse);
        }
        
        // Mettre à jour l'attribut data-max et l'affichage
        inputElement.setAttribute('data-max', maxAmount);
        maxAmountSpan.textContent = formatNumber(maxAmount.toString());
        
        // Vider le champ si la valeur actuelle dépasse le nouveau maximum
        const currentValue = parseInt(unformatNumber(inputElement.value) || '0');
        if (currentValue > maxAmount) {
            inputElement.value = '';
            const hiddenInput = document.getElementById('montant_ticket_' + ticketId);
            if (hiddenInput) hiddenInput.value = '';
        }
        
        // Afficher un message si le solde est insuffisant (sauf pour les chèques)
        const warningDiv = inputElement.parentNode.querySelector('.alert-warning');
        if (sourceValue === 'transactions' && soldeCaisse < montantReste) {
            if (!warningDiv) {
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-warning mt-2 mb-0';
                alertDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>' +
                    '<strong>Attention :</strong> Le solde de caisse (' + formatNumber(soldeCaisse.toString()) + ' FCFA) ' +
                    'est insuffisant pour payer la totalité (' + formatNumber(montantReste.toString()) + ' FCFA). ' +
                    'Vous ne pouvez payer que ' + formatNumber(soldeCaisse.toString()) + ' FCFA maximum.';
                inputElement.parentNode.appendChild(alertDiv);
            }
        } else if (warningDiv) {
            warningDiv.remove();
        }
    };

    // Fonction pour mettre à jour le montant maximum selon la source de paiement (ancienne version)
    window.updateMaxAmount = function(selectElement, bordereauId, montantReste, financementSolde, soldeCaisse) {
        const sourceValue = selectElement.value;
        const inputElement = document.getElementById('input_montant_bordereau_' + bordereauId);
        const maxAmountSpan = document.getElementById('max_amount_bordereau_' + bordereauId);
        
        let maxAmount;
        if (sourceValue === 'financement') {
            maxAmount = Math.min(montantReste, financementSolde);
        } else {
            maxAmount = Math.min(montantReste, soldeCaisse);
        }
        
        // Mettre à jour l'attribut data-max et l'affichage
        inputElement.setAttribute('data-max', maxAmount);
        maxAmountSpan.textContent = formatNumber(maxAmount.toString());
        
        // Vider le champ si la valeur actuelle dépasse le nouveau maximum
        const currentValue = parseInt(unformatNumber(inputElement.value) || '0');
        if (currentValue > maxAmount) {
            inputElement.value = '';
            const hiddenInput = document.getElementById('montant_bordereau_' + bordereauId);
            if (hiddenInput) hiddenInput.value = '';
        }
        
        // Afficher un message si le solde est insuffisant
        const warningDiv = inputElement.parentNode.querySelector('.alert-warning');
        if (sourceValue === 'transactions' && soldeCaisse < montantReste) {
            if (!warningDiv) {
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-warning mt-2 mb-0';
                alertDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>' +
                    '<strong>Attention :</strong> Le solde de caisse (' + formatNumber(soldeCaisse.toString()) + ' FCFA) ' +
                    'est insuffisant pour payer la totalité (' + formatNumber(montantReste.toString()) + ' FCFA). ' +
                    'Vous ne pouvez payer que ' + formatNumber(soldeCaisse.toString()) + ' FCFA maximum.';
                inputElement.parentNode.appendChild(alertDiv);
            }
        } else if (warningDiv) {
            warningDiv.remove();
        }
    };

    // Fonction pour mettre à jour le montant maximum selon la source de paiement (tickets)
    window.updateMaxAmountTicket = function(selectElement, ticketId, montantReste, financementSolde, soldeCaisse) {
        const sourceValue = selectElement.value;
        const inputElement = document.getElementById('input_montant_ticket_' + ticketId);
        const maxAmountSpan = document.getElementById('max_amount_ticket_' + ticketId);
        
        let maxAmount;
        if (sourceValue === 'financement') {
            maxAmount = Math.min(montantReste, financementSolde);
        } else {
            maxAmount = Math.min(montantReste, soldeCaisse);
        }
        
        // Mettre à jour l'attribut data-max et l'affichage
        inputElement.setAttribute('data-max', maxAmount);
        maxAmountSpan.textContent = formatNumber(maxAmount.toString());
        
        // Vider le champ si la valeur actuelle dépasse le nouveau maximum
        const currentValue = parseInt(unformatNumber(inputElement.value) || '0');
        if (currentValue > maxAmount) {
            inputElement.value = '';
            const hiddenInput = document.getElementById('montant_ticket_' + ticketId);
            if (hiddenInput) hiddenInput.value = '';
        }
        
        // Afficher un message si le solde est insuffisant
        const warningDiv = inputElement.parentNode.querySelector('.alert-warning');
        if (sourceValue === 'transactions' && soldeCaisse < montantReste) {
            if (!warningDiv) {
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-warning mt-2 mb-0';
                alertDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>' +
                    '<strong>Attention :</strong> Le solde de caisse (' + formatNumber(soldeCaisse.toString()) + ' FCFA) ' +
                    'est insuffisant pour payer la totalité (' + formatNumber(montantReste.toString()) + ' FCFA). ' +
                    'Vous ne pouvez payer que ' + formatNumber(soldeCaisse.toString()) + ' FCFA maximum.';
                inputElement.parentNode.appendChild(alertDiv);
            }
        } else if (warningDiv) {
            warningDiv.remove();
        }
    };

    // Validation avant soumission du formulaire
    document.querySelectorAll('form[action="save_paiement_agent.php"]').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const sourceSelect = form.querySelector('select[name="source_paiement"]');
            const montantInput = form.querySelector('input[name="montant"]');
            const chequeInput = form.querySelector('input[name="numero_cheque"]');
            
            if (!montantInput || !montantInput.value || parseFloat(montantInput.value) <= 0) {
                e.preventDefault();
                alert('Veuillez saisir un montant valide.');
                return false;
            }
            
            // Validation du numéro de chèque si nécessaire
            if (sourceSelect && sourceSelect.value === 'cheque') {
                if (!chequeInput || !chequeInput.value.trim()) {
                    e.preventDefault();
                    alert('Veuillez saisir le numéro de chèque.');
                    if (chequeInput) chequeInput.focus();
                    return false;
                }
                
                // Validation du format du numéro de chèque (optionnel)
                const chequeNumber = chequeInput.value.trim();
                if (chequeNumber.length < 3) {
                    e.preventDefault();
                    alert('Le numéro de chèque doit contenir au moins 3 caractères.');
                    chequeInput.focus();
                    return false;
                }
            }
            
            const montant = parseFloat(montantInput.value);
            const maxAmount = parseFloat(montantInput.parentNode.querySelector('.montant-input').getAttribute('data-max'));
            
            if (montant > maxAmount) {
                e.preventDefault();
                alert('Le montant saisi (' + formatNumber(montant.toString()) + ' FCFA) dépasse le maximum autorisé (' + formatNumber(maxAmount.toString()) + ' FCFA).');
                return false;
            }
            
            // Confirmation pour les gros montants
            if (montant > 1000000) {
                if (!confirm('Vous êtes sur le point de payer ' + formatNumber(montant.toString()) + ' FCFA. Confirmez-vous ce paiement ?')) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    });
});
</script>
</body>
</html>
