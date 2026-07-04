<?php
/**
 * API Endpoint: لاگین (ورود)
 * شیوه: POST
 * 
 * Body:
 * {
 *   "username": "admin_username",
 *   "password": "admin_password",
 *   "is_admin": true|false
 * }
 * 
 * در صورت موفق:
 * {
 *   "success": true,
 *   "data": {
 *     "user": {...},
 *     "access_token": "...",
 *     "refresh_token": "...",
 *     "expires_in": 900
 *   }
 * }
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../app/Database.php';
require_once __DIR__ . '/../../classes/AuthService.php';
require_once __DIR__ . '/../../classes/ActivityLogger.php';
require_once __DIR__ . '/../../vendor/autoload.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// تنظیم دیتابیس
Database::initialize(__DIR__ . '/../../data/vamban.db');
Database::migrate();

// بررسی متد درخواست
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'شیوه درخواست پشتیبانی نشده است.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// دریافت داده‌های JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'داده‌های نامعتبر'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$username = trim($input['username'] ?? '');
$password = trim($input['password'] ?? '');
$isAdmin = (bool)($input['is_admin'] ?? false);

if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'نام کاربری و رمز عبور الزامی هستند.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// اجرای لاگین
$result = AuthService::login($username, $password, $isAdmin);

if (!$result) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'نام کاربری یا رمز عبور نادرست است.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// تنظیم Refresh Token در Cookie
if (!empty($result['refresh_token'])) {
    AuthService::setRefreshTokenCookie($result['refresh_token']);
}

http_response_code(200);
echo json_encode([
    'success' => true,
    'data' => $result
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
