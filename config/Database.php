<?php

class Database {
    private static ?PDO $instance = null;

    private static function getConfig(): array {
        return [
            'host'    => getenv('DB_HOST') ?: 'db',
            'dbname'  => getenv('MYSQL_DATABASE'),
            'user'    => getenv('MYSQL_USER'),
            'pass'    => getenv('MYSQL_PASSWORD'),
            'charset' => 'utf8mb4',
        ];
    }

    public static function getInstance(): PDO {
        if (self::$instance === null) {

            $config = self::getConfig();

            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                $config['host'],
                $config['dbname'],
                $config['charset']
            );

            self::$instance = new PDO(
                $dsn,
                $config['user'],
                $config['pass'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        }

        return self::$instance;
    }

    private function __construct() {}
}
