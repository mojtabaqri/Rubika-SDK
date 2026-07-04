<?php
/**
 * API Endpoint: تأیید پرداخت (Callback از درگاه)
 * شیوه: POST
 * 
 * Body:
 * {
 *   "escrow_id": 1,
 *   "authority": "AUTH-ABC123...",
 *   "ref_id": "123456789"
 * }
 * 
 * در صورت موفق:
 * {
 *   "success": true,
 *   "message": "پرداخت با موفقیت تأیید شد."
 * }
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../app/Database.php';
require_once __DIR__ . '/../../classes/EscrowService.php';
require_once __DIR__ . '/../../classes/ActivityLogger.php';
require_once __DIR__ . '/../../api/Middleware.php';
require_once __DIR__ . '/../../vendor/autoload.php';

APIMiddleware::setSecurityHeaders();

// تنظیم دیتابیس
Database::initialize(__DIR__ . '/../../data/vamban.db');

try {
    // بررسی متد درخواست
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('شیوه درخواست پشتیبانی نشده است.', 405);
    }
    
    // دریافت داده‌های JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('داده‌های نامعتبر', 400);
    }
    
    $escrowId = (int)($input['escrow_id'] ?? 0);
    $authority = trim($input['authority'] ?? '');
    $refId = trim($input['ref_id'] ?? '');
    
    if ($escrowId <= 0 || empty($authority)) {
        throw new Exception('پارامترهای الزامی ناقص هستند.', 400);
    }
    
    // تأیید پرداخت
    $result = EscrowService::verifyPayment($escrowId, $authority, $refId);
    
    if (!$result) {
        APIMiddleware::errorResponse('خطا در تأیید پرداخت.', 500);
    } else {
        APIMiddleware::jsonResponse(true, [], 'پرداخت با موفقیت تأیید شد.');
    }
    
} catch (Exception $e) {
    // نوشتن خطا در لاگ برای اشکال‌زدایی
    error_log('خطا در callback پرداخت: ' . $e->getMessage());
    APIMiddleware::errorResponse($e->getMessage(), $e->getCode() ?: 400);
}
