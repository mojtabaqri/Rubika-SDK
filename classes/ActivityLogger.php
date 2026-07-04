<?php

/**
 * سرویس ثبت فعالیت‌های حساس
 * لاگ تمام کنش‌های امنیتی اهم
 * @package VambanBot\Logger
 */
class ActivityLogger
{
    /**
     * ثبت یک فعالیت
     * 
     * @param string $action عنوان کنش
     * @param array $metadata داده‌های اضافی
     * @param string $severity سطح اهمیت (info, medium, high)
     * @param int|null $userId شناسه کاربر
     * @param int|null $adminId شناسه ادمین
     */
    public static function log(
        string $action,
        array $metadata = [],
        string $severity = 'info',
        ?int $userId = null,
        ?int $adminId = null
    ): void {
        try {
            $db = Database::get();
            
            $ipAddress = self::getClientIp();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $stmt = $db->prepare('
                INSERT INTO activity_logs (
                    user_id, admin_id, action, description, ip_address, user_agent, metadata, severity
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ');
            
            $metadataJson = json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            
            $stmt->execute([
                $userId,
                $adminId,
                $action,
                self::getActionDescription($action),
                $ipAddress,
                $userAgent,
                $metadataJson,
                $severity,
            ]);
            
            // لاگ کنسول برای فعالیت‌های بحرانی
            if ($severity === 'high') {
                error_log("[🔒 SECURITY] {$action} | IP: {$ipAddress} | User: {$userId} | Data: {$metadataJson}");
            }
        } catch (Exception $e) {
            error_log('خطا در ثبت فعالیت: ' . $e->getMessage());
        }
    }
    
    /**
     * دریافت توضیح فعالیت
     * 
     * @param string $action
     * @return string
     */
    private static function getActionDescription(string $action): string
    {
        $descriptions = [
            'admin_login_success' => 'ورود موفق ادمین',
            'admin_login_failed' => 'ناموفق ورود ادمین',
            'admin_login_blocked' => 'مسدود شدن ورود ادمین',
            'user_login' => 'ورود کاربر',
            'user_logout' => 'خروج کاربر',
            'kyc_submitted' => 'ارسال KYC',
            'kyc_approved' => 'تایید KYC',
            'kyc_rejected' => 'رد کردن KYC',
            'escrow_created' => 'ایجاد واسطه‌گری',
            'escrow_released' => 'آزادسازی وجه',
            'escrow_disputed' => 'اختلاف در معامله',
            'payment_completed' => 'تکمیل پرداخت',
            'admin_created' => 'ایجاد ادمین جدید',
            'admin_password_changed' => 'تغییر رمز عبور ادمین',
            'user_blocked' => 'مسدود شدن کاربر',
            'suspicious_activity' => 'فعالیت مشکوک',
        ];
        
        return $descriptions[$action] ?? $action;
    }
    
    /**
     * دریافت IP کلاینت
     * 
     * @return string
     */
    private static function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
    }
    
    /**
     * دریافت لاگ‌های فعالیت
     * 
     * @param int|null $userId شناسه کاربر
     * @param int $limit تعداد ثبت‌ها
     * @return array
     */
    public static function getLogs(?int $userId = null, int $limit = 100): array
    {
        try {
            $db = Database::get();
            
            if ($userId) {
                $stmt = $db->prepare('SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT ?');
                $stmt->execute([$userId, $limit]);
            } else {
                $stmt = $db->prepare('SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT ?');
                $stmt->execute([$limit]);
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * دریافت لاگ‌های فعالیت بحرانی
     * 
     * @param int $limit
     * @return array
     */
    public static function getCriticalLogs(int $limit = 50): array
    {
        try {
            $db = Database::get();
            $stmt = $db->prepare('SELECT * FROM activity_logs WHERE severity = "high" ORDER BY created_at DESC LIMIT ?');
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * پاکسازی لاگ‌های قدیمی (بیش از 30 روز)
     * 
     * @return bool
     */
    public static function cleanOldLogs(): bool
    {
        try {
            $db = Database::get();
            $stmt = $db->prepare('DELETE FROM activity_logs WHERE created_at < datetime("now", "-30 days")');
            return $stmt->execute();
        } catch (Exception $e) {
            error_log('خطا در پاکسازی لاگ‌های قدیمی: ' . $e->getMessage());
            return false;
        }
    }
}
