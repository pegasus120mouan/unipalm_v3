<?php
require_once '../inc/functions/connexion.php';
require_once '../inc/functions/requete/requete_bordereaux.php';

// Récupérer les paramètres de recherche
$search_numero = $_GET['numero'] ?? null;
$search_agent = $_GET['agent'] ?? null;
$search_date_debut = $_GET['date_debut'] ?? null;
$search_date_fin = $_GET['date_fin'] ?? null;

// Récupérer tous les bordereaux (sans pagination pour l'export)
$filters = [
    'numero' => $search_numero,
    'agent' => $search_agent,
    'date_debut' => $search_date_debut,
    'date_fin' => $search_date_fin
];

// Récupérer les bordereaux avec une limite élevée pour l'export
$result = getBordereaux($conn, 1, 10000, $filters);
$bordereaux = $result['data'];

// Définir les en-têtes pour le téléchargement Excel (CSV)
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=bordereaux_export_' . date('Y-m-d_H-i-s') . '.csv');

// Créer le fichier CSV
$output = fopen('php://output', 'w');

// Ajouter le BOM UTF-8 pour Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// En-têtes des colonnes
fputcsv($output, [
    'Date de génération',
    'Numéro Bordereau',
    'Nombre de tickets',
    'Date Début',
    'Date Fin',
    'Poids Total (kg)',
    'Montant Total (FCFA)',
    'Montant Payé (FCFA)',
    'Reste à Payer (FCFA)',
    'Statut',
    'Agent',
    'Contact Agent',
    'Date Validation'
], ';');

// Fonction pour formater la date
function formatDateExport($date) {
    return $date ? date('d/m/Y', strtotime($date)) : '';
}

// Fonction pour formater les montants
function formatMontant($montant) {
    return number_format($montant ?? 0, 0, ',', ' ');
}

// Données
foreach ($bordereaux as $bordereau) {
    fputcsv($output, [
        formatDateExport($bordereau['date_creation_bordereau']),
        $bordereau['numero_bordereau'],
        $bordereau['nombre_tickets'],
        formatDateExport($bordereau['date_debut']),
        formatDateExport($bordereau['date_fin']),
        number_format($bordereau['poids_total'], 2, ',', ' '),
        formatMontant($bordereau['montant_total']),
        formatMontant($bordereau['montant_payer']),
        formatMontant($bordereau['montant_reste'] ?? $bordereau['montant_total']),
        ucfirst($bordereau['statut_bordereau']),
        $bordereau['nom_complet_agent'],
        $bordereau['contact'],
        formatDateExport($bordereau['date_validation_boss'])
    ], ';');
}

fclose($output);
exit;
?>
