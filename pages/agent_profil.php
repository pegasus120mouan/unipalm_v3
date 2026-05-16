<?php
require_once '../inc/functions/connexion.php';
require_once '../inc/functions/requete/requete_agents.php';
include('header.php');

// Récupérer l'ID de l'agent
$id_agent = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_agent <= 0) {
    header('Location: agents.php');
    exit;
}

// Récupérer les informations de l'agent
$stmt = $conn->prepare("
    SELECT 
        agents.id_agent,
        agents.numero_agent,
        agents.nom,
        agents.prenom,
        agents.contact,
        agents.avatar,
        agents.date_ajout,
        agents.code_pin,
        CONCAT(chef_equipe.nom, ' ', chef_equipe.prenoms) AS chef_equipe,
        chef_equipe.id_chef
    FROM agents
    LEFT JOIN chef_equipe ON agents.id_chef = chef_equipe.id_chef
    WHERE agents.id_agent = ? AND agents.date_suppression IS NULL
");
$stmt->execute([$id_agent]);
$agent = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$agent) {
    header('Location: agents.php');
    exit;
}

$avatarFile = !empty($agent['avatar']) ? $agent['avatar'] : 'agents.png';
$photoAgent = '../dossiers_images/' . $avatarFile;

// Récupérer les statistiques de l'agent (nombre de tickets, etc.)
$stmt_stats = $conn->prepare("SELECT COUNT(*) as total_tickets FROM tickets WHERE id_agent = ?");
$stmt_stats->execute([$id_agent]);
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

// Message de succès/erreur
$message = '';
$messageType = '';
if (isset($_SESSION['profil_message'])) {
    $message = $_SESSION['profil_message'];
    $messageType = $_SESSION['profil_type'] ?? 'success';
    unset($_SESSION['profil_message'], $_SESSION['profil_type']);
}
?>

<style>
.profile-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.profile-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    padding: 30px;
    color: white;
    margin-bottom: 30px;
    box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
}

.profile-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 5px 30px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.profile-sidebar {
    background: linear-gradient(180deg, #f8f9fa 0%, #ffffff 100%);
    padding: 30px;
    text-align: center;
    border-right: 1px solid #eee;
}

.profile-photo-container {
    position: relative;
    display: inline-block;
    margin-bottom: 20px;
}

.profile-photo {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    border: 5px solid #667eea;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.photo-upload-form {
    margin-top: 15px;
}

.btn-upload {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 25px;
    padding: 10px 20px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-upload:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
    color: white;
}

.profile-role {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 5px 20px;
    border-radius: 20px;
    font-size: 14px;
    display: inline-block;
    margin-bottom: 20px;
}

.profile-info-item {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #eee;
}

.profile-info-item:last-child {
    border-bottom: none;
}

.profile-info-label {
    font-weight: 600;
    color: #2c3e50;
}

.profile-info-value {
    color: #667eea;
    font-weight: 500;
}

.profile-content {
    padding: 30px;
}

.nav-tabs-custom {
    border-bottom: 2px solid #eee;
    margin-bottom: 25px;
}

.nav-tabs-custom .nav-link {
    border: none;
    color: #6c757d;
    font-weight: 600;
    padding: 15px 25px;
    border-radius: 0;
    transition: all 0.3s;
}

.nav-tabs-custom .nav-link.active {
    color: #667eea;
    border-bottom: 3px solid #667eea;
    background: transparent;
}

.nav-tabs-custom .nav-link:hover {
    color: #667eea;
}

.form-group label {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 8px;
}

.form-control-custom {
    border: 2px solid #e0e6ed;
    border-radius: 10px;
    padding: 12px 15px;
    transition: all 0.3s;
}

.form-control-custom:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
}

.btn-save {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 25px;
    padding: 12px 30px;
    font-weight: 600;
    transition: all 0.3s;
}

.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
    color: white;
}

.btn-tickets {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    color: white;
    border: none;
    border-radius: 10px;
    padding: 15px;
    font-weight: 600;
    width: 100%;
    margin-top: 20px;
    transition: all 0.3s;
}

.btn-tickets:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(17, 153, 142, 0.4);
    color: white;
}

.stat-badge {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
}

.breadcrumb-custom {
    background: transparent;
    padding: 0;
    margin-bottom: 20px;
}

.breadcrumb-custom a {
    color: #667eea;
    text-decoration: none;
}

.breadcrumb-custom a:hover {
    text-decoration: underline;
}
</style>

