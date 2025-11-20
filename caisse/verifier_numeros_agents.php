<?php
require_once '../inc/functions/connexion.php';
session_start();

echo "<!DOCTYPE html>";
echo "<html lang='fr'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Vérification des Numéros d'Agent</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>";
echo "</head>";
echo "<body class='bg-light'>";
echo "<div class='container mt-5'>";

echo "<div class='row justify-content-center'>";
echo "<div class='col-md-12'>";
echo "<div class='card shadow'>";
echo "<div class='card-header bg-info text-white'>";
echo "<h2 class='mb-0'><i class='fas fa-search'></i> Vérification des Numéros d'Agent</h2>";
echo "</div>";
echo "<div class='card-body'>";

try {
    // 1. Statistiques générales
    echo "<div class='row mb-4'>";
    
    // Total agents
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM agents WHERE date_suppression IS NULL");
    $stmt->execute();
    $total_agents = $stmt->fetchColumn();
    
    // Agents avec numéro
    $stmt = $conn->prepare("SELECT COUNT(*) as avec_numero FROM agents WHERE date_suppression IS NULL AND numero_agent IS NOT NULL AND numero_agent != ''");
    $stmt->execute();
    $avec_numero = $stmt->fetchColumn();
    
    // Agents sans numéro
    $sans_numero = $total_agents - $avec_numero;
    
    // Doublons
    $stmt = $conn->prepare("
        SELECT numero_agent, COUNT(*) as nb 
        FROM agents 
        WHERE date_suppression IS NULL 
        AND numero_agent IS NOT NULL 
        AND numero_agent != ''
        GROUP BY numero_agent 
        HAVING COUNT(*) > 1
    ");
    $stmt->execute();
    $doublons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='col-md-3'>";
    echo "<div class='card bg-primary text-white'>";
    echo "<div class='card-body text-center'>";
    echo "<h3>$total_agents</h3>";
    echo "<p class='mb-0'>Total Agents</p>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='col-md-3'>";
    echo "<div class='card bg-success text-white'>";
    echo "<div class='card-body text-center'>";
    echo "<h3>$avec_numero</h3>";
    echo "<p class='mb-0'>Avec Numéro</p>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='col-md-3'>";
    $color = $sans_numero > 0 ? 'bg-warning' : 'bg-success';
    echo "<div class='card $color text-white'>";
    echo "<div class='card-body text-center'>";
    echo "<h3>$sans_numero</h3>";
    echo "<p class='mb-0'>Sans Numéro</p>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='col-md-3'>";
    $color = count($doublons) > 0 ? 'bg-danger' : 'bg-success';
    echo "<div class='card $color text-white'>";
    echo "<div class='card-body text-center'>";
    echo "<h3>" . count($doublons) . "</h3>";
    echo "<p class='mb-0'>Doublons</p>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    echo "</div>";
    
    // 2. Agents sans numéro
    if ($sans_numero > 0) {
        echo "<div class='alert alert-warning'>";
        echo "<h4><i class='fas fa-exclamation-triangle'></i> Agents sans Numéro</h4>";
        
        $stmt = $conn->prepare("
            SELECT 
                a.id_agent,
                a.nom,
                a.prenom,
                a.id_chef,
                CONCAT(c.nom, ' ', c.prenoms) as nom_chef,
                a.date_ajout
            FROM agents a
            LEFT JOIN chef_equipe c ON a.id_chef = c.id_chef
            WHERE a.date_suppression IS NULL
            AND (a.numero_agent IS NULL OR a.numero_agent = '')
            ORDER BY a.id_chef, a.date_ajout
        ");
        $stmt->execute();
        $agents_sans_numero = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<div class='table-responsive'>";
        echo "<table class='table table-striped'>";
        echo "<thead class='table-dark'>";
        echo "<tr><th>ID</th><th>Nom</th><th>Prénom</th><th>Chef</th><th>Date Ajout</th></tr>";
        echo "</thead>";
        echo "<tbody>";
        
        foreach ($agents_sans_numero as $agent) {
            echo "<tr>";
            echo "<td>" . $agent['id_agent'] . "</td>";
            echo "<td>" . htmlspecialchars($agent['nom']) . "</td>";
            echo "<td>" . htmlspecialchars($agent['prenom']) . "</td>";
            echo "<td>" . htmlspecialchars($agent['nom_chef'] ?? 'Non assigné') . "</td>";
            echo "<td>" . date('d/m/Y', strtotime($agent['date_ajout'])) . "</td>";
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";
        echo "</div>";
        
        echo "<div class='mt-3'>";
        echo "<a href='generer_numeros_agents.php' class='btn btn-warning'>";
        echo "<i class='fas fa-magic'></i> Générer les Numéros Manquants";
        echo "</a>";
        echo "</div>";
        echo "</div>";
    }
    
    // 3. Doublons détectés
    if (!empty($doublons)) {
        echo "<div class='alert alert-danger'>";
        echo "<h4><i class='fas fa-exclamation-circle'></i> Doublons Détectés</h4>";
        echo "<p>Les numéros suivants sont utilisés par plusieurs agents :</p>";
        
        foreach ($doublons as $doublon) {
            echo "<h5>Numéro: <span class='badge bg-danger'>{$doublon['numero_agent']}</span> (utilisé {$doublon['nb']} fois)</h5>";
            
            $stmt = $conn->prepare("
                SELECT 
                    a.id_agent,
                    a.nom,
                    a.prenom,
                    CONCAT(c.nom, ' ', c.prenoms) as nom_chef,
                    a.date_ajout
                FROM agents a
                LEFT JOIN chef_equipe c ON a.id_chef = c.id_chef
                WHERE a.numero_agent = ? AND a.date_suppression IS NULL
                ORDER BY a.date_ajout
            ");
            $stmt->execute([$doublon['numero_agent']]);
            $agents_doublons = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<div class='table-responsive mb-3'>";
            echo "<table class='table table-sm table-bordered'>";
            echo "<thead><tr><th>ID</th><th>Nom</th><th>Prénom</th><th>Chef</th><th>Date Ajout</th></tr></thead>";
            echo "<tbody>";
            
            foreach ($agents_doublons as $agent) {
                echo "<tr>";
                echo "<td>" . $agent['id_agent'] . "</td>";
                echo "<td>" . htmlspecialchars($agent['nom']) . "</td>";
                echo "<td>" . htmlspecialchars($agent['prenom']) . "</td>";
                echo "<td>" . htmlspecialchars($agent['nom_chef'] ?? 'Non assigné') . "</td>";
                echo "<td>" . date('d/m/Y H:i', strtotime($agent['date_ajout'])) . "</td>";
                echo "</tr>";
            }
            echo "</tbody>";
            echo "</table>";
            echo "</div>";
        }
        echo "</div>";
    }
    
    // 4. Analyse par chef d'équipe
    echo "<div class='alert alert-info'>";
    echo "<h4><i class='fas fa-chart-bar'></i> Répartition par Chef d'Équipe</h4>";
    
    $stmt = $conn->prepare("
        SELECT 
            c.id_chef,
            CONCAT(c.nom, ' ', c.prenoms) as nom_chef,
            COUNT(a.id_agent) as nb_agents,
            SUM(CASE WHEN a.numero_agent IS NOT NULL AND a.numero_agent != '' THEN 1 ELSE 0 END) as nb_avec_numero,
            SUM(CASE WHEN a.numero_agent IS NULL OR a.numero_agent = '' THEN 1 ELSE 0 END) as nb_sans_numero
        FROM chef_equipe c
        LEFT JOIN agents a ON c.id_chef = a.id_chef AND a.date_suppression IS NULL
        GROUP BY c.id_chef, c.nom, c.prenoms
        ORDER BY nb_agents DESC
    ");
    $stmt->execute();
    $stats_chefs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='table-responsive'>";
    echo "<table class='table table-striped'>";
    echo "<thead class='table-primary'>";
    echo "<tr>";
    echo "<th>ID Chef</th>";
    echo "<th>Nom Chef</th>";
    echo "<th>Total Agents</th>";
    echo "<th>Avec Numéro</th>";
    echo "<th>Sans Numéro</th>";
    echo "<th>Progression</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    
    foreach ($stats_chefs as $stat) {
        $pourcentage = $stat['nb_agents'] > 0 ? round(($stat['nb_avec_numero'] / $stat['nb_agents']) * 100) : 0;
        $color = $pourcentage == 100 ? 'success' : ($pourcentage >= 50 ? 'warning' : 'danger');
        
        echo "<tr>";
        echo "<td>" . $stat['id_chef'] . "</td>";
        echo "<td>" . htmlspecialchars($stat['nom_chef']) . "</td>";
        echo "<td>" . $stat['nb_agents'] . "</td>";
        echo "<td><span class='badge bg-success'>" . $stat['nb_avec_numero'] . "</span></td>";
        echo "<td><span class='badge bg-danger'>" . $stat['nb_sans_numero'] . "</span></td>";
        echo "<td>";
        echo "<div class='progress' style='height: 20px;'>";
        echo "<div class='progress-bar bg-$color' style='width: {$pourcentage}%'>$pourcentage%</div>";
        echo "</div>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</tbody>";
    echo "</table>";
    echo "</div>";
    echo "</div>";
    
    // 5. Validation du format
    echo "<div class='alert alert-secondary'>";
    echo "<h4><i class='fas fa-check-circle'></i> Validation du Format</h4>";
    
    $stmt = $conn->prepare("
        SELECT numero_agent, COUNT(*) as nb
        FROM agents 
        WHERE date_suppression IS NULL 
        AND numero_agent IS NOT NULL 
        AND numero_agent != ''
        AND numero_agent NOT REGEXP '^AGT-[0-9]{2}[0-9]{3}[0-9]{3}$'
        GROUP BY numero_agent
    ");
    $stmt->execute();
    $formats_invalides = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($formats_invalides)) {
        echo "<p class='text-success'><i class='fas fa-check'></i> Tous les numéros respectent le format AGT-YYXXXYYY</p>";
    } else {
        echo "<p class='text-danger'><i class='fas fa-times'></i> Numéros avec format invalide :</p>";
        echo "<ul>";
        foreach ($formats_invalides as $format) {
            echo "<li><code>" . htmlspecialchars($format['numero_agent']) . "</code> (utilisé {$format['nb']} fois)</li>";
        }
        echo "</ul>";
    }
    echo "</div>";
    
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
echo "<a href='generer_numeros_agents.php' class='btn btn-warning me-2'>";
echo "<i class='fas fa-magic'></i> Générer Numéros";
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

echo "</body>";
echo "</html>";
?>
