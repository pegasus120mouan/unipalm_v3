<?php
require_once '../inc/functions/connexion.php';
require_once '../inc/functions/requete/requete_tickets.php';
require_once '../inc/functions/requete/requete_usines.php';
require_once '../inc/functions/requete/requete_chef_equipes.php';
require_once '../inc/functions/requete/requete_vehicules.php';
require_once '../inc/functions/requete/requete_agents.php';

// Initialisation des variables de pagination et de recherche
$limit = $_GET['limit'] ?? 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Récupérer les paramètres de recherche
$search_usine = $_GET['usine'] ?? null;
$search_date = $_GET['date_creation'] ?? null;
$search_chauffeur = $_GET['chauffeur'] ?? null;
$search_agent = $_GET['agent_id'] ?? null;
$search_numero_ticket = $_GET['numero_ticket'] ?? null;

// Récupérer les données (functions)
if ($search_usine || $search_date || $search_chauffeur || $search_agent || $search_numero_ticket) {
    $tickets = searchTickets($conn, $search_usine, $search_date, $search_chauffeur, $search_agent, $search_numero_ticket);
} else {
    $tickets = getTickets($conn);
}

// Vérifiez si des tickets existent avant de procéder
if (!empty($tickets)) {
    $total_tickets = count($tickets);
    $total_pages = ceil($total_tickets / $limit);
    $page = max(1, min($page, $total_pages));
    $offset = ($page - 1) * $limit;
    $tickets_list = array_slice($tickets, $offset, $limit);
} else {
    $tickets_list = [];
    $total_pages = 1;
}

// Récupérer les données pour les listes déroulantes
$usines = getUsines($conn);
$chefs_equipes = getChefEquipes($conn);
$vehicules = getVehicules($conn);
$agents = getAgents($conn);

include('header.php');
?>

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

<style>
/* ===== STYLES PROFESSIONNELS POUR TICKETS.PHP ===== */

/* Variables CSS pour cohérence */
:root {
    --primary-color: #2c3e50;
    --secondary-color: #3498db;
    --success-color: #27ae60;
    --warning-color: #f39c12;
    --danger-color: #e74c3c;
    --light-bg: #f8f9fa;
    --border-color: #dee2e6;
    --text-muted: #6c757d;
    --shadow-sm: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    --shadow-md: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    --border-radius: 0.375rem;
    --transition: all 0.3s ease;
}

/* Conteneur principal des actions */
.actions-container {
    background: linear-gradient(135deg, var(--light-bg) 0%, #ffffff 100%);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: var(--shadow-sm);
}

.actions-container .btn {
    margin: 0.25rem;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
    border-radius: var(--border-radius);
    transition: var(--transition);
    box-shadow: var(--shadow-sm);
}

.actions-container .btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

/* Amélioration du tableau */
.table-container {
    background: white;
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
    max-height: 70vh;
    overflow-y: auto;
    overflow-x: hidden;
}

#example1 {
    margin-bottom: 0;
    border-collapse: separate;
    border-spacing: 0;
}

#example1 thead th {
    background: linear-gradient(135deg, var(--primary-color) 0%, #34495e 100%);
    color: white;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.875rem;
    letter-spacing: 0.5px;
    padding: 1rem 0.5rem;
    border: none;
    position: sticky;
    top: 0;
    z-index: 10;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

#example1 tbody tr {
    transition: var(--transition);
    border-bottom: 1px solid #f1f3f4;
}

#example1 tbody tr:hover {
    background-color: #f8f9fa;
    transform: scale(1.01);
    box-shadow: var(--shadow-sm);
}

#example1 tbody td {
    padding: 1rem 0.5rem;
    vertical-align: middle;
    border-top: none;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 150px;
}

/* Badges et statuts améliorés */
.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-size: 0.875rem;
    font-weight: 500;
    text-align: center;
    min-width: 120px;
    display: inline-block;
    box-shadow: var(--shadow-sm);
}

.status-pending {
    background: linear-gradient(135deg, #ffeaa7 0%, #fdcb6e 100%);
    color: #2d3436;
}

.status-validated {
    background: linear-gradient(135deg, #81ecec 0%, #00cec9 100%);
    color: white;
}

.status-paid {
    background: linear-gradient(135deg, #a29bfe 0%, #6c5ce7 100%);
    color: white;
}

.status-unpaid {
    background: linear-gradient(135deg, #fab1a0 0%, #e17055 100%);
    color: white;
}

/* Boutons d'actions améliorés */
.action-buttons {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
}

.action-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition);
    box-shadow: var(--shadow-sm);
}

.action-btn:hover {
    transform: scale(1.1);
    box-shadow: var(--shadow-md);
}

.action-btn.edit {
    background: linear-gradient(135deg, var(--secondary-color) 0%, #74b9ff 100%);
    color: white;
}

.action-btn.delete {
    background: linear-gradient(135deg, var(--danger-color) 0%, #fd79a8 100%);
    color: white;
}

.action-btn:disabled {
    background: #95a5a6;
    cursor: not-allowed;
    transform: none;
}

/* Loader amélioré */
#loader {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-sm);
}

.loader-spinner {
    width: 50px;
    height: 50px;
    border: 4px solid var(--light-bg);
    border-top: 4px solid var(--secondary-color);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Pagination professionnelle */
.pagination-container {
    background: white;
    padding: 1.5rem;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
    margin-top: 1.5rem;
}

.pagination-link {
    padding: 0.75rem 1rem;
    margin: 0 0.25rem;
    background: white;
    color: var(--primary-color);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    text-decoration: none;
    transition: var(--transition);
    font-weight: 500;
}

.pagination-link:hover {
    background: var(--secondary-color);
    color: white;
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
}

/* Formulaires améliorés */
.form-group label {
    font-weight: 600;
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

.form-control {
    border: 2px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 0.75rem;
    transition: var(--transition);
    font-size: 1rem;
}

.form-control:focus {
    border-color: var(--secondary-color);
    box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
    outline: none;
}

/* Autocomplete amélioré */
.list {
    background: white;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-md);
    max-height: 250px;
    overflow-y: auto;
    z-index: 1050;
}

.list li {
    padding: 0.75rem 1rem;
    cursor: pointer;
    transition: var(--transition);
    border-bottom: 1px solid #f1f3f4;
}

.list li:hover {
    background: var(--light-bg);
    color: var(--secondary-color);
}

.list li:last-child {
    border-bottom: none;
}

/* Responsive amélioré */
@media (max-width: 768px) {
    .actions-container {
        padding: 1rem;
    }
    
    .actions-container .btn {
        width: 100%;
        margin: 0.25rem 0;
    }
    
    .table-responsive {
        border-radius: var(--border-radius);
        overflow: hidden;
    }
    
    #example1 thead {
        display: none;
    }
    
    #example1 tbody tr {
        display: block;
        margin-bottom: 1rem;
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-sm);
        padding: 1rem;
    }
    
    #example1 tbody td {
        display: block;
        text-align: left !important;
        padding: 0.5rem 0;
        border: none;
    }
    
    #example1 tbody td:before {
        content: attr(data-label) ": ";
        font-weight: 600;
        color: var(--primary-color);
        display: inline-block;
        width: 120px;
    }
}

