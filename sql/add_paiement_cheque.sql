-- Ajouter l'option 'cheque' aux sources de paiement existantes

-- Modifier la table recus_paiements
ALTER TABLE recus_paiements 
MODIFY COLUMN source_paiement ENUM('transactions', 'financement', 'cheque') NOT NULL;

-- Ajouter la colonne numero_cheque
ALTER TABLE recus_paiements 
ADD COLUMN numero_cheque VARCHAR(50) NULL AFTER source_paiement;

-- Modifier la table transactions si elle existe
ALTER TABLE transactions 
ADD COLUMN numero_cheque VARCHAR(50) NULL AFTER type_transaction;

-- Créer un index sur numero_cheque pour les recherches
ALTER TABLE recus_paiements 
ADD INDEX idx_numero_cheque (numero_cheque);

-- Vérifier les modifications
SELECT 
    COLUMN_NAME, 
    COLUMN_TYPE, 
    IS_NULLABLE, 
    COLUMN_DEFAULT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'recus_paiements' 
AND TABLE_SCHEMA = DATABASE()
ORDER BY ORDINAL_POSITION;
