<?php
/**
 * API — enregistrer un vérificateur (table users)
 *
 * POST /create_user.php
 * Content-Type: application/json
 *
 * Body JSON :
 * {
 *   "name": "Jean Dupont",
 *   "login": "jdupont",
 *   "email": "jean@example.com",
 *   "password": "Secret123!",
 *   "password_confirmation": "Secret123!"
 * }
 */

require_once __DIR__ . '/_helpers.php';

verifCorsHeaders('POST, OPTIONS');
verifHandleOptions();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    verifJsonResponse(405, ['success' => false, 'message' => 'Méthode non autorisée. Utilisez POST.']);
}

require_once __DIR__ . '/connexion.php';
verifCheckApiKey();

$data = verifGetInput();

$name  = trim($data['name'] ?? '');
$login = trim($data['login'] ?? '');
$email = trim($data['email'] ?? '');
$password = (string) ($data['password'] ?? '');
$passwordConfirmation = (string) ($data['password_confirmation'] ?? $data['retype_password'] ?? '');

if ($name === '') {
    verifJsonResponse(422, ['success' => false, 'message' => 'Le nom complet est obligatoire']);
}

$loginError = verifValidateLogin($login);
if ($loginError !== null) {
    verifJsonResponse(422, ['success' => false, 'message' => $loginError]);
}

if ($email === '') {
    verifJsonResponse(422, ['success' => false, 'message' => 'L\'email est obligatoire']);
}

if (!verifValidateEmail($email)) {
    verifJsonResponse(422, ['success' => false, 'message' => 'Format email invalide']);
}

if ($password === '') {
    verifJsonResponse(422, ['success' => false, 'message' => 'Le mot de passe est obligatoire']);
}

$passwordError = verifValidatePassword($password, $passwordConfirmation);
if ($passwordError !== null) {
    verifJsonResponse(422, ['success' => false, 'message' => $passwordError]);
}

try {
    $stmt = $conn->prepare('SELECT id FROM users WHERE login = ? OR email = ? LIMIT 1');
    $stmt->execute([$login, $email]);
    if ($stmt->fetch()) {
        verifJsonResponse(409, [
            'success' => false,
            'message' => 'Un utilisateur avec ce login ou cet email existe déjà',
        ]);
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $conn->prepare(
        'INSERT INTO users (name, login, email, password, created_at, updated_at)
         VALUES (?, ?, ?, ?, NOW(), NOW())'
    );
    $stmt->execute([$name, $login, $email, $hashedPassword]);

    $newId = (int) $conn->lastInsertId();

    $stmt = $conn->prepare(
        'SELECT id, name, login, email, email_verified_at, created_at, updated_at FROM users WHERE id = ?'
    );
    $stmt->execute([$newId]);
    $user = $stmt->fetch();

    verifJsonResponse(201, [
        'success' => true,
        'message' => 'Vérificateur enregistré avec succès',
        'data'    => verifFormatUser($user),
    ]);
} catch (PDOException $e) {
    verifJsonResponse(500, [
        'success' => false,
        'message' => 'Erreur lors de l\'enregistrement',
        'error'   => $e->getMessage(),
    ]);
}
