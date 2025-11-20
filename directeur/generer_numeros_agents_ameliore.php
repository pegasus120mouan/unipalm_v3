<?php
require_once '../inc/functions/connexion.php';
session_start();

// Vérifier les permissions
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

/**
 * Génère un numéro d'agent unique au format AGT-25-CHEF-INITIALES+SEQUENCE
 */
function genererNumeroAgentAmeliore($conn, $id_chef, $nom_agent, $prenom_agent) {
    $annee_courte = date('y'); // Année sur 2 chiffres (25 pour 2025)
    
    // Récupérer le nom du chef et créer un code
    $stmt = $conn->prepare("SELECT nom, prenoms FROM chef_equipe WHERE id_chef = ?");
    $stmt->execute([$id_chef]);
    $chef = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$chef) {
        throw new Exception("Chef d'équipe non trouvé");
    }
    
    // Créer un code chef à partir du nom (3 premières lettres en majuscules)
    $code_chef = strtoupper(substr($chef['nom'], 0, 3));
    
    // Créer les initiales de l'agent (première lettre du nom + première lettre du prénom)
    $initiale_nom = strtoupper(substr($nom_agent, 0, 1));
    $initiale_prenom = strtoupper(substr($prenom_agent, 0, 1));
    $initiales_agent = $initiale_nom . $initiale_prenom;
    
    // Format: AGT-25-ZAL-YD (AGT + Année + Code Chef + Initiales Agent)
    $prefixe = "AGT-" . $annee_courte . "-" . $code_chef . "-" . $initiales_agent;
    
    // Récupérer le dernier numéro d'agent créé avec ces initiales pour ce chef cette année
    $stmt = $conn->prepare("
        SELECT numero_agent 
        FROM agents 
        WHERE numero_agent LIKE ? 
        ORDER BY numero_agent DESC 
        LIMIT 1
    ");
    $stmt->execute([$prefixe . '%']);
    $dernier_numero = $stmt->fetchColumn();
    
    if ($dernier_numero) {
        // Extraire le numéro séquentiel et l'incrémenter
        $sequence = (int)substr($dernier_numero, -2) + 1;
    } else {
        // Premier agent avec ces initiales pour ce chef cette année
        $sequence = 1;
    }
    
    // Formater avec des zéros à gauche (2 chiffres pour la séquence)
    $sequence_format = str_pad($sequence, 2, '0', STR_PAD_LEFT);
    $numero_agent = $prefixe . $sequence_format;
    
    // Vérifier l'unicité (sécurité supplémentaire)
    $stmt = $conn->prepare("SELECT COUNT(*) FROM agents WHERE numero_agent = ?");
    $stmt->execute([$numero_agent]);
    
    if ($stmt->fetchColumn() > 0) {
        // Si le numéro existe déjà, incrémenter jusqu'à trouver un numéro libre
        $sequence++;
        $sequence_format = str_pad($sequence, 2, '0', STR_PAD_LEFT);
        $numero_agent = $prefixe . $sequence_format;
    }
    
    return $numero_agent;
}

?>
<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Génération des Numéros d'Agent - Amélioré</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>
</head>
<body class='bg-light'>
<div class='container mt-5'>

<div class='row justify-content-center'>
<div class='col-md-12'>
<div class='card shadow'>
<div class='card-header bg-primary text-white'>
    <h2 class='mb-0'><i class='fas fa-magic'></i> Génération des Numéros d'Agent - Version Améliorée</h2>
</div>
<div class='card-body'>

