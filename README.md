# Rubika Bot SDK

یک کتابخانه‌ی سبک و حرفه‌ای برای ساخت ربات‌های روبیکا با PHP. این SDK برای اتصال به API روبیکا، مدیریت webhook، پردازش رویدادها و ساخت کیپدهای تعاملی طراحی شده است.

## ویژگی‌های اصلی

- ✅ پشتیبانی از رویدادهای روبیکا مانند پیام جدید، پیام ویرایش‌شده، حذف پیام و شروع/توقف ربات
- ✅ کلاس‌های مدل‌محور برای تبدیل داده‌های دریافتی به اشیاء شی‌گرا
- ✅ پشتیبانی از Enum برای انواع رویداد، دکمه، نوع چت و ...
- ✅ سازنده‌های آماده برای ساخت دکمه و کیپد
- ✅ امکان ثبت webhook و پردازش درخواست‌های دریافتی
- ✅ پشتیبانی از متدهای متنوع API مانند ارسال پیام، فایل، موقعیت، تماس، poll و مدیریت چت
- ✅ مناسب برای ساخت ربات‌های ساده تا متوسط با معماری تمیز و قابل توسعه

## ساختار پروژه

```text
src/
├── Bot.php                    # لایه‌ی بالایی SDK برای مدیریت ربات
├── RubikaClient.php          # کلاینت اصلی API روبیکا
├── Builders/
│   └── KeypadBuilder.php     # Builder برای دکمه‌ها و کیپدها
├── Enums/
│   └── Enums.php             # Enumهای موردنیاز SDK
├── Handlers/
│   └── Dispatcher.php        # سیستم رویداد و dispatch
├── Models/
│   ├── Model.php             # کلاس پایه برای هیدرات کردن مدل‌ها
│   └── Models.php            # مدل‌های مختلف داده‌های روبیکا
└── index.php                 # نمونه‌ی ورودی webhook
```

## پیش‌نیازها

- PHP 7.0+ (ترجیحاً نسخه‌ی جدیدتر)
- افزونه‌ی cURL برای PHP
- دسترسی به سرور وب با امکان ثبت webhook

## نصب و راه‌اندازی

### ۱) کپی یا کلون کردن پروژه

```bash
git clone https://github.com/mojtabaqri/Rubika-SDK.git
cd Rubika-SDK
```

### ۲) بارگذاری کلاس‌ها

در فایل入口‌ی پروژه، کلاس‌های لازم را با require یا include بارگذاری کنید:

```php
require_once __DIR__ . '/src/Models/Model.php';
require_once __DIR__ . '/src/Models/Models.php';
require_once __DIR__ . '/src/Enums/Enums.php';
require_once __DIR__ . '/src/Handlers/Dispatcher.php';
require_once __DIR__ . '/src/RubikaClient.php';
require_once __DIR__ . '/src/Bot.php';
```

### ۳) ساخت نمونه‌ی ربات

```php
use RubikaBot\Bot;

$token = 'YOUR_BOT_TOKEN';
$bot = new Bot($token);
```

## تنظیم Webhook

برای دریافت پیام‌ها از روبیکا، باید webhook را روی آدرس مناسب ثبت کنید:

```php
$bot->updateWebhook('https://your-domain.com/index.php', 'ReceiveUpdate');
```

> این کتابخانه برای دریافت رویدادها بر اساس webhook طراحی شده است و از Long Polling پشتیبانی نمی‌کند.

## مثال ساده و سریع

```php
use RubikaBot\Bot;
use RubikaBot\Models\Update;

require_once __DIR__ . '/src/Models/Model.php';
require_once __DIR__ . '/src/Models/Models.php';
require_once __DIR__ . '/src/Enums/Enums.php';
require_once __DIR__ . '/src/Handlers/Dispatcher.php';
require_once __DIR__ . '/src/RubikaClient.php';
require_once __DIR__ . '/src/Bot.php';

$token = 'YOUR_BOT_TOKEN';
$bot = new Bot($token);

$bot->dispatcher()
    ->onNewMessage(function (Update $update) use ($bot) {
        if (!$update->new_message) {
            return;
        }

        $text = trim($update->new_message->text ?? '');

        if ($text === '/start') {
            $bot->sendMessage($update->chat_id, 'سلام! به ربات خوش آمدی 👋');
        } else {
            $bot->sendMessage($update->chat_id, "شما گفتید: {$text}");
        }
    });

$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput, true);

if (is_array($payload) && !empty($payload)) {
    $bot->handleWebhook($payload);
}
```

## مدیریت رویدادها

SDK یک dispatcher داخلی دارد که رویدادهای مختلف را برای شما مدیریت می‌کند:

