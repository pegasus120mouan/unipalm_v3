-- Correction du bordereau BORD-20251117-185-6675
-- Problème : Le reste à payer n'est pas mis à jour après paiement par chèque

-- 1. Vérifier l'état actuel
SELECT 
    'AVANT CORRECTION' as etape,
    numero_bordereau,
    montant_total,
    COALESCE(montant_payer, 0) as montant_payer,
    COALESCE(montant_reste, 0) as montant_reste,
    statut_bordereau
FROM bordereau 
WHERE numero_bordereau = 'BORD-20251117-185-6675';

-- 2. Calculer le total réel payé selon les reçus
SELECT 
    'TOTAL PAYE SELON RECUS' as info,
    COALESCE(SUM(montant_paye), 0) as total_paye_recus,
    COUNT(*) as nb_recus
FROM recus_paiements 
WHERE numero_document = 'BORD-20251117-185-6675' 
AND type_document = 'bordereau';

-- 3. Corriger le bordereau
UPDATE bordereau 
SET montant_payer = (
    SELECT COALESCE(SUM(montant_paye), 0) 
    FROM recus_paiements 
    WHERE numero_document = 'BORD-20251117-185-6675' 
    AND type_document = 'bordereau'
),
montant_reste = montant_total - (
    SELECT COALESCE(SUM(montant_paye), 0) 
    FROM recus_paiements 
    WHERE numero_document = 'BORD-20251117-185-6675' 
    AND type_document = 'bordereau'
),
statut_bordereau = CASE 
    WHEN (montant_total - (
        SELECT COALESCE(SUM(montant_paye), 0) 
        FROM recus_paiements 
        WHERE numero_document = 'BORD-20251117-185-6675' 
        AND type_document = 'bordereau'
    )) <= 0 THEN 'soldé' 
    ELSE 'non soldé' 
END,
date_paie = NOW()
WHERE numero_bordereau = 'BORD-20251117-185-6675';

-- 4. Vérifier après correction
SELECT 
    'APRES CORRECTION' as etape,
    numero_bordereau,
    montant_total,
    COALESCE(montant_payer, 0) as montant_payer,
    COALESCE(montant_reste, 0) as montant_reste,
    statut_bordereau,
    date_paie
FROM bordereau 
WHERE numero_bordereau = 'BORD-20251117-185-6675';

-- 5. Afficher les reçus pour vérification
SELECT 
    'DETAIL RECUS' as info,
    numero_recu,
    montant_paye,
    source_paiement,
    numero_cheque,
    date_creation
FROM recus_paiements 
WHERE numero_document = 'BORD-20251117-185-6675' 
AND type_document = 'bordereau'
ORDER BY date_creation;
