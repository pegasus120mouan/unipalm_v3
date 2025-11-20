<?php
// Génération d'un PDF d'historique des transactions (paiements + financements) pour un agent
// sur une période donnée

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
$sql_agent = "SELECT a.*, CONCAT(a.nom, ' ', a.prenom) AS nom_complet
              FROM agents a
              WHERE a.id_agent = :id_agent AND a.date_suppression IS NULL";
$stmt_agent = $conn->prepare($sql_agent);
$stmt_agent->bindValue(':id_agent', $id_agent, PDO::PARAM_INT);
$stmt_agent->execute();
$agent = $stmt_agent->fetch(PDO::FETCH_ASSOC);
if (!$agent) {
    die('Agent introuvable.');
}

// Paiements (recus_paiements) sur la période
$sql_paiements = "SELECT numero_recu, type_document, numero_document,
                         montant_total, montant_paye, montant_precedent, reste_a_payer,
                         nom_usine, matricule_vehicule, nom_caissier, source_paiement,
                         date_creation
                  FROM recus_paiements
                  WHERE id_agent = :id_agent
                    AND date_creation BETWEEN :debut AND :fin
                  ORDER BY date_creation DESC, numero_recu DESC";
$stmt_p = $conn->prepare($sql_paiements);
$stmt_p->bindValue(':id_agent', $id_agent, PDO::PARAM_INT);
$stmt_p->bindValue(':debut', $debut);
$stmt_p->bindValue(':fin', $fin);
$stmt_p->execute();
$paiements = $stmt_p->fetchAll(PDO::FETCH_ASSOC) ?: [];

// Financements sur la période
$sql_fin = "SELECT Numero_financement, montant, motif, date_financement
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

// =========================
// Génération du PDF
// =========================

class HistoriquePDF extends FPDF {
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
        $this->Cell(0, 6, iconv('UTF-8', 'windows-1252', 'Historique des transactions'), 0, 1, 'C');

        $this->Ln(5);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

$pdf = new HistoriquePDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 20);

// Informations générales
$pdf->SetFont('Arial', '', 11);
$pdf->Ln(4); // descendre un peu sous l'entête
$pdf->Cell(0, 6, iconv('UTF-8', 'windows-1252', 'Agent : ' . $agent['nom_complet']), 0, 1, 'L');
$pdf->Cell(0, 6, iconv('UTF-8', 'windows-1252', 'Période : du ') . date('d/m/Y', strtotime($date_debut)) . iconv('UTF-8', 'windows-1252', ' au ') . date('d/m/Y', strtotime($date_fin)), 0, 1, 'L');
$pdf->Ln(4);

// =========================
// Section Paiements
// =========================

$pdf->SetFont('Arial', 'B', 12);
$pdf->SetFillColor(230, 240, 255);
$pdf->Cell(0, 8, iconv('UTF-8', 'windows-1252', '1. Paiements enregistrés'), 0, 1, 'L', true);
$pdf->Ln(2);