<div class="profile-container">
    <!-- Breadcrumb -->
    <nav class="breadcrumb-custom">
        <a href="agents.php"><i class="fas fa-users mr-1"></i> Agents</a>
        <span class="mx-2">/</span>
        <span class="text-muted"><?= htmlspecialchars($agent['nom'] . ' ' . $agent['prenom']) ?></span>
    </nav>

    <!-- Header -->
    <div class="profile-header">
        <h2><i class="fas fa-user-circle mr-2"></i>Profil de <?= htmlspecialchars($agent['nom'] . ' ' . $agent['prenom']) ?></h2>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
        <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?> mr-2"></i>
        <?= htmlspecialchars($message) ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endif; ?>

    <div class="profile-card">
        <div class="row no-gutters">
            <!-- Sidebar avec photo -->
            <div class="col-md-4 profile-sidebar">
                <div class="profile-photo-container">
                    <img src="<?= htmlspecialchars($photoAgent) ?>" alt="Photo" class="profile-photo" id="profilePhotoPreview">
                </div>
                
                <form action="traitement_agent_profil.php" method="POST" enctype="multipart/form-data" class="photo-upload-form">
                    <input type="hidden" name="id_agent" value="<?= $agent['id_agent'] ?>">
                    <input type="hidden" name="action" value="update_photo">
                    <input type="file" name="photo" id="photoInput" accept="image/*" style="display: none;" onchange="previewAndSubmit(this)">
                    <label for="photoInput" class="btn btn-upload">
                        <i class="fas fa-camera mr-2"></i>Modifier ma photo
                    </label>
                </form>
                
                <div class="profile-role mt-3">
                    <i class="fas fa-id-badge mr-1"></i> Agent
                </div>
                
                <div class="text-left mt-4">
                    <div class="profile-info-item">
                        <span class="profile-info-label">Nom</span>
                        <span class="profile-info-value"><?= htmlspecialchars($agent['nom']) ?></span>
                    </div>
                    <div class="profile-info-item">
                        <span class="profile-info-label">Prénoms</span>
                        <span class="profile-info-value"><?= htmlspecialchars($agent['prenom']) ?></span>
                    </div>
                    <div class="profile-info-item">
                        <span class="profile-info-label">Contact</span>
                        <span class="profile-info-value"><?= htmlspecialchars($agent['contact']) ?></span>
                    </div>
                    <div class="profile-info-item">
                        <span class="profile-info-label">N° Agent</span>
                        <span class="profile-info-value"><?= htmlspecialchars($agent['numero_agent']) ?></span>
                    </div>
                    <div class="profile-info-item">
                        <span class="profile-info-label">Chef d'équipe</span>
                        <span class="profile-info-value"><?= htmlspecialchars($agent['chef_equipe'] ?? 'N/A') ?></span>
                    </div>
                </div>
                
                <a href="generer_carte_agent.php?id=<?= $agent['id_agent'] ?>" class="btn btn-tickets" target="_blank">
                    <i class="fas fa-id-card mr-2"></i>Générer carte
                </a>
            </div>
            
            <!-- Contenu principal -->
            <div class="col-md-8 profile-content">
                <ul class="nav nav-tabs nav-tabs-custom" id="profileTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="edit-tab" data-toggle="tab" href="#editProfile" role="tab">
                            <i class="fas fa-edit mr-2"></i>Modifier mon profil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="pin-tab" data-toggle="tab" href="#changePin" role="tab">
                            <i class="fas fa-key mr-2"></i>Changer mon code PIN
                        </a>
                    </li>
                </ul>
                
                <div class="tab-content" id="profileTabsContent">
                    <!-- Tab Modifier profil -->
                    <div class="tab-pane fade show active" id="editProfile" role="tabpanel">
                        <form action="traitement_agent_profil.php" method="POST">
                            <input type="hidden" name="id_agent" value="<?= $agent['id_agent'] ?>">
                            <input type="hidden" name="action" value="update_profil">
                            
                            <div class="form-group">
                                <label><i class="fas fa-user mr-2"></i>Nom</label>
                                <input type="text" name="nom" class="form-control form-control-custom" value="<?= htmlspecialchars($agent['nom']) ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-user mr-2"></i>Prénoms</label>
                                <input type="text" name="prenom" class="form-control form-control-custom" value="<?= htmlspecialchars($agent['prenom']) ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-phone mr-2"></i>Contact</label>
                                <input type="text" name="contact" class="form-control form-control-custom" value="<?= htmlspecialchars($agent['contact']) ?>" required>
                            </div>
                            
                            <button type="submit" class="btn btn-save">
                                <i class="fas fa-save mr-2"></i>Modifier mon profil
                            </button>
                        </form>
                    </div>
                    
                    <!-- Tab Changer PIN -->
                    <div class="tab-pane fade" id="changePin" role="tabpanel">
                        <form action="traitement_agent_profil.php" method="POST">
                            <input type="hidden" name="id_agent" value="<?= $agent['id_agent'] ?>">
                            <input type="hidden" name="action" value="update_pin">
                            
                            <div class="form-group">
                                <label><i class="fas fa-lock mr-2"></i>Ancien code PIN</label>
                                <input type="password" name="ancien_pin" class="form-control form-control-custom" maxlength="6" pattern="[0-9]{6}" placeholder="******" required>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-key mr-2"></i>Nouveau code PIN</label>
                                <input type="password" name="nouveau_pin" class="form-control form-control-custom" maxlength="6" pattern="[0-9]{6}" placeholder="******" required>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-key mr-2"></i>Confirmer le nouveau code PIN</label>
                                <input type="password" name="confirmer_pin" class="form-control form-control-custom" maxlength="6" pattern="[0-9]{6}" placeholder="******" required>
                            </div>
                            
                            <button type="submit" class="btn btn-save">
                                <i class="fas fa-save mr-2"></i>Changer mon code PIN
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function previewAndSubmit(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profilePhotoPreview').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
        
        // Soumettre le formulaire automatiquement
        input.form.submit();
    }
}
</script>

<?php include('footer.php'); ?>
