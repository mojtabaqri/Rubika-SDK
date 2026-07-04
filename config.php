<?php

if (!function_exists('loadEnvFile')) {
    function loadEnvFile(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }

            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $name = trim($parts[0]);
            $value = trim($parts[1]);
            $value = trim($value, "'\"");

            if ($name === '') {
                continue;
            }

            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

loadEnvFile(__DIR__ . '/.env');

if (!defined('BOT_TOKEN')) {
    define('BOT_TOKEN', getenv('BOT_TOKEN') ?: '');
}
if (!defined('ADMIN_CHAT_ID')) {
    define('ADMIN_CHAT_ID', getenv('CHAT_ID') ?: '');
}
if (!defined('ADMIN_USER')) {
    define('ADMIN_USER', getenv('ADMIN_USER') ?: 'admin');
}
if (!defined('ADMIN_PASS')) {
    define('ADMIN_PASS', getenv('ADMIN_PASS') ?: 'ChangeMe123');
}
if (!defined('APP_TITLE')) {
    define('APP_TITLE', getenv('APP_TITLE') ?: 'Vamban Loan Marketplace');
}

if (!defined('APP_BASE_URL')) {
    define('APP_BASE_URL', getenv('APP_BASE_URL') ?: 'http://localhost');
}

if (!defined('ZARINPAL_MERCHANT_ID')) {
    define('ZARINPAL_MERCHANT_ID', getenv('ZARINPAL_MERCHANT_ID') ?: '');
}

if (!defined('ZARINPAL_CALLBACK_URL')) {
    define('ZARINPAL_CALLBACK_URL', getenv('ZARINPAL_CALLBACK_URL') ?: APP_BASE_URL . '/api/payment-callback.php');
}

if (!defined('ZARINPAL_SANDBOX')) {
    define('ZARINPAL_SANDBOX', filter_var(getenv('ZARINPAL_SANDBOX') ?: 'false', FILTER_VALIDATE_BOOLEAN));
}

if (!defined('DB_FILE')) {
    define('DB_FILE', __DIR__ . '/data/database.sqlite');
}

if (!is_dir(dirname(DB_FILE))) {
    mkdir(dirname(DB_FILE), 0777, true);
}

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'cookie_samesite' => 'Lax',
    ]);
}

require_once __DIR__ . '/app/Database.php';
require_once __DIR__ . '/app/Models.php';
require_once __DIR__ . '/classes/Response.php';
require_once __DIR__ . '/classes/Validator.php';
require_once __DIR__ . '/classes/Logger.php';
require_once __DIR__ . '/classes/ActivityLogger.php';
require_once __DIR__ . '/classes/Csrf.php';
require_once __DIR__ . '/classes/RateLimiter.php';
require_once __DIR__ . '/classes/Transaction.php';
require_once __DIR__ . '/classes/ZarinpalGateway.php';
require_once __DIR__ . '/classes/EscrowService.php';
require_once __DIR__ . '/classes/UserVerification.php';
require_once __DIR__ . '/classes/KYCService.php';
require_once __DIR__ . '/classes/AdminKYCController.php';

Database::initialize(DB_FILE);
Database::migrate();
Csrf::initialize();
