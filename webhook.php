<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput, true);

if (!is_array($payload) || empty($payload)) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true]);
    exit;
}

$chatId = ADMIN_CHAT_ID;
$bot = new RubikaBot\Bot(BOT_TOKEN);

function botSend(string $chatId, string $text): void
{
    global $bot;
    $bot->sendMessage($chatId, $text);
}

$update = $payload;
$message = $update['new_message'] ?? $update['message'] ?? null;
if (!is_array($message)) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true]);
    exit;
}

$rubikaId = $message['sender_id'] ?? $message['user_id'] ?? null;
$text = trim($message['text'] ?? '');

if ($rubikaId === null) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true]);
    exit;
}

$user = User::findByRubikaId($rubikaId);
if (!$user) {
    $user = User::create($rubikaId);
    User::setState($user['id'], 'register_phone');
    botSend($rubikaId, "سلام! خوش آمدید به سامانه‌ی وام. لطفاً شماره موبایل خود را وارد کنید:");
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true]);
    exit;
}

$kycService = new KYCService();

if (stripos($text, '/start') !== false || stripos($text, 'شروع') !== false) {
    User::setState($user['id'], 'register_phone');
    botSend($rubikaId, "سلام! برای شروع لطفاً شماره موبایل خود را ارسال کنید. اگر قبلاً ثبت‌نام کرده‌اید، اطلاعات شما بازیابی خواهد شد.");
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true]);
    exit;
}

$currentStep = $user['current_step'] ?? 'none';

function askNextStep(array $user): void
{
    global $rubikaId;
    $step = $user['current_step'];
    switch ($step) {
        case 'register_phone':
            botSend($rubikaId, 'لطفاً شماره موبایل خود را وارد کنید:');
            break;
        case 'register_full_name':
            botSend($rubikaId, 'نام و نام خانوادگی خود را وارد کنید:');
            break;
        case 'register_address':
            botSend($rubikaId, 'آدرس کامل خود را وارد کنید:');
            break;
        case 'register_postal_code':
            botSend($rubikaId, 'کد پستی ۱۰ رقمی خود را وارد کنید:');
            break;
        case 'register_id_back':
            botSend($rubikaId, 'لطفاً لینک یا توضیح پشت کارت ملی خود را ارسال کنید:');
            break;
        case 'ad_type':
            botSend($rubikaId, 'نوع آگهی را انتخاب کنید: request برای درخواست وام یا offer برای ارائه وام');
            break;
        case 'ad_amount':
            botSend($rubikaId, 'مبلغ را به عدد وارد کنید:');
            break;
        case 'ad_description':
            botSend($rubikaId, 'توضیحات آگهی را بنویسید:');
            break;
        default:
            botSend($rubikaId, 'برای ثبت آگهی عبارت "ثبت آگهی" و برای تکمیل KYC عبارت "احراز هویت" را وارد کنید.');
            break;
    }
}

function notifyAdmin(string $text): void
{
    global $bot;
    if (ADMIN_CHAT_ID !== '') {
        $bot->sendMessage(ADMIN_CHAT_ID, $text);
    }
}

