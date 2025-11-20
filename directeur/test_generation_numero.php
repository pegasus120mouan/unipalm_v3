<?php
require_once '../inc/functions/connexion.php';
session_start();

/**
 * Fonction de test pour générer un numéro d'agent
 */
function genererNumeroTest($conn, $id_chef, $nom_agent, $prenom_agent) {
    $annee_courte = date('y');
    
    // Récupérer le nom du chef
    $stmt = $conn->prepare("SELECT nom, prenoms FROM chef_equipe WHERE id_chef = ?");
    $stmt->execute([$id_chef]);
    $chef = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$chef) {
        return "Erreur: Chef non trouvé";
    }
    
    // Code chef (3 premières lettres)
    $code_chef = strtoupper(substr($chef['nom'], 0, 3));
    
    // Initiales agent
    $initiales = strtoupper(substr($nom_agent, 0, 1) . substr($prenom_agent, 0, 1));
    
    // Compter les agents existants avec ce pattern
    $prefixe = "AGT-" . $annee_courte . "-" . $code_chef . "-" . $initiales;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM agents WHERE numero_agent LIKE ?");
    $stmt->execute([$prefixe . '%']);
    $count = $stmt->fetchColumn();
    
    $sequence = str_pad($count + 1, 2, '0', STR_PAD_LEFT);
    
    return $prefixe . $sequence;
}

?>
<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Test Génération Numéro Agent</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>
</head>
<body class='bg-light'>
<div class='container mt-5'>

<div class='row justify-content-center'>
<div class='col-md-8'>
<div class='card shadow'>
<div class='card-header bg-info text-white'>
    <h2 class='mb-0'><i class='fas fa-flask'></i> Test Génération Numéro Agent</h2>
</div>
<div class='card-body'>

<?php
// Récupérer la liste des chefs d'équipe
$stmt = $conn->prepare("SELECT id_chef, nom, prenoms FROM chef_equipe ORDER BY nom");
$stmt->execute();
$chefs = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_POST['tester'])) {
    $id_chef = $_POST['id_chef'];
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    
    if ($id_chef && $nom && $prenom) {
        $numero_genere = genererNumeroTest($conn, $id_chef, $nom, $prenom);
        
        echo "<div class='alert alert-success'>";
        echo "<h4><i class='fas fa-check-circle'></i> Numéro Généré</h4>";
        echo "<p><strong>Agent :</strong> $nom $prenom</p>";
        echo "<p><strong>Numéro généré :</strong> <span class='badge bg-primary fs-5'>$numero_genere</span></p>";
        echo "</div>";
        
        // Afficher les détails de la génération
        $stmt_chef = $conn->prepare("SELECT nom, prenoms FROM chef_equipe WHERE id_chef = ?");
        $stmt_chef->execute([$id_chef]);
        $chef_info = $stmt_chef->fetch(PDO::FETCH_ASSOC);
        
        echo "<div class='alert alert-info'>";
        echo "<h5><i class='fas fa-info-circle'></i> Détails de la génération</h5>";
        echo "<ul>";
        echo "<li><strong>Format :</strong> AGT-[ANNÉE]-[CODE_CHEF]-[INITIALES_AGENT][SÉQUENCE]</li>";
        echo "<li><strong>Année :</strong> " . date('y') . " (pour " . date('Y') . ")</li>";
        echo "<li><strong>Chef :</strong> " . $chef_info['nom'] . " " . $chef_info['prenoms'] . "</li>";
        echo "<li><strong>Code Chef :</strong> " . strtoupper(substr($chef_info['nom'], 0, 3)) . "</li>";
        echo "<li><strong>Initiales Agent :</strong> " . strtoupper(substr($nom, 0, 1) . substr($prenom, 0, 1)) . "</li>";
        echo "</ul>";
        echo "</div>";
    }
}
?>

<form method="post">
    <div class="mb-3">
        <label for="id_chef" class="form-label">Chef d'Équipe</label>
        <select class="form-select" name="id_chef" required>
            <option value="">Sélectionnez un chef d'équipe</option>
            <?php foreach ($chefs as $chef): ?>
                <option value="<?= $chef['id_chef'] ?>" <?= (isset($_POST['id_chef']) && $_POST['id_chef'] == $chef['id_chef']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($chef['nom'] . ' ' . $chef['prenoms']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="mb-3">
        <label for="nom" class="form-label">Nom de l'Agent</label>
        <input type="text" class="form-control" name="nom" value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" required placeholder="Ex: DUPONT">
    </div>
    
    <div class="mb-3">
        <label for="prenom" class="form-label">Prénom de l'Agent</label>
        <input type="text" class="form-control" name="prenom" value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>" required placeholder="Ex: Jean">
    </div>
    
    <div class="d-grid gap-2">
        <button type="submit" name="tester" class="btn btn-info btn-lg">
            <i class="fas fa-flask"></i> Tester la Génération
        </button>
    </div>
</form>

<hr>

<div class="alert alert-light">
    <h5><i class="fas fa-lightbulb"></i> Format des Numéros</h5>
    <p><strong>Structure :</strong> AGT-25-ZAL-YD01</p>
    <ul class="mb-0">
        <li><strong>AGT</strong> : Préfixe pour "Agent"</li>
        <li><strong>25</strong> : Année (2025)</li>
        <li><strong>ZAL</strong> : Code du chef (3 premières lettres du nom)</li>
        <li><strong>YD</strong> : Initiales de l'agent (Yves Dupont)</li>
        <li><strong>01</strong> : Numéro séquentiel</li>
    </ul>
</div>

</div>
</div>
</div>
</div>

<div class="text-center mt-4">
    <a href="generer_numeros_agents_ameliore.php" class="btn btn-primary me-2">
        <i class="fas fa-magic"></i> Génération Complète
    </a>
    <a href="comptes_agents.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Retour
    </a>
</div>

</div>

<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>
</body>
</html>
