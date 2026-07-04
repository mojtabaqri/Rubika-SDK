<?php
/**
 * API Endpoint: تجدید Access Token
 * شیوه: POST
 * 
 * Headers:
 * Authorization: Bearer <access_token> (اختیاری)
 * Cookie: refresh_token=...
 * 
 * در صورت موفق:
 * {
 *   "success": true,
 *   "data": {
 *     "access_token": "...",
 *     "token_type": "Bearer",
 *     "expires_in": 900
 *   }
 * }
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../app/Database.php';
require_once __DIR__ . '/../../classes/AuthService.php';
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

// دریافت Refresh Token از Cookie یا Body
$refreshToken = AuthService::getRefreshTokenFromCookie();

if (!$refreshToken) {
    $input = json_decode(file_get_contents('php://input'), true);
    $refreshToken = $input['refresh_token'] ?? null;
}

if (!$refreshToken) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Refresh Token پیدا نشد.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// تجدید Access Token
$result = AuthService::refreshAccessToken($refreshToken);

if (!$result) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'تجدید توکن ناموفق بود.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(200);
echo json_encode([
    'success' => true,
    'data' => $result
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
