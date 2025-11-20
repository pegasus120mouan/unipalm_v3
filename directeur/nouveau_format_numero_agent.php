<?php
require_once '../inc/functions/connexion.php';
session_start();

echo "<!DOCTYPE html>";
echo "<html lang='fr'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Nouveau Format Numéro Agent</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "</head>";
echo "<body class='bg-light'>";
echo "<div class='container mt-5'>";

echo "<div class='card'>";
echo "<div class='card-header bg-success text-white'>";
echo "<h2>🆔 Nouveau Format de Numéro d'Agent</h2>";
echo "</div>";
echo "<div class='card-body'>";

// Nouvelle fonction de génération avec initiales de l'agent
function genererNumeroAgentAvecNom($conn, $id_chef, $nom_agent, $prenom_agent) {
    $annee_courte = date('y'); // Année sur 2 chiffres (25 pour 2025)
    
    // Récupérer le nom du chef et créer un code
    $stmt = $conn->prepare("SELECT nom, prenoms FROM chef_equipe WHERE id_chef = ?");
    $stmt->execute([$id_chef]);
    $chef = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$chef) {
        throw new Exception("Chef d'équipe non trouvé");
    }
    
    // Créer un code chef à partir du nom (3 premières lettres en majuscules)
    $code_chef = strtoupper(substr($chef['nom'], 0, 3));
    
    // Créer les initiales de l'agent (première lettre du nom + première lettre du prénom)
    $initiale_nom = strtoupper(substr($nom_agent, 0, 1));
    $initiale_prenom = strtoupper(substr($prenom_agent, 0, 1));
    $initiales_agent = $initiale_nom . $initiale_prenom;
    
    // Format: AGT-25-ZAL-YD (AGT + Année + Code Chef + Initiales Agent)
    $prefixe = "AGT-" . $annee_courte . "-" . $code_chef . "-" . $initiales_agent;
    
    // Récupérer le dernier numéro d'agent créé avec ces initiales pour ce chef cette année
    $stmt = $conn->prepare("
        SELECT numero_agent 
        FROM agents 
        WHERE numero_agent LIKE ? 
        ORDER BY numero_agent DESC 
        LIMIT 1
    ");
    $stmt->execute([$prefixe . '%']);
    $dernier_numero = $stmt->fetchColumn();
    
    if ($dernier_numero) {
        // Extraire le numéro séquentiel et l'incrémenter
        // Format: AGT-25-ZAL-YD01 -> extraire les 2 derniers chiffres
        $sequence = (int)substr($dernier_numero, -2) + 1;
    } else {
        // Premier agent avec ces initiales pour ce chef cette année
        $sequence = 1;
    }
    
    // Formater avec des zéros à gauche (2 chiffres pour la séquence)
    $sequence_format = str_pad($sequence, 2, '0', STR_PAD_LEFT);
    $numero_agent = $prefixe . $sequence_format;
    
    // Vérifier l'unicité
    $stmt_check = $conn->prepare("SELECT COUNT(*) FROM agents WHERE numero_agent = ?");
    $stmt_check->execute([$numero_agent]);
    
    if ($stmt_check->fetchColumn() > 0) {
        do {
            $sequence++;
            $sequence_format = str_pad($sequence, 2, '0', STR_PAD_LEFT);
            $numero_agent = $prefixe . $sequence_format;
            $stmt_check->execute([$numero_agent]);
        } while ($stmt_check->fetchColumn() > 0);
    }
    
    return $numero_agent;
}

