<?php

session_start();
require_once __DIR__ . '/config.php';

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    redirect('auth.php');
}

$userId = (int)$_SESSION['user_id'];
$user = User::findById($userId);
if (!$user) {
    session_destroy();
    redirect('auth.php');
}

$kycService = new KYCService();
if (!$kycService->isUserVerified($userId)) {
    redirect('kyc.php');
}

$message = '';
$success = false;
$type = 'request';
$amount = '';
$description = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = in_array($_POST['type'] ?? '', ['request', 'offer'], true) ? $_POST['type'] : 'request';
    $amount = trim($_POST['amount'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($amount === '' || !is_numeric(str_replace(',', '', $amount))) {
        $message = 'لطفاً مبلغ معتبر وارد کنید.';
    } elseif ($description === '') {
        $message = 'توضیحات آگهی را وارد کنید.';
    } else {
        $cleanAmount = floatval(str_replace(',', '', $amount));
        $ad = Ad::create($userId, $type, $cleanAmount, $description);
        $success = true;
        $message = 'آگهی شما با موفقیت ثبت شد و پس از تایید مدیر منتشر خواهد شد.';
        $amount = '';
        $description = '';
    }
}

$statusLabel = Helpers::formatStatus($user['status']);
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>ثبت آگهی جدید | <?php echo APP_TITLE; ?></title>
    <?php require_once __DIR__ . '/header.php'; ?>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body { font-family: 'Vazir', Tahoma, sans-serif; background: #f7fbff; }
        .rtl { direction: rtl; }
    </style>
</head>
<body class="rtl min-h-screen text-slate-900">
<div class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.16),_transparent_30%),radial-gradient(circle_at_bottom_right,_rgba(59,130,246,0.12),_transparent_28%),#f8fbff]">
    <div class="max-w-5xl mx-auto px-4 py-10">
        <div class="flex items-center justify-between gap-4 mb-8">
            <div>
                <p class="text-sm text-slate-500">ثبت آگهی وام</p>
                <h1 class="text-3xl font-bold text-slate-900">آگهی جدید را ثبت کنید</h1>
            </div>
            <a href="user.php" class="inline-flex items-center gap-2 rounded-3xl bg-white px-5 py-3 text-sm font-semibold text-slate-900 shadow-lg shadow-slate-200 hover:bg-slate-50 transition"><i class="fa-solid fa-arrow-right"></i> بازگشت به پنل</a>
        </div>

        <div class="grid gap-6 lg:grid-cols-3 mb-8">
            <div class="rounded-[32px] bg-white p-6 shadow-[0_24px_60px_rgba(15,23,42,0.08)]">
                <p class="text-sm text-slate-500">وضعیت حساب شما</p>
                <h2 class="mt-4 text-3xl font-semibold text-slate-900"><?php echo $statusLabel; ?></h2>
                <p class="mt-3 text-slate-500 text-sm">برای تایید سریع آگهی، پس از KYC شدن در صف تایید قرار می‌گیرد.</p>
            </div>
            <div class="rounded-[32px] bg-sky-700 p-6 text-white shadow-[0_24px_60px_rgba(14,165,233,0.18)]">
                <p class="text-sm text-sky-100">ثبت آگهی امن</p>
                <h2 class="mt-4 text-3xl font-semibold">آگهی خود را سریع منتشر کنید</h2>
                <p class="mt-4 text-sky-100/80 text-sm">سیستم ما آگهی شما را ثبت می‌کند و پس از بررسی مدیر، در بازار منتشر خواهد شد.</p>
            </div>
            <div class="rounded-[32px] bg-white p-6 shadow-[0_24px_60px_rgba(15,23,42,0.08)]">
                <p class="text-sm text-slate-500">نکات سریع</p>
                <ul class="mt-4 space-y-3 text-slate-600 text-sm">
                    <li>نوع درخواست کنید یا پیشنهاد وام ثبت کنید.</li>
                    <li>مبلغ را به تومان وارد کنید.</li>
                    <li>توضیح شفاف برای اعتماد بیشتر بنویسید.</li>
                </ul>
            </div>
        </div>

        <?php if ($message !== ''): ?>
            <div class="rounded-3xl border <?php echo $success ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700'; ?> p-4 mb-6">
                <?php echo htmlspecialchars($message, ENT_QUOTES); ?>
            </div>
        <?php endif; ?>

        <section class="rounded-[32px] bg-white p-8 shadow-[0_24px_60px_rgba(15,23,42,0.08)]">
            <h2 class="text-xl font-semibold text-slate-900 mb-5">فرم ثبت آگهی</h2>
            <form method="post" class="space-y-6">
                <div class="grid gap-6 lg:grid-cols-2">
                    <div>
                        <label class="block text-slate-700 mb-2">نوع آگهی</label>
                        <select name="type" class="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-300">
                            <option value="request" <?php echo $type === 'request' ? 'selected' : ''; ?>>درخواست وام</option>
                            <option value="offer" <?php echo $type === 'offer' ? 'selected' : ''; ?>>ارائه وام</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-slate-700 mb-2">مبلغ (تومان)</label>
                        <input type="text" name="amount" value="<?php echo htmlspecialchars($amount, ENT_QUOTES); ?>" class="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-300" placeholder="مثال: 5,000,000" required>
                    </div>
                </div>
                <div>
                    <label class="block text-slate-700 mb-2">شرح آگهی</label>
                    <textarea name="description" rows="5" class="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-300" placeholder="توضیحات کامل وام خود را وارد کنید" required><?php echo htmlspecialchars($description, ENT_QUOTES); ?></textarea>
                </div>
                <button type="submit" class="inline-flex items-center justify-center rounded-3xl bg-gradient-to-r from-sky-600 to-blue-700 px-6 py-3 text-white text-base font-semibold shadow-lg shadow-sky-500/20 hover:from-sky-700 hover:to-blue-800 transition">ثبت آگهی</button>
            </form>
        </section>
    </div>
</div>
</body>
</html>