/* Modales améliorées */
.modal-header {
    background: linear-gradient(135deg, var(--primary-color) 0%, #34495e 100%);
    color: white;
    border-radius: var(--border-radius) var(--border-radius) 0 0;
}

.modal-content {
    border: none;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-md);
}

.modal-footer .btn {
    border-radius: var(--border-radius);
    padding: 0.75rem 1.5rem;
    font-weight: 500;
}

/* Animations */
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

.fade-in-up {
    animation: fadeInUp 0.5s ease-out;
}

/* Utilitaires */
.text-gradient {
    background: linear-gradient(135deg, var(--secondary-color) 0%, var(--primary-color) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.card-hover {
    transition: var(--transition);
}

.card-hover:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-md);
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

<div class="row">
    <?php if (isset($_SESSION['warning'])): ?>
        <div class="col-12">
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <?= $_SESSION['warning'] ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </div>
        <?php unset($_SESSION['warning']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['popup'])): ?>
        <div class="col-12">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Ticket enregistré avec succès
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </div>
        <?php unset($_SESSION['popup']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['delete_pop'])): ?>
        <div class="col-12">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                Une erreur s'est produite
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </div>
        <?php unset($_SESSION['delete_pop']); ?>
    <?php endif; ?>

<!-- Section des boutons d'actions -->
<div class="actions-container fade-in-up">
    <div class="row">
        <div class="col-12">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <div class="d-flex flex-wrap">
                    <button type="button" class="btn btn-primary card-hover" data-toggle="modal" data-target="#add-ticket">
                        <i class="fas fa-plus mr-2"></i>Enregistrer un ticket
                    </button>
                    <button type="button" class="btn btn-success card-hover" data-toggle="modal" data-target="#search_ticket">
                        <i class="fas fa-search mr-2"></i>Rechercher un ticket
                    </button>
                    <button type="button" class="btn btn-info card-hover" data-toggle="modal" data-target="#print-bordereau">
                        <i class="fas fa-file-pdf mr-2"></i>Imprimer par usine
                    </button>
                    <button type="button" class="btn btn-warning card-hover" data-toggle="modal" data-target="#print-bordereau-agent">
                        <i class="fas fa-print mr-2"></i>Bordereau
                    </button>
                </div>
                <div class="d-flex flex-wrap mt-2 mt-md-0">
                    <button type="button" class="btn btn-dark card-hover" onclick="window.location.href='export_tickets.php'">
                        <i class="fas fa-download mr-2"></i>Exporter tous
                    </button>
                    <button type="button" class="btn btn-outline-primary card-hover" data-toggle="modal" data-target="#exportDateModal">
                        <i class="fas fa-calendar-alt mr-2"></i>Exporter période
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Conteneur du tableau -->
<div class="table-container fade-in-up">
    <!-- Loader amélioré -->
    <div id="loader" class="text-center">
        <div class="loader-spinner"></div>
        <h5 class="text-muted">Chargement des tickets...</h5>
    </div>
    <!-- Table qui sera initialement cachée -->
    <div class="table-responsive" style="overflow-x: hidden;">
        <table id="example1" class="table table-hover" style="display: none; width: 100%; table-layout: fixed;">

 <!-- <table style="max-height: 90vh !important; overflow-y: scroll !important" id="example1" class="table table-bordered table-striped">-->
    <thead>
      <tr>
        
        <th>Date ticket</th>
        <th>Numero Ticket</th>
        <th>usine</th>
        <th>Chargé de mission</th>
        <th>Vehicule</th>
        <th>Poids</th>
        <th>Ticket créé par</th>
        <th>Date Ajout</th>
        <th>Prix Unitaire</th>
        <th>Date validation</th>
        <th>Montant</th>
        <th>Date Paie</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($tickets_list as $ticket) : ?>
        <tr>
          
          <td><?= isset($ticket['date_ticket']) ? date('d/m/Y', strtotime($ticket['date_ticket'])) : '-' ?></td>
          <td><a href="#" data-toggle="modal" data-target="#ticketModal<?= $ticket['id_ticket'] ?>"><?= isset($ticket['numero_ticket']) ? $ticket['numero_ticket'] : '-' ?></a></td>
          <td><?= isset($ticket['nom_usine']) ? $ticket['nom_usine'] : '-' ?></td>
          <td><?= isset($ticket['nom_complet_agent']) ? $ticket['nom_complet_agent'] : '-' ?></td>
          <td><?= isset($ticket['matricule_vehicule']) ? $ticket['matricule_vehicule'] : '-' ?></td>
          <td><?= isset($ticket['poids']) ? $ticket['poids'] : '-' ?></td>

          <td><?= isset($ticket['utilisateur_nom_complet']) ? $ticket['utilisateur_nom_complet'] : '-' ?></td>
          <td><?= isset($ticket['created_at']) ? date('d/m/Y', strtotime($ticket['created_at'])) : '-' ?></td>

         <td data-label="Prix Unitaire">
            <?php if (!isset($ticket['prix_unitaire']) || $ticket['prix_unitaire'] === null || $ticket['prix_unitaire'] == 0.00): ?>
                <span class="status-badge status-pending">
                    <i class="fas fa-clock mr-1"></i>En Attente
                </span>
            <?php else: ?>
                <span class="status-badge status-validated">
                    <?= number_format($ticket['prix_unitaire'], 0, '', '') ?>
                </span>
            <?php endif; ?>
        </td>

       <td data-label="Date validation">
            <?php if (!isset($ticket['date_validation_boss']) || $ticket['date_validation_boss'] === null): ?>
                <span class="status-badge status-pending">
                    <i class="fas fa-hourglass-half mr-1"></i>En cours
                </span>
            <?php else: ?>
                <span class="status-badge status-validated">
                    <i class="fas fa-check mr-1"></i><?= date('d/m/Y', strtotime($ticket['date_validation_boss'])) ?>
                </span>
            <?php endif; ?>
       </td>


    <td data-label="Montant">
                <?php if (!isset($ticket['montant_paie']) || $ticket['montant_paie'] === null): ?>
            <span class="status-badge status-pending">
                <i class="fas fa-clock mr-1"></i>En attente de PU
            </span>
        <?php else: ?>
            <span class="status-badge status-paid">
                <?= number_format($ticket['montant_paie'], 0, '', '') ?>
            </span>
            <?php endif; ?>
          </td>


              <td data-label="Date Paie">
                <?php if (!isset($ticket['date_paie']) || $ticket['date_paie'] === null): ?>
            <span class="status-badge status-unpaid">
                <i class="fas fa-times mr-1"></i>Non payé
            </span>
        <?php else: ?>
            <span class="status-badge status-paid">
                <i class="fas fa-check mr-1"></i><?= date('d/m/Y', strtotime($ticket['date_paie'])) ?>
            </span>
            <?php endif; ?>
          </td>
          
  
          <td data-label="Actions" class="text-center">
            <div class="action-buttons">
                <?php if (!isset($ticket['date_paie']) || $ticket['date_paie'] === null): ?>
                    <button type="button" class="action-btn edit" data-toggle="modal" data-target="#editModalTicket<?= $ticket['id_ticket'] ?>" title="Modifier">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="action-btn delete" data-toggle="modal" data-target="#confirmDeleteModal" data-id="<?= $ticket['id_ticket'] ?>" title="Supprimer">
                        <i class="fas fa-trash"></i>
                    </button>
                <?php else: ?>
                    <span class="status-badge status-paid">
                        <i class="fas fa-lock mr-1"></i>Payé
                    </span>
                <?php endif; ?>
            </div>
          </td>
