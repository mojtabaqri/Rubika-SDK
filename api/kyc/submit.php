<?php
/**
 * API Endpoint: ارسال درخواست احراز هویت (KYC)
 * شیوه: POST (multipart/form-data)
 * 
 * Headers:
 * Authorization: Bearer <access_token>
 * 
 * Fields:
 * - full_name: نام و نام خانوادگی
 * - address: آدرس کامل
 * - postal_code: کد پستی
 * - phone: شماره تلفن
 * - national_card_back_image: تصویر پشت کارت ملی (file)
 * 
 * در صورت موفق:
 * {
 *   "success": true,
 *   "data": {
 *     "id": 1,
 *     "status": "pending",
 *     "message": "درخواست احراز هویت برای بررسی قرار گرفت."
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
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('شیوه درخواست پشتیبانی نشده است.', 405);
    }
    
    // تحقق از توکن
    $decoded = APIMiddleware::verifyToken();
    $userId = (int)$decoded->sub;
    
    // بررسی فیلد‌های الزامی
    $data = [
        'full_name' => $_POST['full_name'] ?? '',
        'address' => $_POST['address'] ?? '',
        'postal_code' => $_POST['postal_code'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'national_id' => $_POST['national_id'] ?? '',
    ];
    
    // پردازش فایل‌ها
    $files = [];
    if (!empty($_FILES['national_card_back_image'])) {
        $files['id_card_back'] = $_FILES['national_card_back_image'];
    }
    
    // ارسال KYC
    $result = KYCService::submitKYC($userId, $data, $files);
    
    if ($result === false) {
        APIMiddleware::errorResponse('خطا در ارسال درخواست احراز هویت.', 500);
    } elseif (is_array($result) && isset($result['error'])) {
        APIMiddleware::errorResponse($result['error'], 409);
    } elseif (is_array($result) && isset($result['message'])) {
        // موفق
        APIMiddleware::jsonResponse(true, $result, $result['message']);
    } else {
        // خطاهای اعتبار‌سنجی
        APIMiddleware::errorResponse('داده‌های نامعتبر', 400, $result);
    }
    
} catch (Exception $e) {
    APIMiddleware::errorResponse($e->getMessage(), $e->getCode() ?: 400);
}
