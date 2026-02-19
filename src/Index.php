<?php

$host = "db";
$dbname = getenv("MYSQL_DATABASE");
$user = getenv("MYSQL_USER");
$pass = getenv("MYSQL_PASSWORD");

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conexión exitosa a MySQL con Docker";
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
}
