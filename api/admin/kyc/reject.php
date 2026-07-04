<?php
/**
 * API Endpoint: رد کردن درخواست احراز هویت (برای ادمین)
 * شیوه: POST
 * 
 * Headers:
 * Authorization: Bearer <admin_access_token>
 * Content-Type: application/json
 * 
 * Body:
 * {
 *   "verification_id": 1,
 *   "reason": "اطلاعات ناقص است"
 * }
 * 
 * در صورت موفق:
 * {
 *   "success": true,
 *   "message": "درخواست رد شد."
 * }
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../app/Database.php';
require_once __DIR__ . '/../../classes/AuthService.php';
require_once __DIR__ . '/../../classes/KYCService.php';
require_once __DIR__ . '/../../api/Middleware.php';
require_once __DIR__ . '/../../vendor/autoload.php';

APIMiddleware::setSecurityHeaders();
APIMiddleware::handleCORS();

// تنظیم دیتابیس
Database::initialize(__DIR__ . '/../../data/vamban.db');

try {
    // بررسی متد درخواست
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('شیوه درخواست پشتیبانی نشده است.', 405);
    }
    
    // تحقق از توکن
    $decoded = APIMiddleware::verifyToken();
    APIMiddleware::requireAdmin($decoded);
    
    $adminId = (int)$decoded->sub;
    
    // دریافت داده‌های JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || empty($input['verification_id'])) {
        throw new Exception('شناسه‌ی درخواست الزامی است.', 400);
    }
    
    $verificationId = (int)$input['verification_id'];
    $reason = trim($input['reason'] ?? '');
    
    if (empty($reason)) {
        throw new Exception('دلیل رد کردن الزامی است.', 400);
    }
    
    // رد KYC
    $result = KYCService::rejectKYC($verificationId, $adminId, $reason);
    
    if (!$result) {
        APIMiddleware::errorResponse('خطا در رد درخواست.', 500);
    } else {
        APIMiddleware::jsonResponse(true, [], 'درخواست احراز هویت رد شد.');
    }
    
} catch (Exception $e) {
    APIMiddleware::errorResponse($e->getMessage(), $e->getCode() ?: 400);
}
