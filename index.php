<?php
require_once './inc/functions/connexion.php';


if ($_SERVER['REQUEST_METHOD'] == 'POST') {

        $login = $_POST['login'];
        $password = $_POST['password'];
        $hasedpassword=hash('sha256',$password);

        if(empty($login) || empty($password))
        {
          $_SESSION['credentials_refused'] = true;
            header('Location: index.php');
            exit(0);
        }else {

          $stmt = $conn->prepare("SELECT * FROM utilisateurs WHERE login = :login AND password = :password AND statut_compte=1");
          $stmt->bindParam(':login', $login, PDO::PARAM_STR);
          
          $stmt->bindParam(':password', $hasedpassword, PDO::PARAM_STR);
          $stmt->execute();
  
        
          $user = $stmt->fetch(PDO::FETCH_ASSOC);
  
          if ($user) {
             
              $_SESSION['user_id'] = $user['id'];
              $_SESSION['nom'] = $user['nom'];
              $_SESSION['prenoms'] = $user['prenoms'];
              $_SESSION['user_role'] = $user['role'];
              $_SESSION['avatar'] = $user['avatar'];
  
          
              switch ($user['role']) {
                  case 'admin':
                    header("location: ../pages/tickets.php");
                      break;
                  case 'operateur':
                    header("location: ../operateurs/tickets.php");
                      break;
                  case 'directeur':
                    header("location: ../directeur/bordereaux.php");
                      break;
                  case 'verificateur':
                    header("location: verification_agent.php");
                      break;
                  default:
                      header('Location: ../caisse/approvisionnement.php');
                      break;
              }
              exit(0);
          } else {
              
            $_SESSION['connexion_refused'] = true;
            header('Location: index.php');
            exit(0);
          }


        }

       
        

}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>UniPalm - Connexion Sécurisée</title>

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <link rel="icon" href="dist/img/logo.png" type="image/x-icon">
  <link rel="shortcut icon" href="dist/img/logo.png" type="image/x-icon">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Toastr -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
  <style>
    /* ===== STYLES ULTRA-PROFESSIONNELS POUR LA PAGE DE CONNEXION ===== */
    
    :root {
      --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
      --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
      --glass-bg: rgba(255, 255, 255, 0.1);
      --glass-border: rgba(255, 255, 255, 0.2);
      --text-primary: #2c3e50;
      --text-secondary: #7f8c8d;
      --shadow-light: 0 8px 32px rgba(31, 38, 135, 0.37);
      --shadow-heavy: 0 15px 35px rgba(31, 38, 135, 0.5);
      --border-radius: 20px;
      --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Inter', sans-serif;
      background: var(--primary-gradient);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow: hidden;
    }
    
    /* Arrière-plan animé */
    body::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: 
        radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%),
        radial-gradient(circle at 40% 40%, rgba(120, 219, 255, 0.3) 0%, transparent 50%);
      animation: backgroundShift 20s ease-in-out infinite;
    }
    
    @keyframes backgroundShift {
      0%, 100% { transform: scale(1) rotate(0deg); }
      50% { transform: scale(1.1) rotate(2deg); }
    }
    
    /* Particules flottantes */
    .particles {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      pointer-events: none;
      z-index: 1;
    }
    
    .particle {
      position: absolute;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 50%;
      animation: float 15s infinite linear;
    }
    
    @keyframes float {
      0% {
        transform: translateY(100vh) rotate(0deg);
        opacity: 0;
      }
      10% {
        opacity: 1;
      }
      90% {
        opacity: 1;
      }
      100% {
        transform: translateY(-10vh) rotate(360deg);
        opacity: 0;
      }
    }
    
    /* Conteneur principal */
    .login-container {
      position: relative;
      z-index: 10;
      width: 100%;
      max-width: 450px;
      padding: 2rem;
    }
    
    /* Carte de connexion glassmorphism */
    .login-card {
      background: var(--glass-bg);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid var(--glass-border);
      border-radius: var(--border-radius);
      box-shadow: var(--shadow-heavy);
      padding: 3rem 2.5rem;
      position: relative;
      overflow: hidden;
      animation: slideInUp 0.8s ease-out;
    }
    
    @keyframes slideInUp {
      from {
        opacity: 0;
        transform: translateY(50px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    /* Effet de brillance sur la carte */
    .login-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
      transition: left 0.8s;
    }
    
    .login-card:hover::before {
      left: 100%;
    }
    
    /* En-tête avec logo */
    .login-header {
      text-align: center;
      margin-bottom: 2.5rem;
    }
    
    .logo-container {
      position: relative;
      display: inline-block;
      margin-bottom: 1.5rem;
    }
    
    .logo {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      border: 4px solid rgba(255, 255, 255, 0.3);
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
      transition: var(--transition);
      animation: logoFloat 3s ease-in-out infinite;
    }
    
    @keyframes logoFloat {
      0%, 100% { transform: translateY(0px); }
      50% { transform: translateY(-10px); }
    }
    
    .logo:hover {
      transform: scale(1.1) rotateY(180deg);
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
    }
    
    .login-title {
      color: white;
      font-family: 'Poppins', sans-serif;
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
      text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
      letter-spacing: 1px;
    }
    
    .login-subtitle {
      color: rgba(255, 255, 255, 0.8);
      font-size: 1rem;
      font-weight: 400;
      margin-bottom: 0;
    }
    
    /* Formulaire */
    .login-form {
      position: relative;
    }
    
    .form-group {
      margin-bottom: 2rem;
      position: relative;
    }
    
    .form-label {
      color: white;
      font-weight: 600;
      font-size: 0.9rem;
      margin-bottom: 0.75rem;
      display: block;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .input-container {
      position: relative;
      overflow: hidden;
      border-radius: 15px;
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      transition: var(--transition);
    }
    
    .input-container:hover {
      background: rgba(255, 255, 255, 0.15);
      border-color: rgba(255, 255, 255, 0.3);
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }
    
    .input-container:focus-within {
      background: rgba(255, 255, 255, 0.2);
      border-color: rgba(255, 255, 255, 0.5);
      box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.1);
    }
    
    .form-input {
      width: 100%;
      padding: 1.25rem 1.5rem 1.25rem 4rem;
      border: none;
      background: transparent;
      color: white;
      font-size: 1rem;
      font-weight: 500;
      outline: none;
      transition: var(--transition);
    }
    
    .form-input::placeholder {
      color: rgba(255, 255, 255, 0.6);
      font-style: italic;
    }
    
    .input-icon {
      position: absolute;
      left: 1.5rem;
      top: 50%;
      transform: translateY(-50%);
      color: rgba(255, 255, 255, 0.7);
      font-size: 1.2rem;
      transition: var(--transition);
    }
    
    .input-container:focus-within .input-icon {
      color: white;
      transform: translateY(-50%) scale(1.1);
    }
    
    /* Bouton afficher/masquer mot de passe */
    .toggle-password {
      position: absolute;
      right: 1rem;
      top: 50%;
      transform: translateY(-50%);
      background: transparent;
      border: none;
      color: rgba(255, 255, 255, 0.7);
      font-size: 1.1rem;
      cursor: pointer;
      padding: 0;
      line-height: 1;
    }
    .toggle-password:hover {
      color: #ffffff;
    }
    
    /* Bouton de connexion */
    .login-button {
      width: 100%;
      padding: 1.25rem;
      background: var(--success-gradient);
      border: none;
      border-radius: 15px;
      color: white;
      font-size: 1.1rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1px;
      cursor: pointer;
      transition: var(--transition);
      position: relative;
      overflow: hidden;
      box-shadow: 0 8px 25px rgba(79, 172, 254, 0.3);
      margin-top: 1rem;
    }
    
    .login-button::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
      transition: left 0.5s;
    }
    
    .login-button:hover::before {
      left: 100%;
    }
    
    .login-button:hover {
      transform: translateY(-3px);
      box-shadow: 0 15px 35px rgba(79, 172, 254, 0.4);
    }
    
    .login-button:active {
      transform: translateY(-1px);
    }
    
    /* Loading state */
    .login-button.loading {
      pointer-events: none;
      opacity: 0.8;
    }
    
    .login-button.loading::after {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: 20px;
      height: 20px;
      margin: -10px 0 0 -10px;
      border: 2px solid transparent;
      border-top: 2px solid white;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    /* Pied de page */
    .login-footer {
      text-align: center;
      margin-top: 2rem;
      padding-top: 2rem;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .footer-text {
      color: rgba(255, 255, 255, 0.6);
      font-size: 0.9rem;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
      .login-container {
        padding: 1rem;
        max-width: 100%;
      }
      
      .login-card {
        padding: 2rem 1.5rem;
      }
      
      .login-title {
        font-size: 1.5rem;
      }
      
      .logo {
        width: 80px;
        height: 80px;
      }
    }
    
    /* Animations d'entrée */
    .fade-in {
      animation: fadeIn 0.6s ease-out;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    
    .slide-in-left {
      animation: slideInLeft 0.6s ease-out;
    }
    
    @keyframes slideInLeft {
      from {
        opacity: 0;
        transform: translateX(-30px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }
    
    .slide-in-right {
      animation: slideInRight 0.6s ease-out;
    }
    
    @keyframes slideInRight {
      from {
        opacity: 0;
        transform: translateX(30px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }
  </style>
</head>

<body>
  <!-- Particules flottantes -->
  <div class="particles">
    <div class="particle" style="left: 10%; width: 4px; height: 4px; animation-delay: 0s;"></div>
    <div class="particle" style="left: 20%; width: 6px; height: 6px; animation-delay: 2s;"></div>
    <div class="particle" style="left: 30%; width: 3px; height: 3px; animation-delay: 4s;"></div>
    <div class="particle" style="left: 40%; width: 5px; height: 5px; animation-delay: 6s;"></div>
    <div class="particle" style="left: 50%; width: 4px; height: 4px; animation-delay: 8s;"></div>
    <div class="particle" style="left: 60%; width: 6px; height: 6px; animation-delay: 10s;"></div>
    <div class="particle" style="left: 70%; width: 3px; height: 3px; animation-delay: 12s;"></div>
    <div class="particle" style="left: 80%; width: 5px; height: 5px; animation-delay: 14s;"></div>
    <div class="particle" style="left: 90%; width: 4px; height: 4px; animation-delay: 16s;"></div>
  </div>

  <div class="login-container">
    <div class="login-card">
      <!-- En-tête -->
      <div class="login-header">
        <div class="logo-container fade-in">
          <img src="dist/img/logo.png" alt="UniPalm Logo" class="logo">
        </div>
        <h1 class="login-title slide-in-left">UniPalm</h1>
        <p class="login-subtitle slide-in-right">Plateforme de Gestion Sécurisée</p>
      </div>

      <!-- Formulaire -->
      <form class="login-form" action="" method="post" id="loginForm">
        <div class="form-group slide-in-left">
          <label class="form-label" for="login">
            <i class="fas fa-user-circle"></i> Identifiant
          </label>
          <div class="input-container">
            <i class="fas fa-envelope input-icon"></i>
            <input 
              type="text" 
              class="form-input" 
              id="login" 
              name="login" 
              placeholder="Entrez votre identifiant"
              required
              autocomplete="username"
            >
          </div>
        </div>

        <div class="form-group slide-in-right">
          <label class="form-label" for="password">
            <i class="fas fa-shield-alt"></i> Mot de passe
          </label>
          <div class="input-container">
            <i class="fas fa-lock input-icon"></i>
            <input 
              type="password" 
              class="form-input" 
              id="password" 
              name="password" 
              placeholder="Entrez votre mot de passe"
              required
              autocomplete="current-password"
            >
            <button type="button" class="toggle-password" aria-label="Afficher le mot de passe">
              <i class="fa-solid fa-eye"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="login-button fade-in" name="btn_login" id="loginBtn">
          <i class="fas fa-sign-in-alt"></i> Se connecter
        </button>
      </form>

      <!-- Pied de page -->
      <div class="login-footer fade-in">
        <p class="footer-text">
          <i class="fas fa-shield-alt"></i> Connexion sécurisée SSL
        </p>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
  
  <script>
    // Configuration Toastr
    toastr.options = {
      "closeButton": true,
      "debug": false,
      "newestOnTop": true,
      "progressBar": true,
      "positionClass": "toast-top-right",
      "preventDuplicates": false,
      "onclick": null,
      "showDuration": "300",
      "hideDuration": "1000",
      "timeOut": "5000",
      "extendedTimeOut": "1000",
      "showEasing": "swing",
      "hideEasing": "linear",
      "showMethod": "fadeIn",
      "hideMethod": "fadeOut"
    };
    
    // Animation du formulaire
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('loginForm');
      const loginBtn = document.getElementById('loginBtn');
      
      // Animation des champs au focus
      const inputs = document.querySelectorAll('.form-input');
      inputs.forEach(input => {
        input.addEventListener('focus', function() {
          this.parentElement.style.transform = 'translateY(-2px)';
        });
        
        input.addEventListener('blur', function() {
          this.parentElement.style.transform = 'translateY(0)';
        });
      });
      
      // Animation du bouton de soumission
      form.addEventListener('submit', function(e) {
        loginBtn.classList.add('loading');
        loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Connexion en cours...';
        
        // Simulation d'un délai pour l'effet visuel
        setTimeout(() => {
          // Le formulaire sera soumis normalement
        }, 500);
      });
      
      // Validation en temps réel
      inputs.forEach(input => {
        input.addEventListener('input', function() {
          if (this.value.length > 0) {
            this.parentElement.style.borderColor = 'rgba(79, 172, 254, 0.5)';
          } else {
            this.parentElement.style.borderColor = 'rgba(255, 255, 255, 0.2)';
          }
        });
      });

      // Afficher / masquer le mot de passe
      const togglePwdBtn = document.querySelector('.toggle-password');
      const pwdInput = document.getElementById('password');
      if (togglePwdBtn && pwdInput) {
        togglePwdBtn.addEventListener('click', function() {
          const isHidden = pwdInput.getAttribute('type') === 'password';
          pwdInput.setAttribute('type', isHidden ? 'text' : 'password');
          this.innerHTML = isHidden 
            ? '<i class="fa-solid fa-eye-slash"></i>' 
            : '<i class="fa-solid fa-eye"></i>';
          this.setAttribute('aria-label', isHidden ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
        });
      }
    });
    
    // Effet de parallaxe léger
    document.addEventListener('mousemove', function(e) {
      const card = document.querySelector('.login-card');
      const rect = card.getBoundingClientRect();
      const x = e.clientX - rect.left - rect.width / 2;
      const y = e.clientY - rect.top - rect.height / 2;
      
      const rotateX = (y / rect.height) * 5;
      const rotateY = (x / rect.width) * 5;
      
      card.style.transform = `perspective(1000px) rotateX(${-rotateX}deg) rotateY(${rotateY}deg)`;
    });
    
    document.addEventListener('mouseleave', function() {
      const card = document.querySelector('.login-card');
      card.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg)';
    });
  </script>

  <?php
if (isset($_SESSION['connexion_refused']) && $_SESSION['connexion_refused'] == true) {
?>
  <script>
    toastr.error('🚫 Identifiants incorrects', 'Erreur de connexion', {
      "iconClass": "toast-error",
      "timeOut": "6000"
    });
  </script>
<?php
  unset($_SESSION['connexion_refused']);
}

if (isset($_SESSION['credentials_refused']) && $_SESSION['credentials_refused'] == true) {
?>
  <script>
    toastr.warning('⚠️ Veuillez remplir tous les champs', 'Champs requis', {
      "iconClass": "toast-warning",
      "timeOut": "5000"
    });
  </script>
<?php
  unset($_SESSION['credentials_refused']);
}
?>
  
</body>

</html>