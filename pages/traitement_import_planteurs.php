<?php
require_once '../inc/functions/connexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

const IMPORT_API_URL = 'https://api.objetombrepegasus.online/api/planteur/actions/api_import_planteurs.php';

function redirectImport(string $type, string $message): void
{
    $_SESSION[$type] = $message;
    header('Location: plantations.php');
    exit;
}

/**
 * @return array{map: array<int, string>, hasPayloadColumn: bool}
 */
function buildHeaderMap(array $header): array
{
    $map = [];
    $hasPayloadColumn = false;

    foreach ($header as $i => $col) {
        $key = strtolower(trim(preg_replace('/\s+/', '_', (string) $col)));
        $aliases = [
            'payload'      => 'payload_json',
            'payload_json' => 'payload_json',
            'json'         => 'payload_json',
            'data_json'    => 'payload_json',
        ];
        $normalized = $aliases[$key] ?? $key;
        $map[$i] = $normalized;
        if ($normalized === 'payload_json') {
            $hasPayloadColumn = true;
        }
    }

    return ['map' => $map, 'hasPayloadColumn' => $hasPayloadColumn];
}

/**
 * @param array<int, array<int, mixed>> $dataRows
 * @return array<int, array<string, string>>
 */
function buildImportRows(array $dataRows, array $map, bool $hasPayloadColumn): array
{
    $rows = [];

    foreach ($dataRows as $data) {
        if (count(array_filter($data, fn($v) => trim((string) $v) !== '')) === 0) {
            continue;
        }

        $row = [];
        foreach ($data as $i => $value) {
            if (!isset($map[$i])) {
                continue;
            }
            $row[$map[$i]] = trim((string) $value);
        }

        if ($hasPayloadColumn && !empty($row['payload_json'])) {
            $rows[] = ['payload_json' => $row['payload_json']];
            continue;
        }

        if (!$hasPayloadColumn) {
            foreach ($data as $value) {
                $v = trim((string) $value);
                if (strpos($v, '{') === 0 && strpos($v, '"exploitant"') !== false) {
                    $rows[] = ['payload_json' => $v];
                    continue 2;
                }
            }
        }

        if (!empty($row['nom_prenoms']) || !empty($row['telephone']) || !empty($row['payload_json'])) {
            $rows[] = $row;
        }
    }

    return $rows;
}

/**
 * @return array{header: array<int, string>, dataRows: array<int, array<int, string>>}
 */
function readCsvSheet(string $path): array
{
    $handle = fopen($path, 'r');
    if ($handle === false) {
        throw new RuntimeException('Impossible de lire le fichier.');
    }

    $firstLine = fgets($handle);
    if ($firstLine === false) {
        fclose($handle);
        throw new RuntimeException('Fichier vide.');
    }

    $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
    rewind($handle);

    $header = fgetcsv($handle, 0, $delimiter);
    if (!$header) {
        fclose($handle);
        throw new RuntimeException('En-têtes introuvables.');
    }

    $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0] ?? '');

    $dataRows = [];
    while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
        $dataRows[] = $data;
    }
    fclose($handle);

    return ['header' => $header, 'dataRows' => $dataRows];
}

/**
 * @return array{header: array<int, string>, dataRows: array<int, array<int, string>>}
 */
function xlsxRowsToSheet(array $all): array
{
    if (empty($all)) {
        throw new RuntimeException('Fichier Excel vide.');
    }

    $header = array_map(fn($v) => trim((string) $v), array_shift($all));
    $dataRows = [];

    foreach ($all as $line) {
        $dataRows[] = array_map(fn($v) => (string) $v, is_array($line) ? $line : [$line]);
    }

    return ['header' => $header, 'dataRows' => $dataRows];
}

/**
 * Lecture .xlsx sans Composer (SimpleXLSX embarqué), repli PhpSpreadsheet si disponible.
 *
 * @return array{header: array<int, string>, dataRows: array<int, array<int, string>>}
 */
