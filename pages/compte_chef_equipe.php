<?php
require_once '../inc/functions/connexion.php';
require_once '../inc/functions/requete/requete_chef_equipes.php';
include('header.php');

$id_user = $_SESSION['user_id'];

// Filtres de recherche
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$chef_id = isset($_GET['chef_id']) ? trim($_GET['chef_id']) : '';
$date_debut = isset($_GET['date_debut']) ? trim($_GET['date_debut']) : '';
$date_fin = isset($_GET['date_fin']) ? trim($_GET['date_fin']) : '';
$statut_paiement = isset($_GET['statut_paiement']) ? trim($_GET['statut_paiement']) : '';

// Récupérer la liste des chefs d'équipe pour le filtre
$sql_chefs = "SELECT id_chef, CONCAT(nom, ' ', prenoms) as nom_complet FROM chef_equipe ORDER BY nom, prenoms";
$stmt_chefs = $conn->prepare($sql_chefs);
$stmt_chefs->execute();
$chefs_liste = $stmt_chefs->fetchAll(PDO::FETCH_ASSOC);

// Requête pour récupérer les montants dus par chef d'équipe
// Les montants proviennent des tickets des agents sous leur responsabilité
$sql_totaux = "SELECT 
    ce.id_chef,
    CONCAT(ce.nom, ' ', ce.prenoms) AS nom_chef,
    -- Nombre total d'agents sous ce chef
    COUNT(DISTINCT a.id_agent) AS nombre_agents,
    -- Nombre total de tickets
    COUNT(t.id_ticket) AS nombre_tickets,
    -- Montant total des tickets (montant_paie)
    COALESCE(SUM(t.montant_paie), 0) AS montant_total,
    -- Montant déjà payé
    COALESCE(SUM(CASE WHEN t.date_paie IS NOT NULL THEN t.montant_paie ELSE 0 END), 0) AS montant_paye,
    -- Montant dû (non payé)
    COALESCE(SUM(CASE WHEN t.date_paie IS NULL THEN t.montant_paie ELSE 0 END), 0) AS montant_du,
    -- Nombre de tickets non payés
    COUNT(CASE WHEN t.date_paie IS NULL AND t.montant_paie IS NOT NULL THEN 1 END) AS tickets_non_payes
FROM chef_equipe ce
LEFT JOIN agents a ON a.id_chef = ce.id_chef AND a.date_suppression IS NULL
LEFT JOIN tickets t ON t.id_agent = a.id_agent";

$params_totaux = [];
$conditions_totaux = [];

// Filtre par chef d'équipe
if ($chef_id !== '') {
    $conditions_totaux[] = 'ce.id_chef = :chef_id';
    $params_totaux[':chef_id'] = $chef_id;
}

// Filtre par recherche texte
if ($search !== '') {
    $conditions_totaux[] = "(CONCAT(ce.nom, ' ', ce.prenoms) LIKE :search)";
    $params_totaux[':search'] = '%' . $search . '%';
}

// Filtres de période sur date_ticket
if ($date_debut !== '' && $date_fin !== '') {
    $conditions_totaux[] = 'DATE(t.date_ticket) BETWEEN :date_debut AND :date_fin';
    $params_totaux[':date_debut'] = $date_debut;
    $params_totaux[':date_fin'] = $date_fin;
} elseif ($date_debut !== '') {
    $conditions_totaux[] = 'DATE(t.date_ticket) >= :date_debut';
    $params_totaux[':date_debut'] = $date_debut;
} elseif ($date_fin !== '') {
    $conditions_totaux[] = 'DATE(t.date_ticket) <= :date_fin';
    $params_totaux[':date_fin'] = $date_fin;
}

if (!empty($conditions_totaux)) {
    $sql_totaux .= ' WHERE ' . implode(' AND ', $conditions_totaux);
}

$sql_totaux .= ' GROUP BY ce.id_chef, ce.nom, ce.prenoms ORDER BY montant_du DESC, ce.nom, ce.prenoms';

