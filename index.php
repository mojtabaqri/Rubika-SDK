<?php

function saveIncomingData($data)
{
    $logFile = __DIR__ . '/log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $entry = "[{$timestamp}]" . PHP_EOL . var_export($data, true) . PHP_EOL . '---' . PHP_EOL;
    file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}

require_once __DIR__ . '/src/Models/Model.php';
require_once __DIR__ . '/src/Models/Models.php';
require_once __DIR__ . '/src/Enums/Enums.php';
require_once __DIR__ . '/src/Handlers/Dispatcher.php';
require_once __DIR__ . '/src/RubikaClient.php';
require_once __DIR__ . '/src/Bot.php';

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

saveIncomingData(array(
    'method' => $requestMethod,
    'headers' => $requestHeaders,
    'raw_input' => $rawInput,
    'decoded_payload' => $payload,
));

if (!empty($payload)) {
    $bot->handleWebhook($payload);
}

$responsePayload = ['ok' => true];
saveIncomingData(array(
    'response' => $responsePayload,
));

header('Content-Type: application/json; charset=utf-8');
echo json_encode($responsePayload);



 