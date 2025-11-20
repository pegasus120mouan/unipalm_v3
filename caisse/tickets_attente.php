<?php
require_once '../inc/functions/connexion.php';
require_once '../inc/functions/requete/requete_tickets.php';
require_once '../inc/functions/requete/requete_usines.php';
require_once '../inc/functions/requete/requete_chef_equipes.php';
require_once '../inc/functions/requete/requete_vehicules.php';
require_once '../inc/functions/requete/requete_agents.php';

include('header.php');

// Afficher le loader immédiatement avec styles inline de secours
echo '<style>
#pageLoader { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); display: flex; flex-direction: column; justify-content: center; align-items: center; z-index: 9999; }
.spinner-circle { width: 80px; height: 80px; border: 4px solid rgba(26,188,156,0.2); border-top: 4px solid #1abc9c; border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 20px; }
.loading-text { color: #1abc9c; font-size: 18px; font-weight: 600; text-transform: uppercase; letter-spacing: 2px; }
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
</style>
<div id="pageLoader" style="display: flex;">
    <div class="spinner-circle"></div>
    <div class="loading-text">Chargement des tickets en attente...</div>
</div>';

// Forcer le flush du contenu pour afficher le loader immédiatement
if (ob_get_level()) ob_flush();
flush();

$limit = $_GET['limit'] ?? 50; // Limite plus élevée pour les tickets en attente
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Récupérer les paramètres de filtrage
$agent_id = $_GET['agent_id'] ?? null;
$usine_id = $_GET['usine_id'] ?? null;
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';
$search_agent = $_GET['search_agent'] ?? '';
$search_usine = $_GET['search_usine'] ?? '';
$numero_ticket = $_GET['numero_ticket'] ?? '';

// Calculer le nombre total de tickets (pour la pagination) directement en SQL
$total_tickets = countTicketsAttente($conn, $agent_id, $usine_id, $date_debut, $date_fin, $numero_ticket);
$total_pages = $total_tickets > 0 ? ceil($total_tickets / $limit) : 1;
$page = max(1, min($page, $total_pages));
$offset = ($page - 1) * $limit;

// Récupérer uniquement les tickets de la page courante avec LIMIT/OFFSET (limite: 50 par page)
$tickets_list = getTicketsAttente($conn, $agent_id, $usine_id, $date_debut, $date_fin, $numero_ticket, null, $limit, $offset);

// Filtrer les tickets si un terme de recherche texte est présent (en PHP pour flexibilité)
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

// Pour compatibilité avec le reste du fichier
$tickets = $tickets_list;

// Récupérer les listes pour l'autocomplétion
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
                            <h4 class="filter-title mb-0">Filtres Avancés - Tickets en Attente</h4>
                            <p class="filter-subtitle mb-0">Rechercher et filtrer les tickets en attente de validation</p>
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
                                <label for="agent_search_filter" class="filter-label">
                                    <i class="fas fa-user-tie me-2"></i>Agent
                                </label>
                                <div class="autocomplete-container">
                                    <input type="text" 
                                           class="filter-input" 
                                           id="agent_search_filter" 
                                           placeholder="Tapez le nom de l'agent..."
                                           autocomplete="off">
                                    <input type="hidden" name="agent_id" id="agent_id_filter" value="<?= htmlspecialchars($agent_id ?? '') ?>">
                                    <div id="agent_suggestions_filter" class="autocomplete-suggestions"></div>
                                    <div class="input-icon">
                                        <i class="fas fa-search"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recherche par usine -->
                        <div class="col-lg-3 col-md-6">
                            <div class="filter-group">
                                <label for="usine_search_filter" class="filter-label">
                                    <i class="fas fa-industry me-2"></i>Usine
                                </label>
                                <div class="autocomplete-container">
                                    <input type="text" 
                                           class="filter-input" 
                                           id="usine_search_filter" 
                                           placeholder="Tapez le nom de l'usine..."
                                           autocomplete="off">
                                    <input type="hidden" name="usine_id" id="usine_id_filter" value="<?= htmlspecialchars($usine_id ?? '') ?>">
                                    <div id="usine_suggestions_filter" class="autocomplete-suggestions"></div>
                                    <div class="input-icon">
                                        <i class="fas fa-search"></i>
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
                            <a href="tickets_attente.php" class="btn-filter btn-filter-danger">
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

<!-- JavaScript Ultra-Moderne pour Tickets Attente -->
<script>
// Variables globales
let filtersVisible = false;
let selectedTickets = [];

// Fonction pour basculer l'affichage des filtres
function toggleFilters() {
    const filtersContent = document.getElementById('filtersContent');
    const toggleIcon = document.getElementById('toggleIcon');
    
    filtersVisible = !filtersVisible;
    
    if (filtersVisible) {
        filtersContent.classList.add('show');
        filtersContent.style.maxHeight = filtersContent.scrollHeight + 'px';
        toggleIcon.style.transform = 'rotate(180deg)';
    } else {
        filtersContent.classList.remove('show');
        filtersContent.style.maxHeight = '0';
        toggleIcon.style.transform = 'rotate(0deg)';
    }
}

// Fonction pour appliquer les filtres avec animation
function appliquerFiltres() {
    // Afficher le loader moderne
    showModernLoader('Application des filtres...');
    
    const agent_id = document.getElementById('agent_select').value;
    const usine_id = document.getElementById('usine_select').value;
    const date_debut = document.getElementById('date_debut').value;
    const date_fin = document.getElementById('date_fin').value;
    const numero_ticket = document.getElementById('numero_ticket').value;
    
    let params = new URLSearchParams(window.location.search);
    
    if (numero_ticket) params.set('numero_ticket', numero_ticket);
    else params.delete('numero_ticket');
    
    if (agent_id) params.set('agent_id', agent_id);
    else params.delete('agent_id');
    
    if (usine_id) params.set('usine_id', usine_id);
    else params.delete('usine_id');
    
    if (date_debut) params.set('date_debut', date_debut);
    else params.delete('date_debut');
    
    if (date_fin) params.set('date_fin', date_fin);
    else params.delete('date_fin');
    
    // Délai pour montrer l'animation du loader
    setTimeout(() => {
        window.location.href = '?' + params.toString();
    }, 500);
}

// Fonction pour sauvegarder les filtres
function saveFilters() {
    const filters = {
        agent_id: document.getElementById('agent_select').value,
        usine_id: document.getElementById('usine_select').value,
        date_debut: document.getElementById('date_debut').value,
        date_fin: document.getElementById('date_fin').value,
        numero_ticket: document.getElementById('numero_ticket').value
    };
    
    localStorage.setItem('tickets_attente_filters', JSON.stringify(filters));
    
    // Notification de succès
    showNotification('Filtres sauvegardés avec succès!', 'success');
}

// Fonction pour charger les filtres sauvegardés
function loadSavedFilters() {
    const savedFilters = localStorage.getItem('tickets_attente_filters');
    if (savedFilters) {
        const filters = JSON.parse(savedFilters);
        
        if (filters.agent_id) document.getElementById('agent_select').value = filters.agent_id;
        if (filters.usine_id) document.getElementById('usine_select').value = filters.usine_id;
        if (filters.date_debut) document.getElementById('date_debut').value = filters.date_debut;
        if (filters.date_fin) document.getElementById('date_fin').value = filters.date_fin;
        if (filters.numero_ticket) document.getElementById('numero_ticket').value = filters.numero_ticket;
    }
}

// Fonction pour effacer tous les filtres
function clearAllFilters() {
    window.location.href = 'tickets_attente.php';
}

// Fonction pour afficher le loader moderne
function showModernLoader(message = 'Traitement en cours...') {
    showPageLoader(message);
}

// Fonction pour masquer le loader moderne
function hideModernLoader() {
    hidePageLoader();
}

// Fonction pour ajouter un loader à un bouton
function addButtonLoader(buttonElement, message = 'Traitement...') {
    if (buttonElement) {
        buttonElement.classList.add('btn-loading');
        buttonElement.disabled = true;
        const originalText = buttonElement.innerHTML;
        buttonElement.setAttribute('data-original-text', originalText);
        buttonElement.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>' + message;
    }
}

// Fonction pour retirer le loader d'un bouton
function removeButtonLoader(buttonElement) {
    if (buttonElement) {
        buttonElement.classList.remove('btn-loading');
        buttonElement.disabled = false;
        const originalText = buttonElement.getAttribute('data-original-text');
        if (originalText) {
            buttonElement.innerHTML = originalText;
        }
    }
}

// Gestion des checkboxes
function initializeCheckboxes() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const ticketCheckboxes = document.querySelectorAll('.ticket-checkbox');
    
    // Sélectionner/désélectionner tous
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            ticketCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
                updateSelectedTickets();
            });
        });
    }
    
    // Gestion individuelle des checkboxes
    ticketCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectedTickets();
            
            // Mettre à jour l'état du checkbox "Sélectionner tout"
            const checkedCount = document.querySelectorAll('.ticket-checkbox:checked').length;
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = checkedCount === ticketCheckboxes.length;
                selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < ticketCheckboxes.length;
            }
        });
    });
}

// Mettre à jour la liste des tickets sélectionnés
function updateSelectedTickets() {
    selectedTickets = Array.from(document.querySelectorAll('.ticket-checkbox:checked'))
        .map(checkbox => checkbox.value);
    
    // Mettre à jour l'interface des actions en masse
    updateBulkActions();
}

// Mettre à jour les actions en masse
function updateBulkActions() {
    const bulkActions = document.querySelector('.bulk-actions');
    const count = selectedTickets.length;
    
    if (bulkActions) {
        const buttons = bulkActions.querySelectorAll('.btn-bulk');
        buttons.forEach(button => {
            if (count > 0) {
                button.disabled = false;
                button.style.opacity = '1';
                const span = button.querySelector('span');
                if (span && span.textContent.includes('sélection')) {
                    span.textContent = span.textContent.replace(/\d+/, count);
                }
            } else {
                button.disabled = true;
                button.style.opacity = '0.5';
            }
        });
    }
}

// Fonction pour valider tous les tickets sélectionnés
function validerTousLesTickets() {
    const selectedTickets = [];
    $('.ticket-checkbox:checked').each(function() {
        selectedTickets.push($(this).val());
    });

    if (selectedTickets.length === 0) {
        alert('Veuillez sélectionner au moins un ticket à valider');
        return;
    }
    
    // Mettre à jour le message du modal avec le nombre de tickets sélectionnés
    $('#ticketCountMessage').text(selectedTickets.length + ' ticket(s) sélectionné(s)');
    
    // Ouvrir le modal de saisie du prix unitaire
    $('#prixUnitaireModal').modal('show');
}

