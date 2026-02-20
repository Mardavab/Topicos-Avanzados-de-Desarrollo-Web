<?php
declare(strict_types=1);

function getConnection(): PDO
{
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $host = $_ENV['DB_HOST'] ?? 'db';
    $port = $_ENV['DB_PORT'] ?? '3306';
    $name = $_ENV['DB_NAME'] ?? 'urlshortener';
    $user = $_ENV['DB_USER'] ?? 'shortener';
    $pass = $_ENV['DB_PASS'] ?? 'secret';

    $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    return $pdo;
}
