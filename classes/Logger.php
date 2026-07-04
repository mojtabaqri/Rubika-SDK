<?php

class Logger
{
    private const LOG_FILE = __DIR__ . '/../data/escrow.log';

    public static function log(string $event, array $meta = []): void
    {
        $payload = [
            'timestamp' => (new DateTime())->format('Y-m-d H:i:s'),
            'event' => $event,
            'meta' => $meta,
        ];

        $line = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($line === false) {
            return;
        }

        file_put_contents(self::LOG_FILE, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
