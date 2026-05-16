<?php
ob_start();
require_once '../inc/functions/connexion.php';
require('../fpdf/fpdf.php');
ob_end_clean();

// Récupérer l'ID de l'agent
$id_agent = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_agent <= 0) {
    die('ID agent invalide');
}

// Récupérer les informations de l'agent
$stmt = $conn->prepare("
    SELECT 
        agents.id_agent,
        agents.numero_agent,
        agents.nom,
        agents.prenom,
        agents.contact,
        agents.avatar,
        agents.date_ajout,
        CONCAT(chef_equipe.nom, ' ', chef_equipe.prenoms) AS chef_equipe
    FROM agents
    LEFT JOIN chef_equipe ON agents.id_chef = chef_equipe.id_chef
    WHERE agents.id_agent = ? AND agents.date_suppression IS NULL
");
$stmt->execute([$id_agent]);
$agent = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$agent) {
    die('Agent non trouvé');
}

// Chemin de la photo
$avatarFile = !empty($agent['avatar']) ? $agent['avatar'] : 'agents.png';
$photoPath = '../dossiers_images/' . $avatarFile;

// Vérifier si la photo existe
if (!file_exists($photoPath)) {
    $photoPath = '../dossiers_images/agents.png';
}

// Créer le PDF
class PDF extends FPDF {
    // Désactiver le header et footer par défaut
    function Header() {}
    function Footer() {}
    
    function Ellipse($x, $y, $rx, $ry, $style = 'D') {
        if ($style == 'F')
            $op = 'f';
        elseif ($style == 'FD' || $style == 'DF')
            $op = 'B';
        else
            $op = 'S';
        
        $lx = 4/3 * (M_SQRT2 - 1) * $rx;
        $ly = 4/3 * (M_SQRT2 - 1) * $ry;
        $k = $this->k;
        $h = $this->h;
        
        $this->_out(sprintf('%.2F %.2F m %.2F %.2F %.2F %.2F %.2F %.2F c',
            ($x + $rx) * $k, ($h - $y) * $k,
            ($x + $rx) * $k, ($h - ($y - $ly)) * $k,
            ($x + $lx) * $k, ($h - ($y - $ry)) * $k,
            $x * $k, ($h - ($y - $ry)) * $k));
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c',
            ($x - $lx) * $k, ($h - ($y - $ry)) * $k,
            ($x - $rx) * $k, ($h - ($y - $ly)) * $k,
            ($x - $rx) * $k, ($h - $y) * $k));
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c',
            ($x - $rx) * $k, ($h - ($y + $ly)) * $k,
            ($x - $lx) * $k, ($h - ($y + $ry)) * $k,
            $x * $k, ($h - ($y + $ry)) * $k));
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c %s',
            ($x + $lx) * $k, ($h - ($y + $ry)) * $k,
            ($x + $rx) * $k, ($h - ($y + $ly)) * $k,
            ($x + $rx) * $k, ($h - $y) * $k,
            $op));
    }
    
    function Circle($x, $y, $r, $style = 'D') {
        $this->Ellipse($x, $y, $r, $r, $style);
    }
    
    function Arc($x, $y, $r, $startAngle, $endAngle, $style = 'D') {
        // Dessiner un arc de cercle
        $startAngle = deg2rad($startAngle);
        $endAngle = deg2rad($endAngle);
        
        $k = $this->k;
        $h = $this->h;
        
        if ($style == 'F')
            $op = 'f';
        elseif ($style == 'FD' || $style == 'DF')
            $op = 'B';
        else
            $op = 'S';
        
        // Point de départ
        $x1 = $x + $r * cos($startAngle);
        $y1 = $y + $r * sin($startAngle);
        $this->_out(sprintf('%.2F %.2F m', $x1 * $k, ($h - $y1) * $k));
        
        // Dessiner l'arc par segments
        $segments = 36;
        $angleStep = ($endAngle - $startAngle) / $segments;
        
        for ($i = 1; $i <= $segments; $i++) {
            $angle = $startAngle + $i * $angleStep;
            $x2 = $x + $r * cos($angle);
            $y2 = $y + $r * sin($angle);
            $this->_out(sprintf('%.2F %.2F l', $x2 * $k, ($h - $y2) * $k));
        }
        
        $this->_out($op);
    }
}

