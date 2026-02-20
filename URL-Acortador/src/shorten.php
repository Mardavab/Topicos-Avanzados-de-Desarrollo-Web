<?php
// src/shorten.php
// POST /shorten  →  Crea una URL corta
// Este archivo es incluido por index.php, que ya cargó helpers y config.

$db = getDB();
$ip = getClientIP();

// ── 1. Rate Limiting ──────────────────────────────────────────────────────
if (!checkRateLimit($db, $ip)) {
    jsonResponse([
        'error' => 'Demasiadas peticiones. Límite: ' . RATE_LIMIT_MAX . ' por hora.'
    ], 429);
}

// ── 2. Leer y validar el body JSON ────────────────────────────────────────
$body = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE || !is_array($body)) {
    jsonResponse(['error' => 'El body debe ser JSON válido'], 400);
}

$originalUrl = trim($body['url'] ?? '');
if (empty($originalUrl)) {
    jsonResponse(['error' => 'El campo "url" es obligatorio'], 400);
}

// ── 3. Validar la URL ─────────────────────────────────────────────────────
$validation = validateUrl($originalUrl);
if (!$validation['valid']) {
    jsonResponse(['error' => $validation['error']], 400);
}

// ── 4. Parámetros opcionales ──────────────────────────────────────────────

// Fecha de expiración (formato: ISO 8601 o "YYYY-MM-DD HH:MM:SS")
$expiresAt = null;
if (!empty($body['expires_at'])) {
    $ts = strtotime($body['expires_at']);
    if ($ts === false || $ts <= time()) {
        jsonResponse(['error' => 'expires_at debe ser una fecha futura válida (ej: 2025-12-31)'], 400);
    }
    $expiresAt = date('Y-m-d H:i:s', $ts);
}

// Máximo de usos
$maxUses = null;
if (isset($body['max_uses'])) {
    $maxUses = (int) $body['max_uses'];
    if ($maxUses < 1) {
        jsonResponse(['error' => 'max_uses debe ser un número entero positivo'], 400);
    }
}

// ── 5. Generar código único y guardar ─────────────────────────────────────
try {
    $code = generateUniqueCode($db);

    $stmt = $db->prepare('
        INSERT INTO short_urls (code, original_url, creator_ip, max_uses, expires_at)
        VALUES (:code, :url, :ip, :max_uses, :expires_at)
    ');
    $stmt->execute([
        ':code'       => $code,
        ':url'        => $originalUrl,
        ':ip'         => $ip,
        ':max_uses'   => $maxUses,
        ':expires_at' => $expiresAt,
    ]);

} catch (RuntimeException $e) {
    jsonResponse(['error' => 'Error al generar código único. Intenta de nuevo.'], 500);
} catch (PDOException $e) {
    jsonResponse(['error' => 'Error al guardar en la base de datos'], 500);
}

// ── 6. Respuesta exitosa ──────────────────────────────────────────────────
jsonResponse([
    'success'      => true,
    'short_url'    => BASE_URL . '/' . $code,
    'code'         => $code,
    'original_url' => $originalUrl,
    'expires_at'   => $expiresAt,
    'max_uses'     => $maxUses,
    'created_at'   => date('Y-m-d H:i:s'),
], 201);
