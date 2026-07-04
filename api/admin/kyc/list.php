<?php
/**
 * API Endpoint: لیست درخواست‌های احراز هویت (برای ادمین)
 * شیوه: GET
 * 
 * Headers:
 * Authorization: Bearer <admin_access_token>
 * 
 * Query Parameters:
 * - status: pending|approved|rejected (پیش‌فرض: pending)
 * - page: شماره‌ی صفحه (پیش‌فرض: 1)
 * - limit: تعداد ثبت‌ها در هر صفحه (پیش‌فرض: 20)
 * 
 * در صورت موفق:
 * {
 *   "success": true,
 *   "data": [
 *     {
 *       "id": 1,
 *       "user_id": 5,
 *       "full_name": "...",
 *       "submitted_at": "...",
 *       "status": "pending"
 *     }
 *   ],
 *   "pagination": {
 *     "page": 1,
 *     "limit": 20,
 *     "total": 45
 *   }
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
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('شیوه درخواست پشتیبانی نشده است.', 405);
    }
    
    // تحقق از توکن
    $decoded = APIMiddleware::verifyToken();
    APIMiddleware::requireAdmin($decoded);
    
    // دریافت پارامترها
    $status = $_GET['status'] ?? 'pending';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
    
    // اعتبار‌سنجی status
    if (!in_array($status, ['pending', 'approved', 'rejected'])) {
        $status = 'pending';
    }
    
    // محاسباتِ offset
    $offset = ($page - 1) * $limit;
    
    // دریافت لیست
    $data = KYCService::getPendingRequests($status, $limit, $offset);
    
    // دریافت تعداد کل
    $db = Database::get();
    $countStmt = $db->prepare('SELECT COUNT(*) as total FROM user_verifications WHERE status = ?');
    $countStmt->execute([$status]);
    $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
    $total = $countResult['total'] ?? 0;
    
    APIMiddleware::jsonResponse(true, $data, '', 200);
    
} catch (Exception $e) {
    APIMiddleware::errorResponse($e->getMessage(), $e->getCode() ?: 400);
}
