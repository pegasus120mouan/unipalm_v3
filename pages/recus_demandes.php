<?php
require_once '../inc/functions/connexion.php';
//session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Titre de la page
$page_title = "Gestion des Reçus de Demande";
include('header_caisse.php');

// Récupération des filtres
$date_debut = isset($_GET['date_debut']) ? $_GET['date_debut'] : '';
$date_fin = isset($_GET['date_fin']) ? $_GET['date_fin'] : '';
$numero_demande = isset($_GET['numero_demande']) ? $_GET['numero_demande'] : '';
$source_paiement = isset($_GET['source_paiement']) ? $_GET['source_paiement'] : '';

// Construction de la requête SQL de base
$sql = "
    SELECT 
        r.*,
        CONCAT(u.nom, ' ', u.prenoms) as caissier_name
    FROM recus_demandes r
    LEFT JOIN utilisateurs u ON r.caissier_id = u.id
    WHERE 1=1
";

$params = array();

// Ajout des conditions de filtrage
if (!empty($date_debut)) {
    $sql .= " AND DATE(r.date_paiement) >= ?";
    $params[] = $date_debut;
}
if (!empty($date_fin)) {
    $sql .= " AND DATE(r.date_paiement) <= ?";
    $params[] = $date_fin;
}
if (!empty($numero_demande)) {
    $sql .= " AND r.numero_demande LIKE ?";
    $params[] = "%$numero_demande%";
}
if (!empty($source_paiement)) {
    $sql .= " AND r.source_paiement = ?";
    $params[] = $source_paiement;
}

$sql .= " ORDER BY r.date_paiement DESC";

// Exécution de la requête
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$recus = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les sources de paiement uniques pour le filtre
$stmt = $conn->query("SELECT DISTINCT source_paiement FROM recus_demandes ORDER BY source_paiement");
$sources_paiement = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Calculer les statistiques
$stats_sql = "
    SELECT 
        COUNT(*) as total_recus,
        SUM(montant) as total_montant,
        COUNT(CASE WHEN DATE(date_paiement) = CURDATE() THEN 1 END) as recus_aujourd_hui,
        COUNT(DISTINCT source_paiement) as sources_uniques
    FROM recus_demandes
