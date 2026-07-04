<?php

require_once __DIR__ . '/../config.php';

$input = array_merge($_GET, $_POST);

try {
    $service = new EscrowService(new ZarinpalGateway(ZARINPAL_MERCHANT_ID, ZARINPAL_CALLBACK_URL, ZARINPAL_SANDBOX));
    $result = $service->handleCallback($input);
    Response::success('بازگشت پرداخت دریافت شد.', ['result' => $result]);
} catch (Throwable $ex) {
    Logger::log('api.payment_callback.error', ['message' => $ex->getMessage(), 'input' => $input]);
    Response::error('خطا در تایید پرداخت: ' . $ex->getMessage(), 500);
}