$stmt_totaux = $conn->prepare($sql_totaux);
$stmt_totaux->execute($params_totaux);
$chefs_comptes_all = $stmt_totaux->fetchAll(PDO::FETCH_ASSOC);

// Filtrer par statut de paiement si nécessaire
if ($statut_paiement === 'du') {
    $chefs_comptes_all = array_filter($chefs_comptes_all, function($chef) {
        return $chef['montant_du'] > 0;
    });
} elseif ($statut_paiement === 'solde') {
    $chefs_comptes_all = array_filter($chefs_comptes_all, function($chef) {
        return $chef['montant_du'] == 0;
    });
}

// Pagination
$elements_per_page = 15;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$total_chefs = count($chefs_comptes_all);
$total_pages = ceil($total_chefs / $elements_per_page);
$offset = ($page - 1) * $elements_per_page;

// Chefs pour la page actuelle
$chefs_comptes = array_slice($chefs_comptes_all, $offset, $elements_per_page);

// Calcul des totaux globaux
$total_montant_global = array_sum(array_column($chefs_comptes_all, 'montant_total'));
$total_paye_global = array_sum(array_column($chefs_comptes_all, 'montant_paye'));
$total_du_global = array_sum(array_column($chefs_comptes_all, 'montant_du'));
$total_tickets_global = array_sum(array_column($chefs_comptes_all, 'nombre_tickets'));
?>

<style>
:root {
    --primary-color: #2c3e50;
    --secondary-color: #3498db;
    --success-color: #27ae60;
    --warning-color: #f39c12;
    --danger-color: #e74c3c;
    --info-color: #17a2b8;
    --light-bg: #f8f9fa;
    --border-color: #dee2e6;
    --text-muted: #6c757d;
    --shadow-sm: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    --shadow-md: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    --border-radius: 0.5rem;
    --transition: all 0.3s ease;
}

.page-header {
    background: linear-gradient(135deg, #1a237e 0%, #283593 100%);
    color: white;
    padding: 1.5rem 2rem;
    border-radius: var(--border-radius);
    margin-bottom: 2rem;
    box-shadow: var(--shadow-md);
}

.page-header h2 {
    margin: 0;
    font-weight: 600;
}

.page-header p {
    margin: 0.5rem 0 0;
    opacity: 0.9;
}

.filter-card {
    background: linear-gradient(135deg, #1a237e 0%, #283593 100%);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: var(--shadow-sm);
    color: white;
}

.filter-card .form-label {
    color: rgba(255,255,255,0.9);
    font-weight: 500;
    font-size: 0.85rem;
}

.filter-card .form-control,
.filter-card .form-select {
    background: rgba(255,255,255,0.95);
    border: none;
    border-radius: 0.375rem;
}

.stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border-radius: var(--border-radius);
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
    border-left: 4px solid;
    transition: var(--transition);
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.stat-card.total { border-left-color: var(--info-color); }
.stat-card.paye { border-left-color: var(--success-color); }
.stat-card.du { border-left-color: var(--danger-color); }
.stat-card.tickets { border-left-color: var(--warning-color); }

.stat-card .stat-icon {
    font-size: 2rem;
    opacity: 0.3;
    position: absolute;
    right: 1rem;
    top: 1rem;
}

.stat-card .stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary-color);
}

.stat-card .stat-label {
    color: var(--text-muted);
    font-size: 0.85rem;
    margin-top: 0.25rem;
}

.table-container {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
}

.table-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.table-header h5 {
    margin: 0;
    font-weight: 600;
    color: var(--primary-color);
}

.table thead th {
    background: #f8f9fa;
    border-bottom: 2px solid var(--border-color);
    font-weight: 600;
    color: var(--primary-color);
    padding: 1rem;
    white-space: nowrap;
}

.table tbody td {
    padding: 1rem;
    vertical-align: middle;
}

.table tbody tr:hover {
    background-color: rgba(52, 152, 219, 0.05);
}

.chef-name {
    font-weight: 600;
    color: var(--primary-color);
}

