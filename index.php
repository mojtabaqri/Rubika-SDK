<?php

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
  
// Event handlers
$bot->dispatcher()
    ->onNewMessage(function (Update $update) use ($bot) {
        if (!$update->new_message) {
            return;
        }

        $text = trim($update->new_message->text ?? '');
        
        if ($text === '/start') {
            $bot->sendMessage($update->chat_id, "سلام! به ربات خوش آمدی 👋");
        } else {
            $bot->sendMessage($update->chat_id, "سلام دنیا! پیام شما: {$text}");
        }
    });

// Process webhook
$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput, true);

if (is_array($payload) && !empty($payload)) {
    $bot->handleWebhook($payload);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok' => true]);



$request = [
    'time'    => date('Y-m-d H:i:s'),
    'method'  => $_SERVER['REQUEST_METHOD'],
    'headers' => getallheaders(),
    'body'    => file_get_contents('php://input')
];

file_put_contents(
    __DIR__ . '/requests.log',
    json_encode($request, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL . str_repeat('-', 80) . PHP_EOL,
    FILE_APPEND
);

http_response_code(200);
echo json_encode([
    'status' => 'ok'
]);