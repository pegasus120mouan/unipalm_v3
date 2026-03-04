<?php
require_once '../inc/functions/connexion.php';
require_once '../inc/functions/requete/requete_ponts.php';

// Traitement des actions (ajout, modification, suppression) - AVANT le header
if ($_POST) {
    try {
        if (isset($_POST['add_pont'])) {
            $result = createPontBascule($conn, $_POST['nom_pont'], null, null, $_POST['gerant'], $_POST['cooperatif'], $_POST['statut']);
            if ($result) {
                // Redirection avec message de succès pour éviter la double soumission
                header('Location: ponts.php?success=add&code=' . urlencode($result));
                exit();
            } else {
                header('Location: ponts.php?error=add');
                exit();
            }
        }
        
        if (isset($_POST['update_pont'])) {
            $result = updatePontBascule($conn, $_POST['id_pont'], $_POST['code_pont'], $_POST['nom_pont'], $_POST['latitude'], $_POST['longitude'], $_POST['gerant'], $_POST['cooperatif'], $_POST['statut']);
            if ($result) {
                header('Location: ponts.php?success=update');
                exit();
            } else {
                header('Location: ponts.php?error=update');
                exit();
            }
        }
        
        if (isset($_POST['delete_pont'])) {
            $result = deletePontBascule($conn, $_POST['id_pont']);
            if ($result) {
                header('Location: ponts.php?success=delete');
                exit();
            } else {
                header('Location: ponts.php?error=delete');
                exit();
            }
        }
    } catch (Exception $e) {
        header('Location: ponts.php?error=exception&msg=' . urlencode($e->getMessage()));
        exit();
    }
}

// Gestion des messages via GET (après redirection)
$clean_url_needed = false;

if (isset($_GET['success'])) {
    $clean_url_needed = true;
    switch ($_GET['success']) {
        case 'add':
            $success_message = "Pont-bascule ajouté avec succès ! Code généré: " . ($_GET['code'] ?? '');
            break;
        case 'update':
            $success_message = "Pont-bascule modifié avec succès !";
            break;
        case 'delete':
            $success_message = "Pont-bascule supprimé avec succès !";
            break;
    }
}

if (isset($_GET['error'])) {
    $clean_url_needed = true;
    switch ($_GET['error']) {
        case 'add':
            $error_message = "Erreur lors de l'ajout du pont-bascule.";
            break;
        case 'update':
            $error_message = "Erreur lors de la modification du pont-bascule.";
            break;
        case 'delete':
            $error_message = "Erreur lors de la suppression du pont-bascule.";
            break;
        case 'exception':
            $error_message = "Erreur lors de l'opération : " . ($_GET['msg'] ?? 'Erreur inconnue');
            break;
    }
}

// Récupérer tous les ponts-bascules
$ponts = getAllPontsBascules($conn);
if ($ponts === false) {
    $error_message = "Erreur lors de la récupération des données.";
    $ponts = [];
}

// Inclure le header APRÈS le traitement POST
include('header.php');
?>

