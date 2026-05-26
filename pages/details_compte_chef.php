<?php
require_once '../inc/functions/connexion.php';
include('header.php');

$id_user = $_SESSION['user_id'];

// Récupérer l'ID du chef
$chef_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$date_debut = isset($_GET['date_debut']) ? trim($_GET['date_debut']) : '';
$date_fin = isset($_GET['date_fin']) ? trim($_GET['date_fin']) : '';
$statut_filter = isset($_GET['statut']) ? trim($_GET['statut']) : '';

if ($chef_id <= 0) {
    echo '<div class="alert alert-danger">Chef d\'équipe non spécifié.</div>';
    include('footer.php');
    exit;
}

// Récupérer les informations du chef d'équipe
$stmt_chef = $conn->prepare("SELECT * FROM chef_equipe WHERE id_chef = ?");
$stmt_chef->execute([$chef_id]);
$chef = $stmt_chef->fetch(PDO::FETCH_ASSOC);

if (!$chef) {
    echo '<div class="alert alert-danger">Chef d\'équipe non trouvé.</div>';
    include('footer.php');
    exit;
}

// Récupérer les agents de ce chef
$stmt_agents = $conn->prepare("SELECT id_agent, CONCAT(nom, ' ', prenom) as nom_complet FROM agents WHERE id_chef = ? AND date_suppression IS NULL ORDER BY nom, prenom");
$stmt_agents->execute([$chef_id]);
$agents_chef = $stmt_agents->fetchAll(PDO::FETCH_ASSOC);

// Compter le nombre de bordereaux (simplifié)
$nb_bordereaux = 0;

// Récupérer les tickets des agents de ce chef
$sql_tickets = "SELECT 
    t.*,
    CONCAT(a.nom, ' ', a.prenom) as nom_agent,
    a.id_agent,
    us.nom_usine,
    v.matricule_vehicule
FROM tickets t
INNER JOIN agents a ON t.id_agent = a.id_agent
LEFT JOIN usines us ON t.id_usine = us.id_usine
LEFT JOIN vehicules v ON t.vehicule_id = v.vehicules_id
WHERE a.id_chef = :chef_id AND a.date_suppression IS NULL";

$params = [':chef_id' => $chef_id];

// Filtre par statut
if ($statut_filter === 'paye') {
    $sql_tickets .= " AND t.date_paie IS NOT NULL";
} elseif ($statut_filter === 'non_paye') {
    $sql_tickets .= " AND t.date_paie IS NULL AND t.montant_paie IS NOT NULL";
}

if ($date_debut !== '' && $date_fin !== '') {
    $sql_tickets .= " AND DATE(t.date_ticket) BETWEEN :date_debut AND :date_fin";
    $params[':date_debut'] = $date_debut;
    $params[':date_fin'] = $date_fin;
} elseif ($date_debut !== '') {
    $sql_tickets .= " AND DATE(t.date_ticket) >= :date_debut";
    $params[':date_debut'] = $date_debut;
} elseif ($date_fin !== '') {
    $sql_tickets .= " AND DATE(t.date_ticket) <= :date_fin";
    $params[':date_fin'] = $date_fin;
}

$sql_tickets .= " ORDER BY t.date_ticket DESC";

$stmt_tickets = $conn->prepare($sql_tickets);
$stmt_tickets->execute($params);
$tickets = $stmt_tickets->fetchAll(PDO::FETCH_ASSOC);

// Calcul des totaux
$total_montant = 0;
$total_paye = 0;
$total_du = 0;
$nb_tickets = count($tickets);

foreach ($tickets as $ticket) {
    $montant = $ticket['montant_paie'] ?? 0;
    $total_montant += $montant;
    
    if ($ticket['date_paie'] !== null) {
        $total_paye += $montant;
    } else {
        $total_du += $montant;
    }
}

// Pagination
$elements_per_page = 15;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$total_tickets = count($tickets);
$total_pages = ceil($total_tickets / $elements_per_page);
$offset = ($page - 1) * $elements_per_page;
$tickets_page = array_slice($tickets, $offset, $elements_per_page);
?>

<style>
.main-container {
    background: #f5f7fa;
    min-height: 100vh;
    padding: 20px;
}

.chef-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 16px;
    padding: 24px 32px;
    margin-bottom: 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
}

.chef-profile {
    display: flex;
    align-items: center;
    gap: 16px;
}

