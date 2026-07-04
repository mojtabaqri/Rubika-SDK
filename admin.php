<?php

session_start();
require_once __DIR__ . '/config.php';

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

$action = $_GET['action'] ?? 'dashboard';
$message = '';

if (isset($_GET['logout'])) {
    session_destroy();
    redirect('admin.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $user = trim($_POST['username'] ?? '');
    $pass = trim($_POST['password'] ?? '');

    if ($user === ADMIN_USER && $pass === ADMIN_PASS) {
        $_SESSION['admin_logged_in'] = true;
        redirect('admin.php');
    }

    $message = 'نام کاربری یا رمز عبور اشتباه است.';
}

if (!isset($_SESSION['admin_logged_in'])) {
    ?>
    <!doctype html>
    <html lang="fa" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title>ورود مدیر | <?php echo APP_TITLE; ?></title>
        <?php require_once __DIR__ . '/header.php'; ?>
        <link rel="stylesheet" href="assets/style.css">
        <style>
            body { background: #eff6ff; font-family: 'Vazir', Tahoma, sans-serif; }
            .rtl { direction: rtl; }
        </style>
    </head>
    <body class="rtl min-h-screen flex items-center justify-center px-4 py-8 bg-gradient-to-br from-slate-100 via-slate-50 to-sky-100">
    <div class="w-full max-w-xl bg-white shadow-2xl rounded-[32px] border border-slate-200 overflow-hidden">
        <div class="bg-sky-600 p-8 text-white text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-3xl bg-white/15 mx-auto mb-4">
                <i class="fa-solid fa-shield-halved text-2xl"></i>
            </div>
            <h1 class="text-3xl font-extrabold">پنل مدیریت وام روبیکا</h1>
            <p class="mt-3 text-sky-100 text-sm">فضای امن و حرفهای برای مدیریت کاربران آگهیها و تراکنشها</p>
        </div>
        <div class="p-8">
            <?php if ($message !== ''): ?>
                <div class="rounded-3xl bg-red-50 border border-red-200 text-red-700 px-4 py-3 mb-6">
                    <?php echo htmlspecialchars($message, ENT_QUOTES); ?>
                </div>
            <?php endif; ?>
            <form method="post" class="space-y-5">
                <div>
                    <label class="block text-slate-600 font-semibold mb-2">نام کاربری</label>
                    <input type="text" name="username" class="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-300" required>
                </div>
                <div>
                    <label class="block text-slate-600 font-semibold mb-2">رمز عبور</label>
                    <input type="password" name="password" class="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-300" required>
                </div>
                <button type="submit" name="login" class="w-full rounded-3xl bg-gradient-to-r from-sky-600 to-blue-700 text-white font-semibold py-3 shadow-lg shadow-sky-500/20 hover:from-sky-700 hover:to-blue-800 transition">ورود به پنل</button>
            </form>
            <p class="mt-6 text-center text-slate-500 text-sm">برای دسترسی به پنل مدیریت از کاربری و رمز ادمین فایل .env استفاده کنید.</p>
        </div>
    </div>
    </body>
    </html>
    <?php
    exit;
}

$kycService = new KYCService();
$adminKycController = new AdminKYCController($kycService);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['task'])) {
        $task = $_POST['task'];
        switch ($task) {
            case 'user_status':
                $userId = (int)($_POST['user_id'] ?? 0);
                $status = $_POST['status'] === 'verified' ? 'verified' : 'rejected';
                User::setStatus($userId, $status);
                if ($status === 'verified') {
                    User::adjustTrust($userId, 20);
                }
                $message = 'وضعیت کاربر بهروز شد.';
                break;
            case 'ad_status':
                $adId = (int)($_POST['ad_id'] ?? 0);
                $status = $_POST['status'] === 'approved' ? 'approved' : 'rejected';
                Ad::updateStatus($adId, $status);
                $message = 'وضعیت آگهی بهروز شد.';
                break;
            case 'payment_status':
                $paymentId = (int)($_POST['payment_id'] ?? 0);
                $status = $_POST['status'] === 'approved' ? 'approved' : 'rejected';
                Payment::updateStatus($paymentId, $status);
                $message = 'وضعیت پرداخت بهروز شد.';
                break;
            case 'escrow_status':
                $escrowId = (int)($_POST['escrow_id'] ?? 0);
                $status = in_array($_POST['status'], ['held', 'released', 'dispute']) ? $_POST['status'] : 'held';
                Escrow::updateStatus($escrowId, $status);
                $message = 'وضعیت Escrow بهروز شد.';
                break;
            case 'kyc_status':
                $kycId = (int)($_POST['kyc_id'] ?? 0);
                $status = $_POST['status'] === 'approved' ? 'approved' : 'rejected';
                $notes = trim($_POST['admin_notes'] ?? '');
                $adminKycController->review($kycId, $status, $notes, 1);
                $message = 'درخواست KYC بررسی شد.';
                break;
            case 'delete_report':
                $reportId = (int)($_POST['report_id'] ?? 0);
                Report::delete($reportId);
                $message = 'گزارش حذف شد.';
                break;
        }
    }
}

