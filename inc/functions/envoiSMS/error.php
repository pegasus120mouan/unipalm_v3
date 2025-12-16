<?php
$errorMessage = $_GET['message'] ?? 'Une erreur est survenue';
$errorCode = $_GET['code'] ?? '500';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur - Envoi SMS</title>
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
    <div class="max-w-md mx-auto px-4">
        <div class="glass-effect rounded-xl p-8 text-center">
            <div class="text-red-400 text-6xl mb-4">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            
            <h1 class="text-3xl font-bold text-white mb-2">Erreur <?php echo htmlspecialchars($errorCode); ?></h1>
            
            <p class="text-white opacity-80 mb-6">
                <?php echo htmlspecialchars($errorMessage); ?>
            </p>
            
            <div class="space-y-3">
                <a href="index.php" class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition duration-200">
                    <i class="fas fa-home mr-2"></i>
                    Retour à l'accueil
                </a>
                
                <button onclick="window.history.back()" class="block w-full bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 px-6 rounded-lg transition duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Page précédente
                </button>
            </div>
            
            <div class="mt-6 text-white opacity-60 text-sm">
                <p>Si le problème persiste, vérifiez votre configuration Twilio</p>
            </div>
        </div>
    </div>
</body>
</html>
