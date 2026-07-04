<?php

class ZarinpalGateway
{
    private string $merchantId;
    private string $callbackUrl;
    private bool $sandbox;

    public function __construct(string $merchantId, string $callbackUrl, bool $sandbox = false)
    {
        $this->merchantId = $merchantId;
        $this->callbackUrl = $callbackUrl;
        $this->sandbox = $sandbox;
    }

    public function paymentRequest(float $amount, string $description, ?string $orderId = null, array $metadata = []): array
    {
        $url = $this->getApiUrl('/payment/request.json');
        $payload = [
            'merchant_id' => $this->merchantId,
            'amount' => (int)$amount,
            'callback_url' => $this->callbackUrl,
            'description' => $description,
            'metadata' => $metadata,
        ];
        if ($orderId !== null) {
            $payload['order_id'] = $orderId;
        }

        $response = $this->sendJsonRequest($url, $payload);

        if (!isset($response['data']['authority']) || intval($response['data']['code'] ?? 0) !== 100) {
            throw new RuntimeException('خطا در ارسال درخواست پرداخت به زرین‌پال: ' . ($response['errors'][0]['message'] ?? json_encode($response, JSON_UNESCAPED_UNICODE)));
        }

        return [
            'authority' => $response['data']['authority'],
            'payment_url' => $this->buildPaymentUrl($response['data']['authority']),
        ];
    }

    public function paymentVerify(string $authority, float $amount): array
    {
        $url = $this->getApiUrl('/payment/verify.json');
        $payload = [
            'merchant_id' => $this->merchantId,
            'amount' => (int)$amount,
            'authority' => $authority,
        ];

        $response = $this->sendJsonRequest($url, $payload);

        if (intval($response['data']['code'] ?? 0) !== 100) {
            throw new RuntimeException('خطا در تایید پرداخت زرین‌پال: ' . ($response['errors'][0]['message'] ?? json_encode($response, JSON_UNESCAPED_UNICODE)));
        }

        return [
            'ref_id' => $response['data']['ref_id'] ?? null,
            'amount' => $response['data']['amount'] ?? null,
        ];
    }

    private function getApiUrl(string $path): string
    {
        $host = $this->sandbox ? 'https://sandbox.zarinpal.com/pg/v4' : 'https://api.zarinpal.com/pg/v4';
        return $host . $path;
    }

    private function buildPaymentUrl(string $authority): string
    {
        $host = $this->sandbox ? 'https://sandbox.zarinpal.com/pg/StartPay/' : 'https://www.zarinpal.com/pg/StartPay/';
        return $host . urlencode($authority);
    }

    private function sendJsonRequest(string $url, array $payload): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $result = curl_exec($ch);
        if ($result === false) {
            $message = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('خطای اتصال به زرین‌پال: ' . $message);
        }

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($result, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('پاسخ نامعتبر زرین‌پال دریافت شد.');
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new RuntimeException('پاسخ HTTP ناموفق از زرین‌پال: ' . $result);
        }

        return $decoded;
    }
}
