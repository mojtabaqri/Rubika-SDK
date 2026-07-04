<?php
require __DIR__ . '/../vendor/autoload.php';

echo "Direct Raw getMe request:\n";
$url = 'https://botapi.rubika.ir/v3/BIHAAB0GVXFTQQXOFHXUCHKWZHRXQMHOVPTGCEMBGUPDSAJUPGISSIKUCEZOGOOX/getMe';
$response = file_get_contents($url);
if ($response === false) {
    echo "Failed to fetch URL: {$url}\n";
    exit(1);
}
echo $response . "\n";
