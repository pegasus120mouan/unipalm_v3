<?php
/**
 * API — récupération des utilisateurs (table users)
 *
 * GET /users.php                          → liste (pagination optionnelle)
 * GET /users.php?id=1                     → un utilisateur
 * GET /users.php?login=admin              → recherche par login
 * GET /users.php?search=admin             → recherche (name, login, email)
 * GET /users.php?page=1&limit=20          → pagination
 *
 * Authentification (si VERIF_API_KEY est défini dans connexion.php) :
 *   Header X-API-Key: votre_cle   OU   ?api_key=votre_cle
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

require_once __DIR__ . '/connexion.php';

function jsonResponse(int $code, array $payload): void
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function getRequestApiKey(): string
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

function checkApiKey(): void
{
    $requiredKey = defined('VERIF_API_KEY') ? (string) VERIF_API_KEY : '';

    if ($requiredKey === '') {
        return;
    }

    $key = getRequestApiKey();

    if ($key === '' || !hash_equals($requiredKey, $key)) {
        jsonResponse(401, [
            'success' => false,
            'message' => 'Clé API invalide ou manquante',
            'hint'    => 'Ajoutez le header X-API-Key ou le paramètre ?api_key=',
        ]);
    }
}

function formatUser(array $row): array
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

checkApiKey();

$id     = isset($_GET['id']) ? (int) $_GET['id'] : null;
$login  = trim($_GET['login'] ?? '');
$search = trim($_GET['search'] ?? '');
$page   = max(1, (int) ($_GET['page'] ?? 1));
$limit  = min(100, max(1, (int) ($_GET['limit'] ?? 50)));
$offset = ($page - 1) * $limit;

$select = 'SELECT id, name, login, email, email_verified_at, created_at, updated_at FROM users';

try {
    if ($id > 0) {
        $stmt = $conn->prepare($select . ' WHERE id = ?');
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        if (!$user) {
            jsonResponse(404, ['success' => false, 'message' => 'Utilisateur introuvable']);
        }

        jsonResponse(200, [
            'success' => true,
            'data'    => formatUser($user),
        ]);
    }

    if ($login !== '') {
        $stmt = $conn->prepare($select . ' WHERE login = ? LIMIT 1');
        $stmt->execute([$login]);
        $user = $stmt->fetch();

        if (!$user) {
            jsonResponse(404, ['success' => false, 'message' => 'Utilisateur introuvable']);
        }

        jsonResponse(200, [
            'success' => true,
            'data'    => formatUser($user),
        ]);
    }

    $where  = [];
    $params = [];

    if ($search !== '') {
        $where[]  = '(name LIKE ? OR login LIKE ? OR email LIKE ?)';
        $term     = '%' . $search . '%';
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }

    $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

    $countStmt = $conn->prepare('SELECT COUNT(*) FROM users' . $whereSql);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $sql = $select . $whereSql . ' ORDER BY id ASC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $users = array_map('formatUser', $rows);

    jsonResponse(200, [
        'success' => true,
        'meta'    => [
            'total'       => $total,
            'page'        => $page,
            'limit'       => $limit,
            'total_pages' => $limit > 0 ? (int) ceil($total / $limit) : 0,
        ],
        'data' => $users,
    ]);
} catch (PDOException $e) {
    jsonResponse(500, [
        'success' => false,
        'message' => 'Erreur lors de la récupération des utilisateurs',
    ]);
}
