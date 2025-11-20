<?php
require_once '../inc/functions/connexion.php';
require_once '../inc/functions/requete/requete_tickets.php';
require_once '../inc/functions/requete/requete_usines.php';
require_once '../inc/functions/requete/requete_chef_equipes.php';
require_once '../inc/functions/requete/requete_vehicules.php';
require_once '../inc/functions/requete/requete_agents.php';
include('header.php');

// Paramètres de pagination
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 15;
if ($limit <= 0) {
    $limit = 15;
}
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page <= 0) {
    $page = 1;
}

// Récupérer les paramètres de filtrage
$agent_id = $_GET['agent_id'] ?? null;
$usine_id = $_GET['usine_id'] ?? null;
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';
$search_agent = $_GET['search_agent'] ?? '';
$search_usine = $_GET['search_usine'] ?? '';
$numero_ticket = $_GET['numero_ticket'] ?? '';

// Calculer le nombre total de tickets (pour la pagination) directement en SQL
$total_tickets = countTicketsJour($conn, $agent_id, $usine_id, $date_debut, $date_fin, $numero_ticket);
$total_pages = $total_tickets > 0 ? ceil($total_tickets / $limit) : 1;
$page = max(1, min($page, $total_pages));
$offset = ($page - 1) * $limit;

// Récupérer uniquement les tickets de la page courante avec LIMIT/OFFSET
$tickets_list = getTicketsJour($conn, $agent_id, $usine_id, $date_debut, $date_fin, $numero_ticket, null, $limit, $offset);

// Filtrer les tickets si un terme de recherche texte (agent/usine) est présent
if (!empty($search_agent) || !empty($search_usine)) {
    $tickets_list = array_filter($tickets_list, function($ticket) use ($search_agent, $search_usine) {
        $match = true;
        if (!empty($search_agent)) {
            $match = $match && stripos($ticket['agent_nom_complet'], $search_agent) !== false;
        }
        if (!empty($search_usine)) {
            $match = $match && stripos($ticket['nom_usine'], $search_usine) !== false;
        }
        return $match;
    });
}

// Pour compatibilité avec le reste du fichier qui utilise encore $tickets (par ex. modals),
// on ne conserve en mémoire que les tickets de la page courante
$tickets = $tickets_list;

// Récupérer les listes pour l'autocomplétion (seulement si nécessaire)
$agents = getAgents($conn);
$usines = getUsines($conn);
?>

