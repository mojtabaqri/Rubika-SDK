<?php

require __DIR__ . '/../vendor/autoload.php';

$token = 'BIHAAB0GVXFTQQXOFHXUCHKWZHRXQMHOVPTGCEMBGUPDSAJUPGISSIKUCEZOGOOX';
$bot = new RubikaBot\Bot($token);

echo "Running Rubika API getMe test...\n";
$result = $bot->getMe();
print_r($result);

echo "\nTesting local RequestLogger with sample payload...\n";
$sample = [
    'method' => 'POST',
    'headers' => ['Content-Type' => 'application/json'],
    'raw_input' => '{"update_id":123}',
    'decoded_payload' => ['update_id' => 123],
];
RubikaBot\saveIncomingRequest($sample);

echo "Saved sample payload to req.txt\n";
echo file_get_contents(__DIR__ . '/../req.txt');
