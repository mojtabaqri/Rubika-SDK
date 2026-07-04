<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * سرویس احراز هویت JWT
 * پشتیبانی کامل Access Token و Refresh Token
 * لاگین ادمین + کاربر عادی
 * ذخیره‌سازی توکن در HttpOnly Cookie
 * @package VambanBot\Auth
 */
class AuthService
{
    // ثابت‌های JWT
    private const JWT_SECRET = 'your-secret-key-change-this';
    private const ACCESS_TOKEN_EXPIRY = 900; // 15 دقیقه
    private const REFRESH_TOKEN_EXPIRY = 604800; // 7 روز
    private const ALGORITHM = 'HS256';
    
    // ثابت‌های کوکی
    private const REFRESH_COOKIE_NAME = 'refresh_token';
    private const ACCESS_COOKIE_NAME = 'access_token';
    
    // ثابت‌های امنیتی
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOGIN_ATTEMPT_WINDOW = 300; // 5 دقیقه
    
    /**
     * دریافت secret key
     */
    private static function getSecretKey(): string
    {
        $key = getenv('JWT_SECRET') ?: self::JWT_SECRET;
        if ($key === 'your-secret-key-change-this' || strlen($key) < 16) {
            error_log('⚠️ بخش Jwt مناسب نیست! لطفا JWT_SECRET را در .env تعیین کنید');
        }
        return $key;
    }
    
