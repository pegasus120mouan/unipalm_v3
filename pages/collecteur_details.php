<?php
require_once '../inc/functions/connexion.php';

$collecteurId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($collecteurId <= 0) {
    header('Location: collecteurs.php');
    exit;
}

// Récupérer les informations du collecteur via l'API distante
$apiUrl = 'https://api.objetombrepegasus.online/api/planteur/actions/utilisateurs.php?id=' . $collecteurId;
$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
$collecteur = null;

if ($data && $data['success'] && !empty($data['data'])) {
    $collecteur = is_array($data['data']) && isset($data['data'][0]) ? $data['data'][0] : $data['data'];
}

if (!$collecteur) {
    header('Location: collecteurs.php');
    exit;
}

// Filtres de période - Par défaut : pas de filtre (tout afficher)
$filtreActif = isset($_GET['date_debut']) || isset($_GET['date_fin']);
$dateDebut = isset($_GET['date_debut']) ? $_GET['date_debut'] : '';
$dateFin = isset($_GET['date_fin']) ? $_GET['date_fin'] : '';

// Initialiser les variables par défaut
$stats = ['nombre_exploitants' => 0, 'superficie_totale' => 0, 'nombre_parcelles' => 0];
$statsParCulture = [];
$statsMensuel = [];
$derniersExploitants = [];
$statsError = false;

// Construire l'URL de l'API stats_collecteur
$apiStatsUrl = 'https://api.objetombrepegasus.online/api/planteur/actions/api_stats_collecteur.php?collecteur_id=' . $collecteurId;
if ($filtreActif) {
    if ($dateDebut) $apiStatsUrl .= '&date_debut=' . urlencode($dateDebut);
    if ($dateFin) $apiStatsUrl .= '&date_fin=' . urlencode($dateFin);
}

// Appel API unique pour récupérer toutes les statistiques
$ch = curl_init($apiStatsUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Debug si erreur
if (!$response || $httpCode !== 200) {
    error_log("API Stats Collecteur Error: HTTP $httpCode - $curlError - URL: $apiStatsUrl");
}

$apiData = json_decode($response, true);

if ($apiData && isset($apiData['success']) && $apiData['success'] && !empty($apiData['data'])) {
    $data = $apiData['data'];
    
    // Statistiques globales
    $stats = [
        'nombre_exploitants' => $data['stats']['nombre_exploitants'] ?? 0,
        'superficie_totale' => $data['stats']['superficie_totale'] ?? 0,
        'nombre_parcelles' => $data['stats']['nombre_parcelles'] ?? 0
    ];
    
    // Répartition par culture
    $statsParCulture = $data['repartition_cultures'] ?? [];
    
    // Évolution mensuelle
    $statsMensuel = $data['evolution_mensuelle'] ?? [];
    
    // Derniers planteurs
    $derniersExploitants = $data['derniers_planteurs'] ?? [];
}


include('header.php');
?>

<style>
:root {
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --success-gradient: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
    --info-gradient: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
    --warning-gradient: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
}

.page-header {
    background: var(--primary-gradient);
    color: white;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.collecteur-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    border: 4px solid white;
    object-fit: cover;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.stat-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    transition: transform 0.3s;
    height: 100%;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
}

.stat-icon.tickets { background: var(--info-gradient); }
.stat-icon.poids { background: var(--success-gradient); }
.stat-icon.montant { background: var(--warning-gradient); }

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: #2c3e50;
}

.stat-label {
    color: #7f8c8d;
    font-size: 0.9rem;
}

.filter-card {
    background: white;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 25px;
    box-shadow: 0 3px 15px rgba(0,0,0,0.05);
}

.table-container {
    background: white;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 3px 15px rgba(0,0,0,0.05);
}

.table-professional {
    width: 100%;
    border-collapse: collapse;
}

.table-professional thead th {
    background: var(--primary-gradient);
    color: white;
    padding: 15px;
    text-align: left;
    font-weight: 600;
}

.table-professional tbody td {
    padding: 12px 15px;
    border-bottom: 1px solid #eee;
}

.table-professional tbody tr:hover {
    background: #f8f9fa;
}

.badge-usine {
    background: #e8f5e9;
    color: #27ae60;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
}

.back-btn {
    background: rgba(255,255,255,0.2);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: background 0.3s;
}

.back-btn:hover {
    background: rgba(255,255,255,0.3);
    color: white;
    text-decoration: none;
}

.chart-container {
    background: white;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 3px 15px rgba(0,0,0,0.05);
    margin-bottom: 25px;
}
</style>

