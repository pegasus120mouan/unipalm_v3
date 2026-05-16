<?php
/**
 * Client API — vérificateurs (table users sur verif-unipalm)
 * https://api.objetombrepegasus.online/api/verif-unipalm/users.php
 */

const VERIF_UNIPALM_API_URL         = 'https://api.objetombrepegasus.online/api/verif-unipalm/users.php';
const VERIF_UNIPALM_API_CREATE_URL = 'https://api.objetombrepegasus.online/api/verif-unipalm/create_user.php';
const VERIF_UNIPALM_API_UPDATE_URL = 'https://api.objetombrepegasus.online/api/verif-unipalm/update_user.php';

/** Clé API si activée sur le serveur distant (laisser vide si désactivée) */
const VERIF_UNIPALM_API_KEY = '';

/**
 * Appel HTTP vers l'API verif-unipalm
 */
function verifUnipalmApiRequest(string $url, string $method = 'GET', ?array $body = null): array
{
    $headers = ['Accept: application/json'];
    if (VERIF_UNIPALM_API_KEY !== '') {
        $headers[] = 'X-API-Key: ' . VERIF_UNIPALM_API_KEY;
    }

    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_CUSTOMREQUEST  => strtoupper($method),
    ];

    if ($body !== null) {
        $json = json_encode($body, JSON_UNESCAPED_UNICODE);
        $headers[] = 'Content-Type: application/json';
        $opts[CURLOPT_HTTPHEADER] = $headers;
        $opts[CURLOPT_POSTFIELDS] = $json;
    }

    curl_setopt_array($ch, $opts);

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['success' => false, 'http_code' => 0, 'data' => null, 'meta' => [], 'error' => $curlError];
    }

    $json = json_decode($response, true);

    if (!is_array($json)) {
        $snippet = trim(strip_tags(substr((string) $response, 0, 120)));
        $detail  = $snippet !== '' ? " — $snippet" : '';
        return [
            'success'   => false,
            'http_code' => $httpCode,
            'data'      => null,
            'meta'      => [],
            'error'     => "Réponse API invalide (HTTP $httpCode)$detail",
        ];
    }

    $ok = !empty($json['success']) && $httpCode >= 200 && $httpCode < 300;

    return [
        'success'   => $ok,
        'http_code' => $httpCode,
        'data'      => $json['data'] ?? null,
        'meta'      => $json['meta'] ?? [],
        'message'   => $json['message'] ?? null,
        'error'     => $ok ? null : ($json['message'] ?? "HTTP $httpCode"),
    ];
}

/**
 * Appel GET vers l'API users.php
 */
function fetchVerificateursApi(array $params = []): array
{
    $url = VERIF_UNIPALM_API_URL;
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    $result = verifUnipalmApiRequest($url, 'GET');

    if (!$result['success']) {
        return [
            'success' => false,
            'data'    => [],
            'meta'    => [],
            'error'   => $result['error'],
        ];
    }

    $data = $result['data'] ?? [];
    if (isset($data['id'])) {
        $data = [$data];
    }

    return [
        'success' => true,
        'data'    => is_array($data) ? $data : [],
        'meta'    => $result['meta'],
        'error'   => null,
    ];
}

/**
 * Enregistrer un vérificateur via POST create_user.php
 */
function createVerificateurViaApi(array $payload): array
{
    $result = verifUnipalmApiRequest(VERIF_UNIPALM_API_CREATE_URL, 'POST', $payload);

    return [
        'success' => $result['success'],
        'data'    => $result['data'],
        'message' => $result['message'] ?? $result['error'],
        'error'   => $result['error'],
    ];
}

/**
 * Modifier un vérificateur via POST update_user.php
 */
function updateVerificateurViaApi(int $id, array $payload): array
{
    $payload['id'] = $id;
    $result = verifUnipalmApiRequest(VERIF_UNIPALM_API_UPDATE_URL, 'POST', $payload);

    return [
        'success' => $result['success'],
        'data'    => $result['data'],
        'message' => $result['message'] ?? $result['error'],
        'error'   => $result['error'],
    ];
}

/**
 * Récupérer un vérificateur par ID
 */
function getVerificateurByIdFromApi(int $id): ?array
{
    $result = fetchVerificateursApi(['id' => $id]);

    if (!$result['success'] || empty($result['data'])) {
        return null;
    }

    return mapApiUserToVerificateur($result['data'][0]);
}

/**
 * Normalise un enregistrement API vers le format attendu par liste_verificateurs.php
 */
function mapApiUserToVerificateur(array $user): array
{
    $name = trim($user['name'] ?? '');

    return [
        'id'                => (int) ($user['id'] ?? 0),
        'name'              => $name,
        'contact'           => $user['email'] ?? '',
        'email'             => $user['email'] ?? '',
        'login'             => $user['login'] ?? '',
        'email_verified_at' => $user['email_verified_at'] ?? null,
        'statut_compte'     => !empty($user['email_verified_at']) ? 1 : 1,
        'avatar'            => 'default.jpg',
        'created_at'        => $user['created_at'] ?? null,
        'updated_at'        => $user['updated_at'] ?? null,
    ];
}

/**
 * Liste des vérificateurs depuis l'API (recherche optionnelle)
 */
function getVerificateursFromApi(string $search = ''): array
{
    $params = ['limit' => 100];
    if ($search !== '') {
        $params['search'] = $search;
    }

    $result = fetchVerificateursApi($params);
    if (!$result['success']) {
        return [];
    }

    return array_map('mapApiUserToVerificateur', $result['data']);
}

/**
 * Récupère les métadonnées + liste (pour affichage des totaux)
 */
function getVerificateursApiWithMeta(string $search = ''): array
{
    $params = ['limit' => 100];
    if ($search !== '') {
        $params['search'] = $search;
    }

    $result = fetchVerificateursApi($params);

    if (!$result['success']) {
        return [
            'success'       => false,
            'verificateurs' => [],
            'total'         => 0,
            'error'         => $result['error'],
        ];
    }

    $users = array_map('mapApiUserToVerificateur', $result['data']);

    return [
        'success'       => true,
        'verificateurs' => $users,
        'total'         => (int) ($result['meta']['total'] ?? count($users)),
        'error'         => null,
    ];
}
