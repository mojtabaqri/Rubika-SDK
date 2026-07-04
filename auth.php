<?php

session_start();
require_once __DIR__ . '/config.php';

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

$message = '';
$stage = $_GET['stage'] ?? 'phone';
$phone = $_SESSION['auth_phone'] ?? '';

if (isset($_GET['logout'])) {
    session_destroy();
    redirect('auth.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_phone'])) {
        $phone = trim($_POST['phone'] ?? '');
        $phone = preg_replace('/[^0-9]/u', '', $phone);
        if (strlen($phone) < 10) {
            $message = 'لطفاً شماره موبایل معتبر وارد کنید.';
        } else {
            $code = Otp::createCode($phone);
            $_SESSION['auth_phone'] = $phone;
            $_SESSION['auth_stage'] = 'verify';
            $_SESSION['auth_code'] = $code;
            redirect('auth.php?stage=verify');
        }
    }

    if (isset($_POST['verify_code'])) {
        $phone = $_SESSION['auth_phone'] ?? trim($_POST['phone'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $user = Otp::verifyCode($phone, $code);
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            unset($_SESSION['auth_phone'], $_SESSION['auth_stage'], $_SESSION['auth_code']);
            redirect('user.php');
        }

        $message = 'کد وارد شده نامعتبر یا منقضی شده است. دوباره تلاش کنید.';
        $stage = 'verify';
    }
}

if ($stage === 'verify' && !isset($_SESSION['auth_phone'])) {
    redirect('auth.php');
}

$previewCode = $_SESSION['auth_code'] ?? null;
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>ورود یا ثبت‌نام | <?php echo APP_TITLE; ?></title>
    <?php require_once __DIR__ . '/header.php'; ?>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body { font-family: 'Vazir', Tahoma, sans-serif; background: #f4f8ff; }
        .rtl { direction: rtl; }
        .page-bg { background: radial-gradient(circle at top left, rgba(14, 165, 233, 0.14), transparent 28%), radial-gradient(circle at bottom right, rgba(59, 130, 246, 0.14), transparent 24%), #f7fbff; }
        .glass-card { background: rgba(255,255,255,0.94); backdrop-filter: blur(16px); }
    </style>
</head>
<body class="rtl min-h-screen page-bg py-10">
<div class="container mx-auto px-4">
    <div class="max-w-3xl mx-auto rounded-[32px] overflow-hidden shadow-[0_30px_80px_rgba(15,23,42,0.12)] glass-card border border-slate-200/80">
        <div class="grid lg:grid-cols-2">
            <div class="p-10 bg-sky-600 text-white lg:min-h-[540px]">
                <div class="flex items-center gap-3 mb-6">
                    <span class="text-3xl">وام روبیکا</span>
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-3xl bg-white/15 text-white"><i class="fa-solid fa-wallet"></i></span>
                </div>
                <h1 class="text-3xl font-black leading-tight">ورود سریع با موبایل و رمز یکبار مصرف</h1>
                <p class="mt-5 text-sky-100 leading-7">با یک تجربه امن و سریع، ثبت‌نام یا ورود به پنل کاربری را بدون رمز عبور ثابت انجام دهید. به‌سرعت آگهی ثبت کنید، وضعیت KYC را ببینید و معاملات خود را مدیریت کنید.</p>
                <div class="mt-10 space-y-4 text-sm text-sky-100/90">
                    <div class="rounded-3xl bg-white/10 p-4">
                        <strong>ثبت‌نام سریع</strong><br>فقط شماره موبایل وارد کنید.
                    </div>
                    <div class="rounded-3xl bg-white/10 p-4">
                        <strong>کد یکبار مصرف</strong><br>کد ظرف ۱۰ دقیقه اعتبار دارد.
                    </div>
                    <div class="rounded-3xl bg-white/10 p-4">
                        <strong>دسترسی به آگهی‌ها</strong><br>پس از ورود می‌توانید آگهی ثبت یا مدیریت کنید.
                    </div>
                </div>
            </div>
            <div class="p-10">
                <div class="mb-6">
                    <p class="text-sm text-slate-500">صفحه ورود و ثبت‌نام</p>
                    <h2 class="text-2xl font-bold text-slate-900">با موبایل وارد شوید</h2>
                </div>
                <?php if ($message !== ''): ?>
                    <div class="rounded-3xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-700 mb-6"><?php echo htmlspecialchars($message, ENT_QUOTES); ?></div>
                <?php endif; ?>

                <?php if ($stage === 'verify'): ?>
                    <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5 mb-6 text-slate-700">
                        <p class="font-semibold mb-2">کد تایید به شماره زیر ارسال شد:</p>
                        <p class="text-slate-500"><?php echo htmlspecialchars($phone, ENT_QUOTES); ?></p>
                        <?php if ($previewCode): ?>
                            <p class="mt-3 text-sm text-slate-500">کد تست: <strong><?php echo htmlspecialchars($previewCode, ENT_QUOTES); ?></strong></p>
                        <?php endif; ?>
                        <p class="mt-3 text-xs text-slate-400">در نسخه آزمایشی، کد به صورت محلی نمایش داده می‌شود. برای نسخه نهایی می‌توانید سرویس پیامک را متصل کنید.</p>
                    </div>
                    <form method="post" class="space-y-5">
                        <div>
                            <label class="block text-slate-600 mb-2">کد یکبار مصرف</label>
                            <input type="text" name="code" value="" placeholder="مثال: 123456" class="w-full rounded-3xl border border-slate-200 bg-white px-4 py-3 text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-300" required>
                        </div>
                        <div>
                            <button type="submit" name="verify_code" class="w-full rounded-3xl bg-gradient-to-r from-sky-600 to-blue-700 text-white py-3 text-base font-semibold shadow-lg shadow-sky-500/20 hover:from-sky-700 hover:to-blue-800 transition">ورود با کد</button>
                        </div>
                        <div class="text-center text-sm text-slate-500">
                            <a href="auth.php" class="text-sky-600 hover:underline">تغییر شماره موبایل</a>
                        </div>
                    </form>
                <?php else: ?>
                    <form method="post" class="space-y-5">
                        <div>
                            <label class="block text-slate-600 mb-2">شماره موبایل</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($phone, ENT_QUOTES); ?>" placeholder="مثال: 09123456789" class="w-full rounded-3xl border border-slate-200 bg-white px-4 py-3 text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-300" required>
                        </div>
                        <div>
                            <button type="submit" name="send_phone" class="w-full rounded-3xl bg-gradient-to-r from-sky-600 to-blue-700 text-white py-3 text-base font-semibold shadow-lg shadow-sky-500/20 hover:from-sky-700 hover:to-blue-800 transition">دریافت کد ورود</button>
                        </div>
                    </form>
                <?php endif; ?>
                <div class="mt-8 text-sm text-slate-500">
                    <p class="mb-2">با ورود به وام روبیکا، به سامانه ثبت آگهی‌ها، پیگیری KYC و مدیریت وضعیت خود دسترسی پیدا می‌کنید.</p>
                    <a href="index.php" class="text-sky-600 hover:underline">بازگشت به صفحه اصلی</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
