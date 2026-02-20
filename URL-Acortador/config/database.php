<?php
// config/database.php

define('DB_HOST', 'localhost');
define('DB_NAME', 'url_shortener');
define('DB_USER', 'root');        // Cambia por tu usuario
define('DB_PASS', 'tu_password'); // Cambia por tu contraseña
define('DB_CHARSET', 'utf8mb4');

// Longitud del código corto (mínimo 5 según el examen)
define('CODE_LENGTH', 6);

// Rate limiting: máximo de peticiones por IP en la ventana de tiempo
define('RATE_LIMIT_MAX', 60);        // 60 peticiones
define('RATE_LIMIT_WINDOW', 3600);   // por hora (en segundos)

// Dominio base para construir URLs cortas
define('BASE_URL', 'http://localhost/url-shortener/public');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            jsonResponse(['error' => 'Error de conexión a la base de datos'], 500);
            exit;
        }
    }
    return $pdo;
}
