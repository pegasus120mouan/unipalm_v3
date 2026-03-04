<?php
require_once '../inc/functions/connexion.php';
include('header.php');

// Récupérer les véhicules en double (matricules qui apparaissent plus d'une fois)
$stmt_doubles = $conn->prepare("
    SELECT v.*, 
           (SELECT COUNT(*) FROM vehicules v2 WHERE v2.matricule_vehicule = v.matricule_vehicule) as nb_occurrences
    FROM vehicules v
    WHERE v.matricule_vehicule IN (
        SELECT matricule_vehicule 
        FROM vehicules 
        GROUP BY matricule_vehicule 
        HAVING COUNT(*) > 1
    )
    ORDER BY v.matricule_vehicule, v.created_at ASC
");
$stmt_doubles->execute();
$vehicules_doubles = $stmt_doubles->fetchAll();

// Statistiques des doublons
$stmt_stats = $conn->prepare("
    SELECT 
        COUNT(DISTINCT matricule_vehicule) as nb_matricules_doubles,
        COUNT(*) as total_lignes_doubles
    FROM vehicules 
    WHERE matricule_vehicule IN (
        SELECT matricule_vehicule 
        FROM vehicules 
        GROUP BY matricule_vehicule 
        HAVING COUNT(*) > 1
    )
");
$stmt_stats->execute();
$stats = $stmt_stats->fetch();
?>

<style>
.page-header {
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
    color: white;
    padding: 25px 30px;
    border-radius: 12px;
    margin-bottom: 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.page-header h2 {
    margin: 0;
    font-weight: 600;
}
.stats-row {
    display: flex;
    gap: 20px;
    margin-bottom: 25px;
}
.stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    flex: 1;
    text-align: center;
}
.stat-card.danger {
    border-left: 4px solid #e74c3c;
}
.stat-card.warning {
    border-left: 4px solid #f39c12;
}
.stat-card h3 {
    margin: 0;
    font-size: 2.5rem;
    font-weight: 700;
    color: #333;
}
.stat-card p {
    margin: 5px 0 0;
    color: #666;
}
.table-container {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.table-container h5 {
    color: #333;
    font-weight: 600;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e74c3c;
}
.table thead th {
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
    color: white;
    border: none;
    padding: 12px 15px;
    font-weight: 600;
    font-size: 0.9rem;
}
.table thead th:first-child {
    border-radius: 8px 0 0 0;
}
.table thead th:last-child {
    border-radius: 0 8px 0 0;
}
.table tbody tr {
    transition: background 0.2s;
}
.table tbody tr:hover {
    background: #fff5f5;
}
.table tbody td {
    vertical-align: middle;
    padding: 12px 15px;
    border-bottom: 1px solid #eee;
}
.badge-duplicate {
    background: #e74c3c;
    color: white;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}
.badge-type {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}
.badge-type.voiture {
    background: #3498db;
    color: white;
}
.badge-type.moto {
    background: #f39c12;
    color: white;
}
.btn-delete {
    background: #e74c3c;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-delete:hover {
    background: #c0392b;
    transform: scale(1.05);
}
.empty-state {
    text-align: center;
    padding: 50px;
    color: #27ae60;
}
.empty-state i {
    font-size: 4rem;
    margin-bottom: 15px;
}
.highlight-row {
    background: #fff5f5 !important;
}
</style>

<section class="content">
    <div class="container-fluid">
        <!-- En-tête -->
        <div class="page-header">
            <h2><i class="fas fa-exclamation-triangle mr-2"></i>Véhicules en Double</h2>
            <a href="vehicules.php" class="btn btn-light">
                <i class="fas fa-arrow-left mr-1"></i>Retour aux véhicules
            </a>
        </div>

        <!-- Statistiques -->
        <div class="stats-row">
            <div class="stat-card danger">
                <h3><?= $stats['nb_matricules_doubles'] ?? 0 ?></h3>
                <p><i class="fas fa-clone mr-1"></i>Matricules en double</p>
            </div>
            <div class="stat-card warning">
                <h3><?= $stats['total_lignes_doubles'] ?? 0 ?></h3>
                <p><i class="fas fa-list mr-1"></i>Total des lignes concernées</p>
            </div>
        </div>

        <!-- Tableau -->
        <div class="table-container">
            <h5><i class="fas fa-list mr-2"></i>Liste des véhicules en double</h5>
            
            <?php if (count($vehicules_doubles) > 0): ?>
                <div class="table-responsive">
                    <table class="table" id="doublesTable">
                        <thead>
                            <tr>
                                <th><i class="fas fa-hashtag mr-1"></i>ID</th>
                                <th><i class="fas fa-car mr-1"></i>Type</th>
                                <th><i class="fas fa-id-card mr-1"></i>Matricule</th>
                                <th><i class="fas fa-copy mr-1"></i>Occurrences</th>
                                <th><i class="fas fa-calendar mr-1"></i>Date d'ajout</th>
                                <th><i class="fas fa-cog mr-1"></i>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $current_matricule = '';
                            foreach ($vehicules_doubles as $vehicule): 
                                $is_first = ($current_matricule != $vehicule['matricule_vehicule']);
                                $current_matricule = $vehicule['matricule_vehicule'];
                            ?>
                                <tr class="<?= !$is_first ? 'highlight-row' : '' ?>">
                                    <td><strong><?= $vehicule['vehicules_id'] ?></strong></td>
                                    <td>
                                        <span class="badge-type <?= $vehicule['type_vehicule'] ?? 'voiture' ?>">
                                            <?php if (($vehicule['type_vehicule'] ?? 'voiture') == 'voiture'): ?>
                                                <i class="fas fa-car mr-1"></i>Voiture
                                            <?php else: ?>
                                                <i class="fas fa-motorcycle mr-1"></i>Moto
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td><strong><?= htmlspecialchars($vehicule['matricule_vehicule']) ?></strong></td>
                                    <td><span class="badge-duplicate"><?= $vehicule['nb_occurrences'] ?> fois</span></td>
                                    <td><?= date('d/m/Y H:i', strtotime($vehicule['created_at'])) ?></td>
                                    <td>
                                        <button type="button" class="btn-delete delete-btn" 
                                                data-id="<?= $vehicule['vehicules_id'] ?>"
                                                data-matricule="<?= htmlspecialchars($vehicule['matricule_vehicule']) ?>"
                                                title="Supprimer ce doublon">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <h4>Aucun doublon détecté</h4>
                    <p class="text-muted">Tous les matricules de véhicules sont uniques.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle mr-2"></i>Confirmer la suppression</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer le véhicule avec le matricule <strong id="deleteMatricule"></strong> ?</p>
                <p class="text-muted small">Cette action est irréversible.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">
                    <i class="fas fa-trash mr-1"></i>Supprimer
                </button>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>

<script>
$(document).ready(function() {
    var vehicleIdToDelete = null;
    
    // Clic sur le bouton supprimer
    $('.delete-btn').on('click', function() {
        vehicleIdToDelete = $(this).data('id');
        var matricule = $(this).data('matricule');
        $('#deleteMatricule').text(matricule);
        $('#deleteModal').modal('show');
    });
    
    // Confirmation de suppression
    $('#confirmDelete').on('click', function() {
        if (vehicleIdToDelete) {
            // Créer un formulaire et soumettre
            var form = $('<form>', {
                'method': 'POST',
                'action': 'delete_vehicule.php'
            });
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'id',
                'value': vehicleIdToDelete
            }));
            $('body').append(form);
            form.submit();
        }
    });
});
</script>
