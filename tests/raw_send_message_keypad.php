<?php
$token = 'BIHAAB0GVXFTQQXOFHXUCHKWZHRXQMHOVPTGCEMBGUPDSAJUPGISSIKUCEZOGOOX';
$chatId = 'b0BsqLd0pPb05eb9ecffb526a9d97e28';
$url = "https://botapi.rubika.ir/v3/{$token}/sendMessage";

$data = [
    'chat_id' => $chatId,
    'text' => 'Welcome',
    'chat_keypad_type' => 'New',
    'chat_keypad' => [
        'rows' => [
            [
                'buttons' => [
                    [
                        'id' => '100',
                        'type' => 'Simple',
                        'button_text' => 'Add Account',
                    ],
                ],
            ],
            [
                'buttons' => [
                    [
                        'id' => '101',
                        'type' => 'Simple',
                        'button_text' => 'Edit Account',
                    ],
                    [
                        'id' => '102',
                        'type' => 'Simple',
                        'button_text' => 'Remove Account',
                    ],
                ],
            ],
        ],
        'resize_keyboard' => true,
        'one_time_keyboard' => false,
    ],
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

echo "Raw sendMessage keypad response:\n";
echo $response . "\n";

echo "Decoded:\n";
print_r(json_decode($response, true));