<style>
    /* Variables CSS UniPalm */
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        --danger-gradient: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
        --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        --glass-bg: rgba(255, 255, 255, 0.25);
        --glass-border: rgba(255, 255, 255, 0.18);
        --shadow-light: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        --border-radius: 16px;
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
    }

    /* Page Header Professional */
    .page-header {
        background: var(--primary-gradient);
        color: white;
        padding: 2rem 0;
        margin-bottom: 2rem;
        border-radius: 0 0 var(--border-radius) var(--border-radius);
        box-shadow: var(--shadow-light);
        position: relative;
        overflow: hidden;
    }

    .page-header h1 {
        font-size: 2.5rem;
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 1rem;
        position: relative;
        z-index: 1;
    }

    .page-header p {
        font-size: 1.1rem;
        opacity: 0.9;
        margin: 0.5rem 0 0 0;
        position: relative;
        z-index: 1;
    }

    /* Action Buttons */
    .action-buttons-container {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
        flex-wrap: wrap;
    }

    .btn-professional {
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 12px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: var(--transition);
        cursor: pointer;
        font-size: 0.95rem;
        min-width: 150px;
        justify-content: center;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .btn-professional:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        text-decoration: none;
    }

    .btn-professional.primary {
        background: var(--primary-gradient);
        color: white;
    }

    .btn-professional.warning {
        background: var(--warning-gradient);
        color: white;
    }

    /* Styles pour les boutons circulaires */
    .action-buttons-container {
        display: flex;
        gap: 8px;
        justify-content: center;
        align-items: center;
    }

    .btn-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }

    .btn-circle:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.25);
    }

    .btn-circle:active {
        transform: translateY(0);
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }

    .btn-circle-success {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
    }

    .btn-circle-success:hover {
        background: linear-gradient(135deg, #218838, #1ea080);
        color: white;
    }

    .btn-circle-primary {
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white;
    }

    .btn-circle-primary:hover {
        background: linear-gradient(135deg, #0056b3, #004085);
        color: white;
    }

    .btn-circle-danger {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
    }

    .btn-circle-danger:hover {
        background: linear-gradient(135deg, #c82333, #a71e2a);
        color: white;
    }

    /* Animation de pulsation pour les boutons */
    .btn-circle::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255,255,255,0.3);
        transition: all 0.3s ease;
        transform: translate(-50%, -50%);
    }

    .btn-circle:active::before {
        width: 100%;
        height: 100%;
    }

    /* Styles pour la modale de modification */
    #edit-pont .modal-header {
        background: linear-gradient(135deg, #ffc107, #ff8f00) !important;
    }

    #edit-pont .form-control:focus {
        border-color: #ffc107;
        box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
    }

    #edit-pont .btn-warning {
        background: linear-gradient(135deg, #ffc107, #ff8f00);
        border: none;
        transition: all 0.3s ease;
    }

    #edit-pont .btn-warning:hover {
        background: linear-gradient(135deg, #ff8f00, #f57c00);
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

    #gps_preview {
        min-height: 40px;
        display: flex;
        align-items: center;
    }

    /* Styles pour les filtres */
    .filter-container .card {
        border: 1px solid #e3e6f0;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    }

    .filter-container .card-header {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-bottom: 1px solid #e3e6f0;
    }

    .filter-container .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }

    .filter-container .btn-secondary {
        background: linear-gradient(135deg, #6c757d, #5a6268);
        border: none;
        transition: all 0.3s ease;
    }

    .filter-container .btn-secondary:hover {
        background: linear-gradient(135deg, #5a6268, #495057);
        transform: translateY(-1px);
    }

    .table-row-hidden {
        display: none !important;
    }

    /* Styles pour la barre de recherche rapide */
    .input-group .input-group-text {
        background: linear-gradient(135deg, #667eea, #764ba2);
        border: none;
    }

    .input-group .form-control {
        border: 2px solid #e3e6f0;
        transition: all 0.3s ease;
    }

    .input-group .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }

    .input-group .btn-outline-secondary {
        border: 2px solid #e3e6f0;
        border-left: none;
    }

    .input-group .btn-outline-secondary:hover {
        background-color: #dc3545;
        border-color: #dc3545;
        color: white;
    }

    .table-container {
        background: var(--glass-bg);
        backdrop-filter: blur(15px);
        border: 1px solid var(--glass-border);
        border-radius: var(--border-radius);
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: var(--shadow-light);
        overflow: hidden;
    }

    .table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #f8f9fa;
    }

    .table-title {
        font-size: 1.4rem;
        font-weight: 700;
        color: #2c3e50;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .table-professional {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }

    .table-professional thead {
        background: var(--primary-gradient);
        color: white;
    }

    .table-professional th {
        padding: 1rem;
        font-weight: 600;
        text-align: left;
        border: none;
        font-size: 0.9rem;
    }

    .table-professional td {
        padding: 1rem;
        border-bottom: 1px solid #f8f9fa;
        vertical-align: middle;
    }

    .table-professional tbody tr:hover {
        background-color: #f8f9fa;
        transform: scale(1.01);
        transition: var(--transition);
    }

    .badge-professional {
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.75rem;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }

    .badge-success {
        background: var(--success-gradient);
        color: white;
    }

    .badge-info {
        background: var(--info-gradient);
        color: white;
    }

    .avatar-circle {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.8rem;
    }
</style>

<!-- Content Wrapper -->
<div class="content-wrapper">
    <!-- Page Header -->
    <div class="page-header">
        <div class="container-fluid">
            <h1>
                <i class="fas fa-weight-hanging"></i>
                Gestion des Ponts-Bascules
            </h1>
            <p>Gérez les ponts-bascules, leurs gérants et leurs localisations GPS</p>
        </div>
    </div>

    <!-- Main Content -->
    <section class="content">
        <div class="container-fluid">
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?= $success_message ?>
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <?= $error_message ?>
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Barre de recherche rapide -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-primary text-white">
                                <i class="fas fa-search"></i>
                            </span>
                        </div>
                        <input type="text" class="form-control" id="quickSearch" placeholder="Recherche rapide (code, nom, gérant, coopérative...)" onkeyup="quickSearchPonts()">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" onclick="clearQuickSearch()" title="Effacer">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 text-right">
                    <span class="text-muted" id="quickSearchResults">
                        <i class="fas fa-info-circle mr-1"></i>
                        <span id="quickVisibleCount"><?= count($ponts) ?></span> pont(s) affiché(s)
                    </span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons-container">
                <button type="button" class="btn-professional primary" data-toggle="modal" data-target="#add-pont">
                    <i class="fas fa-plus-circle"></i>
                    Nouveau Pont-Bascule
                </button>
                
                <button type="button" class="btn-professional success" onclick="exportData()">
                    <i class="fas fa-file-export"></i>
                    Exporter la Liste
                </button>
                
                <button type="button" class="btn-professional warning" onclick="window.open('geolocalisation_ponts.php', '_blank')">
                    <i class="fas fa-map-marked-alt"></i>
                    Voir sur la Carte
                </button>
            </div>

            <!-- Barre de Filtres -->
            <div class="filter-container mb-4">
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="fas fa-filter mr-2"></i>Filtres de Recherche
                            <button class="btn btn-sm btn-outline-secondary float-right" onclick="toggleFilters()" id="filterToggle">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </h6>
                    </div>
                    <div class="card-body" id="filterBody" style="display: none;">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="searchCode">Code Pont</label>
                                    <input type="text" class="form-control" id="searchCode" placeholder="UNIPALM-PB-..." onkeyup="filterPonts()">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="searchNom">Nom du Pont</label>
                                    <input type="text" class="form-control" id="searchNom" placeholder="Nom du pont..." onkeyup="filterPonts()">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="searchGerant">Gérant</label>
                                    <input type="text" class="form-control" id="searchGerant" placeholder="Nom gérant..." onkeyup="filterPonts()">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="searchCooperative">Coopérative</label>
                                    <select class="form-control" id="searchCooperative" onchange="filterPonts()">
                                        <option value="">Toutes</option>
                                        <?php 
                                        $cooperatives = array_unique(array_filter(array_column($ponts, 'cooperatif')));
                                        foreach ($cooperatives as $coop): ?>
                                            <option value="<?= htmlspecialchars($coop) ?>"><?= htmlspecialchars($coop) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="searchStatut">Statut</label>
                                    <select class="form-control" id="searchStatut" onchange="filterPonts()">
                                        <option value="">Tous</option>
                                        <option value="Actif">Actif</option>
                                        <option value="Inactif">Inactif</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <button class="btn btn-secondary btn-sm" onclick="clearFilters()">
                                    <i class="fas fa-eraser mr-1"></i>Effacer les filtres
                                </button>
                                <span class="ml-3 text-muted" id="filterResults">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    <span id="visibleCount"><?= count($ponts) ?></span> pont(s) affiché(s) sur <?= count($ponts) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Container -->
            <div class="table-container">
                <div class="table-header">
                    <div class="table-title">
                        <i class="fas fa-list-alt"></i>
                        Liste des Ponts-Bascules
                    </div>
                    <div class="badge badge-info">
                        <i class="fas fa-database mr-1"></i>
                        <?= count($ponts) ?> pont<?= count($ponts) > 1 ? 's' : '' ?>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table-professional" id="pontsTable">
                        <thead class="thead-gradient">
                            <tr>
                                <th><i class="fas fa-hashtag mr-2"></i>Code Pont</th>
                                <th><i class="fas fa-building mr-2"></i>Nom du Pont</th>
                                <th><i class="fas fa-map-marker-alt mr-2"></i>Coordonnées GPS</th>
                                <th><i class="fas fa-user-tie mr-2"></i>Gérant</th>
                                <th><i class="fas fa-users mr-2"></i>Coopérative</th>
                                <th><i class="fas fa-toggle-on mr-2"></i>Statut</th>
                                <th><i class="fas fa-cogs mr-2"></i>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ponts)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Aucun pont-bascule enregistré</p>
                                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#add-pont">
                                            <i class="fas fa-plus mr-1"></i>Ajouter le premier pont
                                        </button>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($ponts as $pont): ?>
                                    <tr>
                                        <td>
                                            <strong class="text-primary"><?= htmlspecialchars($pont['code_pont']) ?></strong>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-building mr-2 text-info"></i>
                                                <strong><?= htmlspecialchars($pont['nom_pont'] ?? 'Non défini') ?></strong>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <small class="text-muted">Lat: <?= $pont['latitude'] ?></small>
                                                <small class="text-muted">Lng: <?= $pont['longitude'] ?></small>
                                                <a href="https://maps.google.com/?q=<?= $pont['latitude'] ?>,<?= $pont['longitude'] ?>" 
                                                   target="_blank" class="btn btn-sm btn-outline-info mt-1">
                                                    <i class="fas fa-external-link-alt"></i> Voir
                                                </a>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle bg-success text-white mr-2">
                                                    <?= strtoupper(substr($pont['gerant'], 0, 2)) ?>
                                                </div>
                                                <?= htmlspecialchars($pont['gerant']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($pont['cooperatif']): ?>
                                                <span class="badge-professional badge-success">
                                                    <i class="fas fa-handshake"></i>
                                                    <?= htmlspecialchars($pont['cooperatif']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">Non spécifiée</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (($pont['statut'] ?? 'Inactif') === 'Actif'): ?>
                                                <span class="badge badge-success">
                                                    <i class="fas fa-check-circle mr-1"></i>Actif
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">
                                                    <i class="fas fa-pause-circle mr-1"></i>Inactif
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons-container">
                                                <button type="button" class="btn-circle btn-circle-success" 
                                                        onclick="generateQRCode(<?= $pont['id_pont'] ?>, '<?= htmlspecialchars($pont['code_pont']) ?>', '<?= htmlspecialchars($pont['gerant']) ?>', <?= $pont['latitude'] ?>, <?= $pont['longitude'] ?>, '<?= htmlspecialchars($pont['cooperatif'] ?? '') ?>')" 
                                                        title="Générer QR Code" data-toggle="tooltip">
                                                    <i class="fas fa-qrcode"></i>
                                                </button>
                                                <button type="button" class="btn-circle btn-circle-primary" 
                                                        onclick="editPontDirect(<?= $pont['id_pont'] ?>, '<?= htmlspecialchars($pont['code_pont']) ?>', '<?= htmlspecialchars($pont['nom_pont'] ?? '') ?>', <?= $pont['latitude'] ?>, <?= $pont['longitude'] ?>, '<?= htmlspecialchars($pont['gerant']) ?>', '<?= htmlspecialchars($pont['cooperatif'] ?? '') ?>', '<?= $pont['statut'] ?? 'Inactif' ?>')" 
                                                        title="Modifier les informations" data-toggle="tooltip">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn-circle btn-circle-danger" 
                                                        onclick="deletePont(<?= $pont['id_pont'] ?>, '<?= htmlspecialchars($pont['code_pont']) ?>')" 
                                                        title="Supprimer définitivement" data-toggle="tooltip">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal Ajout Pont-Bascule -->
<div class="modal fade" id="add-pont" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle mr-2"></i>
                    Nouveau Pont-Bascule
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Code automatique :</strong> Le code sera généré au format professionnel<br>
                        <small class="text-muted">Exemple: UNIPALM-PB-0001-CI, UNIPALM-PB-0002-CI, etc.</small>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nom_pont">Nom du Pont *</label>
                                <input type="text" class="form-control" id="nom_pont" name="nom_pont" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="gerant">Gérant *</label>
                                <input type="text" class="form-control" id="gerant" name="gerant" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="cooperatif">Coopérative</label>
                                <input type="text" class="form-control" id="cooperatif" name="cooperatif">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="statut">Statut *</label>
                                <select class="form-control" id="statut" name="statut" required>
                                    <option value="Actif" selected>Actif</option>
                                    <option value="Inactif">Inactif</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                    <button type="submit" name="add_pont" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Modification Pont-Bascule -->
<div class="modal fade" id="edit-pont" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title">
                    <i class="fas fa-edit mr-2"></i>
                    Modifier le Pont-Bascule
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" id="editPontForm">
                <div class="modal-body">
                    <input type="hidden" id="edit_id_pont" name="id_pont">
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Modification :</strong> Vous pouvez modifier tous les champs sauf le code pont qui est généré automatiquement.
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_code_pont">Code Pont</label>
                                <input type="text" class="form-control" id="edit_code_pont" name="code_pont" readonly style="background-color: #f8f9fa;">
                                <small class="text-muted">Le code pont ne peut pas être modifié</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_nom_pont">Nom du Pont *</label>
                                <input type="text" class="form-control" id="edit_nom_pont" name="nom_pont" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_latitude">Latitude *</label>
                                <input type="number" step="any" class="form-control" id="edit_latitude" name="latitude" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_longitude">Longitude *</label>
                                <input type="number" step="any" class="form-control" id="edit_longitude" name="longitude" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_gerant">Gérant *</label>
                                <input type="text" class="form-control" id="edit_gerant" name="gerant" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_cooperatif">Coopérative</label>
                                <input type="text" class="form-control" id="edit_cooperatif" name="cooperatif">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_statut">Statut *</label>
                                <select class="form-control" id="edit_statut" name="statut" required>
                                    <option value="Actif">Actif</option>
                                    <option value="Inactif">Inactif</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-muted">Aperçu GPS</label>
                                <div class="border rounded p-2" style="background-color: #f8f9fa;">
                                    <small class="text-muted" id="gps_preview">Coordonnées GPS s'afficheront ici</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>Annuler
                    </button>
                    <button type="submit" name="update_pont" class="btn btn-warning">
                        <i class="fas fa-save mr-1"></i>Sauvegarder les modifications
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal QR Code -->
<div class="modal fade" id="qrCodeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-qrcode mr-2"></i>
                    QR Code du Pont-Bascule
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <div id="qrCodeContainer">
                    <div class="spinner-border text-success mb-3" role="status">
                        <span class="sr-only">Génération en cours...</span>
                    </div>
                    <p>Génération du QR Code en cours...</p>
                </div>
                
                <div id="qrCodeInfo" style="display:none;">
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-mobile-alt mr-2"></i>
                        <strong>Scannez ce QR code</strong> pour accéder à la page de vérification en ligne
                    </div>
                    
                    <div class="card mt-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Informations du Pont-Bascule</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Code:</strong> <span id="qr-code"></span></p>
                                    <p><strong>Gérant:</strong> <span id="qr-gerant"></span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Latitude:</strong> <span id="qr-latitude"></span></p>
                                    <p><strong>Longitude:</strong> <span id="qr-longitude"></span></p>
                                </div>
                            </div>
                            <p><strong>Coopérative:</strong> <span id="qr-cooperative"></span></p>
                        </div>
                    </div>
                    
                    <div class="small text-muted mt-2">
                        URL de vérification: <br>
                        <code id="verification-url"></code>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="downloadQRCode" style="display:none;">
                    <i class="fas fa-download mr-1"></i>Télécharger QR Code
                </button>
                <button type="button" class="btn btn-primary" id="printQRCode" style="display:none;">
                    <i class="fas fa-print mr-1"></i>Imprimer
                </button>
                <button type="button" class="btn btn-info" id="testVerification" style="display:none;">
                    <i class="fas fa-external-link-alt mr-1"></i>Tester la vérification
                </button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<script>
// Nettoyer l'URL après affichage du message
<?php if ($clean_url_needed): ?>
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.pathname);
}
<?php endif; ?>

// Initialiser les tooltips et événements
$(document).ready(function() {
    $('[data-toggle="tooltip"]').tooltip();
    
    // Mettre à jour l'aperçu GPS en temps réel
    $('#edit_latitude, #edit_longitude').on('input', function() {
        updateGPSPreview();
    });
});

// Fonctions pour la gestion des ponts-bascules
function editPont(id) {
    // Récupérer les données du pont depuis le tableau
    var row = document.querySelector('button[onclick*="editPont(' + id + ')"]').closest('tr');
    var cells = row.querySelectorAll('td');
    
    // Vérifier qu'on a assez de cellules
    if (cells.length < 7) {
        alert('Erreur: Structure du tableau incorrecte');
        return;
    }
    
    // Extraire les données selon la nouvelle structure
    var code_pont = cells[0].textContent.trim();
    
    // Nom du pont (cellule 1)
    var nom_pont_element = cells[1].querySelector('strong');
    var nom_pont = nom_pont_element ? nom_pont_element.textContent.trim() : '';
    
    // Coordonnées (cellule 2)
    var coordsText = cells[2].textContent;
    var latMatch = coordsText.match(/Lat:\s*([-\d.]+)/);
    var lngMatch = coordsText.match(/Lng:\s*([-\d.]+)/);
    var latitude = latMatch ? latMatch[1] : '';
    var longitude = lngMatch ? lngMatch[1] : '';
    
    // Gérant (cellule 3) - récupérer le texte après l'avatar
    var gerant_cell = cells[3];
    var gerant = gerant_cell.textContent.trim();
    // Enlever les initiales de l'avatar (les 2 premières lettres en majuscules)
    gerant = gerant.replace(/^[A-Z]{2}\s*/, '');
    
    // Coopérative (cellule 4)
    var cooperatif_element = cells[4].querySelector('.badge-professional');
    var cooperatif = '';
    if (cooperatif_element) {
        cooperatif = cooperatif_element.textContent.trim();
        // Enlever l'icône du texte
        cooperatif = cooperatif.replace(/^\s*\S+\s*/, ''); // Enlever le premier "mot" (icône)
    } else {
        cooperatif = '';
    }
    
    // Statut (cellule 5)
    var statut_element = cells[5].querySelector('.badge');
    var statut = 'Inactif';
    if (statut_element) {
        var statut_text = statut_element.textContent.trim();
        statut = statut_text.includes('Actif') ? 'Actif' : 'Inactif';
    }
    
    // Nettoyer les données
    if (nom_pont === 'Non défini') nom_pont = '';
    if (cooperatif === 'Non spécifiée') cooperatif = '';
    
    // Remplir le formulaire de modification
    document.getElementById('edit_id_pont').value = id;
    document.getElementById('edit_code_pont').value = code_pont;
    document.getElementById('edit_nom_pont').value = nom_pont;
    document.getElementById('edit_latitude').value = latitude;
    document.getElementById('edit_longitude').value = longitude;
    document.getElementById('edit_gerant').value = gerant;
    document.getElementById('edit_cooperatif').value = cooperatif;
    document.getElementById('edit_statut').value = statut;
    
    // Mettre à jour l'aperçu GPS
    updateGPSPreview();
    
    // Ouvrir la modale
    $('#edit-pont').modal('show');
}

// Fonction alternative plus directe pour l'édition
function editPontDirect(id, code_pont, nom_pont, latitude, longitude, gerant, cooperatif, statut) {
    // Nettoyer les données
    if (!nom_pont || nom_pont === 'Non défini') nom_pont = '';
    if (!cooperatif || cooperatif === 'Non spécifiée') cooperatif = '';
    
    // Remplir le formulaire de modification
    document.getElementById('edit_id_pont').value = id;
    document.getElementById('edit_code_pont').value = code_pont;
    document.getElementById('edit_nom_pont').value = nom_pont;
    document.getElementById('edit_latitude').value = latitude;
    document.getElementById('edit_longitude').value = longitude;
    document.getElementById('edit_gerant').value = gerant;
    document.getElementById('edit_cooperatif').value = cooperatif;
    document.getElementById('edit_statut').value = statut;
    
    // Mettre à jour l'aperçu GPS
    updateGPSPreview();
    
    // Ouvrir la modale
    $('#edit-pont').modal('show');
}

// Fonction pour mettre à jour l'aperçu GPS
function updateGPSPreview() {
    var lat = document.getElementById('edit_latitude').value;
    var lng = document.getElementById('edit_longitude').value;
    var preview = document.getElementById('gps_preview');
    
    if (lat && lng) {
        preview.innerHTML = '<i class="fas fa-map-marker-alt mr-1"></i>Lat: ' + lat + ', Lng: ' + lng + 
                           '<br><a href="https://maps.google.com/?q=' + lat + ',' + lng + '" target="_blank" class="text-info">Voir sur Google Maps</a>';
    } else {
        preview.textContent = 'Coordonnées GPS s\'afficheront ici';
    }
}

function deletePont(id, code) {
    // Créer une modale de confirmation professionnelle
    var confirmModal = document.createElement('div');
    confirmModal.className = 'modal fade';
    confirmModal.innerHTML = 
        '<div class="modal-dialog modal-dialog-centered">' +
            '<div class="modal-content">' +
                '<div class="modal-header bg-danger text-white">' +
                    '<h5 class="modal-title">' +
                        '<i class="fas fa-exclamation-triangle mr-2"></i>' +
                        'Confirmation de suppression' +
                    '</h5>' +
                '</div>' +
                '<div class="modal-body text-center">' +
                    '<div class="mb-3">' +
                        '<i class="fas fa-trash-alt text-danger" style="font-size: 3rem;"></i>' +
                    '</div>' +
                    '<h6>Êtes-vous sûr de vouloir supprimer ce pont-bascule ?</h6>' +
                    '<p class="text-muted mb-0">Code: <strong>' + code + '</strong></p>' +
                    '<p class="text-danger small mt-2">' +
                        '<i class="fas fa-exclamation-triangle mr-1"></i>' +
                        'Cette action est irréversible !' +
                    '</p>' +
                '</div>' +
                '<div class="modal-footer">' +
                    '<button type="button" class="btn btn-secondary" data-dismiss="modal">' +
                        '<i class="fas fa-times mr-1"></i>Annuler' +
                    '</button>' +
                    '<button type="button" class="btn btn-danger" onclick="confirmDelete(' + id + ')">' +
                        '<i class="fas fa-trash-alt mr-1"></i>Supprimer définitivement' +
                    '</button>' +
                '</div>' +
            '</div>' +
        '</div>';
    
    document.body.appendChild(confirmModal);
    $(confirmModal).modal('show');
    
    // Supprimer la modale après fermeture
    $(confirmModal).on('hidden.bs.modal', function() {
        document.body.removeChild(confirmModal);
    });
}

function confirmDelete(id) {
    // Fermer la modale
    $('.modal').modal('hide');
    
    // Créer et soumettre le formulaire
    var form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';
    
    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'id_pont';
    input.value = id;
    form.appendChild(input);
    
    var submit = document.createElement('input');
    submit.type = 'hidden';
    submit.name = 'delete_pont';
    submit.value = '1';
    form.appendChild(submit);
    
    document.body.appendChild(form);
    form.submit();
}

function generateQRCode(id, code, gerant, latitude, longitude, cooperative) {
    $('#qrCodeModal').modal('show');
    
    document.getElementById('qrCodeContainer').innerHTML = '<div class="spinner-border text-success mb-3" role="status"><span class="sr-only">Génération en cours...</span></div><p>Génération du QR Code en cours...</p>';
    document.getElementById('qrCodeInfo').style.display = 'none';
    document.getElementById('downloadQRCode').style.display = 'none';
    document.getElementById('printQRCode').style.display = 'none';
    
    // Créer l'URL de vérification avec le code du pont
    var verificationUrl = 'https://unipalm.ci/verification_pont.php?code=' + encodeURIComponent(code);
    
    var qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' + encodeURIComponent(verificationUrl) + '&format=png&margin=10';
    
    setTimeout(function() {
        var qrImage = document.createElement('img');
        qrImage.src = qrUrl;
        qrImage.alt = 'QR Code du pont-bascule';
        qrImage.className = 'img-fluid border rounded shadow';
        qrImage.style.maxWidth = '300px';
        
        document.getElementById('qrCodeContainer').innerHTML = '';
        document.getElementById('qrCodeContainer').appendChild(qrImage);
        
        document.getElementById('qr-code').textContent = code;
        document.getElementById('qr-gerant').textContent = gerant;
        document.getElementById('qr-latitude').textContent = latitude;
        document.getElementById('qr-longitude').textContent = longitude;
        document.getElementById('qr-cooperative').textContent = cooperative || 'Non spécifiée';
        document.getElementById('verification-url').textContent = verificationUrl;
        
        document.getElementById('qrCodeInfo').style.display = 'block';
        document.getElementById('downloadQRCode').style.display = 'inline-block';
        document.getElementById('printQRCode').style.display = 'inline-block';
        document.getElementById('testVerification').style.display = 'inline-block';
        
        document.getElementById('downloadQRCode').onclick = function() {
            var link = document.createElement('a');
            link.href = qrUrl;
            link.download = 'QRCode_' + code + '.png';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        };
        
        document.getElementById('testVerification').onclick = function() {
            window.open(verificationUrl, '_blank');
        };
        
    }, 1500);
}

function exportData() {
    var csvContent = "Code Pont,Latitude,Longitude,Gérant,Coopérative\n";
    var tableRows = document.querySelectorAll('#pontsTable tbody tr');
    
    if (tableRows.length === 0) {
        alert('Aucune donnée à exporter');
        return;
    }
    
    for (var i = 0; i < tableRows.length; i++) {
        var cells = tableRows[i].querySelectorAll('td');
        if (cells.length >= 4) {
            var code = cells[0].textContent.trim();
            var coords = cells[1].textContent.trim();
            var gerant = cells[2].textContent.trim();
            var cooperative = cells[3].textContent.trim();
            
            var latMatch = coords.match(/Lat:\s*([-\d.]+)/);
            var lngMatch = coords.match(/Lng:\s*([-\d.]+)/);
            var lat = latMatch ? latMatch[1] : '';
            var lng = lngMatch ? lngMatch[1] : '';
            
            csvContent += '"' + code + '","' + lat + '","' + lng + '","' + gerant + '","' + cooperative + '"\n';
        }
    }
    
    var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    var link = document.createElement('a');
    var url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', 'ponts_bascules_' + new Date().toISOString().split('T')[0] + '.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function showMap() {
    var tableRows = document.querySelectorAll('#pontsTable tbody tr');
    
    if (tableRows.length === 0) {
        alert('Aucun pont-bascule à afficher sur la carte');
        return;
    }
    
    var mapUrl = 'https://www.google.com/maps/dir/';
    var hasCoords = false;
    
    for (var i = 0; i < tableRows.length; i++) {
        var coordsCell = tableRows[i].querySelectorAll('td')[1];
        if (coordsCell) {
            var coords = coordsCell.textContent.trim();
            var latMatch = coords.match(/Lat:\s*([-\d.]+)/);
            var lngMatch = coords.match(/Lng:\s*([-\d.]+)/);
            
            if (latMatch && lngMatch) {
                if (hasCoords) mapUrl += '/';
                mapUrl += latMatch[1] + ',' + lngMatch[1];
                hasCoords = true;
            }
        }
    }
    
    if (!hasCoords) {
        alert('Aucune coordonnée valide trouvée');
        return;
    }
    
    window.open(mapUrl, '_blank');
}

// Fonctions pour les filtres
function toggleFilters() {
    var filterBody = document.getElementById('filterBody');
    var toggleBtn = document.getElementById('filterToggle');
    var icon = toggleBtn.querySelector('i');
    
    if (filterBody.style.display === 'none') {
        filterBody.style.display = 'block';
        icon.className = 'fas fa-chevron-up';
    } else {
        filterBody.style.display = 'none';
        icon.className = 'fas fa-chevron-down';
    }
}

function filterPonts() {
    var searchCode = document.getElementById('searchCode').value.toLowerCase();
    var searchNom = document.getElementById('searchNom').value.toLowerCase();
    var searchGerant = document.getElementById('searchGerant').value.toLowerCase();
    var searchCooperative = document.getElementById('searchCooperative').value.toLowerCase();
    var searchStatut = document.getElementById('searchStatut').value.toLowerCase();
    
    var table = document.getElementById('pontsTable');
    var rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    var visibleCount = 0;
    var totalCount = rows.length;
    
    for (var i = 0; i < rows.length; i++) {
        var row = rows[i];
        var cells = row.getElementsByTagName('td');
        
        if (cells.length < 7) continue; // Skip empty rows
        
        var code = cells[0].textContent.toLowerCase();
        var nom = cells[1].textContent.toLowerCase();
        var gerant = cells[3].textContent.toLowerCase();
        var cooperativeElement = cells[4].querySelector('.badge-professional');
        var cooperative = cooperativeElement ? cooperativeElement.textContent.toLowerCase() : '';
        var statutElement = cells[5].querySelector('.badge');
        var statut = statutElement ? statutElement.textContent.toLowerCase() : '';
        
        var showRow = true;
        
        // Filtrer par code
        if (searchCode && !code.includes(searchCode)) {
            showRow = false;
        }
        
        // Filtrer par nom
        if (searchNom && !nom.includes(searchNom)) {
            showRow = false;
        }
        
        // Filtrer par gérant
        if (searchGerant && !gerant.includes(searchGerant)) {
            showRow = false;
        }
        
        // Filtrer par coopérative
        if (searchCooperative && !cooperative.includes(searchCooperative)) {
            showRow = false;
        }
        
        // Filtrer par statut
        if (searchStatut && !statut.includes(searchStatut)) {
            showRow = false;
        }
        
        if (showRow) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    }
    
    // Mettre à jour le compteur
    document.getElementById('visibleCount').textContent = visibleCount;
    
    // Afficher un message si aucun résultat
    var noResultsRow = document.getElementById('noResultsRow');
    if (visibleCount === 0 && totalCount > 0) {
        if (!noResultsRow) {
            var tbody = table.getElementsByTagName('tbody')[0];
            noResultsRow = document.createElement('tr');
            noResultsRow.id = 'noResultsRow';
            noResultsRow.innerHTML = '<td colspan="7" class="text-center py-4"><i class="fas fa-search fa-2x text-muted mb-2"></i><br><strong>Aucun résultat trouvé</strong><br><small class="text-muted">Essayez de modifier vos critères de recherche</small></td>';
            tbody.appendChild(noResultsRow);
        }
        noResultsRow.style.display = '';
    } else if (noResultsRow) {
        noResultsRow.style.display = 'none';
    }
}

function clearFilters() {
    document.getElementById('searchCode').value = '';
    document.getElementById('searchNom').value = '';
    document.getElementById('searchGerant').value = '';
    document.getElementById('searchCooperative').value = '';
    document.getElementById('searchStatut').value = '';
    
    filterPonts();
}

// Fonction de recherche rapide
function quickSearchPonts() {
    var searchTerm = document.getElementById('quickSearch').value.toLowerCase();
    var table = document.getElementById('pontsTable');
    var rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    var visibleCount = 0;
    
    for (var i = 0; i < rows.length; i++) {
        var row = rows[i];
        var cells = row.getElementsByTagName('td');
        
        if (cells.length < 7) continue; // Skip empty rows
        
        var rowText = '';
        // Concatener tout le texte de la ligne pour la recherche
        for (var j = 0; j < 6; j++) { // Exclure la colonne actions
            rowText += cells[j].textContent.toLowerCase() + ' ';
        }
        
        if (!searchTerm || rowText.includes(searchTerm)) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    }
    
    // Mettre à jour le compteur
    document.getElementById('quickVisibleCount').textContent = visibleCount;
    
    // Gérer le message "aucun résultat"
    handleNoResults(visibleCount, rows.length);
}

function clearQuickSearch() {
    document.getElementById('quickSearch').value = '';
    quickSearchPonts();
}

function handleNoResults(visibleCount, totalCount) {
    var table = document.getElementById('pontsTable');
    var noResultsRow = document.getElementById('noResultsRow');
    
    if (visibleCount === 0 && totalCount > 0) {
        if (!noResultsRow) {
            var tbody = table.getElementsByTagName('tbody')[0];
            noResultsRow = document.createElement('tr');
            noResultsRow.id = 'noResultsRow';
            noResultsRow.innerHTML = '<td colspan="7" class="text-center py-4">' +
                '<i class="fas fa-search fa-2x text-muted mb-2"></i><br>' +
                '<strong>Aucun résultat trouvé</strong><br>' +
                '<small class="text-muted">Essayez de modifier vos critères de recherche</small>' +
                '</td>';
            tbody.appendChild(noResultsRow);
        }
        noResultsRow.style.display = '';
    } else if (noResultsRow) {
        noResultsRow.style.display = 'none';
    }
}
</script>

<?php include('footer.php'); ?>
