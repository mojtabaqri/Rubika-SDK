<?php

class UserVerification
{
    public static function findById(int $id): ?array
    {
        return dbFetch('SELECT uv.*, users.phone AS user_phone, users.name AS user_name, users.lastname AS user_lastname FROM user_verifications uv LEFT JOIN users ON users.id = uv.user_id WHERE uv.id = ?', [$id]);
    }

    public static function findByUser(int $userId): ?array
    {
        return dbFetch('SELECT uv.*, users.phone AS user_phone, users.name AS user_name, users.lastname AS user_lastname FROM user_verifications uv LEFT JOIN users ON users.id = uv.user_id WHERE uv.user_id = ? ORDER BY uv.submitted_at DESC LIMIT 1', [$userId]);
    }

    public static function hasPending(int $userId): bool
    {
        $row = dbFetch('SELECT id FROM user_verifications WHERE user_id = ? AND status = ? LIMIT 1', [$userId, 'pending']);
        return $row !== null;
    }

    public static function pendingAll(): array
    {
        return dbFetchAll('SELECT uv.*, users.phone AS user_phone, users.name AS user_name, users.lastname AS user_lastname FROM user_verifications uv LEFT JOIN users ON users.id = uv.user_id WHERE uv.status = ? ORDER BY uv.submitted_at DESC', ['pending']);
    }

    public static function create(int $userId, array $data): array
    {
        dbExecute('INSERT INTO user_verifications (user_id, full_name, address, postal_code, phone, national_id, national_card_back_image, status, admin_notes, submitted_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            $userId,
            $data['full_name'] ?? '',
            $data['address'] ?? '',
            $data['postal_code'] ?? '',
            $data['phone'] ?? '',
            $data['national_id'] ?? '',
            $data['national_card_back_image'] ?? '',
            'pending',
            $data['admin_notes'] ?? '',
            (new DateTime())->format('Y-m-d H:i:s'),
        ]);

        return self::findById((int)Database::get()->lastInsertId());
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

        dbExecute('UPDATE user_verifications SET ' . implode(', ', $fields) . ' WHERE id = ?', $params);
    }
}
