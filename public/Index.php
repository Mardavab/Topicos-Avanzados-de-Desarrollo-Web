<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../app/Response.php';
require_once __DIR__ . '/../app/Services/PasswordGenerator.php';
require_once __DIR__ . '/../app/Services/PasswordValidator.php';
require_once __DIR__ . '/../app/Validators/InputValidator.php';
require_once __DIR__ . '/../app/Controllers/PasswordController.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = '/' . trim(str_replace('/public', '', $uri), '/');

$controller = new PasswordController();

try {
    match (true) {

        // GET /api/password
        $method === 'GET'  && $uri === '/api/password'
            => $controller->generate(),

        // POST /api/passwords
        $method === 'POST' && $uri === '/api/passwords'
            => $controller->generateMultiple(),

        // POST /api/password/validate
        $method === 'POST' && $uri === '/api/password/validate'
            => $controller->validatePassword(),

        // 404 
        default => Response::error(
            "Ruta no encontrada: [{$method}] {$uri}",
            404
        ),
    };
} catch (Throwable $e) {
    // 500
    Response::error('Error interno del servidor: ' . $e->getMessage(), 500);
}