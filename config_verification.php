<?php
// Configuration de la base de données pour la vérification des ponts-bascules
// Ce fichier peut être déployé sur un serveur externe

// ===========================================
// CONFIGURATION SERVEUR DE PRODUCTION
// ===========================================
if ($_SERVER['HTTP_HOST'] === 'unipalm.ci' || $_SERVER['HTTP_HOST'] === 'www.unipalm.ci') {
    // Paramètres pour le serveur de production
    $db_config = [
        'host' => '82.25.118.46', // ou l'IP du serveur MySQL
        'dbname' => 'unipalm_gestion_new', // nom de la base de données de production
        'username' => 'unipalm_user', // utilisateur de la base de données
        'password' => 'z1V07GpfhUqi7XeAlQ8', // mot de passe sécurisé
        'charset' => 'utf8mb4'
    ];
}
// ===========================================
// CONFIGURATION SERVEUR DE TEST/STAGING
// ===========================================
elseif ($_SERVER['HTTP_HOST'] === 'test.unipalm.ci') {
    // Paramètres pour le serveur de test
    $db_config = [
        'host' => 'localhost',
        'dbname' => 'unipalm_test',
        'username' => 'test_user',
        'password' => 'test_password',
        'charset' => 'utf8mb4'
    ];
}
// ===========================================
// CONFIGURATION DÉVELOPPEMENT LOCAL
// ===========================================
else {
    // Paramètres pour le développement local (XAMPP, WAMP, etc.)
    $db_config = [
        'host' => 'localhost',
        'dbname' => 'unipalm',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4'
    ];
}

// ===========================================
// CONNEXION À LA BASE DE DONNÉES
// ===========================================
try {
    $dsn = "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset={$db_config['charset']}";
    $conn = new PDO($dsn, $db_config['username'], $db_config['password']);
    
    // Configuration PDO
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
} catch(PDOException $e) {
    // Log l'erreur (en production, ne pas afficher les détails)
    error_log("Erreur de connexion base de données : " . $e->getMessage());
    
    if ($_SERVER['HTTP_HOST'] === 'unipalm.ci' || $_SERVER['HTTP_HOST'] === 'www.unipalm.ci') {
        die("Service temporairement indisponible. Veuillez réessayer plus tard.");
    } else {
        die("Erreur de connexion : " . $e->getMessage());
    }
}

// ===========================================
// FONCTIONS DE BASE DE DONNÉES
// ===========================================

/**
 * Récupérer un pont-bascule par son code
 */
function getPontBasculeByCode($conn, $code) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                id_pont,
                code_pont,
                nom_pont,
                gerant,
                cooperatif,
                latitude,
                longitude,
                statut,
                date_creation
            FROM pont_bascule 
            WHERE code_pont = :code 
            LIMIT 1
        ");
        
        $stmt->bindParam(':code', $code, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetch();
        
    } catch(PDOException $e) {
        error_log("Erreur getPontBasculeByCode : " . $e->getMessage());
        throw new Exception("Erreur lors de la récupération des données du pont");
    }
}

/**
 * Enregistrer une consultation de vérification (optionnel)
 */
function logVerification($conn, $code_pont, $ip_address = null) {
    try {
        $ip_address = $ip_address ?: $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        $stmt = $conn->prepare("
            INSERT INTO verification_logs (code_pont, ip_address, date_verification) 
            VALUES (:code, :ip, NOW())
        ");
        
        $stmt->bindParam(':code', $code_pont, PDO::PARAM_STR);
        $stmt->bindParam(':ip', $ip_address, PDO::PARAM_STR);
        $stmt->execute();
        
    } catch(PDOException $e) {
        // Ne pas faire échouer la vérification si le log échoue
        error_log("Erreur logVerification : " . $e->getMessage());
    }
}

/**
 * Créer la table de logs si elle n'existe pas
 */
function createLogTableIfNotExists($conn) {
    try {
        $conn->exec("
            CREATE TABLE IF NOT EXISTS verification_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                code_pont VARCHAR(50) NOT NULL,
                ip_address VARCHAR(45) NOT NULL,
                date_verification DATETIME NOT NULL,
                INDEX idx_code_pont (code_pont),
                INDEX idx_date (date_verification)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    } catch(PDOException $e) {
        error_log("Erreur création table logs : " . $e->getMessage());
    }
}

// Créer la table de logs si nécessaire
createLogTableIfNotExists($conn);

// ===========================================
// CONFIGURATION SUPPLÉMENTAIRE
// ===========================================

// Timezone
date_default_timezone_set('Africa/Abidjan');

// Sécurité - Headers
if ($_SERVER['HTTP_HOST'] === 'unipalm.ci' || $_SERVER['HTTP_HOST'] === 'www.unipalm.ci') {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
}
?>
