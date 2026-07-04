<?php

require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('فقط درخواست POST مجاز است.', 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$required = ['escrow_id', 'actor_id', 'reason'];
$errors = Validator::required($input, $required);
$errors += Validator::validateInt($input['escrow_id'] ?? null, 'escrow_id');
$errors += Validator::validateInt($input['actor_id'] ?? null, 'actor_id');

if (!Csrf::validate($input['_csrf'] ?? $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    Response::error('توکن CSRF نامعتبر است.', 403);
}

if (!RateLimiter::allow('dispute_' . ($_SERVER['REMOTE_ADDR'] ?? 'guest'), 5, 300)) {
    Response::error('تعداد درخواست‌ها بیش از حد مجاز است. لطفا بعدا تلاش کنید.', 429);
}

try {
    $service = new EscrowService(new ZarinpalGateway(ZARINPAL_MERCHANT_ID, ZARINPAL_CALLBACK_URL, ZARINPAL_SANDBOX));
    $result = $service->disputeEscrow((int)$input['escrow_id'], (int)$input['actor_id'], Validator::sanitizeString($input['reason']));
    Response::success('اختلاف ثبت شد.', ['result' => $result]);
} catch (Throwable $ex) {
    Logger::log('api.dispute.error', ['message' => $ex->getMessage(), 'input' => $input]);
    Response::error('خطا در ثبت اختلاف: ' . $ex->getMessage(), 500);
}