// Fonction pour confirmer la validation avec le prix unitaire saisi
function confirmerValidationAvecPrix() {
    const prixUnitaire = $('#prixUnitaire').val();
    const updateAllUsine = $('#updateAllUsine').is(':checked');
    
    if (!prixUnitaire || prixUnitaire <= 0) {
        alert('Veuillez saisir un prix unitaire valide');
        return;
    }
    
    const selectedTickets = [];
    $('.ticket-checkbox:checked').each(function() {
        selectedTickets.push($(this).val());
    });
    
    if (selectedTickets.length === 0) {
        alert('Aucun ticket sélectionné');
        return;
    }
    
    // Fermer le modal
    $('#prixUnitaireModal').modal('hide');
    
    // Afficher le loader de traitement
    showProcessingLoader(updateAllUsine);
    
    // Envoyer la requête AJAX pour valider les tickets avec le prix unitaire
    $.ajax({
        url: 'valider_tickets.php',
        method: 'POST',
        data: {
            ticket_ids: selectedTickets,
            prix_unitaire: prixUnitaire,
            is_mass_validation: true,
            update_all_usine: updateAllUsine ? 1 : 0
        },
        success: function(response) {
            try {
                const data = typeof response === 'string' ? JSON.parse(response) : response;
                
                if (data.success) {
                    // Simuler un temps de traitement pour montrer l'animation
                    setTimeout(() => {
                        hideProcessingLoader();
                        
                        // Afficher un message de succès personnalisé
                        let message = data.message;
                        if (updateAllUsine && data.usines_updated && data.usines_updated.length > 0) {
                            message += '\n\n✅ Mise à jour automatique appliquée aux autres tickets des mêmes usines.';
                        }
                        
                        showSuccessMessage(message, () => {
                            window.location.reload();
                        });
                    }, updateAllUsine ? 3000 : 1500); // Plus de temps si mise à jour par usine
                } else {
                    hideProcessingLoader();
                    alert(data.message || 'Erreur lors de la validation des tickets');
                }
            } catch (e) {
                console.error('Erreur de parsing:', e);
                hideProcessingLoader();
                alert('Erreur lors du traitement de la réponse');
            }
        },
        error: function(xhr, status, error) {
            console.error('Erreur:', error);
            console.error('Response:', xhr.responseText);
            hideProcessingLoader();
            alert('Erreur lors de la validation des tickets: ' + error);
        }
    });
}

// Fonction pour rejeter la sélection
function rejeterSelection() {
    if (selectedTickets.length === 0) {
        showNotification('Veuillez sélectionner au moins un ticket', 'warning');
        return;
    }
    
    if (confirm(`Êtes-vous sûr de vouloir rejeter ${selectedTickets.length} ticket(s) ?`)) {
        showModernLoader();
        
        // Simulation d'une requête AJAX
        setTimeout(() => {
            showNotification(`${selectedTickets.length} ticket(s) rejeté(s)`, 'info');
            hideModernLoader();
            location.reload();
        }, 2000);
    }
}

// Fonctions pour les actions individuelles
function viewTicketDetails(ticketId) {
    showNotification('Ouverture des détails du ticket...', 'info');
    // Logique pour afficher les détails
}

function rejectTicket(ticketId) {
    if (confirm('Êtes-vous sûr de vouloir rejeter ce ticket ?')) {
        showNotification('Ticket rejeté', 'warning');
        // Logique pour rejeter le ticket
    }
}

// Fonction pour exporter les tickets
function exportTickets() {
    showNotification('Préparation de l\'export...', 'info');
    // Logique d'export
}

// Fonction pour afficher des notifications
function showNotification(message, type = 'info') {
    // Utiliser Toastr si disponible, sinon alert basique
    if (typeof toastr !== 'undefined') {
        toastr[type](message);
    } else {
        alert(message);
    }
}

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les filtres
    loadSavedFilters();
    
    // Initialiser les checkboxes
    initializeCheckboxes();
    
    // Initialiser les sélecteurs de date
    const date_debut = document.getElementById('date_debut');
    const date_fin = document.getElementById('date_fin');
    
    if (date_debut && date_fin) {
        date_debut.addEventListener('change', function() {
            date_fin.min = this.value;
        });
        
        date_fin.addEventListener('change', function() {
            date_debut.max = this.value;
        });
    }
    
    // Select2 sera initialisé dans le script principal plus bas
    
    // Masquer le loader au chargement
    hideModernLoader();
    
    // Afficher les filtres par défaut
    setTimeout(() => {
        toggleFilters();
    }, 500);
    
    // Gestion des événements de sauvegarde des filtres
    const saveFiltersBtn = document.getElementById('saveFilters');
    if (saveFiltersBtn) {
        saveFiltersBtn.addEventListener('click', saveFilters);
    }
    
    // Animation d'entrée pour les éléments
    animateElements();
});

// Fonction d'animation des éléments
function animateElements() {
    const elements = document.querySelectorAll('.table-row');
    elements.forEach((element, index) => {
        element.style.animationDelay = `${index * 0.05}s`;
        element.classList.add('animate-fade-in');
    });
}

// Gestion du tri des colonnes
function initializeSorting() {
    const sortableHeaders = document.querySelectorAll('.sortable');
    
    sortableHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const sortField = this.dataset.sort;
            const currentSort = new URLSearchParams(window.location.search).get('sort');
            const currentOrder = new URLSearchParams(window.location.search).get('order');
            
            let newOrder = 'asc';
            if (currentSort === sortField && currentOrder === 'asc') {
                newOrder = 'desc';
            }
            
            const params = new URLSearchParams(window.location.search);
            params.set('sort', sortField);
            params.set('order', newOrder);
            
            showModernLoader();
            setTimeout(() => {
                window.location.href = '?' + params.toString();
            }, 300);
        });
    });
}

// Initialiser le tri au chargement
document.addEventListener('DOMContentLoaded', initializeSorting);

