<?php

function verifJsonResponse(int $code, array $payload): void
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function verifCorsHeaders(string $methods = 'GET, POST, OPTIONS'): void
{
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: ' . $methods);
    header('Access-Control-Allow-Headers: Content-Type, X-API-Key, Authorization');
}

function verifHandleOptions(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

function verifGetRequestApiKey(): string
{
    if (!empty($_SERVER['HTTP_X_API_KEY'])) {
        return trim((string) $_SERVER['HTTP_X_API_KEY']);
    }

    if (function_exists('getallheaders')) {
        foreach (getallheaders() as $name => $value) {
            if (strtolower($name) === 'x-api-key') {
                return trim((string) $value);
            }
        }
    }

    $auth = $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? '';

    if ($auth !== '' && preg_match('/Bearer\s+(.+)/i', $auth, $m)) {
        return trim($m[1]);
    }

    return trim((string) ($_GET['api_key'] ?? ''));
}

function verifCheckApiKey(): void
{
    $requiredKey = defined('VERIF_API_KEY') ? (string) VERIF_API_KEY : '';

    if ($requiredKey === '') {
        return;
    }

    $key = verifGetRequestApiKey();

    if ($key === '' || !hash_equals($requiredKey, $key)) {
        verifJsonResponse(401, [
            'success' => false,
            'message' => 'Clé API invalide ou manquante',
            'hint'    => 'Header X-API-Key ou paramètre api_key',
        ]);
    }
}

function verifFormatUser(array $row): array
{
    return [
        'id'                => (int) $row['id'],
        'name'              => $row['name'],
        'login'             => $row['login'],
        'email'             => $row['email'],
        'email_verified_at' => $row['email_verified_at'],
        'created_at'        => $row['created_at'],
        'updated_at'        => $row['updated_at'],
    ];
}

function verifGetInput(): array
{
    $raw = file_get_contents('php://input');
    $json = json_decode($raw ?: '', true);

    if (is_array($json) && !empty($json)) {
        return $json;
    }

    return array_merge($_POST, $_GET);
}

function verifValidateEmail(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

function verifValidateLogin(string $login): ?string
{
    if ($login === '') {
        return 'Le login est obligatoire';
    }

    if (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $login)) {
        return 'Login invalide (3 à 50 caractères : lettres, chiffres, . _ -)';
    }

    return null;
}

function verifValidatePassword(string $password, string $confirmation = ''): ?string
{
    if (strlen($password) < 8) {
        return 'Le mot de passe doit contenir au moins 8 caractères';
    }

    if ($confirmation !== '' && $password !== $confirmation) {
        return 'Les mots de passe ne correspondent pas';
    }

    if (!preg_match('/[A-Z]/', $password)) {
        return 'Le mot de passe doit contenir au moins une majuscule';
    }

    if (!preg_match('/[a-z]/', $password)) {
        return 'Le mot de passe doit contenir au moins une minuscule';
    }

    if (!preg_match('/[0-9]/', $password)) {
        return 'Le mot de passe doit contenir au moins un chiffre';
    }

    return null;
}
