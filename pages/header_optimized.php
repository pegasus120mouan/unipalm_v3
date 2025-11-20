<?php
header('Content-Type: text/html; charset=UTF-8');
setlocale(LC_TIME, 'fr_FR.utf8', 'fra');

require_once '../inc/functions/connexion.php';
require_once '../inc/functions/get_solde.php';

// Nombre de ticket Total
$sql_ticket_total = "SELECT COUNT(id_ticket) AS nb_ticket_tt FROM tickets";
$requete_tt = $conn->prepare($sql_ticket_total);
$requete_tt->execute();
$ticket_total = $requete_tt->fetch(PDO::FETCH_ASSOC);

// Nombre de ticket en attente
$sql_ticket_nv = "SELECT COUNT(id_ticket) AS nb_ticket_nv FROM tickets WHERE date_validation_boss IS NULL";
$requete_tnv = $conn->prepare($sql_ticket_nv);
$requete_tnv->execute();
$ticket_non_valide = $requete_tnv->fetch(PDO::FETCH_ASSOC);

// Nombre de tickets validés
$sql_ticket_v = "SELECT COUNT(id_ticket) AS nb_ticket_nv FROM tickets WHERE date_validation_boss IS NOT NULL";
$requete_tv = $conn->prepare($sql_ticket_v);
$requete_tv->execute();
$ticket_valide = $requete_tv->fetch(PDO::FETCH_ASSOC);

// Nombre de colis tickés payes
$sql_ticket_paye = "SELECT COUNT(id_ticket) AS nb_ticket_paye FROM tickets WHERE date_paie IS NULL AND date_validation_boss IS NOT NULL";
$requete_tpaye = $conn->prepare($sql_ticket_paye);
$requete_tpaye->execute();
$ticket_paye = $requete_tpaye->fetch(PDO::FETCH_ASSOC);

$solde_caisse = getSoldeCaisse();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>UniPalm - Tableau de bord</title>

  <!-- Favicon optimisé -->
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏢</text></svg>" type="image/svg+xml">
  
  <!-- Preconnect pour les fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://cdnjs.cloudflare.com">
  
  <!-- CSS essentiels avec intégrité -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous">
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="../plugins/select2/css/select2.min.css">
  <link rel="stylesheet" href="../dist/css/unipalm-optimized.css">
  
  <!-- Scripts essentiels avec defer -->
  <script src="../plugins/jquery/jquery.min.js"></script>
  <script src="../plugins/bootstrap/js/bootstrap.bundle.min.js" defer></script>
  <script src="../plugins/select2/js/select2.full.min.js" defer></script>
  <script src="../dist/js/adminlte.min.js" defer></script>
  <script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js" defer></script>

</head>