</a-->

<!-- Modale de confirmation -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" role="dialog" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteModalLabel">Confirmer la suppression</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fermer">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Êtes-vous sûr de vouloir supprimer ce ticket ?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Supprimer</a>
            </div>
        </div>
    </div>
</div>

          </td>

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
                <form action="tickets_update_numeros.php?id=<?= $ticket['id_ticket'] ?>" method="post">
                <div class="form-group">
                <label for="exampleInputEmail1">Date ticket</label>
                <input type="date" class="form-control" id="exampleInputEmail1" placeholder="date ticket" name="date_ticket" value="<?= isset($ticket['date_ticket']) ? $ticket['date_ticket'] : '' ?>"> 
              </div> 
                <div class="form-group">
                        <label for="prix_unitaire">Numéro du ticket</label>
                        <input type="text" class="form-control" id="numero_ticket" name="numero_ticket" value="<?= isset($ticket['numero_ticket']) ? $ticket['numero_ticket'] : '' ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Sauvegarder les modifications</button>
                </form>
            </div>
        </div>
    </div>
</div>

          


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
    </tbody>
        </table>
    </div>
</div>

<!-- Modals pour chaque ticket -->
<?php foreach ($tickets_list as $ticket) : ?>
<div class="modal fade" id="ticketModal<?= $ticket['id_ticket'] ?>" tabindex="-1" role="dialog" aria-labelledby="ticketModalLabel<?= $ticket['id_ticket'] ?>" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="ticketModalLabel<?= $ticket['id_ticket'] ?>">
          <i class="fas fa-ticket-alt"></i> Détails du Ticket #<?= $ticket['numero_ticket'] ?>
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row mb-3">
          <div class="col-md-6">
            <strong>Date du ticket:</strong><br>
            <?= isset($ticket['date_ticket']) ? date('d/m/Y', strtotime($ticket['date_ticket'])) : '-' ?>
          </div>
          <div class="col-md-6">
            <strong>Prix unitaire:</strong><br>
            <?= isset($ticket['prix_unitaire']) ? number_format($ticket['prix_unitaire'], 2, '.', ' ') : '-' ?>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <strong>Usine:</strong><br>
            <?= isset($ticket['nom_usine']) ? $ticket['nom_usine'] : '-' ?>
          </div>
          <div class="col-md-6">
            <strong>Montant à payer:</strong><br>
            <?php if(isset($ticket['montant_paie'])): ?>
              <span class="text-primary"><?= number_format($ticket['montant_paie'], 2, '.', ' ') ?></span>
            <?php else: ?>
              -
            <?php endif; ?>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <strong>Agent:</strong><br>
            <?= isset($ticket['nom_complet_agent']) ? $ticket['nom_complet_agent'] : '-' ?>
          </div>
          <div class="col-md-6">
            <strong>Montant payé:</strong><br>
            <?= isset($ticket['montant_paye']) ? number_format($ticket['montant_paye'], 2, '.', ' ') : '-' ?>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <strong>Véhicule:</strong><br>
            <?= isset($ticket['matricule_vehicule']) ? $ticket['matricule_vehicule'] : '-' ?>
          </div>
          <div class="col-md-6">
            <strong>Reste à payer:</strong><br>
            <?= isset($ticket['reste_a_payer']) ? number_format($ticket['reste_a_payer'], 2, '.', ' ') : '-' ?>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <strong>Poids ticket:</strong><br>
            <?= isset($ticket['poids']) ? $ticket['poids'] . ' kg' : '-' ?>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <strong>Créé par:</strong><br>
            <?= isset($ticket['utilisateur_nom_complet']) ? $ticket['utilisateur_nom_complet'] : '-' ?>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6">
            <strong>Date de création:</strong><br>
            <?= isset($ticket['created_at']) ? date('d/m/Y', strtotime($ticket['created_at'])) : '-' ?>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
      </div>
    </div>
  </div>
</div>
<?php endforeach; ?>

