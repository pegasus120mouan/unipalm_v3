<?php
/**
 * Vérification publique d'un agent UNIPALM (QR code).
 * URL : https://unipalm.ci/verification_agent.php?code=AGT-XX-XXX
 */

$host = $_SERVER['HTTP_HOST'] ?? '';
$isProduction = in_array($host, ['unipalm.ci', 'www.unipalm.ci', 'admin.unipalm.online'], true);

if ($isProduction) {
    $dbConfig = [
        'host'     => '82.25.118.46',
        'dbname'   => 'unipalm_gestion_new',
        'username' => 'unipalm_user',
        'password' => 'z1V07GpfhUqi7XeAlQ8',
        'charset'  => 'utf8mb4',
    ];
} else {
    $dbConfig = [
        'host'     => 'localhost',
        'dbname'   => 'unipalm_gestion_new',
        'username' => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
    ];
}

try {
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
    $conn = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    http_response_code(503);
    die('Service temporairement indisponible.');
}

/**
 * Normalise un nom pour comparaison (casse, accents, espaces).
 */
function normalizePersonName(?string $name): string
{
    $name = trim(preg_replace('/\s+/u', ' ', (string) $name));
    $name = mb_strtoupper($name, 'UTF-8');
    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        if ($converted !== false) {
            $name = $converted;
        }
    }
    $name = preg_replace('/[^A-Z\s]/', '', $name);
    return trim(preg_replace('/\s+/', ' ', $name));
}

/**
 * Vérifie si le gérant du pont correspond à l'agent scanné.
 */
function agentMatchesGerant(array $agent, string $gerant): bool
{
    $gerantNorm = normalizePersonName($gerant);
    if ($gerantNorm === '') {
        return false;
    }

    $numero = trim($agent['numero_agent'] ?? '');
    if ($numero !== '' && trim($gerant) === $numero) {
        return true;
    }

    $nom    = normalizePersonName($agent['nom'] ?? '');
    $prenom = normalizePersonName($agent['prenom'] ?? '');

    $variants = array_unique(array_filter([
        normalizePersonName(trim(($agent['nom'] ?? '') . ' ' . ($agent['prenom'] ?? ''))),
        normalizePersonName(trim(($agent['prenom'] ?? '') . ' ' . ($agent['nom'] ?? ''))),
    ]));

    foreach ($variants as $variant) {
        if ($variant === $gerantNorm) {
            return true;
        }
        // Troncature type "TOURE FATIM" vs "TOURE FATIME"
        if (strlen($variant) >= 8 && strpos($variant, $gerantNorm) === 0) {
            return true;
        }
        if (strlen($gerantNorm) >= 8 && strpos($gerantNorm, $variant) === 0) {
            return true;
        }
    }

    if ($nom !== '' && $prenom !== '') {
        if (strpos($gerantNorm, $nom) === false) {
            return false;
        }
        foreach (explode(' ', $prenom) as $part) {
            if (strlen($part) >= 2 && strpos($gerantNorm, $part) === false) {
                return false;
            }
        }
        return true;
    }

    return $nom !== '' && $gerantNorm === $nom;
}

/**
 * @return array<int, array<string, mixed>>
 */
