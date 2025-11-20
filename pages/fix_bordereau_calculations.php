<?php
require_once '../inc/functions/connexion.php';
session_start();

// Vérifier les permissions (admin seulement)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$conn = getConnexion();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();
        
        if (isset($_POST['fix_structure'])) {
            // Corriger la structure de la table
            $sql_structure = [
                "ALTER TABLE `bordereau` MODIFY COLUMN `montant_total` DECIMAL(15,2) NOT NULL DEFAULT '0.00'",
                "ALTER TABLE `bordereau` MODIFY COLUMN `montant_payer` DECIMAL(15,2) DEFAULT NULL",
                "ALTER TABLE `bordereau` MODIFY COLUMN `montant_reste` DECIMAL(15,2) DEFAULT NULL",
                "ALTER TABLE `bordereau` MODIFY COLUMN `poids_total` DECIMAL(15,2) NOT NULL DEFAULT '0.00'"
            ];
            
            foreach ($sql_structure as $sql) {
                $conn->exec($sql);
            }
            
            $message = "Structure de la table bordereau corrigée avec succès !";
        }
        
        if (isset($_POST['recalculate_amounts'])) {
            // Recalculer les montants
            $sql_recalc = "
                UPDATE bordereau b 
                SET b.montant_total = (
                    SELECT COALESCE(SUM(t.prix_unitaire * t.poids), 0)
                    FROM tickets t 
                    WHERE t.numero_bordereau = b.numero_bordereau
                      AND t.prix_unitaire IS NOT NULL 
                      AND t.prix_unitaire > 0
                ),
                b.poids_total = (
                    SELECT COALESCE(SUM(t.poids), 0)
                    FROM tickets t 
                    WHERE t.numero_bordereau = b.numero_bordereau
                )";
            
            $stmt = $conn->prepare($sql_recalc);
            $stmt->execute();
            $affected_rows = $stmt->rowCount();
            
            // Recalculer les montants restants
            $sql_reste = "UPDATE bordereau SET montant_reste = montant_total - COALESCE(montant_payer, 0)";
            $conn->exec($sql_reste);
            
            // Mettre à jour les statuts
            $sql_status = "UPDATE bordereau SET statut_bordereau = CASE 
                WHEN COALESCE(montant_reste, montant_total) <= 0 THEN 'soldé'
                ELSE 'non soldé'
            END";
            $conn->exec($sql_status);
            
            $message = "Montants recalculés pour $affected_rows bordereau(x) !";
        }
        
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "Erreur : " . $e->getMessage();
    }
}

// Récupérer les bordereaux avec de gros montants pour vérification
$sql_check = "SELECT numero_bordereau, montant_total, poids_total, montant_payer, montant_reste, statut_bordereau
              FROM bordereau 
              WHERE montant_total > 100000
              ORDER BY montant_total DESC
              LIMIT 10";
$stmt = $conn->prepare($sql_check);
$stmt->execute();
$bordereaux_check = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Vérifier la structure actuelle
$sql_desc = "DESCRIBE bordereau";
$stmt = $conn->prepare($sql_desc);
$stmt->execute();
$table_structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Correction des Calculs de Bordereaux - UniPalm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-danger text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-tools me-2"></i>
                            Correction des Calculs de Bordereaux
                        </h4>
                        <small>Outil d'administration pour corriger les problèmes de précision DECIMAL</small>
                    </div>
                    <div class="card-body">
                        
                        <?php if ($message): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <i class="fas fa-check-circle me-2"></i>
                                <?= htmlspecialchars($message) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?= htmlspecialchars($error) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-warning">
                                        <h5 class="mb-0">1. Corriger la Structure</h5>
                                    </div>
                                    <div class="card-body">
                                        <p>Modifie les colonnes DECIMAL(10,2) vers DECIMAL(15,2) pour permettre de plus gros montants.</p>
                                        <form method="post">
                                            <button type="submit" name="fix_structure" class="btn btn-warning" 
                                                    onclick="return confirm('Êtes-vous sûr de vouloir modifier la structure de la table ?')">
                                                <i class="fas fa-database me-2"></i>
                                                Corriger la Structure
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-info text-white">
                                        <h5 class="mb-0">2. Recalculer les Montants</h5>
                                    </div>
                                    <div class="card-body">
                                        <p>Recalcule tous les montants des bordereaux à partir des tickets associés.</p>
                                        <form method="post">
                                            <button type="submit" name="recalculate_amounts" class="btn btn-info"
                                                    onclick="return confirm('Êtes-vous sûr de vouloir recalculer tous les montants ?')">
                                                <i class="fas fa-calculator me-2"></i>
                                                Recalculer les Montants
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Structure actuelle -->
                        <div class="card mb-3">
                            <div class="card-header bg-secondary text-white">
                                <h5 class="mb-0">Structure Actuelle de la Table</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Colonne</th>
                                                <th>Type</th>
                                                <th>Null</th>
                                                <th>Défaut</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($table_structure as $col): ?>
                                                <tr class="<?= strpos($col['Type'], 'decimal(10,2)') !== false ? 'table-danger' : '' ?>">
                                                    <td><?= htmlspecialchars($col['Field']) ?></td>
                                                    <td>
                                                        <?= htmlspecialchars($col['Type']) ?>
                                                        <?php if (strpos($col['Type'], 'decimal(10,2)') !== false): ?>
                                                            <span class="badge bg-danger ms-2">PROBLÈME</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($col['Null']) ?></td>
                                                    <td><?= htmlspecialchars($col['Default']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Bordereaux avec gros montants -->
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Bordereaux avec Gros Montants (Vérification)</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>N° Bordereau</th>
                                                <th>Montant Total</th>
                                                <th>Poids Total</th>
                                                <th>Montant Payé</th>
                                                <th>Reste</th>
                                                <th>Statut</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($bordereaux_check)): ?>
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted">
                                                        Aucun bordereau avec montant > 100 000 FCFA
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($bordereaux_check as $b): ?>
                                                    <tr class="<?= $b['montant_total'] >= 99999999 ? 'table-warning' : '' ?>">
                                                        <td><?= htmlspecialchars($b['numero_bordereau']) ?></td>
                                                        <td>
                                                            <?= number_format($b['montant_total'], 0, ',', ' ') ?> FCFA
                                                            <?php if ($b['montant_total'] >= 99999999): ?>
                                                                <span class="badge bg-warning ms-2">TRONQUÉ?</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?= number_format($b['poids_total'], 2, ',', ' ') ?> kg</td>
                                                        <td><?= number_format($b['montant_payer'] ?? 0, 0, ',', ' ') ?> FCFA</td>
                                                        <td><?= number_format($b['montant_reste'] ?? 0, 0, ',', ' ') ?> FCFA</td>
                                                        <td>
                                                            <span class="badge bg-<?= $b['statut_bordereau'] === 'soldé' ? 'success' : 'warning' ?>">
                                                                <?= htmlspecialchars($b['statut_bordereau']) ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <a href="bordereaux.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>
                                Retour aux Bordereaux
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