    /**
     * لاگین ادمین
     * 
     * @param string $username نام کاربری
     * @param string $password رمز عبور
     * @param string $ip آدرس IP
     * @return array|false مشخصات ادمین یا false
     */
    public static function loginAdmin(string $username, string $password, string $ip = ''): array|false
    {
        $username = trim($username);
        $ip = $ip ?: self::getClientIp();
        
        // بررسی سرعت لاگین (Rate Limiting)
        if (!self::checkLoginAttempts($username, $ip)) {
            ActivityLogger::log('admin_login_blocked', [
                'username' => $username,
                'ip' => $ip,
                'reason' => 'تعداد محاولات بیش از حد',
            ], 'high');
            return false;
        }
        
        try {
            $db = Database::get();
            $stmt = $db->prepare('SELECT id, username, password_hash, full_name, role, status FROM admins WHERE username = ? AND status = "active"');
            $stmt->execute([$username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$admin || !password_verify($password, $admin['password_hash'])) {
                self::recordLoginAttempt($username, $ip, false);
                ActivityLogger::log('admin_login_failed', [
                    'username' => $username,
                    'ip' => $ip,
                ], 'medium');
                return false;
            }
            
            // بروزرسانی آخرین ورود
            $update = $db->prepare('UPDATE admins SET last_login = CURRENT_TIMESTAMP WHERE id = ?');
            $update->execute([$admin['id']]);
            
            self::recordLoginAttempt($username, $ip, true);
            ActivityLogger::log('admin_login_success', [
                'admin_id' => $admin['id'],
                'username' => $username,
                'ip' => $ip,
            ], 'medium');
            
            return $admin;
        } catch (Exception $e) {
            error_log('خطا در لاگین ادمین: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * لاگین کاربر با شماره تلفن
     * 
     * @param int $userId شناسه کاربر
     * @param string $ip آدرس IP
     * @return array|false مشخصات کاربر یا false
     */
    public static function loginUser(int $userId, string $ip = ''): array|false
    {
        $ip = $ip ?: self::getClientIp();
        
        try {
            $db = Database::get();
            $stmt = $db->prepare('SELECT id, rubika_id, phone, name, lastname, is_verified FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return false;
            }
            
            // بروزرسانی آخرین ورود
            $update = $db->prepare('UPDATE users SET last_login = ?, last_activity = CURRENT_TIMESTAMP WHERE id = ?');
            $update->execute([date('Y-m-d H:i:s'), $userId]);
            
            ActivityLogger::log('user_login', [
                'user_id' => $userId,
                'ip' => $ip,
            ]);
            
            return $user;
        } catch (Exception $e) {
            error_log('خطا در لاگین کاربر: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * ایجاد Access Token
     * 
     * @param int $id شناسه کاربر یا ادمین
     * @param string $type نوع توکن: 'user' یا 'admin'
     * @param array $extra داده‌های اضافی
     * @return string|false توکن یا false
     */
    public static function createAccessToken(int $id, string $type = 'user', array $extra = []): string|false
    {
        try {
            $issuedAt = time();
            $expire = $issuedAt + self::ACCESS_TOKEN_EXPIRY;
            
            $payload = [
                'iat' => $issuedAt,
                'exp' => $expire,
                'sub' => (string)$id,
                'type' => $type,
                'jti' => self::generateJti(),
            ];
            
            // اضافه کردن داده‌های اضافی
            if (!empty($extra)) {
                $payload = array_merge($payload, $extra);
            }
            
            $token = JWT::encode($payload, self::getSecretKey(), self::ALGORITHM);
            return $token;
        } catch (Exception $e) {
            error_log('خطا در ایجاد Access Token: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * ایجاد Refresh Token
     * 
     * @param int $id شناسه کاربر یا ادمین
     * @param string $type نوع توکن
     * @return string|false توکن یا false
     */
    public static function createRefreshToken(int $id, string $type = 'user'): string|false
    {
        try {
            $issuedAt = time();
            $expire = $issuedAt + self::REFRESH_TOKEN_EXPIRY;
            $jti = self::generateJti();
            
            $payload = [
                'iat' => $issuedAt,
                'exp' => $expire,
                'sub' => (string)$id,
                'type' => $type,
                'jti' => $jti,
            ];
            
            $token = JWT::encode($payload, self::getSecretKey(), self::ALGORITHM);
            return $token;
        } catch (Exception $e) {
            error_log('خطا در ایجاد Refresh Token: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * تحقق از صحت Access Token
     * 
     * @param string $token توکن
     * @return object|false اطلاعات توکن یا false
     */
    public static function verifyAccessToken(string $token): object|false
    {
        try {
            $decoded = JWT::decode($token, new Key(self::getSecretKey(), self::ALGORITHM));
            
            // بررسی وجود در لیست سیاه
            if (self::isTokenBlacklisted($decoded->jti)) {
                return false;
            }
            
            return $decoded;
        } catch (Exception $e) {
            error_log('خطا در اعتبارسنجی Access Token: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * تحقق از صحت Refresh Token
     * 
     * @param string $token توکن
     * @return object|false اطلاعات توکن یا false
     */
    public static function verifyRefreshToken(string $token): object|false
    {
        try {
            $decoded = JWT::decode($token, new Key(self::getSecretKey(), self::ALGORITHM));
            
            // بررسی وجود در لیست سیاه
            if (self::isTokenBlacklisted($decoded->jti)) {
                return false;
            }
            
            return $decoded;
        } catch (Exception $e) {
            error_log('خطا در اعتبارسنجی Refresh Token: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * تجدید Access Token با استفاده از Refresh Token
     * 
     * @param string $refreshToken
     * @return array|false آرایه‌ای با access_token جدید یا false
     */
    public static function refreshAccessToken(string $refreshToken): array|false
    {
        $decoded = self::verifyRefreshToken($refreshToken);
        if (!$decoded) {
            return false;
        }
        
        $accessToken = self::createAccessToken(
            (int)$decoded->sub,
            $decoded->type
        );
        
        if (!$accessToken) {
            return false;
        }
        
        return [
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => self::ACCESS_TOKEN_EXPIRY,
        ];
    }
    
    /**
     * ورود کامل (لاگین + ایجاد توکن‌ها)
     * 
     * @param string $username نام کاربری (برای ادمین)
     * @param string $password رمز عبور
     * @param bool $isAdmin آیا ادمین است؟
     * @param string $ip آدرس IP
     * @return array|false مشخصات و توکن‌ها یا false
     */
    public static function login(string $username, string $password, bool $isAdmin = false, string $ip = ''): array|false
    {
        $ip = $ip ?: self::getClientIp();
        
        if ($isAdmin) {
            $admin = self::loginAdmin($username, $password, $ip);
            if (!$admin) {
                return false;
            }
            
            $accessToken = self::createAccessToken($admin['id'], 'admin', [
                'username' => $admin['username'],
                'role' => $admin['role'],
            ]);
            
            $refreshToken = self::createRefreshToken($admin['id'], 'admin');
            
            if (!$accessToken || !$refreshToken) {
                return false;
            }
            
            return [
                'id' => $admin['id'],
                'username' => $admin['username'],
                'full_name' => $admin['full_name'],
                'role' => $admin['role'],
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type' => 'Bearer',
                'expires_in' => self::ACCESS_TOKEN_EXPIRY,
            ];
        }
        
        // برای کاربر عادی، نام کاربری شماره موبایل است
        return false;
    }
    
    /**
     * تنظیم Refresh Token در HttpOnly Cookie
     * 
     * @param string $refreshToken
     * @param bool $secure استفاده از HTTPS
     * @return void
     */
    public static function setRefreshTokenCookie(string $refreshToken, bool $secure = true): void
    {
        $secure = $secure && !self::isDevelopment();
        
        setcookie(
            self::REFRESH_COOKIE_NAME,
            $refreshToken,
            [
                'expires' => time() + self::REFRESH_TOKEN_EXPIRY,
                'path' => '/',
                'domain' => $_SERVER['HTTP_HOST'] ?? '',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Strict',
            ]
        );
    }
    
    /**
     * دریافت Refresh Token از Cookie
     * 
     * @return string|null
     */
    public static function getRefreshTokenFromCookie(): ?string
    {
        return $_COOKIE[self::REFRESH_COOKIE_NAME] ?? null;
    }
    
    /**
     * دریافت Access Token از Authorization Header
     * 
     * @return string|null
     */
    public static function getAccessTokenFromHeader(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (!preg_match('/Bearer\s+([^\s]+)/', $header, $matches)) {
            return null;
        }
        
        return $matches[1];
    }
    
    /**
     * لاگ‌آوت (حذف توکن و Cookie)
     * 
     * @param string $refreshToken
     * @param int $userId
     * @param string $ip
     * @return bool
     */
    public static function logout(string $refreshToken = '', int $userId = 0, string $ip = ''): bool
    {
        $ip = $ip ?: self::getClientIp();
        
        try {
            // اضافه کردن توکن به لیست سیاه
            if ($refreshToken) {
                $decoded = self::verifyRefreshToken($refreshToken);
                if ($decoded) {
                    self::blacklistToken($decoded->jti, 'refresh');
                }
            }
            
            // حذف Cookie
            setcookie(self::REFRESH_COOKIE_NAME, '', time() - 3600, '/');
            setcookie(self::ACCESS_COOKIE_NAME, '', time() - 3600, '/');
            
            if ($userId > 0) {
                ActivityLogger::log('user_logout', [
                    'user_id' => $userId,
                    'ip' => $ip,
                ]);
            }
            
            return true;
        } catch (Exception $e) {
            error_log('خطا در لاگ‌آوت: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * اضافه کردن توکن به لیست سیاه
     * 
     * @param string $jti شناسه یکتای توکن
     * @param string $type نوع توکن
     * @param string $reason دلیل
     * @return bool
     */
    private static function blacklistToken(string $jti, string $type = 'access', string $reason = ''): bool
    {
        try {
            $db = Database::get();
            
            // محاسبه زمان انقضا
            $expiry = $type === 'refresh' 
                ? date('Y-m-d H:i:s', time() + self::REFRESH_TOKEN_EXPIRY)
                : date('Y-m-d H:i:s', time() + self::ACCESS_TOKEN_EXPIRY);
            
            $stmt = $db->prepare('INSERT INTO token_blacklist (jti, token_type, reason, expires_at) VALUES (?, ?, ?, ?)');
            return $stmt->execute([$jti, $type, $reason, $expiry]);
        } catch (Exception $e) {
            error_log('خطا در اضافه کردن توکن به لیست سیاه: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * بررسی اینکه آیا توکن در لیست سیاه است
     * 
     * @param string $jti شناسه یکتای توکن
     * @return bool
     */
    private static function isTokenBlacklisted(string $jti): bool
    {
        try {
            $db = Database::get();
            $stmt = $db->prepare('SELECT id FROM token_blacklist WHERE jti = ? AND expires_at > CURRENT_TIMESTAMP');
            $stmt->execute([$jti]);
            return (bool)$stmt->fetch();
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * بررسی سرعت لاگین (Rate Limiting)
     * 
     * @param string $username نام کاربری
     * @param string $ip آدرس IP
     * @return bool
     */
    private static function checkLoginAttempts(string $username, string $ip): bool
    {
        try {
            $db = Database::get();
            $stmt = $db->prepare('SELECT COUNT(*) as count FROM login_attempts WHERE (username = ? OR ip_address = ?) AND success = 0 AND attempted_at > ?');
            $stmt->execute([$username, $ip, date('Y-m-d H:i:s', time() - self::LOGIN_ATTEMPT_WINDOW)]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return ($result['count'] ?? 0) < self::MAX_LOGIN_ATTEMPTS;
        } catch (Exception $e) {
            return true;
        }
    }
    
    /**
     * ثبت تلاش لاگین
     * 
     * @param string $username نام کاربری
     * @param string $ip آدرس IP
     * @param bool $success موفقیت
     * @return void
     */
    private static function recordLoginAttempt(string $username, string $ip, bool $success = false): void
    {
        try {
            $db = Database::get();
            $stmt = $db->prepare('INSERT INTO login_attempts (username, ip_address, success) VALUES (?, ?, ?)');
            $stmt->execute([$username, $ip, (int)$success]);
            
            // حذف ثبت‌های قدیمی (بیش از 24 ساعت)
            $db->prepare('DELETE FROM login_attempts WHERE attempted_at < ?')->execute([date('Y-m-d H:i:s', time() - 86400)]);
        } catch (Exception $e) {
            // خاموش نگهداشتن خطا
        }
    }
    
    /**
     * ایجاد ادمین جدید
     * 
     * @param string $username نام کاربری
     * @param string $password رمز عبور
     * @param string $fullName نام کامل
     * @param string $email ایمیل
     * @param string $role نقش
     * @return array|false
     */
    public static function createAdmin(string $username, string $password, string $fullName, string $email = '', string $role = 'admin'): array|false
    {
        try {
            if (strlen($password) < 8) {
                return false;
            }
            
            $db = Database::get();
            $stmt = $db->prepare('INSERT INTO admins (username, password_hash, full_name, email, role) VALUES (?, ?, ?, ?, ?)');
            $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            
            if (!$stmt->execute([$username, $passwordHash, $fullName, $email, $role])) {
                return false;
            }
            
            $adminId = $db->lastInsertId();
            
            ActivityLogger::log('admin_created', [
                'admin_id' => $adminId,
                'username' => $username,
            ], 'medium');
            
            return [
                'id' => $adminId,
                'username' => $username,
                'full_name' => $fullName,
                'email' => $email,
                'role' => $role,
            ];
        } catch (Exception $e) {
            error_log('خطا در ایجاد ادمین: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * بروز کردن رمز عبور ادمین
     * 
     * @param int $adminId شناسه ادمین
     * @param string $newPassword رمز عبور جدید
     * @return bool
     */
    public static function updateAdminPassword(int $adminId, string $newPassword): bool
    {
        try {
            if (strlen($newPassword) < 8) {
                return false;
            }
            
            $db = Database::get();
            $stmt = $db->prepare('UPDATE admins SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
            $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
            
            if (!$stmt->execute([$passwordHash, $adminId])) {
                return false;
            }
            
            ActivityLogger::log('admin_password_changed', [
                'admin_id' => $adminId,
            ], 'high');
            
            return true;
        } catch (Exception $e) {
            error_log('خطا در بروز کردن رمز عبور: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * تولید JTI (JWT ID) منحصربه‌فرد
     * 
     * @return string
     */
    private static function generateJti(): string
    {
        return bin2hex(random_bytes(16));
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
     * بررسی حالت توسعه
     * 
     * @return bool
     */
    private static function isDevelopment(): bool
    {
        return getenv('APP_ENV') === 'development' || getenv('APP_DEBUG') === 'true';
    }
}
