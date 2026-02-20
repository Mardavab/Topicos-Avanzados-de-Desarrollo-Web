<?php
declare(strict_types=1);

class UrlService
{
    private const MAX_RETRIES  = 5;
    private const CODE_LENGTH  = 6;

    public function __construct(private PDO $pdo) {}

    // ── Shorten ────────────────────────────────────────────────────────────
    public function shorten(
        mixed $originalUrl,
        mixed $expiresAt,
        mixed $maxUses,
        string $creatorIp
    ): array {
        if (empty($originalUrl) || !is_string($originalUrl)) {
            throw new ApiException('Field "url" is required');
        }

        $originalUrl = trim($originalUrl);
        validateUrl($originalUrl);

        // Validate expires_at
        $expiresAtValue = null;
        if ($expiresAt !== null) {
            $ts = strtotime((string)$expiresAt);
            if ($ts === false || $ts <= time()) {
                throw new ApiException('"expires_at" must be a future datetime (ISO 8601)');
            }
            $expiresAtValue = date('Y-m-d H:i:s', $ts);
        }

        // Validate max_uses
        $maxUsesValue = null;
        if ($maxUses !== null) {
            $maxUsesValue = (int)$maxUses;
            if ($maxUsesValue < 1) {
                throw new ApiException('"max_uses" must be a positive integer');
            }
        }

        // Generate unique short code
        $code = $this->uniqueCode();

        $stmt = $this->pdo->prepare("
            INSERT INTO urls (short_code, original_url, creator_ip, expires_at, max_uses)
            VALUES (:code, :url, :ip, :exp, :max)
        ");
        $stmt->execute([
            ':code' => $code,
            ':url'  => $originalUrl,
            ':ip'   => $creatorIp,
            ':exp'  => $expiresAtValue,
            ':max'  => $maxUsesValue,
        ]);

        $baseUrl = $_ENV['BASE_URL'] ?? 'http://localhost:8080';

        return [
            'short_code'   => $code,
            'short_url'    => "$baseUrl/$code",
            'original_url' => $originalUrl,
            'expires_at'   => $expiresAtValue,
            'max_uses'     => $maxUsesValue,
            'created_at'   => date('Y-m-d H:i:s'),
        ];
    }

    // ── Resolve & redirect ─────────────────────────────────────────────────
    public function resolve(string $code, string $visitorIp, string $userAgent): string
    {
        $this->validateCode($code);

        $stmt = $this->pdo->prepare("
            SELECT id, original_url, expires_at, max_uses, use_count
            FROM urls WHERE short_code = :code
        ");
        $stmt->execute([':code' => $code]);
        $row = $stmt->fetch();

        if (!$row) {
            throw new ApiException('Short URL not found', 404);
        }

        // Check expiry
        if ($row['expires_at'] && strtotime($row['expires_at']) < time()) {
            throw new ApiException('This short URL has expired', 410);
        }

        // Check max uses
        if ($row['max_uses'] !== null && (int)$row['use_count'] >= (int)$row['max_uses']) {
            throw new ApiException('This short URL has reached its usage limit', 410);
        }

        // Record visit & increment counter
        $this->pdo->prepare("
            INSERT INTO visits (url_id, visitor_ip, user_agent)
            VALUES (:id, :ip, :ua)
        ")->execute([
            ':id' => $row['id'],
            ':ip' => $visitorIp,
            ':ua' => substr($userAgent, 0, 512),
        ]);

        $this->pdo->prepare("
            UPDATE urls SET use_count = use_count + 1 WHERE id = :id
        ")->execute([':id' => $row['id']]);

        return $row['original_url'];
    }

    // ── Statistics ─────────────────────────────────────────────────────────
    public function stats(string $code): array
    {
        $this->validateCode($code);

        $stmt = $this->pdo->prepare("
            SELECT id, short_code, original_url, creator_ip,
                   expires_at, max_uses, use_count, created_at
            FROM urls WHERE short_code = :code
        ");
        $stmt->execute([':code' => $code]);
        $row = $stmt->fetch();

        if (!$row) {
            throw new ApiException('Short URL not found', 404);
        }

        // Visits per day (last 30 days)
        $daily = $this->pdo->prepare("
            SELECT DATE(visited_at) AS day, COUNT(*) AS visits
            FROM visits
            WHERE url_id = :id AND visited_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY day ORDER BY day DESC
        ");
        $daily->execute([':id' => $row['id']]);

        // Last 10 accesses
        $recent = $this->pdo->prepare("
            SELECT visited_at, visitor_ip, user_agent
            FROM visits
            WHERE url_id = :id
            ORDER BY visited_at DESC LIMIT 10
        ");
        $recent->execute([':id' => $row['id']]);

        $baseUrl = $_ENV['BASE_URL'] ?? 'http://localhost:8080';

        return [
            'short_code'    => $row['short_code'],
            'short_url'     => "$baseUrl/{$row['short_code']}",
            'original_url'  => $row['original_url'],
            'created_at'    => $row['created_at'],
            'expires_at'    => $row['expires_at'],
            'max_uses'      => $row['max_uses'],
            'total_visits'  => (int)$row['use_count'],
            'visits_by_day' => $daily->fetchAll(),
            'recent_visits' => $recent->fetchAll(),
        ];
    }

    // ── Helpers ────────────────────────────────────────────────────────────
    private function uniqueCode(): string
    {
        for ($i = 0; $i < self::MAX_RETRIES; $i++) {
            $code = generateCode(self::CODE_LENGTH);
            $stmt = $this->pdo->prepare("SELECT 1 FROM urls WHERE short_code = :c");
            $stmt->execute([':c' => $code]);
            if (!$stmt->fetch()) return $code;
        }
        // Fallback: longer code to guarantee uniqueness
        return generateCode(self::CODE_LENGTH + 2);
    }

    private function validateCode(string $code): void
    {
        if (!preg_match('/^[A-Za-z0-9]{5,20}$/', $code)) {
            throw new ApiException('Invalid short code format', 400);
        }
    }
}
