<?php
/**
 * Import planteurs depuis payload_json (format application mobile)
 * ou lignes plates (CSV simple).
 *
 * POST JSON :
 * { "rows": [ { "payload": { ... } }, { "payload_json": "{...}" }, { "nom_prenoms": "..." } ] }
 *
 * À déployer : .../api/planteur/actions/api_import_planteurs.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée. Utilisez POST.']);
    exit;
}

require_once __DIR__ . '/../connexion.php';
$pdo = $conn;

function success($data, $message = 'Succès') {
    echo json_encode(['success' => true, 'message' => $message, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

function error($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function generateNumeroFiche(): string {
    return 'FICH-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

/**
 * Cherche un planteur déjà en base (évite les doublons à l'import).
 *
 * @return array{id: int, numero_fiche: string, reason: string}|null
 */
function findExistingExploitant(PDO $pdo, string $numeroFiche, string $nomPrenoms, string $telephone): ?array
{
    if ($numeroFiche !== '') {
        $stmt = $pdo->prepare('SELECT id, numero_fiche FROM exploitants WHERE numero_fiche = ? LIMIT 1');
        $stmt->execute([$numeroFiche]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return [
                'id'           => (int) $row['id'],
                'numero_fiche' => $row['numero_fiche'],
                'reason'       => 'numero_fiche',
            ];
        }
    }

    $nomPrenoms = trim($nomPrenoms);
    $telephone  = trim($telephone);
    if ($nomPrenoms !== '' && $telephone !== '') {
        $stmt = $pdo->prepare(
            'SELECT id, numero_fiche FROM exploitants
             WHERE nom_prenoms = ? AND telephone = ?
             LIMIT 1'
        );
        $stmt->execute([$nomPrenoms, $telephone]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return [
                'id'           => (int) $row['id'],
                'numero_fiche' => $row['numero_fiche'],
                'reason'       => 'nom_telephone',
            ];
        }
    }

    return null;
}

function parseDateValue(?string $value): ?string {
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $value, $m)) {
        return $m[3] . '-' . $m[2] . '-' . $m[1];
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
        return substr($value, 0, 10);
    }
    return $value;
}

function normalizePayloadFromRow(array $row): ?array {
    if (!empty($row['payload']) && is_array($row['payload'])) {
        return $row['payload'];
    }

    if (!empty($row['payload_json'])) {
        $raw = is_string($row['payload_json']) ? $row['payload_json'] : json_encode($row['payload_json']);
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }

    foreach ($row as $value) {
        if (is_string($value) && strpos(trim($value), '{') === 0) {
            $decoded = json_decode($value, true);
            if (is_array($decoded) && (isset($decoded['exploitant']) || isset($decoded['exploitation']))) {
                return $decoded;
            }
        }
    }

    if (!empty($row['nom_prenoms']) || !empty($row['telephone'])) {
        return [
            'collecteur_id' => $row['collecteur_id'] ?? null,
            'exploitant' => [
                'numero_fiche'          => $row['numero_fiche'] ?? '',
                'nom_prenoms'           => $row['nom_prenoms'] ?? '',
                'telephone'             => $row['telephone'] ?? '',
                'date_naissance'        => $row['date_naissance'] ?? null,
                'lieu_naissance'        => $row['lieu_naissance'] ?? null,
                'piece_identite'        => $row['piece_identite'] ?? null,
                'situation_matrimoniale'=> $row['situation_matrimoniale'] ?? null,
                'nombre_enfants'        => $row['nombre_enfants'] ?? 0,
            ],
            'exploitation' => [
                'region'                  => $row['region'] ?? '',
                'sous_prefecture_village' => $row['sous_prefecture'] ?? $row['sous_prefecture_village'] ?? '',
                'village'                 => $row['village'] ?? '',
                'latitude'                => $row['latitude'] ?? null,
                'longitude'               => $row['longitude'] ?? null,
            ],
            'cultures'     => [],
            'informations' => [],
        ];
    }

    return null;
}

