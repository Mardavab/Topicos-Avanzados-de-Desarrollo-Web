<?php
// public/index.php  ← Punto de entrada único de la API

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/helpers.php';

// Configurar headers CORS y JSON por defecto
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ──────────────────────────────────────────────
// ROUTER SIMPLE
// Parsear la ruta desde la URL
// ──────────────────────────────────────────────
$requestUri    = $_SERVER['REQUEST_URI'];
$scriptDir     = dirname($_SERVER['SCRIPT_NAME']);
$path          = str_replace($scriptDir, '', parse_url($requestUri, PHP_URL_PATH));
$path          = '/' . trim($path, '/');
$method        = $_SERVER['REQUEST_METHOD'];

// Rutas disponibles:
//   POST /shorten          → Crear URL corta
//   GET  /{code}           → Redirigir
//   GET  /stats/{code}     → Estadísticas

if ($method === 'POST' && $path === '/shorten') {
    require __DIR__ . '/../src/shorten.php';

} elseif ($method === 'GET' && preg_match('#^/stats/([A-Za-z0-9]{1,20})$#', $path, $matches)) {
    $code = $matches[1];
    require __DIR__ . '/../src/stats.php';

} elseif ($method === 'GET' && preg_match('#^/([A-Za-z0-9]{1,20})$#', $path, $matches)) {
    $code = $matches[1];
    require __DIR__ . '/../src/redirect.php';

} else {
    jsonResponse([
        'error'   => 'Ruta no encontrada',
        'endpoints' => [
            'POST /shorten'       => 'Crear URL corta',
            'GET /{code}'         => 'Redirigir a URL original',
            'GET /stats/{code}'   => 'Ver estadísticas',
        ]
    ], 404);
}
