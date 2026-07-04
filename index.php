<?php

$token = "BIHAAB0GVXFTQQXOFHXUCHKWZHRXQMHOVPTGCEMBGUPDSAJUPGISSIKUCEZOGOOX";
$chatId = "b0BsqLd0pPb05eb9ecffb526a9d97e28";

// دریافت Raw JSON از روبیکا
$raw = file_get_contents("php://input");

// تبدیل به JSON خوانا
$text = json_encode(
    json_decode($raw, true),
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);

if ($text === "null") {
    $text = $raw;
}

// محدودیت طول پیام
if (mb_strlen($text) > 3500) {
    $text = mb_substr($text, 0, 3500);
}

$data = [
    "chat_id" => $chatId,
    "text" => $text
];

$ch = curl_init("https://botapi.rubika.ir/v3/$token/sendMessage");

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ),
]);

$response = curl_exec($ch);

curl_close($ch);

// پاسخ به روبیکا
echo json_encode([
    "status" => "OK"
]);