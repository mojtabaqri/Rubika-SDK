<?php
$token = 'BIHAAB0GVXFTQQXOFHXUCHKWZHRXQMHOVPTGCEMBGUPDSAJUPGISSIKUCEZOGOOX';
$chatId = 'b0BsqLd0pPb05eb9ecffb526a9d97e28';
$url = "https://botapi.rubika.ir/v3/{$token}/sendMessage";

$data = [
    'chat_id' => $chatId,
    'text' => 'Hello user, this is my text',
];

$payload = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 20,
]);

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($response === false) {
    echo "cURL error: {$error}\n";
    exit(1);
}

echo "Raw sendMessage JSON response:\n";
echo $response . "\n";

echo "Decoded:\n";
print_r(json_decode($response, true));
