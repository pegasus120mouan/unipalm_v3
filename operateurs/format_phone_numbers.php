<?php
/**
 * Script PHP pour formater tous les numéros de téléphone dans la table agents
 * Format cible: 0768666360 (suppression des espaces, points, tirets, etc.)
 */

require_once '../inc/functions/connexion.php';

try {
    // Connexion à la base de données

    
    echo "=== FORMATAGE DES NUMÉROS DE TÉLÉPHONE ===\n\n";
    
    // 1. Affichage des données avant modification
    echo "1. État avant modification:\n";
    $stmt = $conn->prepare("SELECT id_agent, numero_agent, nom, prenom, contact FROM agents WHERE contact IS NOT NULL AND contact != '' ORDER BY id_agent LIMIT 10");
    $stmt->execute();
    $avant = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($avant as $agent) {
        echo "ID: {$agent['id_agent']} - {$agent['nom']} {$agent['prenom']} - Contact: '{$agent['contact']}'\n";
    }
    
    // 2. Création d'une sauvegarde (optionnel)
    echo "\n2. Création d'une sauvegarde...\n";
    $conn->exec("CREATE TABLE IF NOT EXISTS agents_backup_" . date('Ymd_His') . " AS SELECT * FROM agents WHERE contact IS NOT NULL");
    echo "Sauvegarde créée avec succès.\n";
    
    // 3. Mise à jour des numéros
    echo "\n3. Formatage des numéros de téléphone...\n";
    
    $updateQuery = "
        UPDATE agents 
        SET contact = CASE 
            WHEN REPLACE(
                REPLACE(
                    REPLACE(
                        REPLACE(
                            REPLACE(
                                REPLACE(
                                    REPLACE(
                                        REPLACE(
                                            REPLACE(
                                                REPLACE(contact, ' ', ''),
                                                '.', ''
                                            ), 
                                            '-', ''
                                        ), 
                                        '(', ''
                                    ), 
                                    ')', ''
                                ), 
                                '+', ''
                            ), 
                            '/', ''
                        ), 
                        '_', ''
                    ), 
                    CHAR(9), ''
                ), 
                CHAR(10), ''
            ) LIKE '00225%' 
            THEN SUBSTRING(
                REPLACE(
                    REPLACE(
                        REPLACE(
                            REPLACE(
                                REPLACE(
                                    REPLACE(
                                        REPLACE(
                                            REPLACE(
                                                REPLACE(
                                                    REPLACE(contact, ' ', ''),
                                                    '.', ''
                                                ), 
                                                '-', ''
                                            ), 
                                            '(', ''
                                        ), 
                                        ')', ''
                                    ), 
                                    '+', ''
                                ), 
                                '/', ''
                            ), 
                            '_', ''
                        ), 
                        CHAR(9), ''
                    ), 
                    CHAR(10), ''
                ), 6
            )
            ELSE REPLACE(
                REPLACE(
                    REPLACE(
                        REPLACE(
                            REPLACE(
                                REPLACE(
                                    REPLACE(
                                        REPLACE(
                                            REPLACE(
                                                REPLACE(contact, ' ', ''),
                                                '.', ''
                                            ), 
                                            '-', ''
                                        ), 
                                        '(', ''
                                    ), 
                                    ')', ''
                                ), 
                                '+', ''
                            ), 
                            '/', ''
                        ), 
                        '_', ''
                    ), 
                    CHAR(9), ''
                ), 
                CHAR(10), ''
            )
        END
        WHERE contact IS NOT NULL 
        AND contact != ''
    ";
    
    $stmt = $conn->prepare($updateQuery);
    $stmt->execute();
    $rowsAffected = $stmt->rowCount();
    
    echo "Nombre de lignes mises à jour: $rowsAffected\n";
    
    // 4. Affichage des données après modification
    echo "\n4. État après modification:\n";
    $stmt = $conn->prepare("SELECT id_agent, numero_agent, nom, prenom, contact FROM agents WHERE contact IS NOT NULL AND contact != '' ORDER BY id_agent LIMIT 10");
    $stmt->execute();
    $apres = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($apres as $agent) {
        echo "ID: {$agent['id_agent']} - {$agent['nom']} {$agent['prenom']} - Contact: '{$agent['contact']}'\n";
    }
    
    // 5. Statistiques finales
    echo "\n5. Statistiques finales:\n";
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_agents,
            COUNT(CASE WHEN contact IS NOT NULL AND contact != '' THEN 1 END) as agents_avec_contact,
            COUNT(CASE WHEN contact REGEXP '^[0-9]+$' THEN 1 END) as contacts_numeriques_seulement,
            COUNT(CASE WHEN CHAR_LENGTH(contact) = 10 AND contact LIKE '0%' THEN 1 END) as contacts_format_standard_10,
            COUNT(CASE WHEN CHAR_LENGTH(contact) = 8 AND contact NOT LIKE '0%' THEN 1 END) as contacts_format_8_chiffres,
            COUNT(CASE WHEN CHAR_LENGTH(contact) > 10 THEN 1 END) as contacts_plus_10_chiffres
        FROM agents
    ");
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Total agents: {$stats['total_agents']}\n";
    echo "Agents avec contact: {$stats['agents_avec_contact']}\n";
    echo "Contacts numériques seulement: {$stats['contacts_numeriques_seulement']}\n";
    echo "Contacts format standard (10 chiffres commençant par 0): {$stats['contacts_format_standard_10']}\n";
    echo "Contacts 8 chiffres (sans 0 initial): {$stats['contacts_format_8_chiffres']}\n";
    echo "Contacts plus de 10 chiffres: {$stats['contacts_plus_10_chiffres']}\n";
    
    // 6. Affichage des contacts problématiques
    echo "\n6. Contacts nécessitant une vérification manuelle:\n";
    $stmt = $conn->prepare("
        SELECT id_agent, numero_agent, nom, prenom, contact, CHAR_LENGTH(contact) as longueur
        FROM agents 
        WHERE contact IS NOT NULL 
        AND contact != ''
        AND (
            contact NOT REGEXP '^[0-9]+$' 
            OR CHAR_LENGTH(contact) < 8 
            OR CHAR_LENGTH(contact) > 15
        )
        ORDER BY id_agent
    ");
    $stmt->execute();
    $problematiques = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($problematiques) > 0) {
        foreach ($problematiques as $agent) {
            echo "ID: {$agent['id_agent']} - {$agent['nom']} {$agent['prenom']} - Contact: '{$agent['contact']}' (Longueur: {$agent['longueur']})\n";
        }
    } else {
        echo "Aucun contact problématique détecté.\n";
    }
    
    echo "\n=== FORMATAGE TERMINÉ ===\n";
    
} catch(PDOException $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}
?>
