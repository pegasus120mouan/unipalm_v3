-- Script SQL pour installer le support des chèques

-- 1. Ajouter la colonne numero_cheque à recus_paiements si elle n'existe pas
ALTER TABLE recus_paiements 
ADD COLUMN IF NOT EXISTS numero_cheque VARCHAR(50) NULL AFTER source_paiement;

-- 2. Créer l'index sur numero_cheque
CREATE INDEX IF NOT EXISTS idx_numero_cheque ON recus_paiements (numero_cheque);

-- 3. Modifier l'ENUM source_paiement pour inclure 'cheque'
ALTER TABLE recus_paiements 
MODIFY COLUMN source_paiement ENUM('transactions', 'financement', 'cheque') NOT NULL;

-- 4. Ajouter la colonne numero_cheque à transactions si elle n'existe pas
ALTER TABLE transactions 
ADD COLUMN IF NOT EXISTS numero_cheque VARCHAR(50) NULL AFTER type_transaction;

-- 5. Vérifier les modifications
SELECT 'Vérification des colonnes ajoutées:' as message;

SELECT 
    COLUMN_NAME, 
    COLUMN_TYPE, 
    IS_NULLABLE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'recus_paiements' 
AND COLUMN_NAME IN ('source_paiement', 'numero_cheque')
AND TABLE_SCHEMA = DATABASE()
ORDER BY ORDINAL_POSITION;

SELECT 
    COLUMN_NAME, 
    COLUMN_TYPE, 
    IS_NULLABLE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'transactions' 
AND COLUMN_NAME = 'numero_cheque'
AND TABLE_SCHEMA = DATABASE();