$stats = User::stats() ?: [];
$users = User::all();
$ads = Ad::all();
$payments = Payment::all();
$escrows = Escrow::all();
$kycs = $adminKycController->pendingRequests();
$reports = Report::all();

$navItems = [
    'dashboard' => ['label' => 'داشبورد', 'icon' => 'fa-solid fa-gauge-high'],
    'users' => ['label' => 'کاربران', 'icon' => 'fa-solid fa-users'],
    'ads' => ['label' => 'آگهیها', 'icon' => 'fa-solid fa-bullhorn'],
    'payments' => ['label' => 'پرداختها', 'icon' => 'fa-solid fa-credit-card'],
    'escrow' => ['label' => 'Escrow', 'icon' => 'fa-solid fa-shield-heart'],
    'kyc' => ['label' => 'KYC pending', 'icon' => 'fa-solid fa-id-card-clip'],
    'reports' => ['label' => 'گزارشها', 'icon' => 'fa-solid fa-file-lines'],
];

function getStatusBadge(string $status): string
{
    return match ($status) {
        'pending' => 'bg-amber-100 text-amber-700',
        'verified', 'approved', 'released' => 'bg-emerald-100 text-emerald-700',
        'rejected', 'held', 'dispute' => 'bg-rose-100 text-rose-700',
        default => 'bg-slate-100 text-slate-700',
    };
}

function formatStatus(string $status): string
{
    return Helpers::formatStatus($status);
}

$pendingAds = count(array_filter($ads, fn($item) => $item['status'] === 'pending'));
$pendingKyc = count(array_filter($kycs, fn($item) => $item['status'] === 'pending'));