<!-- Pagination Ultra-Professionnelle -->
<div class="pagination-container-pro fade-in-up">
    <div class="pagination-wrapper">
        <!-- Navigation des pages -->
        <div class="pagination-nav">
            <?php if($page > 1): ?>
                <a href="?page=1<?= isset($_GET['usine']) ? '&usine='.$_GET['usine'] : '' ?><?= isset($_GET['date_creation']) ? '&date_creation='.$_GET['date_creation'] : '' ?><?= isset($_GET['chauffeur']) ? '&chauffeur='.$_GET['chauffeur'] : '' ?><?= isset($_GET['agent_id']) ? '&agent_id='.$_GET['agent_id'] : '' ?><?= isset($_GET['numero_ticket']) ? '&numero_ticket='.$_GET['numero_ticket'] : '' ?>" class="pagination-btn pagination-btn-first" title="Première page">
                    <i class="fas fa-angle-double-left"></i>
                </a>
                <a href="?page=<?= $page - 1 ?><?= isset($_GET['usine']) ? '&usine='.$_GET['usine'] : '' ?><?= isset($_GET['date_creation']) ? '&date_creation='.$_GET['date_creation'] : '' ?><?= isset($_GET['chauffeur']) ? '&chauffeur='.$_GET['chauffeur'] : '' ?><?= isset($_GET['agent_id']) ? '&agent_id='.$_GET['agent_id'] : '' ?><?= isset($_GET['numero_ticket']) ? '&numero_ticket='.$_GET['numero_ticket'] : '' ?>" class="pagination-btn pagination-btn-prev" title="Page précédente">
                    <i class="fas fa-angle-left"></i>
                </a>
            <?php else: ?>
                <span class="pagination-btn pagination-btn-disabled">
                    <i class="fas fa-angle-double-left"></i>
                </span>
                <span class="pagination-btn pagination-btn-disabled">
                    <i class="fas fa-angle-left"></i>
                </span>
            <?php endif; ?>

            <!-- Indicateur de page actuelle -->
            <div class="pagination-info">
                <span class="current-page"><?= $page ?></span>
                <span class="page-separator">/</span>
                <span class="total-pages"><?= $total_pages ?></span>
            </div>

            <?php if($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 ?><?= isset($_GET['usine']) ? '&usine='.$_GET['usine'] : '' ?><?= isset($_GET['date_creation']) ? '&date_creation='.$_GET['date_creation'] : '' ?><?= isset($_GET['chauffeur']) ? '&chauffeur='.$_GET['chauffeur'] : '' ?><?= isset($_GET['agent_id']) ? '&agent_id='.$_GET['agent_id'] : '' ?><?= isset($_GET['numero_ticket']) ? '&numero_ticket='.$_GET['numero_ticket'] : '' ?>" class="pagination-btn pagination-btn-next" title="Page suivante">
                    <i class="fas fa-angle-right"></i>
                </a>
                <a href="?page=<?= $total_pages ?><?= isset($_GET['usine']) ? '&usine='.$_GET['usine'] : '' ?><?= isset($_GET['date_creation']) ? '&date_creation='.$_GET['date_creation'] : '' ?><?= isset($_GET['chauffeur']) ? '&chauffeur='.$_GET['chauffeur'] : '' ?><?= isset($_GET['agent_id']) ? '&agent_id='.$_GET['agent_id'] : '' ?><?= isset($_GET['numero_ticket']) ? '&numero_ticket='.$_GET['numero_ticket'] : '' ?>" class="pagination-btn pagination-btn-last" title="Dernière page">
                    <i class="fas fa-angle-double-right"></i>
                </a>
            <?php else: ?>
                <span class="pagination-btn pagination-btn-disabled">
                    <i class="fas fa-angle-right"></i>
                </span>
                <span class="pagination-btn pagination-btn-disabled">
                    <i class="fas fa-angle-double-right"></i>
                </span>
            <?php endif; ?>
        </div>

        <!-- Contrôle du nombre d'éléments par page -->
        <div class="items-per-page-container">
            <form action="" method="get" class="items-per-page-form-pro">
                <?php if(isset($_GET['usine'])): ?>
                    <input type="hidden" name="usine" value="<?= htmlspecialchars($_GET['usine']) ?>">
                <?php endif; ?>
                <?php if(isset($_GET['date_creation'])): ?>
                    <input type="hidden" name="date_creation" value="<?= htmlspecialchars($_GET['date_creation']) ?>">
                <?php endif; ?>
                <?php if(isset($_GET['chauffeur'])): ?>
                    <input type="hidden" name="chauffeur" value="<?= htmlspecialchars($_GET['chauffeur']) ?>">
                <?php endif; ?>
                <?php if(isset($_GET['agent_id'])): ?>
                    <input type="hidden" name="agent_id" value="<?= htmlspecialchars($_GET['agent_id']) ?>">
                <?php endif; ?>
                <?php if(isset($_GET['numero_ticket'])): ?>
                    <input type="hidden" name="numero_ticket" value="<?= htmlspecialchars($_GET['numero_ticket']) ?>">
                <?php endif; ?>
                
                <div class="items-control-group">
                    <label for="limit" class="items-label">
                        <i class="fas fa-list-ol"></i>
                        Afficher
                    </label>
                    <select name="limit" id="limit" class="items-select">
                        <option value="5" <?= $limit == 5 ? 'selected' : '' ?>>5 éléments</option>
                        <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10 éléments</option>
                        <option value="15" <?= $limit == 15 ? 'selected' : '' ?>>15 éléments</option>
                        <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25 éléments</option>
                        <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50 éléments</option>
                    </select>
                    <button type="submit" class="items-submit-btn">
                        <i class="fas fa-check"></i>
                        Appliquer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Informations sur les résultats -->
    <div class="pagination-stats">
        <div class="stats-info">
            <i class="fas fa-info-circle"></i>
            Affichage de <?= ($page - 1) * $limit + 1 ?> à <?= min($page * $limit, $total_tickets ?? 0) ?> sur <?= $total_tickets ?? 0 ?> ticket(s)
        </div>
    </div>
</div>

<style>
/* ===== STYLES POUR LA PAGINATION ULTRA-PROFESSIONNELLE ===== */

.pagination-container-pro {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border: 1px solid rgba(52, 152, 219, 0.2);
    border-radius: 20px;
    padding: 2rem;
    margin-top: 2rem;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    position: relative;
    overflow: hidden;
}

.pagination-container-pro::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(52, 152, 219, 0.05) 0%, rgba(155, 89, 182, 0.05) 100%);
    border-radius: 20px;
    z-index: 0;
}

.pagination-wrapper {
    position: relative;
    z-index: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 2rem;
}

