<?php
declare(strict_types=1);

class RateLimiter
{
    public function __construct(private PDO $pdo) {}

    public function check(string $ip, int $limit, int $windowSeconds): void
    {
        $this->pdo->exec("
            DELETE FROM rate_limits
            WHERE window_start < DATE_SUB(NOW(), INTERVAL $windowSeconds SECOND)
        ");

        $stmt = $this->pdo->prepare("
            INSERT INTO rate_limits (ip, requests, window_start)
            VALUES (:ip, 1, NOW())
            ON DUPLICATE KEY UPDATE
                requests = IF(
                    window_start < DATE_SUB(NOW(), INTERVAL $windowSeconds SECOND),
                    1,
                    requests + 1
                ),
                window_start = IF(
                    window_start < DATE_SUB(NOW(), INTERVAL $windowSeconds SECOND),
                    NOW(),
                    window_start
                )
        ");
        $stmt->execute([':ip' => $ip]);

        $row = $this->pdo->query(
            "SELECT requests FROM rate_limits WHERE ip = " . $this->pdo->quote($ip)
        )->fetch();

        if ($row && (int)$row['requests'] > $limit) {
            throw new RateLimitException();
        }
    }
}