try {
    echo "<div class='alert alert-info'>";
    echo "<h4>📋 Nouveau Format Proposé</h4>";
    echo "<p><strong>Format :</strong> <code>AGT-{Année}-{CodeChef}-{InitialesAgent}{Séquence}</code></p>";
    echo "<ul>";
    echo "<li><strong>AGT</strong> = Préfixe fixe</li>";
    echo "<li><strong>Année</strong> = 2 chiffres (25 pour 2025)</li>";
    echo "<li><strong>CodeChef</strong> = 3 premières lettres du nom du chef en majuscules</li>";
    echo "<li><strong>InitialesAgent</strong> = Première lettre du nom + première lettre du prénom</li>";
    echo "<li><strong>Séquence</strong> = 2 chiffres avec zéros à gauche (01, 02, etc.)</li>";
    echo "</ul>";
    echo "<p><strong>Exemples :</strong></p>";
    echo "<ul>";
    echo "<li><span class='badge bg-success'>AGT-25-ZAL-YD01</span> = YEO DIAKARIDJA, chef Zalle, 1er avec ces initiales</li>";
    echo "<li><span class='badge bg-success'>AGT-25-SAN-SI01</span> = SANGARE ISSOU, chef Sangare, 1er avec ces initiales</li>";
    echo "<li><span class='badge bg-success'>AGT-25-PEG-PM01</span> = PEGASUS MOUAN, chef Pegasus, 1er avec ces initiales</li>";
    echo "</ul>";
    echo "</div>";
    
    // Récupérer les chefs d'équipe
    $stmt = $conn->prepare("
        SELECT 
            c.id_chef,
            c.nom,
            c.prenoms,
            CONCAT(c.nom, ' ', c.prenoms) as nom_complet,
            COUNT(a.id_agent) as nb_agents
        FROM chef_equipe c
        LEFT JOIN agents a ON c.id_chef = a.id_chef AND a.date_suppression IS NULL
        GROUP BY c.id_chef, c.nom, c.prenoms
        ORDER BY c.nom
    ");
    $stmt->execute();
    $chefs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='alert alert-secondary'>";
    echo "<h4>👥 Exemples par Chef d'Équipe</h4>";
    
    echo "<div class='table-responsive'>";
    echo "<table class='table table-striped'>";
    echo "<thead class='table-dark'>";
    echo "<tr>";
    echo "<th>ID Chef</th>";
    echo "<th>Nom Chef</th>";
    echo "<th>Code Chef</th>";
    echo "<th>Nb Agents</th>";
    echo "<th>Exemple Numéro</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    
    foreach ($chefs as $chef) {
        $code_chef = strtoupper(substr($chef['nom'], 0, 3));
        
        // Récupérer un agent exemple pour ce chef
        $stmt_agent = $conn->prepare("
            SELECT nom, prenom 
            FROM agents 
            WHERE id_chef = ? AND date_suppression IS NULL 
            LIMIT 1
        ");
        $stmt_agent->execute([$chef['id_chef']]);
        $agent_exemple = $stmt_agent->fetch(PDO::FETCH_ASSOC);
        
        if ($agent_exemple) {
            $initiale_nom = strtoupper(substr($agent_exemple['nom'], 0, 1));
            $initiale_prenom = strtoupper(substr($agent_exemple['prenom'], 0, 1));
            $exemple_numero = "AGT-25-" . $code_chef . "-" . $initiale_nom . $initiale_prenom . "01";
            $agent_info = htmlspecialchars($agent_exemple['nom'] . ' ' . $agent_exemple['prenom']);
        } else {
            $exemple_numero = "AGT-25-" . $code_chef . "-XX01";
            $agent_info = "Aucun agent";
        }
        
        echo "<tr>";
        echo "<td>" . $chef['id_chef'] . "</td>";
        echo "<td>" . htmlspecialchars($chef['nom_complet']) . "</td>";
        echo "<td><span class='badge bg-primary'>$code_chef</span></td>";
        echo "<td>" . $chef['nb_agents'] . "</td>";
        echo "<td>";
        echo "<span class='badge bg-success'>$exemple_numero</span><br>";
        echo "<small class='text-muted'>($agent_info)</small>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</tbody>";
    echo "</table>";
    echo "</div>";
    echo "</div>";
    
    // Test de génération
    echo "<div class='alert alert-warning'>";
    echo "<h4>🧪 Test de Génération</h4>";
    
    if (!empty($chefs)) {
        $premier_chef = $chefs[0];
        
        // Récupérer un agent de test pour ce chef
        $stmt_test = $conn->prepare("
            SELECT nom, prenom 
            FROM agents 
            WHERE id_chef = ? AND date_suppression IS NULL 
            LIMIT 1
        ");
        $stmt_test->execute([$premier_chef['id_chef']]);
        $agent_test = $stmt_test->fetch(PDO::FETCH_ASSOC);
        
        if ($agent_test) {
            try {
                $numero_test = genererNumeroAgentAvecNom($conn, $premier_chef['id_chef'], $agent_test['nom'], $agent_test['prenom']);
                echo "<p>Test pour <strong>" . htmlspecialchars($agent_test['nom'] . ' ' . $agent_test['prenom']) . "</strong> chez <strong>" . htmlspecialchars($premier_chef['nom_complet']) . "</strong> :</p>";
                echo "<p>Numéro généré : <span class='badge bg-success fs-6'>$numero_test</span></p>";
                echo "<p class='text-success'>✅ Génération réussie !</p>";
            } catch (Exception $e) {
                echo "<p class='text-danger'>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        } else {
            echo "<p class='text-warning'>⚠️ Aucun agent trouvé pour tester avec ce chef.</p>";
        }
    }
    echo "</div>";
    
    // Avantages du nouveau format
    echo "<div class='alert alert-success'>";
    echo "<h4>✅ Avantages du Nouveau Format</h4>";
    echo "<ul>";
    echo "<li><strong>Lisibilité :</strong> On voit immédiatement le chef (ZAL = Zalle, SAN = Sangare, etc.)</li>";
    echo "<li><strong>Identification Agent :</strong> Les initiales permettent d'identifier rapidement l'agent (YD = Yeo Diakaridja)</li>";
    echo "<li><strong>Unicité :</strong> Chaque agent a un numéro unique basé sur ses initiales</li>";
    echo "<li><strong>Évolutif :</strong> Gestion automatique des doublons d'initiales (YD01, YD02, etc.)</li>";
    echo "<li><strong>Traçabilité :</strong> Identification rapide de l'équipe ET de l'agent</li>";
    echo "</ul>";
    echo "</div>";
    
    // Comparaison des formats
    echo "<div class='alert alert-info'>";
    echo "<h4>📊 Comparaison des Formats</h4>";
    
    echo "<div class='row'>";
    echo "<div class='col-md-6'>";
    echo "<h5>Ancien Format</h5>";
    echo "<ul>";
    echo "<li><code>AGT-25002001</code> → Chef ID 2, agent inconnu</li>";
    echo "<li><code>AGT-25004001</code> → Chef ID 4, agent inconnu</li>";
    echo "<li>Difficile à identifier le chef ET l'agent</li>";
    echo "<li>Numéros cryptiques</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='col-md-6'>";
    echo "<h5>Nouveau Format</h5>";
    echo "<ul>";
    echo "<li><code>AGT-25-ZAL-YD01</code> → Chef Zalle, agent Yeo Diakaridja</li>";
    echo "<li><code>AGT-25-SAN-SI01</code> → Chef Sangare, agent Sangare Issou</li>";
    echo "<li>Identification immédiate du chef ET de l'agent</li>";
    echo "<li>Numéros parlants et mémorisables</li>";
    echo "</ul>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    // Proposition de migration
    if (isset($_POST['migrer_format'])) {
        echo "<div class='alert alert-info'>";
        echo "<h4>🔄 Migration en cours...</h4>";
        echo "</div>";
        
        // Sauvegarder les anciens numéros
        $conn->beginTransaction();
        
        try {
            // Ajouter une colonne temporaire pour sauvegarder les anciens numéros
            $conn->exec("ALTER TABLE agents ADD COLUMN ancien_numero_agent VARCHAR(20) NULL");
            
            // Sauvegarder les anciens numéros
            $conn->exec("UPDATE agents SET ancien_numero_agent = numero_agent WHERE numero_agent IS NOT NULL");
            
            // Réinitialiser les numéros
            $conn->exec("UPDATE agents SET numero_agent = NULL");
            
            // Régénérer avec le nouveau format
            $stmt = $conn->prepare("
                SELECT id_agent, id_chef, nom, prenom 
                FROM agents 
                WHERE date_suppression IS NULL 
                AND id_chef IS NOT NULL
                ORDER BY id_chef, date_ajout
            ");
            $stmt->execute();
            $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $succes = 0;
            foreach ($agents as $agent) {
                $nouveau_numero = genererNumeroAgentAvecNom($conn, $agent['id_chef'], $agent['nom'], $agent['prenom']);
                
                $stmt_update = $conn->prepare("UPDATE agents SET numero_agent = ? WHERE id_agent = ?");
                $stmt_update->execute([$nouveau_numero, $agent['id_agent']]);
                $succes++;
            }
            
            $conn->commit();
            
            echo "<div class='alert alert-success'>";
            echo "<h4>✅ Migration Réussie !</h4>";
            echo "<p>$succes agents mis à jour avec le nouveau format.</p>";
            echo "<p>Les anciens numéros sont sauvegardés dans la colonne 'ancien_numero_agent'.</p>";
            echo "</div>";
            
        } catch (Exception $e) {
            $conn->rollback();
            echo "<div class='alert alert-danger'>";
            echo "<h4>❌ Erreur de Migration</h4>";
            echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }
    } else {
        echo "<div class='alert alert-warning'>";
        echo "<h4>🚀 Appliquer le Nouveau Format</h4>";
        echo "<p>Voulez-vous migrer tous les agents vers ce nouveau format ?</p>";
        echo "<p><strong>⚠️ Attention :</strong> Cette action va :</p>";
        echo "<ul>";
        echo "<li>Sauvegarder les anciens numéros</li>";
        echo "<li>Régénérer tous les numéros avec le nouveau format</li>";
        echo "<li>Cette action est réversible</li>";
        echo "</ul>";
        
        echo "<form method='post'>";
        echo "<button type='submit' name='migrer_format' class='btn btn-success btn-lg'>";
        echo "🔄 Migrer vers le Nouveau Format";
        echo "</button>";
        echo "</form>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h4>❌ Erreur</h4>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<div class='mt-4'>";
echo "<a href='agents.php' class='btn btn-primary me-2'>Retour aux Agents</a>";
echo "<a href='debug_generer_numeros.php' class='btn btn-secondary'>Script Debug</a>";
echo "</div>";

echo "</div>";
echo "</div>";
echo "</div>";
echo "</body>";
echo "</html>";
?>
