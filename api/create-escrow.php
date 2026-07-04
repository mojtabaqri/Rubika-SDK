<?php

require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('فقط درخواست POST مجاز است.', 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$required = ['buyer_id', 'seller_id', 'amount', 'description'];
$errors = Validator::required($input, $required);
$errors += Validator::validateInt($input['buyer_id'] ?? null, 'buyer_id');
$errors += Validator::validateInt($input['seller_id'] ?? null, 'seller_id');
$errors += Validator::validateAmount($input['amount'] ?? null, 'amount');
$errors += Validator::validateJsonString($input['metadata'] ?? '', 'metadata');

if (!Csrf::validate($input['_csrf'] ?? $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    Response::error('توکن CSRF نامعتبر است.', 403);
}

if (!RateLimiter::allow('create_escrow_' . ($_SERVER['REMOTE_ADDR'] ?? 'guest'), 10, 60)) {
    Response::error('تعداد درخواست‌ها بیش از حد مجاز است. لطفا بعدا تلاش کنید.', 429);
}

if (!empty($errors)) {
    Response::error('اطلاعات ورودی نامعتبر است.', 422, $errors);
}

try {
    $metadata = [];
    if (!empty($input['metadata'])) {
        $metadata = is_string($input['metadata']) ? json_decode($input['metadata'], true) : $input['metadata'];
    }

    $service = new EscrowService(new ZarinpalGateway(ZARINPAL_MERCHANT_ID, ZARINPAL_CALLBACK_URL, ZARINPAL_SANDBOX));
    $escrow = $service->createEscrow(
        (int)$input['buyer_id'],
        (int)$input['seller_id'],
        (float)$input['amount'],
        Validator::sanitizeString($input['description']),
        $input['order_id'] ?? null,
        is_array($metadata) ? $metadata : []
    );

    $payment = $service->requestPayment((int)$escrow['id']);

    Response::success('Escrow ایجاد شد و درخواست پرداخت ارسال گردید.', [
        'escrow' => $escrow,
        'payment' => $payment,
    ]);
} catch (Throwable $ex) {
    Logger::log('api.create_escrow.error', ['message' => $ex->getMessage(), 'input' => $input]);
    Response::error('خطا در ایجاد Escrow: ' . $ex->getMessage(), 500);
}
