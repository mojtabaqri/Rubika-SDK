<?php

function dbExecute(string $sql, array $params = []): PDOStatement
{
    $stmt = Database::get()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function dbFetch(string $sql, array $params = []): ?array
{
    $stmt = dbExecute($sql, $params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result === false ? null : $result;
}

function dbFetchAll(string $sql, array $params = []): array
{
    $stmt = dbExecute($sql, $params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function mergePendingData(array $row): array
{
    if (!isset($row['pending_data']) || $row['pending_data'] === null) {
        return $row;
    }

    $pending = json_decode($row['pending_data'], true);
    if (!is_array($pending)) {
        return $row;
    }

    return array_merge($row, $pending);
}

class Helpers
{
    public static function trustLevel(int $score): string
    {
        if ($score >= 90) {
            return 'VIP';
        }
        if ($score >= 70) {
            return 'معتبر';
        }
        if ($score >= 30) {
            return 'معمولی';
        }
        return 'تازه‌کار';
    }

    public static function formatStatus(string $value): string
    {
        $map = [
            'pending' => 'در انتظار',
            'verified' => 'تایید شده',
            'rejected' => 'رد شده',
            'approved' => 'تایید شده',
            'held' => 'مسدود',
            'released' => 'آزاد شده',
            'dispute' => 'اختلاف',
            'request' => 'درخواست وام',
            'offer' => 'ارائه وام',
        ];

        return $map[$value] ?? $value;
    }

    public static function loanCode(int $id): string
    {
        return sprintf('L-1405-%05d', $id);
    }
}

class User
{
    public static function findByRubikaId(string $rubikaId): ?array
    {
        return dbFetch('SELECT * FROM users WHERE rubika_id = ?', [$rubikaId]);
    }

    public static function findById(int $id): ?array
    {
        return dbFetch('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public static function create(string $rubikaId): array
    {
        dbExecute('INSERT OR IGNORE INTO users (rubika_id) VALUES (?)', [$rubikaId]);
        return self::findByRubikaId($rubikaId);
    }

    public static function update(int $id, array $data): void
    {
        $fields = [];
        $params = [];
        foreach ($data as $key => $value) {
            $fields[] = "{$key} = ?";
            $params[] = $value;
        }
        $params[] = $id;
        dbExecute('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?', $params);
    }

    public static function setState(int $id, string $step): void
    {
        self::update($id, ['current_step' => $step]);
    }

    public static function setPendingData(int $id, array $data): void
    {
        $existing = self::findById($id);
        $pending = [];
        if ($existing && !empty($existing['pending_data'])) {
            $pending = json_decode($existing['pending_data'], true) ?: [];
        }
        $pending = array_merge($pending, $data);
        self::update($id, ['pending_data' => json_encode($pending, JSON_UNESCAPED_UNICODE)]);
    }

    public static function clearStep(int $id): void
    {
        self::update($id, ['current_step' => 'none', 'pending_data' => json_encode([])]);
    }

    public static function setStatus(int $id, string $status): void
    {
        self::update($id, ['status' => $status]);
    }

    public static function adjustTrust(int $id, int $delta): void
    {
        $user = self::findById($id);
        if (!$user) {
            return;
        }
        $score = max(0, ($user['trust_score'] ?? 0) + $delta);
        self::update($id, ['trust_score' => $score]);
    }

    public static function findByPhone(string $phone): ?array
    {
        return dbFetch('SELECT * FROM users WHERE phone = ?', [$phone]);
    }

    public static function createByPhone(string $phone): array
    {
        dbExecute('INSERT INTO users (phone, status, trust_score, current_step) VALUES (?, ?, ?, ?)', [$phone, 'pending', 0, 'none']);
        return self::findByPhone($phone);
    }

    public static function all(): array
    {
        return dbFetchAll('SELECT * FROM users ORDER BY id DESC');
    }

    public static function stats(): array
    {
        return dbFetch('SELECT
            COUNT(*) AS total_users,
            SUM(CASE WHEN status = "verified" THEN 1 ELSE 0 END) AS verified_users,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) AS pending_users
            FROM users');
    }
}

class Otp
{
    public static function createCode(string $phone): string
    {
        $user = User::findByPhone($phone);
        if (!$user) {
            $user = User::createByPhone($phone);
        }

        $code = strval(random_int(100000, 999999));
        $expiresAt = (new DateTime('+10 minutes'))->format('Y-m-d H:i:s');

        dbExecute('INSERT INTO login_otps (user_id, phone, code, expires_at) VALUES (?, ?, ?, ?)', [
            $user['id'],
            $phone,
            $code,
            $expiresAt,
        ]);

        return $code;
    }

    public static function verifyCode(string $phone, string $code): ?array
    {
        $otp = dbFetch('SELECT * FROM login_otps WHERE phone = ? AND code = ? ORDER BY created_at DESC LIMIT 1', [$phone, $code]);
        if (!$otp) {
            return null;
        }

        $expiresAt = DateTime::createFromFormat('Y-m-d H:i:s', $otp['expires_at']);
        if (!$expiresAt || new DateTime() > $expiresAt) {
            return null;
        }

        return User::findById((int)$otp['user_id']);
    }
}

class Kyc
{
    public static function create(int $userId, array $data): array
    {
        $fullName = trim(($data['name'] ?? '') . ' ' . ($data['lastname'] ?? ''));
        return UserVerification::create($userId, [
            'full_name' => $fullName,
            'address' => $data['address'] ?? '',
            'postal_code' => $data['postal_code'] ?? $data['postal_code'] ?? '',
            'phone' => $data['phone'] ?? '',
            'national_id' => $data['national_id'] ?? '',
            'national_card_back_image' => $data['id_back'] ?? $data['selfie'] ?? '',
        ]);
    }

    public static function findByUser(int $userId): ?array
    {
        return UserVerification::findByUser($userId);
    }

    public static function all(): array
    {
        return UserVerification::pendingAll();
    }

    public static function updateStatus(int $id, string $status): void
    {
        UserVerification::update($id, ['status' => $status]);
    }
}

class Ad
{
    public static function create(int $userId, string $type, float $amount, string $description): array
    {
        dbExecute('INSERT INTO ads (user_id, type, amount, description, loan_code, status) VALUES (?, ?, ?, ?, ?, ?)', [
            $userId,
            $type,
            $amount,
            $description,
            '',
            'pending',
        ]);
        $id = Database::get()->lastInsertId();
        $loanCode = Helpers::loanCode((int)$id);
        dbExecute('UPDATE ads SET loan_code = ? WHERE id = ?', [$loanCode, $id]);
        return self::findById((int)$id);
    }

    public static function findById(int $id): ?array
    {
        return dbFetch('SELECT * FROM ads WHERE id = ?', [$id]);
    }

    public static function listApproved(): array
    {
        return dbFetchAll('SELECT ads.*, users.name, users.lastname FROM ads LEFT JOIN users ON users.id = ads.user_id WHERE ads.status = "approved" ORDER BY ads.created_at DESC');
    }

    public static function all(): array
    {
        return dbFetchAll('SELECT ads.*, users.name, users.lastname FROM ads LEFT JOIN users ON users.id = ads.user_id ORDER BY ads.created_at DESC');
    }

    public static function byUser(int $userId): array
    {
        return dbFetchAll('SELECT ads.*, users.name, users.lastname FROM ads LEFT JOIN users ON users.id = ads.user_id WHERE ads.user_id = ? ORDER BY ads.created_at DESC', [$userId]);
    }

    public static function updateStatus(int $id, string $status): void
    {
        dbExecute('UPDATE ads SET status = ? WHERE id = ?', [$status, $id]);
    }
}

class Payment
{
    public static function all(): array
    {
        return dbFetchAll('SELECT payments.*, users.name, users.lastname FROM payments LEFT JOIN users ON users.id = payments.user_id ORDER BY payments.created_at DESC');
    }

    public static function updateStatus(int $id, string $status): void
    {
        dbExecute('UPDATE payments SET status = ? WHERE id = ?', [$status, $id]);
    }
}

class Escrow
{
    public static function create(array $data): array
    {
        return Transaction::createEscrow(
            (int)$data['buyer_id'],
            (int)$data['seller_id'],
            (float)$data['amount'],
            (string)$data['description'],
            $data['order_id'] ?? null,
            $data['metadata'] ?? []
        );
    }

    public static function all(): array
    {
        return Transaction::all();
    }

    public static function findById(int $id): ?array
    {
        return Transaction::getById($id);
    }

    public static function findByAuthority(string $authority): ?array
    {
        return Transaction::findByAuthority($authority);
    }

    public static function update(int $id, array $data): void
    {
        Transaction::update($id, $data);
    }

    public static function updateStatus(int $id, string $status): void
    {
        Transaction::update($id, ['status' => $status]);
    }

    public static function logs(int $escrowId): array
    {
        return Transaction::logs($escrowId);
    }
}

class Report
{
    public static function all(): array
    {
        return dbFetchAll('SELECT reports.*, users.name, users.lastname FROM reports LEFT JOIN users ON users.id = reports.user_id ORDER BY reports.created_at DESC');
    }

    public static function delete(int $id): void
    {
        dbExecute('DELETE FROM reports WHERE id = ?', [$id]);
    }
}
