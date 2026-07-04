<?php
/**
 * API Endpoint: خروج (لاگ‌آوت)
 * شیوه: POST
 * 
 * Headers:
 * Authorization: Bearer <access_token>
 * 
 * Body (اختیاری):
 * {
 *   "refresh_token": "..."
 * }
 * 
 * در صورت موفق:
 * {
 *   "success": true,
 *   "message": "با موفقیت خارج شدید."
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

// بررسی متد درخواست
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'شیوه درخواست پشتیبانی نشده است.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// دریافت Access Token
$accessToken = AuthService::getAccessTokenFromHeader();

if (!$accessToken) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'توکن درخواست نشده است.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// تحقق از Access Token
$decoded = AuthService::verifyAccessToken($accessToken);

if (!$decoded) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'توکن نامعتبر یا منقضی است.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// دریافت Refresh Token
$input = json_decode(file_get_contents('php://input'), true);
$refreshToken = $input['refresh_token'] ?? AuthService::getRefreshTokenFromCookie();

// اجرای لاگ‌آوت
AuthService::logout($refreshToken ?? '', (int)$decoded->sub);

http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'با موفقیت خارج شدید.'
], JSON_UNESCAPED_UNICODE);
