<?php
require_once '../inc/functions/connexion.php';
require_once '../inc/functions/requete/api_requete_verificateurs.php';
include('header.php');

$search = trim($_GET['recherche'] ?? '');
$apiResult = getVerificateursApiWithMeta($search);
$verificateurs = $apiResult['verificateurs'];
$total = $apiResult['total'];
$apiError = $apiResult['error'] ?? null;
?>

<style>
    .block-container {
        background-color: #d7dbdd;
        padding: 20px;
        border-radius: 5px;
        width: 100%;
        margin-bottom: 20px;
    }
    .api-source-badge {
        background: #1b5e20;
        color: #fff;
        font-size: 0.8rem;
        padding: 4px 10px;
        border-radius: 12px;
    }
    a.verif-name-link {
        color: #007bff;
        text-decoration: none;
        cursor: pointer;
    }
    a.verif-name-link:hover {
        color: #0056b3;
        text-decoration: underline;
    }
</style>

<h2 class="mb-2">
    <i class="fas fa-user-check"></i> Gestion des vérificateurs
    <span class="api-source-badge ml-2"><i class="fas fa-cloud"></i> API verif-unipalm</span>
</h2>
<p class="text-muted small mb-3">
    Données synchronisées depuis
    <code><?= htmlspecialchars(VERIF_UNIPALM_API_URL) ?></code>
</p>

<?php if (!$apiResult['success']): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-triangle"></i>
    <strong>Impossible de charger les vérificateurs.</strong>
    <?= htmlspecialchars($apiError ?? 'Erreur inconnue') ?>
</div>
<?php endif; ?>

<div class="block-container d-flex flex-wrap align-items-center">
    <button type="button" class="btn btn-success mr-2" data-toggle="modal" data-target="#add-verificateur">
        <i class="fas fa-user-plus"></i> Enregistrer un vérificateur
    </button>
    <a href="liste_verificateurs.php" class="btn btn-outline-secondary mr-2">
        <i class="fas fa-sync-alt"></i> Actualiser
    </a>
    <span class="badge badge-info ml-2">Total API : <?= (int) $total ?></span>
    <span class="badge badge-success ml-1">Affichés : <?= count($verificateurs) ?></span>
</div>

<form action="liste_verificateurs.php" method="GET" class="form-inline mb-3">
    <input class="form-control mr-2" type="search" name="recherche"
           style="width: 320px;" placeholder="Rechercher (nom, login, email)..."
           value="<?= htmlspecialchars($search) ?>">
    <button class="btn btn-primary" type="submit">
        <i class="fas fa-search"></i> Rechercher
    </button>
    <?php if ($search !== ''): ?>
        <a href="liste_verificateurs.php" class="btn btn-secondary ml-2">Réinitialiser</a>
    <?php endif; ?>
</form>

<table id="example1" class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>Nom complet</th>
            <th>Login</th>
            <th>Email</th>
            <th>Email vérifié</th>
            <th>Créé le</th>
            <th>ID</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($verificateurs)): ?>
        <tr>
            <td colspan="6" class="text-center text-muted py-4">
                <?= $apiResult['success'] ? 'Aucun vérificateur trouvé.' : 'Chargement impossible.' ?>
            </td>
        </tr>
    <?php else: ?>
        <?php foreach ($verificateurs as $v): ?>
        <tr>
            <td>
                <a href="#" class="verif-name-link" data-toggle="modal"
                   data-target="#edit-verif-<?= (int) $v['id'] ?>"
                   title="Modifier les informations">
                    <i class="fas fa-edit mr-1"></i>
                    <strong><?= htmlspecialchars($v['name']) ?></strong>
                </a>
            </td>
            <td><?= htmlspecialchars($v['login']) ?></td>
            <td><?= htmlspecialchars($v['email']) ?></td>
            <td>
                <?php if (!empty($v['email_verified_at'])): ?>
                    <span class="badge badge-success"><i class="fas fa-check"></i> Oui</span>
                <?php else: ?>
                    <span class="badge badge-warning"><i class="fas fa-clock"></i> Non</span>
                <?php endif; ?>
            </td>
            <td><?= $v['created_at'] ? htmlspecialchars(date('d/m/Y H:i', strtotime($v['created_at']))) : '—' ?></td>
            <td><span class="badge badge-secondary">#<?= (int) $v['id'] ?></span></td>
        </tr>

        <div class="modal fade" id="edit-verif-<?= (int) $v['id'] ?>" tabindex="-1" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-user-edit"></i>
                            Modifier — <?= htmlspecialchars($v['name']) ?>
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <form method="post" action="traitement_verificateurs.php">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?= (int) $v['id'] ?>">
                            <div class="form-group">
                                <label>Nom complet <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name"
                                       value="<?= htmlspecialchars($v['name']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Login <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="login"
                                       value="<?= htmlspecialchars($v['login']) ?>" required
                                       pattern="[a-zA-Z0-9._-]{3,50}">
                            </div>
                            <div class="form-group">
                                <label>Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email"
                                       value="<?= htmlspecialchars($v['email']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Nouveau mot de passe <small class="text-muted">(optionnel)</small></label>
                                <input type="password" class="form-control" name="password"
                                       placeholder="Laisser vide pour ne pas changer">
                            </div>
                            <div class="form-group">
                                <label>Confirmation mot de passe</label>
                                <input type="password" class="form-control" name="retype_password">
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Enregistrer les modifications
                            </button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>

<div class="modal fade" id="add-verificateur" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Enregistrer un vérificateur</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <form method="post" action="traitement_verificateurs.php">
                    <input type="hidden" name="action" value="create">
                    <div class="form-group">
                        <label>Nom complet <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name"
                               placeholder="Ex : Kouassi Jean" required>
                        <small class="text-muted">Nom et prénoms ensemble (colonne name en base)</small>
                    </div>
                    <div class="form-group">
                        <label>Login <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="login"
                               placeholder="Identifiant de connexion" required
                               pattern="[a-zA-Z0-9._-]{3,50}"
                               title="3 à 50 caractères : lettres, chiffres, . _ -">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Mot de passe</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="form-group">
                        <label>Confirmation</label>
                        <input type="password" class="form-control" name="retype_password" required>
                    </div>
                    <p class="text-muted small">
                        <i class="fas fa-info-circle"></i>
                        8 caractères min., majuscule, minuscule et chiffre.
                    </p>
                    <button type="submit" class="btn btn-success">Enregistrer</button>
                    <button type="button" class="btn btn-light" data-dismiss="modal">Annuler</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
if (isset($_SESSION['popup']) && $_SESSION['popup'] === true) {
    $msg = $_SESSION['message'] ?? 'Opération réussie.';
    echo '<script>Swal.fire({ icon: "success", title: "Succès", text: ' . json_encode($msg) . ', timer: 3000, showConfirmButton: false });</script>';
    $_SESSION['popup'] = false;
    unset($_SESSION['message']);
}
if (isset($_SESSION['delete_pop']) && $_SESSION['delete_pop'] === true) {
    $msg = $_SESSION['message'] ?? 'Une erreur est survenue.';
    echo '<script>Swal.fire({ icon: "error", title: "Erreur", text: ' . json_encode($msg) . ' });</script>';
    $_SESSION['delete_pop'] = false;
    unset($_SESSION['message']);
}
?>

<?php include('footer.php'); ?>
