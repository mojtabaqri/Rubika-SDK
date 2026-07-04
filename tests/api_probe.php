<?php
$token = 'BIHAAB0GVXFTQQXOFHXUCHKWZHRXQMHOVPTGCEMBGUPDSAJUPGISSIKUCEZOGOOX';
$url = "https://botapi.rubika.ir/v3/{$token}/getMe";

function dumpResult($label, $response, $error = null) {
    echo "=== {$label} ===\n";
    if ($error) {
        echo "ERROR: {$error}\n";
    }
    echo "RESPONSE:\n";
    var_export($response);
    echo "\n\n";
}

// GET request
$response = @file_get_contents($url);
dumpResult('GET', $response === false ? 'failed' : $response, $response === false ? error_get_last()['message'] ?? null : null);

// POST JSON request
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
dumpResult('POST JSON', $response, $error);

// POST form-encoded request
$ch = curl_init($url);
$form = http_build_query([]);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $form,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 20,
]);
$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);
dumpResult('POST FORM', $response, $error);

// GET with query string
$response = @file_get_contents($url . '?test=1');
dumpResult('GET QUERY', $response === false ? 'failed' : $response, $response === false ? error_get_last()['message'] ?? null : null);
