<?php
require_once '../inc/functions/connexion.php';
//require_once '../inc/functions/requete/requetes_selection_boutique.php';
include('header.php');

//$_SESSION['user_id'] = $user['id'];
$id_user = $_SESSION['user_id'];

// Liste complète des agents pour le formulaire de prêt
$sql_agents_all = "SELECT id_agent, CONCAT(nom, ' ', prenom) AS nom_agent FROM agents ORDER BY nom, prenom";
$stmt_agents_all = $conn->prepare($sql_agents_all);
$stmt_agents_all->execute();
$agents_all = $stmt_agents_all->fetchAll(PDO::FETCH_ASSOC);

// ====== Chargement des prêts depuis la base ======
// Résumé des prêts par agent
$sql_agents_prets = "SELECT 
    a.id_agent,
    CONCAT(a.nom, ' ', a.prenom) AS nom_agent,

    -- Total des montants initiaux
    SUM(p.montant_initial) AS montant_initial,

    -- Total remboursé
    SUM(p.montant_initial - p.montant_restant) AS montant_rembourse,

    -- Nouveau calcul du solde
    SUM(p.montant_initial) 
        - SUM(p.montant_initial - p.montant_restant) AS solde_financement,

    COUNT(p.id_pret) AS nombre_prets

FROM agents a
INNER JOIN prets p ON a.id_agent = p.id_agent
GROUP BY a.id_agent, a.nom, a.prenom
ORDER BY solde_financement DESC, a.nom, a.prenom";

$stmt_agents_prets = $conn->prepare($sql_agents_prets);
$stmt_agents_prets->execute();
$agents_prets = $stmt_agents_prets->fetchAll(PDO::FETCH_ASSOC);

// Liste détaillée de tous les prêts
$sql_prets = "SELECT p.*, CONCAT(a.nom, ' ', a.prenom) AS nom_agent
              FROM prets p
              INNER JOIN agents a ON p.id_agent = a.id_agent
              ORDER BY p.id_pret DESC";

$stmt_prets = $conn->prepare($sql_prets);
$stmt_prets->execute();
$prets = $stmt_prets->fetchAll(PDO::FETCH_ASSOC);

// Organisation des prêts par agent pour les modales de détail
$prets_par_agent = [];
foreach ($prets as $pret) {
    $aid = $pret['id_agent'];
    if (!isset($prets_par_agent[$aid])) {
        $prets_par_agent[$aid] = [];
    }
    $prets_par_agent[$aid][] = $pret;
}
?>

<div class="card mb-4 shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h3 class="card-title mb-0">Gestion des prêts</h3>
            <small class="text-muted">Créer, rechercher, imprimer et exporter les prêts octroyés.</small>
        </div>
        <div class="btn-toolbar" role="toolbar" aria-label="Actions prêts">
            <div class="btn-group btn-group-sm mr-2" role="group">
                <button type="button"
                        class="btn btn-primary d-flex align-items-center"
                        data-toggle="modal" data-target="#add-pret">
                    <i class="fa fa-plus mr-2"></i>
                    <span>Nouveau prêt</span>
                </button>
            </div>
            <div class="btn-group btn-group-sm mr-2" role="group">
                <button type="button"
                        class="btn btn-danger d-flex align-items-center"
                        data-toggle="modal" data-target="#print-bordereau">
                    <i class="fa fa-print mr-2"></i>
                    <span>Imprimer</span>
                </button>
            </div>
            <div class="btn-group btn-group-sm mr-2" role="group">
                <button type="button"
                        class="btn btn-success d-flex align-items-center"
                        data-toggle="modal" data-target="#search_pret">
                    <i class="fa fa-search mr-2"></i>
                    <span>Rechercher</span>
                </button>
            </div>
            <div class="btn-group btn-group-sm" role="group">
                <button type="button"
                        class="btn btn-dark d-flex align-items-center"
                        onclick="window.location.href='export_prets.php'">
                    <i class="fa fa-download mr-2"></i>
                    <span>Exporter</span>
                </button>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <!-- Modal Nouveau Prêt -->
