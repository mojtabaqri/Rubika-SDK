<?php
require __DIR__ . '/../config.php';

$db = Database::get();
$db->exec("DELETE FROM user_verifications WHERE user_id = 999999");
$db->exec("DELETE FROM users WHERE id = 999999");
$db->exec("INSERT INTO users (id, phone, status, trust_score, current_step) VALUES (999999, '09123456789', 'pending', 0, 'none')");

$path = __DIR__ . '/temp_kyc.png';
file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAACklEQVR4nGMAAIAAeIhvAAAAAElFTkSuQmCC'));

$files = [
    'national_card_back_image' => [
        'name' => 'back.png',
        'type' => 'image/png',
        'tmp_name' => $path,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($path),
    ],
];

$service = new KYCService();

$first = $service->submitKYC(999999, [
    'full_name' => 'Test User',
    'address' => 'Tehran Test',
    'postal_code' => '1234567890',
    'phone' => '09123456789',
    'national_id' => '1000000001',
], $files);

if (!is_array($first) || !isset($first['id'])) {
    fwrite(STDERR, "FAIL:first_submit\n");
    exit(1);
}

$second = $service->submitKYC(999999, [
    'full_name' => 'Test User 2',
    'address' => 'Tehran Test 2',
    'postal_code' => '1234567890',
    'phone' => '09123456789',
    'national_id' => '1000000001',
], $files);

if (!is_array($second) || !isset($second['error']) || strpos($second['error'], 'در حال بررسی') === false) {
    fwrite(STDERR, "FAIL:duplicate_block\n");
    exit(1);
}

$pending = $service->getPendingVerifications();
if (!is_array($pending) || count($pending) < 1) {
    fwrite(STDERR, "FAIL:pending_list\n");
    exit(1);
}

$review = $service->reviewVerification((int)$first['id'], 'approved', 'ok', 1);
if (!is_array($review) || ($review['success'] ?? false) !== true) {
    fwrite(STDERR, "FAIL:review\n");
    exit(1);
}

if (!$service->isUserVerified(999999)) {
    fwrite(STDERR, "FAIL:verified_user\n");
    exit(1);
}

$db->exec("DELETE FROM user_verifications WHERE user_id = 999999");
$db->exec("DELETE FROM users WHERE id = 999999");
@unlink($path);

echo "PASS\n";
