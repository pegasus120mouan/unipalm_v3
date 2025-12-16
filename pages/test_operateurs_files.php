<?php
/**
 * Test pour vérifier l'existence des fichiers operateurs en production
 * URL: http://alerte.unipalm-ci.site/pages/test_operateurs_files.php
 */

echo "<h1>🔍 Test Fichiers Operateurs - UNIPALM</h1>";
echo "<p><strong>Date:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Serveur:</strong> " . $_SERVER['HTTP_HOST'] . "</p>";

echo "<hr>";

// Test 1: Vérifier l'existence du dossier operateurs
echo "<h2>📁 1. Test dossier operateurs</h2>";
$operateurs_dir = __DIR__ . '/../operateurs';
if (is_dir($operateurs_dir)) {
    echo "✅ Dossier <strong>../operateurs</strong> existe<br>";
    
    // Lister le contenu du dossier
    $files = scandir($operateurs_dir);
    echo "<h3>Contenu du dossier operateurs:</h3>";
    echo "<ul>";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $file_path = $operateurs_dir . '/' . $file;
            $size = is_file($file_path) ? filesize($file_path) : 'N/A';
            echo "<li><strong>$file</strong> (" . $size . " bytes)</li>";
        }
    }
    echo "</ul>";
} else {
    echo "❌ Dossier <strong>../operateurs</strong> MANQUANT<br>";
}

echo "<hr>";

// Test 2: Fichiers critiques operateurs
echo "<h2>📄 2. Fichiers critiques operateurs</h2>";
$critical_files = [
    '../operateurs/traitement_agents.php',
    '../operateurs/validate_bordereau.php',
    '../operateurs/save_paiement.php',
    '../operateurs/associer_tickets.php'
];

foreach ($critical_files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "✅ Fichier <strong>$file</strong> existe (" . filesize($path) . " bytes)<br>";
    } else {
        echo "❌ Fichier <strong>$file</strong> MANQUANT<br>";
    }
}

echo "<hr>";

// Test 3: Test spécifique traitement_agents.php
echo "<h2>🎯 3. Test traitement_agents.php</h2>";
$traitement_path = __DIR__ . '/../operateurs/traitement_agents.php';
if (file_exists($traitement_path)) {
    echo "✅ <strong>traitement_agents.php</strong> trouvé !<br>";
    echo "Taille: " . filesize($traitement_path) . " bytes<br>";
    echo "Dernière modification: " . date('Y-m-d H:i:s', filemtime($traitement_path)) . "<br>";
    
    // Tester si le fichier est accessible
    try {
        $content = file_get_contents($traitement_path, false, null, 0, 200);
        echo "✅ Fichier lisible<br>";
        echo "Début du fichier: <code>" . htmlspecialchars(substr($content, 0, 100)) . "...</code><br>";
    } catch (Exception $e) {
        echo "❌ Erreur lecture fichier: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ <strong>traitement_agents.php</strong> MANQUANT !<br>";
    echo "Chemin testé: $traitement_path<br>";
}

echo "<hr>";

// Test 4: Test d'accès URL directe
echo "<h2>🌐 4. Test accès URL</h2>";
echo "<p>Testez ces URLs directement :</p>";
echo "<ul>";
echo "<li><a href='../operateurs/traitement_agents.php' target='_blank'>../operateurs/traitement_agents.php</a></li>";
echo "<li><a href='traitement_agents.php' target='_blank'>traitement_agents.php (dans pages/)</a></li>";
echo "</ul>";

echo "<hr>";

// Test 5: Simulation du problème
echo "<h2>🚨 5. Simulation du problème</h2>";
echo "<p><strong>Problème identifié :</strong></p>";
echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
echo "<p>Le fichier <code>pages/agents.php</code> fait appel à :</p>";
echo "<code>action=\"traitement_agents.php\"</code>";
echo "<p>Mais ce fichier se trouve dans <code>operateurs/traitement_agents.php</code></p>";
echo "<p><strong>Solution :</strong> Changer en <code>action=\"../operateurs/traitement_agents.php\"</code></p>";
echo "</div>";

echo "<hr>";
echo "<h2>📋 Résumé</h2>";
echo "<p>Ce test confirme si le problème vient du mauvais chemin vers traitement_agents.php</p>";
?>
