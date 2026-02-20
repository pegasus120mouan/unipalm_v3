<?php
header('Content-Type: text/html; charset=UTF-8');

setlocale(LC_TIME, 'fr_FR.utf8', 'fra');  // Force la configuration en français

require_once '../inc/functions/connexion.php';
require_once '../inc/functions/get_solde.php';

// Nombre de ticket Total
$sql_ticket_total = "SELECT COUNT(id_ticket) AS nb_ticket_tt FROM tickets";
$requete_tt = $conn->prepare($sql_ticket_total);
$requete_tt->execute();
$ticket_total = $requete_tt->fetch(PDO::FETCH_ASSOC);

// Nombre de ticket en attente
$sql_ticket_nv = "SELECT COUNT(id_ticket) AS nb_ticket_nv FROM tickets WHERE  date_validation_boss IS NULL";
$requete_tnv = $conn->prepare($sql_ticket_nv);
$requete_tnv->execute();
$ticket_non_valide = $requete_tnv->fetch(PDO::FETCH_ASSOC);

// Nombre de tickets validés
$sql_ticket_v = "SELECT COUNT(id_ticket) AS nb_ticket_nv FROM tickets
WHERE date_validation_boss IS NOT NULL";
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
    // Redirigez vers la page de connexion si l'utilisateur n'est pas connecté
    header("Location: ../index.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tableau de bord</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="icon" href="../dist/img/logo.png" type="image/x-icon">
  <link rel="shortcut icon" href="../dist/img/logo.png" type="image/x-icon">

  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
  <!-- Ionicons -->
  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">

  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
  <!-- Tempusdominus Bootstrap 4 -->
  <link rel="stylesheet" href="../plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
  <!-- iCheck -->
  <link rel="stylesheet" href="../plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <!-- JQVMap -->
  <link rel="stylesheet" href="../plugins/jqvmap/jqvmap.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
  <!-- overlayScrollbars -->
  <link rel="stylesheet" href="../plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <!-- Daterange picker -->
  <link rel="stylesheet" href="../plugins/daterangepicker/daterangepicker.css">
  <!-- summernote -->
  <link rel="stylesheet" href="../plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">
  <link rel="stylesheet" href="../plugins/summernote/summernote-bs4.min.css">
  <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
  <!-- DataTables -->
  <link rel="stylesheet" href="../plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="../plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="../plugins/datatables-buttons/css/buttons.bootstrap4.min.css"> 
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"> 
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
  <!-- DataTables -->
  <link rel="stylesheet" href="../plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="../plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="../plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
  <link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
  <!-- Select2 -->
  <link href="../plugins/select2/css/select2.min.css" rel="stylesheet" />
  <link href="../plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css" rel="stylesheet" />
  
  <!-- Loader moderne -->
  <link rel="stylesheet" href="../dist/css/loading-spinner.css">
  
  <!-- Scripts nécessaires -->
  <script src="../plugins/jquery/jquery.min.js"></script>
  <script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../plugins/select2/js/select2.full.min.js"></script>
  <script src="../dist/js/adminlte.min.js"></script>

  <style>
    /* ===== STYLES ULTRA-PROFESSIONNELS POUR LE HEADER ===== */
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --success-gradient: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
        --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        --glass-bg: rgba(255, 255, 255, 0.95);
        --glass-border: rgba(255, 255, 255, 0.3);
        --shadow-light: 0 8px 32px rgba(31, 38, 135, 0.15);
        --shadow-heavy: 0 15px 35px rgba(31, 38, 135, 0.25);
        --border-radius: 20px;
        --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Navbar professionnel */
    .main-header.navbar {
        background: var(--glass-bg) !important;
        backdrop-filter: blur(15px) !important;
        border-bottom: 1px solid var(--glass-border) !important;
        box-shadow: var(--shadow-light) !important;
        padding: 0.75rem 1.5rem !important;
        min-height: 70px !important;
    }

    .navbar-nav .nav-link {
        color: #2c3e50 !important;
        font-weight: 600 !important;
        font-size: 0.95rem !important;
        padding: 0.75rem 1.25rem !important;
        border-radius: 12px !important;
        transition: var(--transition) !important;
        position: relative !important;
        margin: 0 0.25rem !important;
    }

    .navbar-nav .nav-link:hover {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)) !important;
        color: #667eea !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2) !important;
    }

    .navbar-nav .nav-link.active {
        background: var(--primary-gradient) !important;
        color: white !important;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3) !important;
    }

    /* Bouton menu hamburger */
    .navbar-nav .nav-link[data-widget="pushmenu"] {
        background: var(--primary-gradient) !important;
        color: white !important;
        border-radius: 12px !important;
        width: 45px !important;
        height: 45px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        margin-right: 1rem !important;
    }

    .navbar-nav .nav-link[data-widget="pushmenu"]:hover {
        transform: scale(1.05) !important;
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4) !important;
    }

    /* Recherche moderne */
    .navbar-search-block {
        background: white !important;
        border-radius: 25px !important;
        box-shadow: var(--shadow-light) !important;
        border: 2px solid var(--glass-border) !important;
        overflow: hidden !important;
    }

    .form-control-navbar {
        border: none !important;
        padding: 12px 20px !important;
        font-size: 0.95rem !important;
        background: transparent !important;
    }

    .form-control-navbar:focus {
        box-shadow: none !important;
        border-color: transparent !important;
    }

    .btn-navbar {
        border: none !important;
        background: var(--primary-gradient) !important;
        color: white !important;
        padding: 12px 15px !important;
        transition: var(--transition) !important;
    }

    .btn-navbar:hover {
        background: linear-gradient(135deg, #5a6fd8 0%, #6b4190 100%) !important;
        color: white !important;
    }

    /* Notifications modernes */
    .navbar-badge {
        background: var(--warning-gradient) !important;
        border: 2px solid white !important;
        font-size: 0.7rem !important;
        font-weight: 700 !important;
        animation: pulse 2s infinite !important;
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }

    .dropdown-menu {
        border: none !important;
        border-radius: 15px !important;
        box-shadow: var(--shadow-heavy) !important;
        background: var(--glass-bg) !important;
        backdrop-filter: blur(15px) !important;
        margin-top: 10px !important;
    }

    .dropdown-item {
        padding: 12px 20px !important;
        border-radius: 10px !important;
        margin: 5px 10px !important;
        transition: var(--transition) !important;
    }

    .dropdown-item:hover {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)) !important;
        color: #667eea !important;
        transform: translateX(5px) !important;
    }

    /* Bouton plein écran */
    .nav-link[data-widget="fullscreen"] {
        background: var(--info-gradient) !important;
        color: white !important;
        border-radius: 12px !important;
        width: 45px !important;
        height: 45px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    /* Bouton déconnexion */
    .nav-link.text-danger {
        background: var(--warning-gradient) !important;
        color: white !important;
        border-radius: 12px !important;
        width: 45px !important;
        height: 45px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        margin-left: 0.5rem !important;
    }

    .nav-link.text-danger:hover {
        background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%) !important;
        transform: scale(1.05) !important;
        box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4) !important;
    }

    /* Sidebar professionnel */
    .main-sidebar {
        background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%) !important;
        box-shadow: var(--shadow-heavy) !important;
    }

    .brand-link {
        background: rgba(255, 255, 255, 0.1) !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
        padding: 1.5rem !important;
        transition: var(--transition) !important;
    }

    .brand-link:hover {
        background: rgba(255, 255, 255, 0.15) !important;
        transform: translateY(-1px) !important;
    }

    .brand-text {
        font-weight: 700 !important;
        font-size: 1.3rem !important;
        color: white !important;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3) !important;
    }

    .brand-image {
        border: 3px solid white !important;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2) !important;
    }

    /* Panel utilisateur */
    .user-panel {
        background: rgba(255, 255, 255, 0.1) !important;
        border-radius: 15px !important;
        margin: 1rem !important;
        padding: 1rem !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
    }

    .user-panel .image img {
        border: 3px solid white !important;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2) !important;
    }

    .user-panel .info a {
        color: white !important;
        font-weight: 600 !important;
        font-size: 1rem !important;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3) !important;
    }

    /* Menu sidebar */
    .nav-sidebar .nav-link {
        color: rgba(255, 255, 255, 0.9) !important;
        border-radius: 10px !important;
        margin: 2px 8px !important;
        padding: 12px 15px !important;
        transition: var(--transition) !important;
    }

    .nav-sidebar .nav-link:hover {
        background: rgba(255, 255, 255, 0.1) !important;
        color: white !important;
        transform: translateX(5px) !important;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1) !important;
    }

    .nav-sidebar .nav-link.active {
        background: var(--primary-gradient) !important;
        color: white !important;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3) !important;
    }

    .nav-treeview .nav-link {
        padding-left: 2.5rem !important;
        font-size: 0.9rem !important;
    }

    .nav-header {
        color: rgba(255, 255, 255, 0.7) !important;
        font-weight: 700 !important;
        font-size: 0.8rem !important;
        letter-spacing: 1px !important;
        margin-top: 1.5rem !important;
        padding: 0.5rem 1rem !important;
    }

    /* Recherche sidebar */
    .form-control-sidebar {
        background: rgba(255, 255, 255, 0.1) !important;
        border: 1px solid rgba(255, 255, 255, 0.2) !important;
        color: white !important;
        border-radius: 10px !important;
    }

    .form-control-sidebar::placeholder {
        color: rgba(255, 255, 255, 0.6) !important;
    }

    .btn-sidebar {
        background: var(--primary-gradient) !important;
        border: none !important;
        color: white !important;
        border-radius: 0 10px 10px 0 !important;
    }

    /* Select2 amélioré */
    .select2-container .select2-selection--single {
        height: 45px !important;
        background: white !important;
        border: 2px solid #e9ecef !important;
        border-radius: 12px !important;
        box-shadow: var(--shadow-light) !important;
        transition: var(--transition) !important;
    }

    .select2-container .select2-selection--single:hover {
        border-color: #667eea !important;
    }

    .select2-container .select2-selection--single .select2-selection__rendered {
        line-height: 41px !important;
        padding-left: 15px !important;
        color: #2c3e50 !important;
        font-weight: 500 !important;
    }

    .select2-container .select2-selection--single .select2-selection__arrow {
        height: 41px !important;
        width: 35px !important;
        right: 5px !important;
    }

    .select2-container .select2-dropdown {
        border: 2px solid #667eea !important;
        border-radius: 12px !important;
        box-shadow: var(--shadow-heavy) !important;
        background: var(--glass-bg) !important;
        backdrop-filter: blur(15px) !important;
    }

    .select2-container .select2-results__option {
        padding: 12px 15px !important;
        font-size: 0.95rem !important;
        transition: var(--transition) !important;
    }

    .select2-container .select2-results__option--highlighted[aria-selected] {
        background: var(--primary-gradient) !important;
        color: white !important;
    }

    /* Responsive design */
    @media (max-width: 768px) {
        .main-header.navbar {
            padding: 0.5rem 1rem !important;
            min-height: 60px !important;
        }
        
        .navbar-nav .nav-link {
            padding: 0.5rem 0.75rem !important;
            font-size: 0.9rem !important;
        }
        
        .d-none.d-sm-inline-block {
            display: none !important;
        }
    }
    /* Styles pour les menus déroulants de la sidebar */
    .nav-sidebar .nav-item .nav-treeview {
      display: none;
      padding-left: 1rem;
    }
    
    .nav-sidebar .nav-item.menu-open .nav-treeview {
      display: block;
    }
    
    .nav-sidebar .nav-item > a {
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .nav-sidebar .nav-item > a:hover {
      background-color: rgba(255, 255, 255, 0.1);
    }
    
    .nav-sidebar .nav-item.menu-open > a {
      background-color: rgba(255, 255, 255, 0.1);
    }
    
    /* Animation pour les icônes de flèche */
    .nav-sidebar .nav-item > a .right {
      transition: transform 0.3s ease;
    }
    
    .nav-sidebar .nav-item.menu-open > a .right {
      transform: rotate(-90deg);
    }
    
    /* Styles pour les dropdowns de la navbar */
    .navbar-nav .dropdown-menu {
      display: none;
      position: absolute;
      top: 100%;
      right: 0;
      z-index: 1000;
      min-width: 250px;
      padding: 0.5rem 0;
      margin: 0.125rem 0 0;
      background-color: #fff;
      border: 1px solid rgba(0, 0, 0, 0.15);
      border-radius: 0.375rem;
      box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.175);
    }
    
    .navbar-nav .dropdown.show .dropdown-menu {
      display: block;
    }
    
    .navbar-nav .dropdown-menu .dropdown-item {
      display: flex;
      align-items: center;
      padding: 0.5rem 1rem;
      color: #212529;
      text-decoration: none;
      transition: all 0.3s ease;
    }
    
    .navbar-nav .dropdown-menu .dropdown-item:hover {
      background-color: #f8f9fa;
      color: #16181b;
    }
    
    .navbar-nav .dropdown-menu .dropdown-item.text-danger:hover {
      background-color: #f8d7da;
      color: #721c24;
    }
    
    .navbar-nav .dropdown-menu .dropdown-header {
      padding: 0.5rem 1rem;
      margin-bottom: 0;
      font-size: 0.875rem;
      color: #6c757d;
      white-space: nowrap;
    }
    
    .navbar-nav .dropdown-menu .dropdown-divider {
      height: 0;
      margin: 0.5rem 0;
      overflow: hidden;
      border-top: 1px solid #dee2e6;
    }
    
    /* Animation pour l'avatar utilisateur */
    .navbar-nav .nav-link img {
      transition: all 0.3s ease;
    }
    
    .navbar-nav .dropdown.show .nav-link img {
      transform: scale(1.1);
      box-shadow: 0 4px 12px rgba(0,0,0,0.3) !important;
    }
  </style>

  <script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>
  
  <!-- Scripts pour les fonctionnalités des menus -->
  <script>
  $(document).ready(function() {
    // Initialisation des dropdowns Bootstrap
    $('.dropdown-toggle').dropdown();
    
    // Gestion des menus déroulants de la sidebar avec AdminLTE
    $('.nav-sidebar .nav-item > a').on('click', function(e) {
      var $this = $(this);
      var $parent = $this.parent('.nav-item');
      var $submenu = $this.next('.nav-treeview');
      
      // Si c'est un lien avec sous-menu
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
    $('.navbar-nav .nav-link[data-toggle="dropdown"]').off('click.dropdown').on('click.dropdown', function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      var $this = $(this);
      var $dropdown = $this.parent('.nav-item.dropdown');
      var $menu = $this.next('.dropdown-menu');
      
      console.log('🖱️ Clic sur dropdown navbar:', $this.attr('title') || 'Dropdown');
      console.log('📋 Dropdown parent trouvé:', $dropdown.length);
      console.log('📋 Menu trouvé:', $menu.length);
      
      // Fermer tous les autres dropdowns
      $('.navbar-nav .dropdown').not($dropdown).removeClass('show');
      $('.navbar-nav .dropdown-menu').not($menu).removeClass('show');
      
      // Toggle du dropdown actuel
      $dropdown.toggleClass('show');
      $menu.toggleClass('show');
      
      console.log('📋 Dropdown état:', $dropdown.hasClass('show') ? 'Ouvert' : 'Fermé');
    });
    
    // Fermer les dropdowns en cliquant ailleurs
    $(document).on('click', function(e) {
      if (!$(e.target).closest('.navbar-nav .dropdown').length) {
        $('.navbar-nav .dropdown').removeClass('show');
        $('.navbar-nav .dropdown-menu').removeClass('show');
      }
    });
    
    // Gestion du bouton menu hamburger
    $('[data-widget="pushmenu"]').on('click', function(e) {
      e.preventDefault();
      $('body').toggleClass('sidebar-collapse');
    });
    
    // Gestion du plein écran
    $('[data-widget="fullscreen"]').on('click', function(e) {
      e.preventDefault();
      if (document.fullscreenElement) {
        document.exitFullscreen();
      } else {
        document.documentElement.requestFullscreen();
      }
    });
    
    // Gestion de la recherche navbar
    $('[data-widget="navbar-search"]').on('click', function(e) {
      e.preventDefault();
      $('.navbar-search-block').toggle();
      console.log('🔍 Toggle recherche');
    });
  });
  </script>

    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link href="https://api.mapbox.com/mapbox-gl-js/v3.8.0/mapbox-gl.css" rel="stylesheet">

</head>

<body class="hold-transition sidebar-mini layout-fixed">
  <div class="wrapper">

    <!-- Preloader 
  <div class="preloader flex-column justify-content-center align-items-center">
    <img class="animation__shake" src="dist/img/AdminLTELogo.png" alt="AdminLTELogo" height="60" width="60">
  </div>-->

    <!-- Navbar Ultra-Professionnel -->
    <nav class="main-header navbar navbar-expand navbar-pro">
      <!-- Left navbar links -->
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" data-widget="pushmenu" href="javascript:void(0)" role="button" title="Menu">
            <i class="fas fa-bars"></i>
          </a>
        </li>
        <li class="nav-item d-none d-lg-inline-block">
          <a href="tickets.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'tickets.php' ? 'active' : '' ?>">
            <i class="fas fa-home mr-2"></i>
            Accueil
          </a>
        </li>
        <li class="nav-item d-none d-lg-inline-block">
          <a href="tickets.php" class="nav-link <?= in_array(basename($_SERVER['PHP_SELF']), ['tickets.php', 'tickets_jour.php', 'tickets_attente.php']) ? 'active' : '' ?>">
            <i class="fas fa-ticket-alt mr-2"></i>
            Tickets
          </a>
        </li>
        <li class="nav-item d-none d-lg-inline-block">
          <a href="paiements.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'paiements.php' ? 'active' : '' ?>">
            <i class="fas fa-credit-card mr-2"></i>
            Paiements
          </a>
        </li>
        <li class="nav-item d-none d-xl-inline-block">
          <a href="tickets_payes.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'tickets_payes.php' ? 'active' : '' ?>">
            <i class="fas fa-money-check-alt mr-2"></i>
            Tickets Payés
          </a>
        </li>
      </ul>

      <!-- Right navbar links -->
      <ul class="navbar-nav ml-auto">
        <!-- Recherche intelligente -->
        <li class="nav-item dropdown d-none d-md-block">
          <a class="nav-link" data-widget="navbar-search" href="javascript:void(0)" role="button" title="Recherche avancée">
            <i class="fas fa-search"></i>
          </a>
          <div class="navbar-search-block">
            <form class="form-inline" action="recherche_colis.php" method="GET">
              <div class="input-group">
                <input class="form-control form-control-navbar" 
                       type="search" 
                       name="search" 
                       placeholder="Rechercher un ticket, agent, usine..." 
                       aria-label="Recherche">
                <div class="input-group-append">
                  <button class="btn btn-navbar" type="submit" title="Rechercher">
                    <i class="fas fa-search"></i>
                  </button>
                  <button class="btn btn-navbar" type="button" data-widget="navbar-search" title="Fermer">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
              </div>
            </form>
          </div>
        </li>

        <!-- Notifications intelligentes -->
        <li class="nav-item dropdown">
          <a class="nav-link" data-toggle="dropdown" href="javascript:void(0)" title="Notifications">
            <i class="fas fa-bell"></i>
            <span class="badge navbar-badge"><?= $ticket_non_valide['nb_ticket_nv'] ?></span>
          </a>
          <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
            <div class="dropdown-header">
              <i class="fas fa-bell mr-2"></i>
              <strong>Notifications Système</strong>
            </div>
            <div class="dropdown-divider"></div>
            <a href="tickets_attente.php" class="dropdown-item">
              <i class="fas fa-clock text-warning mr-3"></i>
              <div>
                <strong><?= $ticket_non_valide['nb_ticket_nv'] ?> tickets en attente</strong>
                <p class="text-muted text-sm mb-0">Nécessitent une validation</p>
              </div>
            </a>
            <div class="dropdown-divider"></div>
            <a href="tickets_payes.php" class="dropdown-item">
              <i class="fas fa-money-bill text-success mr-3"></i>
              <div>
                <strong><?= $ticket_paye['nb_ticket_paye'] ?> tickets validés</strong>
                <p class="text-muted text-sm mb-0">En attente de paiement</p>
              </div>
            </a>
            <div class="dropdown-divider"></div>
            <a href="javascript:void(0)" class="dropdown-item">
              <i class="fas fa-chart-line text-info mr-3"></i>
              <div>
                <strong>Solde caisse: <?= number_format($solde_caisse, 0, ',', ' ') ?> FCFA</strong>
                <p class="text-muted text-sm mb-0">Situation financière</p>
              </div>
            </a>
            <div class="dropdown-divider"></div>
            <a href="tickets.php" class="dropdown-item dropdown-footer">
              <i class="fas fa-eye mr-2"></i>
              Voir tous les tickets
            </a>
          </div>
        </li>
        <!-- Profil utilisateur -->
        <li class="nav-item dropdown d-none d-sm-block">
          <a class="nav-link" data-toggle="dropdown" href="javascript:void(0)" title="Profil">
            <img src="../dossiers_images/<?php echo $_SESSION['avatar']; ?>" 
                 class="img-circle" 
                 style="width: 35px; height: 35px; border: 2px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.2);" 
                 alt="Avatar">
          </a>
          <div class="dropdown-menu dropdown-menu-right">
            <div class="dropdown-header">
              <strong><?php echo $_SESSION['nom']; ?> <?php echo $_SESSION['prenoms']; ?></strong>
              <p class="text-muted text-sm mb-0"><?php echo $_SESSION['user_role']; ?></p>
            </div>
            <div class="dropdown-divider"></div>
            <a href="profile.php" class="dropdown-item">
              <i class="fas fa-user mr-2"></i> Mon Profil
            </a>
            <a href="settings.php" class="dropdown-item">
              <i class="fas fa-cog mr-2"></i> Paramètres
            </a>
            <div class="dropdown-divider"></div>
            <a href="../logout.php" class="dropdown-item text-danger">
              <i class="fas fa-sign-out-alt mr-2"></i> Déconnexion
            </a>
          </div>
        </li>
        
        <!-- Plein écran -->
        <li class="nav-item">
          <a class="nav-link" data-widget="fullscreen" href="javascript:void(0)" role="button" title="Plein écran">
            <i class="fas fa-expand-arrows-alt"></i>
          </a>
        </li>
        
        <!-- Déconnexion rapide -->
        <li class="nav-item d-sm-none">
          <a class="nav-link text-danger" href="../logout.php" role="button" title="Déconnexion">
            <i class="fas fa-power-off"></i>
          </a>
        </li>
      </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
      <!-- Brand Logo -->
      <a href="tickets.php" class="brand-link">
        <img src="../dist/img/logo.png" alt="Unipalm" class="brand-image img-circle elevation-3"
          style="opacity: .8">
        <span class="brand-text font-weight-light">Unipalm</span>
      </a>

      <!-- Sidebar -->
      <div class="sidebar">
        <!-- Sidebar user panel (optional) -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
          <div class="image">
            <img src="../dossiers_images/<?php echo $_SESSION['avatar']; ?>" class="img-circle elevation-2" alt="Logo">
            <!-- <img src="../../dist/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image">-->
          </div>
          <div class="info">
            <a href="javascript:void(0)" class="d-block"><?php echo $_SESSION['nom']; ?> <?php echo $_SESSION['prenoms']; ?></a>
          </div>
        </div>

        <!-- SidebarSearch Form -->
        <div class="form-inline">
          <div class="input-group" data-widget="sidebar-search">
            <input class="form-control form-control-sidebar" type="search" placeholder="Search" aria-label="Search">
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
            <!-- Add icons to the links using the .nav-icon class
               with font-awesome or any other icon font library -->
            <li class="nav-item menu-open">
              <a href="javascript:void(0)" class="nav-link active">
                <i class="nav-icon fas fa-tachometer-alt"></i>
                <p>
                  Mes tickets
                  <i class="right fas fa-angle-left"></i>
                </p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                  <a href="tickets.php" class="nav-link active">
                    <i class="fas fa-ticket-alt"></i>
                    <p>Liste des tickets</p>
                  </a>
                </li>

                <li class="nav-item">
                  <a href="tickets_jour.php" class="nav-link">
                    <i class="fas fa-calendar-day"></i>
                    <p>Tickets du jour</p>
                  </a>
                </li>

                <li class="nav-item">
                  <a href="tickets_attente.php" class="nav-link">
                    <i class="fas fa-clock"></i>
                    <p>Tickets en Attente</p>
                  </a>
                </li>

                <li class="nav-item">
                  <a href="tickets_valides.php" class="nav-link">
                    <i class="fas fa-check-circle"></i>
                    <p>Tickets en Validés</p>
                  </a>
                </li>

                <li class="nav-item">
                  <a href="tickets_payes.php" class="nav-link">
                    <i class="fas fa-money-bill-wave"></i>
                    <p>Tickets Payés</p>
                  </a>
                </li>

                <li class="nav-item">
                  <a href="tickets_modifications.php" class="nav-link">
                    <i class="fas fa-edit"></i>
                    <p>Modifications de tickets</p>
                  </a>
                </li>

                <li class="nav-item">
                  <a href="recherche_trie.php" class="nav-link">
                    <i class="fas fa-search"></i>
                    <p>Recherche avancée</p>
                  </a>
                </li>

                <li class="nav-item">
                  <a href="recherche_chef_equipe.php" class="nav-link">
                    <i class="fas fa-search"></i>
                    <p>Recherche chef d'equipe</p>
                  </a>
                </li>
              </ul>
            </li>

            <li class="nav-item">
              <a href="javascript:void(0)" class="nav-link">
                <i class="nav-icon fas fa-table"></i>
                <p>
                  Listes des utilisateurs
                  <i class="fas fa-angle-left right"></i>
                </p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                  <a href="utilisateurs.php" class="nav-link">
                    <i class="fas fa-male"></i>
                    <p>Listes des utilisateurs</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="liste_admins.php" class="nav-link">
                    <i class="fas fa-user-tie"></i>
                    <p>Listes des admins</p>
                  </a>
                </li>

                <li class="nav-item">
                  <a href="gestion_access.php" class="nav-link">
                    <i class="fas fa-lock"></i>
                    <p>Gestion des acccès</p>
                  </a>
                </li>
              </ul>
            </li>

            <li class="nav-item">
              <a href="javascript:void(0)" class="nav-link">
                <i class="nav-icon fas fa-cogs"></i>
                <p>
                  Gestion
                  <i class="fas fa-angle-left right"></i>
                </p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                  <a href="chef_equipe.php" class="nav-link">
                    <i class="fas fa-users"></i>
                    <p>Gestion chef equipe</p>
                  </a>
                </li>

                <li class="nav-item">
                  <a href="agents.php" class="nav-link">
                    <i class="fas fa-user-plus"></i>
                    <p>Gestion des agents</p>
                  </a>
                </li>

                <li class="nav-item">
                  <a href="usines.php" class="nav-link">
                    <i class="fas fa-industry"></i>
                    <p>Gestion des usines</p>
                  </a>
                </li>

                <li class="nav-item">
                  <a href="vehicules.php" class="nav-link">
                    <i class="fas fa-car"></i>
                    <p>Gestion des véhicules</p>
                  </a>
                </li>
              </ul>
            </li>

            <li class="nav-item">
              <a href="javascript:void(0)" class="nav-link">
                <i class="nav-icon fas fa-money-bill-alt"></i>
                <p>
                  Gestion financière
                  <i class="fas fa-angle-left right"></i>
                </p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                  <a href="prix_unitaires.php" class="nav-link">
                    <i class="fas fa-tags"></i>
                    <p>Prix unitaires</p>
                  </a>
                </li>

                <li class="nav-item">
                  <a href="bordereaux.php" class="nav-link">
                    <i class="fas fa-file-invoice"></i>
                    <p>Bordereaux</p>
                  </a>
                </li>

                <li class="nav-item">
                  <a href="financements.php" class="nav-link">
                    <i class="fas fa-money-bill-wave"></i>
                    <p>Financements</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="prets.php" class="nav-link">
                    <i class="fas fa-money-bill-wave"></i>
                    <p>Prêts</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="gestion_usines.php" class="nav-link">
                    <i class="fas fa-money-bill-wave"></i>
                    <p>Montant Usines</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="comptes_agents.php" class="nav-link">
                    <i class="fas fa-user"></i>
                    <p>Comptes agents</p>
                  </a>
                </li>
                
                <li class="nav-item">
                  <a href="recus.php" class="nav-link">
                    <i class="fas fa-receipt"></i>
                    <p>Reçus</p>
                  </a>
                </li>

                <li class="nav-item">
                  <a href="divers.php" class="nav-link">
                    <i class="fas fa-money-bill-wave-alt"></i>
                    <p>Sorties diverses</p>
                  </a>
                </li>
              </ul>
            </li>

            <li class="nav-item">
              <a href="javascript:void(0)" class="nav-link">
                <i class="nav-icon fas fa-door-open"></i>
                <p>
                  Gestion des sorties
                  <i class="fas fa-angle-left right"></i>
                </p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                  <a href="demandes.php" class="nav-link">
                    <i class="fas fa-list nav-icon"></i>
                    <p>Liste des demandes</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="demande_attente.php" class="nav-link">
                    <i class="fas fa-check-circle nav-icon"></i>
                    <p>Demandes en attente</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="demande_valide.php" class="nav-link">
                    <i class="fas fa-check-circle nav-icon"></i>
                    <p>Demandes validées</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="sorties_diverses.php" class="nav-link">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Sorties diverses</p>
                  </a>
                </li>
              </ul>
            </li>


            <li class="nav-item">
              <a href="javascript:void(0)" class="nav-link">
                <i class="nav-icon fas fa-door-open"></i>
                <p>
                  Gestion des ponts
                  <i class="fas fa-angle-left right"></i>
                </p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                  <a href="ponts.php" class="nav-link">
                    <i class="fas fa-list nav-icon"></i>
                    <p>Liste des ponts</p>
                  </a>
                </li>
              </ul>
            </li>


            <li class="nav-item">
              <a href="javascript:void(0)" class="nav-link">
                <i class="nav-icon fas fa-tree"></i>
                <p>
                  Gestion des plantations
                  <i class="fas fa-angle-left right"></i>
                </p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                  <a href="plantations.php" class="nav-link">
                    <i class="fas fa-seedling nav-icon"></i>
                    <p>Liste des plantations</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="collecteurs.php" class="nav-link">
                    <i class="fas fa-users nav-icon"></i>
                    <p>Liste des collecteurs</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="regions.php" class="nav-link">
                    <i class="fas fa-map-marked-alt nav-icon"></i>
                    <p>Régions</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="zones_collecteurs.php" class="nav-link">
                    <i class="fas fa-map-marked-alt nav-icon"></i>
                    <p>Zones</p>
                  </a>
                </li>
              </ul>
            </li>

            <li class="nav-header"><strong>TRANSACTIONS</strong></li>
            <li class="nav-item">
              <a href="approvisionnement.php" class="nav-link">
                <i class="fas fa-truck-loading"></i>
                <p>
                  Approvisionnement
                  <span class="badge badge-info right">2</span>
                </p>
              </a>
            </li>

            <li class="nav-item">
              <a href="paiements.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'paiements.php' ? 'active' : '' ?>">
                <i class="nav-icon fas fa-money-bill"></i>
                <p>Paiements de tickets et bordereaux</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="paiements_demande.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'paiements_demande.php' ? 'active' : '' ?>">
                <i class="nav-icon fas fa-money-bill"></i>
                <p>Paiements demandes</p>
              </a>
            </li>
            
            <li class="nav-item">
              <a href="recus.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'recus.php' ? 'active' : '' ?>">
                <i class="nav-icon fas fa-receipt"></i>
                <p>Reçus des paiements</p>
              </a>
            </li>

            <li class="nav-item">
              <a href="recus_demandes.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'recus_demandes.php' ? 'active' : '' ?>">
                <i class="nav-icon fas fa-receipt"></i>
                <p>Reçus des demandes</p>
              </a>
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
          <div class="row mb-2">
            <div class="col-sm-6">
              <h1 class="m-0">Tableau de bord</h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
              <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="tickets.php">Accueil</a></li>
                <li class="breadcrumb-item active"><?php echo $_SESSION['user_role']; ?></li>
              </ol>
            </div><!-- /.col -->
          </div><!-- /.row -->
        </div><!-- /.container-fluid -->
      </div>
      <!-- /.content-header -->

      <!-- Main content -->
      <section class="content">
        <div class="container-fluid">
          <!-- Small boxes (Stat box) -->
          <div class="row">
            <div class="col-lg-3 col-6">
              <!-- small box -->
              <div class="small-box bg-info">
                <div class="inner">
                  <h3><?php echo $ticket_total['nb_ticket_tt'];   ?>
                </h3>
                <p>Nombre ticket Total</strong></p>

                </div>
              </div>
            </div>
            
            <!-- ./col -->
            <div class="col-lg-3 col-6">
              <!-- small box -->
              <div class="small-box bg-danger">
                <div class="inner">
                <h3><?php echo $ticket_non_valide['nb_ticket_nv'];?>

               </h3>
               <p>Nombre de tickets en <strong>attente</strong></p>
                </div>
              </div>
            </div>
            <!-- ./col -->
            <div class="col-lg-3 col-6">
              <!-- small box -->
              <div class="small-box bg-warning">
                <div class="inner">
                <h3><?php echo $ticket_valide['nb_ticket_nv'];?>
                </h3>
                <p>Total tickets <strong>validés</strong></p>

                </div>
                 </div>
            </div>
            <!-- ./col -->
            <div class="col-lg-3 col-6">
              <!-- small box -->
              <div class="small-box bg-danger">
                <div class="inner">
                 <h3><?php echo $ticket_paye['nb_ticket_paye'];?>
                </h3>
                <p>Nombre de ticket <strong>VALIDES et non payés</strong></p>
                </div>
              </div>
            </div>
            <!-- ./col -->
          </div>
          <!-- /.row -->