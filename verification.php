<?php
/**
 * Page de vérification d'un collecteur UNIPALM
 * Accessible via QR code: https://unipalm.ci/verification.php?id=XX
 */

// Configuration de la base de données
if ($_SERVER['HTTP_HOST'] === 'unipalm.ci' || $_SERVER['HTTP_HOST'] === 'www.unipalm.ci') {
    // Paramètres pour le serveur de production
    $db_config = [
        'host' => '82.25.118.46',
        'dbname' => 'recensement_agricole',
        'username' => 'unipalm_user',
        'password' => 'z1V07GpfhUqi7XeAlQ8',
        'charset' => 'utf8mb4'
    ];
} else {
    // Paramètres pour le développement local
    $db_config = [
        'host' => 'localhost',
        'dbname' => 'recensement_agricole',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4'
    ];
}

try {
    $dsn = "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset={$db_config['charset']}";
    $conn = new PDO($dsn, $db_config['username'], $db_config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    die('Erreur de connexion à la base de données');
}

$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user = null;
$found = false;

if ($userId > 0) {
    try {
        $stmt = $conn->prepare("
            SELECT u.id, u.nom, u.prenoms, u.contact, u.role, u.avatar, u.created_at,
                   z.nom_zone as zone_nom
            FROM utilisateurs u
            LEFT JOIN zones z ON u.zone_id = z.id
            WHERE u.id = ? AND u.statut_compte = 1
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $found = $user !== false;
    } catch (PDOException $e) {
        $found = false;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification - UNIPALM</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #27ae60 0%, #1e8449 100%);
            padding: 20px;
        }
        .card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 450px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .logo {
            font-size: 2rem;
            font-weight: 700;
            color: #27ae60;
            margin-bottom: 10px;
        }
        .logo span { color: #2c3e50; }
        .subtitle {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-bottom: 30px;
        }
        .icon-container {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            font-size: 3rem;
        }
        .icon-success {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
        }
        .icon-error {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
        }
        .status-text {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .status-success { color: #27ae60; }
        .status-error { color: #e74c3c; }
        .user-info {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin-top: 20px;
            text-align: left;
        }
        .user-info .row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .user-info .row:last-child { border-bottom: none; }
        .user-info .label {
            color: #7f8c8d;
            font-size: 0.85rem;
        }
        .user-info .value {
            font-weight: 600;
            color: #2c3e50;
        }
        .avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #27ae60;
            margin-bottom: 15px;
        }
        .badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            background: #27ae60;
            color: white;
            text-transform: uppercase;
        }
        .card-number {
            margin-top: 20px;
            padding: 10px;
            background: #27ae60;
            color: white;
            border-radius: 10px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">UNI<span>PALM</span></div>
        <div class="subtitle">Système de vérification d'identité</div>
        
        <?php if ($found): ?>
            <div class="icon-container icon-success">
                <i class="fas fa-check"></i>
            </div>
            <div class="status-text status-success">
                Ce collecteur appartient à UNIPALM
            </div>
            
            <div class="user-info">
                <div style="text-align: center; margin-bottom: 15px;">
                    <span class="badge"><?= htmlspecialchars($user['role']) ?></span>
                </div>
                <div class="row">
                    <span class="label">Nom</span>
                    <span class="value"><?= htmlspecialchars($user['nom']) ?></span>
                </div>
                <div class="row">
                    <span class="label">Prénoms</span>
                    <span class="value"><?= htmlspecialchars($user['prenoms']) ?></span>
                </div>
                <div class="row">
                    <span class="label">Contact</span>
                    <span class="value"><?= htmlspecialchars($user['contact']) ?></span>
                </div>
                <div class="row">
                    <span class="label">Zone</span>
                    <span class="value"><?= htmlspecialchars($user['zone_nom'] ?? 'Non assigné') ?></span>
                </div>
                <div class="row">
                    <span class="label">Membre depuis</span>
                    <span class="value"><?= date('d/m/Y', strtotime($user['created_at'])) ?></span>
                </div>
            </div>
            
            <div class="card-number">
                CARTE N° : UNI-<?= str_pad($user['id'], 6, '0', STR_PAD_LEFT) ?>
            </div>
            
        <?php else: ?>
            <div class="icon-container icon-error">
                <i class="fas fa-times"></i>
            </div>
            <div class="status-text status-error">
                Utilisateur introuvable
            </div>
            <p style="color: #7f8c8d; margin-top: 15px;">
                Cette carte d'identification n'est pas reconnue dans notre système.<br>
                Veuillez contacter UNIPALM pour plus d'informations.
            </p>
        <?php endif; ?>
    </div>
</body>
</html>
