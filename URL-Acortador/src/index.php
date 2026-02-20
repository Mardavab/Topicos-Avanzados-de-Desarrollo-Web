<?php
declare(strict_types=1);

// ─── Bootstrap ──────────────────────────────────────────────────────────────
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/RateLimiter.php';
require_once __DIR__ . '/UrlService.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri    = rtrim($uri, '/') ?: '/';
$ip     = getClientIp();

$pdo        = getConnection();
$limiter    = new RateLimiter($pdo);
$service    = new UrlService($pdo);

// ─── Router ─────────────────────────────────────────────────────────────────
try {
    // POST /shorten
    if ($method === 'POST' && $uri === '/shorten') {
        $limiter->check($ip, limit: 20, windowSeconds: 60);

        $body = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            jsonError(400, 'Invalid JSON body');
        }

        $result = $service->shorten(
            originalUrl: $body['url']        ?? null,
            expiresAt:   $body['expires_at'] ?? null,
            maxUses:     $body['max_uses']   ?? null,
            creatorIp:   $ip
        );

        http_response_code(201);
        echo json_encode(['data' => $result]);
        exit;
    }

    // GET /stats/{code}
    if ($method === 'GET' && preg_match('#^/stats/([A-Za-z0-9]+)$#', $uri, $m)) {
        $stats = $service->stats($m[1]);
        echo json_encode(['data' => $stats]);
        exit;
    }

    // GET /{code}  — redirect
    if ($method === 'GET' && preg_match('#^/([A-Za-z0-9]+)$#', $uri, $m)) {
        $url = $service->resolve($m[1], $ip, $_SERVER['HTTP_USER_AGENT'] ?? '');
        header('Location: ' . $url, true, 302);
        exit;
    }

    // GET /  — health check
    if ($method === 'GET' && $uri === '/') {
        echo json_encode(['status' => 'ok', 'service' => 'URL Shortener API']);
        exit;
    }

    jsonError(404, 'Endpoint not found');

} catch (RateLimitException $e) {
    jsonError(429, $e->getMessage());
} catch (ApiException $e) {
    jsonError($e->getCode(), $e->getMessage());
} catch (Throwable $e) {
    error_log($e->getMessage());
    jsonError(500, 'Internal server error');
}