<!-- Section de filtres ultra-professionnelle -->
<div class="filters-section mb-4">
    <div class="container-fluid">
        <div class="filters-container">
            <div class="filters-header" onclick="toggleFilters()">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="filter-icon-container">
                            <i class="fas fa-filter"></i>
                        </div>
                        <div class="filter-title-container">
                            <h4 class="filter-title mb-0">Filtres Avancés</h4>
                            <p class="filter-subtitle mb-0">Affiner votre recherche de tickets</p>
                        </div>
                    </div>
                    <div class="toggle-icon">
                        <i class="fas fa-chevron-down" id="toggleIcon"></i>
                    </div>
                </div>
            </div>
            
            <div class="filters-content" id="filtersContent">
                <form id="filterForm" method="GET" class="advanced-filter-form">
                    <div class="row g-4">
                        <!-- Numéro de ticket -->
                        <div class="col-lg-3 col-md-6">
                            <div class="filter-group">
                                <label for="numero_ticket" class="filter-label">
                                    <i class="fas fa-ticket-alt me-2"></i>Numéro de ticket
                                </label>
                                <div class="filter-input-container">
                                    <input type="text" 
                                           class="filter-input" 
                                           name="numero_ticket" 
                                           id="numero_ticket"
                                           placeholder="Ex: TK-2024-001" 
                                           value="<?= htmlspecialchars($numero_ticket) ?>">
                                    <div class="input-icon">
                                        <i class="fas fa-search"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recherche par agent -->
                        <div class="col-lg-3 col-md-6">
                            <div class="filter-group">
                                <label for="agent_select" class="filter-label">
                                    <i class="fas fa-user-tie me-2"></i>Agent
                                </label>
                                <div class="filter-select-container">
                                    <select class="filter-select" name="agent_id" id="agent_select">
                                        <option value="">Tous les agents</option>
                                        <?php foreach($agents as $agent): ?>
                                            <option value="<?= $agent['id_agent'] ?>" <?= ($agent_id == $agent['id_agent']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($agent['nom_complet_agent']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="select-icon">
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recherche par usine -->
                        <div class="col-lg-3 col-md-6">
                            <div class="filter-group">
                                <label for="usine_select" class="filter-label">
                                    <i class="fas fa-industry me-2"></i>Usine
                                </label>
                                <div class="filter-select-container">
                                    <select class="filter-select" name="usine_id" id="usine_select">
                                        <option value="">Toutes les usines</option>
                                        <?php foreach($usines as $usine): ?>
                                            <option value="<?= $usine['id_usine'] ?>" <?= ($usine_id == $usine['id_usine']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($usine['nom_usine']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="select-icon">
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Date de début -->
                        <div class="col-lg-3 col-md-6">
                            <div class="filter-group">
                                <label for="date_debut" class="filter-label">
                                    <i class="fas fa-calendar-alt me-2"></i>Date de début
                                </label>
                                <div class="filter-input-container">
                                    <input type="date" 
                                           class="filter-input date-input" 
                                           name="date_debut" 
                                           id="date_debut"
                                           value="<?= htmlspecialchars($date_debut) ?>">
                                    <div class="input-icon">
                                        <i class="fas fa-calendar"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Date de fin -->
                        <div class="col-lg-3 col-md-6">
                            <div class="filter-group">
                                <label for="date_fin" class="filter-label">
                                    <i class="fas fa-calendar-check me-2"></i>Date de fin
                                </label>
                                <div class="filter-input-container">
                                    <input type="date" 
                                           class="filter-input date-input" 
                                           name="date_fin" 
                                           id="date_fin"
                                           value="<?= htmlspecialchars($date_fin) ?>">
                                    <div class="input-icon">
                                        <i class="fas fa-calendar"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    
                    <!-- Boutons d'action -->
                    <div class="filter-actions mt-4">
                        <div class="d-flex flex-wrap gap-3 justify-content-center">
                            <button type="submit" class="btn-filter btn-filter-primary" id="applyFilters">
                                <i class="fas fa-search me-2"></i>
                                <span>Appliquer les filtres</span>
                                <div class="btn-ripple"></div>
                            </button>
                            <button type="button" class="btn-filter btn-filter-secondary" id="saveFilters">
                                <i class="fas fa-save me-2"></i>
                                <span>Sauvegarder</span>
                            </button>
                            <a href="tickets_jour.php" class="btn-filter btn-filter-danger">
                                <i class="fas fa-times me-2"></i>
                                <span>Réinitialiser</span>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
            
            
            <!-- Filtres actifs -->
            <?php if($agent_id || $usine_id || $date_debut || $date_fin || $numero_ticket): ?>
            <div class="active-filters-section mt-4">
                <div class="active-filters-header">
                    <h5 class="active-filters-title">
                        <i class="fas fa-filter me-2"></i>
                        Filtres Actifs
                    </h5>
                    <button type="button" class="clear-all-filters" onclick="clearAllFilters()">
                        <i class="fas fa-times me-1"></i>
                        Tout effacer
                    </button>
                </div>
                <div class="active-filters-list">
                    <?php if($numero_ticket): ?>
                        <div class="filter-tag filter-tag-ticket">
                            <div class="filter-tag-icon">
                                <i class="fas fa-ticket-alt"></i>
                            </div>
                            <div class="filter-tag-content">
                                <span class="filter-tag-label">Ticket N°</span>
                                <span class="filter-tag-value"><?= htmlspecialchars($numero_ticket) ?></span>
                            </div>
                            <a href="?<?= http_build_query(array_merge($_GET, ['numero_ticket' => null])) ?>" class="filter-tag-remove">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                    <?php if($agent_id): ?>
                        <?php 
                        $agent_name = '';
                        foreach($agents as $agent) {
                            if($agent['id_agent'] == $agent_id) {
                                $agent_name = $agent['nom_complet_agent'];
                                break;
                            }
                        }
                        ?>
                        <div class="filter-tag filter-tag-agent">
                            <div class="filter-tag-icon">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div class="filter-tag-content">
                                <span class="filter-tag-label">Agent</span>
                                <span class="filter-tag-value"><?= htmlspecialchars($agent_name) ?></span>
                            </div>
                            <a href="?<?= http_build_query(array_merge($_GET, ['agent_id' => null])) ?>" class="filter-tag-remove">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                    <?php if($usine_id): ?>
                        <?php 
                        $usine_name = '';
                        foreach($usines as $usine) {
                            if($usine['id_usine'] == $usine_id) {
                                $usine_name = $usine['nom_usine'];
                                break;
                            }
                        }
                        ?>
                        <div class="filter-tag filter-tag-usine">
                            <div class="filter-tag-icon">
                                <i class="fas fa-industry"></i>
                            </div>
                            <div class="filter-tag-content">
                                <span class="filter-tag-label">Usine</span>
                                <span class="filter-tag-value"><?= htmlspecialchars($usine_name) ?></span>
                            </div>
                            <a href="?<?= http_build_query(array_merge($_GET, ['usine_id' => null])) ?>" class="filter-tag-remove">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                    <?php if($date_debut): ?>
                        <div class="filter-tag filter-tag-date">
                            <div class="filter-tag-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="filter-tag-content">
                                <span class="filter-tag-label">Depuis</span>
                                <span class="filter-tag-value"><?= date('d/m/Y', strtotime($date_debut)) ?></span>
                            </div>
                            <a href="?<?= http_build_query(array_merge($_GET, ['date_debut' => null])) ?>" class="filter-tag-remove">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                    <?php if($date_fin): ?>
                        <div class="filter-tag filter-tag-date">
                            <div class="filter-tag-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="filter-tag-content">
                                <span class="filter-tag-label">Jusqu'au</span>
                                <span class="filter-tag-value"><?= date('d/m/Y', strtotime($date_fin)) ?></span>
                            </div>
                            <a href="?<?= http_build_query(array_merge($_GET, ['date_fin' => null])) ?>" class="filter-tag-remove">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Styles ultra-professionnels pour tickets_jour.php -->
<style>
:root {
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --warning-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    --danger-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    --dark-gradient: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
    --glass-bg: rgba(255, 255, 255, 0.1);
    --glass-border: rgba(255, 255, 255, 0.2);
    --shadow-light: 0 8px 32px rgba(31, 38, 135, 0.37);
    --shadow-medium: 0 15px 35px rgba(31, 38, 135, 0.2);
    --border-radius: 16px;
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Section des filtres */
.filters-section {
    margin-bottom: 2rem;
}

.filters-container {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    border: 1px solid var(--glass-border);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-light);
    overflow: hidden;
}

.filters-header {
    background: var(--primary-gradient);
    padding: 1.5rem 2rem;
    cursor: pointer;
    transition: var(--transition);
}

.filters-header:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-medium);
}

.filter-icon-container {
    width: 50px;
    height: 50px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
}

.filter-icon-container i {
    font-size: 1.5rem;
    color: white;
}

.filter-title {
    color: white;
    font-size: 1.5rem;
    font-weight: 700;
}

.filter-subtitle {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
}

.toggle-icon i {
    color: white;
    font-size: 1.2rem;
    transition: var(--transition);
}

.filters-content {
    padding: 2rem;
    background: rgba(255, 255, 255, 0.05);
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease-out;
}

.filters-content.show {
    max-height: 1000px;
}

.filter-group {
    margin-bottom: 1.5rem;
}

.filter-label {
    display: flex;
    align-items: center;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.filter-input-container,
.filter-select-container {
    position: relative;
}

.filter-input,
.filter-select {
    width: 100%;
    padding: 0.75rem 1rem;
    padding-right: 3rem;
    border: 2px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.9);
    font-size: 0.9rem;
    transition: var(--transition);
    backdrop-filter: blur(10px);
}

.filter-input:focus,
.filter-select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    transform: translateY(-2px);
}

.input-icon,
.select-icon {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #667eea;
    pointer-events: none;
}

/* Boutons de filtres */
.filter-actions {
    padding-top: 2rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.btn-filter {
    position: relative;
    padding: 0.75rem 2rem;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.9rem;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition);
    overflow: hidden;
    cursor: pointer;
}

.btn-filter-primary {
    background: var(--primary-gradient);
    color: white;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.btn-filter-secondary {
    background: var(--success-gradient);
    color: white;
    box-shadow: 0 4px 15px rgba(79, 172, 254, 0.4);
}

.btn-filter-danger {
    background: var(--danger-gradient);
    color: white;
    box-shadow: 0 4px 15px rgba(250, 112, 154, 0.4);
}

.btn-filter:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
}

/* Filtres actifs */
.active-filters-section {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    padding: 1.5rem;
    margin-top: 1rem;
}

.active-filters-header {
    display: flex;
    justify-content: between;
    align-items: center;
    margin-bottom: 1rem;
}

.active-filters-title {
    color: #2c3e50;
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0;
}

.clear-all-filters {
    background: var(--danger-gradient);
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-size: 0.8rem;
    cursor: pointer;
    transition: var(--transition);
}

.active-filters-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.filter-tag {
    display: flex;
    align-items: center;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 25px;
    padding: 0.5rem 1rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    transition: var(--transition);
}

.filter-tag:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.filter-tag-icon {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 0.5rem;
    font-size: 0.8rem;
}

.filter-tag-ticket .filter-tag-icon {
    background: var(--primary-gradient);
    color: white;
}

.filter-tag-agent .filter-tag-icon {
    background: var(--success-gradient);
    color: white;
}

.filter-tag-usine .filter-tag-icon {
    background: var(--warning-gradient);
    color: white;
}

.filter-tag-date .filter-tag-icon {
    background: var(--secondary-gradient);
    color: white;
}

.filter-tag-content {
    display: flex;
    flex-direction: column;
    margin-right: 0.5rem;
}

.filter-tag-label {
    font-size: 0.7rem;
    color: #666;
    font-weight: 500;
}

.filter-tag-value {
    font-size: 0.8rem;
    color: #2c3e50;
    font-weight: 600;
}

.filter-tag-remove {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #e74c3c;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    font-size: 0.7rem;
    transition: var(--transition);
}

.filter-tag-remove:hover {
    background: #c0392b;
    transform: scale(1.1);
}

/* En-tête des tickets */
.tickets-header-section {
    margin-bottom: 2rem;
}

.tickets-header-container {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    border: 1px solid var(--glass-border);
    border-radius: var(--border-radius);
    padding: 2rem;
    box-shadow: var(--shadow-light);
}

.tickets-title-group {
    display: flex;
    align-items: center;
}

.tickets-icon-container {
    width: 60px;
    height: 60px;
    background: var(--primary-gradient);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1.5rem;
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.tickets-icon-container i {
    font-size: 1.8rem;
    color: white;
}

.tickets-main-title {
    font-size: 2rem;
    font-weight: 700;
    color: #2c3e50;
    margin: 0;
}

.tickets-subtitle {
    color: #7f8c8d;
    font-size: 1rem;
}

.tickets-stats-group {
    display: flex;
    gap: 1rem;
}

.stat-card {
    display: flex;
    align-items: center;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 12px;
    padding: 1rem 1.5rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    transition: var(--transition);
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    font-size: 1.2rem;
    color: white;
}

.stat-card-primary .stat-icon {
    background: var(--primary-gradient);
}

.stat-card-info .stat-icon {
    background: var(--success-gradient);
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: #2c3e50;
    line-height: 1;
}

.stat-label {
    font-size: 0.8rem;
    color: #7f8c8d;
    margin-top: 0.25rem;
}

/* Loader moderne */
.modern-loader {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 300px;
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border-radius: var(--border-radius);
}

.loader-container {
    text-align: center;
}

.loader-spinner {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 1.5rem;
}

.spinner-ring {
    width: 40px;
    height: 40px;
    border: 3px solid rgba(102, 126, 234, 0.1);
    border-top: 3px solid #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 5px;
}

.spinner-ring:nth-child(2) {
    animation-delay: 0.1s;
    border-top-color: #764ba2;
}

.spinner-ring:nth-child(3) {
    animation-delay: 0.2s;
    border-top-color: #f093fb;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loader-text h4 {
    color: #2c3e50;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.loader-text p {
    color: #7f8c8d;
    font-size: 0.9rem;
}

/* Tableau professionnel */
.tickets-table-section {
    margin-bottom: 2rem;
}

.professional-table-container {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    border: 1px solid var(--glass-border);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-light);
    overflow: hidden;
}

.professional-table {
    width: 100%;
    border-collapse: collapse;
    background: transparent;
}

.table-header {
    background: var(--primary-gradient);
}

.table-header th {
    padding: 1.5rem 1rem;
    border: none;
    text-align: left;
    font-weight: 600;
    font-size: 0.9rem;
}

.th-content {
    display: flex;
    align-items: center;
    color: white;
}

.th-content i {
    opacity: 0.8;
}

.table-body tr {
    background: rgba(255, 255, 255, 0.05);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    transition: var(--transition);
}

.table-body tr:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.table-body td {
    padding: 1rem;
    border: none;
    vertical-align: middle;
}

.cell-content {
    display: flex;
    align-items: center;
}

/* Badges et statuts */
.date-badge {
    display: flex;
    align-items: center;
    background: rgba(255, 255, 255, 0.9);
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 500;
    color: #2c3e50;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.date-badge.validated {
    background: var(--success-gradient);
    color: white;
}

.date-badge.paid {
    background: var(--warning-gradient);
    color: white;
}

.ticket-number {
    display: flex;
    align-items: center;
    background: var(--primary-gradient);
    color: white;
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.85rem;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.usine-name {
    display: flex;
    align-items: center;
    background: rgba(255, 255, 255, 0.9);
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 500;
    color: #2c3e50;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.agent-info {
    display: flex;
    align-items: center;
}

.agent-avatar {
    width: 32px;
    height: 32px;
    background: var(--success-gradient);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 0.75rem;
    color: white;
    font-size: 0.9rem;
}

.agent-name {
    font-weight: 500;
    color: #2c3e50;
    font-size: 0.85rem;
}

.vehicule-badge {
    display: flex;
    align-items: center;
    background: var(--dark-gradient);
    color: white;
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.85rem;
    box-shadow: 0 2px 8px rgba(44, 62, 80, 0.3);
}

.poids-value {
    display: flex;
    align-items: center;
    background: rgba(255, 255, 255, 0.9);
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
    font-size: 0.85rem;
    color: #2c3e50;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.weight-number {
    font-weight: 600;
    margin-right: 0.25rem;
}

.weight-unit {
    font-size: 0.75rem;
    opacity: 0.7;
}

.creator-info {
    display: flex;
    align-items: center;
    background: rgba(255, 255, 255, 0.9);
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
    font-size: 0.85rem;
    color: #2c3e50;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.status-badge {
    display: flex;
    align-items: center;
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 500;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.status-pending {
    background: var(--warning-gradient);
    color: white;
}

.status-progress {
    background: var(--secondary-gradient);
    color: white;
}

.status-waiting {
    background: var(--danger-gradient);
    color: white;
}

.status-unpaid {
    background: #e74c3c;
    color: white;
}

.price-badge, .amount-badge {
    display: flex;
    align-items: center;
    background: var(--success-gradient);
    color: white;
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.85rem;
    box-shadow: 0 2px 8px rgba(79, 172, 254, 0.3);
}

.price-value, .amount-value {
    margin-right: 0.25rem;
}

.currency {
    font-size: 0.75rem;
    opacity: 0.8;
}

/* État vide */
.empty-state {
    text-align: center;
    padding: 3rem 2rem;
}

.empty-icon {
    font-size: 4rem;
    color: #bdc3c7;
    margin-bottom: 1.5rem;
}

.empty-content h4 {
    color: #2c3e50;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.empty-content p {
    color: #7f8c8d;
    font-size: 0.9rem;
}

/* Pagination professionnelle */
.professional-pagination-section {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    border: 1px solid var(--glass-border);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    box-shadow: var(--shadow-light);
}

.pagination-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}

.pagination-info {
    display: flex;
    align-items: center;
}

.results-summary {
    display: flex;
    align-items: center;
    color: #2c3e50;
    font-size: 0.9rem;
    font-weight: 500;
}

.pagination-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.pagination-btn {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--primary-gradient);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    transition: var(--transition);
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.pagination-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    color: white;
    text-decoration: none;
}

.pagination-numbers {
    display: flex;
    gap: 0.25rem;
}

.pagination-number {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.9);
    color: #2c3e50;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 500;
    transition: var(--transition);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.pagination-number:hover {
    background: var(--primary-gradient);
    color: white;
    transform: translateY(-2px);
    text-decoration: none;
}

.pagination-number.active {
    background: var(--primary-gradient);
    color: white;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.items-per-page {
    display: flex;
    align-items: center;
}

.items-control {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.items-label {
    color: #2c3e50;
    font-size: 0.9rem;
    font-weight: 500;
    margin: 0;
}

.items-select {
    padding: 0.5rem 0.75rem;
    border: 2px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.9);
    color: #2c3e50;
    font-size: 0.85rem;
    cursor: pointer;
    transition: var(--transition);
}

.items-select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* Responsive */
@media (max-width: 768px) {
    .tickets-stats-group {
        flex-direction: column;
        width: 100%;
    }
    
    .stat-card {
        width: 100%;
    }
    
    .pagination-container {
        flex-direction: column;
        text-align: center;
    }
    
    .professional-table-container {
        overflow-x: auto;
    }
    
    .filters-content {
        padding: 1rem;
    }
    
    .tickets-header-container {
        padding: 1.5rem;
    }
    
    .tickets-title-group {
        flex-direction: column;
        text-align: center;
        margin-bottom: 1rem;
    }
    
    .tickets-icon-container {
        margin-right: 0;
        margin-bottom: 1rem;
    }
}
</style>

  <style>
        @media only screen and (max-width: 767px) {
            
            th {
                display: none; 
            }
            tbody tr {
                display: block;
                margin-bottom: 20px;
                border: 1px solid #ccc;
                padding: 10px;
            }
            tbody tr td::before {

                font-weight: bold;
                margin-right: 5px;
            }
        }
        .margin-right-15 {
        margin-right: 15px;
       }
        .block-container {
      background-color:  #d7dbdd ;
      padding: 20px;
      border-radius: 5px;
      width: 100%;
      margin-bottom: 20px;
    }
    </style>


<!-- En-tête de la section tickets -->
<div class="tickets-header-section mb-4">
    <div class="tickets-header-container">
        <div class="d-flex align-items-center justify-content-between flex-wrap">
            <div class="tickets-title-group">
                <div class="tickets-icon-container">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="tickets-title-content">
                    <h2 class="tickets-main-title mb-1">Tickets du Jour</h2>
                    <p class="tickets-subtitle mb-0">Gestion et suivi des tickets quotidiens</p>
                </div>
            </div>
            <div class="tickets-stats-group">
                <div class="stat-card stat-card-primary">
                    <div class="stat-icon">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $total_tickets; ?></div>
                        <div class="stat-label">Tickets Total</div>
                    </div>
                </div>
                <div class="stat-card stat-card-info">
                    <div class="stat-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo count($tickets_list); ?></div>
                        <div class="stat-label">Affichés</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Section tableau ultra-professionnelle -->
<div class="tickets-table-section">
    <!-- Loader moderne -->
    <div id="loader" class="modern-loader">
        <div class="loader-container">
            <div class="loader-spinner">
                <div class="spinner-ring"></div>
                <div class="spinner-ring"></div>
                <div class="spinner-ring"></div>
            </div>
            <div class="loader-text">
                <h4>Chargement des tickets...</h4>
                <p>Veuillez patienter</p>
            </div>
        </div>
    </div>
    
    <!-- Table professionnelle -->
    <div class="professional-table-container" id="tableContainer" style="display: none;">
        <table id="example1" class="professional-table">

        <thead class="table-header">
            <tr>
                <th class="th-date">
                    <div class="th-content">
                        <i class="fas fa-calendar-alt me-2"></i>
                        <span>Date Ticket</span>
                    </div>
                </th>
                <th class="th-ticket">
                    <div class="th-content">
                        <i class="fas fa-ticket-alt me-2"></i>
                        <span>N° Ticket</span>
                    </div>
                </th>
                <th class="th-usine">
                    <div class="th-content">
                        <i class="fas fa-industry me-2"></i>
                        <span>Usine</span>
                    </div>
                </th>
                <th class="th-agent">
                    <div class="th-content">
                        <i class="fas fa-user-tie me-2"></i>
                        <span>Chargé de Mission</span>
                    </div>
                </th>
                <th class="th-vehicule">
                    <div class="th-content">
                        <i class="fas fa-truck me-2"></i>
                        <span>Véhicule</span>
                    </div>
                </th>
                <th class="th-poids">
                    <div class="th-content">
                        <i class="fas fa-weight-hanging me-2"></i>
                        <span>Poids</span>
                    </div>
                </th>
                <th class="th-creator">
                    <div class="th-content">
                        <i class="fas fa-user-plus me-2"></i>
                        <span>Créé par</span>
                    </div>
                </th>
                <th class="th-price">
                    <div class="th-content">
                        <span>Prix Unitaire</span>
                    </div>
                </th>
                <th class="th-validation">
                    <div class="th-content">
                        <i class="fas fa-check-circle me-2"></i>
                        <span>Validation</span>
                    </div>
                </th>
                <th class="th-montant">
                    <div class="th-content">
                        <i class="fas fa-coins me-2"></i>
                        <span>Montant</span>
                    </div>
                </th>
                <th class="th-paie">
                    <div class="th-content">
                        <i class="fas fa-credit-card me-2"></i>
                        <span>Date Paie</span>
                    </div>
                </th>
            </tr>
        </thead>
        <tbody class="table-body">
            <?php if (!empty($tickets_list)) : ?>
                <?php foreach ($tickets_list as $ticket) : ?>
                    <tr class="table-row" data-ticket-id="<?= $ticket['id_ticket'] ?>">
                        <td class="td-date">
                            <div class="cell-content">
                                <div class="date-badge">
                                    <i class="fas fa-calendar me-2"></i>
                                    <?= date('d/m/Y', strtotime($ticket['date_ticket'])) ?>
                                </div>
                            </div>
                        </td>
                        <td class="td-ticket">
                            <div class="cell-content">
                                <div class="ticket-number">
                                    <i class="fas fa-hashtag me-1"></i>
                                    <?= $ticket['numero_ticket'] ?>
                                </div>
                            </div>
                        </td>
                        <td class="td-usine">
                            <div class="cell-content">
                                <div class="usine-name">
                                    <i class="fas fa-building me-2"></i>
                                    <?= $ticket['nom_usine'] ?>
                                </div>
                            </div>
                        </td>
                        <td class="td-agent">
                            <div class="cell-content">
                                <div class="agent-info">
                                    <div class="agent-avatar">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div class="agent-name">
                                        <?= $ticket['agent_nom_complet'] ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="td-vehicule">
                            <div class="cell-content">
                                <div class="vehicule-badge">
                                    <i class="fas fa-car me-2"></i>
                                    <?= $ticket['matricule_vehicule'] ?>
                                </div>
                            </div>
                        </td>
                        <td class="td-poids">
                            <div class="cell-content">
                                <div class="poids-value">
                                    <i class="fas fa-weight me-1"></i>
                                    <span class="weight-number"><?= $ticket['poids'] ?></span>
                                    <span class="weight-unit">kg</span>
                                </div>
                            </div>
                        </td>
                        <td class="td-creator">
                            <div class="cell-content">
                                <div class="creator-info">
                                    <i class="fas fa-user-edit me-2"></i>
                                    <?= $ticket['utilisateur_nom_complet'] ?>
                                </div>
                            </div>
                        </td>

                        <td class="td-price">
                            <div class="cell-content">
                                <?php if ($ticket['prix_unitaire'] === null || $ticket['prix_unitaire'] == 0.00): ?>
                                    <div class="status-badge status-pending">
                                        <i class="fas fa-clock me-2"></i>
                                        <span>En attente</span>
                                    </div>
                                <?php else: ?>
                                    <div class="price-badge">
                                        <span class="price-value"><?= number_format($ticket['prix_unitaire'], 2) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>




                        <td class="td-validation">
                            <div class="cell-content">
                                <?php if ($ticket['date_validation_boss'] === null): ?>
                                    <div class="status-badge status-progress">
                                        <i class="fas fa-spinner fa-spin me-2"></i>
                                        <span>En cours</span>
                                    </div>
                                <?php else: ?>
                                    <div class="date-badge validated">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <?= date('d/m/Y', strtotime($ticket['date_validation_boss'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>


                        <td class="td-montant">
                            <div class="cell-content">
                                <?php if ($ticket['montant_paie'] === null): ?>
                                    <div class="status-badge status-waiting">
                                        <i class="fas fa-hourglass-half me-2"></i>
                                        <span>Attente PU</span>
                                    </div>
                                <?php else: ?>
                                    <div class="amount-badge">
                                        <i class="fas fa-coins me-1"></i>
                                        <span class="amount-value"><?= number_format($ticket['montant_paie'], 2) ?></span>
                                        <span class="currency">€</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>


                        <td class="td-paie">
                            <div class="cell-content">
                                <?php if ($ticket['date_paie'] === null): ?>
                                    <div class="status-badge status-unpaid">
                                        <i class="fas fa-times-circle me-2"></i>
                                        <span>Non payé</span>
                                    </div>
                                <?php else: ?>
                                    <div class="date-badge paid">
                                        <i class="fas fa-check-double me-2"></i>
                                        <?= date('d/m/Y', strtotime($ticket['date_paie'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
          
  
      <!--    <td class="actions">
            <a class="edit" data-toggle="modal" data-target="#editModalTicket<?= $ticket['id_ticket'] ?>">
            <i class="fas fa-pen fa-xs" style="font-size:24px;color:blue"></i>
            </a>
            <a href="delete_commandes.php?id=<?= $ticket['id_ticket'] ?>" class="trash"><i class="fas fa-trash fa-xs" style="font-size:24px;color:red"></i></a>
          </td>-->

          <div class="modal fade" id="editModalTicket<?= $ticket['id_ticket'] ?>" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Modification Ticket <?= $ticket['id_ticket'] ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Formulaire de modification du ticket -->
                <form action="commandes_update.php?id=<?= $ticket['id_ticket'] ?>" method="post">
                <div class="form-group">
                        <label for="prix_unitaire">Numéro du ticket</label>
                        <input type="text" class="form-control" id="numero_ticket" name="numero_ticket" value="<?= $ticket['numero_ticket'] ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="prix_unitaire">Prix Unitaire</label>
                        <input type="number" class="form-control" id="prix_unitaire" name="prix_unitaire" value="<?= $ticket['prix_unitaire'] ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="date_validation_boss">Date de Validation</label>
                        <input type="date" class="form-control" id="date_validation_boss" name="date_validation_boss" value="<?= $ticket['date_validation_boss'] ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Sauvegarder les modifications</button>
                </form>
            </div>
        </div>
    </div>
</div>

        <!--  <td>
            <button 
            type="button" 
            class="btn btn-success" 
            data-toggle="modal" 
            data-target="#valider_ticket<?= $ticket['id_ticket'] ?>" 
            <?= $ticket['prix_unitaire'] == 0.00 ? '' : 'disabled title="Le prix est déjà validé"' ?>>
            Valider un ticket
           </button>

        </td>-->
          


         <div class="modal" id="valider_ticket<?= $ticket['id_ticket'] ?>">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-body">
                <form action="traitement_tickets.php" method="post">
                  <input type="hidden" name="id_ticket" value="<?= $ticket['id_ticket'] ?>">
                  <div class="form-group">
                    <label>Ajouter le prix unitaire</label>
                  </div>
                  <div class="form-group">
                <input type="text" class="form-control" id="exampleInputEmail1" placeholder="Prix unitaire" name="prix_unitaire">
              </div>
                  <button type="submit" class="btn btn-primary mr-2" name="saveCommande">Ajouter</button>
                  <button class="btn btn-light">Annuler</button>
                </form>
              </div>
            </div>
          </div>
        </div>


                <?php endforeach; ?>
            <?php else: ?>
                <tr class="empty-row">
                    <td colspan="11" class="empty-cell">
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-inbox"></i>
                            </div>
                            <div class="empty-content">
                                <h4>Aucun ticket trouvé</h4>
                                <p>Il n'y a pas de tickets correspondant à vos critères de recherche.</p>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
        </table>
    </div>
</div>

<!-- Pagination ultra-professionnelle -->
<div class="professional-pagination-section mt-4">
    <div class="pagination-container">
        <div class="pagination-info">
            <div class="results-summary">
                <i class="fas fa-info-circle me-2"></i>
                <span>Affichage de <?= $offset + 1 ?> à <?= min($offset + $limit, $total_tickets) ?> sur <?= $total_tickets ?> tickets</span>
            </div>
        </div>
        
        <div class="pagination-controls">
            <?php if($page > 1): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" class="pagination-btn pagination-first" title="Première page">
                    <i class="fas fa-angle-double-left"></i>
                </a>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="pagination-btn pagination-prev" title="Page précédente">
                    <i class="fas fa-angle-left"></i>
                </a>
            <?php endif; ?>
            
            <div class="pagination-numbers">
                <?php 
                $start = max(1, $page - 2);
                $end = min($total_pages, $page + 2);
                for($i = $start; $i <= $end; $i++): 
                ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                       class="pagination-number <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
            
            <?php if($page < $total_pages): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="pagination-btn pagination-next" title="Page suivante">
                    <i class="fas fa-angle-right"></i>
                </a>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>" class="pagination-btn pagination-last" title="Dernière page">
                    <i class="fas fa-angle-double-right"></i>
                </a>
            <?php endif; ?>
        </div>
        
        <div class="items-per-page">
            <form method="get" class="items-form">
                <?php foreach($_GET as $key => $value): ?>
                    <?php if($key !== 'limit' && $key !== 'page'): ?>
                        <input type="hidden" name="<?= $key ?>" value="<?= htmlspecialchars($value) ?>">
                    <?php endif; ?>
                <?php endforeach; ?>
                <div class="items-control">
                    <label for="limit" class="items-label">
                        <i class="fas fa-list me-2"></i>Afficher
                    </label>
                    <select name="limit" id="limit" class="items-select" onchange="this.form.submit()">
                        <option value="5" <?= $limit == 5 ? 'selected' : '' ?>>5</option>
                        <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10</option>
                        <option value="15" <?= $limit == 15 ? 'selected' : '' ?>>15</option>
                        <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25</option>
                        <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50</option>
                    </select>
                </div>
            </form>
        </div>
    </div>
</div>



  <div class="modal fade" id="add-ticket">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">Enregistrer un ticket</h4>
        </div>
        <div class="modal-body">
          <form class="forms-sample" method="post" action="traitement_tickets.php">
            <div class="card-body">
            <div class="form-group">
                <label for="exampleInputEmail1">Date ticket</label>
                <input type="date" class="form-control" id="exampleInputEmail1" placeholder="date ticket" name="date_ticket">
              </div>
              <div class="form-group">
                <label for="exampleInputEmail1">Numéro du Ticket</label>
                <input type="text" class="form-control" id="exampleInputEmail1" placeholder="Numero du ticket" name="numero_ticket">
              </div>
               <div class="form-group">
                  <label>Selection Usine</label>
                  <select id="select" name="usine" class="form-control">
                      <?php
                      // Vérifier si des usines existent
                      if (!empty($usines)) {

                          foreach ($usines as $usine) {
                              echo '<option value="' . htmlspecialchars($usine['id_usine']) . '">' . htmlspecialchars($usine['nom_usine']) . '</option>';
                          }
                      } else {
                          echo '<option value="">Aucune usine disponible</option>';
                      }
                      ?>
                  </select>
              </div>

              <div class="form-group">
                  <label>Selection chef Equipe</label>
                  <select id="select" name="chef_equipe" class="form-control">
                      <?php
                      // Vérifier si des usines existent
                      if (!empty($chefs_equipes)) {
                          foreach ($chefs_equipes as $chefs_equipe) {
                              echo '<option value="' . htmlspecialchars($chefs_equipe['id_chef']) . '">' . htmlspecialchars($chefs_equipe['chef_nom_complet']) . '</option>';
                          }
                      } else {
                          echo '<option value="">Aucune chef eéuipe disponible</option>';
                      }
                      ?>
                  </select>
              </div>

              <div class="form-group">
                  <label>Selection véhicules</label>
                  <select id="select" name="vehicule" class="form-control">
                      <?php
                      // Vérifier si des usines existent
                      if (!empty($vehicules)) {
                          foreach ($vehicules as $vehicule) {
                              echo '<option value="' . htmlspecialchars($vehicule['vehicules_id']) . '">' . htmlspecialchars($vehicule['matricule_vehicule']) . '</option>';
                          }
                      } else {
                          echo '<option value="">Aucun vehicule disponible</option>';
                      }
                      ?>
                  </select>
              </div>

              <div class="form-group">
                <label for="exampleInputPassword1">Poids</label>
                <input type="text" class="form-control" id="exampleInputPassword1" placeholder="Poids" name="poids">
              </div>

              <button type="submit" class="btn btn-primary mr-2" name="saveCommande">Enregister</button>
              <button class="btn btn-light">Annuler</button>
            </div>
          </form>
        </div>
      </div>
      <!-- /.modal-content -->
    </div>


    <!-- /.modal-dialog -->
  </div>

<!-- Recherche par Communes -->



  


<!-- /.row (main row) -->
</div><!-- /.container-fluid -->
<!-- /.content -->
</div>
<!-- /.content-wrapper -->
<!-- <footer class="main-footer">
    <strong>Copyright &copy; 2014-2021 <a href="https://adminlte.io">AdminLTE.io</a>.</strong>
    All rights reserved.
    <div class="float-right d-none d-sm-inline-block">
      <b>Version</b> 3.2.0
    </div>
  </footer>-->

<!-- Control Sidebar -->
<aside class="control-sidebar control-sidebar-dark">
  <!-- Control sidebar content goes here -->
</aside>
<!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les filtres
    initializeFilters();
    
    // Gestion du formulaire de recherche
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        // Masquer la table et afficher le loader
        document.getElementById('tableContainer').style.display = 'none';
        document.getElementById('loader').style.display = 'flex';
    });

    // Afficher le loader au démarrage
    document.getElementById('loader').style.display = 'flex';
    document.getElementById('tableContainer').style.display = 'none';
    
    // Cacher le loader et afficher la table après un court délai
    setTimeout(function() {
        document.getElementById('loader').style.display = 'none';
        document.getElementById('tableContainer').style.display = 'block';
        
        // Initialiser DataTables après avoir affiché la table
        if($.fn.DataTable.isDataTable('#example1')) {
            $('#example1').DataTable().destroy();
        }
        $('#example1').DataTable({
            "responsive": true,
            "lengthChange": false,
            "autoWidth": false,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.21/i18n/French.json"
            }
        });
    }, 1000);
});

// Fonction pour gérer l'affichage des filtres
function toggleFilters() {
    const filtersContent = document.getElementById('filtersContent');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (filtersContent.classList.contains('show')) {
        filtersContent.classList.remove('show');
        toggleIcon.classList.remove('fa-chevron-up');
        toggleIcon.classList.add('fa-chevron-down');
    } else {
        filtersContent.classList.add('show');
        toggleIcon.classList.remove('fa-chevron-down');
        toggleIcon.classList.add('fa-chevron-up');
    }
}

// Fonction pour initialiser les filtres
function initializeFilters() {
    // Afficher les filtres par défaut si des filtres sont actifs
    const hasActiveFilters = document.querySelector('.active-filters-section');
    if (hasActiveFilters) {
        const filtersContent = document.getElementById('filtersContent');
        const toggleIcon = document.getElementById('toggleIcon');
        filtersContent.classList.add('show');
        toggleIcon.classList.remove('fa-chevron-down');
        toggleIcon.classList.add('fa-chevron-up');
    }
    
    // Initialiser Select2 pour les sélecteurs
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('#agent_select, #usine_select').select2({
            theme: 'bootstrap4',
            width: '100%'
        });
    }
}

// Fonction pour effacer tous les filtres
function clearAllFilters() {
    window.location.href = 'tickets_jour.php';
}

// Fonction pour sauvegarder les filtres
document.addEventListener('DOMContentLoaded', function() {
    const saveFiltersBtn = document.getElementById('saveFilters');
    if (saveFiltersBtn) {
        saveFiltersBtn.addEventListener('click', function() {
            const formData = new FormData(document.getElementById('filterForm'));
            const filters = {};
            
            for (let [key, value] of formData.entries()) {
                if (value) filters[key] = value;
            }
            
            localStorage.setItem('tickets_jour_filters', JSON.stringify(filters));
            
            // Afficher une notification de succès
            if (typeof toastr !== 'undefined') {
                toastr.success('Filtres sauvegardés avec succès!');
            } else {
                alert('Filtres sauvegardés avec succès!');
            }
        });
    }
});
</script>

<style>
/* Styles existants */
.search-fieldset {
    border: 1px solid #ddd;
    border-radius: 8px;
    background-color: rgb(189, 195, 199);
    margin-bottom: 20px;
    position: relative;
    padding: 25px 15px 15px;
}

.search-legend {
    font-size: 1.2rem;
    font-weight: 600;
    color: #495057;
    width: auto;
    padding: 0 10px;
    margin-bottom: 0;
    background-color: #f8f9fa;
    border: 1px solid #ddd;
    border-radius: 4px;
    position: absolute;
    top: -15px;
    left: 15px;
}

/* Styles pour le formulaire et les entrées */
.form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
}

.input-group {
    border-radius: 8px;
    overflow: hidden;
}

.input-group-text {
    background-color: #e9ecef;
    border-color: #ced4da;
}

.input-group-prepend .input-group-text {
    border-top-left-radius: 8px;
    border-bottom-left-radius: 8px;
    border-right: none;
    background-color: #f8f9fa;
}

.input-group .form-control {
    border-top-right-radius: 8px !important;
    border-bottom-right-radius: 8px !important;
    border-left: none;
}

/* Styles pour le loader */
#loader {
    display: none;
    position: relative;
    min-height: 200px;
    width: 100%;
    background: rgba(255, 255, 255, 0.8);
}