function importPayload(PDO $pdo, array $payload): void
{
    $exploitant    = $payload['exploitant'] ?? [];
    $exploitation  = $payload['exploitation'] ?? [];
    $cultures      = $payload['cultures'] ?? [];
    $informations  = $payload['informations'] ?? [];

    $nomPrenoms = trim($exploitant['nom_prenoms'] ?? '');
    $telephone  = trim($exploitant['telephone'] ?? '');

    if ($nomPrenoms === '' || $telephone === '') {
        throw new InvalidArgumentException('nom_prenoms et telephone obligatoires dans exploitant');
    }

    $numeroFiche = trim($exploitant['numero_fiche'] ?? '');
    if ($numeroFiche === '') {
        $numeroFiche = generateNumeroFiche();
    }

    $collecteurId = $payload['collecteur_id'] ?? null;
    $collecteurId = ($collecteurId !== null && $collecteurId !== '') ? (int) $collecteurId : null;

    $dateEnreg = parseDateValue($exploitant['date_enregistrement'] ?? '') ?: date('Y-m-d');
    $dateNaissance = parseDateValue($exploitant['date_naissance'] ?? null);
    $nombreEnfants = isset($exploitant['nombre_enfants']) ? (int) $exploitant['nombre_enfants'] : 0;

    $stmt = $pdo->prepare(
        "INSERT INTO exploitants (
            numero_fiche, date_enregistrement, nom_prenoms, date_naissance, lieu_naissance,
            piece_identite, telephone, situation_matrimoniale, nombre_enfants,
            collecteur_id, created_at
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
    );
    $stmt->execute([
        $numeroFiche,
        $dateEnreg,
        $nomPrenoms,
        $dateNaissance,
        trim($exploitant['lieu_naissance'] ?? '') ?: null,
        trim($exploitant['piece_identite'] ?? '') ?: null,
        $telephone,
        trim($exploitant['situation_matrimoniale'] ?? '') ?: null,
        $nombreEnfants,
        $collecteurId,
    ]);
    $exploitantId = (int) $pdo->lastInsertId();

    $lat = $exploitation['latitude'] ?? null;
    $lng = $exploitation['longitude'] ?? null;
    $lat = ($lat !== null && $lat !== '') ? (float) $lat : null;
    $lng = ($lng !== null && $lng !== '') ? (float) $lng : null;

    $delegueId = $exploitation['delegue_id'] ?? null;
    $delegueId = ($delegueId !== null && $delegueId !== '') ? (int) $delegueId : null;

    $stmt = $pdo->prepare(
        "INSERT INTO exploitations (
            exploitant_id, region, sous_prefecture_village, village,
            latitude, longitude, delegue_id, delegue_nom, video
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $exploitantId,
        trim($exploitation['region'] ?? '') ?: null,
        trim($exploitation['sous_prefecture_village'] ?? '') ?: null,
        trim($exploitation['village'] ?? '') ?: null,
        $lat,
        $lng,
        $delegueId,
        trim($exploitation['delegue_nom'] ?? '') ?: null,
        trim($exploitation['video'] ?? '') ?: null,
    ]);
    $exploitationId = (int) $pdo->lastInsertId();

    if (is_array($cultures)) {
        $stmtCulture = $pdo->prepare(
            "INSERT INTO cultures (
                exploitation_id, type_culture, autre_culture, superficie_ha,
                age_culture, mode_culture, production_estimee_kg, created_at
             ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmtParcelle = $pdo->prepare(
            "INSERT INTO parcelles (exploitation_id, culture_id, nom, points, superficie_calculee, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())"
        );

        foreach ($cultures as $idx => $culture) {
            if (!is_array($culture)) {
                continue;
            }

            $superficie = $culture['superficie_ha'] ?? null;
            $superficie = ($superficie !== null && $superficie !== '') ? (float) $superficie : null;

            $stmtCulture->execute([
                $exploitationId,
                trim($culture['type_culture'] ?? '') ?: null,
                trim($culture['autre_culture'] ?? '') ?: null,
                $superficie,
                isset($culture['age_culture']) && $culture['age_culture'] !== '' ? (int) $culture['age_culture'] : null,
                trim($culture['mode_culture'] ?? '') ?: null,
                isset($culture['production_estimee_kg']) && $culture['production_estimee_kg'] !== ''
                    ? (float) $culture['production_estimee_kg'] : null,
            ]);
            $cultureId = (int) $pdo->lastInsertId();

            $parcellePoints = $culture['parcelle_points'] ?? $culture['parcelles'] ?? [];
            if (is_array($parcellePoints) && count($parcellePoints) > 0) {
                $pointsJson = json_encode($parcellePoints, JSON_UNESCAPED_UNICODE);
                $stmtParcelle->execute([
                    $exploitationId,
                    $cultureId,
                    'Parcelle ' . ($idx + 1),
                    $pointsJson,
                    $superficie,
                ]);
            }
        }
    }

    if (is_array($informations) && !empty($informations)) {
        $usagePhyto = $informations['usage_phytosanitaires'] ?? false;
        $usagePhyto = ($usagePhyto === true || $usagePhyto === 1 || $usagePhyto === '1') ? 1 : 0;

        $stmt = $pdo->prepare(
            "INSERT INTO informations_complementaires (
                exploitation_id, type_semences, usage_phytosanitaires, nombre_travailleurs, created_at
             ) VALUES (?, ?, ?, ?, NOW())"
        );
        $stmt->execute([
            $exploitationId,
            trim($informations['type_semences'] ?? '') ?: null,
            $usagePhyto,
            isset($informations['nombre_travailleurs']) ? (int) $informations['nombre_travailleurs'] : 0,
        ]);
    }
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!is_array($data) || empty($data['rows']) || !is_array($data['rows'])) {
    error('Tableau "rows" requis dans le corps JSON.', 400);
}

