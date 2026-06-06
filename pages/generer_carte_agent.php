<?php
require_once '../inc/functions/connexion.php';
require('../fpdf/fpdf.php');

$id_agent = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_agent <= 0) {
    die('ID agent invalide');
}

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

$avatarFile = !empty($agent['avatar']) ? $agent['avatar'] : 'agents.png';
$photoPath = '../dossiers_images/' . $avatarFile;
if (!file_exists($photoPath)) {
    $photoPath = '../dossiers_images/agents.png';
}

function enc(string $text): string
{
    return mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
}

/**
 * Logo Palmci atténué pour filigrane (repli sur logo UniPalm).
 */
function resolveFiligranePath(): ?string
{
    $candidates = [
        '../dist/img/cartes/palmci.png',
        '../dist/img/cartes/palmci.jpg',
        '../dist/img/cartes/logo.png',
    ];

    foreach ($candidates as $path) {
        if (is_file($path)) {
            return $path;
        }
    }

    return null;
}

function makeFiligrane(string $srcPath, float $opacity = 0.10): ?string
{
    if (!is_file($srcPath)) {
        return null;
    }

    if (!extension_loaded('gd')) {
        return $srcPath;
    }

    $ext = strtolower(pathinfo($srcPath, PATHINFO_EXTENSION));
    if ($ext === 'png') {
        $img = @imagecreatefrompng($srcPath);
    } elseif (in_array($ext, ['jpg', 'jpeg'], true)) {
        $img = @imagecreatefromjpeg($srcPath);
    } else {
        return $srcPath;
    }

    if (!$img) {
        return $srcPath;
    }

    $w = imagesx($img);
    $h = imagesy($img);
    $canvas = imagecreatetruecolor($w, $h);
    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);
    $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
    imagefilledrectangle($canvas, 0, 0, $w, $h, $transparent);
    imagealphablending($canvas, true);
    imagecopymerge($canvas, $img, 0, 0, 0, 0, $w, $h, (int) max(1, min(100, round($opacity * 100))));

    $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'filigrane_agent_' . md5($srcPath . $opacity) . '.png';
    imagepng($canvas, $tmp);
    imagedestroy($img);
    imagedestroy($canvas);

    return $tmp;
}

class PDF extends FPDF
{
    public function Header() {}
    public function Footer() {}

    public function roundedRect(float $x, float $y, float $w, float $h, float $r, string $style = 'D'): void
    {
        $k = $this->k;
        $hp = $this->h;
        $op = ($style === 'F') ? 'f' : (($style === 'FD' || $style === 'DF') ? 'B' : 'S');
        $MyArc = 4 / 3 * (sqrt(2) - 1);

        $this->_out(sprintf('%.2F %.2F m', ($x + $r) * $k, ($hp - $y) * $k));
        $this->_out(sprintf('%.2F %.2F l', ($x + $w - $r) * $k, ($hp - $y) * $k));
        $this->_arc($x + $w - $r + $r * $MyArc, $y, $x + $w, $y + $r - $r * $MyArc, $x + $w, $y + $r);
        $this->_out(sprintf('%.2F %.2F l', ($x + $w) * $k, ($hp - ($y + $h - $r)) * $k));
        $this->_arc($x + $w, $y + $h - $r + $r * $MyArc, $x + $w - $r + $r * $MyArc, $y + $h, $x + $w - $r, $y + $h);
        $this->_out(sprintf('%.2F %.2F l', ($x + $r) * $k, ($hp - ($y + $h)) * $k));
        $this->_arc($x + $r - $r * $MyArc, $y + $h, $x, $y + $h - $r + $r * $MyArc, $x, $y + $h - $r);
        $this->_out(sprintf('%.2F %.2F l', $x * $k, ($hp - ($y + $r)) * $k));
        $this->_arc($x, $y + $r - $r * $MyArc, $x + $r - $r * $MyArc, $y, $x + $r, $y);
        $this->_out($op);
    }

    private function _arc(float $x1, float $y1, float $x2, float $y2, float $x3, float $y3): void
    {
        $h = $this->h;
        $this->_out(sprintf(
            '%.2F %.2F %.2F %.2F %.2F %.2F c',
            $x1 * $this->k,
            ($h - $y1) * $this->k,
            $x2 * $this->k,
            ($h - $y2) * $this->k,
            $x3 * $this->k,
            ($h - $y3) * $this->k
        ));
    }
}

$cardW = 85.6;
$cardH = 54;

$bleuFond    = [200, 230, 255];
$bleuBordure = [147, 197, 253];
$bleuHeader  = [96, 165, 250];
$grisFonce   = [50, 50, 50];
$grisMoyen   = [80, 80, 80];
$blanc       = [255, 255, 255];

$pdf = new PDF('L', 'mm', [$cardW, $cardH]);
$pdf->AddPage();
$pdf->SetAutoPageBreak(false);

// Fond bleu clair
$pdf->SetFillColor($bleuFond[0], $bleuFond[1], $bleuFond[2]);
$pdf->roundedRect(0, 0, $cardW, $cardH, 3, 'F');

// Bordure intérieure
$pdf->SetDrawColor($bleuBordure[0], $bleuBordure[1], $bleuBordure[2]);
$pdf->SetLineWidth(0.5);
$pdf->roundedRect(2, 2, $cardW - 4, $cardH - 4, 2, 'S');