<?php
try {
    // 1. Identifier les agents sans numéro
    echo "<div class='alert alert-info'>";
    echo "<h4><i class='fas fa-search'></i> Analyse des Agents</h4>";
    echo "</div>";
    
    $stmt = $conn->prepare("
        SELECT 
            a.id_agent,
            a.nom,
            a.prenom,
            a.id_chef,
            a.numero_agent,
            CONCAT(c.nom, ' ', c.prenoms) as nom_chef,
            a.date_ajout
        FROM agents a
        LEFT JOIN chef_equipe c ON a.id_chef = c.id_chef
        WHERE a.date_suppression IS NULL
        ORDER BY a.id_chef, a.date_ajout ASC
    ");
    $stmt->execute();
    $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $agents_sans_numero = array_filter($agents, function($agent) {
        return empty($agent['numero_agent']);
    });
    
    $agents_avec_numero = array_filter($agents, function($agent) {
        return !empty($agent['numero_agent']);
    });
    
    // Statistiques
    echo "<div class='row mb-4'>";
    echo "<div class='col-md-3'>";
    echo "<div class='card bg-primary text-white'>";
    echo "<div class='card-body text-center'>";
    echo "<h3>" . count($agents) . "</h3>";
    echo "<p class='mb-0'>Total Agents</p>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='col-md-3'>";
    echo "<div class='card bg-success text-white'>";
    echo "<div class='card-body text-center'>";
    echo "<h3>" . count($agents_avec_numero) . "</h3>";
    echo "<p class='mb-0'>Avec Numéro</p>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='col-md-3'>";
    echo "<div class='card bg-danger text-white'>";
    echo "<div class='card-body text-center'>";
    echo "<h3>" . count($agents_sans_numero) . "</h3>";
    echo "<p class='mb-0'>Sans Numéro</p>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='col-md-3'>";
    echo "<div class='card bg-info text-white'>";
    echo "<div class='card-body text-center'>";
    echo "<h3>" . date('Y') . "</h3>";
    echo "<p class='mb-0'>Année Courante</p>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    if (empty($agents_sans_numero)) {
        echo "<div class='alert alert-success'>";
        echo "<h4><i class='fas fa-check-circle'></i> Tous les agents ont déjà un numéro !</h4>";
        echo "<p>Aucune action nécessaire.</p>";
        echo "</div>";
    } else {
        // Afficher les agents sans numéro avec aperçu des numéros
        echo "<div class='alert alert-warning'>";
        echo "<h4><i class='fas fa-exclamation-triangle'></i> Agents sans numéro détectés</h4>";
        echo "<p>Les agents suivants n'ont pas de numéro d'agent :</p>";
        
        echo "<div class='table-responsive'>";
        echo "<table class='table table-striped table-sm'>";
        echo "<thead class='table-dark'>";
        echo "<tr>";
        echo "<th>ID</th>";
        echo "<th>Nom</th>";
        echo "<th>Prénom</th>";
        echo "<th>Chef d'Équipe</th>";
        echo "<th>Date Ajout</th>";
        echo "<th>Numéro Proposé</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";
        
        $numeros_proposes = [];
        foreach ($agents_sans_numero as $agent) {
            if ($agent['id_chef']) {
                try {
                    $numero_propose = genererNumeroAgentAmeliore($conn, $agent['id_chef'], $agent['nom'], $agent['prenom']);
                    $numeros_proposes[$agent['id_agent']] = $numero_propose;
                } catch (Exception $e) {
                    $numero_propose = "Erreur: " . $e->getMessage();
                }
            } else {
                $numero_propose = "Chef manquant";
            }
            
            echo "<tr>";
            echo "<td>" . $agent['id_agent'] . "</td>";
            echo "<td>" . htmlspecialchars($agent['nom']) . "</td>";
            echo "<td>" . htmlspecialchars($agent['prenom']) . "</td>";
            echo "<td>" . htmlspecialchars($agent['nom_chef'] ?? 'Non assigné') . "</td>";
            echo "<td>" . date('d/m/Y', strtotime($agent['date_ajout'])) . "</td>";
            echo "<td>";
            if ($agent['id_chef'] && strpos($numero_propose, 'Erreur') === false) {
                echo "<span class='badge bg-primary'>$numero_propose</span>";
            } else {
                echo "<span class='badge bg-danger'>$numero_propose</span>";
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";
        echo "</div>";
        echo "</div>";
        
        // Traitement de la génération
        if (isset($_POST['generer_numeros'])) {
            echo "<div class='alert alert-info'>";
            echo "<h4><i class='fas fa-cogs'></i> Génération en cours...</h4>";
            echo "</div>";
            
            $conn->beginTransaction();
            $succes = 0;
            $erreurs = 0;
            $details = [];
            
            foreach ($agents_sans_numero as $agent) {
                if (!$agent['id_chef']) {
                    $erreurs++;
                    $details[] = [
                        'agent' => $agent['nom'] . ' ' . $agent['prenom'],
                        'status' => 'error',
                        'message' => 'Chef d\'équipe manquant'
                    ];
                    continue;
                }
                
                try {
                    $numero_agent = genererNumeroAgentAmeliore($conn, $agent['id_chef'], $agent['nom'], $agent['prenom']);
                    
                    $stmt_update = $conn->prepare("
                        UPDATE agents 
                        SET numero_agent = ?, 
                            date_modification = NOW() 
                        WHERE id_agent = ?
                    ");
                    $result = $stmt_update->execute([$numero_agent, $agent['id_agent']]);
                    
                    if ($result) {
                        $succes++;
                        $details[] = [
                            'agent' => $agent['nom'] . ' ' . $agent['prenom'],
                            'status' => 'success',
                            'message' => "Numéro généré: $numero_agent"
                        ];
                    } else {
                        $erreurs++;
                        $details[] = [
                            'agent' => $agent['nom'] . ' ' . $agent['prenom'],
                            'status' => 'error',
                            'message' => 'Erreur lors de la mise à jour'
                        ];
                    }
                } catch (Exception $e) {
                    $erreurs++;
                    $details[] = [
                        'agent' => $agent['nom'] . ' ' . $agent['prenom'],
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ];
                }
            }
            
            if ($erreurs == 0) {
                $conn->commit();
                echo "<div class='alert alert-success'>";
                echo "<h4><i class='fas fa-check-circle'></i> Génération Terminée avec Succès !</h4>";
                echo "<p><strong>$succes</strong> numéros d'agent générés avec succès.</p>";
            } else {
                $conn->rollback();
                echo "<div class='alert alert-danger'>";
                echo "<h4><i class='fas fa-exclamation-circle'></i> Génération Interrompue</h4>";
                echo "<p><strong>$erreurs</strong> erreur(s) détectée(s). Aucune modification appliquée.</p>";
            }
            
            // Afficher les détails
            echo "<h5>Détails de la génération :</h5>";
            echo "<div class='table-responsive'>";
            echo "<table class='table table-sm'>";
            echo "<thead><tr><th>Agent</th><th>Statut</th><th>Message</th></tr></thead>";
            echo "<tbody>";
            
            foreach ($details as $detail) {
                $badge_class = $detail['status'] === 'success' ? 'bg-success' : 'bg-danger';
                $icon = $detail['status'] === 'success' ? 'fa-check' : 'fa-times';
                
                echo "<tr>";
                echo "<td>" . htmlspecialchars($detail['agent']) . "</td>";
                echo "<td><span class='badge $badge_class'><i class='fas $icon'></i> " . ucfirst($detail['status']) . "</span></td>";
                echo "<td>" . htmlspecialchars($detail['message']) . "</td>";
                echo "</tr>";
            }
            echo "</tbody>";
            echo "</table>";
            echo "</div>";
            echo "</div>";
            
            if ($succes > 0) {
                echo "<div class='alert alert-info'>";
                echo "<h5><i class='fas fa-info-circle'></i> Actions Recommandées</h5>";
                echo "<ul>";
                echo "<li>Vérifiez la page <a href='comptes_agents.php' class='btn btn-sm btn-primary'>Comptes Agents</a></li>";
                echo "<li>Informez les chefs d'équipe des nouveaux numéros</li>";
                echo "<li>Mettez à jour vos documents si nécessaire</li>";
                echo "</ul>";
                echo "</div>";
            }
            
        } else {
            // Formulaire de confirmation
            echo "<div class='alert alert-warning'>";
            echo "<h4><i class='fas fa-question-circle'></i> Confirmer la Génération</h4>";
            echo "<p>Voulez-vous générer automatiquement les numéros d'agent pour tous les agents sans numéro ?</p>";
            echo "<p><strong>Format des numéros :</strong> AGT-" . date('y') . "-[CODE_CHEF]-[INITIALES_AGENT][SEQUENCE]</p>";
            echo "<p><strong>Exemple :</strong> AGT-25-ZAL-YD01 (Agent 2025, Chef Zalissa, Yves Dupont, séquence 01)</p>";
            echo "<p><strong>Attention :</strong> Cette action est irréversible.</p>";
            
            echo "<form method='post'>";
            echo "<div class='d-flex gap-3'>";
            echo "<button type='submit' name='generer_numeros' class='btn btn-success btn-lg'>";
            echo "<i class='fas fa-magic'></i> Générer les Numéros (" . count($agents_sans_numero) . " agents)";
            echo "</button>";
            echo "<a href='comptes_agents.php' class='btn btn-secondary btn-lg'>";
            echo "<i class='fas fa-arrow-left'></i> Retour aux Comptes Agents";
            echo "</a>";
            echo "</div>";
            echo "</form>";
            echo "</div>";
        }
    }
    
    // Afficher un échantillon des agents avec numéro (pour vérification)
    if (!empty($agents_avec_numero)) {
        $echantillon = array_slice($agents_avec_numero, 0, 10); // Afficher seulement les 10 premiers
        echo "<div class='mt-4'>";
        echo "<h4><i class='fas fa-list'></i> Échantillon des Agents avec Numéro (10 premiers)</h4>";
        echo "<div class='table-responsive'>";
        echo "<table class='table table-striped table-sm'>";
        echo "<thead class='table-success'>";
        echo "<tr>";
        echo "<th>Numéro Agent</th>";
        echo "<th>Nom</th>";
        echo "<th>Prénom</th>";
        echo "<th>Chef d'Équipe</th>";
        echo "<th>Date Ajout</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";
        
        foreach ($echantillon as $agent) {
            echo "<tr>";
            echo "<td><span class='badge bg-success'>" . htmlspecialchars($agent['numero_agent']) . "</span></td>";
            echo "<td>" . htmlspecialchars($agent['nom']) . "</td>";
            echo "<td>" . htmlspecialchars($agent['prenom']) . "</td>";
            echo "<td>" . htmlspecialchars($agent['nom_chef'] ?? 'Non assigné') . "</td>";
            echo "<td>" . date('d/m/Y', strtotime($agent['date_ajout'])) . "</td>";
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";
        
        if (count($agents_avec_numero) > 10) {
            echo "<p class='text-muted'>... et " . (count($agents_avec_numero) - 10) . " autres agents avec numéro</p>";
        }
        
        echo "</div>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h4><i class='fas fa-exclamation-triangle'></i> Erreur</h4>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<div class='mt-4 text-center'>
    <a href='comptes_agents.php' class='btn btn-primary me-2'>
        <i class='fas fa-users'></i> Retour aux Comptes Agents
    </a>
    <a href='verifier_numeros_agents.php' class='btn btn-secondary me-2'>
        <i class='fas fa-search'></i> Vérifier les Numéros
    </a>
    <a href='debug_generer_numeros.php' class='btn btn-info'>
        <i class='fas fa-bug'></i> Debug
    </a>
</div>

</div>
</div>
</div>
</div>
</div>

<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>
</body>
</html>
