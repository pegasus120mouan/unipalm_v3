<?php
// Génération d'un PDF d'historique des financements pour un agent sur une période donnée

ob_clean();

require_once '../inc/functions/connexion.php';
require('../fpdf/fpdf.php');

$id_agent = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$date_debut = isset($_GET['date_debut']) ? $_GET['date_debut'] : '';
$date_fin   = isset($_GET['date_fin']) ? $_GET['date_fin'] : '';

if ($id_agent <= 0 || empty($date_debut) || empty($date_fin)) {
    die('Paramètres invalides.');
}

// Normaliser les dates (on inclut toute la journée de fin)
$debut = $date_debut . ' 00:00:00';
$fin   = $date_fin . ' 23:59:59';

// Infos agent
$sql_agent = "SELECT a.*, CONCAT(a.nom, ' ', a.prenom) AS nom_complet,
                     ce.nom AS chef_nom, ce.prenoms AS chef_prenoms
              FROM agents a
              LEFT JOIN chef_equipe ce ON a.id_chef = ce.id_chef
              WHERE a.id_agent = :id_agent AND a.date_suppression IS NULL";
$stmt_agent = $conn->prepare($sql_agent);
$stmt_agent->bindValue(':id_agent', $id_agent, PDO::PARAM_INT);
$stmt_agent->execute();
$agent = $stmt_agent->fetch(PDO::FETCH_ASSOC);
if (!$agent) {
    die('Agent introuvable.');
}

// Financements sur la période
$sql_fin = "SELECT Numero_financement, montant, motif, date_financement,
                   CASE 
                       WHEN montant > 0 THEN 'Financement accordé'
                       WHEN montant < 0 THEN 'Remboursement'
                       ELSE 'Neutre'
                   END as type_operation
            FROM financement
            WHERE id_agent = :id_agent_fin
              AND date_financement BETWEEN :debut_fin AND :fin_fin
            ORDER BY date_financement DESC, Numero_financement DESC";
$stmt_f = $conn->prepare($sql_fin);
$stmt_f->bindValue(':id_agent_fin', $id_agent, PDO::PARAM_INT);
$stmt_f->bindValue(':debut_fin', $debut);
$stmt_f->bindValue(':fin_fin', $fin);
$stmt_f->execute();
$financements = $stmt_f->fetchAll(PDO::FETCH_ASSOC) ?: [];

// Calculs des totaux
$total_financements = 0;
$total_remboursements = 0;
$solde_periode = 0;

foreach ($financements as $f) {
    if ($f['montant'] > 0) {
        $total_financements += $f['montant'];
    } else {
        $total_remboursements += abs($f['montant']);
    }
    $solde_periode += $f['montant'];
}

// =========================
// Génération du PDF
// =========================

class FinancementPDF extends FPDF {
    function Header() {
        // Logo si disponible
        if (file_exists('../dist/img/logo.png')) {
            $this->Image('../dist/img/logo.png', 10, 8, 25);
        }
        // Décaler le curseur à droite du logo
        $this->SetXY(40, 10);

        // Ligne 1 : Nom de la coopérative
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 7, iconv('UTF-8', 'windows-1252', 'UNIPALM COOP'), 0, 1, 'C');

        // Ligne 2 : Sous-titre
        $this->SetFont('Arial', '', 11);
        $this->Cell(0, 6, iconv('UTF-8', 'windows-1252', 'Historique des financements'), 0, 1, 'C');

        $this->Ln(5);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

$pdf = new FinancementPDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 20);

// Informations générales
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, iconv('UTF-8', 'windows-1252', 'HISTORIQUE DES FINANCEMENTS'), 0, 1, 'C');
$pdf->Ln(3);

// Infos agent et période
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(40, 6, iconv('UTF-8', 'windows-1252', 'Agent :'), 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, iconv('UTF-8', 'windows-1252', $agent['nom_complet']), 0, 1);

if (!empty($agent['chef_nom'])) {
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(40, 6, iconv('UTF-8', 'windows-1252', 'Chef d\'équipe :'), 0, 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, iconv('UTF-8', 'windows-1252', $agent['chef_nom'] . ' ' . $agent['chef_prenoms']), 0, 1);
}

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(40, 6, iconv('UTF-8', 'windows-1252', 'Période :'), 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, iconv('UTF-8', 'windows-1252', 'Du ' . date('d/m/Y', strtotime($date_debut)) . ' au ' . date('d/m/Y', strtotime($date_fin))), 0, 1);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(40, 6, iconv('UTF-8', 'windows-1252', 'Date d\'édition :'), 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, iconv('UTF-8', 'windows-1252', date('d/m/Y à H:i')), 0, 1);

$pdf->Ln(5);

// Résumé financier
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 8, iconv('UTF-8', 'windows-1252', 'RÉSUMÉ FINANCIER'), 0, 1, 'C');
$pdf->Ln(2);

