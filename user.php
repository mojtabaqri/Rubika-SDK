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

$kyc = KYCService::getKYCStatus($userId);
$ads = Ad::byUser($userId);
$statusLabel = Helpers::formatStatus($user['status']);
$trustLabel = Helpers::trustLevel((int)$user['trust_score']);
$mobile = htmlspecialchars($user['phone'] ?? '', ENT_QUOTES);

if (isset($_GET['logout'])) {
    session_destroy();
    redirect('auth.php');
}

?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>پنل کاربری | <?php echo APP_TITLE; ?></title>
    <?php require_once __DIR__ . '/header.php'; ?>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body { font-family: 'Vazir', Tahoma, sans-serif; background: #f7fbff; }
        .rtl { direction: rtl; }
    </style>
</head>
<body class="rtl min-h-screen text-slate-900">
<div class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(59,130,246,0.18),_transparent_35%),radial-gradient(circle_at_bottom_right,_rgba(14,165,233,0.12),_transparent_30%),#f8fbff]">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-8">
            <div>
                <p class="text-sm text-slate-500">خوش آمدید،</p>
                <h1 class="text-3xl font-extrabold text-slate-900"><?php echo htmlspecialchars($user['name'] . ' ' . $user['lastname'], ENT_QUOTES); ?></h1>
                <p class="mt-2 text-slate-500 text-sm">پنل کاربری شما در وام روبیکا</p>
            </div>
            <div class="flex flex-wrap gap-3 items-center">
                <a href="auth.php?logout=1" class="inline-flex items-center gap-2 rounded-3xl border border-slate-200 bg-white px-5 py-3 text-sm text-slate-700 hover:bg-slate-50 transition"><i class="fa-solid fa-right-from-bracket"></i> خروج</a>
                <a href="post_ad.php" class="inline-flex items-center gap-2 rounded-3xl bg-sky-600 px-5 py-3 text-sm text-white shadow-lg shadow-sky-500/15 hover:bg-sky-700 transition"><i class="fa-solid fa-plus"></i> ثبت آگهی جدید</a>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-3">
            <div class="rounded-[28px] bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)]">
                <p class="text-sm text-slate-500">وضعیت حساب</p>
                <h2 class="mt-4 text-3xl font-semibold text-slate-900"><?php echo $statusLabel; ?></h2>
                <p class="mt-3 text-slate-500 text-sm">این وضعیت نشان‌دهنده مرحله KYC و دسترسی شماست.</p>
            </div>
            <div class="rounded-[28px] bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)]">
                <p class="text-sm text-slate-500">امتیاز اعتبار</p>
                <h2 class="mt-4 text-3xl font-semibold text-slate-900"><?php echo (int)$user['trust_score']; ?>%</h2>
                <p class="mt-3 text-slate-500 text-sm">سطح اعتماد: <?php echo $trustLabel; ?></p>
            </div>
            <div class="rounded-[28px] bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)]">
                <p class="text-sm text-slate-500">شماره موبایل</p>
                <h2 class="mt-4 text-3xl font-semibold text-slate-900"><?php echo $mobile; ?></h2>
                <p class="mt-3 text-slate-500 text-sm">برای ورود دوباره از همین شماره استفاده کنید.</p>
            </div>
        </div>

        <div class="mt-8 grid gap-6 xl:grid-cols-2">
            <section class="rounded-[32px] bg-white p-6 shadow-[0_24px_60px_rgba(15,23,42,0.08)]">
                <div class="flex items-center justify-between gap-4 mb-6">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">وضعیت KYC</h2>
                        <p class="mt-2 text-slate-500 text-sm">اطلاعات احراز هویت شما.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-4 py-2 text-slate-700 text-sm"><?php echo $kyc ? Helpers::formatStatus($kyc['status']) : 'ارسال نشده'; ?></span>
                </div>
                <?php if ($kyc): ?>
                    <ul class="space-y-3 text-slate-600 text-sm">
                        <li><strong>ثبت شده در:</strong> <?php echo htmlspecialchars($kyc['submitted_at'], ENT_QUOTES); ?></li>
                        <li><strong>کد ملی:</strong> <?php echo htmlspecialchars($kyc['national_id'] ?? 'ثبت نشده', ENT_QUOTES); ?></li>
                        <li><strong>پشت کارت ملی:</strong> <?php echo htmlspecialchars($kyc['national_card_back_image'] ?? 'ثبت نشده', ENT_QUOTES); ?></li>
                    </ul>
                <?php else: ?>
                    <p class="text-slate-500 text-sm">در حال حاضر هیچ درخواست KYC فعال وجود ندارد.</p>
                <?php endif; ?>
            </section>
            <section class="rounded-[32px] bg-gradient-to-br from-sky-600 to-blue-700 p-6 shadow-[0_24px_60px_rgba(14,165,233,0.22)] text-white">
                <div>
                    <h2 class="text-xl font-semibold">ثبت آگهی‌های جدید</h2>
                    <p class="mt-3 text-slate-100 text-sm">با ثبت آگهی درخواست یا پیشنهاد وام، معاملات خود را در بازار وام روبیکا شروع کنید.</p>
                </div>
                <div class="mt-8 space-y-4">
                    <div class="rounded-3xl bg-white/10 p-4">
                        <p class="text-sm">وضعیت KYC: <?php echo $statusLabel; ?></p>
                    </div>
                    <div class="rounded-3xl bg-white/10 p-4">
                        <p class="text-sm">آگهی‌های شما: <?php echo count($ads); ?></p>
                    </div>
                </div>
                <a href="post_ad.php" class="mt-6 inline-flex items-center gap-2 rounded-3xl bg-white px-5 py-3 text-sm font-semibold text-slate-900 hover:bg-slate-100 transition">ثبت آگهی جدید <i class="fa-solid fa-arrow-left"></i></a>
            </section>
        </div>

        <section class="mt-8 rounded-[32px] bg-white p-6 shadow-[0_24px_60px_rgba(15,23,42,0.08)]">
            <div class="flex items-center justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">آگهی‌های من</h2>
                    <p class="mt-2 text-slate-500 text-sm">لیست همه آگهی‌های ثبت شده شما.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-4 py-2 text-slate-700 text-sm"><?php echo count($ads); ?> آگهی</span>
            </div>
            <?php if (count($ads) > 0): ?>
                <div class="space-y-4">
                    <?php foreach ($ads as $ad): ?>
                        <div class="rounded-3xl border border-slate-200 p-5">
                            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900"><?php echo htmlspecialchars($ad['loan_code'], ENT_QUOTES); ?></h3>
                                    <p class="mt-2 text-slate-500 text-sm"><?php echo htmlspecialchars($ad['description'], ENT_QUOTES); ?></p>
                                </div>
                                <div class="flex flex-wrap gap-2 items-center">
                                    <span class="rounded-full bg-slate-100 px-3 py-2 text-slate-700 text-xs"><?php echo ucfirst($ad['type']); ?></span>
                                    <span class="rounded-full bg-sky-50 px-3 py-2 text-sky-700 text-xs"><?php echo number_format($ad['amount']); ?> تومان</span>
                                    <span class="rounded-full bg-amber-50 px-3 py-2 text-amber-700 text-xs"><?php echo Helpers::formatStatus($ad['status']); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-slate-500">شما هنوز هیچ آگهی‌ای ثبت نکرده‌اید. با کلیک روی دکمه ثبت آگهی، اولین آگهی خود را بسازید.</p>
            <?php endif; ?>
        </section>
    </div>
</div>
</body>
</html>