// Fonction pour afficher le loader de traitement avec animation
function showProcessingLoader(isUsineUpdate = false) {
    const loaderHtml = `
        <div id="processingLoader" class="processing-loader-overlay">
            <div class="processing-loader-content">
                <div class="processing-animation">
                    <div class="spinner-border text-primary" role="status" style="width: 4rem; height: 4rem;">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                </div>
                <div class="processing-text">
                    <h4 class="text-primary mb-2">
                        <i class="fas fa-cog fa-spin me-2"></i>
                        Traitement en cours...
                    </h4>
                    <p class="text-muted mb-0" id="processingMessage">
                        ${isUsineUpdate ? 
                            'Validation des tickets et mise à jour automatique par usine...' : 
                            'Validation des tickets sélectionnés...'}
                    </p>
                    <div class="progress mt-3" style="height: 6px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
                             role="progressbar" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    $('body').append(loaderHtml);
    
    // Animation de la barre de progression
    let progress = 0;
    const progressInterval = setInterval(() => {
        progress += Math.random() * 15;
        if (progress > 90) progress = 90;
        
        $('#processingLoader .progress-bar').css('width', progress + '%');
        
        if (progress > 50 && isUsineUpdate) {
            $('#processingMessage').text('Mise à jour des tickets de la même usine...');
        }
    }, 200);
    
    // Stocker l'interval pour pouvoir l'arrêter
    $('#processingLoader').data('progressInterval', progressInterval);
}

// Fonction pour masquer le loader de traitement
function hideProcessingLoader() {
    const loader = $('#processingLoader');
    if (loader.length) {
        // Arrêter l'animation de progression
        const progressInterval = loader.data('progressInterval');
        if (progressInterval) {
            clearInterval(progressInterval);
        }
        
        // Compléter la barre de progression
        loader.find('.progress-bar').css('width', '100%');
        
        // Masquer avec animation
        setTimeout(() => {
            loader.fadeOut(300, function() {
                $(this).remove();
            });
        }, 500);
    }
}

// Fonction pour afficher un message de succès personnalisé
function showSuccessMessage(message, callback) {
    const successHtml = `
        <div id="successMessage" class="success-message-overlay">
            <div class="success-message-content">
                <div class="success-animation">
                    <div class="success-checkmark">
                        <div class="check-icon">
                            <span class="icon-line line-tip"></span>
                            <span class="icon-line line-long"></span>
                            <div class="icon-circle"></div>
                            <div class="icon-fix"></div>
                        </div>
                    </div>
                </div>
                <div class="success-text">
                    <h4 class="text-success mb-3">
                        <i class="fas fa-check-circle me-2"></i>
                        Opération réussie !
                    </h4>
                    <p class="text-muted mb-4">${message.replace(/\n/g, '<br>')}</p>
                    <button type="button" class="btn btn-success" onclick="closeSuccessMessage()">
                        <i class="fas fa-arrow-right me-2"></i>Continuer
                    </button>
                </div>
            </div>
        </div>
    `;
    
    $('body').append(successHtml);
    
    // Stocker le callback
    window.successCallback = callback;
    
    // Auto-fermeture après 5 secondes
    setTimeout(() => {
        closeSuccessMessage();
    }, 5000);
}

// Fonction pour fermer le message de succès
function closeSuccessMessage() {
    const successMessage = $('#successMessage');
    if (successMessage.length) {
        successMessage.fadeOut(300, function() {
            $(this).remove();
            if (window.successCallback) {
                window.successCallback();
                window.successCallback = null;
            }
        });
    }
}
</script>

<!-- Ajout du style pour l'autocomplétion -->
<style>
.ui-autocomplete {
    max-height: 200px;
    overflow-y: auto;
    overflow-x: hidden;
    z-index: 1000;
    background-color: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 5px 0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.ui-menu-item {
    padding: 8px 15px;
    cursor: pointer;
    list-style: none;
}

.ui-menu-item:hover {
    background-color: #f8f9fa;
}

.search-container {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 5px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.input-group .form-control {
    height: 45px;
    font-size: 16px;
    border-radius: 4px;
}

.input-group .btn {
    padding: 0 20px;
}

.active-filters {
    background-color: #fff;
    padding: 10px 15px;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.badge {
    font-size: 0.9rem;
    padding: 8px 12px;
    background-color: #17a2b8;
    border: none;
}

.badge a {
    text-decoration: none;
}

.badge a:hover {
    opacity: 0.8;
}

.badge i {
    margin-right: 5px;
}

.btn-outline-danger {
    border-radius: 20px;
    padding: 5px 15px;
}

.input-group .form-control {
    height: 45px;
    font-size: 16px;
    border-radius: 4px;
}

.input-group-append .btn {
    border-top-right-radius: 4px;
    border-bottom-right-radius: 4px;
}

.spacing {
    margin-right: 10px; 
    margin-bottom: 20px;
}
</style>

<!-- Ajout de jQuery UI pour l'autocomplétion -->
<link rel="stylesheet" href="../../plugins/jquery-ui/jquery-ui.min.css">
<script src="../../plugins/jquery-ui/jquery-ui.min.js"></script>

<script>
$(document).ready(function() {
    // Préparer les données des agents pour l'autocomplétion
    var agents = <?= json_encode(array_map(function($agent) {
        return [
            'value' => $agent['id_agent'],
            'label' => $agent['nom_complet_agent']
        ];
    }, $agents)) ?>;

    // Préparer les données des usines pour l'autocomplétion
    var usines = <?= json_encode(array_map(function($usine) {
        return [
            'value' => $usine['id_usine'],
            'label' => $usine['nom_usine']
        ];
    }, $usines)) ?>;

    // Autocomplétion pour les agents
    $("#agent_search").autocomplete({
        source: function(request, response) {
            var term = request.term.toLowerCase();
            var matches = agents.filter(function(agent) {
                return agent.label.toLowerCase().indexOf(term) !== -1;
            });
            response(matches);
        },
        select: function(event, ui) {
            window.location.href = 'tickets_attente.php?' + $.param({
                ...getUrlParams(),
                'agent_id': ui.item.value,
                'search_agent': ui.item.label
            });
            return false;
        },
        minLength: 1
    });

    // Autocomplétion pour les usines
    $("#usine_search").autocomplete({
        source: function(request, response) {
            var term = request.term.toLowerCase();
            var matches = usines.filter(function(usine) {
                return usine.label.toLowerCase().indexOf(term) !== -1;
            });
            response(matches);
        },
        select: function(event, ui) {
            window.location.href = 'tickets_attente.php?' + $.param({
                ...getUrlParams(),
                'usine_id': ui.item.value,
                'search_usine': ui.item.label
            });
            return false;
        },
        minLength: 1
    });

    // Fonction utilitaire pour obtenir les paramètres d'URL actuels
    function getUrlParams() {
        var params = {};
        window.location.search.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(str, key, value) {
            params[key] = value;
        });
        return params;
    }
});
</script>

<!-- CSS Ultra-Professionnel pour Tickets Attente -->
<style>
/* Variables CSS pour cohérence */
:root {
    --primary-color: #667eea;
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --secondary-color: #f093fb;
    --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --success-color: #4facfe;
    --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --warning-color: #ffecd2;
    --warning-gradient: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
    --danger-color: #ff9a9e;
    --danger-gradient: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
    --dark-color: #2c3e50;
    --light-color: #f8f9fa;
    --border-radius: 12px;
    --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Section des filtres */
.filters-section {
    margin-bottom: 2rem;
}

.filters-container {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.filters-header {
    background: var(--primary-gradient);
    padding: 1.5rem 2rem;
    cursor: pointer;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}

.filters-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.filters-header:hover::before {
    left: 100%;
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
    backdrop-filter: blur(10px);
}

.filter-icon-container i {
    color: white;
    font-size: 1.2rem;
}

.filter-title {
    color: white;
    font-weight: 700;
    font-size: 1.4rem;
    margin: 0;
}

.filter-subtitle {
    color: rgba(255, 255, 255, 0.9);
    font-size: 0.9rem;
    margin: 0;
}

.toggle-icon {
    color: white;
    font-size: 1.2rem;
    transition: var(--transition);
}

.filters-content {
    padding: 2rem;
    background: white;
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
    display: block;
    font-weight: 600;
    color: var(--dark-color);
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
    border: 2px solid #e9ecef;
    border-radius: var(--border-radius);
    font-size: 0.9rem;
    transition: var(--transition);
    background: white;
}

.filter-input:focus,
.filter-select:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.input-icon,
.select-icon {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    pointer-events: none;
}

.filter-actions {
    padding-top: 1.5rem;
    border-top: 1px solid #e9ecef;
}

.btn-filter {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: var(--border-radius);
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}

.btn-filter-primary {
    background: var(--primary-gradient);
    color: white;
}

.btn-filter-secondary {
    background: var(--secondary-gradient);
    color: white;
}

.btn-filter-danger {
    background: var(--danger-gradient);
    color: white;
}

.btn-filter:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

/* Filtres actifs */
.active-filters-section {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    box-shadow: var(--box-shadow);
}

.active-filters-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.active-filters-title {
    color: var(--dark-color);
    font-weight: 700;
    margin: 0;
}

.clear-all-filters {
    background: var(--danger-gradient);
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: var(--border-radius);
    font-size: 0.8rem;
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
    background: white;
    border-radius: var(--border-radius);
    padding: 0.5rem 1rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    border-left: 4px solid;
}

.filter-tag-ticket { border-left-color: var(--primary-color); }
.filter-tag-agent { border-left-color: var(--success-color); }
.filter-tag-usine { border-left-color: var(--warning-color); }
.filter-tag-date { border-left-color: var(--secondary-color); }

.filter-tag-icon {
    margin-right: 0.5rem;
    color: var(--dark-color);
}

.filter-tag-content {
    display: flex;
    flex-direction: column;
}

.filter-tag-label {
    font-size: 0.7rem;
    color: #6c757d;
    font-weight: 600;
}

.filter-tag-value {
    font-size: 0.8rem;
    color: var(--dark-color);
    font-weight: 700;
}

.filter-tag-remove {
    margin-left: 0.75rem;
    color: #dc3545;
    text-decoration: none;
    padding: 0.25rem;
    border-radius: 50%;
    transition: var(--transition);
}

.filter-tag-remove:hover {
    background: rgba(220, 53, 69, 0.1);
}

/* En-tête de page */
.page-header-section {
    margin-bottom: 2rem;
}

.page-header-content {
    background: var(--primary-gradient);
    border-radius: var(--border-radius);
    padding: 2rem;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    overflow: hidden;
}

.page-header-content::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 100%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: float 6s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-20px) rotate(180deg); }
}

.page-title-container {
    display: flex;
    align-items: center;
}

.page-icon {
    width: 60px;
    height: 60px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1.5rem;
    backdrop-filter: blur(10px);
}

.page-icon i {
    font-size: 1.5rem;
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    margin: 0;
}

.page-subtitle {
    font-size: 1rem;
    opacity: 0.9;
    margin: 0;
}

.page-actions {
    display: flex;
    gap: 1rem;
}

.btn-action {
    padding: 0.75rem 1.5rem;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: var(--border-radius);
    background: rgba(255, 255, 255, 0.1);
    color: white;
    text-decoration: none;
    font-weight: 600;
    transition: var(--transition);
    backdrop-filter: blur(10px);
}

.btn-action:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
}

/* Statistiques */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.stat-card {
    background: white;
    border-radius: var(--border-radius);
    padding: 1.5rem;
    box-shadow: var(--box-shadow);
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
}

.stat-card-primary::before { background: var(--primary-gradient); }
.stat-card-warning::before { background: var(--warning-gradient); }
.stat-card-success::before { background: var(--success-gradient); }
.stat-card-danger::before { background: var(--danger-gradient); }

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
}

.stat-card-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
}

.stat-card-primary .stat-card-icon { background: var(--primary-gradient); }
.stat-card-warning .stat-card-icon { background: var(--warning-gradient); }
.stat-card-success .stat-card-icon { background: var(--success-gradient); }
.stat-card-danger .stat-card-icon { background: var(--danger-gradient); }

.stat-card-content {
    flex: 1;
    margin-left: 1rem;
}

.stat-card-number {
    font-size: 2rem;
    font-weight: 700;
    color: var(--dark-color);
    line-height: 1;
}

.stat-card-label {
    font-size: 0.9rem;
    color: #6c757d;
    font-weight: 600;
    margin-top: 0.25rem;
}

.stat-card-trend {
    display: flex;
    align-items: center;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--success-color);
}

/* Section tableau */
.table-section {
    margin-top: 2rem;
}

.table-container {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    overflow: hidden;
}

.table-header {
    background: var(--primary-gradient);
    padding: 1.5rem 2rem;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.table-title {
    font-size: 1.3rem;
    font-weight: 700;
    margin: 0;
}

.table-subtitle {
    font-size: 0.9rem;
    opacity: 0.9;
    margin-top: 0.25rem;
}

.results-count,
.total-count {
}

.bulk-actions {
    display: flex;
    gap: 0.75rem;
}

.btn-bulk {
    padding: 0.5rem 1rem;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: var(--border-radius);
    background: rgba(255, 255, 255, 0.1);
    color: white;
    font-size: 0.8rem;
    font-weight: 600;
    transition: var(--transition);
    backdrop-filter: blur(10px);
}

.btn-bulk:hover {
    background: rgba(255, 255, 255, 0.2);
}

/* Scripts modernes */
.modern-loader {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 3rem;
    background: white;
}

.loader-container {
    text-align: center;
}

.loader-rings {
    display: inline-block;
    position: relative;
    width: 80px;
    height: 80px;
    margin-bottom: 1rem;
}

.ring {
    box-sizing: border-box;
    display: block;
    position: absolute;
    width: 64px;
    height: 64px;
    margin: 8px;
    border: 8px solid var(--primary-color);
    border-radius: 50%;
    animation: ring 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
    border-color: var(--primary-color) transparent transparent transparent;
}

.ring:nth-child(1) { animation-delay: -0.45s; }
.ring:nth-child(2) { animation-delay: -0.3s; }
.ring:nth-child(3) { animation-delay: -0.15s; }

@keyframes ring {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loader-text h4 {
    color: var(--dark-color);
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.loader-text p {
    color: #6c757d;
    margin: 0;
}

/* Tableau moderne */
.modern-table-wrapper {
    overflow-x: auto;
}

.modern-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 1.1rem;
}

.modern-table td {
    padding: 1rem 0.75rem;
    font-size: 1.1rem;
    line-height: 1.4;
}

.modern-table th {
    padding: 1.2rem 0.75rem;
    font-size: 1rem;
    font-weight: 600;
}

.table-head {
    background: var(--primary-gradient);
    color: white;
    padding: 1rem 0.75rem;
    text-align: left;
    border-bottom: 2px solid #dee2e6;
    color: var(--dark-color);
    position: relative;
}

.th-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.sortable {
    cursor: pointer;
    transition: var(--transition);
}

.sortable:hover {
    background: rgba(102, 126, 234, 0.1);
}

.sort-icon {
    opacity: 0.5;
    transition: var(--transition);
}

.sortable:hover .sort-icon {
    opacity: 1;
    color: var(--primary-color);
}

.checkbox-column,
.actions-column {
    width: 80px;
    text-align: center;
}

/* Lignes du tableau */
.table-row {
    transition: var(--transition);
    border-bottom: 1px solid #f1f3f4;
}

.table-row:hover {
    background: rgba(102, 126, 234, 0.05);
    transform: scale(1.01);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.table-row td {
    padding: 1rem 0.75rem;
    vertical-align: middle;
}

.cell-content {
    display: flex;
    align-items: center;
}

/* Checkboxes personnalisées */
.custom-checkbox {
    position: relative;
    display: inline-block;
}

.checkbox-input {
    opacity: 0;
    position: absolute;
}

.checkbox-label {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 2px solid #dee2e6;
    border-radius: 4px;
    cursor: pointer;
    transition: var(--transition);
    position: relative;
}

.checkbox-input:checked + .checkbox-label {
    background: var(--primary-gradient);
    border-color: var(--primary-color);
}

.checkbox-input:checked + .checkbox-label::after {
    content: '✓';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-size: 12px;
    font-weight: bold;
}

/* Badges et statuts */
.date-badge,
.ticket-number,
.usine-badge,
.agent-info,
.vehicule-badge,
.poids-value,
.createur-info {
    display: flex;
    align-items: center;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 1rem;
    white-space: nowrap;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.7rem 1.4rem;
    border-radius: 25px;
    font-size: 1rem;
    font-weight: 500;
    letter-spacing: 0.3px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.status-badge::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s;
}

.status-badge:hover::before {
    left: 100%;
}

.status-badge:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
}

.status-pending {
    background: linear-gradient(135deg, #ff9a56 0%, #ff6b35 100%);
    color: white;
    border: 1px solid rgba(255, 154, 86, 0.3);
}

.status-validated {
    background: linear-gradient(135deg, #4ecdc4 0%, #44a08d 100%);
    color: white;
    border: 1px solid rgba(78, 205, 196, 0.3);
}

.status-in-progress {
    background: linear-gradient(135deg, #ffa726 0%, #fb8c00 100%);
    color: white;
    border: 1px solid rgba(255, 167, 38, 0.3);
}

.status-waiting {
    background: linear-gradient(135deg, #42a5f5 0%, #1e88e5 100%);
    color: white;
    border: 1px solid rgba(66, 165, 245, 0.3);
}

.status-amount {
    background: linear-gradient(135deg, #66bb6a 0%, #43a047 100%);
    color: white;
    border: 1px solid rgba(102, 187, 106, 0.3);
}

.status-unpaid {
    background: linear-gradient(135deg, #ef5350 0%, #e53935 100%);
    color: white;
    border: 1px solid rgba(239, 83, 80, 0.3);
}

/* Liens */
.ticket-link,
.usine-link {
    text-decoration: none;
    color: var(--primary-color);
    transition: var(--transition);
}

.ticket-link:hover,
.usine-link:hover {
    color: var(--secondary-color);
    text-decoration: none;
}

/* Boutons d'action du tableau */
.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.btn-action-table {
    padding: 0.6rem 1.2rem;
    border: none;
    border-radius: var(--border-radius);
    font-size: 1rem;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.btn-validate {
    background: var(--success-gradient);
    color: white;
}

.btn-view {
    background: var(--secondary-gradient);
    color: white;
}

.btn-reject {
    background: var(--danger-gradient);
    color: white;
}

.btn-action-table:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}


/* Responsive */
@media (max-width: 768px) {
    .page-header-content {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .table-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .bulk-actions {
        justify-content: center;
    }
    
    .modern-table {
        font-size: 1rem;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .active-filters-list {
        flex-direction: column;
    }
}

/* Amélioration de la lisibilité - Tailles de police plus grandes */
.table tbody tr {
    font-size: 1.1rem;
}

.table tbody td {
    padding: 1.2rem;
    font-size: 1.1rem;
    line-height: 1.5;
}

.table thead th {
    padding: 1.2rem;
    font-size: 1.1rem;
    font-weight: 600;
}

.status-badge {
    font-size: 1.1rem !important;
    padding: 0.8rem 1.5rem !important;
}

.btn-action-table {
    font-size: 1.1rem !important;
    padding: 0.7rem 1.3rem !important;
}

/* Responsive pour les nouvelles tailles */
@media (max-width: 768px) {
    .table tbody td, .table thead th {
        font-size: 1rem;
        padding: 1rem;
    }

    .status-badge {
        font-size: 1rem !important;
        padding: 0.6rem 1.2rem !important;
    }

    .btn-action-table {
        font-size: 1rem !important;
        padding: 0.6rem 1rem !important;
    }
}

/* Amélioration de la pagination */
.pagination-container {
    font-size: 1.2rem;
    padding: 1.5rem;
}

.pagination-container .btn {
    font-size: 1.1rem;
    padding: 0.8rem 1.5rem;
    margin: 0 0.5rem;
}

.pagination-container span {
    font-size: 1.2rem;
    font-weight: 600;
}

/* Amélioration des filtres et labels */
.filter-label {
    font-size: 1rem;
    font-weight: 600;
}

.filter-input {
    font-size: 1rem;
    padding: 0.8rem 1rem;
}

.filter-tag-label {
    font-size: 0.9rem;
}

.filter-tag-value {
    font-size: 1rem;
    font-weight: 700;
}

/* Amélioration des cartes statistiques */
.stat-number {
    font-size: 2.2rem;
}

.stat-label {
    font-size: 1rem;
}

/* Animations d'entrée */
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

.filters-section,
.page-header-section,
.table-section {
    animation: fadeInUp 0.6s ease-out;
}

.stat-card {
    animation: fadeInUp 0.6s ease-out;
}

.stat-card:nth-child(1) { animation-delay: 0.1s; }
.stat-card:nth-child(2) { animation-delay: 0.2s; }
        }
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


<!-- En-tête avec statistiques ultra-moderne -->
<div class="page-header-section mb-5">
    <div class="container-fluid">
        <div class="page-header-content">
            <div class="page-title-container">
                <div class="page-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="page-title-content">
                    <h1 class="page-title">Tickets en Attente</h1>
                    <p class="page-subtitle">Gestion et validation des tickets en attente</p>
                </div>
            </div>
            
            <div class="page-actions">
                <button class="btn-action btn-action-primary" data-toggle="modal" data-target="#modalUsineTickets">
                    <i class="fas fa-check-double me-2"></i>
                    <span>Validation en masse</span>
                </button>
                <button class="btn-action btn-action-secondary" onclick="exportTickets()">
                    <i class="fas fa-download me-2"></i>
                    <span>Exporter</span>
                </button>
            </div>
        </div>
        
        <!-- Statistiques modernes -->
        <div class="stats-grid mt-4">
            <div class="stat-card stat-card-primary">
                <div class="stat-card-icon">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <div class="stat-card-content">
                    <div class="stat-card-number"><?= $total_tickets ?></div>
                    <div class="stat-card-label">Total Tickets</div>
                </div>
                <div class="stat-card-trend">
                    <i class="fas fa-arrow-up"></i>
                    <span>+12%</span>
                </div>
            </div>
            
            <div class="stat-card stat-card-warning">
                <div class="stat-card-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-card-content">
                    <div class="stat-card-number"><?= count($tickets) ?></div>
                    <div class="stat-card-label">En Attente</div>
                </div>
                <div class="stat-card-trend">
                    <i class="fas fa-arrow-down"></i>
                    <span>-5%</span>
                </div>
            </div>
            
            <div class="stat-card stat-card-success">
                <div class="stat-card-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-card-content">
                    <div class="stat-card-number">0</div>
                    <div class="stat-card-label">Validés Aujourd'hui</div>
                </div>
                <div class="stat-card-trend">
                    <i class="fas fa-minus"></i>
                    <span>0%</span>
                </div>
            </div>
            
            <div class="stat-card stat-card-danger">
                <div class="stat-card-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-card-content">
                    <div class="stat-card-number">0</div>
                    <div class="stat-card-label">Rejetés</div>
                </div>
                <div class="stat-card-trend">
                    <i class="fas fa-minus"></i>
                    <span>0%</span>
                </div>
            </div>
        </div>
    </div>
</div>



 <!-- <button type="button" class="btn btn-primary spacing" data-toggle="modal" data-target="#add-commande">
    Enregistrer une commande
  </button>


    <button type="button" class="btn btn-outline-secondary spacing" data-toggle="modal" data-target="#recherche-commande1">
        <i class="fas fa-print custom-icon"></i>
    </button>


  <a class="btn btn-outline-secondary" href="commandes_print.php"><i class="fa fa-print" style="font-size:24px;color:green"></i></a>


     Utilisation du formulaire Bootstrap avec ms-auto pour aligner à droite
<form action="page_recherche.php" method="GET" class="d-flex ml-auto">
    <input class="form-control me-2" type="search" name="recherche" style="width: 400px;" placeholder="Recherche..." aria-label="Search">
    <button class="btn btn-outline-primary spacing" style="margin-left: 15px;" type="submit">Rechercher</button>
</form>

-->




<!-- Tableau ultra-moderne des tickets -->
<div class="table-section">
    <div class="container-fluid">
        <div class="table-container">
            <div class="table-header">
                <div class="table-title-container">
                    <h3 class="table-title">
                        <i class="fas fa-list me-2"></i>
                        Liste des Tickets en Attente
                    </h3>
                    <div class="table-subtitle">
                        <span class="results-count"><?= count($tickets_list) ?></span> sur <span class="total-count"><?= count($tickets) ?></span> tickets
                    </div>
                </div>
                
                <div class="table-actions">
                    <div class="bulk-actions">
                        <button type="button" class="btn-bulk btn-bulk-success" onclick="validerTousLesTickets()">
                            <i class="fas fa-check-double me-2"></i>
                            <span>Valider la sélection</span>
                        </button>
                        <button type="button" class="btn-bulk btn-bulk-danger" onclick="rejeterSelection()">
                            <i class="fas fa-times me-2"></i>
                            <span>Rejeter la sélection</span>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Loader moderne -->
            <div id="modernLoader" class="modern-loader" style="display: none;">
                <div class="loader-container">
                    <div class="loader-rings">
                        <div class="ring"></div>
                        <div class="ring"></div>
                        <div class="ring"></div>
                    </div>
                    <div class="loader-text">
                        <h4>Chargement des tickets...</h4>
                        <p>Veuillez patienter pendant le traitement des données</p>
                    </div>
                </div>
            </div>
            
            <div class="table-content">
                <div class="modern-table-wrapper">
                    <table class="modern-table" id="example1">
                        <thead class="table-head">
                            <tr>
                                <th class="checkbox-column">
                                    <div class="custom-checkbox">
                                        <input type="checkbox" id="selectAll" class="checkbox-input">
                                        <label for="selectAll" class="checkbox-label"></label>
                                    </div>
                                </th>
                                <th class="sortable" data-sort="date">
                                    <div class="th-content">
                                        <span>Date Ticket</span>
                                    </div>
                                </th>
                                <th class="sortable" data-sort="numero">
                                    <div class="th-content">
                                        <span>Numéro Ticket</span>
                                      
                                    </div>
                                </th>
                                <th class="sortable" data-sort="usine">
                                    <div class="th-content">
                                        <span>Usine</span>
                                    </div>
                                </th>
                                <th class="sortable" data-sort="agent">
                                    <div class="th-content">
                                        <span>Chargé de Mission</span>
                                    </div>
                                </th>
                                <th class="sortable" data-sort="vehicule">
                                    <div class="th-content">
                                        <span>Véhicule</span>
                                    </div>
                                </th>
                                <th class="sortable" data-sort="poids">
                                    <div class="th-content">
                                        <span>Poids</span>
                                    </div>
                                </th>

                                <th class="sortable" data-sort="date_ajout">
                                    <div class="th-content">
                                        <span>Date Ajout</span>
                                    </div>
                                </th>
                                <th class="sortable" data-sort="prix">
                                    <div class="th-content">
                                        <span>Prix Unitaire</span>
                                    </div>
                                </th>
                                <th class="sortable" data-sort="montant">
                                    <div class="th-content">
                                        <span>Montant</span>
                                    </div>
                                </th>
                                <th class="actions-column">
                                    <div class="th-content">
                                        <span>Actions</span>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="table-body">
                            <?php if (!empty($tickets_list)) : ?>
                                <?php foreach ($tickets_list as $ticket) : ?>
                                    <tr class="table-row" data-ticket-id="<?= $ticket['id_ticket'] ?>">
                                        <td class="checkbox-cell">
                                            <div class="custom-checkbox">
                                                <input type="checkbox" id="ticket_<?= $ticket['id_ticket'] ?>" class="checkbox-input ticket-checkbox" value="<?= $ticket['id_ticket'] ?>">
                                                <label for="ticket_<?= $ticket['id_ticket'] ?>" class="checkbox-label"></label>
                                            </div>
                                        </td>
                                        <td class="date-cell">
                                            <div class="cell-content">
                                                <div class="date-badge">
                                                    <?= date('d/m/Y', strtotime($ticket['date_ticket'])) ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="ticket-number-cell">
                                            <div class="cell-content">
                                                <a href="#" class="ticket-link" data-toggle="modal" data-target="#ticketModal<?= $ticket['id_ticket'] ?>">
                                                    <div class="ticket-number">
                                                        <?= $ticket['numero_ticket'] ?>
                                                    </div>
                                                </a>
                                            </div>
                                        </td>
                                        <td class="usine-cell">
                                            <div class="cell-content">
                                                <a href="javascript:void(0)" class="usine-link" onclick="showUsineTickets(<?= $ticket['id_usine'] ?>, '<?= addslashes($ticket['nom_usine']) ?>')">
                                                    <div class="usine-badge">
                                                        <?= $ticket['nom_usine'] ?>
                                                    </div>
                                                </a>
                                            </div>
                                        </td>
                                        <td class="agent-cell">
                                            <div class="cell-content">
                                                <div class="agent-info">
                                                    <?= $ticket['agent_nom_complet'] ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="vehicule-cell">
                                            <div class="cell-content">
                                                <div class="vehicule-badge">
                                                    <?= $ticket['matricule_vehicule'] ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="poids-cell">
                                            <div class="cell-content">
                                                <div class="poids-value">
                                                    <?= $ticket['poids'] ?> kg
                                                </div>
                                            </div>
                                        </td>
                                        <td class="date-ajout-cell">
                                            <div class="cell-content">
                                                <div class="date-badge">
                                                    <?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="prix-cell">
                                            <div class="cell-content">
                                                <?php if ($ticket['prix_unitaire'] === null || $ticket['prix_unitaire'] == 0.00): ?>
                                                    <div class="status-badge status-pending">
                                                        <span>Attente</span>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="status-badge status-validated">
                                                        <span><?= $ticket['prix_unitaire'] ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="montant-cell">
                                            <div class="cell-content">
                                                <?php if ($ticket['montant_paie'] === null): ?>
                                                    <div class="status-badge status-waiting">
                                                        <span>En attente de PU</span>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="status-badge status-amount">
                                                        <span><?= $ticket['montant_paie'] ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="actions-cell">
                                            <div class="cell-content">
                                                <div class="action-buttons">
                                                    <button type="button" class="btn-action-table btn-validate" data-toggle="modal" data-target="#valider_un_ticket<?= $ticket['id_ticket'] ?>" title="Valider le ticket">
                                                        <span>Valider</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
          <div class="modal" id="valider_un_ticket<?= $ticket['id_ticket'] ?>">

          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-body">
                <form action="valider_un_tickets.php" method="post">
                  <input type="hidden" name="id_ticket" value="<?= $ticket['id_ticket'] ?>">
                  <input type="hidden" name="current_url" value="<?= $_SERVER['REQUEST_URI'] ?>">
                  <div class="form-group">
                    <label>Ajouter le prix unitaire</label>
                  </div>
                  <div class="form-group">
                    <input type="number" 
                           class="form-control" 
                           name="prix_unitaire" 
                           value="<?= $ticket['prix_unitaire'] ?>" 
                           <?= ($ticket['prix_unitaire'] > 0) ? 'readonly' : '' ?> 
                           min="0.01" 
                           step="0.01" 
                           required>
                  </div>
                  <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Valider</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>

<script>
function submitValidation(event, ticketId) {
    event.preventDefault();
    const form = document.getElementById('form-validation-' + ticketId);
    const prix_unitaire = form.querySelector('[name="prix_unitaire"]').value;
    const id_ticket = form.querySelector('[name="id_ticket"]').value;

    if (!prix_unitaire || prix_unitaire <= 0) {
        alert('Veuillez entrer un prix unitaire valide');
        return false;
    }

    $.ajax({
        url: 'valider_tickets.php',
        method: 'POST',
        data: {
            ticket_id: id_ticket,
            prix_unitaire: prix_unitaire
        },
        success: function(response) {
            try {
                const data = typeof response === 'string' ? JSON.parse(response) : response;
                if (data.success) {
                    // Fermer le modal
                    $(`#valider_ticket${ticketId}`).modal('hide');
                    // Recharger la page
                    window.location.reload();
                } else {
                    alert(data.message || 'Erreur lors de la validation du ticket');
                }
            } catch (e) {
                console.error('Erreur de parsing:', e);
                alert('Erreur lors du traitement de la réponse');
            }
        },
        error: function(xhr, status, error) {
            console.error('Erreur:', error);
            console.error('Response:', xhr.responseText);
            alert('Erreur lors de la validation du ticket');
        }
    });

    return false;
}