// Format carte de crédit standard (85.6mm x 53.98mm)
$cardW = 86;
$cardH = 54;
$pdf = new PDF('L', 'mm', array($cardW, $cardH));
$pdf->AddPage();
$pdf->SetAutoPageBreak(false);

// Couleurs VERT
$vertFonce = array(27, 94, 32);    // Vert foncé
$vertClair = array(76, 175, 80);   // Vert clair
$blanc = array(255, 255, 255);
$gris = array(100, 100, 100);
$noir = array(0, 0, 0);
$or = array(218, 165, 32);

// ========== FOND ==========
// Partie blanche (bas)
$pdf->SetFillColor(255, 255, 255);
$pdf->Rect(0, 0, $cardW, $cardH, 'F');

// Partie blanche (haut - bandeau avec logo)
$pdf->SetFillColor(255, 255, 255);
$pdf->Rect(0, 0, $cardW, 7, 'F');

// Partie verte foncée (milieu)
$pdf->SetFillColor($vertFonce[0], $vertFonce[1], $vertFonce[2]);
$pdf->Rect(0, 7, $cardW, 18, 'F');

// ========== LOGO UNIPALM (image) ==========
$logoPath = '../dist/img/cartes/logo.png';
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, $cardW - 18, 1, 16, 16);
} else {
    // Fallback texte si logo non trouvé
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY($cardW - 25, 2);
    $pdf->Cell(23, 4, 'UNIPALM', 0, 0, 'R');
}

// ========== AGENT ==========
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetXY($cardW - 25, 12);
$pdf->Cell(23, 5, 'AGENT', 0, 0, 'R');

// ========== NOM ET ÉQUIPE ==========
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetXY(28, 10);
$nomComplet = mb_convert_encoding(strtoupper($agent['nom']) . ' ' . ucfirst(strtolower($agent['prenom'])), 'ISO-8859-1', 'UTF-8');
$pdf->Cell(35, 5, $nomComplet, 0, 0, 'L');

