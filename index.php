<?php

$debugEnabled = true; // set to false to disable all logging
define('RUBIKA_DEBUG_ENABLED', $debugEnabled);

require_once __DIR__ . '/src/DebugLogger.php';
require_once __DIR__ . '/src/Models/Model.php';
require_once __DIR__ . '/src/Models/Models.php';
require_once __DIR__ . '/src/Enums/Enums.php';
require_once __DIR__ . '/src/Handlers/Dispatcher.php';
require_once __DIR__ . '/src/RubikaClient.php';
require_once __DIR__ . '/src/Bot.php';

if (RUBIKA_DEBUG_ENABLED) {
    set_error_handler(function ($severity, $message, $file, $line) {
        \RubikaBot\debugLog('PHP error', array(
            'severity' => $severity,
            'message' => $message,
            'file' => $file,
            'line' => $line,
        ), true);
        return true;
    });

    set_exception_handler(function ($exception) {
        \RubikaBot\debugLog('Unhandled exception', array(
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ), true);
    });

    register_shutdown_function(function () {
        $error = error_get_last();
        if ($error !== null) {
            \RubikaBot\debugLog('Fatal error', array(
                'type' => $error['type'],
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
            ), true);
        }
    });
}

use RubikaBot\Bot;
use RubikaBot\Models\Update;

$token = 'BIHAAB0GVXFTQQXOFHXUCHKWZHRXQMHOVPTGCEMBGUPDSAJUPGISSIKUCEZOGOOX';
$bot = new Bot($token);

$bot->dispatcher()->onNewMessage(function (Update $update) use ($bot) {
    if (!$update->new_message) {
        return;
    }

    $text = trim($update->new_message->text ?? '');

    if ($text === '/start') {
        $bot->sendMessage($update->chat_id, "سلام! به ربات خوش آمدی 👋");
        return;
    }

    $bot->sendMessage($update->chat_id, "سلام دنیا! پیام شما: {$text}");
});

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
$requestHeaders = function_exists('getallheaders') ? getallheaders() : array();
$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput, true);

if (!is_array($payload)) {
    $payload = array();
}

\RubikaBot\debugLog('Incoming webhook request', array(
    'method' => $requestMethod,
    'headers' => $requestHeaders,
    'raw_input' => $rawInput,
    'decoded_payload' => $payload,
));

try {
    if (is_array($payload) && !empty($payload)) {
        $bot->handleWebhook($payload);
    }
} catch (\Throwable $e) {
    \RubikaBot\debugLog('Webhook processing error', array(
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ));
    throw $e;
}

$responsePayload = ['ok' => true];
\RubikaBot\debugLog('Outgoing webhook response', array(
    'response' => $responsePayload,
));

header('Content-Type: application/json; charset=utf-8');
echo json_encode($responsePayload);



 