<?php
/**
 * API Endpoint: شروع فرآیند پرداخت
 * شیوه: POST
 * 
 * Headers:
 * Authorization: Bearer <access_token>
 * Content-Type: application/json
 * 
 * Body:
 * {
 *   "escrow_id": 1
 * }
 * 
 * در صورت موفق:
 * {
 *   "success": true,
 *   "data": {
 *     "authority": "AUTH-ABC123...",
 *     "amount": 100000,
 *     "fee": 1000,
 *     "total_amount": 101000,
 *     "payment_url": "https://payment.zarinpal.com/pg/StartPay/AUTH-ABC123..."
 *   }
 * }
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../app/Database.php';
require_once __DIR__ . '/../../classes/AuthService.php';
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
    
    // دریافت داده‌های JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || empty($input['escrow_id'])) {
        throw new Exception('شناسه‌ی معامله الزامی است.', 400);
    }
    
    $escrowId = (int)$input['escrow_id'];
    
    // دریافت معامله
    $escrow = EscrowService::getEscrow($escrowId);
    
    if (!$escrow) {
        throw new Exception('معامله پیدا نشد.', 404);
    }
    
    if ((int)$escrow['buyer_id'] !== $buyerId) {
        throw new Exception('شما اجازه پرداخت این معامله را ندارید.', 403);
    }
    
    // شروع پرداخت
    $result = EscrowService::initializePayment($escrowId);
    
    if (!$result) {
        APIMiddleware::errorResponse('خطا در شروع فرآیند پرداخت.', 500);
    } else {
        $result['payment_url'] = 'https://payment.zarinpal.com/pg/StartPay/' . $result['authority'];
        APIMiddleware::jsonResponse(true, $result);
    }
    
} catch (Exception $e) {
    APIMiddleware::errorResponse($e->getMessage(), $e->getCode() ?: 400);
}