/* Navigation des pages */
.pagination-nav {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.pagination-btn {
    width: 45px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.pagination-btn:not(.pagination-btn-disabled) {
    background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
}

.pagination-btn:not(.pagination-btn-disabled):hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(52, 152, 219, 0.4);
    background: linear-gradient(135deg, #2980b9 0%, #1f4e79 100%);
}

.pagination-btn:not(.pagination-btn-disabled):active {
    transform: translateY(-1px);
}

.pagination-btn-disabled {
    background: #bdc3c7;
    color: #7f8c8d;
    cursor: not-allowed;
    box-shadow: none;
}

.pagination-btn-first,
.pagination-btn-last {
    background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
    box-shadow: 0 4px 15px rgba(155, 89, 182, 0.3);
}

.pagination-btn-first:hover,
.pagination-btn-last:hover {
    background: linear-gradient(135deg, #8e44ad 0%, #6a1b9a 100%);
    box-shadow: 0 8px 25px rgba(155, 89, 182, 0.4);
}

/* Indicateur de page */
.pagination-info {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 25px;
    margin: 0 1rem;
    font-weight: 700;
    font-size: 1.1rem;
    box-shadow: 0 6px 20px rgba(44, 62, 80, 0.3);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    min-width: 80px;
    justify-content: center;
}

.current-page {
    color: #3498db;
    font-size: 1.2rem;
}

.page-separator {
    opacity: 0.7;
    margin: 0 0.25rem;
}

.total-pages {
    opacity: 0.9;
}

/* Contrôle des éléments par page */
.items-per-page-container {
    display: flex;
    align-items: center;
}

.items-control-group {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: white;
    padding: 0.75rem 1.5rem;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    border: 2px solid rgba(52, 152, 219, 0.1);
}

.items-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: #2c3e50;
    margin: 0;
    white-space: nowrap;
}

.items-label i {
    color: #3498db;
}

.items-select {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 0.5rem 1rem;
    font-weight: 500;
    background: white;
    color: #2c3e50;
    min-width: 130px;
    transition: all 0.3s ease;
}

.items-select:focus {
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
    outline: none;
}

.items-submit-btn {
    background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
    color: white;
    border: none;
    border-radius: 10px;
    padding: 0.5rem 1.25rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);
}

.items-submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(39, 174, 96, 0.4);
    background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
}

/* Statistiques */
.pagination-stats {
    margin-top: 1.5rem;
    text-align: center;
    position: relative;
    z-index: 1;
}

.stats-info {
    background: linear-gradient(135deg, rgba(52, 152, 219, 0.1) 0%, rgba(155, 89, 182, 0.1) 100%);
    color: #2c3e50;
    padding: 0.75rem 1.5rem;
    border-radius: 15px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    border: 1px solid rgba(52, 152, 219, 0.2);
}

.stats-info i {
    color: #3498db;
}