?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>پنل مدیریت | <?php echo APP_TITLE; ?></title>
    <?php require_once __DIR__ . '/header.php'; ?>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body { font-family: 'Vazir', Tahoma, sans-serif; background: #f8fbff; }
        .rtl { direction: rtl; }
        .sidebar::-webkit-scrollbar { width: 8px; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(148, 163, 184, 0.45); border-radius: 999px; }
    </style>
</head>
<body class="rtl min-h-screen text-slate-800">
<div class="flex min-h-screen">
    <aside class="sidebar hidden lg:block w-80 bg-slate-950 text-slate-100 border-r border-slate-200/10 shadow-xl">
        <div class="h-full flex flex-col">
            <div class="px-8 py-8 border-b border-slate-800/70">
                <div class="inline-flex items-center gap-3 rounded-3xl bg-slate-900/80 p-4 shadow-sm">
                    <div class="flex h-12 w-12 items-center justify-center rounded-3xl bg-sky-500/15 text-sky-300 text-xl">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">وام روبیکا</p>
                        <h2 class="mt-1 text-xl font-semibold">پنل ادمین</h2>
                    </div>
                </div>
            </div>
            <div class="flex-1 overflow-y-auto px-4 py-6">
                <nav class="space-y-2">
                    <?php foreach ($navItems as $key => $item): ?>
                        <a href="admin.php?action=<?php echo $key; ?>" class="flex items-center gap-3 rounded-3xl px-4 py-3 transition hover:bg-slate-800 <?php echo $action === $key ? 'bg-slate-800 text-white shadow-[0_20px_60px_rgba(15,23,42,0.12)]' : 'text-slate-300'; ?>">
                            <i class="<?php echo $item['icon']; ?> w-5 text-sky-300"></i>
                            <span class="text-sm font-medium"><?php echo $item['label']; ?></span>
                            <?php if ($key === 'kyc' && $pendingKyc > 0): ?>
                                <span class="mr-auto inline-flex items-center rounded-full bg-rose-500/10 px-3 py-1 text-[11px] font-semibold text-rose-500"><?php echo $pendingKyc; ?></span>
                            <?php endif; ?>
                            <?php if ($key === 'ads' && $pendingAds > 0): ?>
                                <span class="mr-auto inline-flex items-center rounded-full bg-amber-500/10 px-3 py-1 text-[11px] font-semibold text-amber-500"><?php echo $pendingAds; ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </div>
            <div class="border-t border-slate-800/70 px-8 py-5">
                <div class="rounded-3xl bg-slate-900/80 p-4">
                    <p class="text-xs uppercase text-slate-500">حساب کاربری</p>
                    <p class="mt-3 font-semibold">مدیر ارشد</p>
                    <p class="mt-1 text-slate-400 text-sm">امتیاز امنیتی عالی</p>
                </div>
            </div>
        </div>
    </aside>

    <main class="flex-1">
        <header class="border-b border-slate-200/70 bg-white/80 backdrop-blur-xl px-6 py-5 shadow-sm lg:px-10">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-sm text-slate-500">خوش آمدید به پنل مدیریت</p>
                    <h1 class="mt-2 text-2xl font-semibold text-slate-900">داشبورد حرفهای وام روبیکا</h1>
                </div>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <a href="admin.php?logout=1" class="inline-flex items-center gap-2 rounded-3xl border border-slate-200 bg-white px-4 py-3 text-slate-700 transition hover:bg-slate-50"><i class="fa-solid fa-right-from-bracket"></i> خروج</a>
                    <a href="index.php" class="inline-flex items-center gap-2 rounded-3xl bg-sky-600 px-4 py-3 text-white shadow-lg shadow-sky-500/20 hover:bg-sky-700 transition"><i class="fa-solid fa-house"></i> مشاهده سایت</a>
                </div>
            </div>
        </header>

        <section class="px-6 py-8 lg:px-10">
            <?php if ($message !== ''): ?>
                <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 mb-6">
                    <?php echo htmlspecialchars($message, ENT_QUOTES); ?>
                </div>
            <?php endif; ?>
            <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-[28px] bg-white p-6 shadow-[0_24px_60px_rgba(15,23,42,0.08)]">
                    <div class="flex items-center justify-between text-slate-400 mb-6">
                        <p class="text-sm font-semibold">کل کاربران</p>
                        <span class="rounded-2xl bg-sky-50 px-3 py-1 text-sky-600 text-xs">+</span>
                    </div>
                    <h2 class="text-3xl font-semibold text-slate-900"><?php echo $stats['total_users'] ?? 0; ?></h2>
                    <p class="mt-3 text-sm text-slate-500">کاربران ثبت نام شده</p>
                </article>
                <article class="rounded-[28px] bg-white p-6 shadow-[0_24px_60px_rgba(15,23,42,0.08)]">
                    <div class="flex items-center justify-between text-slate-400 mb-6">
                        <p class="text-sm font-semibold">کاربران تاییدشده</p>
                        <span class="rounded-2xl bg-emerald-50 px-3 py-1 text-emerald-600 text-xs">امن</span>
                    </div>
                    <h2 class="text-3xl font-semibold text-slate-900"><?php echo $stats['verified_users'] ?? 0; ?></h2>
                    <p class="mt-3 text-sm text-slate-500">کاربرانی که KYC آنها تایید شده</p>
                </article>
                <article class="rounded-[28px] bg-white p-6 shadow-[0_24px_60px_rgba(15,23,42,0.08)]">
                    <div class="flex items-center justify-between text-slate-400 mb-6">
                        <p class="text-sm font-semibold">آگهیها</p>
                        <span class="rounded-2xl bg-amber-50 px-3 py-1 text-amber-600 text-xs"><?php echo $pendingAds; ?> در انتظار</span>
                    </div>
                    <h2 class="text-3xl font-semibold text-slate-900"><?php echo count($ads); ?></h2>
                    <p class="mt-3 text-sm text-slate-500">آگهیهای فعال در بازار</p>
                </article>
                <article class="rounded-[28px] bg-white p-6 shadow-[0_24px_60px_rgba(15,23,42,0.08)]">
                    <div class="flex items-center justify-between text-slate-400 mb-6">
                        <p class="text-sm font-semibold">KYC منتظر</p>
                        <span class="rounded-2xl bg-rose-50 px-3 py-1 text-rose-600 text-xs">نیاز به بررسی</span>
                    </div>
                    <h2 class="text-3xl font-semibold text-slate-900"><?php echo $pendingKyc; ?></h2>
                    <p class="mt-3 text-sm text-slate-500">درخواستهای KYC در صف</p>
                </article>
            </div>
        </section>

        <section class="px-6 pb-10 lg:px-10">
            <div class="rounded-[32px] bg-gradient-to-br from-white to-slate-100 px-6 py-6 shadow-[0_40px_80px_rgba(15,23,42,0.06)]">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">نمای کلی</h2>
                        <p class="mt-2 text-slate-500">کنترل کامل روی وضعیت بازار کاربران و تراکنشها.</p>
                    </div>
                    <div class="inline-flex items-center gap-3 rounded-full bg-slate-50 px-4 py-3 text-sm text-slate-600 shadow-sm">
                        <i class="fa-solid fa-circle-info text-sky-500"></i>
                        <span>این پنل بر اساس آخرین وضعیت دیتابیس ساخته شده است.</span>
                    </div>
                </div>
            </div>

            <?php if ($action === 'dashboard'): ?>
                <div class="mt-8 grid gap-6 xl:grid-cols-2">
                    <div class="rounded-[32px] bg-white p-6 shadow-[0_24px_60px_rgba(15,23,42,0.06)]">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-slate-900">پیشرفت کاربران</h3>
                            <span class="text-slate-500 text-sm">امنیت و اعتماد</span>
                        </div>
                        <div class="mt-6 space-y-4">
                            <div>
                                <div class="flex items-center justify-between text-sm text-slate-500 mb-2">کاربران تایید شده</div>
                                <div class="h-3 rounded-full bg-slate-200 overflow-hidden"><div class="h-full w-[<?php echo $stats['verified_users'] && $stats['total_users'] ? round($stats['verified_users'] / $stats['total_users'] * 100) : 0; ?>%] bg-sky-600"></div></div>
                            </div>
                            <div>
                                <div class="flex items-center justify-between text-sm text-slate-500 mb-2">کاربران در انتظار</div>
                                <div class="h-3 rounded-full bg-slate-200 overflow-hidden"><div class="h-full w-[<?php echo $stats['pending_users'] && $stats['total_users'] ? round($stats['pending_users'] / $stats['total_users'] * 100) : 0; ?>%] bg-amber-500"></div></div>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-[32px] bg-white p-6 shadow-[0_24px_60px_rgba(15,23,42,0.06)]">
                        <h3 class="text-lg font-semibold text-slate-900">مرور سریع</h3>
                        <div class="grid gap-4 mt-6 sm:grid-cols-2">
                            <div class="rounded-3xl border border-slate-200 p-4">
                                <p class="text-sm text-slate-500">پرداختها</p>
                                <p class="mt-3 text-3xl font-semibold text-slate-900"><?php echo count($payments); ?></p>
                            </div>
                            <div class="rounded-3xl border border-slate-200 p-4">
                                <p class="text-sm text-slate-500">Escrow</p>
                                <p class="mt-3 text-3xl font-semibold text-slate-900"><?php echo count($escrows); ?></p>
                            </div>
                            <div class="rounded-3xl border border-slate-200 p-4">
                                <p class="text-sm text-slate-500">گزارشها</p>
                                <p class="mt-3 text-3xl font-semibold text-slate-900"><?php echo count($reports); ?></p>
                            </div>
                            <div class="rounded-3xl border border-slate-200 p-4">
                                <p class="text-sm text-slate-500">آگهیهای تایید نشده</p>
                                <p class="mt-3 text-3xl font-semibold text-slate-900"><?php echo $pendingAds; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($action !== 'dashboard'): ?>
                <div class="mt-8 rounded-[32px] bg-white p-6 shadow-[0_24px_60px_rgba(15,23,42,0.06)]">
                    <div class="flex items-center justify-between gap-4 flex-col sm:flex-row">
                        <div>
                            <h3 class="text-xl font-semibold text-slate-900">
                                <?php echo $navItems[$action]['label'] ?? 'بخش'; ?>
                            </h3>
                            <p class="mt-2 text-slate-500 text-sm">نمایش اطلاعات کامل و اقدامات سریع مدیریت.</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-4 py-2 text-slate-600 text-sm"><?php echo date('Y/m/d H:i'); ?></span>
                    </div>

                    <div class="mt-8 overflow-x-auto">
                        <table class="min-w-full text-left border-separate border-spacing-y-3">
                            <thead>
                            <tr class="text-slate-500 text-sm">
                                <?php if ($action === 'users'): ?>
                                    <th class="px-4 py-3">#</th>
                                    <th class="px-4 py-3">نام</th>
                                    <th class="px-4 py-3">تلفن</th>
                                    <th class="px-4 py-3">وضعیت</th>
                                    <th class="px-4 py-3">اعتماد</th>
                                    <th class="px-4 py-3">اقدامات</th>
                                <?php elseif ($action === 'ads'): ?>
                                    <th class="px-4 py-3">#</th>
                                    <th class="px-4 py-3">کد وام</th>
                                    <th class="px-4 py-3">کاربر</th>
                                    <th class="px-4 py-3">مبلغ</th>
                                    <th class="px-4 py-3">وضعیت</th>
                                    <th class="px-4 py-3">اقدامات</th>
                                <?php elseif ($action === 'payments'): ?>
                                    <th class="px-4 py-3">#</th>
                                    <th class="px-4 py-3">کاربر</th>
                                    <th class="px-4 py-3">مبلغ</th>
                                    <th class="px-4 py-3">تاریخ</th>
                                    <th class="px-4 py-3">وضعیت</th>
                                    <th class="px-4 py-3">اقدامات</th>
                                <?php elseif ($action === 'escrow'): ?>
                                    <th class="px-4 py-3">#</th>
                                    <th class="px-4 py-3">کد Escrow</th>
                                    <th class="px-4 py-3">مبلغ</th>
                                    <th class="px-4 py-3">وضعیت</th>
                                    <th class="px-4 py-3">توضیحات</th>
                                    <th class="px-4 py-3">اقدامات</th>
                                <?php elseif ($action === 'kyc'): ?>
                                    <th class="px-4 py-3">#</th>
                                    <th class="px-4 py-3">نام کاربر</th>
                                    <th class="px-4 py-3">تاریخ</th>
                                    <th class="px-4 py-3">وضعیت</th>
                                    <th class="px-4 py-3">اقدامات</th>
                                <?php elseif ($action === 'reports'): ?>
                                    <th class="px-4 py-3">#</th>
                                    <th class="px-4 py-3">کاربر</th>
                                    <th class="px-4 py-3">موضوع</th>
                                    <th class="px-4 py-3">تاریخ</th>
                                    <th class="px-4 py-3">اقدامات</th>
                                <?php endif; ?>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if ($action === 'users'): ?>
                                <?php foreach ($users as $user): ?>
                                    <tr class="bg-slate-50 rounded-[24px] border border-slate-200/80">
                                        <td class="px-4 py-4 font-semibold"><?php echo $user['id']; ?></td>
                                        <td class="px-4 py-4"><?php echo htmlspecialchars($user['name'] . ' ' . $user['lastname'], ENT_QUOTES); ?></td>
                                        <td class="px-4 py-4"><?php echo htmlspecialchars($user['phone'], ENT_QUOTES); ?></td>
                                        <td class="px-4 py-4"><span class="rounded-full px-3 py-1 text-xs font-semibold <?php echo getStatusBadge($user['status']); ?>"><?php echo formatStatus($user['status']); ?></span></td>
                                        <td class="px-4 py-4"><?php echo $user['trust_score']; ?>%</td>
                                        <td class="px-4 py-4">
                                            <form method="post" class="flex flex-wrap gap-2">
                                                <input type="hidden" name="task" value="user_status">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" name="status" value="verified" class="rounded-2xl bg-emerald-500 px-3 py-2 text-white text-xs">تایید</button>
                                                <button type="submit" name="status" value="rejected" class="rounded-2xl bg-rose-500 px-3 py-2 text-white text-xs">رد</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php elseif ($action === 'ads'): ?>
                                <?php foreach ($ads as $ad): ?>
                                    <tr class="bg-slate-50 rounded-[24px] border border-slate-200/80">
                                        <td class="px-4 py-4 font-semibold"><?php echo $ad['id']; ?></td>
                                        <td class="px-4 py-4"><?php echo htmlspecialchars($ad['loan_code'], ENT_QUOTES); ?></td>
                                        <td class="px-4 py-4"><?php echo htmlspecialchars($ad['name'] . ' ' . $ad['lastname'], ENT_QUOTES); ?></td>
                                        <td class="px-4 py-4"><?php echo number_format($ad['amount']); ?> تومان</td>
                                        <td class="px-4 py-4"><span class="rounded-full px-3 py-1 text-xs font-semibold <?php echo getStatusBadge($ad['status']); ?>"><?php echo formatStatus($ad['status']); ?></span></td>
                                        <td class="px-4 py-4">
                                            <form method="post" class="flex flex-wrap gap-2">
                                                <input type="hidden" name="task" value="ad_status">
                                                <input type="hidden" name="ad_id" value="<?php echo $ad['id']; ?>">
                                                <button type="submit" name="status" value="approved" class="rounded-2xl bg-sky-600 px-3 py-2 text-white text-xs">تایید</button>
                                                <button type="submit" name="status" value="rejected" class="rounded-2xl bg-rose-500 px-3 py-2 text-white text-xs">رد</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php elseif ($action === 'payments'): ?>
                                <?php foreach ($payments as $payment): ?>
                                    <tr class="bg-slate-50 rounded-[24px] border border-slate-200/80">
                                        <td class="px-4 py-4 font-semibold"><?php echo $payment['id']; ?></td>
                                        <td class="px-4 py-4"><?php echo htmlspecialchars($payment['name'] . ' ' . $payment['lastname'], ENT_QUOTES); ?></td>
                                        <td class="px-4 py-4"><?php echo number_format($payment['amount']); ?> تومان</td>
                                        <td class="px-4 py-4"><?php echo htmlspecialchars($payment['created_at'], ENT_QUOTES); ?></td>
                                        <td class="px-4 py-4"><span class="rounded-full px-3 py-1 text-xs font-semibold <?php echo getStatusBadge($payment['status']); ?>"><?php echo formatStatus($payment['status']); ?></span></td>
                                        <td class="px-4 py-4">
                                            <form method="post" class="flex flex-wrap gap-2">
                                                <input type="hidden" name="task" value="payment_status">
                                                <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                                <button type="submit" name="status" value="approved" class="rounded-2xl bg-sky-600 px-3 py-2 text-white text-xs">تایید</button>
                                                <button type="submit" name="status" value="rejected" class="rounded-2xl bg-rose-500 px-3 py-2 text-white text-xs">رد</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php elseif ($action === 'escrow'): ?>
                                <?php foreach ($escrows as $escrow): ?>
                                    <tr class="bg-slate-50 rounded-[24px] border border-slate-200/80">
                                        <td class="px-4 py-4 font-semibold"><?php echo $escrow['id']; ?></td>
                                        <td class="px-4 py-4"><?php echo htmlspecialchars($escrow['escrow_code'] ?? 'ESC-' . $escrow['id'], ENT_QUOTES); ?></td>
                                        <td class="px-4 py-4"><?php echo number_format($escrow['amount']); ?> تومان</td>
                                        <td class="px-4 py-4"><span class="rounded-full px-3 py-1 text-xs font-semibold <?php echo getStatusBadge($escrow['status']); ?>"><?php echo formatStatus($escrow['status']); ?></span></td>
                                        <td class="px-4 py-4"><?php echo htmlspecialchars($escrow['description'] ?? '-', ENT_QUOTES); ?></td>
                                        <td class="px-4 py-4">
                                            <form method="post" class="flex flex-wrap gap-2">
                                                <input type="hidden" name="task" value="escrow_status">
                                                <input type="hidden" name="escrow_id" value="<?php echo $escrow['id']; ?>">
                                                <button type="submit" name="status" value="released" class="rounded-2xl bg-emerald-500 px-3 py-2 text-white text-xs">آزاد</button>
                                                <button type="submit" name="status" value="held" class="rounded-2xl bg-slate-600 px-3 py-2 text-white text-xs">مسدود</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php elseif ($action === 'kyc'): ?>
                                <?php foreach ($kycs as $kyc): ?>
                                    <tr class="bg-slate-50 rounded-[24px] border border-slate-200/80">
                                        <td class="px-4 py-4 font-semibold"><?php echo $kyc['id']; ?></td>
                                        <td class="px-4 py-4"><?php echo htmlspecialchars($kyc['full_name'], ENT_QUOTES); ?></td>
                                        <td class="px-4 py-4"><?php echo htmlspecialchars($kyc['submitted_at'] ?? $kyc['created_at'] ?? '', ENT_QUOTES); ?></td>
                                        <td class="px-4 py-4"><span class="rounded-full px-3 py-1 text-xs font-semibold <?php echo getStatusBadge($kyc['status']); ?>"><?php echo formatStatus($kyc['status']); ?></span></td>
                                        <td class="px-4 py-4">
                                            <form method="post" class="flex flex-wrap gap-2">
                                                <input type="hidden" name="task" value="kyc_status">
                                                <input type="hidden" name="kyc_id" value="<?php echo $kyc['id']; ?>">
                                                <input type="text" name="admin_notes" placeholder="دلیل/یادداشت" class="rounded-2xl border border-slate-200 px-3 py-2 text-slate-700 text-xs" />
                                                <button type="submit" name="status" value="approved" class="rounded-2xl bg-emerald-500 px-3 py-2 text-white text-xs">تأیید</button>
                                                <button type="submit" name="status" value="rejected" class="rounded-2xl bg-rose-500 px-3 py-2 text-white text-xs">رد</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php elseif ($action === 'reports'): ?>
                                <?php foreach ($reports as $report): ?>
                                    <tr class="bg-slate-50 rounded-[24px] border border-slate-200/80">
                                        <td class="px-4 py-4 font-semibold"><?php echo $report['id']; ?></td>
                                        <td class="px-4 py-4"><?php echo htmlspecialchars($report['name'] . ' ' . $report['lastname'], ENT_QUOTES); ?></td>
                                        <td class="px-4 py-4"><?php echo htmlspecialchars($report['title'] ?? 'گزارش کاربران', ENT_QUOTES); ?></td>
                                        <td class="px-4 py-4"><?php echo htmlspecialchars($report['created_at'], ENT_QUOTES); ?></td>
                                        <td class="px-4 py-4">
                                            <form method="post" class="flex flex-wrap gap-2">
                                                <input type="hidden" name="task" value="delete_report">
                                                <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                                <button type="submit" class="rounded-2xl bg-rose-500 px-3 py-2 text-white text-xs">حذف</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </main>
</div>
</body>
</html>
