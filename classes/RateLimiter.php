<?php

class RateLimiter
{
    public static function allow(string $key, int $limit = 10, int $seconds = 60): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $sessionKey = 'rate_limiter_' . md5($key);
        $bucket = $_SESSION[$sessionKey] ?? ['count' => 0, 'expires_at' => time() + $seconds];

        if (time() > $bucket['expires_at']) {
            $bucket = ['count' => 0, 'expires_at' => time() + $seconds];
        }

        if ($bucket['count'] >= $limit) {
            $_SESSION[$sessionKey] = $bucket;
            return false;
        }

        $bucket['count'] += 1;
        $_SESSION[$sessionKey] = $bucket;
        return true;
    }
}
