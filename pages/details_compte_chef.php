<?php
require_once '../inc/functions/connexion.php';
include('header.php');

$id_user = $_SESSION['user_id'];

// Récupérer l'ID du chef
$chef_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$date_debut = isset($_GET['date_debut']) ? trim($_GET['date_debut']) : '';
$date_fin = isset($_GET['date_fin']) ? trim($_GET['date_fin']) : '';
$agent_filter = isset($_GET['agent_id']) ? trim($_GET['agent_id']) : '';

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

if ($agent_filter !== '') {
    $sql_tickets .= " AND a.id_agent = :agent_id";
    $params[':agent_id'] = $agent_filter;
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
$tickets_non_payes = 0;

foreach ($tickets as $ticket) {
    $montant = $ticket['montant_paie'] ?? 0;
    $total_montant += $montant;
    
    if ($ticket['date_paie'] !== null) {
        $total_paye += $montant;
    } else {
        $total_du += $montant;
        if ($montant > 0) {
            $tickets_non_payes++;
        }
    }
}

// Résumé par agent
$resume_agents = [];
foreach ($tickets as $ticket) {
    $id_agent = $ticket['id_agent'];
    if (!isset($resume_agents[$id_agent])) {
        $resume_agents[$id_agent] = [
            'nom' => $ticket['nom_agent'],
            'total' => 0,
            'paye' => 0,
            'du' => 0,
            'tickets' => 0
        ];
    }
    $montant = $ticket['montant_paie'] ?? 0;
    $resume_agents[$id_agent]['total'] += $montant;
    $resume_agents[$id_agent]['tickets']++;
    
    if ($ticket['date_paie'] !== null) {
        $resume_agents[$id_agent]['paye'] += $montant;
    } else {
        $resume_agents[$id_agent]['du'] += $montant;
    }
}

// Pagination
$elements_per_page = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$total_tickets = count($tickets);
$total_pages = ceil($total_tickets / $elements_per_page);
$offset = ($page - 1) * $elements_per_page;
$tickets_page = array_slice($tickets, $offset, $elements_per_page);
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
}

.page-header {
    background: linear-gradient(135deg, #1a237e 0%, #283593 100%);
    color: white;
    padding: 1.5rem 2rem;
    border-radius: var(--border-radius);
    margin-bottom: 2rem;
    box-shadow: var(--shadow-md);
}

.chef-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.chef-avatar {
    width: 60px;
    height: 60px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border-radius: var(--border-radius);
    padding: 1.25rem;
    box-shadow: var(--shadow-sm);
    border-left: 4px solid;
    position: relative;
}

.stat-card.total { border-left-color: var(--info-color); }
.stat-card.paye { border-left-color: var(--success-color); }
.stat-card.du { border-left-color: var(--danger-color); }
.stat-card.tickets { border-left-color: var(--warning-color); }

.stat-card .stat-value {
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--primary-color);
}

.stat-card .stat-label {
    color: var(--text-muted);
    font-size: 0.8rem;
}

.filter-card {
    background: white;
    border-radius: var(--border-radius);
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: var(--shadow-sm);
}

.resume-agents {
    background: white;
    border-radius: var(--border-radius);
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: var(--shadow-sm);
}

.resume-agents h5 {
    color: var(--primary-color);
    margin-bottom: 1rem;
    font-weight: 600;
}

.agent-resume-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    border-bottom: 1px solid var(--border-color);
}

.agent-resume-item:last-child {
    border-bottom: none;
}

.agent-resume-item .agent-name {
    font-weight: 500;
}

