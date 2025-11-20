<?php
require_once '../inc/functions/connexion.php';
session_start();

// Vérifier les permissions (optionnel)
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

echo "<!DOCTYPE html>";
echo "<html lang='fr'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Génération des Numéros d'Agent</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>";
echo "</head>";
echo "<body class='bg-light'>";
echo "<div class='container mt-5'>";

echo "<div class='row justify-content-center'>";
echo "<div class='col-md-10'>";
echo "<div class='card shadow'>";
echo "<div class='card-header bg-warning text-dark'>";
echo "<h2 class='mb-0'><i class='fas fa-magic'></i> Génération des Numéros d'Agent</h2>";
echo "</div>";
echo "<div class='card-body'>";

// Inclure la fonction de génération
require_once 'traitement_agents.php';

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
    
    echo "<div class='row mb-4'>";
    echo "<div class='col-md-4'>";
    echo "<div class='card bg-primary text-white'>";
    echo "<div class='card-body text-center'>";
    echo "<h3>" . count($agents) . "</h3>";
    echo "<p class='mb-0'>Total Agents</p>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='col-md-4'>";
    echo "<div class='card bg-success text-white'>";
    echo "<div class='card-body text-center'>";
    echo "<h3>" . count($agents_avec_numero) . "</h3>";
    echo "<p class='mb-0'>Avec Numéro</p>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='col-md-4'>";
    echo "<div class='card bg-danger text-white'>";
    echo "<div class='card-body text-center'>";
    echo "<h3>" . count($agents_sans_numero) . "</h3>";
    echo "<p class='mb-0'>Sans Numéro</p>";
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
        // Afficher les agents sans numéro
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
                $numero_propose = genererNumeroAgent($conn, $agent['id_chef']);
                $numeros_proposes[$agent['id_agent']] = $numero_propose;
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
            if ($agent['id_chef']) {
                echo "<span class='badge bg-primary'>$numero_propose</span>";
            } else {
                echo "<span class='badge bg-danger'>Chef manquant</span>";
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
                    $numero_agent = genererNumeroAgent($conn, $agent['id_chef']);
                    
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
            echo "<h5>Détails :</h5>";
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
                echo "<li>Vérifiez la page <a href='agents.php' class='btn btn-sm btn-primary'>agents.php</a></li>";
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
            echo "<p><strong>Attention :</strong> Cette action est irréversible.</p>";
            
            echo "<form method='post'>";
            echo "<div class='d-flex gap-3'>";
            echo "<button type='submit' name='generer_numeros' class='btn btn-success btn-lg'>";
            echo "<i class='fas fa-magic'></i> Générer les Numéros";
            echo "</button>";
            echo "<a href='agents.php' class='btn btn-secondary btn-lg'>";
            echo "<i class='fas fa-arrow-left'></i> Retour aux Agents";
            echo "</a>";
            echo "</div>";
            echo "</form>";
            echo "</div>";
        }
    }
    
    // Afficher les agents avec numéro (pour vérification)
    if (!empty($agents_avec_numero)) {
        echo "<div class='mt-4'>";
        echo "<h4><i class='fas fa-list'></i> Agents avec Numéro (Vérification)</h4>";
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
        
        foreach ($agents_avec_numero as $agent) {
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
        echo "</div>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h4><i class='fas fa-exclamation-triangle'></i> Erreur</h4>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<div class='mt-4 text-center'>";
echo "<a href='agents.php' class='btn btn-primary me-2'>";
echo "<i class='fas fa-users'></i> Retour aux Agents";
echo "</a>";
echo "<a href='test_numero_agent.php' class='btn btn-secondary'>";
echo "<i class='fas fa-flask'></i> Test Numéros";
echo "</a>";
echo "</div>";

echo "</div>"; // card-body
echo "</div>"; // card
echo "</div>"; // col
echo "</div>"; // row
echo "</div>"; // container

echo "<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>";
echo "</body>";
echo "</html>";
?>
