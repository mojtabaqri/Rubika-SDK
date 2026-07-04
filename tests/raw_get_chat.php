<?php
$token = 'BIHAAB0GVXFTQQXOFHXUCHKWZHRXQMHOVPTGCEMBGUPDSAJUPGISSIKUCEZOGOOX';
$chatId = 'b0BsqLd0pPb05eb9ecffb526a9d97e28';
$url = "https://botapi.rubika.ir/v3/{$token}/getChat";
$params = [
    'chat_id' => $chatId,
];

$ch = curl_init($url);
$data = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $data,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
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

echo "Raw getChat response:\n";
echo $response . "\n";

echo "Decoded:\n";
print_r(json_decode($response, true));