/* Responsive */
@media (max-width: 768px) {
    .pagination-wrapper {
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .pagination-nav {
        order: 2;
    }
    
    .items-per-page-container {
        order: 1;
        width: 100%;
    }
    
    .items-control-group {
        flex-wrap: wrap;
        justify-content: center;
        gap: 0.75rem;
    }
    
    .pagination-info {
        margin: 0;
        font-size: 1rem;
    }
    
    .pagination-btn {
        width: 40px;
        height: 40px;
    }
}

@media (max-width: 480px) {
    .pagination-container-pro {
        padding: 1.5rem;
    }
    
    .items-control-group {
        flex-direction: column;
        text-align: center;
    }
    
    .items-select {
        min-width: 100%;
    }
}
</style>

  <div class="modal fade" id="add-ticket" tabindex="-1" role="dialog" aria-labelledby="addTicketModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title" id="addTicketModalLabel">Enregistrer un ticket</h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
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
                <label for="input" class="font-weight-bold mb-2">Sélection Usine</label>
                <div class="position-relative">
                    <input type="text" class="form-control" id="input" placeholder="Sélectionner une usine" autocomplete="off">
                    <input type="hidden" name="id_usine" id="usine_id">
                    <ul class="list shadow-sm"></ul>
                </div>
              </div>

              <div class="form-group">
                <label for="input" class="font-weight-bold mb-2">Sélectionner un chargé de mission</label>
                <div class="position-relative">
                    <input type="text" class="form-control" id="input_agent" placeholder="Sélectionner un chargé de mission" autocomplete="off">
                    <input type="hidden" name="id_agent" id="agent_id">
                    <ul class="list shadow-sm"></ul>
                </div>
              </div>

              <div class="form-group">
                <label for="input" class="font-weight-bold mb-2">Sélection véhicule</label>
                <div class="position-relative">
                    <input type="text" class="form-control" id="input_vehicule" placeholder="Sélectionner un véhicule" autocomplete="off">
                    <input type="hidden" name="vehicule_id" id="vehicule_id">
                    <ul class="list shadow-sm"></ul>
                </div>
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

  <div class="modal fade" id="print-bordereau">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title">
            <i class="fas fa-file-pdf"></i> Impression des tickets par usine
          </h5>
          <button type="button" class="close text-white" data-dismiss="modal">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form action="print_tickets_usine.php" method="POST" target="_blank">
          <div class="modal-body">
            <div class="form-group">
              <label for="usine_search">Sélectionner une usine</label>
              <div class="autocomplete-container">
                <input type="text" 
                       class="form-control" 
                       id="usine_search" 
                       placeholder="Tapez le nom de l'usine..."
                       autocomplete="off"
                       required>
                <input type="hidden" name="id_usine" id="id_usine" required>
                <div id="usine_suggestions" class="autocomplete-suggestions"></div>
              </div>
            </div>
            <div class="form-group">
              <label for="date_debut">Date début</label>
              <input type="date" class="form-control" name="date_debut" id="date_debut" required>
            </div>
            <div class="form-group">
              <label for="date_fin">Date fin</label>
              <input type="date" class="form-control" name="date_fin" id="date_fin" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
            <button type="submit" class="btn btn-success">
              <i class="fas fa-file-pdf"></i> Générer PDF
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal pour impression bordereau par agent -->
<div class="modal fade" id="print-bordereau-agent">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Impression bordereau</h4>
            </div>
            <div class="modal-body">
                <form class="forms-sample" method="post" action="print_bordereau.php" target="_blank">
                    <div class="card-body">
                        <div class="form-group">
                            <label for="agent_search_print">Chargé de Mission</label>
                            <div class="autocomplete-container">
                                <input type="text" 
                                       class="form-control" 
                                       id="agent_search_print" 
                                       placeholder="Tapez le nom du chargé de mission..."
                                       autocomplete="off"
                                       required>
                                <input type="hidden" name="id_agent" id="id_agent_print" required>
                                <div id="agent_suggestions_print" class="autocomplete-suggestions"></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="date_debut">Date de debut</label>
                            <input type="date" class="form-control" id="date_debut" name="date_debut" required>
                        </div>
                        <div class="form-group">
                            <label for="date_fin">Date Fin</label>
                            <input type="date" class="form-control" id="date_fin" name="date_fin" required>
                        </div>

                        <button type="submit" class="btn btn-primary mr-2" name="saveCommande">Imprimer</button>
                        <button type="button" class="btn btn-light" data-dismiss="modal">Annuler</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Recherche par tickets-->
<div class="modal fade" id="search_ticket">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-search mr-2"></i>Rechercher un ticket
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-column">
                     <button type="button" class="btn btn-primary btn-block mb-3" data-toggle="modal" data-target="#searchByTicketModal" data-dismiss="modal">
                        <i class="fas fa-user-tie mr-2"></i>Recherche par numero de ticket
                    </button>
                    <button type="button" class="btn btn-primary btn-block mb-3" data-toggle="modal" data-target="#searchByAgentModal" data-dismiss="modal">
                        <i class="fas fa-user-tie mr-2"></i>Recherche par chargé de Mission
                    </button>
                    
                    <button type="button" class="btn btn-primary btn-block mb-3" data-toggle="modal" data-target="#searchByUsineModal" data-dismiss="modal">
                        <i class="fas fa-industry mr-2"></i>Recherche par Usine
                    </button>
                    
                    <button type="button" class="btn btn-primary btn-block mb-3" data-toggle="modal" data-target="#searchByDateModal" data-dismiss="modal">
                        <i class="fas fa-calendar-alt mr-2"></i>Recherche par Date
                    </button>

                    <button type="button" class="btn btn-primary btn-block mb-3" data-toggle="modal" data-target="#searchByBetweendateModal" data-dismiss="modal">
                        <i class="fas fa-calendar-alt mr-2"></i>Recherche entre 2 dates
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
                                    <?= $agent['nom_complet_agent'] ?>
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

<!-- Modal Recherche par Date -->
<div class="modal fade" id="exportDateModal" tabindex="-1" role="dialog" aria-labelledby="searchByDateModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="searchByDateModalLabel">
                    <i class="fas fa-calendar-alt mr-2"></i>Exporter Tickets sur une période
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="exportDateForm" method="get" action="export_tickets_periode.php" target="_blank">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="date_debut" class="font-weight-bold mb-2">Date de début</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                            </div>
                            <input type="date" class="form-control custom-input" id="date_debut" name="date_debut" required 
                                   style="padding: 0.5rem; border: 1px solid #ced4da; border-radius: 0 0.25rem 0.25rem 0;">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="date_fin" class="font-weight-bold mb-2">Date fin</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                            </div>
                            <input type="date" class="form-control custom-input" id="date_fin" name="date_fin" required
                                   style="padding: 0.5rem; border: 1px solid #ced4da; border-radius: 0 0.25rem 0.25rem 0;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-2"></i>Annuler
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-file-export mr-2"></i>Exporter
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Recherche par Date -->
<div class="modal fade" id="searchByBetweendateModal" tabindex="-1" role="dialog" aria-labelledby="searchByDateModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="searchByBetweendateModalLabel">
                    <i class="fas fa-calendar-alt mr-2"></i>Recherche entre 2 dates
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="searchByBetweendateForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="date_debut">Sélectionner date Début</label>
                        <input type="date" class="form-control" id="date_debut" name="date_debut" placeholder="date debut" required>
                    </div>
                    <div class="form-group">
                        <label for="date_fin">Sélectionner date de Fin</label>
                        <input type="date" class="form-control" id="date_fin" name="date_fin" placeholder="date fin" required>
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

<!-- Modal Recherche par Numero de Ticket -->
<div class="modal fade" id="searchByTicketModal" tabindex="-1" role="dialog" aria-labelledby="searchByTicketModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="searchByTicketModalLabel">
                    <i class="fas fa-ticket-alt mr-2"></i>Recherche par Numéro de Ticket
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="searchByTicketForm" action="tickets.php" method="GET">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="numero_ticket">Numéro de Ticket</label>
                        <input type="text" class="form-control" id="numero_ticket" name="numero_ticket" required placeholder="Entrez le numéro du ticket">
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
document.addEventListener('DOMContentLoaded', function() {
    // Afficher le loader
    document.getElementById('loader').style.display = 'block';
    
    // Cacher le loader et afficher la table après un court délai
    setTimeout(function() {
        document.getElementById('loader').style.display = 'none';
        document.getElementById('example1').style.display = 'table';
    }, 1000); // 1 seconde de délai
    
    // Initialisation des modals
    $('.modal').modal({
        keyboard: false,
        backdrop: 'static',
        show: false
    });

    // Gestion de la suppression
    $('.delete').click(function(e) {
        e.preventDefault();
        var ticketId = $(this).data('id');
        $('#confirmDeleteBtn').attr('href', 'traitement_tickets.php?action=delete&id=' + ticketId);
        $('#confirmDeleteModal').modal('show');
    });

    // Gestion du clic sur le bouton de confirmation de suppression
    $('#confirmDeleteBtn').click(function(e) {
        e.preventDefault();
        var deleteUrl = $(this).attr('href');
        
        // Fermer le modal
        $('#confirmDeleteModal').modal('hide');
        
        // Rediriger vers l'URL de suppression
        window.location.href = deleteUrl;
    });

    // Configuration pour la sélection d'usine
    setupAutoComplete({
        inputId: 'input',
        hiddenInputId: 'usine_id',
        listSelector: '#usine-list',
        apiUrl: '../inc/functions/requete/api_requete_usines.php',
        nameField: 'nom_usine',
        idField: 'id_usine'
    });

    // Configuration pour la sélection d'agent
    setupAutoComplete({
        inputId: 'input_agent',
        hiddenInputId: 'agent_id',
        listSelector: '#agent-list',
        apiUrl: '../inc/functions/requete/api_requete_agents.php',
        nameField: 'nom_complet_agent',
        idField: 'id_agent'
    });

    // Configuration pour la sélection de véhicule
    setupAutoComplete({
        inputId: 'input_vehicule',
        hiddenInputId: 'vehicule_id',
        listSelector: '#vehicule-list',
        apiUrl: '../inc/functions/requete/api_requete_vehicules.php',
        nameField: 'matricule_vehicule',
        idField: 'vehicules_id'
    });

    function setupAutoComplete(config) {
        const input = document.getElementById(config.inputId);
        const hiddenInput = document.getElementById(config.hiddenInputId);
        const list = input.parentElement.querySelector('.list');
        let data = [];

        // Récupération des données
        fetch(config.apiUrl)
            .then(response => response.json())
            .then(result => {
                if (result.success && result.data.length > 0) {
                    data = result.data;
                } else {
                    console.error('Aucune donnée trouvée');
                }
            })
            .catch(error => console.error('Erreur:', error));

        function showSuggestions() {
            const inputValue = input.value.toLowerCase();
            list.innerHTML = '';
            list.style.display = 'none';

            if (!inputValue) {
                hiddenInput.value = '';
                return;
            }

            const matchingItems = data.filter(item => 
                item[config.nameField].toLowerCase().includes(inputValue)
            );

            if (matchingItems.length > 0) {
                list.style.display = 'block';
                matchingItems.forEach(item => {
                    const li = document.createElement('li');
                    const name = item[config.nameField];
                    const index = name.toLowerCase().indexOf(inputValue);
                    const avant = name.substring(0, index);
                    const match = name.substring(index, index + inputValue.length);
                    const apres = name.substring(index + inputValue.length);

                    li.innerHTML = avant + '<strong>' + match + '</strong>' + apres;
                    
                    li.addEventListener('click', () => {
                        input.value = name;
                        hiddenInput.value = item[config.idField];
                        list.style.display = 'none';
                    });

                    list.appendChild(li);
                });
            }
        }

        input.addEventListener('input', showSuggestions);
        input.addEventListener('focus', showSuggestions);

        // Fermer la liste si on clique ailleurs
        document.addEventListener('click', (e) => {
            if (e.target !== input) {
                list.style.display = 'none';
            }
        });

        // Empêcher la fermeture lors du clic sur la liste
        list.addEventListener('click', (e) => {
            e.stopPropagation();
        });

        // Réinitialiser à la fermeture du modal
        $('#add-ticket').on('hidden.bs.modal', function () {
            input.value = '';
            hiddenInput.value = '';
            list.style.display = 'none';
        });
    }

    // Gestion du formulaire de recherche par numéro de ticket
    document.getElementById('searchByTicketForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const numeroTicket = this.querySelector('[name="numero_ticket"]').value;
        window.location.href = 'http://angenor.test/pages/tickets.php?numero_ticket=' + encodeURIComponent(numeroTicket);
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion des notifications
    <?php if (isset($_SESSION['success_modal'])): ?>
        $('#successModal').modal('show');
        var audio = new Audio("../inc/sons/notification.mp3");
        audio.volume = 1.0;
        audio.play().catch((error) => {
            console.error('Erreur de lecture audio :', error);
        });
        <?php 
        unset($_SESSION['success_modal']);
        unset($_SESSION['prix_unitaire']);
    endif; ?>

    <?php if (isset($_SESSION['warning'])): ?>
        $('#warningModal').modal('show');
        <?php unset($_SESSION['warning']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['delete_pop'])): ?>
        $('#errorModal').modal('show');
        <?php unset($_SESSION['delete_pop']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['popup']) && $_SESSION['popup'] == true): ?>
        var audio = new Audio("../inc/sons/notification.mp3");
        audio.volume = 1.0;
        audio.play().then(() => {
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
        <?php $_SESSION['popup'] = false; ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['delete_pop']) && $_SESSION['delete_pop'] == true): ?>
        var Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });

        Toast.fire({
            icon: 'error',
            title: 'Action échouée.'
        });
        <?php $_SESSION['delete_pop'] = false; ?>
    <?php endif; ?>
});
</script>