function readXlsxSheet(string $path): array
{
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('Extension PHP ZipArchive requise pour lire les fichiers Excel.');
    }

    $simpleXlsxPath = dirname(__DIR__) . '/assets/class/SimpleXLSX.php';
    if (is_file($simpleXlsxPath)) {
        require_once $simpleXlsxPath;
        $xlsx = SimpleXLSX::parse($path);
        if ($xlsx) {
            return xlsxRowsToSheet($xlsx->rows());
        }
        $parseError = SimpleXLSX::parseError();
        if ($parseError) {
            throw new RuntimeException('Fichier Excel illisible : ' . $parseError);
        }
    }

    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (is_file($autoload)) {
        require_once $autoload;
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $all = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        return xlsxRowsToSheet($all);
    }

    throw new RuntimeException(
        'Lecture Excel indisponible : fichier assets/class/SimpleXLSX.php manquant sur le serveur.'
    );
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectImport('error', 'Méthode non autorisée.');
}

if (empty($_FILES['fichier_csv']['tmp_name']) || $_FILES['fichier_csv']['error'] !== UPLOAD_ERR_OK) {
    redirectImport('error', 'Veuillez sélectionner un fichier valide.');
}

$ext = strtolower(pathinfo($_FILES['fichier_csv']['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['csv', 'xlsx'], true)) {
    redirectImport('error', 'Formats acceptés : .csv ou .xlsx (colonne payload_json ou nom_prenoms + telephone).');
}

try {
    $sheet = ($ext === 'xlsx')
        ? readXlsxSheet($_FILES['fichier_csv']['tmp_name'])
        : readCsvSheet($_FILES['fichier_csv']['tmp_name']);
} catch (RuntimeException $e) {
    redirectImport('error', $e->getMessage());
}

$headerInfo = buildHeaderMap($sheet['header']);
$rows = buildImportRows($sheet['dataRows'], $headerInfo['map'], $headerInfo['hasPayloadColumn']);

if (empty($rows)) {
    redirectImport('error', 'Aucune ligne valide. Utilisez une colonne payload_json ou nom_prenoms + telephone.');
}

$payload = json_encode(['rows' => $rows], JSON_UNESCAPED_UNICODE);

$ch = curl_init(IMPORT_API_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Accept: application/json',
    ],
    CURLOPT_TIMEOUT        => 300,
    CURLOPT_SSL_VERIFYPEER => false,
]);

$response = curl_exec($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($response === false || $curlError) {
    redirectImport('error', 'Erreur API : ' . ($curlError ?: 'connexion impossible'));
}

$json = json_decode($response, true);
if (!is_array($json)) {
    redirectImport('error', 'Réponse API invalide (HTTP ' . $httpCode . '). Déployez api_import_planteurs.php sur le serveur.');
}

if (empty($json['success'])) {
    redirectImport('error', $json['error'] ?? $json['message'] ?? 'Import échoué.');
}

$d = $json['data'] ?? [];
$imported   = (int) ($d['imported'] ?? 0);
$skipped    = (int) ($d['skipped'] ?? 0);
$duplicates = (int) ($d['duplicates'] ?? 0);
$errList    = $d['errors'] ?? [];

$msg = "$imported enregistrement(s) importé(s) (exploitant + exploitation + cultures)";
if ($duplicates > 0) {
    $msg .= ", $duplicates déjà en base (non réimporté(s))";
}
if ($skipped > $duplicates) {
    $msg .= ', ' . ($skipped - $duplicates) . ' autre(s) ignoré(s)';
} elseif ($skipped > 0 && $duplicates === 0) {
    $msg .= ", $skipped ignoré(s)";
}
if (!empty($errList)) {
    $preview = array_slice($errList, 0, 5);
    $msg .= '. ' . implode(' | ', $preview);
    if (count($errList) > 5) {
        $msg .= ' …';
    }
}

redirectImport('success', $msg);
