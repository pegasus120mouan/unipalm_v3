<?php 
session_start(); 

// Détection automatique de l'environnement
$isLocal = (
    $_SERVER['HTTP_HOST'] === 'localhost' || 
    $_SERVER['HTTP_HOST'] === '127.0.0.1' || 
    strpos($_SERVER['HTTP_HOST'], 'localhost:') === 0 ||
    strpos($_SERVER['HTTP_HOST'], '.local') !== false ||
    strpos($_SERVER['HTTP_HOST'], 'unipalm.test') !== false  // Ajout pour environnement de test
);

// FORCER LOCAL pour debug (à supprimer après test)
$isLocal = true;

// Configuration des erreurs selon l'environnement
if ($isLocal) {
    // Environnement LOCAL - Afficher les erreurs
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    // Environnement PRODUCTION - Afficher temporairement pour diagnostic
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
}

// Auth guard: redirect to login if not authenticated
$__PUBLIC_SCRIPTS = [
    'index.php',
    'login_verification.php',
    'register.php',
    'connexion-test.php',
    'debug_online.php',
    'ponts.php'  // Temporaire pour test
];

$__current_script = basename($_SERVER['SCRIPT_NAME'] ?? '');
if (!in_array($__current_script, $__PUBLIC_SCRIPTS, true)) {
    if (empty($_SESSION['user_id'])) {
        // Redirection relative pour fonctionner partout
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $base_path = dirname(dirname(dirname($_SERVER['SCRIPT_NAME'])));
        $redirect_url = $protocol . '://' . $host . $base_path . '/index.php';
        header('Location: ' . $redirect_url);
        exit();
    }
}

// Configuration Base de Données selon l'environnement
if ($isLocal) {
    // Configuration LOCALE (Développement)
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'unipalm_gestion_new');
} else {
    // Configuration PRODUCTION
    define('DB_HOST', '82.25.118.46');
    define('DB_USER', 'unipalm_user');
    define('DB_PASS', 'z1V07GpfhUqi7XeAlQ8');
    define('DB_NAME', 'unipalm_gestion_new');
}

// Fonction pour établir la connexion à la base de données
function getConnexion() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            $conn = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            // Afficher l'erreur pour diagnostic
            echo "Erreur de connexion : " . $e->getMessage();
            echo "<br>Host: " . DB_HOST;
            echo "<br>User: " . DB_USER;
            echo "<br>DB: " . DB_NAME;
            exit();
        }
    }
    
    return $conn;
}

// Pour la compatibilité avec le code existant
try {
    $conn = getConnexion();
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
    exit();
}
?>