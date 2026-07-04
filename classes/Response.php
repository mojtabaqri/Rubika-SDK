<?php

class Response
{
    public static function json(array $payload, int $statusCode = 200): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code($statusCode);
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function success(string $message, array $data = []): void
    {
        self::json(array_merge(['success' => true, 'message' => $message], $data), 200);
    }

    public static function error(string $message, int $statusCode = 400, array $errors = []): void
    {
        $payload = ['success' => false, 'message' => $message];
        if (!empty($errors)) {
            $payload['errors'] = $errors;
        }
        self::json($payload, $statusCode);
    }
}
