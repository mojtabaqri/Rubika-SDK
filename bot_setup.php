<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

$bot = new RubikaBot\Bot(BOT_TOKEN);

$action = $_GET['action'] ?? 'status';

if ($action === 'status') {
    $result = $bot->getMe();
    echo json_encode([
        'ok' => true,
        'bot_token' => BOT_TOKEN !== '' ? 'configured' : 'missing',
        'admin_chat_id' => ADMIN_CHAT_ID,
        'result' => $result,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'webhook') {
    $url = APP_BASE_URL . '/webhook.php';
    $result = $bot->updateWebhook($url, 'ReceiveUpdate');
    echo json_encode([
        'ok' => true,
        'url' => $url,
        'result' => $result,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['ok' => false, 'message' => 'Unknown action'], JSON_UNESCAPED_UNICODE);
