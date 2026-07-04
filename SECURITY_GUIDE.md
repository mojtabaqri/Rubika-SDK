# 🔐 سیستم احراز هویت و پرداخت امن Vamban Bot

یک سیستم جامع و ایمن برای احراز هویت، مدیریت معاملات و پرداخت‌های درون‌پلتفرمی با ادغام درگاه زرین‌پال.

## 🚀 ویژگی‌های اصلی

### 1️⃣ احراز هویت پیشرفته (JWT + Refresh Token)
- **JWT Tokens**: Access Token (15 دقیقه) و Refresh Token (7 روز)
- **HttpOnly Cookies**: ذخیره‌سازی امن Refresh Token
- **Token Blacklist**: قافل کردن توکن‌های لاگ‌شده
- **سرعت کنترل (Rate Limiting)**: حفاظت در برابر حملات brute-force
- **Login Attempts Logging**: ثبت تمام تلاش‌های ورود

### 2️⃣ سیستم KYC (احراز هویت کاربر)
- **مراحل KYC**: نام و نام خانوادگی، آدرس، کد پستی، شماره موبایل، عکس کارت ملی
- **آپلود امن فایل**: اعتبار‌سنجی و بهینه‌سازی تصاویر
- **پنل ادمین**: تایید/رد درخواست‌ها با یادداشت‌ها
- **یکپارچگی**: چک KYC در تمام نقاط حیاتی

### 3️⃣ سیستم Escrow و پرداخت
- **معاملات امن**: واسطه‌گری تا تأیید تحویل
- **ادغام Zarinpal**: پرداخت درون‌پلتفرمی
- **کمیسیون خودکار**: محاسباتِ هوشمند و شفاف
- **اختلاف‌رسانی**: مکانیزم حل اختلاف

### 4️⃣ امنیت جامع
- **Prepared Statements**: محافظت ضد SQL Injection
- **CSRF Protection**: توکن‌های validate شده
- **XSS Prevention**: انکوڈینگ تمام output
- **Rate Limiting**: محدود کردن درخواست‌ها
- **Activity Logging**: ثبت تمام فعالیت‌های اهم
- **Password Hashing**: bcrypt با 12 pepper

## 📋 نصب و راه‌اندازی

### 1. نصب Composer Dependencies
```bash
cd /path/to/Rubika-SDK
composer require firebase/php-jwt
```

### 2. پیکربندی .env
```bash
cp .env.example .env
# ویرایش فایل و مقادیر خود را وارد کنید
```

### 3. راه‌اندازی دیتابیس
```bash
# دیتابیس SQLite به صورت خودکار ایجاد می‌شود
# فقط مطمئن شوید که پوشه‌ی `data/` نوشتنی است
chmod 755 data/
```

### 4. ایجاد ادمین اول
```php
<?php
require_once 'init.php';

// ایجاد ادمین
$admin = AuthService::createAdmin(
    'admin',
    'ChangeMe123!',
    'نام ادمین',
    'admin@domain.com',
    'admin'
);

if ($admin) {
    echo "ادمین با موفقیت ایجاد شد!\n";
    var_dump($admin);
}
?>
```

## 🔌 API Endpoints

### احراز هویت (Authentication)
- **POST `/api/auth/login.php`**: ورود
- **POST `/api/auth/refresh.php`**: تجدید Access Token
- **POST `/api/auth/logout.php`**: خروج
- **GET `/api/auth/me.php`**: اطلاعات کاربر جاری

### KYC (احراز هویت کاربر)
- **POST `/api/kyc/submit.php`**: ارسال درخواست KYC
- **GET `/api/kyc/status.php`**: دریافت وضعیت KYC
- **GET `/api/admin/kyc/list.php`** (ادمین): لیست درخواست‌ها
- **POST `/api/admin/kyc/approve.php`** (ادمین): تایید KYC
- **POST `/api/admin/kyc/reject.php`** (ادمین): رد کردن KYC

### Escrow و پرداخت
- **POST `/api/escrow/create.php`**: ایجاد معاملهٔ جدید
- **POST `/api/payment/initialize.php`**: شروع فرآیند پرداخت
- **POST `/api/payment/verify.php`**: تأیید پرداخت (Callback Zarinpal)

## 🗄️ ساختار دیتابیس

### جدول `admins`
```sql
CREATE TABLE admins (
    id INTEGER PRIMARY KEY,
    username TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    full_name TEXT,
    email TEXT,
    role TEXT DEFAULT 'admin',
    status TEXT DEFAULT 'active',
    last_login TEXT,
    created_at TEXT,
    updated_at TEXT
);
```

### جدول `token_blacklist`
```sql
CREATE TABLE token_blacklist (
    id INTEGER PRIMARY KEY,
    jti TEXT UNIQUE NOT NULL,
    token_type TEXT DEFAULT 'refresh',
    reason TEXT,
    blacklisted_at TEXT,
    expires_at TEXT
);
```

