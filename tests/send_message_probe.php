<?php
$token = 'BIHAAB0GVXFTQQXOFHXUCHKWZHRXQMHOVPTGCEMBGUPDSAJUPGISSIKUCEZOGOOX';
$chatId = 'b0BsqLd0pPb05eb9ecffb526a9d97e28';
$url = "https://botapi.rubika.ir/v3/{$token}/sendMessage";

$paramsList = [
    'chat_id_text' => ['chat_id' => $chatId, 'text' => 'تست ارسال پیام فرم'],
    'chat_id_text_en' => ['chat_id' => $chatId, 'text' => 'test send message'],
    'receiver_id_text' => ['receiver_id' => $chatId, 'text' => 'تست receiver_id'],
    'user_id_text' => ['user_id' => $chatId, 'text' => 'تست user_id'],
    'chat_id_body' => ['chat_id' => $chatId, 'body' => 'تست body'],
    'chat_id_message' => ['chat_id' => $chatId, 'message' => 'تست message'],
];

foreach ($paramsList as $label => $params) {
    echo "=== {$label} ===\n";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $params,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
    ]);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    if ($response === false) {
        echo "cURL error: {$error}\n";
    } else {
        echo "Response: {$response}\n";
    }
    echo "\n";
}