.chef-name-link {
    font-weight: 600;
    color: var(--primary-color);
    text-decoration: none;
    transition: var(--transition);
}

.chef-name-link:hover {
    color: var(--secondary-color);
    text-decoration: underline;
}

.badge-agents {
    background: var(--info-color);
    color: white;
    padding: 0.35rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.montant-total {
    font-weight: 600;
    color: var(--primary-color);
}

.montant-paye {
    color: var(--success-color);
    font-weight: 600;
}

.montant-du {
    color: var(--danger-color);
    font-weight: 700;
    font-size: 1.1rem;
}

.montant-zero {
    color: var(--success-color);
}

.btn-details {
    background: linear-gradient(135deg, var(--secondary-color) 0%, #2980b9 100%);
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    font-size: 0.85rem;
    transition: var(--transition);
}

.btn-details:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(52, 152, 219, 0.4);
    color: white;
}

.pagination-container {
    padding: 1rem 1.5rem;
    background: #f8f9fa;
    border-top: 1px solid var(--border-color);
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: var(--text-muted);
}

.empty-state i {
    font-size: 4rem;
    opacity: 0.3;
    margin-bottom: 1rem;
}
</style>

<!-- En-tête de page -->
<div class="filter-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1"><i class="fas fa-users-cog me-2"></i>Compte Chef d'Équipe</h4>
            <small class="opacity-75">Gérer les montants dus aux chefs d'équipe basés sur les tickets de leurs agents</small>
        </div>
    </div>
    
    <!-- Filtres -->
    <form method="GET" class="row g-3">
        <div class="col-md-3">
            <label class="form-label"><i class="fas fa-search me-1"></i>Rechercher</label>
            <input type="text" name="search" class="form-control" placeholder="Nom du chef..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label"><i class="fas fa-user-tie me-1"></i>Chef d'équipe</label>
            <select name="chef_id" class="form-select">
                <option value="">Tous les chefs</option>
                <?php foreach ($chefs_liste as $chef): ?>
                    <option value="<?= $chef['id_chef'] ?>" <?= $chef_id == $chef['id_chef'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($chef['nom_complet']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label"><i class="fas fa-calendar me-1"></i>Date début</label>
            <input type="date" name="date_debut" class="form-control" value="<?= htmlspecialchars($date_debut) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label"><i class="fas fa-calendar me-1"></i>Date fin</label>
            <input type="date" name="date_fin" class="form-control" value="<?= htmlspecialchars($date_fin) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label"><i class="fas fa-filter me-1"></i>Statut</label>
            <select name="statut_paiement" class="form-select">
                <option value="">Tous</option>
                <option value="du" <?= $statut_paiement === 'du' ? 'selected' : '' ?>>Avec montant dû</option>
                <option value="solde" <?= $statut_paiement === 'solde' ? 'selected' : '' ?>>Soldé</option>
            </select>
        </div>
        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-light"><i class="fas fa-search me-1"></i>Appliquer les filtres</button>
            <a href="compte_chef_equipe.php" class="btn btn-outline-light"><i class="fas fa-redo me-1"></i>Réinitialiser</a>
        </div>
    </form>
</div>

<!-- Cartes statistiques -->
<div class="stats-cards">
    <div class="stat-card total position-relative">
        <i class="fas fa-money-bill-wave stat-icon"></i>
        <div class="stat-value"><?= number_format($total_montant_global, 0, ',', ' ') ?> FCFA</div>
        <div class="stat-label">Montant Total</div>
    </div>
    <div class="stat-card paye position-relative">
        <i class="fas fa-check-circle stat-icon"></i>
        <div class="stat-value"><?= number_format($total_paye_global, 0, ',', ' ') ?> FCFA</div>
        <div class="stat-label">Déjà Payé</div>
    </div>
    <div class="stat-card du position-relative">
        <i class="fas fa-exclamation-circle stat-icon"></i>
        <div class="stat-value"><?= number_format($total_du_global, 0, ',', ' ') ?> FCFA</div>
        <div class="stat-label">Montant Dû</div>
    </div>
    <div class="stat-card tickets position-relative">
        <i class="fas fa-ticket-alt stat-icon"></i>
        <div class="stat-value"><?= number_format($total_tickets_global, 0, ',', ' ') ?></div>
        <div class="stat-label">Total Tickets</div>
    </div>
</div>

<!-- Tableau des chefs d'équipe -->
<div class="table-container">
    <div class="table-header">
        <h5><i class="fas fa-list me-2"></i>Liste des Chefs d'Équipe</h5>
        <span class="badge bg-primary"><?= $total_chefs ?> chef(s)</span>
    </div>
    
    <?php if (empty($chefs_comptes)): ?>
        <div class="empty-state">
            <i class="fas fa-users-slash d-block"></i>
            <h5>Aucun chef d'équipe trouvé</h5>
            <p>Modifiez vos critères de recherche</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th><i class="fas fa-user-tie me-1"></i>Chef d'Équipe</th>
                        <th class="text-center"><i class="fas fa-users me-1"></i>Agents</th>
                        <th class="text-center"><i class="fas fa-ticket-alt me-1"></i>Tickets</th>
                        <th class="text-end"><i class="fas fa-coins me-1"></i>Montant Total</th>
                        <th class="text-end"><i class="fas fa-check me-1"></i>Payé</th>
                        <th class="text-end"><i class="fas fa-exclamation-triangle me-1"></i>Montant Dû</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($chefs_comptes as $chef): ?>
                        <tr>
                            <td>
                                <a href="details_compte_chef.php?id=<?= $chef['id_chef'] ?>&date_debut=<?= $date_debut ?>&date_fin=<?= $date_fin ?>" class="chef-name-link">
                                    <?= htmlspecialchars($chef['nom_chef']) ?>
                                </a>
                            </td>
                            <td class="text-center">
                                <span class="badge-agents"><?= $chef['nombre_agents'] ?></span>
                            </td>
                            <td class="text-center">
                                <?= number_format($chef['nombre_tickets'], 0, ',', ' ') ?>
                                <?php if ($chef['tickets_non_payes'] > 0): ?>
                                    <br><small class="text-danger">(<?= $chef['tickets_non_payes'] ?> non payés)</small>
                                <?php endif; ?>
                            </td>
                            <td class="text-end montant-total">
                                <?= number_format($chef['montant_total'], 0, ',', ' ') ?> FCFA
                            </td>
                            <td class="text-end montant-paye">
                                <?= number_format($chef['montant_paye'], 0, ',', ' ') ?> FCFA
                            </td>
                            <td class="text-end <?= $chef['montant_du'] > 0 ? 'montant-du' : 'montant-zero' ?>">
                                <?= number_format($chef['montant_du'], 0, ',', ' ') ?> FCFA
                            </td>
                            <td class="text-center">
                                <a href="details_compte_chef.php?id=<?= $chef['id_chef'] ?>&date_debut=<?= $date_debut ?>&date_fin=<?= $date_fin ?>" 
                                   class="btn btn-details btn-sm">
                                    <i class="fas fa-eye me-1"></i>Détails
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination-container d-flex justify-content-between align-items-center">
                <div class="text-muted">
                    Affichage <?= $offset + 1 ?> - <?= min($offset + $elements_per_page, $total_chefs) ?> sur <?= $total_chefs ?>
                </div>
                <nav>
                    <ul class="pagination mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&chef_id=<?= $chef_id ?>&date_debut=<?= $date_debut ?>&date_fin=<?= $date_fin ?>&statut_paiement=<?= $statut_paiement ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&chef_id=<?= $chef_id ?>&date_debut=<?= $date_debut ?>&date_fin=<?= $date_fin ?>&statut_paiement=<?= $statut_paiement ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&chef_id=<?= $chef_id ?>&date_debut=<?= $date_debut ?>&date_fin=<?= $date_fin ?>&statut_paiement=<?= $statut_paiement ?>">
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

<?php include('footer.php'); ?>
