-- Script pour recalculer les montants des bordereaux après correction de la précision DECIMAL
-- Ce script corrige les montants qui ont été tronqués à cause de DECIMAL(10,2)

-- Recalculer tous les montants_total des bordereaux
UPDATE bordereau b 
SET b.montant_total = (
    SELECT COALESCE(SUM(t.prix_unitaire * t.poids), 0)
    FROM tickets t 
    WHERE t.numero_bordereau = b.numero_bordereau
      AND t.prix_unitaire IS NOT NULL 
      AND t.prix_unitaire > 0
),
b.poids_total = (
    SELECT COALESCE(SUM(t.poids), 0)
    FROM tickets t 
    WHERE t.numero_bordereau = b.numero_bordereau
);

-- Recalculer les montants restants
UPDATE bordereau 
SET montant_reste = montant_total - COALESCE(montant_payer, 0)
WHERE montant_reste IS NOT NULL;

-- Mettre à jour le statut en fonction du nouveau montant restant
UPDATE bordereau 
SET statut_bordereau = CASE 
    WHEN COALESCE(montant_reste, montant_total) <= 0 THEN 'soldé'
    ELSE 'non soldé'
END;

-- Afficher les bordereaux avec de gros montants pour vérification
SELECT 
    numero_bordereau,
    montant_total,
    poids_total,
    montant_payer,
    montant_reste,
    statut_bordereau
FROM bordereau 
WHERE montant_total > 1000000
ORDER BY montant_total DESC;
