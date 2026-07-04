<?php

require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('فقط درخواست POST مجاز است.', 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$required = ['escrow_id', 'actor_id'];
$errors = Validator::required($input, $required);
$errors += Validator::validateInt($input['escrow_id'] ?? null, 'escrow_id');
$errors += Validator::validateInt($input['actor_id'] ?? null, 'actor_id');

if (!Csrf::validate($input['_csrf'] ?? $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    Response::error('توکن CSRF نامعتبر است.', 403);
}

try {
    $service = new EscrowService(new ZarinpalGateway(ZARINPAL_MERCHANT_ID, ZARINPAL_CALLBACK_URL, ZARINPAL_SANDBOX));
    $result = $service->releaseFunds((int)$input['escrow_id'], (int)$input['actor_id']);
    Response::success('آزادسازی وجه انجام شد.', ['result' => $result]);
} catch (Throwable $ex) {
    Logger::log('api.release_funds.error', ['message' => $ex->getMessage(), 'input' => $input]);
    Response::error('خطا در آزادسازی وجه: ' . $ex->getMessage(), 500);
}