// Pour la validation multiple
function validerTicketsSelectionnes() {
    const selectedTickets = [];
    $('.ticket-checkbox:checked').each(function() {
        selectedTickets.push($(this).val());
    });

    if (selectedTickets.length === 0) {
        alert('Veuillez sélectionner au moins un ticket à valider');
        return;
    }

    if (confirm('Voulez-vous vraiment valider les tickets sélectionnés ?')) {
        $.ajax({
            url: 'valider_tickets.php',
            method: 'POST',
            data: { 
                ticket_ids: selectedTickets,
                is_mass_validation: true
            },
            success: function(response) {
                try {
                    const data = typeof response === 'string' ? JSON.parse(response) : response;
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || 'Erreur lors de la validation des tickets');
                    }
                } catch (e) {
                    console.error('Erreur de parsing:', e);
                    alert('Erreur lors du traitement de la réponse');
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur:', error);
                console.error('Response:', xhr.responseText);  // Pour voir la réponse brute
                alert('Erreur lors de la validation des tickets: ' + error);
            }
        });
    }
}
</script>

      <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="13" class="text-center">Pas de tickets en attente de validation</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>

</div>

  <div class="pagination-container bg-secondary d-flex justify-content-center w-100 text-white p-3">
    <?php if($page > 1): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="btn btn-primary"><</a>
    <?php endif; ?>
    
    <span class="mx-2"><?= $page . '/' . $total_pages ?></span>
    
    <?php if($page < $total_pages): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="btn btn-primary">></a>
    <?php endif; ?>
    
    <form action="" method="get" class="items-per-page-form ml-3">
        <?php
        // Conserver les paramètres de filtrage actuels
        foreach (['agent_id', 'usine_id', 'search_agent', 'search_usine', 'numero_ticket'] as $param) {
            if (isset($_GET[$param])) {
                echo '<input type="hidden" name="' . $param . '" value="' . htmlspecialchars($_GET[$param]) . '">';
            }
        }
        ?>
        <label for="limit">Afficher :</label>
        <select name="limit" id="limit" class="items-per-page-select" onchange="this.form.submit()">
            <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25</option>
            <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50</option>
            <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100</option>
            <option value="200" <?= $limit == 200 ? 'selected' : '' ?>>200</option>
        </select>
    </form>
  </div>