<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="dashboard.php" class="nav-link">Accueil</a>
      </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
      <!-- Navbar Search -->
      <li class="nav-item">
        <a class="nav-link" data-widget="navbar-search" href="#" role="button">
          <i class="fas fa-search"></i>
        </a>
        <div class="navbar-search-block">
          <form class="form-inline">
            <div class="input-group input-group-sm">
              <input class="form-control form-control-navbar" type="search" placeholder="Rechercher" aria-label="Search">
              <div class="input-group-append">
                <button class="btn btn-navbar" type="submit">
                  <i class="fas fa-search"></i>
                </button>
              </div>
            </div>
          </form>
        </div>
      </li>

      <!-- Notifications Dropdown Menu -->
      <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#">
          <i class="far fa-bell"></i>
          <span class="badge badge-warning navbar-badge"><?= $ticket_non_valide['nb_ticket_nv'] ?></span>
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
          <span class="dropdown-item dropdown-header"><?= $ticket_non_valide['nb_ticket_nv'] ?> Notifications</span>
          <div class="dropdown-divider"></div>
          <a href="tickets_attente.php" class="dropdown-item">
            <i class="fas fa-envelope mr-2"></i> <?= $ticket_non_valide['nb_ticket_nv'] ?> tickets en attente
          </a>
          <div class="dropdown-divider"></div>
          <a href="tickets_attente.php" class="dropdown-item dropdown-footer">Voir toutes les notifications</a>
        </div>
      </li>

      <!-- User Dropdown Menu -->
      <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#">
          <img src="../dist/img/user2-160x160.jpg" alt="User Avatar" class="img-size-50 mr-3 img-circle">
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
          <div class="dropdown-divider"></div>
          <a href="../logout.php" class="dropdown-item text-danger">
            <i class="fas fa-sign-out-alt mr-2"></i>
            Déconnexion
          </a>
        </div>
      </li>

      <!-- Fullscreen -->
      <li class="nav-item">
        <a class="nav-link" data-widget="fullscreen" href="#" role="button">
          <i class="fas fa-expand-arrows-alt"></i>
        </a>
      </li>
    </ul>
  </nav>
  <!-- /.navbar -->

  <!-- Main Sidebar Container -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="dashboard.php" class="brand-link">
      <img src="../dist/img/AdminLTELogo.png" alt="UniPalm Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
      <span class="brand-text font-weight-light">UniPalm</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar user panel (optional) -->
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <img src="../dist/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image">
        </div>
        <div class="info">
          <a href="#" class="d-block"><?= $_SESSION['nom'] ?? 'Utilisateur' ?></a>
        </div>
      </div>

      <!-- SidebarSearch Form -->
      <div class="form-inline">
        <div class="input-group" data-widget="sidebar-search">
          <input class="form-control form-control-sidebar" type="search" placeholder="Rechercher" aria-label="Search">
          <div class="input-group-append">
            <button class="btn btn-sidebar">
              <i class="fas fa-search fa-fw"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Sidebar Menu -->
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          <!-- Dashboard -->
          <li class="nav-item">
            <a href="dashboard.php" class="nav-link">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Tableau de bord</p>
            </a>
          </li>

          <!-- Tickets -->
          <li class="nav-item">
            <a href="javascript:void(0)" class="nav-link">
              <i class="nav-icon fas fa-ticket-alt"></i>
              <p>
                Tickets
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="tickets_jour.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Tickets du jour</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="tickets_attente.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>En attente</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="tickets_valides.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Validés</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="tickets_payes.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Payés</p>
                </a>
              </li>
            </ul>
          </li>

          <!-- Financements -->
          <li class="nav-item">
            <a href="financements.php" class="nav-link">
              <i class="nav-icon fas fa-money-bill-wave"></i>
              <p>Financements</p>
            </a>
          </li>

          <!-- Prêts -->
          <li class="nav-item">
            <a href="prets.php" class="nav-link">
              <i class="nav-icon fas fa-hand-holding-usd"></i>
              <p>Prêts</p>
            </a>
          </li>

          <!-- Agents -->
          <li class="nav-item">
            <a href="agents.php" class="nav-link">
              <i class="nav-icon fas fa-users"></i>
              <p>Agents</p>
            </a>
          </li>

          <!-- Usines -->
          <li class="nav-item">
            <a href="usines.php" class="nav-link">
              <i class="nav-icon fas fa-industry"></i>
              <p>Usines</p>
            </a>
          </li>

          <!-- Véhicules -->
          <li class="nav-item">
            <a href="vehicules.php" class="nav-link">
              <i class="nav-icon fas fa-truck"></i>
              <p>Véhicules</p>
            </a>
          </li>

          <!-- Rapports -->
          <li class="nav-item">
            <a href="javascript:void(0)" class="nav-link">
              <i class="nav-icon fas fa-chart-bar"></i>
              <p>
                Rapports
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="rapports_tickets.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Rapports tickets</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="rapports_financiers.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Rapports financiers</p>
                </a>
              </li>
            </ul>
          </li>
        </ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <!-- Le contenu de la page sera inséré ici -->
      </div>
    </div>

<script>
$(document).ready(function() {
    // Gestion des menus déroulants de la sidebar
    $('.nav-sidebar .nav-item > a').on('click', function(e) {
        var $this = $(this);
        var $parent = $this.parent('.nav-item');
        var $submenu = $this.next('.nav-treeview');
        
        if ($submenu.length > 0 && $this.attr('href') === 'javascript:void(0)') {
            e.preventDefault();
            
            // Fermer les autres menus ouverts
            $('.nav-sidebar .nav-item.menu-open').not($parent).each(function() {
                $(this).removeClass('menu-open');
                $(this).find('.nav-treeview').slideUp(300);
            });
            
            // Toggle du menu actuel
            if ($parent.hasClass('menu-open')) {
                $parent.removeClass('menu-open');
                $submenu.slideUp(300);
            } else {
                $parent.addClass('menu-open');
                $submenu.slideDown(300);
            }
        }
    });
    
    // Gestion des dropdowns de la navbar
    $('.navbar-nav .nav-link[data-toggle="dropdown"]').on('click', function(e) {
        e.preventDefault();
        var $dropdown = $(this).parent('.dropdown');
        var $menu = $dropdown.find('.dropdown-menu');
        
        // Fermer les autres dropdowns
        $('.navbar-nav .dropdown').not($dropdown).removeClass('show');
        $('.navbar-nav .dropdown-menu').not($menu).removeClass('show');
        
        // Toggle du dropdown actuel
        $dropdown.toggleClass('show');
        $menu.toggleClass('show');
    });
    
    // Fermer les dropdowns en cliquant ailleurs
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.navbar-nav .dropdown').length) {
            $('.navbar-nav .dropdown').removeClass('show');
            $('.navbar-nav .dropdown-menu').removeClass('show');
        }
    });
});
</script>
