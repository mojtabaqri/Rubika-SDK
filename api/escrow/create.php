<?php
/**
 * API Endpoint: ایجاد معاملهٔ جدید (Escrow)
 * شیوه: POST
 * 
 * Headers:
 * Authorization: Bearer <access_token>
 * Content-Type: application/json
 * 
 * Body:
 * {
 *   "seller_id": 2,
 *   "amount": 100000,
 *   "description": "خرید محصول XYZ"
 * }
 * 
 * در صورت موفق:
 * {
 *   "success": true,
 *   "data": {
 *     "id": 1,
 *     "order_id": "ORD-ABC123-1720000000",
 *     "status": "pending",
 *     "total_amount": 101000
 *   }
 * }
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../app/Database.php';
require_once __DIR__ . '/../../classes/AuthService.php';
require_once __DIR__ . '/../../classes/KYCService.php';
require_once __DIR__ . '/../../classes/EscrowService.php';
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
    $buyerId = (int)$decoded->sub;
    
    // بررسی KYC
    APIMiddleware::requireKYC($buyerId);
    
    // دریافت داده‌های JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('داده‌های نامعتبر', 400);
    }
    
    $sellerId = (int)($input['seller_id'] ?? 0);
    $amount = (float)($input['amount'] ?? 0);
    $description = trim($input['description'] ?? '');
    
    // اعتبار‌سنجی
    if ($sellerId <= 0) {
        throw new Exception('شناسه‌ی فروشنده نامعتبر است.', 400);
    }
    
    if ($amount <= 0) {
        throw new Exception('مبلغ باید بزرگتر از صفر باشد.', 400);
    }
    
    if ($buyerId === $sellerId) {
        throw new Exception('نمی‌توانید برای خود معامله ایجاد کنید.', 400);
    }
    
    // ایجاد Escrow
    $result = EscrowService::createEscrow($buyerId, $sellerId, $amount, $description);
    
    if (!$result) {
        APIMiddleware::errorResponse('خطا در ایجاد معامله.', 500);
    } else {
        APIMiddleware::jsonResponse(true, $result);
    }
    
} catch (Exception $e) {
    APIMiddleware::errorResponse($e->getMessage(), $e->getCode() ?: 400);
}