```php
$bot->dispatcher()
    ->onNewMessage(function (Update $update) use ($bot) {
        // پیام جدید
    })
    ->onUpdatedMessage(function (Update $update) use ($bot) {
        // پیام ویرایش‌شده
    })
    ->onRemovedMessage(function (Update $update) use ($bot) {
        // پیام حذف‌شده
    })
    ->onStartedBot(function (Update $update) use ($bot) {
        // زمانی که ربات شروع شود
    })
    ->onStoppedBot(function (Update $update) use ($bot) {
        // زمانی که ربات متوقف شود
    });
```

## ارسال پیام‌ها

### ارسال متن ساده

```php
$bot->sendMessage($chatId, 'سلام دنیا');
```

### ارسال پیام با گزینه‌های اضافی

```php
$bot->sendMessage($chatId, 'این یک پیام با گزینه است', [
    'disable_web_page_preview' => true,
]);
```

## ساخت کیپد و دکمه‌ها

این کتابخانه برای ساخت کیپدهای تعاملی، دکمه‌های ساده، لینک، موقعیت و سایر انواع دکمه‌ها فراهم شده است.

```php
use RubikaBot\Builders\Button;
use RubikaBot\Builders\KeypadRow;
use RubikaBot\Builders\Keypad;

$row = new KeypadRow();
$row->addButtons(
    Button::simple('btn_1', 'گزینه ۱'),
    Button::simple('btn_2', 'گزینه ۲')
);

$keypad = new Keypad();
$keypad->addRow($row)
    ->resizeKeyboard(true)
    ->oneTimeKeyboard(true);

$bot->sendMessage($chatId, 'یکی را انتخاب کنید', [
    'chat_keypad' => $keypad->toArray(),
    'chat_keypad_type' => 'New',
]);
```

### انواع دکمه‌های پشتیبانی‌شده

```php
Button::simple($id, $text);
Button::link($id, $text, $url);
Button::location($id, $text);
Button::askPhoneNumber($id, $text);
Button::askLocation($id, $text);
```

## کار با مدل‌ها

تمام داده‌های دریافتی به صورت خودکار به مدل‌های مربوطه تبدیل می‌شوند. برای مثال:

```php
use RubikaBot\Models\Update;

$update = Update::fromArray($payload);

if ($update && $update->new_message) {
    $text = $update->new_message->text ?? '';
    $chatId = $update->chat_id;
}
```

این قابلیت باعث می‌شود دسترسی به داده‌های ورودی بسیار خواناتر و ایمن‌تر شود.

## استفاده از Enumها

SDK برای دسته‌بندی نوع رویدادها و دکمه‌ها از Enumها استفاده می‌کند:

```php
use RubikaBot\Enums\UpdateType;
use RubikaBot\Enums\ButtonType;

$eventType = UpdateType::NEW_MESSAGE;
$buttonType = ButtonType::SIMPLE;
```

## متدهای اصلی کلاینت

کلاس اصلی ربات از طریق متدهای زیر با API روبیکا در ارتباط است:

- `getMe()`
- `sendMessage($params, $text = null, array $options = array())`
- `sendPoll(array $params)`
- `sendLocation(array $params)`
- `sendContact(array $params)`
- `getChat(array $params)`
- `getUpdates(array $params = array())`
- `forwardMessage(array $params)`
- `editMessageText(array $params)`
- `editMessageKeypad(array $params)`
- `deleteMessage(array $params)`
- `setCommands(array $params)`
- `updateWebhook($url, $type = 'ReceiveUpdate')`
- `editChatKeypad(array $params)`
- `getFile(array $params)`
- `sendFile(array $params)`
- `requestSendFile(array $params)`
- `banChatMember(array $params)`
- `unbanChatMember(array $params)`
- `uploadFile($uploadUrl, $filePath)`

## نکات مهم

1. برای استفاده از SDK، باید webhook را روی سرورتان فعال کنید.
2. داده‌های ورودى به‌صورت خودکار به مدل‌های مناسب تبدیل می‌شوند.
3. ساختار کد به‌صورت شی‌گرا و قابل گسترش طراحی شده است.
4. این SDK برای استفاده‌ی ساده و سریع در پروژه‌های PHP نوشته شده است.

## نمونه‌ی کامل ورود webhook

در فایل نمونه‌ی موجود در پروژه، یک مثال کاربردی برای دریافت و پردازش پیام‌ها آورده شده است. برای استفاده از آن، توکن ربات خود را جایگزین کنید و فایل را روی سرویس وب‌هوک خود قرار دهید.

## لایسنس

این پروژه با مجوز MIT منتشر شده است.
