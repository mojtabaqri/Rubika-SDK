<?php
require __DIR__ . '/../vendor/autoload.php';

$token = 'BIHAAB0GVXFTQQXOFHXUCHKWZHRXQMHOVPTGCEMBGUPDSAJUPGISSIKUCEZOGOOX';
$chatId = 'b0BsqLd0pPb05eb9ecffb526a9d97e28';
$bot = new RubikaBot\Bot($token);

$messageText = 'تست پیام از SDK';

echo "Sending message to chat_id: {$chatId}\n";
$result = $bot->sendMessage($chatId, $messageText);

echo "Result:\n";
print_r($result);
