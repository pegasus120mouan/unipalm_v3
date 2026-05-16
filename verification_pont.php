<?php
// Configuration et connexion à la base de données
require_once 'pont_verification.php';

// Récupérer le code du pont depuis l'URL
$code_pont = isset($_GET['code']) ? trim($_GET['code']) : '';
$pont = null;
$error_message = '';

if ($code_pont) {
    try {
        $pont = getPontBasculeByCode($conn, $code_pont);
        if (!$pont) {
            $error_message = "Aucun pont-bascule trouvé avec le code : " . htmlspecialchars($code_pont);
        } else {
            // Enregistrer la consultation (optionnel)
            logVerification($conn, $code_pont);
        }
    } catch (Exception $e) {
        $error_message = "Erreur lors de la récupération des données : " . $e->getMessage();
        
        // En mode développement, afficher plus de détails
        if ($_SERVER['HTTP_HOST'] !== 'unipalm.ci' && $_SERVER['HTTP_HOST'] !== 'www.unipalm.ci') {
            $error_message .= "<br><br><strong>Détails techniques :</strong><br>";
            $error_message .= "Fichier: " . $e->getFile() . "<br>";
            $error_message .= "Ligne: " . $e->getLine() . "<br>";
            $error_message .= "Trace: " . nl2br($e->getTraceAsString());
        }
    }
} else {
    $error_message = "Code du pont-bascule manquant dans l'URL";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification Pont-Bascule - UniPalm</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --danger-gradient: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
            --glass-bg: rgba(255, 255, 255, 0.25);
            --glass-border: rgba(255, 255, 255, 0.18);
            --shadow-light: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            --border-radius: 15px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--primary-gradient);
            min-height: 100vh;
            padding: 20px 0;
        }

        .verification-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 15px;
        }

        .header-card {
            background: var(--glass-bg);
            backdrop-filter: blur(15px);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-light);
            text-align: center;
            color: white;
        }

        .header-card h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .header-card .subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            font-weight: 300;
        }

        .pont-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 2.5rem;
            box-shadow: var(--shadow-light);
            margin-bottom: 2rem;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .pont-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid #f8f9fa;
        }

        .pont-code {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .pont-nom {
            font-size: 1.3rem;
            color: #6c757d;
            font-weight: 500;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .info-item {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 12px;
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
        }

        .info-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .info-label {
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .info-value {
            font-size: 1.2rem;
            color: #2c3e50;
            font-weight: 600;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .status-actif {
            background: var(--success-gradient);
            color: white;
        }

        .status-inactif {
            background: var(--danger-gradient);
            color: white;
        }

        .coordinates-section {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }

        .coordinates-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1rem;
            text-align: center;
        }

        .coordinates-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .coordinate-item {
            text-align: center;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .coordinate-label {
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .coordinate-value {
            font-size: 1.1rem;
            color: #2c3e50;
            font-weight: 700;
            font-family: 'Courier New', monospace;
        }

        .map-link {
            display: inline-flex;
            align-items: center;
            background: var(--primary-gradient);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            margin: 0 auto;
        }

        .map-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            color: white;
            text-decoration: none;
        }

        .error-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 3rem;
            box-shadow: var(--shadow-light);
            text-align: center;
            border-left: 5px solid #dc3545;
        }

        .error-icon {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 1rem;
        }

        .error-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1rem;
        }

        .error-message {
            color: #6c757d;
            font-size: 1.1rem;
            line-height: 1.6;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            background: var(--glass-bg);
            backdrop-filter: blur(15px);
            border: 1px solid var(--glass-border);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-top: 2rem;
        }

        .back-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            color: white;
            text-decoration: none;
        }

        .qr-info {
            background: linear-gradient(135deg, #e8f5e8, #f0f8f0);
            border: 1px solid #28a745;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: center;
        }

        .qr-info-icon {
            font-size: 2rem;
            color: #28a745;
            margin-bottom: 0.5rem;
        }

        .qr-info-text {
            color: #155724;
            font-weight: 500;
            margin-bottom: 0;
        }

        @media (max-width: 768px) {
            .header-card h1 {
                font-size: 2rem;
            }
            
            .pont-code {
                font-size: 1.5rem;
            }
            
            .coordinates-grid {
                grid-template-columns: 1fr;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <!-- Header -->
        <div class="header-card">
            <h1><i class="fas fa-qrcode mr-3"></i>Vérification Pont-Bascule</h1>
            <p class="subtitle">Système de vérification UniPalm</p>
        </div>

        <?php if ($pont): ?>
            <!-- QR Code Info -->
            <div class="qr-info">
                <div class="qr-info-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <p class="qr-info-text">QR Code scanné avec succès - Pont-bascule vérifié</p>
            </div>

            <!-- Pont Information Card -->
            <div class="pont-card">
                <!-- Header Section -->
                <div class="pont-header">
                    <div class="pont-code"><?= htmlspecialchars($pont['code_pont']) ?></div>
                    <div class="pont-nom">
                        <?= $pont['nom_pont'] ? htmlspecialchars($pont['nom_pont']) : 'Nom non défini' ?>
                    </div>
                </div>

                <!-- Information Grid -->
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-user-tie mr-2"></i>Gérant
                        </div>
                        <div class="info-value"><?= htmlspecialchars($pont['gerant']) ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-handshake mr-2"></i>Coopérative
                        </div>
                        <div class="info-value">
                            <?= $pont['cooperatif'] ? htmlspecialchars($pont['cooperatif']) : 'Non spécifiée' ?>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-info-circle mr-2"></i>Statut
                        </div>
                        <div class="info-value">
                            <span class="status-badge <?= $pont['statut'] === 'Actif' ? 'status-actif' : 'status-inactif' ?>">
                                <i class="fas fa-<?= $pont['statut'] === 'Actif' ? 'check-circle' : 'pause-circle' ?> mr-2"></i>
                                <?= htmlspecialchars($pont['statut']) ?>
                            </span>
                        </div>
                    </div>

                </div>

            </div>

        <?php else: ?>
            <!-- Error Card -->
            <div class="error-card">
                <div class="error-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h2 class="error-title">Pont-Bascule Non Trouvé</h2>
                <p class="error-message"><?= htmlspecialchars($error_message) ?></p>
                
                <?php if ($code_pont): ?>
                    <div class="mt-4">
                        <strong>Code recherché :</strong> 
                        <code><?= htmlspecialchars($code_pont) ?></code>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Back Button -->
        <div class="text-center">
            <a href="pages/ponts.php" class="back-button">
                <i class="fas fa-arrow-left mr-2"></i>
                Retour à la gestion des ponts
            </a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Animation d'entrée
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.pont-card, .error-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });
        });

        // Copier les coordonnées au clic
        document.querySelectorAll('.coordinate-value').forEach(element => {
            element.addEventListener('click', function() {
                navigator.clipboard.writeText(this.textContent).then(() => {
                    const originalText = this.textContent;
                    this.textContent = 'Copié!';
                    this.style.color = '#28a745';
                    
                    setTimeout(() => {
                        this.textContent = originalText;
                        this.style.color = '#2c3e50';
                    }, 1500);
                });
            });
        });
    </script>
</body>
</html>