#loader img {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

/* Styles pour le bouton de recherche */
.btn-primary {
    position: relative;
    transition: all 0.3s ease;
}

.btn-primary:active {
    transform: scale(0.95);
}

.btn-primary:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.btn-lg {
    padding: 0.5rem 2rem;
}

.pagination-container {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-top: 20px;
}

.pagination-link {
    padding: 8px;
    text-decoration: none;
    color: white;
    background-color: #007bff; 
    border: 1px solid #007bff;
    border-radius: 4px; 
    margin-right: 4px;
}

.items-per-page-form {
    margin-left: 20px;
}

label {
    margin-right: 5px;
}

.items-per-page-select {
    padding: 6px;
    border-radius: 4px; 
}

.submit-button {
    padding: 6px 10px;
    background-color: #007bff;
    color: #fff;
    border: none;
    border-radius: 4px; 
    cursor: pointer;
}
 .custom-icon {
            color: green;
            font-size: 24px;
            margin-right: 8px;
 }
 .spacing {
    margin-right: 10px; 
    margin-bottom: 20px;
}

@media only screen and (max-width: 767px) {
            
            th {
                display: none; 
            }
            tbody tr {
                display: block;
                margin-bottom: 20px;
                border: 1px solid #ccc;
                padding: 10px;
            }
            tbody tr td::before {

                font-weight: bold;
                margin-right: 5px;
            }
        }
        .margin-right-15 {
        margin-right: 15px;
       }
        .block-container {
      background-color:  #d7dbdd ;
      padding: 20px;
      border-radius: 5px;
      width: 100%;
      margin-bottom: 20px;
    }
</style>
<?php