function fetchPontsForAgent(PDO $conn, array $agent): array
{
    $stmt = $conn->query("
        SELECT id_pont, code_pont, nom_pont, latitude, longitude, gerant, cooperatif, statut
        FROM pont_bascule
        ORDER BY nom_pont ASC, code_pont ASC
    ");
    $all = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $matched = [];

    foreach ($all as $pont) {
        if (agentMatchesGerant($agent, (string) ($pont['gerant'] ?? ''))) {
            $matched[] = $pont;
        }
    }

    return $matched;
}

$code  = isset($_GET['code']) ? trim($_GET['code']) : '';
$agent = null;
$ponts  = [];
$error = null;

if ($code !== '') {
    try {
        $stmt = $conn->prepare("
            SELECT 
                agents.id_agent,
                agents.numero_agent,
                agents.nom,
                agents.prenom,
                agents.contact,
                agents.date_ajout,
                CONCAT(chef_equipe.nom, ' ', chef_equipe.prenoms) AS chef_equipe
            FROM agents
            LEFT JOIN chef_equipe ON agents.id_chef = chef_equipe.id_chef
            WHERE agents.numero_agent = ?
              AND agents.date_suppression IS NULL
        ");
        $stmt->execute([$code]);
        $agent = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$agent) {
            $error = 'Aucun agent trouvé avec ce code.';
        } else {
            $ponts = fetchPontsForAgent($conn, $agent);
        }
    } catch (PDOException $e) {
        $error = 'Erreur lors de la vérification.';
    }
} else {
    $error = 'Code agent non spécifié.';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification Agent - UNIPALM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e40af 0%, #60a5fa 50%, #93c5fd 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .verification-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, #2563eb 0%, #60a5fa 100%);
            color: white;
            padding: 25px;
            text-align: center;
        }
        .card-header img { width: 80px; height: 80px; margin-bottom: 15px; }
        .card-header h1 { font-size: 1.5rem; margin-bottom: 5px; }
        .card-header p { opacity: 0.9; font-size: 0.9rem; }
        .card-body { padding: 25px 30px 30px; }
        .agent-name { text-align: center; margin-bottom: 25px; }
        .agent-name h2 { color: #1e40af; font-size: 1.5rem; margin-bottom: 5px; }
        .agent-name .badge {
            background: linear-gradient(135deg, #60a5fa 0%, #2563eb 100%);
            color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.85rem;
        }
        .info-section { background: #f8f9fa; border-radius: 15px; padding: 20px; margin-bottom: 20px; }
        .info-section h3 { color: #1e40af; font-size: 1rem; margin-bottom: 15px; }
        .info-row {
            display: flex; justify-content: space-between;
            padding: 10px 0; border-bottom: 1px solid #e0e0e0;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #666; font-weight: 500; }
        .info-value { color: #1e40af; font-weight: 600; text-align: right; }
        .status-badge {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 20px; border-radius: 30px; font-weight: 600;
        }
        .status-valid { background: #dbeafe; color: #1d4ed8; }
        .status-invalid { background: #ffebee; color: #c62828; }
        .ponts-section { background: #eff6ff; border-radius: 15px; padding: 20px; margin-bottom: 20px; }
        .ponts-section h3 { color: #1e40af; font-size: 1rem; margin-bottom: 15px; }
        .pont-item {
            background: white; border-radius: 12px; padding: 14px 16px;
            margin-bottom: 10px; border: 1px solid #dbeafe;
        }
        .pont-item:last-child { margin-bottom: 0; }
        .pont-title { color: #1e3a8a; font-weight: 700; font-size: 0.95rem; margin-bottom: 6px; }
        .pont-meta { color: #64748b; font-size: 0.82rem; line-height: 1.5; }
        .pont-meta strong { color: #334155; }
        .pont-statut {
            display: inline-block; padding: 2px 10px; border-radius: 999px;
            font-size: 0.75rem; font-weight: 600; margin-top: 6px;
        }
        .pont-statut.actif { background: #dcfce7; color: #166534; }
        .pont-statut.inactif { background: #fee2e2; color: #991b1b; }
        .pont-empty { color: #64748b; font-size: 0.9rem; text-align: center; padding: 10px 0; }
        .error-container { text-align: center; padding: 40px; }
        .error-container i { font-size: 4rem; color: #c62828; margin-bottom: 20px; }
        .error-container h2 { color: #c62828; margin-bottom: 10px; }
        @media (max-width: 576px) {
            .info-row { flex-direction: column; gap: 5px; }
            .info-value { text-align: left; }
        }
    </style>
</head>
<body>
    <div class="verification-card">
        <div class="card-header">
            <img src="dist/img/cartes/logo.png" alt="UNIPALM Logo" onerror="this.src='dist/img/logo.png'">
            <h1>UNIPALM COOP-CA</h1>
            <p>Vérification d'identité Agent</p>
        </div>

        <?php if ($agent): ?>
        <div class="card-body">
            <div class="agent-name">
                <h2><?= htmlspecialchars(strtoupper($agent['nom']) . ' ' . ucfirst(strtolower($agent['prenom']))) ?></h2>
                <span class="badge"><i class="fas fa-id-badge me-1"></i> AGENT</span>
            </div>
            <div class="info-section">
                <h3><i class="fas fa-info-circle"></i> Informations de l'agent</h3>
                <div class="info-row">
                    <span class="info-label">N° Agent</span>
                    <span class="info-value"><?= htmlspecialchars($agent['numero_agent']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Contact</span>
                    <span class="info-value"><?= htmlspecialchars($agent['contact']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Chef d'équipe</span>
                    <span class="info-value"><?= htmlspecialchars($agent['chef_equipe'] ?? 'Non assigné') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date d'inscription</span>
                    <span class="info-value"><?= date('d/m/Y', strtotime($agent['date_ajout'])) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Validité</span>
                    <span class="info-value"><?= date('d/m/Y', strtotime($agent['date_ajout'] . ' +1 year')) ?></span>
                </div>
            </div>

            <div class="ponts-section">
                <h3><i class="fas fa-weight-hanging"></i> Ponts bascule gérés (<?= count($ponts) ?>)</h3>
                <?php if (!empty($ponts)): ?>
                    <?php foreach ($ponts as $pont): ?>
                        <?php
                        $statut = $pont['statut'] ?? 'Inactif';
                        $statutClass = strtolower($statut) === 'actif' ? 'actif' : 'inactif';
                        $lat = isset($pont['latitude']) ? (float) $pont['latitude'] : 0.0;
                        $lng = isset($pont['longitude']) ? (float) $pont['longitude'] : 0.0;
                        $mapsUrl = ($lat != 0.0 && $lng != 0.0)
                            ? 'https://www.google.com/maps?q=' . urlencode($lat . ',' . $lng)
                            : null;
                        $nomPont = trim($pont['nom_pont'] ?? '') ?: $pont['code_pont'];
                        ?>
                        <div class="pont-item">
                            <div class="pont-title"><?= htmlspecialchars($nomPont) ?></div>
                            <div class="pont-meta">
                                <div><strong>Code :</strong> <?= htmlspecialchars($pont['code_pont']) ?></div>
                                <?php if (!empty(trim($pont['cooperatif'] ?? ''))): ?>
                                    <div><strong>Coopérative :</strong> <?= htmlspecialchars(trim($pont['cooperatif'])) ?></div>
                                <?php endif; ?>
                                <?php if ($mapsUrl): ?>
                                    <div><strong>Localisation :</strong>
                                        <a href="<?= htmlspecialchars($mapsUrl) ?>" target="_blank" rel="noopener">Voir sur la carte</a>
                                    </div>
                                <?php endif; ?>
                                <span class="pont-statut <?= $statutClass ?>"><?= htmlspecialchars($statut) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="pont-empty">Aucun pont bascule associé à cet agent.</div>
                <?php endif; ?>
            </div>

            <div class="text-center">
                <div class="status-badge status-valid">
                    <i class="fas fa-check-circle"></i> Agent vérifié et actif
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="error-container">
            <i class="fas fa-exclamation-triangle"></i>
            <h2>Agent non trouvé</h2>
            <p><?= htmlspecialchars($error ?? 'Vérification impossible.') ?></p>
            <div class="status-badge status-invalid mt-3">
                <i class="fas fa-times-circle"></i> Vérification échouée
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
