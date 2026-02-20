<?php

require_once __DIR__ . '/controllers/QRController.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$controller = new QRController();

if ($uri === '/api/qr' && $method === 'POST') {
    $controller->generate();
} else {
    http_response_code(404);
    echo json_encode(["error" => "Endpoint no encontrado"]);
}