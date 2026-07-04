<?php
/**
 * Middleware برای احراز هویت JWT و KYC
 * استفاده: require_once 'api/Middleware.php'; verifyToken();
 * @package VambanBot\API
 */

class APIMiddleware
{
    /**
     * تحقق از Access Token و دریافت اطلاعات کاربر
     * 
     * @return object اطلاعات توکن
     * @throws Exception اگر توکن نامعتبر باشد
     */
    public static function verifyToken(): object
    {
        $accessToken = AuthService::getAccessTokenFromHeader();
        
        if (!$accessToken) {
            http_response_code(401);
            throw new Exception('توکن درخواست نشده است.', 401);
        }
        
        $decoded = AuthService::verifyAccessToken($accessToken);
        
        if (!$decoded) {
            http_response_code(401);
            throw new Exception('توکن نامعتبر یا منقضی است.', 401);
        }
        
        return $decoded;
    }
    
    /**
     * بررسی KYC کاربر
     * 
     * @param int $userId شناسه کاربر
     * @throws Exception اگر کاربر احراز هویت نشده باشد
     */
    public static function requireKYC(int $userId): void
    {
        if (!KYCService::isUserVerified($userId)) {
            http_response_code(403);
            throw new Exception('ابتدا باید احراز هویت کنید.', 403);
        }
    }
    
    /**
     * بررسی اینکه کاربر یک ادمین باشد
     * 
     * @param object $decoded اطلاعات توکن
     * @throws Exception اگر کاربر ادمین نباشد
     */
    public static function requireAdmin(object $decoded): void
    {
        if (!isset($decoded->type) || $decoded->type !== 'admin') {
            http_response_code(403);
            throw new Exception('این عملیات صرفا برای ادمین‌ها است.', 403);
        }
    }
    
    /**
     * پاسخ JSON استاندارد
     * 
     * @param bool $success موفقیت
     * @param array $data داده‌ها
     * @param string $message پیام
     * @param int $code کد HTTP
     */
    public static function jsonResponse(bool $success, array $data = [], string $message = '', int $code = 200): void
    {
        http_response_code($code);
        
        $response = [
            'success' => $success,
        ];
        
        if (!empty($data)) {
            $response['data'] = $data;
        }
        
        if (!empty($message)) {
            $response['message'] = $message;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    
    /**
     * پاسخ خطا
     * 
     * @param string $error پیام خطا
     * @param int $code کد HTTP
     * @param array $errors لیست خطاهای فیلد
     */
    public static function errorResponse(string $error, int $code = 400, array $errors = []): void
    {
        http_response_code($code);
        
        $response = [
            'success' => false,
            'error' => $error,
        ];
        
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    
    /**
     * تنظیم Headers امنتی CORS
     */
    public static function setSecurityHeaders(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: ' . (getenv('APP_ENV') === 'development' ? '*' : getenv('ALLOWED_ORIGIN') ?: 'https://domain.com'));
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
    
    /**
     * مدیریت درخواست OPTIONS (Preflight)
     */
    public static function handleCORS(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
    
    /**
     * اعتبار‌سنجی Content-Type
     * 
     * @param array $allowed انواع مجاز (مثلاً: ['application/json'])
     * @throws Exception اگر Content-Type نامعتبر باشد
     */
    public static function validateContentType(array $allowed = ['application/json']): void
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $contentType = explode(';', $contentType)[0]; // حذف charset
        
        if (!in_array($contentType, $allowed)) {
            http_response_code(415);
            throw new Exception('Content-Type پشتیبانی نشده است.', 415);
        }
    }
    
    /**
     * محدود کردن سرعت درخواست (Rate Limiting - ساده)
     * 
     * @param string $identifier شناسه‌ی منحصربه‌فرد (IP یا user ID)
     * @param int $maxRequests حداکثر درخواست‌ها
     * @param int $windowSeconds پنجره‌ی زمانی
     * @throws Exception اگر حد تجاوز شود
     */
    public static function rateLimit(string $identifier, int $maxRequests = 100, int $windowSeconds = 60): void
    {
        $cacheFile = __DIR__ . '/../../data/cache/' . md5($identifier) . '.txt';
        
        if (!is_dir(dirname($cacheFile))) {
            mkdir(dirname($cacheFile), 0755, true);
        }
        
        $now = time();
        $requests = [];
        
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true) ?? ['requests' => []];
            $requests = array_filter($data['requests'] ?? [], fn($t) => $now - $t < $windowSeconds);
        }
        
        if (count($requests) >= $maxRequests) {
            http_response_code(429);
            throw new Exception('تعداد درخواست‌ها بیش از حد است. لطفا بعدا دوباره سعی کنید.', 429);
        }
        
        $requests[] = $now;
        file_put_contents($cacheFile, json_encode(['requests' => $requests]));
    }
}