### جدول `activity_logs`
```sql
CREATE TABLE activity_logs (
    id INTEGER PRIMARY KEY,
    user_id INTEGER,
    admin_id INTEGER,
    action TEXT NOT NULL,
    description TEXT,
    ip_address TEXT,
    user_agent TEXT,
    metadata TEXT,
    severity TEXT DEFAULT 'info',
    created_at TEXT
);
```

### جدول `escrows`
```sql
CREATE TABLE escrows (
    id INTEGER PRIMARY KEY,
    buyer_id INTEGER,
    seller_id INTEGER,
    amount REAL,
    fee REAL DEFAULT 0,
    status TEXT DEFAULT 'pending',
    description TEXT,
    metadata TEXT,
    order_id TEXT,
    zarinpal_authority TEXT,
    zarinpal_ref_id TEXT,
    payment_verified_at TEXT,
    released_at TEXT,
    disputed_at TEXT,
    dispute_reason TEXT,
    created_at TEXT,
    updated_at TEXT
);
```

## 📚 نمونه‌های استفاده

### لاگین ادمین
```bash
curl -X POST http://localhost/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{
    "username": "admin",
    "password": "ChangeMe123!",
    "is_admin": true
  }'
```

### دریافت اطلاعات کاربر جاری
```bash
curl -X GET http://localhost/api/auth/me.php \
  -H "Authorization: Bearer <access_token>"
```

### ارسال درخواست KYC
```bash
curl -X POST http://localhost/api/kyc/submit.php \
  -H "Authorization: Bearer <access_token>" \
  -F "full_name=علی محمدی" \
  -F "address=تهران، خیابان ولیعصر" \
  -F "postal_code=1234567890" \
  -F "phone=989123456789" \
  -F "national_card_back_image=@/path/to/card.jpg"
```

### ایجاد معامله (Escrow)
```bash
curl -X POST http://localhost/api/escrow/create.php \
  -H "Authorization: Bearer <access_token>" \
  -H "Content-Type: application/json" \
  -d '{
    "seller_id": 2,
    "amount": 100000,
    "description": "خرید محصول"
  }'
```

## 🔒 نکات امنیتی

### 1. کنترل دسترسی
- تمام endpoint های اداری نیاز به token ادمین دارند
- عملیات حساس نیاز به تایید KYC دارند
- Token Blacklist برای لاگ‌شده‌های ایمن

### 2. اعتبار‌سنجی داده‌ها
- Prepared Statements برای تمام queries
- Whitelist برای فایل‌های آپلود
- اعتبار‌سنجی Content-Type

### 3. سرعت کنترل
- محدود کردن تلاش‌های ورود
- Rate Limiting برای API endpoints
- مراقبت از Brute-Force

### 4. لاگ‌گذاری
- تمام ورود و خروج
- تمام عملیات KYC
- تمام معاملات و پرداخت‌ها
- تمام تغییرات حساب ادمین

## 🛠️ مثال‌های توسعه

### ایجاد ادمین جدید
```php
require_once 'init.php';

$admin = AuthService::createAdmin(
    'newadmin',
    'SecurePassword123!',
    'نام ادمین',
    'admin@example.com'
);
```

### دریافت لاگ‌های فعالیت
```php
require_once 'init.php';

// لاگ‌های حساس (High Severity)
$logs = ActivityLogger::getCriticalLogs(50);

foreach ($logs as $log) {
    echo $log['action'] . " - " . $log['created_at'] . "\n";
}
```

### مدیریت معاملات
```php
require_once 'init.php';

// دریافت معاملات کاربر
$escrows = EscrowService::getUserEscrows($userId);

// دریافت جزئیات
foreach ($escrows as $escrow) {
    $logs = EscrowService::getEscrowLogs($escrow['id']);
    echo "معامله #" . $escrow['id'] . ": " . $escrow['status'] . "\n";
}
```

## 📝 نام‌گذاری کلاس‌ها

- `AuthService`: احراز هویت با JWT
- `KYCService`: مدیریت تایید هویت
- `EscrowService`: مدیریت معاملات امن
- `ActivityLogger`: ثبت فعالیت‌های حساس
- `APIMiddleware`: Helper برای API endpoints

## 🚨 حل مشکلات

### مشکل: "دیتابیس قابل‌دسترس نیست"
```bash
chmod 755 data/
chmod 644 data/vamban.db
```

### مشکل: "JWT_SECRET پیکربندی نشده"
```bash
# ویرایش .env و تنظیم JWT_SECRET
JWT_SECRET=your-secure-secret-key-min-32-characters
```

### مشکل: "خطای Upload فایل"
```bash
# ایجاد پوشه uploads و تنظیم مجوزات
mkdir -p uploads/kyc
chmod 755 uploads/
chmod 755 uploads/kyc/
```

## 📞 پشتیبانی

برای استفاده مناسب از سیستم:
1. تمام محدودیت‌های امنیتی را رعایت کنید
2. تغییرات در Production با احتیاط انجام دهید
3. لاگ‌ها را به‌صورت منظم بررسی کنید
4. Tokens را همیشه عبور secure رکھیں

## 📄 لایسنس

MIT - استفاده آزادانه برای پروژه‌های تجاری و شخصی
