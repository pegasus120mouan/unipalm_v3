<?php
/**
 * Script CLI pour générer les numéros d'agent manquants
 * Usage: php generer_numeros_agents_cli.php
 */

require_once '../inc/functions/connexion.php';
require_once '../pages/traitement_agents.php';

echo "=== GÉNÉRATION DES NUMÉROS D'AGENT ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // 1. Identifier les agents sans numéro
    echo "1. Analyse des agents...\n";
    
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
    ");
    $stmt->execute();
    $agents_sans_numero = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Agents sans numéro trouvés: " . count($agents_sans_numero) . "\n\n";
    
    if (empty($agents_sans_numero)) {
        echo "✅ Tous les agents ont déjà un numéro d'agent.\n";
        exit(0);
    }
    
    // 2. Afficher les agents concernés
    echo "2. Agents à traiter:\n";
    echo str_repeat("-", 80) . "\n";
    printf("%-5s %-20s %-20s %-20s %-15s\n", "ID", "Nom", "Prénom", "Chef", "Numéro Proposé");
    echo str_repeat("-", 80) . "\n";
    
    $agents_valides = [];
    foreach ($agents_sans_numero as $agent) {
        if ($agent['id_chef']) {
            $numero_propose = genererNumeroAgent($conn, $agent['id_chef']);
            $agents_valides[] = $agent;
            printf("%-5s %-20s %-20s %-20s %-15s\n", 
                $agent['id_agent'],
                substr($agent['nom'], 0, 19),
                substr($agent['prenom'], 0, 19),
                substr($agent['nom_chef'] ?? 'N/A', 0, 19),
                $numero_propose
            );
        } else {
            printf("%-5s %-20s %-20s %-20s %-15s\n", 
                $agent['id_agent'],
                substr($agent['nom'], 0, 19),
                substr($agent['prenom'], 0, 19),
                "CHEF MANQUANT",
                "ERREUR"
            );
        }
    }
    echo str_repeat("-", 80) . "\n\n";
    
    if (empty($agents_valides)) {
        echo "❌ Aucun agent valide à traiter (chefs d'équipe manquants).\n";
        exit(1);
    }
    
    // 3. Demander confirmation
    echo "3. Confirmation:\n";
    echo "Voulez-vous générer les numéros pour " . count($agents_valides) . " agent(s) ? (y/N): ";
    
    $handle = fopen("php://stdin", "r");
    $confirmation = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($confirmation) !== 'y' && strtolower($confirmation) !== 'yes') {
        echo "❌ Opération annulée par l'utilisateur.\n";
        exit(0);
    }
    
    // 4. Générer les numéros
    echo "\n4. Génération des numéros...\n";
    
    $conn->beginTransaction();
    $succes = 0;
    $erreurs = 0;
    
    foreach ($agents_valides as $agent) {
        try {
            $numero_agent = genererNumeroAgent($conn, $agent['id_chef']);
            
            $stmt_update = $conn->prepare("
                UPDATE agents 
                SET numero_agent = ?, 
                    date_modification = NOW() 
                WHERE id_agent = ?
            ");
            $result = $stmt_update->execute([$numero_agent, $agent['id_agent']]);
            
            if ($result) {
                $succes++;
                echo "✅ {$agent['nom']} {$agent['prenom']} -> $numero_agent\n";
            } else {
                $erreurs++;
                echo "❌ Erreur pour {$agent['nom']} {$agent['prenom']}\n";
            }
        } catch (Exception $e) {
            $erreurs++;
            echo "❌ Erreur pour {$agent['nom']} {$agent['prenom']}: " . $e->getMessage() . "\n";
        }
    }
    
    if ($erreurs == 0) {
        $conn->commit();
        echo "\n✅ SUCCÈS: $succes numéro(s) d'agent généré(s) avec succès!\n";
        
        // Log de l'opération
        $log_message = date('Y-m-d H:i:s') . " - Génération automatique de $succes numéros d'agent\n";
        file_put_contents('../logs/numeros_agents.log', $log_message, FILE_APPEND | LOCK_EX);
        
    } else {
        $conn->rollback();
        echo "\n❌ ÉCHEC: $erreurs erreur(s) détectée(s). Aucune modification appliquée.\n";
        exit(1);
    }
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    echo "❌ ERREUR FATALE: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== FIN DU SCRIPT ===\n";
?>
