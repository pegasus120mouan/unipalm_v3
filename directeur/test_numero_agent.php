<?php
require_once '../inc/functions/connexion.php';

echo "<!DOCTYPE html>";
echo "<html lang='fr'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Test Numéro Agent</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "</head>";
echo "<body class='bg-light'>";
echo "<div class='container mt-5'>";

echo "<div class='row justify-content-center'>";
echo "<div class='col-md-8'>";
echo "<div class='card shadow'>";
echo "<div class='card-header bg-primary text-white'>";
echo "<h2 class='mb-0'>🧪 Test de Génération du Numéro d'Agent (Format: AGT-25+ID_CHEF+SEQUENCE)</h2>";
echo "</div>";
echo "<div class='card-body'>";

// Inclure la fonction de génération
require_once 'traitement_agents.php';

try {
    echo "<div class='alert alert-info'>";
    echo "<h4>📋 Nouveau Format de Numéro d'Agent</h4>";
    echo "<p><strong>Format :</strong> <code>AGT-{Année}{ID_Chef}{Séquence}</code></p>";
    echo "<ul>";
    echo "<li><strong>AGT</strong> = Préfixe fixe</li>";
    echo "<li><strong>Année</strong> = 2 chiffres (25 pour 2025)</li>";
    echo "<li><strong>ID_Chef</strong> = 3 chiffres avec zéros à gauche (001, 002, etc.)</li>";
    echo "<li><strong>Séquence</strong> = 3 chiffres avec zéros à gauche (001, 002, etc.)</li>";
    echo "</ul>";
    echo "<p><strong>Exemple :</strong> <span class='badge bg-success'>AGT-25001001</span> = Agent de 2025, Chef ID 1, 1er agent</p>";
    echo "</div>";
    
    // Récupérer quelques chefs d'équipe pour les tests
    $stmt = $conn->prepare("SELECT id_chef, CONCAT(nom, ' ', prenoms) as nom_chef FROM chef_equipe LIMIT 3");
    $stmt->execute();
    $chefs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Test de génération de plusieurs numéros
    echo "<div class='alert alert-secondary'>";
    echo "<h5>Génération de numéros d'agent par chef :</h5>";
    
    if (!empty($chefs)) {
        foreach ($chefs as $chef) {
            echo "<h6>Chef: " . htmlspecialchars($chef['nom_chef']) . " (ID: " . $chef['id_chef'] . ")</h6>";
            for ($i = 1; $i <= 3; $i++) {
                $numero = genererNumeroAgent($conn, $chef['id_chef']);
                echo "<p><strong>Agent $i :</strong> <span class='badge bg-primary'>$numero</span></p>";
            }
            echo "<hr>";
        }
    } else {
        echo "<p class='text-warning'>Aucun chef d'équipe trouvé pour les tests.</p>";
    }
    echo "</div>";
    
    // Vérifier la structure de la table agents
    echo "<div class='alert alert-warning'>";
    echo "<h5>🔍 Structure de la Table 'agents' :</h5>";
    
    $stmt = $conn->prepare("DESCRIBE agents");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table class='table table-sm table-striped'>";
    echo "<thead><tr><th>Colonne</th><th>Type</th><th>Null</th><th>Défaut</th></tr></thead>";
    echo "<tbody>";
    
    $numero_agent_exists = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'numero_agent') {
            $numero_agent_exists = true;
        }
        echo "<tr>";
        echo "<td><strong>" . $col['Field'] . "</strong></td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</tbody>";
    echo "</table>";
    
    if ($numero_agent_exists) {
        echo "<div class='alert alert-success mt-3'>";
        echo "<i class='fas fa-check-circle'></i> <strong>Colonne 'numero_agent' trouvée !</strong>";
        echo "</div>";
    } else {
        echo "<div class='alert alert-danger mt-3'>";
        echo "<i class='fas fa-exclamation-triangle'></i> <strong>Colonne 'numero_agent' manquante !</strong>";
        echo "<p>Vous devez exécuter cette commande SQL :</p>";
        echo "<code>ALTER TABLE agents ADD COLUMN numero_agent VARCHAR(20) NULL AFTER id_agent;</code>";
        echo "</div>";
    }
    echo "</div>";
    
    // Afficher les derniers agents créés
    echo "<div class='alert alert-info'>";
    echo "<h5>👥 Derniers Agents Créés :</h5>";
    
    $stmt = $conn->prepare("
        SELECT id_agent, numero_agent, nom, prenom, date_ajout 
        FROM agents 
        WHERE date_suppression IS NULL 
        ORDER BY date_ajout DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($agents)) {
        echo "<table class='table table-sm'>";
        echo "<thead><tr><th>ID</th><th>N° Agent</th><th>Nom</th><th>Prénom</th><th>Date Ajout</th></tr></thead>";
        echo "<tbody>";
        
        foreach ($agents as $agent) {
            echo "<tr>";
            echo "<td>" . $agent['id_agent'] . "</td>";
            echo "<td><span class='badge bg-primary'>" . ($agent['numero_agent'] ?? 'N/A') . "</span></td>";
            echo "<td>" . htmlspecialchars($agent['nom']) . "</td>";
            echo "<td>" . htmlspecialchars($agent['prenom']) . "</td>";
            echo "<td>" . date('d/m/Y H:i', strtotime($agent['date_ajout'])) . "</td>";
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";
    } else {
        echo "<p>Aucun agent trouvé.</p>";
    }
    echo "</div>";
    
    // Instructions
    echo "<div class='alert alert-success'>";
    echo "<h4>✅ Instructions pour Tester</h4>";
    echo "<ol>";
    echo "<li>Assurez-vous que la colonne 'numero_agent' existe dans la table 'agents'</li>";
    echo "<li>Allez sur la page <a href='agents.php' class='btn btn-sm btn-primary'>agents.php</a></li>";
    echo "<li>Cliquez sur 'Enregistrer un agent'</li>";
    echo "<li>Remplissez le formulaire et soumettez</li>";
    echo "<li>Vérifiez que le numéro d'agent est généré automatiquement</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h4>❌ Erreur</h4>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</div>"; // card-body
echo "</div>"; // card
echo "</div>"; // col
echo "</div>"; // row
echo "</div>"; // container

echo "</body>";
echo "</html>";
?>