<!-- Modal de recherche par agent -->
<div class="modal fade" id="searchByAgentModal" tabindex="-1" role="dialog" aria-labelledby="searchByAgentModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="searchByAgentModalLabel">Filtrer par agent</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="" method="get">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="agent_id">Sélectionner un agent</label>
                        <select class="form-control" name="agent_id" id="agent_id" required>
                            <option value="">Choisir un agent</option>
                            <?php foreach ($agents as $agent): ?>
                                <option value="<?= $agent['id_agent'] ?>" <?= ($agent_id == $agent['id_agent'] ? 'selected' : '') ?>>
                                    <?= htmlspecialchars($agent['nom_agent'] . ' ' . $agent['prenom_agent']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Recherche par tickets-->
<div class="modal fade" id="search_ticket">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-search mr-2"></i>Rechercher des tickets
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-column">
                    <button type="button" class="btn btn-primary btn-block mb-3" data-toggle="modal" data-target="#searchByAgentModal" data-dismiss="modal">
                        <i class="fas fa-user-tie mr-2"></i>Recherche par chargé de Mission
                    </button>
                    
                    <button type="button" class="btn btn-primary btn-block mb-3" data-toggle="modal" data-target="#searchByUsineModal" data-dismiss="modal">
                        <i class="fas fa-industry mr-2"></i>Recherche par Usine
                    </button>
                    
                    <button type="button" class="btn btn-primary btn-block mb-3" data-toggle="modal" data-target="#searchByDateModal" data-dismiss="modal">
                        <i class="fas fa-calendar-alt mr-2"></i>Recherche par Date
                    </button>
                    
                    <button type="button" class="btn btn-primary btn-block mb-3" data-toggle="modal" data-target="#searchByVehiculeModal" data-dismiss="modal">
                        <i class="fas fa-truck mr-2"></i>Recherche par Véhicule
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Recherche par Agent -->
<div class="modal fade" id="searchByAgentModal" tabindex="-1" role="dialog" aria-labelledby="searchByAgentModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="searchByAgentModalLabel">
                    <i class="fas fa-user-tie mr-2"></i>Recherche par chargé de Mission
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="searchByAgentForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="agent_id">Sélectionner un chargé de Mission</label>
                        <select class="form-control" name="agent_id" id="agent_id" required>
                            <option value="">Choisir un chargé de Mission</option>
                            <?php foreach ($agents as $agent): ?>
                                <option value="<?= $agent['id_agent'] ?>">
                                    <?= $agent['nom_agent'] . ' ' . $agent['prenom_agent'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Rechercher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Recherche par Usine -->
<div class="modal fade" id="searchByUsineModal" tabindex="-1" role="dialog" aria-labelledby="searchByUsineModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="searchByUsineModalLabel">
                    <i class="fas fa-industry mr-2"></i>Recherche par Usine
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="searchByUsineForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="usine">Sélectionner une Usine</label>
                        <select class="form-control" name="usine" id="usine" required>
                            <option value="">Choisir une usine</option>
                            <?php foreach ($usines as $usine): ?>
                                <option value="<?= $usine['id_usine'] ?>">
                                    <?= $usine['nom_usine'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Rechercher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Recherche par Date -->
<div class="modal fade" id="searchByDateModal" tabindex="-1" role="dialog" aria-labelledby="searchByDateModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="searchByDateModalLabel">
                    <i class="fas fa-calendar-alt mr-2"></i>Recherche par Date
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="searchByDateForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="date_creation">Sélectionner une Date</label>
                        <input type="date" class="form-control" id="date_creation" name="date_creation" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Rechercher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Recherche par Véhicule -->
<div class="modal fade" id="searchByVehiculeModal" tabindex="-1" role="dialog" aria-labelledby="searchByVehiculeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="searchByVehiculeModalLabel">
                    <i class="fas fa-truck mr-2"></i>Recherche par Véhicule
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="searchByVehiculeForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="chauffeur">Sélectionner un Véhicule</label>
                        <select class="form-control" name="chauffeur" id="chauffeur" required>
                            <option value="">Choisir un véhicule</option>
                            <?php foreach ($vehicules as $vehicule): ?>
                                <option value="<?= $vehicule['vehicules_id'] ?>">
                                    <?= $vehicule['matricule_vehicule'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Rechercher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Gestionnaire pour le formulaire de recherche par usine
document.getElementById('searchByUsineForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const usineId = document.getElementById('usine').value;
    if (usineId) {
        window.location.href = 'tickets.php?usine=' + usineId;
    }
});

// Gestionnaire pour le formulaire de recherche par date
document.getElementById('searchByDateForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const date = document.getElementById('date_creation').value;
    if (date) {
        window.location.href = 'tickets.php?date_creation=' + date;
    }
});

// Gestionnaire pour le formulaire de recherche par véhicule
document.getElementById('searchByVehiculeForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const vehiculeId = document.getElementById('chauffeur').value;
    if (vehiculeId) {
        window.location.href = 'tickets.php?chauffeur=' + vehiculeId;
    }
});
</script>

<?php foreach ($tickets as $ticket) : ?>
  <div class="modal fade" id="ticketModal<?= $ticket['id_ticket'] ?>" tabindex="-1" role="dialog" aria-labelledby="ticketModalLabel<?= $ticket['id_ticket'] ?>" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="ticketModalLabel<?= $ticket['id_ticket'] ?>">
            <i class="fas fa-ticket-alt mr-2"></i>Détails du Ticket #<?= $ticket['numero_ticket'] ?>
          </h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="row mb-4">
            <div class="col-md-6">
              <div class="info-group">
                <label class="text-muted">Date du ticket:</label>
                <p><?= date('d/m/Y', strtotime($ticket['date_ticket'])) ?></p>
              </div>
              <div class="info-group">
                <label class="text-muted">Usine:</label>
                <p><?= $ticket['nom_usine'] ?></p>
              </div>
              <div class="info-group">
                <label class="text-muted">Agent:</label>
                <p><?= $ticket['agent_nom_complet'] ?></p>
              </div>
              <div class="info-group">
                <label class="text-muted">Véhicule:</label>
                <p><?= $ticket['matricule_vehicule'] ?></p>
              </div>
              <div class="info-group">
                <label class="text-muted">Poids ticket:</label>
                <p><?= $ticket['poids'] ?> kg</p>
              </div>
            </div>
            <div class="col-md-6">
              <div class="info-group">
                <label class="text-muted">Prix unitaire:</label>
                <p><?= number_format($ticket['prix_unitaire'], 2, ',', ' ') ?> FCFA</p>
              </div>
              <div class="info-group">
                <label class="text-muted">Montant à payer:</label>
                <p class="text-primary"><?= number_format($ticket['montant_paie'], 2, ',', ' ') ?> FCFA</p>
              </div>
              <div class="info-group">
                <label class="text-muted">Montant payé:</label>
                <p class="text-success"><?= number_format($ticket['montant_payer'] ?? 0, 2, ',', ' ') ?> FCFA</p>
              </div>
              <div class="info-group">
                <label class="text-muted">Reste à payer:</label>
                <p class="<?= ($ticket['montant_reste'] == 0) ? 'text-success' : 'text-danger' ?>">
                  <?= number_format($ticket['montant_reste'] ?? $ticket['montant_paie'], 2, ',', ' ') ?> FCFA
                </p>
              </div>
            </div>
          </div>
          <div class="border-top pt-3">
            <div class="info-group">
              <label class="text-muted">Créé par:</label>
              <p><?= $ticket['utilisateur_nom_complet'] ?></p>
            </div>
            <div class="info-group">
              <label class="text-muted">Date de création:</label>
              <p><?= date('d/m/Y', strtotime($ticket['created_at'])) ?></p>
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
        </div>
      </div>
    </div>
  </div>

  <style>
  .info-group {
    margin-bottom: 15px;
  }
  .info-group label {
    display: block;
    font-size: 0.9em;
    margin-bottom: 2px;
  }
  .info-group p {
    margin-bottom: 0;
  }
  .modal-header.bg-primary {
    background-color: #007bff !important;
  }

  .modal-header .close.text-white {
    color: #fff;
    opacity: 1;
  }

  .modal-header .close.text-white:hover {
    opacity: 0.75;
  }
  </style>
<?php endforeach; ?>

</body>

</html>

<script>
function getUrlParams() {
    const params = new URLSearchParams(window.location.search);
    const urlParams = {};
    
    // Récupérer tous les paramètres actuels
    for(const [key, value] of params.entries()) {
        if(value) urlParams[key] = value;
    }
    
    return urlParams;
}

function submitValidation(event, ticketId) {
    event.preventDefault();
    const form = document.getElementById('form-validation-' + ticketId);
    const formData = new FormData(form);

    $.ajax({
        url: 'valider_tickets.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                // Construire l'URL de redirection avec les paramètres actuels
                const params = new URLSearchParams(urlParams);
                window.location.href = 'tickets_attente.php?' + params.toString();
            } else {
                alert(response.message || 'Erreur lors de la validation du ticket');
            }
        },
        error: function(xhr, status, error) {
            console.error('Erreur:', error);
            console.error('Response:', xhr.responseText);
            alert('Erreur lors de la validation du ticket');
        }
    });

    return false;
}

