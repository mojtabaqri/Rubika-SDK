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
if ($kycService->isUserVerified($userId)) {
    redirect('user.php');
}

$verification = KYCService::getKYCStatus($userId);
$message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['_csrf'] ?? null)) {
        $errors['csrf'] = 'توکن CSRF نامعتبر است.';
    } else {
        try {
            $result = KYCService::submitKYC($userId, [
                'full_name' => trim($_POST['full_name'] ?? ''),
                'address' => trim($_POST['address'] ?? ''),
                'postal_code' => trim($_POST['postal_code'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'national_id' => trim($_POST['national_id'] ?? ''),
            ], $_FILES);

            if (is_array($result) && isset($result['error'])) {
                $errors['general'] = $result['error'];
                $message = '';
            } elseif (is_array($result) && isset($result['id'])) {
                $message = 'درخواست احراز هویت شما ثبت شد. لطفاً منتظر تایید مدیر بمانید.';
                $errors = [];
            } elseif (is_array($result) && !empty($result)) {
                $errors = $result;
                $message = '';
            } else {
                $errors['general'] = 'خطایی رخ داده است. دوباره تلاش کنید.';
                $message = '';
            }
        } catch (InvalidArgumentException $ex) {
            $decoded = json_decode($ex->getMessage(), true);
            if (is_array($decoded)) {
                $errors = $decoded;
            } else {
                $errors['general'] = $ex->getMessage();
            }
        } catch (Throwable $ex) {
            Logger::log('kyc.submit.error', ['user_id' => $userId, 'error' => $ex->getMessage()]);
            $errors['general'] = 'خطایی رخ داده است. دوباره تلاش کنید.';
        }
    }
}

$verification = KYCService::getKYCStatus($userId);
$submitDisabled = $verification && $verification['status'] === 'pending';
$rejectedReason = $verification && $verification['status'] === 'rejected' ? $verification['admin_notes'] : null;
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>احراز هویت | <?php echo APP_TITLE; ?></title>
    <?php require_once __DIR__ . '/header.php'; ?>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body { font-family: 'Vazir', Tahoma, sans-serif; background: #f4f8ff; }
        .rtl { direction: rtl; }
    </style>
</head>
<body class="rtl min-h-screen text-slate-900">
<div class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(59,130,246,0.16),_transparent_28%),radial-gradient(circle_at_bottom_right,_rgba(14,165,233,0.12),_transparent_26%),#f8fbff]">
    <div class="max-w-4xl mx-auto px-4 py-10">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-8">
            <div>
                <p class="text-sm text-slate-500">احراز هویت</p>
                <h1 class="text-3xl font-bold text-slate-900">تکمیل فرآیند KYC</h1>
                <p class="mt-3 text-slate-500">برای ادامه فعالیت‌های مهم سایت و ربات، احراز هویت شما لازم است.</p>
            </div>
            <a href="user.php" class="inline-flex items-center gap-2 rounded-3xl bg-white px-5 py-3 text-sm font-semibold text-slate-900 shadow-lg shadow-slate-200 hover:bg-slate-50 transition"><i class="fa-solid fa-arrow-right"></i> بازگشت به پنل</a>
        </div>

        <?php if ($message !== ''): ?>
            <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-4 mb-6 text-emerald-700"><?php echo htmlspecialchars($message, ENT_QUOTES); ?></div>
        <?php endif; ?>

        <?php if (!empty($errors['general'])): ?>
            <div class="rounded-3xl border border-rose-200 bg-rose-50 p-4 mb-6 text-rose-700"><?php echo htmlspecialchars($errors['general'], ENT_QUOTES); ?></div>
        <?php endif; ?>

        <?php if ($verification): ?>
            <div class="rounded-[32px] bg-white p-6 shadow-[0_24px_60px_rgba(15,23,42,0.08)] mb-8">
                <div class="flex items-center justify-between gap-4 mb-4">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">درخواست قبلی شما</h2>
                        <p class="mt-2 text-slate-500 text-sm">وضعیت فعلی درخواست KYC شما.</p>
                    </div>
                    <span class="rounded-full px-4 py-2 text-sm font-semibold <?php echo $verification['status'] === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700'; ?>"><?php echo Helpers::formatStatus($verification['status']); ?></span>
                </div>
                <dl class="grid gap-3 text-sm text-slate-600">
                    <div><dt class="font-semibold">نام کامل</dt><dd><?php echo htmlspecialchars($verification['full_name'], ENT_QUOTES); ?></dd></div>
                    <div><dt class="font-semibold">آدرس</dt><dd><?php echo htmlspecialchars($verification['address'], ENT_QUOTES); ?></dd></div>
                    <div><dt class="font-semibold">کد پستی</dt><dd><?php echo htmlspecialchars($verification['postal_code'] ?? '-', ENT_QUOTES); ?></dd></div>
                    <div><dt class="font-semibold">شماره موبایل</dt><dd><?php echo htmlspecialchars($verification['phone'] ?? '-', ENT_QUOTES); ?></dd></div>
                    <div><dt class="font-semibold">کد ملی</dt><dd><?php echo htmlspecialchars($verification['national_id'] ?? '-', ENT_QUOTES); ?></dd></div>
                    <div><dt class="font-semibold">پشت کارت ملی</dt><dd><?php echo htmlspecialchars($verification['national_card_back_image'] ?? 'ثبت نشده', ENT_QUOTES); ?></dd></div>
                    <?php if ($verification['status'] === 'rejected' && $rejectedReason): ?>
                        <div><dt class="font-semibold">دلیل رد</dt><dd class="text-rose-700"><?php echo htmlspecialchars($rejectedReason, ENT_QUOTES); ?></dd></div>
                    <?php endif; ?>
                </dl>
            </div>
        <?php endif; ?>

        <?php if ($submitDisabled): ?>
            <div class="rounded-[32px] bg-white p-8 shadow-[0_24px_60px_rgba(15,23,42,0.08)] text-slate-700">
                <p class="text-lg font-semibold">درخواست شما در حال بررسی است.</p>
                <p class="mt-3 text-slate-500">پس از تایید مدیر، دسترسی کامل شما فعال خواهد شد.</p>
            </div>
        <?php else: ?>
            <section class="rounded-[32px] bg-white p-8 shadow-[0_24px_60px_rgba(15,23,42,0.08)]">
                <h2 class="text-xl font-semibold text-slate-900 mb-4">فرم احراز هویت</h2>
                <form method="post" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="_csrf" value="<?php echo htmlspecialchars(Csrf::getToken(), ENT_QUOTES); ?>">
                    <div>
                        <label class="block text-slate-700 mb-2">نام و نام خانوادگی</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? '', ENT_QUOTES); ?>" class="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-300" required>
                        <?php if (!empty($errors['full_name'])): ?><p class="mt-2 text-rose-600 text-sm"><?php echo htmlspecialchars($errors['full_name'], ENT_QUOTES); ?></p><?php endif; ?>
                    </div>
                    <div>
                        <label class="block text-slate-700 mb-2">آدرس کامل</label>
                        <textarea name="address" rows="4" class="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-300" required><?php echo htmlspecialchars($_POST['address'] ?? '', ENT_QUOTES); ?></textarea>
                        <?php if (!empty($errors['address'])): ?><p class="mt-2 text-rose-600 text-sm"><?php echo htmlspecialchars($errors['address'], ENT_QUOTES); ?></p><?php endif; ?>
                    </div>
                    <div class="grid gap-6 lg:grid-cols-2">
                        <div>
                            <label class="block text-slate-700 mb-2">کد پستی</label>
                            <input type="text" name="postal_code" value="<?php echo htmlspecialchars($_POST['postal_code'] ?? '', ENT_QUOTES); ?>" class="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-300" placeholder="مثال: 1234567890" required>
                            <?php if (!empty($errors['postal_code'])): ?><p class="mt-2 text-rose-600 text-sm"><?php echo htmlspecialchars($errors['postal_code'], ENT_QUOTES); ?></p><?php endif; ?>
                        </div>
                        <div>
                            <label class="block text-slate-700 mb-2">شماره موبایل</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? $user['phone'] ?? '', ENT_QUOTES); ?>" class="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-300" placeholder="مثال: 09123456789" required>
                            <?php if (!empty($errors['phone'])): ?><p class="mt-2 text-rose-600 text-sm"><?php echo htmlspecialchars($errors['phone'], ENT_QUOTES); ?></p><?php endif; ?>
                        </div>
                    </div>
                    <div class="grid gap-6 lg:grid-cols-2">
                        <div>
                            <label class="block text-slate-700 mb-2">کد ملی</label>
                            <input type="text" name="national_id" value="<?php echo htmlspecialchars($_POST['national_id'] ?? '', ENT_QUOTES); ?>" class="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-300" placeholder="مثال: 0012345678" maxlength="10" required>
                            <?php if (!empty($errors['national_id'])): ?><p class="mt-2 text-rose-600 text-sm"><?php echo htmlspecialchars($errors['national_id'], ENT_QUOTES); ?></p><?php endif; ?>
                        </div>
                        <div>
                            <label class="block text-slate-700 mb-2">تصویر پشت کارت ملی (JPG یا PNG)</label>
                            <input type="file" name="national_card_back_image" accept="image/jpeg,image/png" class="w-full text-slate-700" required>
                            <?php if (!empty($errors['files'])): ?><p class="mt-2 text-rose-600 text-sm"><?php echo htmlspecialchars($errors['files'], ENT_QUOTES); ?></p><?php endif; ?>
                        </div>
                    </div>
                    <button type="submit" class="inline-flex items-center justify-center rounded-3xl bg-gradient-to-r from-sky-600 to-blue-700 px-6 py-3 text-white text-base font-semibold shadow-lg shadow-sky-500/20 hover:from-sky-700 hover:to-blue-800 transition">ارسال درخواست احراز هویت</button>
                </form>
            </section>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
