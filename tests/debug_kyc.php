<?php
require __DIR__ . '/../config.php';

$db = Database::get();
$db->exec('DELETE FROM user_verifications WHERE user_id = 999999');
$db->exec('DELETE FROM users WHERE id = 999999');
$db->exec('INSERT INTO users (id, phone, status, trust_score, current_step) VALUES (999999, "09123456789", "pending", 0, "none")');

$svc = new KYCService();
$path = __DIR__ . '/temp_kyc.png';
file_put_contents($path, 'fake-image-content');
$files = [
    'national_card_back_image' => [
        'name' => 'back.png',
        'type' => 'image/png',
        'tmp_name' => $path,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($path),
    ],
];

$result = $svc->submitKYC(999999, [
    'full_name' => 'Test User',
    'address' => 'Tehran Test',
    'postal_code' => '1234567890',
    'phone' => '09123456789',
    'national_id' => '0012345678',
], $files);

var_export($result);
echo PHP_EOL;