$pdf->SetFont('Arial', '', 8);
$pdf->SetXY(28, 16);
$chefEquipe = $agent['chef_equipe'] ?? 'Non assigné';
$pdf->Cell(35, 4, mb_convert_encoding('Equipe: ' . $chefEquipe, 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');

// ========== PHOTO AVEC BORDURE RONDE ==========
$photoSize = 20;
$photoCenterX = 14;
$photoCenterY = 17;
$photoRadius = $photoSize / 2;

// D'abord, dessiner un cercle vert foncé de fond (pour éviter le blanc)
$pdf->SetFillColor($vertFonce[0], $vertFonce[1], $vertFonce[2]);
$pdf->Circle($photoCenterX, $photoCenterY, $photoRadius + 0.5, 'F');

// Dessiner la photo
if (file_exists($photoPath)) {
    $ext = strtolower(pathinfo($photoPath, PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
        $pdf->Image($photoPath, $photoCenterX - $photoRadius, $photoCenterY - $photoRadius, $photoSize, $photoSize);
    }
}

// Masquer les coins avec la couleur du fond appropriée
$pdf->SetFillColor($vertFonce[0], $vertFonce[1], $vertFonce[2]);
$cornerSize = 6;

// Coins supérieurs (vert foncé)
$pdf->Rect($photoCenterX - $photoRadius - 1, $photoCenterY - $photoRadius - 1, $cornerSize, $cornerSize, 'F');
$pdf->Rect($photoCenterX + $photoRadius - $cornerSize + 1, $photoCenterY - $photoRadius - 1, $cornerSize + 1, $cornerSize, 'F');

// Coins inférieurs (blanc car zone blanche)
$pdf->SetFillColor(255, 255, 255);
$pdf->Rect($photoCenterX - $photoRadius - 1, $photoCenterY + $photoRadius - $cornerSize + 1, $cornerSize, $cornerSize + 1, 'F');
$pdf->Rect($photoCenterX + $photoRadius - $cornerSize + 1, $photoCenterY + $photoRadius - $cornerSize + 1, $cornerSize + 1, $cornerSize + 1, 'F');

// Masquer avec des arcs épais
$pdf->SetDrawColor($vertFonce[0], $vertFonce[1], $vertFonce[2]);
$pdf->SetLineWidth(5);
$pdf->Arc($photoCenterX, $photoCenterY, $photoRadius + 2.5, 180, 360, 'D');

$pdf->SetDrawColor(255, 255, 255);
$pdf->SetLineWidth(5);
$pdf->Arc($photoCenterX, $photoCenterY, $photoRadius + 2.5, 0, 180, 'D');

// Bordure dorée finale
$pdf->SetDrawColor(218, 165, 32);
$pdf->SetLineWidth(1.5);
$pdf->Circle($photoCenterX, $photoCenterY, $photoRadius + 0.5, 'D');

// ========== INFORMATIONS (partie blanche) ==========
$pdf->SetTextColor($noir[0], $noir[1], $noir[2]);
$infoY = 30;
$col1X = 5;
$col2X = 35;

// ID No
$pdf->SetFont('Arial', 'B', 7);
$pdf->SetXY($col1X, $infoY);
$pdf->Cell(28, 3, 'ID No', 0, 0, 'L');

$pdf->SetFont('Arial', '', 7);
$pdf->SetXY($col1X, $infoY + 3);
$pdf->Cell(28, 3, $agent['numero_agent'], 0, 0, 'L');

// Contact
$pdf->SetFont('Arial', 'B', 7);
$pdf->SetXY($col1X, $infoY + 8);
$pdf->Cell(28, 3, 'Contact', 0, 0, 'L');

$pdf->SetFont('Arial', '', 7);
$pdf->SetXY($col1X, $infoY + 11);
$pdf->Cell(28, 3, $agent['contact'], 0, 0, 'L');

// Joined Date
$pdf->SetFont('Arial', 'B', 7);
$pdf->SetXY($col2X, $infoY);
$pdf->Cell(25, 3, 'Joined Date', 0, 0, 'L');

$pdf->SetFont('Arial', '', 7);
$pdf->SetXY($col2X, $infoY + 3);
$dateCreation = date('d/m/Y', strtotime($agent['date_ajout']));
$pdf->Cell(25, 3, $dateCreation, 0, 0, 'L');

// Expire Date
$pdf->SetFont('Arial', 'B', 7);
$pdf->SetXY($col2X, $infoY + 8);
$pdf->Cell(25, 3, 'Expire Date', 0, 0, 'L');

$pdf->SetFont('Arial', '', 7);
$pdf->SetXY($col2X, $infoY + 11);
$dateExpire = date('d/m/Y', strtotime($agent['date_ajout'] . ' +1 year'));
$pdf->Cell(25, 3, $dateExpire, 0, 0, 'L');

// ========== QR CODE ==========
$qrX = 64;
$qrY = 30;
$qrSize = 18;

// URL de vérification
$verificationUrl = 'https://unipalm.ci/verification_agent.php?code=' . urlencode($agent['numero_agent']);

// Générer le QR Code via API Google Charts
$qrCodeUrl = 'https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=' . urlencode($verificationUrl) . '&choe=UTF-8';

// Télécharger le QR code temporairement
$qrCodePath = '../dossiers_images/temp_qr_agent_' . $agent['id_agent'] . '.png';
$qrContent = @file_get_contents($qrCodeUrl);
if ($qrContent) {
    file_put_contents($qrCodePath, $qrContent);
    $pdf->Image($qrCodePath, $qrX, $qrY, $qrSize, $qrSize);
    // Supprimer le fichier temporaire
    unlink($qrCodePath);
}

// Numéro sous le QR code
$pdf->SetFont('Arial', '', 5);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY($qrX, $qrY + $qrSize + 0.5);
$pdf->Cell($qrSize, 3, $agent['numero_agent'], 0, 0, 'C');

// ========== LIGNE DE SÉPARATION DÉCORATIVE ==========
$pdf->SetDrawColor($vertFonce[0], $vertFonce[1], $vertFonce[2]);
$pdf->SetLineWidth(0.3);
$pdf->Line(5, 28, $cardW - 5, 28);

// Sortie du PDF
$pdf->Output('I', 'Carte_Agent_' . $agent['numero_agent'] . '.pdf');
