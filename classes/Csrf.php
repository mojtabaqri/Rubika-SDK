<?php

class Csrf
{
    private const TOKEN_KEY = '_csrf_token';

    public static function initialize(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION[self::TOKEN_KEY])) {
            $_SESSION[self::TOKEN_KEY] = bin2hex(random_bytes(32));
        }
    }

    public static function getToken(): string
    {
        self::initialize();
        return $_SESSION[self::TOKEN_KEY];
    }

    public static function validate(?string $token): bool
    {
        self::initialize();
        return is_string($token) && hash_equals($_SESSION[self::TOKEN_KEY] ?? '', $token);
    }
}