if ($currentStep !== 'none' && $currentStep !== null) {
    switch ($currentStep) {
        case 'register_phone':
            User::setPendingData($user['id'], ['phone' => $text]);
            User::setState($user['id'], 'register_full_name');
            botSend($rubikaId, 'نام و نام خانوادگی خود را وارد کنید:');
            break;
        case 'register_full_name':
            User::setPendingData($user['id'], ['full_name' => $text]);
            User::setState($user['id'], 'register_address');
            botSend($rubikaId, 'آدرس کامل خود را وارد کنید:');
            break;
        case 'register_address':
            User::setPendingData($user['id'], ['address' => $text]);
            User::setState($user['id'], 'register_postal_code');
            botSend($rubikaId, 'کد پستی ۱۰ رقمی خود را وارد کنید:');
            break;
        case 'register_postal_code':
            User::setPendingData($user['id'], ['postal_code' => $text]);
            User::setState($user['id'], 'register_id_back');
            botSend($rubikaId, 'لطفاً لینک یا توضیح پشت کارت ملی خود را ارسال کنید:');
            break;
        case 'register_id_back':
            User::setPendingData($user['id'], ['national_card_back_image' => $text]);
            $updated = User::findById($user['id']);
            $pending = json_decode($updated['pending_data'] ?? '{}', true) ?: [];
            $kycService = new KYCService();
            $kycService->submitVerificationFromText($user['id'], [
                'full_name' => $pending['full_name'] ?? '',
                'address' => $pending['address'] ?? '',
                'postal_code' => $pending['postal_code'] ?? '',
                'phone' => $pending['phone'] ?? $updated['phone'],
                'national_card_back_image' => $pending['national_card_back_image'] ?? '',
            ]);
            User::clearStep($user['id']);
            botSend($rubikaId, 'درخواست احراز هویت شما ثبت شد. منتظر تایید مدیر باشید.');
            notifyAdmin("یک درخواست KYC جدید ثبت شد. کاربر: {$pending['full_name']} ({$pending['phone']}).");
            break;
        case 'ad_type':
            $type = strtolower($text) === 'offer' ? 'offer' : 'request';
            User::setPendingData($user['id'], ['ad_type' => $type]);
            User::setState($user['id'], 'ad_amount');
            botSend($rubikaId, 'مبلغ را به عدد وارد کنید:');
            break;
        case 'ad_amount':
            $amount = floatval(str_replace(',', '.', $text));
            if ($amount <= 0) {
                botSend($rubikaId, 'لطفاً مبلغ معتبر وارد کنید:');
                break;
            }
            User::setPendingData($user['id'], ['ad_amount' => $amount]);
            User::setState($user['id'], 'ad_description');
            botSend($rubikaId, 'توضیحات آگهی را بنویسید:');
            break;
        case 'ad_description':
            $pending = json_decode($user['pending_data'] ?? '{}', true) ?: [];
            $type = $pending['ad_type'] ?? 'request';
            $amount = floatval($pending['ad_amount'] ?? 0);
            $description = $text;
            $ad = Ad::create($user['id'], $type, $amount, $description);
            User::clearStep($user['id']);
            botSend($rubikaId, "آگهی شما ثبت شد و پس از تایید مدیر منتشر خواهد شد. کد آگهی: {$ad['loan_code']}");
            notifyAdmin("آگهی جدید برای بررسی ثبت شد. کد: {$ad['loan_code']}");
            break;
        default:
            askNextStep($user);
            break;
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true]);
    exit;
}

$normalized = strtolower($text);
if (strpos($normalized, 'ثبت آگهی') !== false || strpos($normalized, 'آگهی') !== false) {
    if (!$kycService->isUserVerified($user['id'])) {
        $link = APP_BASE_URL . '/kyc.php';
        botSend($rubikaId, "برای ثبت آگهی ابتدا باید احراز هویت شما تایید شود. لطفاً از طریق سایت KYC خود را کامل کنید: $link");
    } else {
        User::setState($user['id'], 'ad_type');
        askNextStep($user);
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true]);
    exit;
}

if (strpos($normalized, 'احراز') !== false || strpos($normalized, 'kyc') !== false) {
    User::setState($user['id'], 'register_phone');
    botSend($rubikaId, 'برای شروع احراز هویت شماره موبایل خود را وارد کنید:');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true]);
    exit;
}

$greeting = "سلام.
برای ثبت نام و احراز هویت عبارت 'احراز هویت' را ارسال کنید.
برای ثبت آگهی عبارت 'ثبت آگهی' را ارسال کنید.
وضعیت فعلی شما: " . Helpers::formatStatus($user['status']) . ".";
botSend($rubikaId, $greeting);

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok' => true]);
exit;
