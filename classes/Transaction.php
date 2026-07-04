<?php

class Transaction
{
    public static function createEscrow(int $buyerId, int $sellerId, float $amount, string $description, ?string $orderId = null, array $metadata = []): array
    {
        $jsonMetadata = json_encode($metadata, JSON_UNESCAPED_UNICODE);

        dbExecute('INSERT INTO escrows (buyer_id, seller_id, amount, fee, status, description, metadata, order_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', [
            $buyerId,
            $sellerId,
            $amount,
            0.0,
            'pending',
            $description,
            $jsonMetadata ?: '{}',
            $orderId,
        ]);

        return self::getById((int)Database::get()->lastInsertId());
    }

    public static function getById(int $id): ?array
    {
        return dbFetch('SELECT * FROM escrows WHERE id = ?', [$id]);
    }

    public static function findByAuthority(string $authority): ?array
    {
        return dbFetch('SELECT * FROM escrows WHERE zarinpal_authority = ?', [$authority]);
    }

    public static function findByOrderId(string $orderId): ?array
    {
        return dbFetch('SELECT * FROM escrows WHERE order_id = ?', [$orderId]);
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
        $fields[] = 'updated_at = CURRENT_TIMESTAMP';

        dbExecute('UPDATE escrows SET ' . implode(', ', $fields) . ' WHERE id = ?', $params);
    }

    public static function all(): array
    {
        return dbFetchAll('SELECT escrows.*, buyer.phone AS buyer_phone, seller.phone AS seller_phone, buyer.name AS buyer_name, seller.name AS seller_name FROM escrows LEFT JOIN users AS buyer ON buyer.id = escrows.buyer_id LEFT JOIN users AS seller ON seller.id = escrows.seller_id ORDER BY escrows.created_at DESC');
    }

    public static function logs(int $escrowId): array
    {
        return dbFetchAll('SELECT * FROM escrow_logs WHERE escrow_id = ? ORDER BY created_at DESC', [$escrowId]);
    }

    public static function logEvent(int $escrowId, string $event, ?int $actorId = null, array $data = []): void
    {
        dbExecute('INSERT INTO escrow_logs (escrow_id, event, actor_id, data) VALUES (?, ?, ?, ?)', [
            $escrowId,
            $event,
            $actorId,
            json_encode($data, JSON_UNESCAPED_UNICODE),
        ]);
    }
}
