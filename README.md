# Rubika Bot SDK

کتابخانه‌ی پیشرفته و حرفه‌ای برای ساخت ربات روبیکا با PHP

## ویژگی‌ها
- ✅ معماری شی‌گرا و تمیز
- ✅ سیستم Event Dispatcher
- ✅ مدل‌های خودکار (Nested Objects)
- ✅ Enums برای تمام انواع
- ✅ Builders برای Keypad و Button
- ✅ webhook-based فقط (Long Polling حذف شده)

## ساختار پروژه
```
src/
├── Bot.php                    # هسته‌ی SDK
├── RubikaClient.php          # کلاینت API
├── Models/
│   ├── Model.php             # کلاس پایه با nested object support
│   └── Models.php            # تمام مدل‌های API
├── Handlers/
│   └── Dispatcher.php        # سیستم Event dispatcher
├── Enums/
│   └── Enums.php             # تمام Enum های API
└── Builders/
    └── KeypadBuilder.php     # Builders برای Keypad و Button

index.php                      # Webhook entry point
```

## استفاده

### ۱. تنظیم Webhook

```php
$bot = new Bot('YOUR_TOKEN');

$bot->updateWebhook('https://your-domain.com/index.php', 'ReceiveUpdate');
```

### ۲. مدیریت رویدادها

```php
$bot->dispatcher()
    ->onNewMessage(function (Update $update) use ($bot) {
        $text = $update->new_message->text;
        $bot->sendMessage($update->chat_id, "دریافت: $text");
    })
    ->onUpdatedMessage(function (Update $update) use ($bot) {
        // ...
    });
```

### ۳. ساخت Keypad

```php
use RubikaBot\Builders\Button;
use RubikaBot\Builders\KeypadRow;
use RubikaBot\Builders\Keypad;

$row = new KeypadRow();
$row->addButtons(
    Button::simple('1', 'گزینه ۱'),
    Button::simple('2', 'گزینه ۲')
);

$keypad = new Keypad();
$keypad->addRow($row)->resizeKeyboard(true);

$bot->sendMessage($chatId, 'انتخاب کنید:', [
    'chat_keypad' => $keypad->toArray(),
    'chat_keypad_type' => 'New'
]);
```

## نکات مهم

1. **Nested Models**: مدل‌ها به‌صورت خودکار nested objects را handle می‌کنند
2. **Event-driven**: کل ربات بر اساس Event handling بنا شده است
3. **Type-safe**: Enums برای تمام انواع استفاده شده‌اند
4. **Webhook-only**: Long Polling پشتیبانی نمی‌شود

## لایسنس

MIT
