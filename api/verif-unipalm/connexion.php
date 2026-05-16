<?php
/**
 * Connexion PDO — application verif-unipalm (table users)
 * À déployer avec users.php et agents.php sur le serveur mywallet.
 */

$isLocal = (
    ($_SERVER['HTTP_HOST'] ?? '') === 'localhost'
    || ($_SERVER['HTTP_HOST'] ?? '') === '127.0.0.1'
    || str_contains($_SERVER['HTTP_HOST'] ?? '', 'localhost:')
    || str_contains($_SERVER['HTTP_HOST'] ?? '', '.test')
    || str_contains($_SERVER['HTTP_HOST'] ?? '', 'unipalm.test')
);

if ($isLocal) {
    $db_config = [
        'host'    => 'localhost',
        'dbname'  => 'verif_ticket',
        'username'=> 'root',
        'password'=> '',
        'charset' => 'utf8mb4',
    ];
} else {
    $db_config = [
        'host'    => 'localhost',
        'dbname'  => 'verif_ticket',
        'username'=> 'root',
        'password'=> '',
        'charset' => 'utf8mb4',
    ];
}

/**
 * Clé API (optionnelle).
 * Laissez vide '' pour désactiver l'authentification (tests Postman).
 * En production : define('VERIF_API_KEY', 'votre_cle_secrete');
 */
if (!defined('VERIF_API_KEY')) {
    define('VERIF_API_KEY', '');
}

try {
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $db_config['host'],
        $db_config['dbname'],
        $db_config['charset']
    );
    $conn = new PDO($dsn, $db_config['username'], $db_config['password'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de connexion à la base de données',
    ]);
    exit;
}
