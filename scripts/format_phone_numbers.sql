-- Script pour formater tous les numéros de téléphone dans la table agents
-- Format cible: 0768666360 (suppression des espaces, points, tirets, etc.)

-- Sauvegarde avant modification (optionnel)
-- CREATE TABLE agents_backup AS SELECT * FROM agents WHERE contact IS NOT NULL;

-- Mise à jour des numéros de téléphone
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
                                    REPLACE(contact, ' ', ''),  -- Supprime les espaces
                                    '.', ''                     -- Supprime les points
                                ), 
                                '-', ''                         -- Supprime les tirets
                            ), 
                            '(', ''                             -- Supprime les parenthèses ouvrantes
                        ), 
                        ')', ''                                 -- Supprime les parenthèses fermantes
                    ), 
                    '+', ''                                     -- Supprime le signe +
                ), 
                '/', ''                                         -- Supprime les slashes
            ), 
            '_', ''                                             -- Supprime les underscores
        ), 
        '\t', ''                                                -- Supprime les tabulations
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
                                        REPLACE(contact, ' ', ''),  -- Supprime les espaces
                                        '.', ''                     -- Supprime les points
                                    ), 
                                    '-', ''                         -- Supprime les tirets
                                ), 
                                '(', ''                             -- Supprime les parenthèses ouvrantes
                            ), 
                            ')', ''                                 -- Supprime les parenthèses fermantes
                        ), 
                        '+', ''                                     -- Supprime le signe +
                    ), 
                    '/', ''                                         -- Supprime les slashes
                ), 
                '_', ''                                             -- Supprime les underscores
            ), 
            '\t', ''                                                -- Supprime les tabulations
        ), 6                                                        -- Supprime les 5 premiers caractères (00225)
    )
    ELSE REPLACE(
        REPLACE(
            REPLACE(
                REPLACE(
                    REPLACE(
                        REPLACE(
                            REPLACE(
                                REPLACE(
                                    REPLACE(contact, ' ', ''),  -- Supprime les espaces
                                    '.', ''                     -- Supprime les points
                                ), 
                                '-', ''                         -- Supprime les tirets
                            ), 
                            '(', ''                             -- Supprime les parenthèses ouvrantes
                        ), 
                        ')', ''                                 -- Supprime les parenthèses fermantes
                    ), 
                    '+', ''                                     -- Supprime le signe +
                ), 
                '/', ''                                         -- Supprime les slashes
            ), 
            '_', ''                                             -- Supprime les underscores
        ), 
        '\t', ''                                                -- Supprime les tabulations
    )
END
WHERE contact IS NOT NULL 
AND contact != '';

-- Vérification des résultats
SELECT 
    id_agent,
    numero_agent,
    nom,
    prenom,
    contact,
    CHAR_LENGTH(contact) as longueur_contact
FROM agents 
WHERE contact IS NOT NULL 
AND contact != ''
ORDER BY id_agent;

-- Statistiques après mise à jour
SELECT 
    COUNT(*) as total_agents,
    COUNT(CASE WHEN contact IS NOT NULL AND contact != '' THEN 1 END) as agents_avec_contact,
    COUNT(CASE WHEN contact REGEXP '^[0-9]+$' THEN 1 END) as contacts_numeriques_seulement,
    COUNT(CASE WHEN CHAR_LENGTH(contact) = 10 AND contact LIKE '0%' THEN 1 END) as contacts_format_standard
FROM agents;
