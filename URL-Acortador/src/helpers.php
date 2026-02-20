<?php
declare(strict_types=1);

class ApiException extends RuntimeException
{
    public function __construct(string $message, int $code = 400)
    {
        parent::__construct($message, $code);
    }
}

class RateLimitException extends ApiException
{
    public function __construct()
    {
        parent::__construct('Too many requests. Please slow down.', 429);
    }
}

function jsonError(int $status, string $message): never
{
    http_response_code($status);
    echo json_encode(['error' => ['status' => $status, 'message' => $message]]);
    exit;
}

function getClientIp(): string
{
    foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            return trim(explode(',', $_SERVER[$key])[0]);
        }
    }
    return '0.0.0.0';
}

function generateCode(int $length = 6): string
{
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $code  = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, 61)];
    }
    return $code;
}

function validateUrl(string $url): void
{
    if (strlen($url) > 2048) {
        throw new ApiException('URL too long (max 2048 chars)');
    }
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        throw new ApiException('Invalid URL format');
    }
    $scheme = parse_url($url, PHP_URL_SCHEME);
    if (!in_array($scheme, ['http', 'https'], true)) {
        throw new ApiException('Only http/https URLs are allowed');
    }
    $host    = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
    $ownHost = strtolower($_ENV['APP_HOST'] ?? 'localhost');
    if ($host === $ownHost) {
        throw new ApiException('Cannot shorten a URL from this service (loop prevention)', 400);
    }
}
