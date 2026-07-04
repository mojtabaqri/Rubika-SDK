<?php
/**
 * API Endpoint: دریافت اطلاعات کاربر جاری
 * شیوه: GET
 * 
 * Headers:
 * Authorization: Bearer <access_token>
 * 
 * در صورت موفق:
 * {
 *   "success": true,
 *   "data": {
 *     "id": 1,
 *     "username": "...",
 *     "type": "admin|user",
 *     "kyc_verified": true|false
 *   }
 * }
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../app/Database.php';
require_once __DIR__ . '/../../classes/AuthService.php';
require_once __DIR__ . '/../../classes/KYCService.php';
require_once __DIR__ . '/../../vendor/autoload.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// تنظیم دیتابیس
Database::initialize(__DIR__ . '/../../data/vamban.db');

// بررسی متد درخواست
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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

try {
    $db = Database::get();
    $userId = (int)$decoded->sub;
    $userType = $decoded->type ?? 'user';
    
    if ($userType === 'admin') {
        $stmt = $db->prepare('SELECT id, username, full_name, role FROM admins WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'ادمین پیدا نشد.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'full_name' => $user['full_name'],
                'role' => $user['role'],
                'type' => 'admin'
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        $stmt = $db->prepare('SELECT id, rubika_id, phone, name, lastname, is_verified FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'کاربر پیدا نشد.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $kycStatus = KYCService::getKYCStatus($userId);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => [
                'id' => $user['id'],
                'rubika_id' => $user['rubika_id'],
                'phone' => $user['phone'],
                'name' => $user['name'],
                'lastname' => $user['lastname'],
                'is_verified' => (bool)$user['is_verified'],
                'kyc_status' => $kycStatus ? $kycStatus['status'] : null,
                'type' => 'user'
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'خطای سرور'
    ], JSON_UNESCAPED_UNICODE);
}