<div class="right_col" role="main">

    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-auto">
                <a href="collecteurs.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>
            <div class="col-auto">
                <?php 
                $avatarUrl = !empty($collecteur['avatar']) 
                    ? 'http://51.178.49.141:9000/planteurs/' . $collecteur['avatar']
                    : '../img/default-avatar.png';
                ?>
                <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar" class="collecteur-avatar" onerror="this.src='../img/default-avatar.png'">
            </div>
            <div class="col">
                <h2 class="mb-1"><?= htmlspecialchars($collecteur['nom'] . ' ' . $collecteur['prenoms']) ?></h2>
                <p class="mb-0 opacity-75">
                    <i class="fas fa-phone mr-2"></i><?= htmlspecialchars($collecteur['contact'] ?? 'N/A') ?>
                    <span class="mx-3">|</span>
                    <i class="fas fa-map-marker-alt mr-2"></i><?= htmlspecialchars($collecteur['nom_zone'] ?? 'Non assigné') ?>
                    <span class="mx-3">|</span>
                    <span class="badge badge-light"><?= htmlspecialchars($collecteur['role'] ?? 'Collecteur') ?></span>
                </p>
            </div>
        </div>
    </div>

    <!-- Filtres de période -->
    <div class="filter-card">
        <form method="GET" class="row align-items-end">
            <input type="hidden" name="id" value="<?= $collecteurId ?>">
            <div class="col-md-3">
                <label class="font-weight-bold"><i class="fas fa-calendar mr-2"></i>Date début</label>
                <input type="date" name="date_debut" class="form-control" value="<?= htmlspecialchars($dateDebut) ?>">
            </div>
            <div class="col-md-3">
                <label class="font-weight-bold"><i class="fas fa-calendar mr-2"></i>Date fin</label>
                <input type="date" name="date_fin" class="form-control" value="<?= htmlspecialchars($dateFin) ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-filter mr-2"></i>Filtrer
                </button>
            </div>
            <div class="col-md-2">
                <a href="?id=<?= $collecteurId ?>&date_debut=<?= date('Y-01-01') ?>&date_fin=<?= date('Y-12-31') ?>" class="btn btn-outline-secondary btn-block">
                    <i class="fas fa-calendar-alt mr-2"></i>Année en cours
                </a>
            </div>
            <div class="col-md-1">
                <a href="?id=<?= $collecteurId ?>" class="btn btn-outline-danger btn-block" title="Voir tout">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
        <?php if ($filtreActif): ?>
        <div class="mt-2">
            <span class="badge badge-info">
                <i class="fas fa-filter mr-1"></i>
                Filtre actif : <?= $dateDebut ? $dateDebut : 'Début' ?> → <?= $dateFin ? $dateFin : 'Fin' ?>
            </span>
        </div>
        <?php endif; ?>
    </div>

    <!-- Statistiques globales -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon tickets mr-3">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?= number_format($stats['nombre_exploitants'] ?? 0, 0, ',', ' ') ?></div>
                        <div class="stat-label">Planteurs recensés</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon poids mr-3">
                        <i class="fas fa-map"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?= number_format($stats['superficie_totale'] ?? 0, 2, ',', ' ') ?> ha</div>
                        <div class="stat-label">Superficie totale</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon montant mr-3">
                        <i class="fas fa-draw-polygon"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?= number_format($stats['nombre_parcelles'] ?? 0, 0, ',', ' ') ?></div>
                        <div class="stat-label">Parcelles enregistrées</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Répartition par type de culture -->
        <div class="col-md-6 mb-4">
            <div class="table-container">
                <h5 class="mb-3"><i class="fas fa-seedling mr-2 text-primary"></i>Répartition par Culture</h5>
                <?php if (empty($statsParCulture)): ?>
                    <p class="text-muted text-center py-4">Aucune donnée pour cette période</p>
                <?php else: ?>
                    <table class="table-professional">
                        <thead>
                            <tr>
                                <th>Type de culture</th>
                                <th>Planteurs</th>
                                <th>Superficie (ha)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($statsParCulture as $culture): ?>
                            <tr>
                                <td><span class="badge-usine"><?= htmlspecialchars($culture['type_culture'] ?? 'N/A') ?></span></td>
                                <td><?= number_format($culture['nombre_exploitants'], 0, ',', ' ') ?></td>
                                <td><?= number_format($culture['superficie_totale'], 2, ',', ' ') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Évolution mensuelle -->
        <div class="col-md-6 mb-4">
            <div class="table-container">
                <h5 class="mb-3"><i class="fas fa-chart-line mr-2 text-success"></i>Évolution Mensuelle</h5>
                <?php if (empty($statsMensuel)): ?>
                    <p class="text-muted text-center py-4">Aucune donnée pour cette période</p>
                <?php else: ?>
                    <table class="table-professional">
                        <thead>
                            <tr>
                                <th>Mois</th>
                                <th>Planteurs</th>
                                <th>Superficie (ha)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($statsMensuel as $mois): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($mois['mois']) ?></strong></td>
                                <td><?= number_format($mois['nombre_exploitants'], 0, ',', ' ') ?></td>
                                <td><?= number_format($mois['superficie_totale'], 2, ',', ' ') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Derniers planteurs recensés -->
    <div class="table-container">
        <h5 class="mb-3"><i class="fas fa-history mr-2 text-info"></i>Derniers Planteurs Recensés (20 derniers)</h5>
        <?php if (empty($derniersExploitants)): ?>
            <p class="text-muted text-center py-4">Aucun planteur pour cette période</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table-professional">
                    <thead>
                        <tr>
                            <th>Nom & Prénoms</th>
                            <th>Téléphone</th>
                            <th>Région</th>
                            <th>Village</th>
                            <th>Superficie (ha)</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($derniersExploitants as $exploitant): ?>
                        <tr>
                            <td><strong class="text-primary"><?= htmlspecialchars($exploitant['nom_prenoms']) ?></strong></td>
                            <td><?= htmlspecialchars($exploitant['telephone'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($exploitant['region'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($exploitant['village'] ?? 'N/A') ?></td>
                            <td><?= number_format($exploitant['superficie_totale'] ?? 0, 2, ',', ' ') ?></td>
                            <td><?= date('d/m/Y', strtotime($exploitant['date_enregistrement'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include('footer.php'); ?>
