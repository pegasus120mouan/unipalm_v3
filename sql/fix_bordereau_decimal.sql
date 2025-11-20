-- Correction de la précision des champs DECIMAL dans la table bordereau
-- Problème : DECIMAL(10,2) ne peut stocker que 8 chiffres avant la virgule (max: 99 999 999,99)
-- Solution : Augmenter à DECIMAL(15,2) pour permettre 13 chiffres avant la virgule

-- Modifier la colonne montant_total
ALTER TABLE `bordereau` 
MODIFY COLUMN `montant_total` DECIMAL(15,2) NOT NULL DEFAULT '0.00';

-- Modifier la colonne montant_payer
ALTER TABLE `bordereau` 
MODIFY COLUMN `montant_payer` DECIMAL(15,2) DEFAULT NULL;

-- Modifier la colonne montant_reste
ALTER TABLE `bordereau` 
MODIFY COLUMN `montant_reste` DECIMAL(15,2) DEFAULT NULL;

-- Modifier aussi poids_total pour être cohérent
ALTER TABLE `bordereau` 
MODIFY COLUMN `poids_total` DECIMAL(15,2) NOT NULL DEFAULT '0.00';

-- Vérifier les modifications
DESCRIBE bordereau;
