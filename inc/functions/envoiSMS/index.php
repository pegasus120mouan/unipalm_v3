<?php
require_once 'vendor/autoload.php';
require_once 'config.php';

$message = '';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phoneNumber = $_POST['phone_number'] ?? '';
    $messageText = $_POST['message'] ?? '';
    
    if (!empty($phoneNumber) && !empty($messageText)) {
        try {
            $smsService = createSmsService();
            
            $formattedPhone = $smsService->formatPhoneNumber($phoneNumber);
            $result = $smsService->sendSms($formattedPhone, $messageText);
            
            if ($result['success']) {
                $provider = $_ENV['SMS_PROVIDER'] ?? 'twilio';
                $success = "SMS envoyé avec succès à " . $result['to'] . " via " . ucfirst($provider);
            } else {
                $error = $result['error'];
            }
        } catch (Exception $e) {
            $error = "Erreur de configuration: " . $e->getMessage();
        }
    } else {
        $error = "Veuillez remplir tous les champs";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $_ENV['APP_NAME']; ?></title>
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
        <div class="max-w-md mx-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="glass-effect rounded-full w-20 h-20 mx-auto mb-4 flex items-center justify-center">
                    <i class="fas fa-sms text-3xl text-white"></i>
                </div>
                <h1 class="text-3xl font-bold text-white mb-2"><?php echo $_ENV['APP_NAME']; ?></h1>
                <p class="text-white opacity-80">Envoyez des SMS facilement</p>
            </div>

            <!-- Alert Messages -->
            <?php if ($success): ?>
                <div class="bg-green-500 text-white p-4 rounded-lg mb-6 flex items-center">
                    <i class="fas fa-check-circle mr-3"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-500 text-white p-4 rounded-lg mb-6 flex items-center">
                    <i class="fas fa-exclamation-circle mr-3"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- SMS Form -->
            <div class="glass-effect rounded-xl p-6 shadow-xl">
                <form method="POST" id="smsForm">
                    <div class="mb-6">
                        <label for="phone_number" class="block text-white font-medium mb-2">
                            <i class="fas fa-phone mr-2"></i>Numéro de téléphone
                        </label>
                        <input 
                            type="tel" 
                            id="phone_number" 
                            name="phone_number" 
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                            placeholder="2250101010101"
                            value="<?php echo htmlspecialchars($_POST['phone_number'] ?? ''); ?>"
                            required
                        >
                        <small class="text-white opacity-70 text-sm">Format international requis (ex: +33123456789)</small>
                    </div>

                    <div class="mb-6">
                        <label for="message" class="block text-white font-medium mb-2">
                            <i class="fas fa-comment mr-2"></i>Message
                        </label>
                        <textarea 
                            id="message" 
                            name="message" 
                            rows="4" 
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 resize-none"
                            placeholder="Tapez votre message ici..."
                            maxlength="1600"
                            required
                        ><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                        <div class="flex justify-between items-center mt-2">
                            <small class="text-white opacity-70 text-sm">Maximum 1600 caractères</small>
                            <small id="charCount" class="text-white opacity-70 text-sm">0/1600</small>
                        </div>
                    </div>

                    <button 
                        type="submit" 
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition duration-200 transform hover:scale-105 focus:ring-4 focus:ring-blue-300"
                        id="sendBtn"
                    >
                        <i class="fas fa-paper-plane mr-2"></i>
                        <span id="btnText">Envoyer SMS</span>
                        <i class="fas fa-spinner fa-spin ml-2 hidden" id="spinner"></i>
                    </button>
                </form>
            </div>

            <!-- Info Card -->
            <div class="glass-effect rounded-xl p-4 mt-6">
                <h3 class="text-white font-semibold mb-2">
                    <i class="fas fa-info-circle mr-2"></i>Informations
                </h3>
                <ul class="text-white opacity-80 text-sm space-y-1">
                    <li>• Utilisez le format international (+33, +1, etc.)</li>
                    <li>• Maximum 1600 caractères par message</li>
                    <li>• Service sécurisé via Twilio</li>
                    <li>• Webhooks configurés pour réception</li>
                </ul>
                <div class="mt-4">
                    <a href="admin.php" class="inline-flex items-center text-white opacity-80 hover:opacity-100 text-sm transition duration-200">
                        <i class="fas fa-cogs mr-2"></i>Administration
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
</body>
</html>