.chef-avatar {
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    font-weight: 600;
}

.chef-details h2 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    color: #1a1a2e;
}

.chef-details h2 span {
    font-size: 0.9rem;
    font-weight: 500;
    color: #6c757d;
    margin-left: 8px;
}

.chef-meta {
    color: #6c757d;
    font-size: 0.9rem;
    margin-top: 4px;
}

.btn-retour {
    background: white;
    border: 1px solid #dee2e6;
    color: #495057;
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-retour:hover {
    background: #f8f9fa;
    color: #212529;
    text-decoration: none;
}

.content-grid {
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: 24px;
    margin-bottom: 24px;
}

.synthese-box {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.synthese-box h6 {
    color: #6c757d;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 16px;
    font-weight: 600;
}

.synthese-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}

.synthese-item:last-child {
    border-bottom: none;
}

.synthese-item span:first-child {
    color: #6c757d;
}

.synthese-item span:last-child {
    font-weight: 600;
    color: #1a1a2e;
}

.finance-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}

.finance-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border-left: 4px solid;
}

.finance-card.total { border-left-color: #6c757d; }
.finance-card.paye { border-left-color: #28a745; }
.finance-card.reste { border-left-color: #dc3545; }
.finance-card.solde { border-left-color: #28a745; background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); }

.finance-card .label {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #6c757d;
    font-size: 0.85rem;
    margin-bottom: 4px;
}

.finance-card .label i {
    font-size: 0.75rem;
}

.finance-card .sub-label {
    color: #adb5bd;
    font-size: 0.75rem;
    margin-bottom: 8px;
}

.finance-card .value {
    font-size: 1.1rem;
    font-weight: 700;
}

.finance-card.total .value { color: #1a1a2e; }
.finance-card.paye .value { color: #28a745; }
.finance-card.reste .value { color: #dc3545; }
.finance-card.solde .value { color: #155724; }

.action-buttons {
    display: flex;
    gap: 12px;
    margin-bottom: 24px;
}

.btn-action {
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.9rem;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-tickets {
    background: #4361ee;
    color: white;
    border: none;
}

.btn-tickets:hover {
    background: #3a56d4;
    color: white;
}

.btn-paiement-global {
    background: #28a745;
    color: white;
    border: none;
    cursor: pointer;
}

.btn-paiement-global:hover {
    background: #218838;
    color: white;
}

.tickets-section {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    overflow: hidden;
}

.tickets-header {
    padding: 16px 24px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.tickets-header h5 {
    margin: 0;
    font-weight: 600;
    color: #1a1a2e;
    display: flex;
    align-items: center;
    gap: 8px;
}

.filter-row {
    display: flex;
    gap: 16px;
    padding: 16px 24px;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    align-items: center;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.filter-group label {
    font-size: 0.75rem;
    color: #6c757d;
    text-transform: uppercase;
    font-weight: 600;
}

.filter-group select,
.filter-group input {
    padding: 8px 12px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    font-size: 0.9rem;
    min-width: 150px;
}

.filter-buttons {
    display: flex;
    gap: 8px;
    margin-left: auto;
}

.btn-filter {
    background: #4361ee;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 0.85rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-reset {
    background: white;
    color: #6c757d;
    border: 1px solid #dee2e6;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 0.85rem;
    text-decoration: none;
}

.tickets-table {
    width: 100%;
}

.tickets-table th {
    background: #f8f9fa;
    padding: 12px 16px;
    text-align: left;
    font-size: 0.8rem;
    color: #6c757d;
    text-transform: uppercase;
    font-weight: 600;
    border-bottom: 1px solid #e9ecef;
}

.tickets-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #f0f0f0;
    font-size: 0.9rem;
    color: #495057;
}

.tickets-table tr:hover {
    background: #f8f9fa;
}

.ticket-number {
    font-weight: 600;
    color: #1a1a2e;
}

.montant {
    font-weight: 600;
}

.montant.total { color: #1a1a2e; }
.montant.paye { color: #28a745; }
.montant.reste { color: #dc3545; }

.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status-paye {
    background: #d4edda;
    color: #155724;
}

.status-non-paye {
    background: #fff3cd;
    color: #856404;
}

.btn-paiement {
    background: #28a745;
    color: white;
    border: none;
    padding: 6px 14px;
    border-radius: 6px;
    font-size: 0.8rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-paiement:hover {
    background: #218838;
    color: white;
    text-decoration: none;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
}

.empty-state i {
    font-size: 3rem;
    opacity: 0.3;
    margin-bottom: 16px;
}

.pagination-row {
    padding: 16px 24px;
    background: #f8f9fa;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.pagination-info {
    color: #6c757d;
    font-size: 0.85rem;
}

@media (max-width: 992px) {
    .content-grid {
        grid-template-columns: 1fr;
    }
    .finance-grid {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 768px) {
    .chef-header {
        flex-direction: column;
        gap: 16px;
        text-align: center;
    }
    .chef-profile {
        flex-direction: column;
    }
    .finance-grid {
        grid-template-columns: 1fr;
    }
    .filter-row {
        flex-wrap: wrap;
    }
}
</style>

<div class="main-container">
    <!-- Messages de notification -->
    <?php if (isset($_SESSION['paiement_success']) && isset($_SESSION['paiement_details'])): 
        $details = $_SESSION['paiement_details'];
    ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-start">
                <i class="fas fa-check-circle fa-2x me-3 mt-1"></i>
                <div>
                    <h5 class="alert-heading mb-2">Paiement effectué avec succès !</h5>
                    <p class="mb-1"><strong>N° Reçu :</strong> <?= $details['numero_recu'] ?></p>
                    <p class="mb-1"><strong>Montant payé :</strong> <?= number_format($details['montant'], 0, ',', ' ') ?> FCFA</p>
                    <p class="mb-1"><strong>Tickets soldés :</strong> <?= $details['tickets_soldes'] ?> | <strong>Partiels :</strong> <?= $details['tickets_partiels'] ?></p>
                    <p class="mb-1"><strong>Reste à payer :</strong> <?= number_format($details['reste_a_payer'], 0, ',', ' ') ?> FCFA</p>
                    <p class="mb-0"><strong>Nouveau solde caisse :</strong> <?= number_format($details['nouveau_solde'], 0, ',', ' ') ?> FCFA</p>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php 
        unset($_SESSION['paiement_success']); 
        unset($_SESSION['paiement_details']);
        unset($_SESSION['success']);
        ?>
    <?php elseif (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?= $_SESSION['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- En-tête Chef -->
    <div class="chef-header">
        <div class="chef-profile">
            <div class="chef-avatar">
                <?= strtoupper(substr($chef['nom'], 0, 1)) ?>
            </div>
            <div class="chef-details">
                <h2>
                    <?= htmlspecialchars(strtoupper($chef['nom'] . ' ' . $chef['prenoms'])) ?>
                    <span>Chef #<?= $chef['id_chef'] ?></span>
                </h2>
                <div class="chef-meta">
                    <?php if (count($agents_chef) > 0): ?>
                        Agents : <?= count($agents_chef) ?> agent(s) sous sa responsabilité
                    <?php else: ?>
                        Aucun agent assigné
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <a href="compte_chef_equipe.php" class="btn-retour">
            <i class="fas fa-arrow-left"></i> Retour à la liste
        </a>
    </div>

    <!-- Grille Synthèse + Finance -->
    <div class="content-grid">
        <!-- Synthèse -->
        <div class="synthese-box">
            <h6>Synthèse</h6>
            <div class="synthese-item">
                <span>Tickets</span>
                <span><?= number_format($nb_tickets, 0, ',', ' ') ?></span>
            </div>
            <div class="synthese-item">
                <span>Bordereaux</span>
                <span><?= number_format($nb_bordereaux, 0, ',', ' ') ?></span>
            </div>
            <div class="synthese-item">
                <span>Agents</span>
                <span><?= count($agents_chef) ?></span>
            </div>
        </div>

        <!-- Synthèse Financière -->
        <div>
            <h6 style="color: #6c757d; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 16px; font-weight: 600;">
                Synthèse Financière
            </h6>
            <div class="finance-grid">
                <div class="finance-card total">
                    <div class="label"><i class="fas fa-circle"></i> Total montant</div>
                    <div class="sub-label">Total</div>
                    <div class="value"><?= number_format($total_montant, 0, ',', ' ') ?> FCFA</div>
                </div>
                <div class="finance-card paye">
                    <div class="label"><i class="fas fa-circle"></i> Montant payé</div>
                    <div class="sub-label">Montant payé</div>
                    <div class="value"><?= number_format($total_paye, 0, ',', ' ') ?> FCFA</div>
                </div>
                <div class="finance-card reste">
                    <div class="label"><i class="fas fa-file-invoice-dollar"></i> Reste à payer</div>
                    <div class="sub-label">Reste à payer</div>
                    <div class="value"><?= number_format($total_du, 0, ',', ' ') ?> FCFA</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Boutons d'action -->
    <div class="action-buttons">
        <a href="?id=<?= $chef_id ?>" class="btn-action btn-tickets">
            <i class="fas fa-ticket-alt"></i> Tickets
        </a>
        <?php if ($total_du > 0): ?>
        <button type="button" class="btn-action btn-paiement-global" data-toggle="modal" data-target="#modalPaiement">
            <i class="fas fa-money-bill-wave"></i> Effectuer un paiement
        </button>
        <?php endif; ?>
    </div>

    <!-- Section Tickets -->
    <div class="tickets-section">
        <div class="tickets-header">
            <h5><i class="fas fa-ticket-alt"></i> Tickets du chef d'équipe</h5>
        </div>

        <!-- Filtres -->
        <form method="GET" class="filter-row">
            <input type="hidden" name="id" value="<?= $chef_id ?>">
            <div class="filter-group">
                <label>Statut du ticket</label>
                <select name="statut">
                    <option value="">Tous</option>
                    <option value="paye" <?= $statut_filter === 'paye' ? 'selected' : '' ?>>Payé</option>
                    <option value="non_paye" <?= $statut_filter === 'non_paye' ? 'selected' : '' ?>>Non payé</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Date début</label>
                <input type="date" name="date_debut" value="<?= htmlspecialchars($date_debut) ?>">
            </div>
            <div class="filter-group">
                <label>Date fin</label>
                <input type="date" name="date_fin" value="<?= htmlspecialchars($date_fin) ?>">
            </div>
            <div class="filter-buttons">
                <button type="submit" class="btn-filter">
                    <i class="fas fa-search"></i> Filtrer
                </button>
                <a href="?id=<?= $chef_id ?>" class="btn-reset">Réinitialiser</a>
            </div>
        </form>

        <!-- Tableau -->
        <?php if (empty($tickets_page)): ?>
            <div class="empty-state">
                <i class="fas fa-ticket-alt d-block"></i>
                <p>Aucun ticket trouvé pour ce chef d'équipe</p>
            </div>
        <?php else: ?>
            <table class="tickets-table">
                <thead>
                    <tr>
                        <th>Date ticket</th>
                        <th>N° Ticket</th>
                        <th>Agent</th>
                        <th>Usine</th>
                        <th>Poids</th>
                        <th>Montant</th>
                        <th>Payé</th>
                        <th>Reste à payer</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets_page as $ticket): 
                        $montant = $ticket['montant_paie'] ?? 0;
                        $est_paye = $ticket['date_paie'] !== null;
                        $paye = $est_paye ? $montant : 0;
                        $reste = $est_paye ? 0 : $montant;
                    ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($ticket['date_ticket'])) ?></td>
                            <td class="ticket-number"><?= htmlspecialchars($ticket['numero_ticket']) ?></td>
                            <td><?= htmlspecialchars($ticket['nom_agent']) ?></td>
                            <td><?= htmlspecialchars($ticket['nom_usine'] ?? '-') ?></td>
                            <td><?= number_format($ticket['poids'] ?? 0, 0, ',', ' ') ?></td>
                            <td class="montant total"><?= number_format($montant, 0, ',', ' ') ?> FCFA</td>
                            <td class="montant paye"><?= number_format($paye, 0, ',', ' ') ?> FCFA</td>
                            <td class="montant reste"><?= number_format($reste, 0, ',', ' ') ?> FCFA</td>
                            <td>
                                <?php if ($est_paye): ?>
                                    <span class="status-badge status-paye">Payé</span>
                                <?php else: ?>
                                    <span class="status-badge status-non-paye">Non payé</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination-row">
                    <div class="pagination-info">
                        Affichage <?= $offset + 1 ?> à <?= min($offset + $elements_per_page, $total_tickets) ?> sur <?= $total_tickets ?> ticket(s)
                    </div>
                    <nav>
                        <ul class="pagination mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?id=<?= $chef_id ?>&page=<?= $page - 1 ?>&statut=<?= $statut_filter ?>&date_debut=<?= $date_debut ?>&date_fin=<?= $date_fin ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?id=<?= $chef_id ?>&page=<?= $i ?>&statut=<?= $statut_filter ?>&date_debut=<?= $date_debut ?>&date_fin=<?= $date_fin ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?id=<?= $chef_id ?>&page=<?= $page + 1 ?>&statut=<?= $statut_filter ?>&date_debut=<?= $date_debut ?>&date_fin=<?= $date_fin ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de Paiement Groupé -->
<?php if ($total_du > 0): ?>
<div class="modal fade" id="modalPaiement" tabindex="-1" aria-labelledby="modalPaiementLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white;">
                <h5 class="modal-title" id="modalPaiementLabel">
                    <i class="fas fa-money-bill-wave me-2"></i>Paiement pour <?= htmlspecialchars($chef['nom'] . ' ' . $chef['prenoms']) ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" style="color: white;">&times;</span>
                </button>
            </div>
            <form action="traitement_paiement_chef.php" method="POST" id="formPaiement">
                <div class="modal-body">
                    <input type="hidden" name="chef_id" value="<?= $chef_id ?>">
                    
                    <!-- Solde Caisse -->
                    <div class="alert alert-info d-flex align-items-center mb-4">
                        <i class="fas fa-wallet fa-2x me-3"></i>
                        <div>
                            <strong>Solde Caisse disponible :</strong> 
                            <span class="fs-5"><?= number_format($solde_caisse, 0, ',', ' ') ?> FCFA</span>
                            <?php if ($solde_caisse < $total_du): ?>
                                <br><small class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i>Solde insuffisant pour payer la totalité</small>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Résumé financier -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="p-3 rounded" style="background: #e9ecef;">
                                <small class="text-muted d-block">Montant Total</small>
                                <strong class="fs-5"><?= number_format($total_montant, 0, ',', ' ') ?> FCFA</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 rounded" style="background: #d4edda;">
                                <small class="text-muted d-block">Déjà Payé</small>
                                <strong class="fs-5 text-success"><?= number_format($total_paye, 0, ',', ' ') ?> FCFA</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 rounded" style="background: #f8d7da;">
                                <small class="text-muted d-block">Reste à Payer</small>
                                <strong class="fs-5 text-danger" id="resteAPayer"><?= number_format($total_du, 0, ',', ' ') ?> FCFA</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Montant à payer -->
                    <div class="mb-4">
                        <label for="montant_paiement_display" class="form-label fw-bold">
                            <i class="fas fa-coins me-1"></i>Montant à payer
                        </label>
                        <div class="input-group input-group-lg">
                            <input type="text" 
                                   class="form-control" 
                                   id="montant_paiement_display" 
                                   placeholder="Ex: 150 000 000"
                                   style="font-size: 1.25rem; font-weight: 600;"
                                   autocomplete="off">
                            <input type="hidden" id="montant_paiement" name="montant_paiement">
                            <span class="input-group-text">FCFA</span>
                        </div>
                        <div class="form-text">
                            Montant maximum : <strong><?= number_format($total_du, 0, ',', ' ') ?> FCFA</strong>
                        </div>
                    </div>

                    <!-- Aperçu après paiement -->
                    <div class="p-3 rounded" style="background: #f8f9fa; border: 2px dashed #dee2e6;">
                        <div class="row">
                            <div class="col-md-4">
                                <small class="text-muted">Reste à payer :</small>
                                <div class="fs-5 fw-bold" id="nouveauReste" style="color: #dc3545;">
                                    <?= number_format($total_du, 0, ',', ' ') ?> FCFA
                                </div>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted">Nouveau total payé :</small>
                                <div class="fs-5 fw-bold text-success" id="nouveauPaye">
                                    <?= number_format($total_paye, 0, ',', ' ') ?> FCFA
                                </div>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted">Nouveau solde caisse :</small>
                                <div class="fs-5 fw-bold text-info" id="nouveauSoldeCaisse">
                                    <?= number_format($solde_caisse, 0, ',', ' ') ?> FCFA
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Motif (optionnel) -->
                    <div class="mt-4">
                        <label for="motif_paiement" class="form-label">
                            <i class="fas fa-comment me-1"></i>Motif / Référence (optionnel)
                        </label>
                        <input type="text" class="form-control" id="motif_paiement" name="motif_paiement" placeholder="Ex: Paiement partiel mois de Mai">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>Annuler
                    </button>
                    <button type="submit" class="btn btn-success btn-lg" id="btnValiderPaiement">
                        <i class="fas fa-check mr-1"></i>Valider le paiement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Fonction pour formater les montants avec espaces (ex: 150 000 000)
function formatMontant(nombre) {
    if (nombre === null || nombre === undefined) return '0';
    var str = Math.abs(nombre).toString();
    var result = '';
    var count = 0;
    for (var i = str.length - 1; i >= 0; i--) {
        if (count > 0 && count % 3 === 0) {
            result = ' ' + result;
        }
        result = str[i] + result;
        count++;
    }
    return nombre < 0 ? '-' + result : result;
}

document.addEventListener('DOMContentLoaded', function() {
    const montantDisplay = document.getElementById('montant_paiement_display');
    const montantInput = document.getElementById('montant_paiement');
    const nouveauResteEl = document.getElementById('nouveauReste');
    const nouveauPayeEl = document.getElementById('nouveauPaye');
    const btnValider = document.getElementById('btnValiderPaiement');
    const totalDu = <?= $total_du ?>;
    const totalPaye = <?= $total_paye ?>;
    const soldeCaisse = <?= $solde_caisse ?>;
    const montantMax = Math.min(totalDu, soldeCaisse);
    
    // Formater le champ de saisie en temps réel
    montantDisplay.addEventListener('input', function(e) {
        // Garder seulement les chiffres
        let valeur = this.value.replace(/[^\d]/g, '');
        let nombre = parseInt(valeur) || 0;
        
        // Mettre à jour le champ hidden avec la valeur numérique
        montantInput.value = nombre;
        
        // Formater l'affichage avec des espaces
        if (nombre > 0) {
            this.value = formatMontant(nombre);
        } else {
            this.value = '';
        }
        
        // Mettre à jour l'aperçu
        updateApercu();
    });
    
    // Mise à jour de l'aperçu
    function updateApercu() {
        let montant = parseInt(montantInput.value) || 0;
        
        // Vérifier les limites
        let erreur = '';
        if (montant > soldeCaisse) {
            erreur = 'Solde caisse insuffisant !';
        } else if (montant > totalDu) {
            erreur = 'Montant supérieur au reste à payer !';
        }
        
        const nouveauReste = Math.max(0, totalDu - montant);
        const nouveauPaye = totalPaye + montant;
        const nouveauSoldeCaisse = soldeCaisse - montant;
        
        nouveauResteEl.textContent = formatMontant(nouveauReste) + ' FCFA';
        nouveauPayeEl.textContent = formatMontant(nouveauPaye) + ' FCFA';
        
        if (erreur) {
            nouveauResteEl.style.color = '#dc3545';
            nouveauResteEl.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>' + erreur;
            btnValider.disabled = true;
        } else if (nouveauReste === 0) {
            nouveauResteEl.style.color = '#28a745';
            nouveauResteEl.innerHTML = '<i class="fas fa-check-circle me-1"></i>Soldé !';
            btnValider.disabled = false;
        } else {
            nouveauResteEl.style.color = '#dc3545';
            btnValider.disabled = false;
        }
        
        // Afficher le nouveau solde caisse
        if (document.getElementById('nouveauSoldeCaisse')) {
            document.getElementById('nouveauSoldeCaisse').textContent = formatMontant(nouveauSoldeCaisse) + ' FCFA';
        }
    }
    
    // Validation du formulaire
    document.getElementById('formPaiement').addEventListener('submit', function(e) {
        const montant = parseInt(montantInput.value) || 0;
        if (montant <= 0) {
            e.preventDefault();
            alert('Veuillez entrer un montant valide.');
            return false;
        }
        if (montant > soldeCaisse) {
            e.preventDefault();
            alert('Le montant dépasse le solde de caisse disponible (' + new Intl.NumberFormat('fr-FR').format(soldeCaisse) + ' FCFA).');
            return false;
        }
        if (montant > totalDu) {
            e.preventDefault();
            alert('Le montant ne peut pas dépasser le reste à payer.');
            return false;
        }
        
        // Confirmation
        if (!confirm('Confirmer le paiement de ' + new Intl.NumberFormat('fr-FR').format(montant) + ' FCFA ?')) {
            e.preventDefault();
            return false;
        }
    });
});
</script>
<?php endif; ?>

<?php include('footer.php'); ?>