// Pour la validation multiple
function validerTicketsSelectionnes() {
    const selectedTickets = [];
    $('.ticket-checkbox:checked').each(function() {
        selectedTickets.push($(this).val());
    });

    console.log('=== Validation des tickets ===');
    console.log('Tickets sélectionnés:', selectedTickets);
    console.log('Nombre total:', selectedTickets.length);

    if (selectedTickets.length === 0) {
        alert('Veuillez sélectionner au moins un ticket');
        return;
    }

    if (confirm('Voulez-vous vraiment valider les ' + selectedTickets.length + ' ticket(s) sélectionné(s) ?')) {
        console.log('=== Envoi de la requête ===');
        console.log('URL:', 'valider_tickets.php');
        console.log('Données:', { ticket_ids: selectedTickets });

        $.ajax({
            url: 'valider_tickets.php',
            method: 'POST',
            data: {
                ticket_ids: selectedTickets
            },
            success: function(response) {
                console.log('=== Réponse reçue ===');
                console.log('Réponse brute:', response);
                
                try {
                    const data = JSON.parse(response);
                    console.log('Données parsées:', data);
                    
                    if (data.success) {
                        alert('Les tickets ont été validés avec succès');
                        $('#modalResultatsRecherche').modal('hide');
                        location.reload();
                    } else {
                        alert(data.message || 'Erreur lors de la validation');
                    }
                } catch (e) {
                    console.error('=== Erreur de parsing ===');
                    console.error('Type:', e.name);
                    console.error('Message:', e.message);
                    console.error('Stack:', e.stack);
                    alert('Erreur lors du traitement de la réponse');
                }
            },
            error: function(xhr, status, error) {
                console.error('=== Erreur AJAX ===');
                console.error('Status:', status);
                console.error('Erreur:', error);
                console.error('Réponse:', xhr.responseText);
                alert('Erreur lors de la validation des tickets');
            }
        });
    }
}
</script>
<script>
function validerTicketsSelectionnes() {
    // Pour la validation en masse uniquement
    const selectedTickets = [];
    $('.ticket-checkbox:checked').each(function() {
        selectedTickets.push($(this).val());
    });

    if (selectedTickets.length === 0) {
        alert('Veuillez sélectionner au moins un ticket à valider');
        return;
    }

    $.ajax({
        url: 'valider_tickets.php',
        method: 'POST',
        data: { 
            ticket_ids: selectedTickets,
            is_mass_validation: true
        },
        success: function(response) {
            try {
                const data = typeof response === 'string' ? JSON.parse(response) : response;
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Erreur lors de la validation des tickets');
                }
            } catch (e) {
                console.error('Erreur:', e);
                alert('Erreur lors du traitement de la réponse');
            }
        },
        error: function(xhr, status, error) {
            console.error('Erreur:', error);
            console.error('Response:', xhr.responseText);  // Pour voir la réponse brute
            alert('Erreur lors de la validation des tickets: ' + error);
        }
    });
}

function submitValidation(event, ticketId) {
    event.preventDefault();
    const form = document.getElementById('form-validation-' + ticketId);
    const prix_unitaire = form.querySelector('[name="prix_unitaire"]').value;

    if (!prix_unitaire || prix_unitaire <= 0) {
        alert('Veuillez entrer un prix unitaire valide');
        return false;
    }

    // Validation d'un seul ticket
    $.ajax({
        url: 'valider_tickets.php',
        method: 'POST',
        data: {
            ticket_id: ticketId,
            prix_unitaire: prix_unitaire,
            is_mass_validation: false
        },
        success: function(response) {
            try {
                const data = typeof response === 'string' ? JSON.parse(response) : response;
                if (data.success) {
                    $(`#valider_ticket${ticketId}`).modal('hide');
                    window.location.reload();
                } else {
                    alert(data.message || 'Erreur lors de la validation du ticket');
                }
            } catch (e) {
                console.error('Erreur:', e);
                alert('Erreur lors du traitement de la réponse');
            }
        },
        error: function(xhr, error) {
            console.error('Erreur:', error);
            alert('Erreur lors de la validation du ticket');
        }
    });

    return false;
}
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialisation de tous les modals
    $('.modal').modal({
        keyboard: false,
        backdrop: 'static',
        show: false
    });

    // Gestionnaire spécifique pour le modal d'ajout
    $('#add-ticket').on('show.bs.modal', function (e) {
        console.log('Modal add-ticket en cours d\'ouverture');
    });
});
</script>
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

