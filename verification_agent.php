<?php
require_once 'inc/functions/connexion.php';

// Récupérer le code de l'agent
$code = isset($_GET['code']) ? trim($_GET['code']) : '';

$agent = null;
$error = null;

if (!empty($code)) {
    // Rechercher l'agent par son numéro
    $stmt = $conn->prepare("
        SELECT 
            agents.id_agent,
            agents.numero_agent,
            agents.nom,
            agents.prenom,
            agents.contact,
            agents.avatar,
            agents.date_ajout,
            CONCAT(chef_equipe.nom, ' ', chef_equipe.prenoms) AS chef_equipe,
            chef_equipe.id_chef
        FROM agents
        LEFT JOIN chef_equipe ON agents.id_chef = chef_equipe.id_chef
        WHERE agents.numero_agent = ? AND agents.date_suppression IS NULL
    ");
    $stmt->execute([$code]);
    $agent = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$agent) {
        $error = "Aucun agent trouvé avec ce code.";
    }
} else {
    $error = "Code agent non spécifié.";
}

// Chemin de la photo
$photoUrl = 'dossiers_images/agents.png';
if ($agent && !empty($agent['avatar'])) {
    $photoUrl = 'dossiers_images/' . $agent['avatar'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification Agent - UNIPALM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1b5e20 0%, #4caf50 50%, #81c784 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .verification-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 100%);
            color: white;
            padding: 25px;
            text-align: center;
        }
        
        .card-header img {
            width: 80px;
            height: 80px;
            margin-bottom: 15px;
        }
        
        .card-header h1 {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }
        
        .card-header p {
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        .agent-photo {
            text-align: center;
            margin-top: -50px;
            position: relative;
            z-index: 10;
        }
        
        .agent-photo img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 5px solid white;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
            object-fit: cover;
            background: white;
        }
        
        .card-body {
            padding: 20px 30px 30px;
        }
        
        .agent-name {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .agent-name h2 {
            color: #1b5e20;
            font-size: 1.5rem;
            margin-bottom: 5px;
        }
        
        .agent-name .badge {
            background: linear-gradient(135deg, #4caf50 0%, #2e7d32 100%);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        
        .info-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .info-section h3 {
            color: #1b5e20;
            font-size: 1rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #666;
            font-weight: 500;
        }
        
        .info-value {
            color: #1b5e20;
            font-weight: 600;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 30px;
            font-weight: 600;
            margin-top: 15px;
        }
        
        .status-valid {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-invalid {
            background: #ffebee;
            color: #c62828;
        }
        
        .verification-footer {
            text-align: center;
            padding: 20px;
            background: #f5f5f5;
            border-top: 1px solid #e0e0e0;
        }
        
        .verification-footer p {
            color: #666;
            font-size: 0.85rem;
            margin-bottom: 10px;
        }
        
        .verification-footer .url {
            color: #1b5e20;
            font-weight: 600;
            word-break: break-all;
        }
        
        .error-container {
            text-align: center;
            padding: 40px;
        }
        
        .error-container i {
            font-size: 4rem;
            color: #c62828;
            margin-bottom: 20px;
        }
        
        .error-container h2 {
            color: #c62828;
            margin-bottom: 10px;
        }
        
        .error-container p {
            color: #666;
        }
        
        @media (max-width: 576px) {
            .card-header h1 {
                font-size: 1.2rem;
            }
            
            .agent-name h2 {
                font-size: 1.2rem;
            }
            
            .info-row {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="verification-card">
        <div class="card-header">
            <img src="dist/img/cartes/logo.png" alt="UNIPALM Logo" onerror="this.src='dist/img/logo.png'">
            <h1>UNIPALM COOP-CA</h1>
            <p>Vérification d'identité Agent</p>
        </div>
        
        <?php if ($agent): ?>
        <div class="agent-photo">
            <img src="<?= htmlspecialchars($photoUrl) ?>" alt="Photo Agent" onerror="this.src='dossiers_images/agents.png'">
        </div>
        
        <div class="card-body">
            <div class="agent-name">
                <h2><?= htmlspecialchars(strtoupper($agent['nom']) . ' ' . ucfirst(strtolower($agent['prenom']))) ?></h2>
                <span class="badge"><i class="fas fa-id-badge me-1"></i> AGENT</span>
            </div>
            
            <div class="info-section">
                <h3><i class="fas fa-info-circle"></i> Informations de l'agent</h3>
                
                <div class="info-row">
                    <span class="info-label">N° Agent</span>
                    <span class="info-value"><?= htmlspecialchars($agent['numero_agent']) ?></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Contact</span>
                    <span class="info-value"><?= htmlspecialchars($agent['contact']) ?></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Chef d'équipe</span>
                    <span class="info-value"><?= htmlspecialchars($agent['chef_equipe'] ?? 'Non assigné') ?></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Date d'inscription</span>
                    <span class="info-value"><?= date('d/m/Y', strtotime($agent['date_ajout'])) ?></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Validité</span>
                    <span class="info-value"><?= date('d/m/Y', strtotime($agent['date_ajout'] . ' +1 year')) ?></span>
                </div>
            </div>
            
            <div class="text-center">
                <div class="status-badge status-valid">
                    <i class="fas fa-check-circle"></i>
                    Agent vérifié et actif
                </div>
            </div>
        </div>
        
        <?php else: ?>
        <div class="error-container">
            <i class="fas fa-exclamation-triangle"></i>
            <h2>Agent non trouvé</h2>
            <p><?= htmlspecialchars($error) ?></p>
            <div class="status-badge status-invalid mt-3">
                <i class="fas fa-times-circle"></i>
                Vérification échouée
            </div>
        </div>
        <?php endif; ?>
        
        <div class="verification-footer">
            <p>URL de vérification :</p>
            <span class="url">https://unipalm.ci/verification_agent.php?code=<?= htmlspecialchars($code) ?></span>
        </div>
    </div>
</body>
</html>
