<?php
require_once 'vendor/autoload.php';
require_once 'config.php';

// Vérification simple d'authentification (à améliorer en production)
session_start();
$isAuthenticated = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

if (!$isAuthenticated && isset($_POST['admin_password'])) {
    // Mot de passe simple (à changer en production)
    if ($_POST['admin_password'] === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        $isAuthenticated = true;
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Fonctions pour lire les logs
function readLogFile($filename, $limit = 50) {
    $filepath = __DIR__ . '/logs/' . $filename;
    if (!file_exists($filepath)) {
        return [];
    }
    
    $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    return array_slice(array_reverse($lines), 0, $limit);
}

function parseLogLine($line, $type) {
    $data = [];
    
    if (preg_match('/\[(.*?)\](.*)/', $line, $matches)) {
        $data['timestamp'] = $matches[1];
        $content = trim($matches[2]);
        
        if ($type === 'calls') {
            if (preg_match('/CallSID: (.*?) \| From: (.*?) \| To: (.*?) \| Direction: (.*)/', $content, $callMatches)) {
                $data['call_sid'] = $callMatches[1];
                $data['from'] = $callMatches[2];
                $data['to'] = $callMatches[3];
                $data['direction'] = $callMatches[4];
            }
        } elseif ($type === 'sms') {
            if (preg_match('/MessageSID: (.*?) \| From: (.*?) \| To: (.*?) \| Direction: (.*?) \| Body: (.*)/', $content, $smsMatches)) {
                $data['message_sid'] = $smsMatches[1];
                $data['from'] = $smsMatches[2];
                $data['to'] = $smsMatches[3];
                $data['direction'] = $smsMatches[4];
                $data['body'] = $smsMatches[5];
            }
        }
    }
    
    return $data;
}

if (!$isAuthenticated) {
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Envoi SMS</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center">
    <div class="glass-effect rounded-xl p-8 max-w-md w-full mx-4">
        <div class="text-center mb-6">
            <i class="fas fa-shield-alt text-4xl text-white mb-4"></i>
            <h1 class="text-2xl font-bold text-white">Administration</h1>
        </div>
        
        <form method="POST">
            <div class="mb-4">
                <label class="block text-white font-medium mb-2">Mot de passe</label>
                <input type="password" name="admin_password" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500" required>
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition duration-200">
                <i class="fas fa-sign-in-alt mr-2"></i>Se connecter
            </button>
        </form>
    </div>
</body>
</html>
<?php
    exit;
}

// Interface d'administration
$callLogs = readLogFile('calls.log');
$smsLogs = readLogFile('sms.log');
$webhookLogs = readLogFile('webhook.log', 20);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Envoi SMS</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
    </style>
</head>
<body class="gradient-bg min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-white">
                <i class="fas fa-cogs mr-3"></i>Administration SMS
            </h1>
            <div class="space-x-4">
                <a href="index.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200">
                    <i class="fas fa-home mr-2"></i>Accueil
                </a>
                <a href="?logout=1" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition duration-200">
                    <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                </a>
            </div>
        </div>

        <!-- Configuration Webhook -->
        <div class="glass-effect rounded-xl p-6 mb-8">
            <h2 class="text-xl font-bold text-white mb-4">
                <i class="fas fa-link mr-2"></i>Configuration Webhook
            </h2>
            <div class="bg-gray-800 p-4 rounded-lg text-green-400 font-mono text-sm">
                <p><strong>URL Webhook:</strong> <?php echo (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/webhook.php'; ?></p>
                <p><strong>Méthode:</strong> POST</p>
            </div>
            <div class="mt-4 text-white opacity-80 text-sm">
                <p>Configurez cette URL dans votre console Twilio pour recevoir les webhooks d'appels et SMS.</p>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="glass-effect rounded-xl p-6 text-center">
                <i class="fas fa-phone text-3xl text-blue-400 mb-3"></i>
                <h3 class="text-xl font-bold text-white"><?php echo count($callLogs); ?></h3>
                <p class="text-white opacity-80">Appels</p>
            </div>
            <div class="glass-effect rounded-xl p-6 text-center">
                <i class="fas fa-sms text-3xl text-green-400 mb-3"></i>
                <h3 class="text-xl font-bold text-white"><?php echo count($smsLogs); ?></h3>
                <p class="text-white opacity-80">SMS</p>
            </div>
            <div class="glass-effect rounded-xl p-6 text-center">
                <i class="fas fa-server text-3xl text-purple-400 mb-3"></i>
                <h3 class="text-xl font-bold text-white"><?php echo count($webhookLogs); ?></h3>
                <p class="text-white opacity-80">Webhooks</p>
            </div>
        </div>

        <!-- Onglets -->
        <div class="glass-effect rounded-xl p-6">
            <div class="flex space-x-4 mb-6">
                <button onclick="showTab('calls')" id="tab-calls" class="px-4 py-2 bg-blue-600 text-white rounded-lg transition duration-200">
                    <i class="fas fa-phone mr-2"></i>Appels
                </button>
                <button onclick="showTab('sms')" id="tab-sms" class="px-4 py-2 bg-gray-600 text-white rounded-lg transition duration-200">
                    <i class="fas fa-sms mr-2"></i>SMS
                </button>
                <button onclick="showTab('webhooks')" id="tab-webhooks" class="px-4 py-2 bg-gray-600 text-white rounded-lg transition duration-200">
                    <i class="fas fa-server mr-2"></i>Webhooks
                </button>
            </div>

            <!-- Logs d'appels -->
            <div id="content-calls" class="tab-content">
                <h3 class="text-lg font-bold text-white mb-4">Historique des appels</h3>
                <?php if (empty($callLogs)): ?>
                    <p class="text-white opacity-80">Aucun appel enregistré</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-white text-sm">
                            <thead>
                                <tr class="border-b border-gray-600">
                                    <th class="text-left py-2">Date/Heure</th>
                                    <th class="text-left py-2">De</th>
                                    <th class="text-left py-2">Vers</th>
                                    <th class="text-left py-2">Direction</th>
                                    <th class="text-left py-2">Call SID</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($callLogs as $log): ?>
                                    <?php $data = parseLogLine($log, 'calls'); ?>
                                    <?php if (!empty($data)): ?>
                                        <tr class="border-b border-gray-700">
                                            <td class="py-2"><?php echo htmlspecialchars($data['timestamp'] ?? ''); ?></td>
                                            <td class="py-2"><?php echo htmlspecialchars($data['from'] ?? ''); ?></td>
                                            <td class="py-2"><?php echo htmlspecialchars($data['to'] ?? ''); ?></td>
                                            <td class="py-2">
                                                <span class="px-2 py-1 rounded text-xs <?php echo ($data['direction'] ?? '') === 'incoming' ? 'bg-green-600' : 'bg-blue-600'; ?>">
                                                    <?php echo htmlspecialchars($data['direction'] ?? ''); ?>
                                                </span>
                                            </td>
                                            <td class="py-2 font-mono text-xs"><?php echo htmlspecialchars($data['call_sid'] ?? ''); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Logs SMS -->
            <div id="content-sms" class="tab-content hidden">
                <h3 class="text-lg font-bold text-white mb-4">Historique des SMS</h3>
                <?php if (empty($smsLogs)): ?>
                    <p class="text-white opacity-80">Aucun SMS enregistré</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($smsLogs as $log): ?>
                            <?php $data = parseLogLine($log, 'sms'); ?>
                            <?php if (!empty($data)): ?>
                                <div class="bg-gray-800 p-4 rounded-lg">
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <span class="text-white font-medium"><?php echo htmlspecialchars($data['from'] ?? ''); ?></span>
                                            <span class="text-gray-400">→</span>
                                            <span class="text-white font-medium"><?php echo htmlspecialchars($data['to'] ?? ''); ?></span>
                                        </div>
                                        <div class="text-right">
                                            <span class="px-2 py-1 rounded text-xs <?php echo ($data['direction'] ?? '') === 'received' ? 'bg-green-600' : 'bg-blue-600'; ?>">
                                                <?php echo htmlspecialchars($data['direction'] ?? ''); ?>
                                            </span>
                                            <div class="text-gray-400 text-xs mt-1"><?php echo htmlspecialchars($data['timestamp'] ?? ''); ?></div>
                                        </div>
                                    </div>
                                    <p class="text-white"><?php echo htmlspecialchars($data['body'] ?? ''); ?></p>
                                    <div class="text-gray-400 text-xs mt-2 font-mono"><?php echo htmlspecialchars($data['message_sid'] ?? ''); ?></div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Logs Webhooks -->
            <div id="content-webhooks" class="tab-content hidden">
                <h3 class="text-lg font-bold text-white mb-4">Logs Webhooks</h3>
                <?php if (empty($webhookLogs)): ?>
                    <p class="text-white opacity-80">Aucun webhook enregistré</p>
                <?php else: ?>
                    <div class="space-y-2">
                        <?php foreach ($webhookLogs as $log): ?>
                            <div class="bg-gray-800 p-3 rounded text-white font-mono text-xs">
                                <?php echo htmlspecialchars($log); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Cacher tous les contenus
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            // Réinitialiser tous les boutons
            document.querySelectorAll('[id^="tab-"]').forEach(button => {
                button.classList.remove('bg-blue-600');
                button.classList.add('bg-gray-600');
            });
            
            // Afficher le contenu sélectionné
            document.getElementById('content-' + tabName).classList.remove('hidden');
            document.getElementById('tab-' + tabName).classList.remove('bg-gray-600');
            document.getElementById('tab-' + tabName).classList.add('bg-blue-600');
        }
        
        // Auto-refresh toutes les 30 secondes
        setInterval(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