// Filigrane Palmci (centre)
$filigraneSrc = resolveFiligranePath();
if ($filigraneSrc) {
    $filigranePath = makeFiligrane($filigraneSrc, 0.10);
    if ($filigranePath) {
        $wmSize = 38;
        $wmX = ($cardW - $wmSize) / 2;
        $wmY = ($cardH - $wmSize) / 2;
        $pdf->Image($filigranePath, $wmX, $wmY, $wmSize, $wmSize);
        if ($filigranePath !== $filigraneSrc && strpos($filigranePath, sys_get_temp_dir()) === 0) {
            @unlink($filigranePath);
        }
    }
}

// En-tête bleu
$pdf->SetFillColor($bleuHeader[0], $bleuHeader[1], $bleuHeader[2]);
$pdf->roundedRect(3, 3, $cardW - 6, 12, 1, 'F');

$pdf->SetTextColor($blanc[0], $blanc[1], $blanc[2]);
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetXY(8, 6.5);
$pdf->Cell(40, 4, 'UNIPALM', 0, 0, 'L');

$pdf->SetFont('Arial', '', 6);
$pdf->SetXY(8, 10.5);
$pdf->Cell(40, 3, enc("CARTE D'IDENTIFICATION"), 0, 0, 'L');

$pdf->SetFont('Arial', 'B', 7);
$pdf->SetXY($cardW - 8, 8.5);
$pdf->Cell(0, 4, 'AGENT', 0, 0, 'R');

// Photo
$photoX = 6;
$photoY = 18;
$photoSize = 20;

$pdf->SetFillColor(230, 230, 230);
$pdf->roundedRect($photoX, $photoY, $photoSize, $photoSize, 1, 'F');
$pdf->SetDrawColor($bleuHeader[0], $bleuHeader[1], $bleuHeader[2]);
$pdf->SetLineWidth(0.3);
$pdf->roundedRect($photoX, $photoY, $photoSize, $photoSize, 1, 'S');

if (file_exists($photoPath)) {
    $ext = strtolower(pathinfo($photoPath, PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
        $pdf->Image($photoPath, $photoX + 0.5, $photoY + 0.5, $photoSize - 1, $photoSize - 1);
    }
}

// Coopérative / chef d'équipe sous la photo
$equipeY = $photoY + $photoSize + 1.5;
$equipeW = $photoSize + 2;
$pdf->SetFont('Arial', 'B', 5);
$pdf->SetTextColor($grisMoyen[0], $grisMoyen[1], $grisMoyen[2]);
$pdf->SetXY($photoX, $equipeY);
$pdf->MultiCell($equipeW, 2.2, enc($agent['chef_equipe'] ?: 'Non assigné'), 0, 'L');

// Informations (centre)
$infoX = 30;
$infoY = 20;

$pdf->SetTextColor($grisFonce[0], $grisFonce[1], $grisFonce[2]);
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetXY($infoX, $infoY);
$pdf->Cell(45, 4, enc(strtoupper($agent['nom'] ?? '')), 0, 0, 'L');

$infoY += 5;
$pdf->SetFont('Arial', '', 8);
$pdf->SetXY($infoX, $infoY);
$pdf->Cell(45, 4, enc($agent['prenom'] ?? ''), 0, 0, 'L');

$infoY += 6;
$pdf->SetFont('Arial', '', 7);
$pdf->SetTextColor($grisMoyen[0], $grisMoyen[1], $grisMoyen[2]);
$pdf->SetXY($infoX, $infoY);
$pdf->Cell(12, 3, 'Contact:', 0, 0, 'L');
$pdf->SetFont('Arial', 'B', 7);
$pdf->Cell(33, 3, enc($agent['contact'] ?? ''), 0, 0, 'L');

// QR Code (API fiable, Google Charts étant déprécié)
$qrX = $cardW - 22;
$qrY = 18;
$qrSize = 18;
$verificationUrl = 'https://unipalm.ci/verification_agent.php?code=' . urlencode($agent['numero_agent']);
$qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($verificationUrl);
$qrCodePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'temp_qr_agent_' . $agent['id_agent'] . '.png';
$qrContent = @file_get_contents($qrCodeUrl);
if ($qrContent) {
    file_put_contents($qrCodePath, $qrContent);
    $pdf->Image($qrCodePath, $qrX, $qrY, $qrSize, $qrSize);
    @unlink($qrCodePath);
}

// Pied de page
$pdf->SetFillColor($bleuHeader[0], $bleuHeader[1], $bleuHeader[2]);
$pdf->roundedRect(3, $cardH - 10, $cardW - 6, 7, 1, 'F');

$pdf->SetTextColor($blanc[0], $blanc[1], $blanc[2]);
$pdf->SetFont('Arial', 'B', 7);
$pdf->SetXY(8, $cardH - 6.5);
$pdf->Cell(50, 3, enc('CARTE N° : ' . ($agent['numero_agent'] ?? '')), 0, 0, 'L');

$dateCreation = !empty($agent['date_ajout'])
    ? date('d/m/Y', strtotime($agent['date_ajout']))
    : date('d/m/Y');
$pdf->SetFont('Arial', '', 6);
$pdf->SetXY($cardW - 8, $cardH - 6.5);
$pdf->Cell(0, 3, enc('Créé le: ' . $dateCreation), 0, 0, 'R');

$fileName = 'Carte_Agent_' . preg_replace('/[^A-Za-z0-9._-]+/', '_', $agent['numero_agent']) . '.pdf';
$pdf->Output('D', $fileName);
exit;