$rows = $data['rows'];
$maxRows = 500;
if (count($rows) > $maxRows) {
    error("Maximum $maxRows lignes par import.", 400);
}

$imported   = 0;
$skipped    = 0;
$duplicates = 0;
$errors     = [];
$skippedRows = [];

foreach ($rows as $index => $row) {
    $line = $index + 1;

    if (!is_array($row)) {
        $errors[] = "Ligne $line : format invalide";
        $skipped++;
        continue;
    }

    $payload = normalizePayloadFromRow($row);
    if ($payload === null) {
        $errors[] = "Ligne $line : payload_json invalide ou vide";
        $skipped++;
        continue;
    }

    $exploitant = $payload['exploitant'] ?? [];
    $nomPrenoms = trim($exploitant['nom_prenoms'] ?? '');
    $telephone  = trim($exploitant['telephone'] ?? '');
    $numeroFicheCheck = trim($exploitant['numero_fiche'] ?? '');

    $existing = findExistingExploitant($pdo, $numeroFicheCheck, $nomPrenoms, $telephone);
    if ($existing !== null) {
        $duplicates++;
        $skipped++;
        $label = $existing['numero_fiche'] ?: ('id ' . $existing['id']);
        $detail = $existing['reason'] === 'numero_fiche'
            ? "fiche $label déjà en base"
            : "planteur déjà en base ($label, même nom et téléphone)";
        $skippedRows[] = [
            'line'         => $line,
            'numero_fiche' => $existing['numero_fiche'],
            'reason'       => $existing['reason'],
        ];
        $errors[] = "Ligne $line : ignorée — $detail";
        continue;
    }

    try {
        $pdo->beginTransaction();
        importPayload($pdo, $payload);
        $pdo->commit();
        $imported++;
    } catch (InvalidArgumentException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $errors[] = "Ligne $line : " . $e->getMessage();
        $skipped++;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $errors[] = "Ligne $line : " . $e->getMessage();
        $skipped++;
    }
}

success([
    'imported'    => $imported,
    'skipped'     => $skipped,
    'duplicates'  => $duplicates,
    'skipped_rows'=> $skippedRows,
    'errors'      => $errors,
    'total'       => count($rows),
], "$imported enregistrement(s) importé(s)");
