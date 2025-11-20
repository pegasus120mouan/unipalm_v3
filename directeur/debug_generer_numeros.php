<?php
require_once '../inc/functions/connexion.php';
session_start();

echo "<!DOCTYPE html>";
echo "<html lang='fr'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Debug Génération Numéros</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "</head>";
echo "<body class='bg-light'>";
echo "<div class='container mt-5'>";

echo "<div class='card'>";
echo "<div class='card-header bg-primary text-white'>";
echo "<h2>🐛 Debug Génération des Numéros d'Agent</h2>";
echo "</div>";
echo "<div class='card-body'>";

// Fonction de génération (copie locale avec nouveau format)
function genererNumeroAgentLocal($conn, $id_chef, $nom_agent, $prenom_agent) {
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
    
    // Vérifier l'unicité (sécurité supplémentaire)
    $stmt_check = $conn->prepare("SELECT COUNT(*) FROM agents WHERE numero_agent = ?");
    $stmt_check->execute([$numero_agent]);
    
    if ($stmt_check->fetchColumn() > 0) {
        // Si le numéro existe déjà, incrémenter jusqu'à trouver un numéro libre
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
    echo "<h4>Étape 1 : Test de la fonction de génération</h4>";
    
    // Test avec le chef ID 1 et un agent exemple
    try {
        $test_numero = genererNumeroAgentLocal($conn, 2, 'YEO', 'DIAKARIDJA');
        echo "<p>Test génération pour YEO DIAKARIDJA (chef ID 2) : <span class='badge bg-success'>$test_numero</span></p>";
        echo "<p class='text-success'>✅ Nouveau format avec initiales fonctionnel !</p>";
    } catch (Exception $e) {
        echo "<p class='text-danger'>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    echo "</div>";
    
    echo "<div class='alert alert-warning'>";
    echo "<h4>Étape 2 : Vérification de la structure de la table</h4>";
    
    // Vérifier que la colonne numero_agent existe
    $stmt = $conn->prepare("SHOW COLUMNS FROM agents LIKE 'numero_agent'");
    $stmt->execute();
    $column_exists = $stmt->fetch();
    
    if ($column_exists) {
        echo "<p>✅ Colonne 'numero_agent' existe</p>";
        echo "<p>Type : " . $column_exists['Type'] . "</p>";
    } else {
        echo "<p>❌ Colonne 'numero_agent' manquante !</p>";
        echo "<p><strong>Solution :</strong> Exécutez cette commande SQL :</p>";
        echo "<code>ALTER TABLE agents ADD COLUMN numero_agent VARCHAR(20) NULL AFTER id_agent;</code>";
    }
    echo "</div>";
    
    echo "<div class='alert alert-secondary'>";
    echo "<h4>Étape 3 : Agents sans numéro</h4>";
    
    $stmt = $conn->prepare("
        SELECT 
            a.id_agent,
            a.nom,
            a.prenom,
            a.id_chef,
            a.numero_agent,
            CONCAT(c.nom, ' ', c.prenoms) as nom_chef
        FROM agents a
        LEFT JOIN chef_equipe c ON a.id_chef = c.id_chef
        WHERE a.date_suppression IS NULL
        AND (a.numero_agent IS NULL OR a.numero_agent = '')
        ORDER BY a.id_chef, a.date_ajout ASC
        LIMIT 10
    ");
    $stmt->execute();
    $agents_sans_numero = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Agents sans numéro trouvés : " . count($agents_sans_numero) . "</p>";
    
    if (!empty($agents_sans_numero)) {
        echo "<table class='table table-sm'>";
        echo "<thead><tr><th>ID</th><th>Nom</th><th>Prénom</th><th>ID Chef</th><th>Chef</th><th>Numéro Proposé</th></tr></thead>";
        echo "<tbody>";
        
        foreach ($agents_sans_numero as $agent) {
            $numero_propose = $agent['id_chef'] ? genererNumeroAgentLocal($conn, $agent['id_chef']) : 'Chef manquant';
            
            echo "<tr>";
            echo "<td>" . $agent['id_agent'] . "</td>";
            echo "<td>" . htmlspecialchars($agent['nom']) . "</td>";
            echo "<td>" . htmlspecialchars($agent['prenom']) . "</td>";
            echo "<td>" . ($agent['id_chef'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($agent['nom_chef'] ?? 'Non assigné') . "</td>";
            echo "<td>";
            if ($agent['id_chef']) {
                echo "<span class='badge bg-primary'>$numero_propose</span>";
            } else {
                echo "<span class='badge bg-danger'>Chef manquant</span>";
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";
    }
    echo "</div>";
    
    // Traitement de la génération
    if (isset($_POST['generer_debug'])) {
        echo "<div class='alert alert-info'>";
        echo "<h4>Étape 4 : Génération en cours...</h4>";
        echo "</div>";
        
        $conn->beginTransaction();
        $succes = 0;
        $erreurs = 0;
        $details = [];
        
        foreach ($agents_sans_numero as $agent) {
            if (!$agent['id_chef']) {
                $erreurs++;
                $details[] = [
                    'agent' => $agent['nom'] . ' ' . $agent['prenom'],
                    'status' => 'error',
                    'message' => 'Chef d\'équipe manquant (ID: ' . $agent['id_agent'] . ')'
                ];
                continue;
            }
            
            try {
                $numero_agent = genererNumeroAgentLocal($conn, $agent['id_chef'], $agent['nom'], $agent['prenom']);
                
                $stmt_update = $conn->prepare("
                    UPDATE agents 
                    SET numero_agent = ?, 
                        date_modification = NOW() 
                    WHERE id_agent = ?
                ");
                $result = $stmt_update->execute([$numero_agent, $agent['id_agent']]);
                
                if ($result) {
                    $succes++;
                    $details[] = [
                        'agent' => $agent['nom'] . ' ' . $agent['prenom'],
                        'status' => 'success',
                        'message' => "Numéro généré: $numero_agent"
                    ];
                } else {
                    $erreurs++;
                    $details[] = [
                        'agent' => $agent['nom'] . ' ' . $agent['prenom'],
                        'status' => 'error',
                        'message' => 'Erreur lors de la mise à jour SQL'
                    ];
                }
            } catch (Exception $e) {
                $erreurs++;
                $details[] = [
                    'agent' => $agent['nom'] . ' ' . $agent['prenom'],
                    'status' => 'error',
                    'message' => 'Exception: ' . $e->getMessage()
                ];
            }
        }
        
        if ($erreurs == 0) {
            $conn->commit();
            echo "<div class='alert alert-success'>";
            echo "<h4>✅ Génération Réussie !</h4>";
            echo "<p><strong>$succes</strong> numéro(s) d'agent généré(s) avec succès.</p>";
        } else {
            $conn->rollback();
            echo "<div class='alert alert-danger'>";
            echo "<h4>❌ Génération Échouée</h4>";
            echo "<p><strong>$erreurs</strong> erreur(s) détectée(s). Aucune modification appliquée.</p>";
        }
        
        // Afficher les détails
        echo "<h5>Détails :</h5>";
        echo "<table class='table table-sm'>";
        echo "<thead><tr><th>Agent</th><th>Statut</th><th>Message</th></tr></thead>";
        echo "<tbody>";
        
        foreach ($details as $detail) {
            $badge_class = $detail['status'] === 'success' ? 'bg-success' : 'bg-danger';
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($detail['agent']) . "</td>";
            echo "<td><span class='badge $badge_class'>" . ucfirst($detail['status']) . "</span></td>";
            echo "<td>" . htmlspecialchars($detail['message']) . "</td>";
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";
        echo "</div>";
        
        if ($succes > 0) {
            echo "<div class='mt-3'>";
            echo "<a href='agents.php' class='btn btn-primary'>Voir les Agents</a>";
            echo "</div>";
        }
    } else {
        // Formulaire de génération
        if (!empty($agents_sans_numero)) {
            echo "<div class='alert alert-warning'>";
            echo "<h4>Génération des Numéros</h4>";
            echo "<p>Voulez-vous générer les numéros pour " . count($agents_sans_numero) . " agent(s) ?</p>";
            
            echo "<form method='post'>";
            echo "<button type='submit' name='generer_debug' class='btn btn-success'>";
            echo "🚀 Générer les Numéros (Debug)";
            echo "</button>";
            echo "</form>";
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h4>Erreur Fatale</h4>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "<div class='mt-4'>";
echo "<a href='agents.php' class='btn btn-primary me-2'>Retour aux Agents</a>";
echo "<a href='generer_numeros_agents.php' class='btn btn-secondary'>Script Principal</a>";
echo "</div>";

echo "</div>";
echo "</div>";
echo "</div>";
echo "</body>";
echo "</html>";
?>
