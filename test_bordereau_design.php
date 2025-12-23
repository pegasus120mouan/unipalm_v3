<?php
// Test direct du design du bordereau
require('fpdf/fpdf.php');
require_once 'inc/functions/connexion.php';

// Données de test
$bordereau = [
    'numero_bordereau' => 'TEST123',
    'nom_complet_agent' => 'OUEDRAOGO BARKET',
    'date_debut' => '2025-12-17',
    'date_fin' => '2025-12-23',
    'poids_total' => 19640,
    'created_at' => '2025-12-23 13:42:00'
];
$montant_total_bordereau = 2491360;

class PDF extends FPDF {
    function Header() {
        if (file_exists('dist/img/logo.png')) {
            $this->Image('dist/img/logo.png', 10, 10, 30);
        }
        
        // Titre de l'entreprise en vert
        $this->SetTextColor(0, 128, 0);
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'UNIPALM COOP - CA', 0, 1, 'C');
        
        // Sous-titre en vert clair
        $this->SetTextColor(144, 238, 144);
        $this->SetFont('Arial', '', 11);
        $this->Cell(0, 5, iconv('UTF-8', 'windows-1252', 'Société Coopérative Agricole Unie pour le Palmier'), 0, 1, 'C');
        
        $this->Ln(15);
    }

    function Footer() {
        $this->SetY(-20);
        
        // Ligne verte
        $this->SetDrawColor(144, 238, 144);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        
        // Informations de contact en vert clair
        $this->SetTextColor(144, 238, 144);
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 10, iconv('UTF-8', 'windows-1252', 'Contact: +225 XX XX XX XX XX - Email: contact@unipalm.ci'), 0, 0, 'C');
    }
}

$pdf = new PDF();
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 35);

// Titre du bordereau
$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 10, iconv('UTF-8', 'windows-1252', 'BORDEREAU DE DÉCHARGEMENT N° ') . $bordereau['numero_bordereau'], 0, 1, 'C');
$pdf->Ln(5);

// Section informations du bordereau - Design professionnel
// En-tête avec fond coloré
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetFillColor(52, 73, 94); // Bleu foncé professionnel
$pdf->SetTextColor(255, 255, 255); // Texte blanc
$pdf->Cell(190, 10, iconv('UTF-8', 'windows-1252', 'INFORMATIONS DU BORDEREAU'), 1, 1, 'C', true);

// Réinitialiser les couleurs pour le contenu
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFillColor(248, 249, 250); // Gris très clair pour alternance

// Informations en deux colonnes pour un look plus professionnel
$pdf->SetFont('Arial', 'B', 10);

// Ligne 1: Agent et Période
$pdf->Cell(95, 8, iconv('UTF-8', 'windows-1252', 'AGENT RESPONSABLE'), 'LTB', 0, 'L', true);
$pdf->Cell(95, 8, iconv('UTF-8', 'windows-1252', 'PÉRIODE DE COLLECTE'), 'RTB', 1, 'L', true);

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(95, 8, iconv('UTF-8', 'windows-1252', $bordereau['nom_complet_agent']), 'LB', 0, 'L');
$pdf->Cell(95, 8, date('d/m/Y', strtotime($bordereau['date_debut'])) . ' au ' . date('d/m/Y', strtotime($bordereau['date_fin'])), 'RB', 1, 'L');

// Ligne 2: Poids et Montant
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(95, 8, 'POIDS TOTAL COLLECTÉ', 'LTB', 0, 'L', true);
$pdf->Cell(95, 8, 'MONTANT TOTAL', 'RTB', 1, 'L', true);

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(22, 160, 133); // Vert pour le poids
$pdf->Cell(95, 8, number_format($bordereau['poids_total'], 0, ',', ' ') . ' KG', 'LB', 0, 'L');
$pdf->SetTextColor(231, 76, 60); // Rouge pour le montant
$pdf->Cell(95, 8, number_format($montant_total_bordereau, 0, ',', ' ') . ' FCFA', 'RB', 1, 'L');

// Ligne 3: Date de création centrée
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(190, 8, iconv('UTF-8', 'windows-1252', 'DATE DE CRÉATION'), 'LTR', 1, 'C', true);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(190, 8, date('d/m/Y à H:i', strtotime($bordereau['created_at'])), 'LBR', 1, 'C');

$pdf->Output('I', 'Test_Bordereau_Design.pdf');
?>