";
$stats_stmt = $conn->query($stats_sql);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - UniPalm</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --danger-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --glass-bg: rgba(255, 255, 255, 0.25);
            --glass-border: rgba(255, 255, 255, 0.18);
            --shadow-light: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            --shadow-dark: 0 4px 15px 0 rgba(31, 38, 135, 0.2);
            --text-primary: #2d3748;
            --text-secondary: #718096;
            --border-radius: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            min-height: 100vh;
            color: var(--text-primary);
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translate3d(0, -100%, 0);
            }
            to {
                opacity: 1;
                transform: translate3d(0, 0, 0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translate3d(0, 100%, 0);
            }
            to {
                opacity: 1;
                transform: translate3d(0, 0, 0);
            }
        }

        .header-container {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-light);
            animation: fadeInDown 0.8s ease-out;
        }

        .unipalm-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .unipalm-logo i {
            font-size: 3rem;
            background: var(--success-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-right: 1rem;
            animation: pulse 2s infinite;
        }

        .unipalm-logo h1 {
            font-size: 2.5rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0;
        }

        .page-subtitle {
            text-align: center;
            color: var(--text-secondary);
            font-size: 1.1rem;
            margin-top: 0.5rem;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--shadow-light);
            transition: var(--transition);
            animation: fadeInUp 0.8s ease-out;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-dark);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            display: block;
        }

        .stat-card:nth-child(1) .stat-icon {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-card:nth-child(2) .stat-icon {
            background: var(--success-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-card:nth-child(3) .stat-icon {
            background: var(--warning-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-card:nth-child(4) .stat-icon {
            background: var(--danger-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .filter-container, .table-container {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-light);
            animation: fadeInUp 1s ease-out;
        }

        .filter-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
            display: flex;
            align-items: center;
        }

        .filter-title i {
            margin-right: 0.5rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .form-control {
            border: 2px solid transparent;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: var(--transition);
            background: rgba(255, 255, 255, 0.9);
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            background: white;
        }

        .btn-gradient {
            background: var(--primary-gradient);
            border: none;
            border-radius: 12px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            color: white;
            transition: var(--transition);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .table {
            background: transparent;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .table thead th {
            background: var(--primary-gradient);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1rem;
            border: none;
            font-size: 0.85rem;
        }

        .table tbody tr {
            background: rgba(255, 255, 255, 0.9);
            transition: var(--transition);
        }

        .table tbody tr:hover {
            background: rgba(255, 255, 255, 1);
            transform: scale(1.01);
        }

        .table tbody td {
            padding: 1rem;
            border: none;
            vertical-align: middle;
        }

        .btn-action {
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            font-weight: 500;
            transition: var(--transition);
            margin: 0.2rem;
        }

        .btn-print {
            background: var(--success-gradient);
            border: none;
            color: white;
        }

        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(67, 233, 123, 0.4);
            color: white;
        }

        .btn-delete {
            background: var(--danger-gradient);
            border: none;
            color: white;
        }

        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(250, 112, 154, 0.4);
            color: white;
        }

        .print-button {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--primary-gradient);
            border: none;
            color: white;
            font-size: 1.5rem;
            box-shadow: var(--shadow-dark);
            transition: var(--transition);
            z-index: 1000;
        }

        .print-button:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        @media (max-width: 768px) {
            .unipalm-logo h1 {
                font-size: 2rem;
            }
            
            .unipalm-logo i {
                font-size: 2.5rem;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="container-fluid py-4">
    <!-- Header UniPalm -->
    <div class="header-container">
        <div class="unipalm-logo">
            <i class="fas fa-leaf"></i>
            <h1>UniPalm</h1>
        </div>
        <p class="page-subtitle"><?= $page_title ?></p>
    </div>

    <!-- Statistiques -->
    <div class="stats-container">
        <div class="stat-card">
            <i class="fas fa-receipt stat-icon"></i>
            <div class="stat-number"><?= number_format($stats['total_recus']) ?></div>
            <div class="stat-label">Total Reçus</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-coins stat-icon"></i>
            <div class="stat-number"><?= number_format($stats['total_montant'], 0, ',', ' ') ?></div>
            <div class="stat-label">Total Montant (FCFA)</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-calendar-day stat-icon"></i>
            <div class="stat-number"><?= number_format($stats['recus_aujourd_hui']) ?></div>
            <div class="stat-label">Reçus Aujourd'hui</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-source-branch stat-icon"></i>
            <div class="stat-number"><?= number_format($stats['sources_uniques']) ?></div>
            <div class="stat-label">Sources de Paiement</div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="filter-container">
        <h3 class="filter-title">
            <i class="fas fa-filter"></i>
            Filtres de recherche
        </h3>
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Date début</label>
                <input type="date" name="date_debut" class="form-control" value="<?= htmlspecialchars($date_debut) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Date fin</label>
                <input type="date" name="date_fin" class="form-control" value="<?= htmlspecialchars($date_fin) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">N° Demande</label>
                <input type="text" name="numero_demande" class="form-control" value="<?= htmlspecialchars($numero_demande) ?>" placeholder="Rechercher...">
            </div>
            <div class="col-md-3">
                <label class="form-label">Source de paiement</label>
                <select name="source_paiement" class="form-control">
                    <option value="">Toutes les sources</option>
                    <?php foreach ($sources_paiement as $source): ?>
                        <option value="<?= htmlspecialchars($source) ?>" <?= $source_paiement === $source ? 'selected' : '' ?>>
                            <?= htmlspecialchars($source) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-gradient me-2">
                    <i class="fas fa-search me-1"></i>
                    Filtrer
                </button>
                <a href="recus_demandes.php" class="btn btn-gradient">
                    <i class="fas fa-undo me-1"></i>
                    Réinitialiser
                </a>
            </div>
        </form>
    </div>

    <!-- Tableau des reçus -->
    <div class="table-container">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date Paiement</th>
                        <th>N° Demande</th>
                        <th>Montant</th>
                        <th>Source</th>
                        <th>Caissier</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recus)): ?>
                        <?php foreach ($recus as $recu): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($recu['date_paiement'])) ?></td>
                                <td><strong><?= htmlspecialchars($recu['numero_demande']) ?></strong></td>
                                <td><strong><?= number_format($recu['montant'], 0, ',', ' ') ?> FCFA</strong></td>
                                <td>
                                    <span class="badge bg-primary rounded-pill">
                                        <?= htmlspecialchars($recu['source_paiement']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($recu['caissier_name']) ?></td>
                                <td>
                                    <a href="recu_demande_pdf.php?id=<?= $recu['id'] ?>" 
                                       class="btn btn-print btn-action" 
                                       target="_blank"
                                       title="Imprimer le reçu">
                                        <i class="fas fa-print"></i> Imprimer
                                    </a>
                                    <?php if (!isset($recu['date_validation_boss']) || $recu['date_validation_boss'] === null): ?>
                                        <button type="button" 
                                                class="btn btn-delete btn-action" 
                                                onclick="confirmerSuppression(<?= $recu['id'] ?>, '<?= htmlspecialchars($recu['numero_demande']) ?>')"
                                                title="Supprimer le reçu">
                                            <i class="fas fa-trash"></i> Supprimer
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Aucun reçu de demande trouvé</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bouton d'impression flottant -->
    <button class="print-button" onclick="window.print()" title="Imprimer la page">
        <i class="fas fa-print"></i>
    </button>
</div>

</body>
</html>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function confirmerSuppression(id, numeroDemande) {
    Swal.fire({
        title: 'Confirmer la suppression',
        html: `Êtes-vous sûr de vouloir supprimer le reçu de la demande <strong>${numeroDemande}</strong> ?<br><br><span class="text-danger">Cette action est irréversible !</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: '<i class="fas fa-trash"></i> Oui, supprimer',
        cancelButtonText: '<i class="fas fa-times"></i> Annuler',
        background: 'rgba(255, 255, 255, 0.95)',
        backdrop: 'rgba(0, 0, 0, 0.4)',
        customClass: {
            popup: 'animate__animated animate__fadeInDown'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Afficher un loader
            Swal.fire({
                title: 'Suppression en cours...',
                html: 'Veuillez patienter',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading()
                }
            });
            
            // Créer et soumettre le formulaire
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = 'delete_recu_demande.php';
            
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'id_recu';
            input.value = id;
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
    });
}

// Afficher les messages de succès/erreur avec SweetAlert2
<?php if (isset($_SESSION['success_message'])): ?>
    Swal.fire({
        icon: 'success',
        title: 'Succès !',
        text: "<?= addslashes($_SESSION['success_message']) ?>",
        timer: 3000,
        showConfirmButton: false,
        toast: true,
        position: 'top-end',
        background: 'rgba(255, 255, 255, 0.95)',
        customClass: {
            popup: 'animate__animated animate__slideInRight'
        }
    });
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    Swal.fire({
        icon: 'error',
        title: 'Erreur !',
        text: "<?= addslashes($_SESSION['error_message']) ?>",
        timer: 5000,
        showConfirmButton: false,
        toast: true,
        position: 'top-end',
        background: 'rgba(255, 255, 255, 0.95)',
        customClass: {
            popup: 'animate__animated animate__slideInRight'
        }
    });
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

// Animation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Animer les cartes statistiques
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(50px)';
            card.style.transition = 'all 0.6s ease';
            
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100);
        }, index * 200);
    });
    
    // Effet de compteur pour les statistiques
    const numbers = document.querySelectorAll('.stat-number');
    numbers.forEach(number => {
        const target = parseInt(number.textContent.replace(/\s/g, ''));
        if (!isNaN(target)) {
            animateCounter(number, target);
        }
    });
});

function animateCounter(element, target) {
    let current = 0;
    const increment = target / 50;
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            current = target;
            clearInterval(timer);
        }
        element.textContent = Math.floor(current).toLocaleString('fr-FR');
    }, 30);
}
</script>