<div class="modal fade" id="add-pret" tabindex="-1" role="dialog" aria-labelledby="addPretLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPretLabel">Nouveau prêt</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="add_pret.php" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="id_agent">Agent bénéficiaire</label>
                        <select class="form-control" id="id_agent" name="id_agent" required>
                            <option value="">Sélectionner un agent</option>
                            <?php foreach ($agents_all as $agent): ?>
                                <option value="<?= $agent['id_agent'] ?>"><?= htmlspecialchars($agent['nom_agent']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="montant_initial">Montant du prêt (FCFA)</label>
                        <input type="number" class="form-control" id="montant_initial" name="montant_initial" min="1" required>
                    </div>
                    <div class="form-group">
                        <label for="motif">Motif du prêt</label>
                        <textarea class="form-control" id="motif" name="motif" rows="3" placeholder="Motif du prêt"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
    </div>
</div>

<div class="card mt-4 shadow-sm">
    <div class="card-header">
        <h3 class="card-title mb-0">Liste détaillée des prêts octroyés</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="tablePrets" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID Prêt</th>
                        <th>Agent</th>
                        <th>Date d’octroi</th>
                        <th class="text-right">Montant initial</th>
                        <th class="text-right">Montant payé</th>
                        <th class="text-right">Montant restant</th>
                        <th>Date d’échéance</th>
                        <th>Statut</th>
                        <th>Motif</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($prets as $pret): ?>
                    <tr>
                        <td><?= (int)$pret['id_pret'] ?></td>
                        <td><?= htmlspecialchars($pret['nom_agent']) ?></td>
                        <td>
                            <?= !empty($pret['date_octroi'])
                                ? date('d/m/Y', strtotime($pret['date_octroi']))
                                : '-' ?>
                        </td>
                        <td class="text-right">
                            <span class="mr-2">
                                <?= number_format($pret['montant_initial'], 0, ',', ' ') ?> FCFA
                            </span>
                            <button type="button"
                                    class="btn btn-warning btn-xs"
                                    title="Modifier le montant initial"
                                    data-toggle="modal"
                                    data-target="#edit-montant-<?= (int)$pret['id_pret'] ?>">
                                <i class="fa fa-pencil"></i>
                            </button>
                        </td>
                        <?php
                            $mi = (float)$pret['montant_initial'];
                            $mr = (float)($pret['montant_restant'] ?? 0);
                            $mp = max($mi - $mr, 0);
                        ?>
                        <td class="text-right">
                            <?= number_format($mp, 0, ',', ' ') ?> FCFA
                        </td>
                        <td class="text-right">
                            <?= number_format($mr, 0, ',', ' ') ?> FCFA
                        </td>
                        <td>
                            <?= !empty($pret['date_echeance'])
                                ? date('d/m/Y', strtotime($pret['date_echeance']))
                                : '-' ?>
                        </td>
                        <td>
                            <?php
                                $statut = (string)$pret['statut'];
                                $badgeClass = 'badge-secondary';
                                if ($statut === 'en_cours') {
                                    $badgeClass = 'badge-warning';
                                } elseif ($statut === 'termine' || $statut === 'solde' || $statut === 'soldé') {
                                    $badgeClass = 'badge-success';
                                } elseif ($statut === 'annule' || $statut === 'annulé') {
                                    $badgeClass = 'badge-danger';
                                }
                            ?>
                            <span class="badge <?= $badgeClass ?>">
                                <?= htmlspecialchars($statut) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($pret['motif']) ?></td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button"
                                        class="btn btn-warning"
                                        title="Modifier le prêt"
                                        data-toggle="modal"
                                        data-target="#edit-pret-<?= (int)$pret['id_pret'] ?>">
                                    <i class="fa fa-pencil"></i>
                                </button>
                                <button type="button"
                                        class="btn btn-danger"
                                        title="Supprimer le prêt"
                                        data-toggle="modal"
                                        data-target="#delete-pret-<?= (int)$pret['id_pret'] ?>">
                                    <i class="fa fa-trash"></i>
                                </button>
                                <button type="button"
                                        class="btn btn-success"
                                        title="Effectuer un remboursement"
                                        data-toggle="modal"
                                        data-target="#remboursement-pret-<?= (int)$pret['id_pret'] ?>">
                                    <i class="fa fa-money"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php foreach ($prets as $pret): ?>
<!-- Modal modification montant initial du prêt -->
<div class="modal fade" id="edit-montant-<?= (int)$pret['id_pret'] ?>" tabindex="-1" role="dialog" aria-labelledby="editMontantLabel-<?= (int)$pret['id_pret'] ?>" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editMontantLabel-<?= (int)$pret['id_pret'] ?>">Modifier le montant du prêt #<?= (int)$pret['id_pret'] ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="edit_pret.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id_pret" value="<?= (int)$pret['id_pret'] ?>">
                    <div class="form-group">
                        <label>Agent</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($pret['nom_agent']) ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="montant_initial_<?= (int)$pret['id_pret'] ?>">Nouveau montant initial (FCFA)</label>
                        <input type="number"
                               class="form-control"
                               id="montant_initial_<?= (int)$pret['id_pret'] ?>"
                               name="montant_initial"
                               min="1"
                               value="<?= (float)$pret['montant_initial'] ?>"
                               required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal confirmation suppression du prêt -->
<div class="modal fade" id="delete-pret-<?= (int)$pret['id_pret'] ?>" tabindex="-1" role="dialog" aria-labelledby="deletePretLabel-<?= (int)$pret['id_pret'] ?>" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deletePretLabel-<?= (int)$pret['id_pret'] ?>">Supprimer le prêt #<?= (int)$pret['id_pret'] ?></h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="delete_pret.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id_pret" value="<?= (int)$pret['id_pret'] ?>">
                    <p>Voulez-vous vraiment supprimer ce prêt&nbsp;?</p>
                    <ul class="mb-0">
                        <li><strong>Agent :</strong> <?= htmlspecialchars($pret['nom_agent']) ?></li>
                        <li><strong>Montant initial :</strong> <?= number_format($pret['montant_initial'], 0, ',', ' ') ?> FCFA</li>
                        <li><strong>Date d’octroi :</strong>
                            <?= !empty($pret['date_octroi'])
                                ? date('d/m/Y', strtotime($pret['date_octroi']))
                                : '-' ?>
                        </li>
                    </ul>
                    <small class="text-danger d-block mt-2">Cette action est irréversible.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">Supprimer définitivement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal remboursement du prêt -->
<div class="modal fade" id="remboursement-pret-<?= (int)$pret['id_pret'] ?>" tabindex="-1" role="dialog" aria-labelledby="remboursementPretLabel-<?= (int)$pret['id_pret'] ?>" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="remboursementPretLabel-<?= (int)$pret['id_pret'] ?>">Effectuer un remboursement - Prêt #<?= (int)$pret['id_pret'] ?></h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="remboursement_pret.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id_pret" value="<?= (int)$pret['id_pret'] ?>">
                    <?php
                        $montantInitial = (float)$pret['montant_initial'];
                        $montantRestant = (float)($pret['montant_restant'] ?? 0);
                        $totalPaye = max($montantInitial - $montantRestant, 0);
                    ?>
                    <div class="form-group">
                        <label>Montant du prêt</label>
                        <input type="text" class="form-control" value="<?= number_format($montantInitial, 0, ',', ' ') ?> FCFA" disabled>
                    </div>
                    <div class="form-group">
                        <label>Total payé</label>
                        <input type="text" class="form-control" value="<?= number_format($totalPaye, 0, ',', ' ') ?> FCFA" disabled>
                    </div>
                    <div class="form-group">
                        <label>Montant restant</label>
                        <input type="text" class="form-control" value="<?= number_format($montantRestant, 0, ',', ' ') ?> FCFA" disabled>
                    </div>
                    <div class="form-group">
                        <label for="montant_paiement_<?= (int)$pret['id_pret'] ?>">Montant à payer (FCFA)</label>
                        <input type="number"
                               class="form-control"
                               id="montant_paiement_<?= (int)$pret['id_pret'] ?>"
                               name="montant_paiement"
                               min="1"
                               <?= $montantRestant > 0 ? 'max="' . $montantRestant . '"' : '' ?>
                               required>
                        <small class="form-text text-muted">Vous pouvez payer au maximum le montant restant.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">Valider le remboursement</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>
