<?php
require_once '../inc/functions/connexion.php';
require_once '../inc/functions/requete/api_requete_verificateurs.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: liste_verificateurs.php');
    exit;
}

$action   = $_POST['action'] ?? 'create';
$name     = trim($_POST['name'] ?? '');
$login    = trim($_POST['login'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$retype   = $_POST['retype_password'] ?? '';

if ($name === '' || $login === '' || $email === '') {
    $_SESSION['delete_pop'] = true;
    $_SESSION['message'] = 'Nom complet, login et email sont obligatoires.';
    header('Location: liste_verificateurs.php');
    exit;
}

if ($action === 'update') {
    $id = (int) ($_POST['id'] ?? 0);

    if ($id <= 0) {
        $_SESSION['delete_pop'] = true;
        $_SESSION['message'] = 'Identifiant invalide.';
        header('Location: liste_verificateurs.php');
        exit;
    }

    $payload = [
        'name'  => $name,
        'login' => $login,
        'email' => $email,
    ];

    if ($password !== '') {
        $payload['password'] = $password;
        $payload['password_confirmation'] = $retype;
    }

    $result = updateVerificateurViaApi($id, $payload);
} else {
    if ($password === '') {
        $_SESSION['delete_pop'] = true;
        $_SESSION['message'] = 'Le mot de passe est obligatoire pour un nouvel enregistrement.';
        header('Location: liste_verificateurs.php');
        exit;
    }

    $payload = [
        'name'                  => $name,
        'login'                 => $login,
        'email'                 => $email,
        'password'              => $password,
        'password_confirmation' => $retype,
    ];

    $result = createVerificateurViaApi($payload);
}

if ($result['success']) {
    $_SESSION['popup'] = true;
    $_SESSION['message'] = $result['message'] ?? 'Opération réussie.';
} else {
    $_SESSION['delete_pop'] = true;
    $_SESSION['message'] = $result['error'] ?? 'Erreur lors de l\'opération.';
}

header('Location: liste_verificateurs.php');
exit;