<!-- Modal Validation en Masse -->
<div class="modal fade" id="modalUsineTickets" tabindex="-1" role="dialog" aria-labelledby="modalUsineTicketsLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalUsineTicketsLabel">Validation en Masse des Tickets</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formValidationMasse">
                  <!--  <form action="valider_tickets_masse.php" method="POST">-->
                    <div class="row">
                        <!-- Sélection des agents -->
                        <div class="col-md-6 mb-3">
                            <label for="agents">Agents</label>
                            <select class="form-control select2-agents" id="agents" name="agents">
                                <?php
                                $agents = getAgents($conn);
                                foreach($agents as $agent) {
                                    echo '<option value="'.$agent['id_agent'].'">'.$agent['nom_agent'].' '.$agent['prenom_agent'].'</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <!-- Sélection des usines -->
                        <div class="col-md-6 mb-3">
                            <label for="usines">Usines</label>
                            <select class="form-control select2-usines" id="usines" name="usines">
                                <?php
                                $usines = getUsines($conn);
                                foreach($usines as $usine) {
                                    echo '<option value="'.$usine['id_usine'].'">'.$usine['nom_usine'].'</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <!-- Date début -->
                        <div class="col-md-6 mb-3">
                            <label for="date_debut">Date début</label>
                            <input type="date" class="form-control" id="fdate_debut" name="date_debut" required>
                        </div>
                        <!-- Date fin -->
                        <div class="col-md-6 mb-3">
                            <label for="date_fin">Date fin</label>
                            <input type="date" class="form-control" id="fdate_fin" name="date_fin" required>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="validerEnMasse()">Rechercher les tickets</button>
            </div>
        </div>
    </div>
</div>

<!-- Script pour l'initialisation des select2 et la validation en masse -->
<script>
// Fonctions de gestion du loader
function showPageLoader(message = 'Chargement des tickets...') {
    const loader = document.getElementById('pageLoader');
    const loadingText = loader.querySelector('.loading-text');
    if (loader) {
        loadingText.textContent = message;
        loader.classList.remove('hidden');
        loader.style.display = 'flex';
    }
}

function hidePageLoader() {
    const loader = document.getElementById('pageLoader');
    if (loader) {
        loader.classList.add('hidden');
        setTimeout(() => {
            loader.style.display = 'none';
        }, 300);
    }
}

// Le loader est déjà affiché par PHP, pas besoin de le réafficher

$(document).ready(function() {
    console.log('🚀 Page tickets_attente.php chargée');
    
    // Vérifier que jQuery et les plugins sont disponibles
    if (typeof $ === 'undefined') {
        console.error('❌ jQuery non disponible');
        hidePageLoader();
        return;
    }
    
    console.log('✅ jQuery disponible');
    console.log('📦 Select2 disponible:', typeof $.fn.select2 !== 'undefined');
    console.log('📦 DataTables disponible:', typeof $.fn.DataTable !== 'undefined');
    
    // Mettre à jour le message du loader
    showPageLoader('Configuration des filtres...');
    
    // Restaurer les filtres sauvegardés
    restoreFilters();

    // Initialisation des select2 avec vérification
    function initializeAllSelect2() {
        if (typeof $.fn.select2 !== 'undefined') {
            // Select2 pour les filtres principaux
            $('#agent_select, #usine_select').select2({
                theme: 'bootstrap4',
                placeholder: 'Sélectionner...',
                allowClear: true,
                width: '100%'
            });
            
            // Select2 pour les modals
            $('.select2-agents').select2({
                placeholder: 'Sélectionner des agents',
                language: 'fr',
                width: '100%',
                dropdownParent: $('#modalUsineTickets'),
                allowClear: true
            });

            $('.select2-usines').select2({
                placeholder: 'Sélectionner des usines',
                language: 'fr',
                width: '100%',
                dropdownParent: $('#modalUsineTickets'),
                allowClear: true
            });
            
            console.log('✅ Tous les Select2 initialisés avec succès');
        } else {
            console.log('⚠️ Select2 non disponible, nouvelle tentative...');
            setTimeout(initializeAllSelect2, 200);
        }
    }
    
    // Démarrer l'initialisation après un délai
    setTimeout(initializeAllSelect2, 300);

    // Reset form on modal close
    $('#modalUsineTickets').on('hidden.bs.modal', function () {
        $('#formValidationMasse')[0].reset();
        $('.select2-agents, .select2-usines').val(null).trigger('change');
    });
    
    // Ajouter des loaders aux boutons d'action
    $('.btn[onclick*="valider"], .btn[onclick*="rejeter"], .btn[onclick*="modifier"]').on('click', function() {
        const button = this;
        const action = button.textContent.toLowerCase();
        
        if (action.includes('valider')) {
            addButtonLoader(button, 'Validation...');
            showPageLoader('Validation des tickets...');
        } else if (action.includes('rejeter')) {
            addButtonLoader(button, 'Rejet...');
            showPageLoader('Rejet des tickets...');
        } else if (action.includes('modifier')) {
            addButtonLoader(button, 'Modification...');
            showPageLoader('Modification en cours...');
        }
        
        // Simuler un délai pour voir le loader (à remplacer par la vraie logique)
        setTimeout(() => {
            removeButtonLoader(button);
            hidePageLoader();
        }, 2000);
    });
    
    // Loader pour les formulaires de filtrage
    $('form').on('submit', function() {
        const submitButton = $(this).find('button[type="submit"], input[type="submit"]');
        if (submitButton.length > 0) {
            addButtonLoader(submitButton[0], 'Recherche...');
        }
        showPageLoader('Recherche des tickets...');
    });
    
    // Loader pour les liens de pagination
    $('.pagination a').on('click', function() {
        showPageLoader('Chargement de la page...');
    });

    // Le loader sera caché automatiquement par le script window.addEventListener('load')
    console.log('✅ jQuery et plugins initialisés');
});

function validerEnMasse() {
    const agent_id = $('#agents').val();
    const usine_id = $('#usines').val();
    const date_debut = $('#fdate_debut').val();
    const date_fin = $('#fdate_fin').val();

    // const date_debut = document.getElementById('fdate_debut').value;
    // const date_fin = document.getElementById('fdate_fin').value;

    // Afficher les valeurs sélectionnées
    console.log('=== Valeurs sélectionnées ===');
    console.log('Agent ID:', agent_id);
    console.log('Agent Nom:', $('#agents option:selected').text());
    console.log('Usine ID:', usine_id);
    console.log('Usine Nom:', $('#usines option:selected').text());
    console.log('Date début:', date_debut);
    console.log('Date fin:', date_fin);
    console.log('========================');

    // Validation des champs
    if (!agent_id || !usine_id || !date_debut || !date_fin) {
        alert('Veuillez remplir tous les champs');
        return;
    }

    // Rechercher les tickets
    $.ajax({
        url: 'rechercher_tickets.php',
        method: 'POST',
        data: {
            agent_id: agent_id,
            usine_id: usine_id,
            date_debut: date_debut,
            date_fin: date_fin
        },
        success: function(response) {
            try {
                console.log('=== Réponse du serveur ===');
                
                console.log('Données reçues:', response);
                const data = response;
                console.log('=== Réponse du serveur ===');
                console.log('Données reçues:', data);
                console.log('Nombre de tickets:', data.tickets ? data.tickets.length : 0);
                console.log('========================');

                if (data.success) {
                    // Remplir le tableau avec les résultats
                    let html = '';
                    data.tickets.forEach(ticket => {
                        html += `
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" class="ticket-checkbox" value="${ticket.id_ticket}">
                                </td>
                                <td>${ticket.numero_ticket}</td>
                                <td>${ticket.date_ticket}</td>
                                <td>${ticket.nom_agent} ${ticket.prenom_agent}</td>
                                <td>${ticket.nom_usine}</td>
                                <td>${ticket.prix_unitaire}</td>
                                <td>${ticket.montant_paie}</td>
                               
                            </tr>
                        `;
                    });
                    $('#resultsTableBody').html(html);
                    
                    // Fermer le modal de recherche et ouvrir celui des résultats
                    $('#modalUsineTickets').modal('hide');
                    $('#modalResultatsRecherche').modal('show');
                } else {
                    alert('Aucun ticket trouvé pour ces critères');
                }
            } catch (e) {
                console.error('=== Erreur ===');
                console.error('Type:', e.name);
                console.error('Message:', e.message);
                console.error('Stack:', e.stack);
                console.error('========================');
                alert('Erreur lors du traitement des résultats');
            }
        },
        error: function(xhr, status, error) {
            console.error('=== Erreur AJAX ===');
            console.error('Status:', status);
            console.error('Erreur:', error);
            console.error('Réponse:', xhr.responseText);
            console.error('========================');
            alert('Erreur lors de la recherche des tickets');
        }
    });
}
</script>

<!-- Modal Résultats de Recherche -->
<div class="modal fade" id="modalResultatsRecherche" tabindex="-1" role="dialog" aria-labelledby="modalResultatsRechercheLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalResultatsRechercheLabel">Résultats de la Recherche</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th width="50px">
                                    <input type="checkbox" id="checkAll" class="check-all">
                                </th>
                                <th>N° Ticket</th>
                                <th>Date</th>
                                <th>Agent</th>
                                <th>Usine</th>
                                <th>Prix unitaire</th>
                                <th>Montant</th>
                            </tr>
                        </thead>
                        <tbody id="resultsTableBody">
                            <!-- Les résultats seront insérés ici -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <div>
                    <span class="text-muted" id="selectedCount">0 ticket(s) sélectionné(s)</span>
                </div>
                <div>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                    <button type="button" class="btn btn-success" onclick="validerTicketsSelectionnes()">
                        <i class="fa fa-check"></i> Valider la sélection
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour saisir le prix unitaire -->
<div class="modal fade" id="prixUnitaireModal" tabindex="-1" aria-labelledby="prixUnitaireModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--success-gradient); color: white;">
                <h5 class="modal-title" id="prixUnitaireModalLabel">
                    <i class="fas fa-euro-sign me-2"></i>Saisir le Prix Unitaire
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <span id="ticketCountMessage">0 ticket(s) sélectionné(s)</span>
                </div>
                <form id="prixUnitaireForm">
                    <div class="mb-3">
                        <label for="prixUnitaire" class="form-label">
                            <i class="fas fa-money-bill-wave me-2"></i>Prix Unitaire (FCFA)
                        </label>
                        <input type="number" 
                               class="form-control" 
                               id="prixUnitaire" 
                               name="prix_unitaire" 
                               step="0.01" 
                               min="0" 
                               placeholder="Entrez le prix unitaire"
                               required>
                        <div class="form-text">Le prix sera appliqué à tous les tickets sélectionnés</div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="updateAllUsine" name="update_all_usine" value="1">
                            <label class="form-check-label" for="updateAllUsine">
                                <i class="fas fa-industry me-2 text-primary"></i>
                                <strong>Appliquer automatiquement ce prix à tous les tickets en attente de la même usine</strong>
                            </label>
                        </div>
                        <div class="form-text text-warning">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            Si cette option est cochée, tous les tickets non validés de la même usine recevront automatiquement ce prix unitaire après validation.
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Annuler
                </button>
                <button type="button" class="btn btn-success" onclick="confirmerValidationAvecPrix()">
                    <i class="fas fa-check me-2"></i>Valider avec ce prix
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Afficher le loader
    document.getElementById('loader').style.display = 'block';
    
    // Cacher le loader et afficher la table après un court délai
    setTimeout(function() {
        document.getElementById('loader').style.display = 'none';
        document.getElementById('example1').style.display = 'table';
    }, 1000); // 1 seconde de délai
    
    // Initialisation des autres fonctionnalités
});
</script>

<style>
/* Styles pour Select2 */
.select2-container--default .select2-selection--single {
    height: 38px;
    border: 1px solid #ced4da;
    border-radius: 4px;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 38px;
    color: #495057;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px;
}

