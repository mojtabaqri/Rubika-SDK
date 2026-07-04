<?php

/**
 * سرویس احراز هویت کاربران (KYC)
 * مدیریت مراحل تایید هویت
 * @package VambanBot\KYC
 */
class KYCService
{
    // مراحل KYC
    public const STEP_PERSONAL_INFO = 'personal_info';
    public const STEP_ADDRESS = 'address';
    public const STEP_MOBILE = 'mobile';
    public const STEP_ID_CARD = 'id_card';
    
    // حالت‌ها
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    
    // پوشه‌های آپلود
    private const UPLOAD_DIR = __DIR__ . '/../uploads/kyc/';
    private const MAX_FILE_SIZE = 5242880; // 5 مگابایت
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    /**
     * ارسال اطلاعات KYC
     * 
     * @param int $userId شناسه کاربر
     * @param array $data اطلاعات درخواست
     * @param array $files فایل‌های آپلود شده
     * @return array|false
     */
    public static function submitKYC(int $userId, array $data, array $files = []): array|false
    {
        try {
            $db = Database::get();
            
            // بررسی اینکه آیا کاربر موجود است
            $stmt = $db->prepare('SELECT id FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            if (!$stmt->fetch()) {
                return false;
            }

            $existing = self::getKYCStatus($userId);
            if ($existing && isset($existing['status']) && $existing['status'] === self::STATUS_PENDING) {
                return ['error' => 'درخواست احراز هویت شما در حال بررسی است. تا زمان تعیین تکلیف، امکان ثبت درخواست جدید وجود ندارد.'];
            }
            
            // اعتبار‌سنجی داده‌ها
            $errors = self::validateKYCData($data);
            if (!empty($errors)) {
                return $errors;
            }
            
            // پردازش فایل‌های آپلود
            $uploadedFiles = [];
            if (!empty($files)) {
                $uploadedFiles = self::handleFileUploads($userId, $files);
                if (empty($uploadedFiles) && !empty($files)) {
                    return ['files' => 'عکس پشت کارت ملی الزامی است.'];
                }
            } elseif (empty($data['national_card_back_image'] ?? '')) {
                return ['files' => 'عکس پشت کارت ملی الزامی است.'];
            }
            
            // ذخیره‌سازی در دیتابیس
            $stmt = $db->prepare('
                INSERT INTO user_verifications (
                    user_id, full_name, address, postal_code, phone, national_id,
                    national_card_back_image, status, submitted_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
            ');
            
            $success = $stmt->execute([
                $userId,
                $data['full_name'] ?? '',
                $data['address'] ?? '',
                $data['postal_code'] ?? '',
                $data['phone'] ?? '',
                $data['national_id'] ?? '',
                $uploadedFiles['id_card_back'] ?? $uploadedFiles['national_card_back_image'] ?? '',
                self::STATUS_PENDING,
            ]);
            
            if (!$success) {
                return false;
            }

            $db->prepare('UPDATE users SET status = ?, is_verified = 0 WHERE id = ?')->execute([self::STATUS_PENDING, $userId]);
            
            $verificationId = $db->lastInsertId();
            
            // ثبت لاگ فعالیت
            ActivityLogger::log('kyc_submitted', [
                'user_id' => $userId,
                'verification_id' => $verificationId,
            ]);
            
            return [
                'id' => $verificationId,
                'status' => self::STATUS_PENDING,
                'message' => 'درخواست احراز هویت برای بررسی قرار گرفت.',
            ];
        } catch (Exception $e) {
            error_log('خطا در ارسال KYC: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * روش سازگار با کدهای قدیمی که روی نمونه‌ی کلاس فراخوانی می‌شوند.
     */
    public function submitVerification(int $userId, array $data, array $files = []): array|false
    {
        return self::submitKYC($userId, $data, $files);
    }

    /**
     * روش سازگار با کدهای قدیمی که روی نمونه‌ی کلاس فراخوانی می‌شوند.
     */
    public function submitVerificationFromText(int $userId, array $data): array|false
    {
        return self::submitKYC($userId, $data, []);
    }

    /**
     * روش سازگار با کدهای قدیمی که روی نمونه‌ی کلاس فراخوانی می‌شوند.
     */
    public function getVerificationByUser(int $userId): ?array
    {
        return self::getKYCStatus($userId);
    }

    /**
     * دریافت وضعیت KYC کاربر
     * 
     * @param int $userId شناسه کاربر
     * @return array|null
     */
    public static function getKYCStatus(int $userId): ?array
    {
        try {
            $db = Database::get();
            $stmt = $db->prepare('
                SELECT id, status, full_name, address, postal_code, phone, national_id,
                    national_card_back_image, submitted_at, reviewed_at, admin_notes 
                FROM user_verifications 
                WHERE user_id = ? 
                ORDER BY submitted_at DESC 
                LIMIT 1
            ');
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * بررسی اینکه آیا کاربر احراز هویت شده است
     * 
     * @param int $userId
     * @return bool
     */
    public static function isUserVerified(int $userId): bool
    {
        try {
            $db = Database::get();
            $stmt = $db->prepare('SELECT id FROM user_verifications WHERE user_id = ? AND status = ?');
            $stmt->execute([$userId, self::STATUS_APPROVED]);
            return (bool)$stmt->fetch();
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * اعتبار‌سنجی اطلاعات KYC
     * 
     * @param array $data
     * @return array خطاها (خالی اگر معتبر باشد)
     */
    private static function validateKYCData(array $data): array
    {
        $errors = [];
        
        // نام و نام خانوادگی
        $fullName = trim($data['full_name'] ?? '');
        if (empty($fullName) || strlen($fullName) < 3 || strlen($fullName) > 100) {
            $errors['full_name'] = 'نام و نام خانوادگی باید بین 3 تا 100 حرف باشد.';
        }
        
        // آدرس
        $address = trim($data['address'] ?? '');
        if (empty($address) || strlen($address) < 5 || strlen($address) > 255) {
            $errors['address'] = 'آدرس باید بین 5 تا 255 حرف باشد.';
        }
        
        // کد پستی
        $postalCode = trim($data['postal_code'] ?? '');
        if (!preg_match('/^\d{10}$/', str_replace('-', '', $postalCode))) {
            $errors['postal_code'] = 'کد پستی نامعتبر است (10 رقم).';
        }
        
        // شماره تلفن
        $phone = preg_replace('/[^0-9]/', '', $data['phone'] ?? '');
        if (!preg_match('/^(?:98|0)?9\d{9}$/', $phone)) {
            $errors['phone'] = 'شماره تلفن نامعتبر است.';
        }

        // کد ملی
        $nationalId = preg_replace('/[^0-9]/', '', $data['national_id'] ?? '');
        if (!preg_match('/^\d{10}$/', $nationalId) || !self::isValidNationalId($nationalId)) {
            $errors['national_id'] = 'کد ملی باید 10 رقم بوده و معتبر باشد.';
        }
        
        return $errors;
    }
    
    /**
     * پردازش آپلود فایل‌ها
     * 
     * @param int $userId
     * @param array $files فایل‌های $_FILES
     * @return array نام‌های فایل‌های آپلود شده
     */
    private static function handleFileUploads(int $userId, array $files): array
    {
        $uploadedFiles = [];
        
        // تأیید وجود پوشه
        if (!is_dir(self::UPLOAD_DIR)) {
            mkdir(self::UPLOAD_DIR, 0755, true);
        }
        
        foreach ($files as $fieldName => $file) {
            if (!isset($file['tmp_name']) || empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
                continue;
            }
            
            // اعتبار‌سنجی فایل
            $validationResult = self::validateFile($file);
            if ($validationResult !== true) {
                error_log("خطای آپلود برای {$fieldName}: {$validationResult}");
                continue;
            }
            
            // تولید نام فایل امن
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = sprintf(
                'user_%d_%s_%s.%s',
                $userId,
                $fieldName,
                time(),
                strtolower($extension)
            );
            
            $filePath = self::UPLOAD_DIR . $fileName;
            
            $moved = false;
            if (is_uploaded_file($file['tmp_name'])) {
                $moved = move_uploaded_file($file['tmp_name'], $filePath);
            } elseif (is_file($file['tmp_name'])) {
                $moved = copy($file['tmp_name'], $filePath);
            }

            if ($moved) {
                // تبدیل تصویر به WebP برای صرفه‌جویی در فضا
                self::optimizeImage($filePath);
                $uploadedFiles[$fieldName] = $fileName;
            }
        }
        
        return $uploadedFiles;
    }
    
    /**
     * اعتبار‌سنجی فایل
     * 
     * @param array $file
     * @return true|string
     */
    private static function isValidNationalId(string $nationalId): bool
    {
        if (!preg_match('/^\d{10}$/', $nationalId)) {
            return false;
        }

        $digits = str_split($nationalId);
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int)$digits[$i] * (10 - $i);
        }

        $remainder = $sum % 11;
        $controlDigit = $remainder < 2 ? $remainder : 11 - $remainder;
        return $controlDigit === (int)$digits[9];
    }

    private static function validateFile(array $file): true|string
    {
        // بررسی اندازه
        if ($file['size'] > self::MAX_FILE_SIZE) {
            return 'حجم فایل از 5 مگابایت بیشتر است.';
        }
        
        // بررسی توسعه
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            return 'فرمت فایل پشتیبانی نشده است.';
        }
        
        // بررسی MIME Type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mimeType, $allowedMimeTypes)) {
            return 'فایل معتبر نیست.';
        }
        
        return true;
    }
    
    /**
     * بهینه‌سازی تصویر
     * 
     * @param string $filePath مسیر فایل
     * @return void
     */
    private static function optimizeImage(string $filePath): void
    {
        try {
            // اگر GD موجود است، تصویر را کوچک کنید
            if (!function_exists('getimagesize')) {
                return;
            }
            
            $imageInfo = getimagesize($filePath);
            if (!$imageInfo) {
                return;
            }
            
            $width = $imageInfo[0];
            $height = $imageInfo[1];
            
            // تغییر اندازه اگر بیش از حد بزرگ باشد
            if ($width > 2000 || $height > 2000) {
                $ratio = min(2000 / $width, 2000 / $height);
                $newWidth = (int)($width * $ratio);
                $newHeight = (int)($height * $ratio);
                
                $image = imagecreatefromstring(file_get_contents($filePath));
                $resized = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                imagejpeg($resized, $filePath, 85);
                imagedestroy($image);
                imagedestroy($resized);
            }
        } catch (Exception $e) {
            // خاموش نگهداشتن خطاها در بهینه‌سازی
        }
    }
    
    /**
     * دریافت لیست درخواست‌های KYC برای ادمین
     * 
     * @param string $status وضعیت (pending, approved, rejected)
     * @param int $limit تعداد ثبت‌ها
     * @param int $offset شروع از
     * @return array
     */
    public static function getPendingRequests(string $status = self::STATUS_PENDING, int $limit = 20, int $offset = 0): array
    {
        return self::getPendingVerifications($limit, $offset, $status);
    }

    public static function getPendingVerifications(int $limit = 20, int $offset = 0, string $status = self::STATUS_PENDING): array
    {
        try {
            $db = Database::get();
            $stmt = $db->prepare('
                SELECT uv.*, u.rubika_id, u.phone FROM user_verifications uv
                JOIN users u ON uv.user_id = u.id
                WHERE uv.status = ?
                ORDER BY uv.submitted_at DESC
                LIMIT ? OFFSET ?
            ');
            $stmt->execute([$status, $limit, $offset]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            return [];
        }
    }

    public static function reviewVerification(int $verificationId, string $status, string $notes, int $adminId): array
    {
        try {
            $normalizedStatus = strtolower(trim($status));
            if (!in_array($normalizedStatus, [self::STATUS_APPROVED, self::STATUS_REJECTED], true)) {
                return ['success' => false, 'message' => 'وضعیت نامعتبر است.'];
            }

            $db = Database::get();
            $stmt = $db->prepare('SELECT user_id FROM user_verifications WHERE id = ?');
            $stmt->execute([$verificationId]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$record) {
                return ['success' => false, 'message' => 'درخواست KYC پیدا نشد.'];
            }

            $update = $db->prepare('UPDATE user_verifications SET status = ?, reviewed_at = CURRENT_TIMESTAMP, reviewed_by = ?, admin_notes = ? WHERE id = ?');
            $update->execute([$normalizedStatus, $adminId, $notes, $verificationId]);

            $userUpdate = $db->prepare('UPDATE users SET status = ?, is_verified = ?, last_activity = CURRENT_TIMESTAMP WHERE id = ?');
            $userUpdate->execute([
                $normalizedStatus === self::STATUS_APPROVED ? 'verified' : 'rejected',
                $normalizedStatus === self::STATUS_APPROVED ? 1 : 0,
                $record['user_id'],
            ]);

            if ($normalizedStatus === self::STATUS_APPROVED) {
                User::adjustTrust((int)$record['user_id'], 20);
            }

            ActivityLogger::log($normalizedStatus === self::STATUS_APPROVED ? 'kyc_approved' : 'kyc_rejected', [
                'verification_id' => $verificationId,
                'user_id' => $record['user_id'],
                'notes' => $notes,
            ], 'medium', $record['user_id'], $adminId);

            return [
                'success' => true,
                'message' => $normalizedStatus === self::STATUS_APPROVED ? 'درخواست KYC تایید شد.' : 'درخواست KYC رد شد.',
            ];
        } catch (Exception $e) {
            error_log('خطا در بررسی KYC: ' . $e->getMessage());
            return ['success' => false, 'message' => 'خطایی در ثبت نتیجه KYC رخ داد.'];
        }
    }
    
    /**
     * تایید درخواست KYC
     * 
     * @param int $verificationId شناسه درخواست
     * @param int $adminId شناسه ادمین
     * @param string $notes یادداشت‌های ادمین
     * @return bool
     */
    public static function approveKYC(int $verificationId, int $adminId, string $notes = ''): bool
    {
        $result = self::reviewVerification($verificationId, self::STATUS_APPROVED, $notes, $adminId);
        return ($result['success'] ?? false) === true;
    }
    
    /**
     * رد کردن درخواست KYC
     * 
     * @param int $verificationId شناسه درخواست
     * @param int $adminId شناسه ادمین
     * @param string $reason دلیل رد کردن
     * @return bool
     */
    public static function rejectKYC(int $verificationId, int $adminId, string $reason): bool
    {
        $result = self::reviewVerification($verificationId, self::STATUS_REJECTED, $reason, $adminId);
        return ($result['success'] ?? false) === true;
    }
}

