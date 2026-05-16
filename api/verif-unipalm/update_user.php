<?php
/**
 * API — modifier un vérificateur (table users)
 *
 * POST /update_user.php
 * Body JSON : { "id": 1, "name": "...", "login": "...", "email": "...",
 *               "password": "...", "password_confirmation": "..." }  // mot de passe optionnel
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

$id    = (int) ($data['id'] ?? 0);
$name  = trim($data['name'] ?? '');
$login = trim($data['login'] ?? '');
$email = trim($data['email'] ?? '');
$password = (string) ($data['password'] ?? '');
$passwordConfirmation = (string) ($data['password_confirmation'] ?? $data['retype_password'] ?? '');

if ($id <= 0) {
    verifJsonResponse(422, ['success' => false, 'message' => 'ID utilisateur invalide']);
}

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

if ($password !== '') {
    $passwordError = verifValidatePassword($password, $passwordConfirmation);
    if ($passwordError !== null) {
        verifJsonResponse(422, ['success' => false, 'message' => $passwordError]);
    }
}

try {
    $stmt = $conn->prepare('SELECT id FROM users WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        verifJsonResponse(404, ['success' => false, 'message' => 'Utilisateur introuvable']);
    }

    $stmt = $conn->prepare(
        'SELECT id FROM users WHERE id != ? AND (login = ? OR email = ?) LIMIT 1'
    );
    $stmt->execute([$id, $login, $email]);
    if ($stmt->fetch()) {
        verifJsonResponse(409, [
            'success' => false,
            'message' => 'Ce login ou cet email est déjà utilisé par un autre compte',
        ]);
    }

    if ($password !== '') {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare(
            'UPDATE users SET name = ?, login = ?, email = ?, password = ?, updated_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$name, $login, $email, $hashedPassword, $id]);
    } else {
        $stmt = $conn->prepare(
            'UPDATE users SET name = ?, login = ?, email = ?, updated_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$name, $login, $email, $id]);
    }

    $stmt = $conn->prepare(
        'SELECT id, name, login, email, email_verified_at, created_at, updated_at FROM users WHERE id = ?'
    );
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    verifJsonResponse(200, [
        'success' => true,
        'message' => 'Vérificateur mis à jour avec succès',
        'data'    => verifFormatUser($user),
    ]);
} catch (PDOException $e) {
    verifJsonResponse(500, [
        'success' => false,
        'message' => 'Erreur lors de la mise à jour',
        'error'   => $e->getMessage(),
    ]);
}
