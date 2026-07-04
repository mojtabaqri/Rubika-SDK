<?php
/**
 * Bootstrap فایل اولیه
 * این فایل باید در تمام صفحات درخواست شود
 * require_once '/path/to/init.php';
 */

error_reporting(E_ALL);
ini_set('display_errors', getenv('APP_DEBUG') === 'true' ? 1 : 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/data/error.log');

// تنظیم منطقه‌ی زمانی (تهران)
date_default_timezone_set('Asia/Tehran');

// شروع جلسه
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// بارگذاری تنظیمات
require_once __DIR__ . '/config.php';

// بارگذاری کلاس ها
require_once __DIR__ . '/app/Database.php';
require_once __DIR__ . '/app/Models.php';

// بارگذاری سرویس‌ها
require_once __DIR__ . '/classes/AuthService.php';
require_once __DIR__ . '/classes/ActivityLogger.php';
require_once __DIR__ . '/classes/KYCService.php';
require_once __DIR__ . '/classes/EscrowService.php';
require_once __DIR__ . '/classes/Logger.php';
require_once __DIR__ . '/classes/Validator.php';
require_once __DIR__ . '/classes/Response.php';
require_once __DIR__ . '/classes/RateLimiter.php';

// بارگذاری vendor autoload
require_once __DIR__ . '/vendor/autoload.php';

// تنظیم دیتابیس
try {
    Database::initialize(__DIR__ . '/data/vamban.db');
    Database::migrate();
} catch (Exception $e) {
    error_log('خطا در اتصال به دیتابیس: ' . $e->getMessage());
}

// تابع کمکی برای redirect
if (!function_exists('redirect')) {
    function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}

// تابع کمکی برای دریافت URL پایه
if (!function_exists('baseUrl')) {
    function baseUrl(): string
    {
        $scheme = $_SERVER['REQUEST_SCHEME'] ?? 'https';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return "{$scheme}://{$host}";
    }
}

// تابع کمکی برای کد کردن اطلاعات HTML
if (!function_exists('e')) {
    function e(?string $text): string
    {
        return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// تابع کمکی برای ترجمه (اختیاری - توسط شما پیاده‌سازی شود)
if (!function_exists('trans')) {
    function trans(string $key, array $replace = []): string
    {
        $translations = [
            'nav.dashboard' => 'داشبورد',
            'nav.transactions' => 'معاملات',
            'nav.profile' => 'پروفایل',
            'nav.kyc' => 'احراز هویت',
            'nav.logout' => 'خروج',
        ];
        
        $text = $translations[$key] ?? $key;
        
        foreach ($replace as $search => $value) {
            $text = str_replace(":{$search}", $value, $text);
        }
        
        return $text;
    }
}

// تابع کمکی برای دریافت کاربر جاری
if (!function_exists('currentUser')) {
    function currentUser(): ?array
    {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        
        try {
            $db = Database::get();
            $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Exception $e) {
            return null;
        }
    }
}

// تابع کمکی برای بررسی امنیت KYC
if (!function_exists('requireKYC')) {
    function requireKYC(): void
    {
        $user = currentUser();
        
        if (!$user || !KYCService::isUserVerified($user['id'])) {
            $_SESSION['redirect_after_kyc'] = $_SERVER['REQUEST_URI'] ?? '/';
            redirect('/kyc.php');
        }
    }
}

// تابع کمکی برای بررسی ورود
if (!function_exists('requireAuth')) {
    function requireAuth(): void
    {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';
            redirect('/auth.php');
        }
    }
}

// تابع کمکی برای بررسی ادمین
if (!function_exists('requireAdmin')) {
    function requireAdmin(): void
    {
        if (!isset($_SESSION['admin_id'])) {
            redirect('/admin/login.php');
        }
    }
}
