<?php
/**
 * API Endpoint: دریافت وضعیت احراز هویت (KYC)
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
 *     "status": "pending|approved|rejected",
 *     "full_name": "...",
 *     "submitted_at": "2026-07-04 12:00:00",
 *     "reviewed_at": null,
 *     "admin_notes": null
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
    $userId = (int)$decoded->sub;
    
    // دریافت وضعیت KYC
    $kycStatus = KYCService::getKYCStatus($userId);
    
    if (!$kycStatus) {
        APIMiddleware::jsonResponse(true, [
            'status' => null,
            'message' => 'درخواست احراز هویتی ثبت نشده است.'
        ]);
    } else {
        APIMiddleware::jsonResponse(true, $kycStatus);
    }
    
} catch (Exception $e) {
    APIMiddleware::errorResponse($e->getMessage(), $e->getCode() ?: 400);
}