if (!empty($paiements)) {
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(30, 7, iconv('UTF-8', 'windows-1252', 'Date'), 1, 0, 'C', true);
    $pdf->Cell(25, 7, iconv('UTF-8', 'windows-1252', 'N° Reçu'), 1, 0, 'C', true);
    $pdf->Cell(45, 7, iconv('UTF-8', 'windows-1252', 'Document'), 1, 0, 'C', true);
    $pdf->Cell(25, 7, iconv('UTF-8', 'windows-1252', 'Payé'), 1, 0, 'C', true);
    $pdf->Cell(25, 7, iconv('UTF-8', 'windows-1252', 'Total'), 1, 0, 'C', true);
    $pdf->Cell(25, 7, iconv('UTF-8', 'windows-1252', 'Reste'), 1, 0, 'C', true);
    $pdf->Cell(15, 7, iconv('UTF-8', 'windows-1252', 'Src'), 1, 1, 'C', true);

    $pdf->SetFont('Arial', '', 8);
    $total_paye = 0;

    foreach ($paiements as $p) {
        $dateStr = !empty($p['date_creation']) ? date('d/m/Y H:i', strtotime($p['date_creation'])) : '';
        // Document : uniquement le numéro (sans préfixe bordereau / ticket)
        $doc = !empty($p['numero_document']) ? $p['numero_document'] : '';
        $montantPaye = (float)$p['montant_paye'];
        $montantTotal = (float)$p['montant_total'];
        $reste = (float)$p['reste_a_payer'];
        $src = ($p['source_paiement'] === 'financement') ? 'FIN' : 
               (($p['source_paiement'] === 'cheque') ? 'CHEQUE' : 'CAIS');

        $total_paye += $montantPaye;

        $pdf->Cell(30, 6, $dateStr, 1, 0, 'L');
        $pdf->Cell(25, 6, $p['numero_recu'], 1, 0, 'L');
        $pdf->Cell(45, 6, iconv('UTF-8', 'windows-1252', substr($doc, 0, 28)), 1, 0, 'L');
        $pdf->Cell(25, 6, number_format($montantPaye, 0, ',', ' '), 1, 0, 'R');
        $pdf->Cell(25, 6, number_format($montantTotal, 0, ',', ' '), 1, 0, 'R');
        $pdf->Cell(25, 6, number_format($reste, 0, ',', ' '), 1, 0, 'R');
        $pdf->Cell(15, 6, $src, 1, 1, 'C');
    }

    // Total des montants payés
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(100, 7, iconv('UTF-8', 'windows-1252', 'Total montants payés'), 1, 0, 'R');
    $pdf->Cell(25, 7, number_format($total_paye, 0, ',', ' '), 1, 0, 'R');
    $pdf->Cell(65, 7, '', 1, 1, 'R');
} else {
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->Ln(2);
    $pdf->Cell(0, 6, iconv('UTF-8', 'windows-1252', 'Aucun paiement enregistré pour cette période.'), 0, 1, 'L');
}

$pdf->Ln(8);

// =========================
// Section Financements
// =========================

$pdf->SetFont('Arial', 'B', 12);
$pdf->SetFillColor(230, 240, 255);
$pdf->Cell(0, 8, iconv('UTF-8', 'windows-1252', '2. Financements'), 0, 1, 'L', true);
$pdf->Ln(2);

if (!empty($financements)) {
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(30, 7, iconv('UTF-8', 'windows-1252', 'Date'), 1, 0, 'C', true);
    $pdf->Cell(30, 7, iconv('UTF-8', 'windows-1252', 'N° Financement'), 1, 0, 'C', true);
    $pdf->Cell(30, 7, iconv('UTF-8', 'windows-1252', 'Montant'), 1, 0, 'C', true);
    $pdf->Cell(100, 7, iconv('UTF-8', 'windows-1252', 'Motif'), 1, 1, 'C', true);

    $pdf->SetFont('Arial', '', 8);
    $total_pos = 0;
    $total_neg = 0;

    foreach ($financements as $f) {
        $dateStr = !empty($f['date_financement']) ? date('d/m/Y H:i', strtotime($f['date_financement'])) : '';
        $montant = (float)$f['montant'];
        if ($montant >= 0) {
            $total_pos += $montant;
        } else {
            $total_neg += $montant;
        }

        $pdf->Cell(30, 6, $dateStr, 1, 0, 'L');
        $pdf->Cell(30, 6, $f['Numero_financement'], 1, 0, 'L');
        $pdf->Cell(30, 6, number_format($montant, 0, ',', ' '), 1, 0, 'R');
        $pdf->Cell(100, 6, iconv('UTF-8', 'windows-1252', substr($f['motif'], 0, 55)), 1, 1, 'L');
    }

    // Totaux financements
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(60, 7, iconv('UTF-8', 'windows-1252', 'Total financements (+)'), 1, 0, 'R');
    $pdf->Cell(30, 7, number_format($total_pos, 0, ',', ' '), 1, 0, 'R');
    $pdf->Cell(100, 7, '', 1, 1, 'R');

    $pdf->Cell(60, 7, iconv('UTF-8', 'windows-1252', 'Total remboursements (-)'), 1, 0, 'R');
    $pdf->Cell(30, 7, number_format($total_neg, 0, ',', ' '), 1, 0, 'R');
    $pdf->Cell(100, 7, '', 1, 1, 'R');
} else {
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->Ln(2);
    $pdf->Cell(0, 6, iconv('UTF-8', 'windows-1252', 'Aucun mouvement de financement pour cette période.'), 0, 1, 'L');
}

// Sortie du PDF (affichage dans le navigateur, téléchargeable / imprimable)
$nomFichier = 'Historique_transactions_agent_' . $id_agent . '_' . $date_debut . '_au_' . $date_fin . '.pdf';
$pdf->Output('I', $nomFichier);
exit;
