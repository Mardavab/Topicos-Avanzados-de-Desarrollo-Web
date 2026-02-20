<?php

declare(strict_types=1);

require_once __DIR__ . '/controllers/QRController.php';

header('Access-Control-Allow-Origin: *');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Normalizar URI (quitar barra final si existe)
$uri = rtrim($uri, '/');

$controller = new QRController();

if ($uri === '/api/qr' && $method === 'POST') {

    $controller->generate();
    exit;

}

http_response_code(404);
header('Content-Type: application/json');

echo json_encode([
    "error" => "Endpoint no encontrado"
]);
