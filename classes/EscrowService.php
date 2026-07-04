<?php

/**
 * سرویس واسطه‌گری (Escrow) و مدیریت معاملات
 * ادغام کامل درگاه زرین‌پال
 * @package VambanBot\Escrow
 */
class EscrowService
{
    // حالت‌های Escrow
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAYMENT_WAITING = 'payment_waiting';
    public const STATUS_PAYMENT_VERIFIED = 'payment_verified';
    public const STATUS_RELEASED = 'released';
    public const STATUS_DISPUTED = 'disputed';
    public const STATUS_CANCELLED = 'cancelled';
    
    // درصد کمیسیون
    private const PLATFORM_FEE_PERCENTAGE = 1.0; // 1%
    
    /**
     * ایجاد معاملهٔ جدید در واسطه‌گری
     * 
     * @param int $buyerId شناسه خریدار
     * @param int $sellerId شناسه فروشنده
     * @param float $amount مبلغ معامله
     * @param string $description توضیحات
     * @param array $metadata داده‌های اضافی
     * @return array|false
     */
    public static function createEscrow(
        int $buyerId,
        int $sellerId,
        float $amount,
        string $description = '',
        array $metadata = []
    ): array|false {
        try {
            // بررسی مبلغ
            if ($amount <= 0 || !is_finite($amount)) {
                return false;
            }
            
            // بررسی اینکه خریدار و فروشنده موجود هستند
            $db = Database::get();
            
            $buyerStmt = $db->prepare('SELECT id FROM users WHERE id = ?');
            $buyerStmt->execute([$buyerId]);
            if (!$buyerStmt->fetch()) {
                return false;
            }
            
            $sellerStmt = $db->prepare('SELECT id FROM users WHERE id = ?');
            $sellerStmt->execute([$sellerId]);
            if (!$sellerStmt->fetch()) {
                return false;
            }
            
            // بررسی اینکه خریدار احراز هویت شده باشد
            if (!KYCService::isUserVerified($buyerId)) {
                return false;
            }
            
            // محاسباتِ کمیسیون
            $fee = self::calculateFee($amount);
            $totalAmount = $amount + $fee;
            
            // ایجاد سفارش
            $orderId = self::generateOrderId();
            
            $insert = $db->prepare('
                INSERT INTO escrows (
                    buyer_id, seller_id, amount, fee, status, description, 
                    metadata, order_id, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ');
            
            $metadataJson = json_encode($metadata, JSON_UNESCAPED_UNICODE);
            
            if (!$insert->execute([
                $buyerId,
                $sellerId,
                $amount,
                $fee,
                self::STATUS_PENDING,
                $description,
                $metadataJson,
                $orderId,
            ])) {
                return false;
            }
            
            $escrowId = $db->lastInsertId();
            
            // ثبت لاگ
            self::logEscrowEvent($escrowId, 'escrow_created', [
                'buyer_id' => $buyerId,
                'seller_id' => $sellerId,
                'amount' => $amount,
                'fee' => $fee,
            ]);
            
            ActivityLogger::log('escrow_created', [
                'escrow_id' => $escrowId,
                'buyer_id' => $buyerId,
                'seller_id' => $sellerId,
                'amount' => $amount,
                'order_id' => $orderId,
            ], 'medium', $buyerId);
            
            return [
                'id' => $escrowId,
                'order_id' => $orderId,
                'buyer_id' => $buyerId,
                'seller_id' => $sellerId,
                'amount' => $amount,
                'fee' => $fee,
                'total_amount' => $totalAmount,
                'status' => self::STATUS_PENDING,
            ];
        } catch (Exception $e) {
            error_log('خطا در ایجاد Escrow: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * دریافت جزئیات Escrow
     * 
     * @param int $escrowId
     * @return array|null
     */
    public static function getEscrow(int $escrowId): ?array
    {
        try {
            $db = Database::get();
            $stmt = $db->prepare('SELECT * FROM escrows WHERE id = ?');
            $stmt->execute([$escrowId]);
            $escrow = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($escrow && !empty($escrow['metadata'])) {
                $escrow['metadata'] = json_decode($escrow['metadata'], true) ?: [];
            }
            
            return $escrow ?: null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * شروع فرآیند پرداخت
     * 
     * @param int $escrowId
     * @param string $returnUrl URL بازگشت
     * @return array|false شامل authority کوپال یا false
     */
    public static function initializePayment(int $escrowId, string $returnUrl = ''): array|false
    {
        try {
            $escrow = self::getEscrow($escrowId);
            if (!$escrow || $escrow['status'] !== self::STATUS_PENDING) {
                return false;
            }
            
            $db = Database::get();
            
            // تولید authority برای درگاه
            $authority = self::generateAuthority();
            
            // بروز کردن Escrow
            $update = $db->prepare('
                UPDATE escrows 
                SET status = ?, zarinpal_authority = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ');
            
            if (!$update->execute([self::STATUS_PAYMENT_WAITING, $authority, $escrowId])) {
                return false;
            }
            
            // ثبت لاگ
            self::logEscrowEvent($escrowId, 'payment_initiated', [
                'authority' => $authority,
            ]);
            
            $totalAmount = (int)(($escrow['amount'] + $escrow['fee']) * 100); // به تومان
            
            return [
                'authority' => $authority,
                'amount' => $escrow['amount'],
                'fee' => $escrow['fee'],
                'total_amount' => $escrow['amount'] + $escrow['fee'],
                'order_id' => $escrow['order_id'],
            ];
        } catch (Exception $e) {
            error_log('خطا در شروع پرداخت: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * تأیید پرداخت (Verify Payment)
     * 
     * @param int $escrowId
     * @param string $authority
     * @param string $refId
     * @return bool
     */
    public static function verifyPayment(int $escrowId, string $authority, string $refId = ''): bool
    {
        try {
            $escrow = self::getEscrow($escrowId);
            if (!$escrow || $escrow['status'] !== self::STATUS_PAYMENT_WAITING) {
                return false;
            }
            
            if ($escrow['zarinpal_authority'] !== $authority) {
                return false;
            }
            
            $db = Database::get();
            
            // بروز کردن Escrow
            $update = $db->prepare('
                UPDATE escrows 
                SET status = ?, zarinpal_ref_id = ?, payment_verified_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ');
            
            if (!$update->execute([self::STATUS_PAYMENT_VERIFIED, $refId, $escrowId])) {
                return false;
            }
            
            // ثبت لاگ
            self::logEscrowEvent($escrowId, 'payment_verified', [
                'ref_id' => $refId,
            ]);
            
            ActivityLogger::log('payment_completed', [
                'escrow_id' => $escrowId,
                'amount' => $escrow['amount'],
                'ref_id' => $refId,
            ], 'medium', $escrow['buyer_id']);
            
            return true;
        } catch (Exception $e) {
            error_log('خطا در تأیید پرداخت: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * آزادسازی وجه به فروشنده
     * 
     * @param int $escrowId شناسه Escrow
     * @param int|null $releasedBy شناسه کاربری که آزادسازی را درخواست کرد
     * @return bool
     */
    public static function releaseEscrow(int $escrowId, ?int $releasedBy = null): bool
    {
        try {
            $escrow = self::getEscrow($escrowId);
            if (!$escrow || $escrow['status'] !== self::STATUS_PAYMENT_VERIFIED) {
                return false;
            }
            
            $db = Database::get();
            
            // بروز کردن Escrow
            $update = $db->prepare('
                UPDATE escrows 
                SET status = ?, released_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ');
            
            if (!$update->execute([self::STATUS_RELEASED, $escrowId])) {
                return false;
            }
            
            // ثبت لاگ
            self::logEscrowEvent($escrowId, 'escrow_released', [
                'released_by' => $releasedBy,
                'amount_to_seller' => $escrow['amount'],
                'platform_fee' => $escrow['fee'],
            ]);
            
            ActivityLogger::log('escrow_released', [
                'escrow_id' => $escrowId,
                'buyer_id' => $escrow['buyer_id'],
                'seller_id' => $escrow['seller_id'],
                'amount' => $escrow['amount'],
            ], 'high', $escrow['seller_id']);
            
            return true;
        } catch (Exception $e) {
            error_log('خطا در آزادسازی Escrow: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * ثبت اختلاف در معامله
     * 
     * @param int $escrowId
     * @param int $initiatedBy شناسه کاربری که اختلاف را ثبت کرد
     * @param string $reason دلیل اختلاف
     * @return bool
     */
    public static function raiseDispute(int $escrowId, int $initiatedBy, string $reason): bool
    {
        try {
            $escrow = self::getEscrow($escrowId);
            if (!$escrow || !in_array($escrow['status'], [
                self::STATUS_PAYMENT_VERIFIED,
                self::STATUS_RELEASED,
            ])) {
                return false;
            }
            
            $db = Database::get();
            
            // بروز کردن Escrow
            $update = $db->prepare('
                UPDATE escrows 
                SET status = ?, disputed_at = CURRENT_TIMESTAMP, dispute_reason = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ');
            
            if (!$update->execute([self::STATUS_DISPUTED, $reason, $escrowId])) {
                return false;
            }
            
            // ثبت لاگ
            self::logEscrowEvent($escrowId, 'dispute_raised', [
                'initiated_by' => $initiatedBy,
                'reason' => $reason,
            ]);
            
            ActivityLogger::log('escrow_disputed', [
                'escrow_id' => $escrowId,
                'initiated_by' => $initiatedBy,
                'reason' => $reason,
            ], 'high', $initiatedBy);
            
            return true;
        } catch (Exception $e) {
            error_log('خطا درثبت اختلاف: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * انصراف از معامله
     * 
     * @param int $escrowId
     * @param int $cancelledBy
     * @param string $reason
     * @return bool
     */
    public static function cancelEscrow(int $escrowId, int $cancelledBy, string $reason = ''): bool
    {
        try {
            $escrow = self::getEscrow($escrowId);
            if (!$escrow || $escrow['status'] !== self::STATUS_PENDING) {
                return false;
            }
            
            $db = Database::get();
            
            // بروز کردن Escrow
            $update = $db->prepare('
                UPDATE escrows 
                SET status = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ');
            
            if (!$update->execute([self::STATUS_CANCELLED, $escrowId])) {
                return false;
            }
            
            // ثبت لاگ
            self::logEscrowEvent($escrowId, 'escrow_cancelled', [
                'cancelled_by' => $cancelledBy,
                'reason' => $reason,
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log('خطا در انصراف از معامله: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * دریافت معاملات کاربر (خرید و فروش)
     * 
     * @param int $userId
     * @param string $role نوع (buyer, seller, all)
     * @param int $limit
     * @return array
     */
    public static function getUserEscrows(int $userId, string $role = 'all', int $limit = 50): array
    {
        try {
            $db = Database::get();
            
            if ($role === 'buyer') {
                $stmt = $db->prepare('
                    SELECT * FROM escrows 
                    WHERE buyer_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT ?
                ');
                $stmt->execute([$userId, $limit]);
            } elseif ($role === 'seller') {
                $stmt = $db->prepare('
                    SELECT * FROM escrows 
                    WHERE seller_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT ?
                ');
                $stmt->execute([$userId, $limit]);
            } else {
                $stmt = $db->prepare('
                    SELECT * FROM escrows 
                    WHERE buyer_id = ? OR seller_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT ?
                ');
                $stmt->execute([$userId, $userId, $limit]);
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * محاسباتِ کمیسیون
     * 
     * @param float $amount
     * @return float
     */
    private static function calculateFee(float $amount): float
    {
        return round(($amount * self::PLATFORM_FEE_PERCENTAGE) / 100, 2);
    }
    
    /**
     * تولید شناسه‌ی منحصربه‌فرد سفارش
     * 
     * @return string
     */
    private static function generateOrderId(): string
    {
        return 'ORD-' . strtoupper(substr(uniqid(), -8)) . '-' . time();
    }
    
    /**
     * تولید Authority برای درگاه
     * 
     * @return string
     */
    private static function generateAuthority(): string
    {
        return 'AUTH-' . strtoupper(bin2hex(random_bytes(24)));
    }
    
    /**
     * ثبت رویداد Escrow
     * 
     * @param int $escrowId
     * @param string $event نام رویداد
     * @param array $data داده‌های اضافی
     * @return void
     */
    private static function logEscrowEvent(int $escrowId, string $event, array $data = []): void
    {
        try {
            $db = Database::get();
            $stmt = $db->prepare('
                INSERT INTO escrow_logs (escrow_id, event, data, created_at) 
                VALUES (?, ?, ?, CURRENT_TIMESTAMP)
            ');
            $stmt->execute([
                $escrowId,
                $event,
                json_encode($data, JSON_UNESCAPED_UNICODE),
            ]);
        } catch (Exception $e) {
            // خاموش نگهداشتن خطاها
        }
    }
    
    /**
     * دریافت لاگ رویدادهای Escrow
     * 
     * @param int $escrowId
     * @return array
     */
    public static function getEscrowLogs(int $escrowId): array
    {
        try {
            $db = Database::get();
            $stmt = $db->prepare('
                SELECT * FROM escrow_logs 
                WHERE escrow_id = ? 
                ORDER BY created_at ASC
            ');
            $stmt->execute([$escrowId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            return [];
        }
    }
}