.select2-dropdown {
    border: 1px solid #ced4da;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Styles pour les modals */
.modal-xl {
    max-width: 90%;
}

.modal-header.bg-primary {
    background-color: #007bff !important;
}

.modal-header .close.text-white {
    color: #fff;
    opacity: 1;
}

.modal-header .close.text-white:hover {
    opacity: 0.75;
}

/* Styles pour les tableaux */
.table-responsive {
    max-height: 60vh;
    overflow-y: auto;
}

/* Styles pour les boutons */
.btn-success {
    background-color: #28a745;
    border-color: #28a745;
}

.btn-success:hover {
    background-color: #218838;
    border-color: #1e7e34;
}

/* Styles pour les checkboxes */
.check-all {
    margin: 0;
    padding: 0;
}
</style>

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

/* Styles pour le conteneur de bloc */
.block-container {
    background-color: #d7dbdd;
    padding: 20px;
    border-radius: 5px;
    width: 100%;
    margin-bottom: 20px;
}

/* Styles pour la table */
.table-responsive {
    margin-top: 20px;
}

.table {
    background-color: white;
    font-size: 1.1rem;
}

.table td, .table th {
    padding: 1rem;
    font-size: 1.1rem;
    line-height: 1.4;
}

.table thead th {
    font-size: 1rem;
    font-weight: 600;
}

/* Styles pour les filtres actifs */
.active-filters {
    margin-top: 10px;
}

.badge {
    font-size: 1rem;
    padding: 10px 15px;
    margin-right: 8px;
    margin-bottom: 8px;
    border-radius: 20px;
}

.badge i {
    margin-left: 5px;
    cursor: pointer;
}

/* Styles pour les modals */
.modal-content {
    border-radius: 8px;
}

.modal-header {
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
}

/* CSS pour l'autocomplétion */
.autocomplete-container {
    position: relative;
}

.autocomplete-suggestions {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #ddd;
    border-top: none;
    border-radius: 0 0 8px 8px;
    max-height: 200px;
    overflow-y: auto;
    z-index: 1050;
    display: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.autocomplete-suggestion {
    padding: 12px 16px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
    transition: background-color 0.2s ease;
}

.autocomplete-suggestion:hover,
.autocomplete-suggestion.selected {
    background-color: #f8f9fa;
}

.autocomplete-suggestion:last-child {
    border-bottom: none;
}

.autocomplete-suggestion .agent-name {
    font-weight: 500;
    color: #333;
}

.autocomplete-loading {
    padding: 12px 16px;
    text-align: center;
    color: #666;
    font-style: italic;
}

.autocomplete-no-results {
    padding: 12px 16px;
    text-align: center;
    color: #999;
    font-style: italic;
}
</style>

<!-- JavaScript pour l'autocomplétion -->
<script>
$(document).ready(function() {
    // ===== AUTOCOMPLÉTION POUR LES AGENTS =====
    let searchAgentTimeout;
    let selectedAgentIndex = -1;
    
    function searchAgentsFilter(query) {
        if (query.length < 2) {
            $('#agent_suggestions_filter').hide().empty();
            return;
        }
        
        $('#agent_suggestions_filter').show().html('<div class="autocomplete-loading">Recherche en cours...</div>');
        
        $.ajax({
            url: '../api/search_agents.php',
            method: 'GET',
            data: { q: query },
            dataType: 'json',
            success: function(data) {
                displayAgentSuggestions(data);
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error, 'Status:', status, 'Response:', xhr.responseText);
                $('#agent_suggestions_filter').html('<div class="autocomplete-no-results">Erreur lors de la recherche: ' + error + '</div>');
            }
        });
    }
    
    function displayAgentSuggestions(agents) {
        const suggestionsDiv = $('#agent_suggestions_filter');
        
        if (agents.length === 0) {
            suggestionsDiv.html('<div class="autocomplete-no-results">Aucun résultat trouvé</div>');
            return;
        }
        
        let html = '';
        agents.forEach(function(agent, index) {
            html += `<div class="autocomplete-suggestion" data-id="${agent.id}" data-index="${index}">
                        <div class="agent-name">${agent.text}</div>
                     </div>`;
        });
        
        suggestionsDiv.html(html);
        selectedAgentIndex = -1;
    }
    
    $('#agent_search_filter').on('input', function() {
        const query = $(this).val().trim();
        $('#agent_id_filter').val('');
        selectedAgentIndex = -1;
        clearTimeout(searchAgentTimeout);
        searchAgentTimeout = setTimeout(function() {
            searchAgentsFilter(query);
        }, 300);
    });
    
    $('#agent_search_filter').on('keydown', function(e) {
        const suggestions = $('#agent_suggestions_filter .autocomplete-suggestion');
        if (suggestions.length === 0) return;
        
        switch(e.keyCode) {
            case 38: e.preventDefault(); selectedAgentIndex = selectedAgentIndex > 0 ? selectedAgentIndex - 1 : suggestions.length - 1; updateAgentSelection(); break;
            case 40: e.preventDefault(); selectedAgentIndex = selectedAgentIndex < suggestions.length - 1 ? selectedAgentIndex + 1 : 0; updateAgentSelection(); break;
            case 13: e.preventDefault(); if (selectedAgentIndex >= 0) { selectAgentSuggestion(suggestions.eq(selectedAgentIndex)); } break;
            case 27: $('#agent_suggestions_filter').hide(); selectedAgentIndex = -1; break;
        }
    });
    
    function updateAgentSelection() {
        $('#agent_suggestions_filter .autocomplete-suggestion').removeClass('selected');
        if (selectedAgentIndex >= 0) {
            $('#agent_suggestions_filter .autocomplete-suggestion').eq(selectedAgentIndex).addClass('selected');
        }
    }
    
    $(document).on('click', '#agent_suggestions_filter .autocomplete-suggestion', function() {
        selectAgentSuggestion($(this));
    });
    
    function selectAgentSuggestion($suggestion) {
        const agentId = $suggestion.data('id');
        const agentName = $suggestion.find('.agent-name').text();
        
        $('#agent_search_filter').val(agentName);
        $('#agent_id_filter').val(agentId);
        $('#agent_suggestions_filter').hide();
        selectedAgentIndex = -1;
    }
    
    // ===== AUTOCOMPLÉTION POUR LES USINES =====
    let searchUsineTimeout;
    let selectedUsineIndex = -1;
    
    function searchUsinesFilter(query) {
        if (query.length < 2) {
            $('#usine_suggestions_filter').hide().empty();
            return;
        }
        
        $('#usine_suggestions_filter').show().html('<div class="autocomplete-loading">Recherche en cours...</div>');
        
        $.ajax({
            url: '../api/search_usines.php',
            method: 'GET',
            data: { q: query },
            dataType: 'json',
            success: function(data) {
                if (data.debug) {
                    console.log('Debug info:', data);
                    $('#usine_suggestions_filter').html('<div class="autocomplete-no-results">Debug: ' + data.error + '</div>');
                } else {
                    displayUsineSuggestions(data);
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX:', error);
                $('#usine_suggestions_filter').html('<div class="autocomplete-no-results">Erreur lors de la recherche: ' + error + '</div>');
            }
        });
    }
    
    function displayUsineSuggestions(usines) {
        const suggestionsDiv = $('#usine_suggestions_filter');
        
        if (usines.length === 0) {
            suggestionsDiv.html('<div class="autocomplete-no-results">Aucun résultat trouvé</div>');
            return;
        }
        
        let html = '';
        usines.forEach(function(usine, index) {
            html += `<div class="autocomplete-suggestion" data-id="${usine.id}" data-index="${index}">
                        <div class="agent-name">${usine.text}</div>
                     </div>`;
        });
        
        suggestionsDiv.html(html);
        selectedUsineIndex = -1;
    }
    
    $('#usine_search_filter').on('input', function() {
        const query = $(this).val().trim();
        $('#usine_id_filter').val('');
        selectedUsineIndex = -1;
        clearTimeout(searchUsineTimeout);
        searchUsineTimeout = setTimeout(function() {
            searchUsinesFilter(query);
        }, 300);
    });
    
    $('#usine_search_filter').on('keydown', function(e) {
        const suggestions = $('#usine_suggestions_filter .autocomplete-suggestion');
        if (suggestions.length === 0) return;
        
        switch(e.keyCode) {
            case 38: e.preventDefault(); selectedUsineIndex = selectedUsineIndex > 0 ? selectedUsineIndex - 1 : suggestions.length - 1; updateUsineSelection(); break;
            case 40: e.preventDefault(); selectedUsineIndex = selectedUsineIndex < suggestions.length - 1 ? selectedUsineIndex + 1 : 0; updateUsineSelection(); break;
            case 13: e.preventDefault(); if (selectedUsineIndex >= 0) { selectUsineSuggestion(suggestions.eq(selectedUsineIndex)); } break;
            case 27: $('#usine_suggestions_filter').hide(); selectedUsineIndex = -1; break;
        }
    });
    
    function updateUsineSelection() {
        $('#usine_suggestions_filter .autocomplete-suggestion').removeClass('selected');
        if (selectedUsineIndex >= 0) {
            $('#usine_suggestions_filter .autocomplete-suggestion').eq(selectedUsineIndex).addClass('selected');
        }
    }
    
    $(document).on('click', '#usine_suggestions_filter .autocomplete-suggestion', function() {
        selectUsineSuggestion($(this));
    });
    
    function selectUsineSuggestion($suggestion) {
        const usineId = $suggestion.data('id');
        const usineName = $suggestion.find('.agent-name').text();
        
        $('#usine_search_filter').val(usineName);
        $('#usine_id_filter').val(usineId);
        $('#usine_suggestions_filter').hide();
        selectedUsineIndex = -1;
    }
    
    // Cacher les suggestions quand on clique ailleurs
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.autocomplete-container').length) {
            $('#agent_suggestions_filter').hide();
            $('#usine_suggestions_filter').hide();
            selectedAgentIndex = -1;
            selectedUsineIndex = -1;
        }
    });
    
    // Initialiser les valeurs si elles existent déjà
    <?php if (!empty($agent_id) && !empty($agents)): ?>
        <?php foreach($agents as $agent): ?>
            <?php if($agent['id_agent'] == $agent_id): ?>
                $('#agent_search_filter').val('<?= htmlspecialchars($agent['nom_complet_agent']) ?>');
                break;
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php if (!empty($usine_id) && !empty($usines)): ?>
        <?php foreach($usines as $usine): ?>
            <?php if($usine['id_usine'] == $usine_id): ?>
                $('#usine_search_filter').val('<?= htmlspecialchars($usine['nom_usine']) ?>');
                break;
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
    
    // Réinitialiser le formulaire quand le modal se ferme
    $('#prixUnitaireModal').on('hidden.bs.modal', function () {
        $('#prixUnitaireForm')[0].reset();
    });
});
</script>

<!-- Styles CSS pour les nouveaux éléments -->
<style>
/* Styles pour le loader de traitement */
.processing-loader-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    backdrop-filter: blur(5px);
}

.processing-loader-content {
    background: white;
    padding: 3rem;
    border-radius: 20px;
    text-align: center;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    max-width: 400px;
    width: 90%;
    animation: slideInUp 0.5s ease-out;
}

.processing-animation {
    margin-bottom: 2rem;
}

.processing-text h4 {
    font-weight: 600;
    margin-bottom: 1rem;
}

.progress {
    background-color: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
}

.progress-bar {
    transition: width 0.3s ease;
}

/* Styles pour le message de succès */
.success-message-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    backdrop-filter: blur(5px);
}

.success-message-content {
    background: white;
    padding: 3rem;
    border-radius: 20px;
    text-align: center;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    max-width: 450px;
    width: 90%;
    animation: bounceIn 0.6s ease-out;
}

.success-animation {
    margin-bottom: 2rem;
}

.success-checkmark {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: block;
    stroke-width: 2;
    stroke: #28a745;
    stroke-miterlimit: 10;
    margin: 0 auto 1rem;
    box-shadow: inset 0px 0px 0px #28a745;
    animation: fill 0.4s ease-in-out 0.4s forwards, scale 0.3s ease-in-out 0.9s both;
    position: relative;
}

.success-checkmark .check-icon {
    width: 56px;
    height: 56px;
    position: absolute;
    left: 12px;
    top: 12px;
    z-index: 1;
    transform: scale(0);
    animation: scale 0.3s ease-in-out 0.9s both;
}

.check-icon .icon-line {
    height: 2px;
    background: #28a745;
    display: block;
    border-radius: 2px;
    position: absolute;
    z-index: 10;
}

.check-icon .line-tip {
    top: 19px;
    left: 14px;
    width: 25px;
    transform: rotate(45deg);
    animation: icon-line-tip 0.75s;
}

.check-icon .line-long {
    top: 38px;
    right: 8px;
    width: 47px;
    transform: rotate(-45deg);
    animation: icon-line-long 0.75s;
}

.check-icon .icon-circle {
    top: -2px;
    left: -2px;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    position: absolute;
    border: 4px solid rgba(40, 167, 69, 0.2);
    z-index: 10;
}

.check-icon .icon-fix {
    top: 8px;
    width: 5px;
    left: 26px;
    z-index: 1;
    height: 85px;
    position: absolute;
    transform: rotate(-45deg);
}

/* Animations */
@keyframes slideInUp {
    from {
        transform: translateY(50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@keyframes bounceIn {
    0% {
        transform: scale(0.3);
        opacity: 0;
    }
    50% {
        transform: scale(1.05);
    }
    70% {
        transform: scale(0.9);
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

@keyframes fill {
    100% {
        box-shadow: inset 0px 0px 0px 30px #28a745;
    }
}

@keyframes scale {
    0%, 20% {
        transform: scale(0);
    }
    100% {
        transform: scale(1);
    }
}

@keyframes icon-line-tip {
    0% {
        width: 0;
        left: 1px;
        top: 19px;
    }
    54% {
        width: 0;
        left: 1px;
        top: 19px;
    }
    70% {
        width: 50px;
        left: -8px;
        top: 37px;
    }
    84% {
        width: 17px;
        left: 21px;
        top: 48px;
    }
    100% {
        width: 25px;
        left: 14px;
        top: 45px;
    }
}

@keyframes icon-line-long {
    0% {
        width: 0;
        right: 46px;
        top: 54px;
    }
    65% {
        width: 0;
        right: 46px;
        top: 54px;
    }
    84% {
        width: 55px;
        right: 0px;
        top: 35px;
    }
    100% {
        width: 47px;
        right: 8px;
        top: 38px;
    }
}

/* Amélioration du style de la checkbox */
.form-check-input:checked {
    background-color: #007bff;
    border-color: #007bff;
}

.form-check-label {
    cursor: pointer;
    font-size: 0.95rem;
}

.form-check-label strong {
    color: #495057;
}

.form-text.text-warning {
    font-size: 0.85rem;
    margin-top: 0.5rem;
}
</style>

<!-- Script pour cacher le loader à la fin du chargement -->
<script>
// Cacher le loader dès que la page est complètement chargée
window.addEventListener('load', function() {
    setTimeout(function() {
        const loader = document.getElementById('pageLoader');
        if (loader) {
            loader.classList.add('hidden');
            setTimeout(() => {
                loader.style.display = 'none';
            }, 300);
        }
        console.log('✅ Page tickets_attente.php complètement chargée');
    }, 500); // Petit délai pour voir l'animation
});

// Backup: cacher le loader après un délai maximum
setTimeout(function() {
    const loader = document.getElementById('pageLoader');
    if (loader && loader.style.display !== 'none') {
        loader.classList.add('hidden');
        setTimeout(() => {
            loader.style.display = 'none';
        }, 300);
        console.log('⚠️ Loader caché par timeout de sécurité');
    }
}, 5000); // 5 secondes maximum
</script>