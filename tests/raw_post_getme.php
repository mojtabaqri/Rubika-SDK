<?php
$url = 'https://botapi.rubika.ir/v3/BIHAAB0GVXFTQQXOFHXUCHKWZHRXQMHOVPTGCEMBGUPDSAJUPGISSIKUCEZOGOOX/getMe';
$ch = curl_init($url);
$payload = json_encode([], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

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

echo "POST getMe raw response:\n";
echo $response . "\n";

$decoded = json_decode($response, true);
echo "\nDecoded output:\n";
var_export($decoded);