.agent-resume-item .agent-stats {
    display: flex;
    gap: 1.5rem;
    font-size: 0.85rem;
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

.table thead th {
    background: #f8f9fa;
    font-weight: 600;
    color: var(--primary-color);
    padding: 0.75rem;
    font-size: 0.85rem;
}

.table tbody td {
    padding: 0.75rem;
    vertical-align: middle;
    font-size: 0.9rem;
}

.status-paye {
    background: #d4edda;
    color: #155724;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-non-paye {
    background: #f8d7da;
    color: #721c24;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-attente {
    background: #fff3cd;
    color: #856404;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
}

.btn-back {
    background: rgba(255,255,255,0.2);
    color: white;
    border: 1px solid rgba(255,255,255,0.3);
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    text-decoration: none;
    transition: all 0.3s ease;
}

.btn-back:hover {
    background: rgba(255,255,255,0.3);
    color: white;
}

.pagination-container {
    padding: 1rem 1.5rem;
    background: #f8f9fa;
    border-top: 1px solid var(--border-color);
}
</style>

<!-- En-tête -->
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div class="chef-info">
            <div class="chef-avatar">
                <i class="fas fa-user-tie"></i>
            </div>
            <div>
                <h4 class="mb-1"><?= htmlspecialchars($chef['nom'] . ' ' . $chef['prenoms']) ?></h4>
                <small class="opacity-75">
                    <i class="fas fa-phone me-1"></i><?= htmlspecialchars($chef['contact'] ?? 'N/A') ?>
                    <span class="mx-2">|</span>
                    <i class="fas fa-users me-1"></i><?= count($agents_chef) ?> agent(s)
                </small>
            </div>
        </div>
        <a href="compte_chef_equipe.php" class="btn-back">
            <i class="fas fa-arrow-left me-1"></i>Retour
        </a>
    </div>
</div>

<!-- Statistiques -->
<div class="stats-cards">
    <div class="stat-card total">
        <div class="stat-value"><?= number_format($total_montant, 0, ',', ' ') ?> FCFA</div>
        <div class="stat-label">Montant Total</div>
    </div>
    <div class="stat-card paye">
        <div class="stat-value"><?= number_format($total_paye, 0, ',', ' ') ?> FCFA</div>
        <div class="stat-label">Déjà Payé</div>
    </div>
    <div class="stat-card du">
        <div class="stat-value"><?= number_format($total_du, 0, ',', ' ') ?> FCFA</div>
        <div class="stat-label">Montant Dû</div>
    </div>
    <div class="stat-card tickets">
        <div class="stat-value"><?= $total_tickets ?></div>
        <div class="stat-label">Tickets (<?= $tickets_non_payes ?> non payés)</div>
    </div>
</div>

<!-- Filtres -->
<div class="filter-card">
    <form method="GET" class="row g-3 align-items-end">
        <input type="hidden" name="id" value="<?= $chef_id ?>">
        <div class="col-md-3">
            <label class="form-label"><i class="fas fa-user me-1"></i>Agent</label>
            <select name="agent_id" class="form-select">
                <option value="">Tous les agents</option>
                <?php foreach ($agents_chef as $agent): ?>
                    <option value="<?= $agent['id_agent'] ?>" <?= $agent_filter == $agent['id_agent'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($agent['nom_complet']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label"><i class="fas fa-calendar me-1"></i>Date début</label>
            <input type="date" name="date_debut" class="form-control" value="<?= htmlspecialchars($date_debut) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label"><i class="fas fa-calendar me-1"></i>Date fin</label>
            <input type="date" name="date_fin" class="form-control" value="<?= htmlspecialchars($date_fin) ?>">
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary me-2"><i class="fas fa-filter me-1"></i>Filtrer</button>
            <a href="details_compte_chef.php?id=<?= $chef_id ?>" class="btn btn-outline-secondary"><i class="fas fa-redo me-1"></i></a>
        </div>
    </form>
</div>

<!-- Résumé par agent -->
<?php if (!empty($resume_agents)): ?>
<div class="resume-agents">
    <h5><i class="fas fa-chart-pie me-2"></i>Résumé par Agent</h5>
    <?php foreach ($resume_agents as $agent_data): ?>
        <div class="agent-resume-item">
            <span class="agent-name"><?= htmlspecialchars($agent_data['nom']) ?></span>
            <div class="agent-stats">
                <span><i class="fas fa-ticket-alt text-muted me-1"></i><?= $agent_data['tickets'] ?> tickets</span>
                <span><i class="fas fa-coins text-info me-1"></i><?= number_format($agent_data['total'], 0, ',', ' ') ?> FCFA</span>
                <span class="text-success"><i class="fas fa-check me-1"></i><?= number_format($agent_data['paye'], 0, ',', ' ') ?></span>
                <span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i><?= number_format($agent_data['du'], 0, ',', ' ') ?></span>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Liste des tickets -->
<div class="table-container">
    <div class="table-header">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Liste des Tickets</h5>
        <span class="badge bg-primary"><?= $total_tickets ?> ticket(s)</span>
    </div>
    
    <?php if (empty($tickets_page)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-ticket-alt fa-3x mb-3 opacity-25"></i>
            <p>Aucun ticket trouvé</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>N° Ticket</th>
                        <th>Agent</th>
                        <th>Usine</th>
                        <th>Véhicule</th>
                        <th class="text-end">Poids (kg)</th>
                        <th class="text-end">Montant</th>
                        <th class="text-center">Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets_page as $ticket): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($ticket['date_ticket'])) ?></td>
                            <td><strong><?= htmlspecialchars($ticket['numero_ticket']) ?></strong></td>
                            <td><?= htmlspecialchars($ticket['nom_agent']) ?></td>
                            <td><?= htmlspecialchars($ticket['nom_usine'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($ticket['matricule_vehicule'] ?? '-') ?></td>
                            <td class="text-end"><?= number_format($ticket['poids'] ?? 0, 0, ',', ' ') ?></td>
                            <td class="text-end">
                                <?php if ($ticket['montant_paie']): ?>
                                    <strong><?= number_format($ticket['montant_paie'], 0, ',', ' ') ?> FCFA</strong>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($ticket['montant_paie'] === null): ?>
                                    <span class="status-attente">En attente PU</span>
                                <?php elseif ($ticket['date_paie'] !== null): ?>
                                    <span class="status-paye"><i class="fas fa-check me-1"></i>Payé</span>
                                <?php else: ?>
                                    <span class="status-non-paye"><i class="fas fa-clock me-1"></i>Non payé</span>
                                <?php endif; ?>
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
                    Affichage <?= $offset + 1 ?> - <?= min($offset + $elements_per_page, $total_tickets) ?> sur <?= $total_tickets ?>
                </div>
                <nav>
                    <ul class="pagination mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?id=<?= $chef_id ?>&page=<?= $page - 1 ?>&agent_id=<?= $agent_filter ?>&date_debut=<?= $date_debut ?>&date_fin=<?= $date_fin ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?id=<?= $chef_id ?>&page=<?= $i ?>&agent_id=<?= $agent_filter ?>&date_debut=<?= $date_debut ?>&date_fin=<?= $date_fin ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?id=<?= $chef_id ?>&page=<?= $page + 1 ?>&agent_id=<?= $agent_filter ?>&date_debut=<?= $date_debut ?>&date_fin=<?= $date_fin ?>">
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
