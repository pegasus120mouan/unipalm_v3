<?php
/**
 * Script de diagnostic pour la production
 * URL de test: http://alerte.unipalm-ci.site/test_production_diagnostic.php
 */

echo "<h1>🔍 Diagnostic Production UNIPALM</h1>";
echo "<p><strong>Date/Heure:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Serveur:</strong> " . $_SERVER['HTTP_HOST'] . "</p>";
echo "<p><strong>Script:</strong> " . $_SERVER['SCRIPT_NAME'] . "</p>";

echo "<hr>";

// Test 1: Structure des dossiers
echo "<h2>📁 1. Structure des dossiers</h2>";
$required_dirs = [
    'inc',
    'inc/functions',
    'inc/functions/envoiSMS',
    'inc/functions/envoiSMS/src',
    'pages',
    'operateurs',
    'caisse',
    'directeur'
];

foreach ($required_dirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (is_dir($path)) {
        echo "✅ Dossier <strong>$dir</strong> existe<br>";
    } else {
        echo "❌ Dossier <strong>$dir</strong> MANQUANT<br>";
    }
}

echo "<hr>";

// Test 2: Fichiers SMS critiques
echo "<h2>📄 2. Fichiers SMS critiques</h2>";
$required_files = [
    'inc/functions/envoiSMS/config.php',
    'inc/functions/envoiSMS/src/OvlSmsService.php',
    'inc/functions/connexion.php'
];

foreach ($required_files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "✅ Fichier <strong>$file</strong> existe (" . filesize($path) . " bytes)<br>";
    } else {
        echo "❌ Fichier <strong>$file</strong> MANQUANT<br>";
    }
}

echo "<hr>";

// Test 3: Configuration SMS
echo "<h2>⚙️ 3. Test configuration SMS</h2>";
$config_path = __DIR__ . '/inc/functions/envoiSMS/config.php';
if (file_exists($config_path)) {
    try {
        require_once $config_path;
        echo "✅ Configuration SMS chargée<br>";
        
        // Vérifier les variables d'environnement
        $sms_vars = ['SMS_PROVIDER', 'HSMS_CLIENT_ID', 'HSMS_CLIENT_SECRET', 'HSMS_TOKEN'];
        foreach ($sms_vars as $var) {
            if (isset($_ENV[$var]) && !empty($_ENV[$var])) {
                echo "✅ Variable <strong>$var</strong> définie<br>";
            } else {
                echo "❌ Variable <strong>$var</strong> manquante ou vide<br>";
            }
        }
        
        // Test de la fonction createSmsService
        if (function_exists('createSmsService')) {
            echo "✅ Fonction createSmsService disponible<br>";
            try {
                $smsService = createSmsService();
                echo "✅ Service SMS créé avec succès<br>";
            } catch (Exception $e) {
                echo "❌ Erreur création service SMS: " . $e->getMessage() . "<br>";
            }
        } else {
            echo "❌ Fonction createSmsService non trouvée<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Erreur chargement config: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Fichier de configuration SMS non trouvé<br>";
}

echo "<hr>";

// Test 4: Connexion base de données
echo "<h2>🗄️ 4. Test connexion base de données</h2>";
$connexion_path = __DIR__ . '/inc/functions/connexion.php';
if (file_exists($connexion_path)) {
    try {
        require_once $connexion_path;
        echo "✅ Fichier connexion.php chargé<br>";
        
        if (defined('DB_HOST') && defined('DB_USER') && defined('DB_NAME')) {
            echo "✅ Constantes DB définies:<br>";
            echo "&nbsp;&nbsp;- Host: " . DB_HOST . "<br>";
            echo "&nbsp;&nbsp;- User: " . DB_USER . "<br>";
            echo "&nbsp;&nbsp;- Database: " . DB_NAME . "<br>";
            
            // Test connexion
            try {
                $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
                echo "✅ Connexion base de données réussie<br>";
            } catch (PDOException $e) {
                echo "❌ Erreur connexion DB: " . $e->getMessage() . "<br>";
            }
        } else {
            echo "❌ Constantes DB non définies<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Erreur chargement connexion: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Fichier connexion.php non trouvé<br>";
}

echo "<hr>";

// Test 5: Permissions et droits d'écriture
echo "<h2>🔐 5. Test permissions</h2>";
$test_dirs = ['inc', 'pages', 'operateurs'];
foreach ($test_dirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (is_dir($path)) {
        if (is_readable($path)) {
            echo "✅ Dossier <strong>$dir</strong> lisible<br>";
        } else {
            echo "❌ Dossier <strong>$dir</strong> non lisible<br>";
        }
        
        if (is_writable($path)) {
            echo "✅ Dossier <strong>$dir</strong> en écriture<br>";
        } else {
            echo "⚠️ Dossier <strong>$dir</strong> en lecture seule<br>";
        }
    }
}

echo "<hr>";

// Test 6: Variables serveur importantes
echo "<h2>🌐 6. Informations serveur</h2>";
echo "<strong>PHP Version:</strong> " . phpversion() . "<br>";
echo "<strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "<strong>Script Filename:</strong> " . $_SERVER['SCRIPT_FILENAME'] . "<br>";
echo "<strong>Current Directory:</strong> " . __DIR__ . "<br>";
echo "<strong>Include Path:</strong> " . get_include_path() . "<br>";

// Extensions PHP importantes
$required_extensions = ['pdo', 'pdo_mysql', 'curl', 'json'];
echo "<br><strong>Extensions PHP:</strong><br>";
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ Extension <strong>$ext</strong> chargée<br>";
    } else {
        echo "❌ Extension <strong>$ext</strong> manquante<br>";
    }
}

echo "<hr>";
echo "<h2>📋 Résumé</h2>";
echo "<p>Ce diagnostic vous aide à identifier les problèmes sur le serveur de production.</p>";
echo "<p><strong>Prochaines étapes:</strong></p>";
echo "<ul>";
echo "<li>Vérifiez que tous les fichiers sont bien uploadés sur le serveur</li>";
echo "<li>Assurez-vous que les permissions sont correctes</li>";
echo "<li>Testez l'accès à la page agents.php après correction des erreurs</li>";
echo "</ul>";
?>