// Tableau résumé
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(60, 7, iconv('UTF-8', 'windows-1252', 'Type'), 1, 0, 'C');
$pdf->Cell(60, 7, iconv('UTF-8', 'windows-1252', 'Montant (FCFA)'), 1, 0, 'C');
$pdf->Cell(60, 7, iconv('UTF-8', 'windows-1252', 'Nombre d\'opérations'), 1, 1, 'C');

$pdf->SetFont('Arial', '', 9);

// Financements accordés
$nb_financements = count(array_filter($financements, function($f) { return $f['montant'] > 0; }));
$pdf->Cell(60, 6, iconv('UTF-8', 'windows-1252', 'Financements accordés'), 1, 0);
$pdf->Cell(60, 6, number_format($total_financements, 0, ',', ' '), 1, 0, 'R');
$pdf->Cell(60, 6, $nb_financements, 1, 1, 'C');

// Remboursements
$nb_remboursements = count(array_filter($financements, function($f) { return $f['montant'] < 0; }));
$pdf->Cell(60, 6, iconv('UTF-8', 'windows-1252', 'Remboursements'), 1, 0);
$pdf->Cell(60, 6, number_format($total_remboursements, 0, ',', ' '), 1, 0, 'R');
$pdf->Cell(60, 6, $nb_remboursements, 1, 1, 'C');

// Solde de la période
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(60, 6, iconv('UTF-8', 'windows-1252', 'Solde de la période'), 1, 0);
$pdf->Cell(60, 6, number_format($solde_periode, 0, ',', ' '), 1, 0, 'R');
$pdf->Cell(60, 6, count($financements), 1, 1, 'C');

$pdf->Ln(8);

// Détail des opérations
if (!empty($financements)) {
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 8, iconv('UTF-8', 'windows-1252', 'DÉTAIL DES OPÉRATIONS'), 0, 1, 'C');
    $pdf->Ln(2);

    // En-têtes du tableau détaillé
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(20, 7, iconv('UTF-8', 'windows-1252', 'Date'), 1, 0, 'C');
    $pdf->Cell(25, 7, iconv('UTF-8', 'windows-1252', 'Numéro'), 1, 0, 'C');
    $pdf->Cell(25, 7, iconv('UTF-8', 'windows-1252', 'Type'), 1, 0, 'C');
    $pdf->Cell(30, 7, iconv('UTF-8', 'windows-1252', 'Montant (FCFA)'), 1, 0, 'C');
    $pdf->Cell(80, 7, iconv('UTF-8', 'windows-1252', 'Motif'), 1, 1, 'C');

    $pdf->SetFont('Arial', '', 8);

    foreach ($financements as $f) {
        // Vérifier si on a assez de place pour une nouvelle ligne
        if ($pdf->GetY() > 250) {
            $pdf->AddPage();
            // Répéter les en-têtes
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(20, 7, iconv('UTF-8', 'windows-1252', 'Date'), 1, 0, 'C');
            $pdf->Cell(25, 7, iconv('UTF-8', 'windows-1252', 'Numéro'), 1, 0, 'C');
            $pdf->Cell(25, 7, iconv('UTF-8', 'windows-1252', 'Type'), 1, 0, 'C');
            $pdf->Cell(30, 7, iconv('UTF-8', 'windows-1252', 'Montant (FCFA)'), 1, 0, 'C');
            $pdf->Cell(80, 7, iconv('UTF-8', 'windows-1252', 'Motif'), 1, 1, 'C');
            $pdf->SetFont('Arial', '', 8);
        }

        $date_formatted = date('d/m/Y', strtotime($f['date_financement']));
        $type_operation = $f['montant'] > 0 ? 'Financement' : 'Remboursement';
        $montant_formatted = ($f['montant'] > 0 ? '+' : '') . number_format($f['montant'], 0, ',', ' ');
        $motif = !empty($f['motif']) ? $f['motif'] : 'Aucun motif';

        // Limiter la longueur du motif
        if (strlen($motif) > 50) {
            $motif = substr($motif, 0, 47) . '...';
        }

        $pdf->Cell(20, 6, $date_formatted, 1, 0, 'C');
        $pdf->Cell(25, 6, $f['Numero_financement'], 1, 0, 'C');
        $pdf->Cell(25, 6, iconv('UTF-8', 'windows-1252', $type_operation), 1, 0, 'C');
        $pdf->Cell(30, 6, $montant_formatted, 1, 0, 'R');
        $pdf->Cell(80, 6, iconv('UTF-8', 'windows-1252', $motif), 1, 1, 'L');
    }
} else {
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Cell(0, 10, iconv('UTF-8', 'windows-1252', 'Aucun financement trouvé pour cette période.'), 0, 1, 'C');
}

// Signature et date
$pdf->Ln(15);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(0, 6, iconv('UTF-8', 'windows-1252', 'Document généré le ' . date('d/m/Y à H:i') . ' par le système UniPalm'), 0, 1, 'C');

// Sortie du PDF
$filename = 'Historique_Financements_' . $agent['nom_complet'] . '_' . $date_debut . '_' . $date_fin . '.pdf';
$filename = str_replace(' ', '_', $filename);

// Afficher le PDF dans le navigateur au lieu de le télécharger
$pdf->Output('I', $filename);
?>