<!-- CSS pour l'autocomplétion -->
<style>
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
    border-radius: 0 0 4px 4px;
    max-height: 200px;
    overflow-y: auto;
    z-index: 1050;
    display: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.autocomplete-suggestion {
    padding: 10px 15px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
    transition: background-color 0.2s;
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
    padding: 10px 15px;
    text-align: center;
    color: #666;
    font-style: italic;
}

.autocomplete-no-results {
    padding: 10px 15px;
    text-align: center;
    color: #999;
    font-style: italic;
}
</style>

<!-- JavaScript pour l'autocomplétion -->
<script>
$(document).ready(function() {
    // ===== AUTOCOMPLÉTION POUR LES AGENTS =====
    let searchTimeout;
    let selectedIndex = -1;
    
    // Fonction pour effectuer la recherche
    function searchAgents(query) {
        if (query.length < 2) {
            $('#agent_suggestions_print').hide().empty();
            return;
        }
        
        // Afficher le loader
        $('#agent_suggestions_print').show().html('<div class="autocomplete-loading">Recherche en cours...</div>');
        
        $.ajax({
            url: '../api/search_agents.php',
            method: 'GET',
            data: { q: query },
            dataType: 'json',
            success: function(data) {
                displaySuggestions(data);
            },
            error: function() {
                $('#agent_suggestions_print').html('<div class="autocomplete-no-results">Erreur lors de la recherche</div>');
            }
        });
    }
    
    // Fonction pour afficher les suggestions
    function displaySuggestions(agents) {
        const suggestionsDiv = $('#agent_suggestions_print');
        
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
        selectedIndex = -1;
    }
    
    // Événement de saisie dans le champ de recherche
    $('#agent_search_print').on('input', function() {
        const query = $(this).val().trim();
        
        // Réinitialiser la sélection
        $('#id_agent_print').val('');
        selectedIndex = -1;
        
        // Annuler la recherche précédente
        clearTimeout(searchTimeout);
        
        // Lancer une nouvelle recherche après un délai
        searchTimeout = setTimeout(function() {
            searchAgents(query);
        }, 300);
    });
    
    // Gestion des touches du clavier
    $('#agent_search_print').on('keydown', function(e) {
        const suggestions = $('.autocomplete-suggestion');
        
        if (suggestions.length === 0) return;
        
        switch(e.keyCode) {
            case 38: // Flèche haut
                e.preventDefault();
                selectedIndex = selectedIndex > 0 ? selectedIndex - 1 : suggestions.length - 1;
                updateSelection();
                break;
                
            case 40: // Flèche bas
                e.preventDefault();
                selectedIndex = selectedIndex < suggestions.length - 1 ? selectedIndex + 1 : 0;
                updateSelection();
                break;
                
            case 13: // Entrée
                e.preventDefault();
                if (selectedIndex >= 0) {
                    selectSuggestion(suggestions.eq(selectedIndex));
                }
                break;
                
            case 27: // Échap
                $('#agent_suggestions_print').hide();
                selectedIndex = -1;
                break;
        }
    });
    
    // Fonction pour mettre à jour la sélection visuelle
    function updateSelection() {
        $('.autocomplete-suggestion').removeClass('selected');
        if (selectedIndex >= 0) {
            $('.autocomplete-suggestion').eq(selectedIndex).addClass('selected');
        }
    }
    
    // Clic sur une suggestion
    $(document).on('click', '.autocomplete-suggestion', function() {
        selectSuggestion($(this));
    });
    
    // Fonction pour sélectionner une suggestion
    function selectSuggestion($suggestion) {
        const agentId = $suggestion.data('id');
        const agentName = $suggestion.find('.agent-name').text();
        
        $('#agent_search_print').val(agentName);
        $('#id_agent_print').val(agentId);
        $('#agent_suggestions_print').hide();
        selectedIndex = -1;
    }
    
    // Cacher les suggestions quand on clique ailleurs
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.autocomplete-container').length) {
            $('#agent_suggestions_print').hide();
            selectedIndex = -1;
        }
    });
    
    // Réinitialiser le formulaire quand le modal se ferme
    $('#print-bordereau-agent').on('hidden.bs.modal', function() {
        $('#agent_search_print').val('');
        $('#id_agent_print').val('');
        $('#agent_suggestions_print').hide().empty();
        selectedIndex = -1;
    });
    
    // Focus sur le champ quand le modal s'ouvre
    $('#print-bordereau-agent').on('shown.bs.modal', function() {
        $('#agent_search_print').focus();
    });
    
    // ===== AUTOCOMPLÉTION POUR LES USINES =====
    let searchUsineTimeout;
    let selectedUsineIndex = -1;
    
    // Fonction pour effectuer la recherche d'usines
    function searchUsines(query) {
        if (query.length < 2) {
            $('#usine_suggestions').hide().empty();
            return;
        }
        
        // Afficher le loader
        $('#usine_suggestions').show().html('<div class="autocomplete-loading">Recherche en cours...</div>');
        
        $.ajax({
            url: '../api/search_usines.php',
            method: 'GET',
            data: { q: query },
            dataType: 'json',
            success: function(data) {
                // Mode debug temporaire
                if (data.debug) {
                    console.log('Debug info:', data);
                    $('#usine_suggestions').html('<div class="autocomplete-no-results">Debug: ' + data.error + '</div>');
                } else {
                    displayUsineSuggestions(data);
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX:', error);
                console.error('Status:', status);
                console.error('Réponse:', xhr.responseText);
                $('#usine_suggestions').html('<div class="autocomplete-no-results">Erreur lors de la recherche: ' + error + '</div>');
            }
        });
    }
    
    // Fonction pour afficher les suggestions d'usines
    function displayUsineSuggestions(usines) {
        const suggestionsDiv = $('#usine_suggestions');
        
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
    
    // Événement de saisie dans le champ de recherche d'usines
    $('#usine_search').on('input', function() {
        const query = $(this).val().trim();
        
        // Réinitialiser la sélection
        $('#id_usine').val('');
        selectedUsineIndex = -1;
        
        // Annuler la recherche précédente
        clearTimeout(searchUsineTimeout);
        
        // Lancer une nouvelle recherche après un délai
        searchUsineTimeout = setTimeout(function() {
            searchUsines(query);
        }, 300);
    });
    
    // Gestion des touches du clavier pour les usines
    $('#usine_search').on('keydown', function(e) {
        const suggestions = $('#usine_suggestions .autocomplete-suggestion');
        
        if (suggestions.length === 0) return;
        
        switch(e.keyCode) {
            case 38: // Flèche haut
                e.preventDefault();
                selectedUsineIndex = selectedUsineIndex > 0 ? selectedUsineIndex - 1 : suggestions.length - 1;
                updateUsineSelection();
                break;
                
            case 40: // Flèche bas
                e.preventDefault();
                selectedUsineIndex = selectedUsineIndex < suggestions.length - 1 ? selectedUsineIndex + 1 : 0;
                updateUsineSelection();
                break;
                
            case 13: // Entrée
                e.preventDefault();
                if (selectedUsineIndex >= 0) {
                    selectUsineSuggestion(suggestions.eq(selectedUsineIndex));
                }
                break;
                
            case 27: // Échap
                $('#usine_suggestions').hide();
                selectedUsineIndex = -1;
                break;
        }
    });
    
    // Fonction pour mettre à jour la sélection visuelle des usines
    function updateUsineSelection() {
        $('#usine_suggestions .autocomplete-suggestion').removeClass('selected');
        if (selectedUsineIndex >= 0) {
            $('#usine_suggestions .autocomplete-suggestion').eq(selectedUsineIndex).addClass('selected');
        }
    }
    
    // Clic sur une suggestion d'usine
    $(document).on('click', '#usine_suggestions .autocomplete-suggestion', function() {
        selectUsineSuggestion($(this));
    });
    
    // Fonction pour sélectionner une suggestion d'usine
    function selectUsineSuggestion($suggestion) {
        const usineId = $suggestion.data('id');
        const usineName = $suggestion.find('.agent-name').text();
        
        $('#usine_search').val(usineName);
        $('#id_usine').val(usineId);
        $('#usine_suggestions').hide();
        selectedUsineIndex = -1;
    }
    
    // Réinitialiser le formulaire d'usines quand le modal se ferme
    $('#print-bordereau').on('hidden.bs.modal', function() {
        $('#usine_search').val('');
        $('#id_usine').val('');
        $('#usine_suggestions').hide().empty();
        selectedUsineIndex = -1;
    });
    
    // Focus sur le champ d'usine quand le modal s'ouvre
    $('#print-bordereau').on('shown.bs.modal', function() {
        $('#usine_search').focus();
    });
});
</script>

<!-- Scripts -->
<script src="../../plugins/jquery/jquery.min.js"></script>
<script src="../../plugins/jquery-ui/jquery-ui.min.js"></script>
<script src="../../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../../plugins/chart.js/Chart.min.js"></script>
<script src="../../plugins/sparklines/sparkline.js"></script>
<script src="../../plugins/jqvmap/jquery.vmap.min.js"></script>
<script src="../../plugins/jqvmap/maps/jquery.vmap.usa.js"></script>
<script src="../../plugins/jquery-knob/jquery.knob.min.js"></script>
<script src="../../plugins/moment/moment.min.js"></script>
<script src="../../plugins/daterangepicker/daterangepicker.js"></script>
<script src="../../plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
<script src="../../plugins/summernote/summernote-bs4.min.js"></script>
<script src="../../plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<script src="../../dist/js/adminlte.js"></script>
</body>
</html>