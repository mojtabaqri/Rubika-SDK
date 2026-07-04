<?php

require_once __DIR__ . '/config.php';

$ads = Ad::listApproved();
$stats = User::stats() ?: ['total_users' => 0, 'verified_users' => 0, 'pending_users' => 0];
$pendingKyc = count(array_filter(Kyc::all(), fn($item) => $item['status'] === 'pending'));
$approvedAds = count($ads);
$searchTerm = trim($_GET['q'] ?? '');
$displayAds = $ads;

if ($searchTerm !== '') {
    $displayAds = array_values(array_filter($ads, function ($ad) use ($searchTerm): bool {
        $haystack = implode(' ', [
            $ad['loan_code'] ?? '',
            $ad['description'] ?? '',
            $ad['name'] ?? '',
            $ad['lastname'] ?? '',
            number_format((int)($ad['amount'] ?? 0)),
            Helpers::formatStatus($ad['type'] ?? ''),
        ]);
        return mb_stripos($haystack, $searchTerm) !== false;
    }));
}
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>وام روبیکا | بازار همتا به همتا وام</title>
    <?php require_once __DIR__ . '/header.php'; ?>
    <link rel="stylesheet" href="assets/bootstrap.min.css">
    <link rel="stylesheet" href="assets/style.css">
<style>
    body {
        font-family: 'Vazir', Tahoma, sans-serif;
    }
    .hero-title, .section-title, .section-description, .feature-card, .step-card, .cta-card, .navbar, .navbar-brand, .nav-link {
        font-family: 'Vazir', Tahoma, sans-serif;
    }
</style>
</head>
<body>
<header class="navbar navbar-expand-lg site-header py-3 shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bolder text-primary" href="#home">
            <span class="site-brand-badge">وام روبیکا</span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu" aria-controls="navbarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarMenu">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 align-items-center gap-1">
                <li class="nav-item"><a class="nav-link site-nav-link active" href="#home">خانه</a></li>
                <li class="nav-item"><a class="nav-link site-nav-link" href="#ads">آگهی‌ها</a></li>
                <li class="nav-item"><a class="nav-link site-nav-link" href="#how">چگونه کار می‌کند</a></li>
                <li class="nav-item"><a class="nav-link site-nav-link" href="#trust">امنیت</a></li>
                <li class="nav-item"><a class="nav-link site-nav-link" href="#about">درباره ما</a></li>
                <li class="nav-item"><a class="nav-link site-nav-link" href="auth.php">پشتیبانی</a></li>
            </ul>
            <form class="search-pill d-flex align-items-center gap-2 mt-3 mt-lg-0" method="get" action="index.php">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="q" value="<?php echo htmlspecialchars($searchTerm, ENT_QUOTES); ?>" class="form-control form-control-sm border-0 shadow-none" placeholder="جستجو آگهی...">
                <button class="btn btn-sm btn-primary" type="submit">جستجو</button>
            </form>
            <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                <a href="auth.php" class="btn btn-outline-primary btn-sm">ورود / ثبتنام</a>
                <a href="auth.php" class="btn btn-primary btn-sm">ثبت آگهی</a>
            </div>
        </div>
    </div>
</header>

<section id="home" class="hero-section">
    <div class="container hero-content py-6">
        <div class="row align-items-center gy-5">
            <div class="col-lg-6">
                <span class="badge bg-white text-primary rounded-pill px-4 py-2 mb-3">بازار همتا به همتا وام</span>
                <h1 class="hero-title">وام بده وام بگیر با اعتماد و امنیت</h1>
                <p class="hero-subtitle">پلتفرم پیشرفته بازار وام همتا به همتا با KYC Escrow و سیستم امتیازدهی برای معاملات واقعی و امن.</p>
                <div class="d-flex flex-column flex-sm-row gap-3 mt-4 hero-actions">
                    <a href="auth.php" class="btn btn-primary btn-lg">شروع ثبتنام</a>
                    <a href="#ads" class="btn btn-outline-primary btn-lg">مشاهده آگهیها</a>
                </div>
                <div class="hero-stats mt-5">
                    <div class="hero-stat">
                        <strong><?php echo number_format($stats['total_users']); ?></strong>
                        <span>کاربر فعال</span>
                    </div>
                    <div class="hero-stat">
                        <strong><?php echo number_format($approvedAds); ?></strong>
                        <span>آگهی تایید شده</span>
                    </div>
                    <div class="hero-stat">
                        <strong><?php echo number_format($pendingKyc); ?></strong>
                        <span>درخواست KYC</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card section-card p-4 shadow-lg border-0" style="border-radius: 32px; background: rgba(255,255,255,0.18); backdrop-filter: blur(18px);">
                    <div class="text-start mb-4 pt-3">
                        <span class="badge bg-white text-primary rounded-pill px-3 py-2">امنیت</span>
                    </div>
                    <div class="d-flex justify-content-center mb-4">
                        <div class="icon-circle" style="background: rgba(255,255,255,0.22); color:#fff;"><i class="fa-solid fa-shield-halved fa-2x"></i></div>
                    </div>
                    <h3 class="text-white mb-3">معاملات وام با کمترین ریسک</h3>
                    <p class="text-white-75">با سیستم KYC و Escrow کسبوکار شما در محیطی امن و حرفهای مدیریت میشود و هر معامله با شفافیت کامل انجام میگیرد.</p>
                    <a href="#trust" class="btn btn-outline-light mt-3">مشاهده امنیت</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="ads" class="features-section py-6">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">آگهیهای فعال</h2>
            <p class="section-description mx-auto">آگهیهای تایید شده بازار وام روبیکا را مشاهده کنید و روی فرصت مناسب خود کلیک کنید.</p>
            <?php if ($searchTerm !== ''): ?>
                <div class="mt-3 text-primary fw-bold">نتایج جستجو برای: <?php echo htmlspecialchars($searchTerm, ENT_QUOTES); ?></div>
            <?php endif; ?>
        </div>
        <div class="row g-4">
            <?php if (count($displayAds) > 0): ?>
                <?php foreach ($displayAds as $ad): ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="feature-card h-100 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <span class="badge bg-primary text-white rounded-pill px-3 py-2"><?php echo htmlspecialchars(Helpers::formatStatus($ad['type']), ENT_QUOTES); ?></span>
                                    </div>
                                    <span class="small-text text-primary"><?php echo number_format($ad['amount']); ?> تومان</span>
                                </div>
                                <h3 class="h5 mb-3">کد آگهی <?php echo htmlspecialchars($ad['loan_code'], ENT_QUOTES); ?></h3>
                                <p class="text-muted"><?php echo htmlspecialchars(mb_substr($ad['description'], 0, 100), ENT_QUOTES); ?>...</p>
                            </div>
                            <div class="mt-4 d-flex justify-content-between align-items-center">
                                <span class="text-muted small">ثبت شده توسط <?php echo htmlspecialchars($ad['name'] . ' ' . $ad['lastname'], ENT_QUOTES); ?></span>
                                <a href="ad.php?id=<?php echo $ad['id']; ?>" class="btn btn-sm btn-primary">جزئیات</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="rounded-[30px] bg-white p-6 text-center shadow-[0_24px_60px_rgba(15,23,42,0.08)]">
                        <h3 class="mb-3"><?php echo $searchTerm !== '' ? 'هیچ آگهی با این جستجو پیدا نشد' : 'هنوز آگهی تایید شدهای وجود ندارد'; ?></h3>
                        <p class="text-muted"><?php echo $searchTerm !== '' ? 'جستجوی دیگری انجام دهید یا آگهی جدیدی ثبت کنید.' : 'اولین آگهی خود را ثبت کنید تا پس از تایید مدیر در این لیست نمایش داده شود.'; ?></p>
                        <a href="auth.php" class="btn btn-primary mt-3">ثبت آگهی</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section id="how" class="how-it-works py-6 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">چگونه کار میکند</h2>
            <p class="section-description mx-auto">چهار گام ساده برای شروع معاملات امن در وام روبیکا.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-xl-3">
                <div class="step-card h-100">
                    <div class="step-number">۱</div>
                    <div class="icon-circle mb-3"><i class="fa-solid fa-phone"></i></div>
                    <h4>ورود با موبایل</h4>
                    <p>ثبت نام امن با شماره موبایل و کد یکبارمصرف.</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="step-card h-100">
                    <div class="step-number">۲</div>
                    <div class="icon-circle mb-3"><i class="fa-solid fa-id-card"></i></div>
                    <h4>احراز هویت</h4>
                    <p>KYC سریع و بررسی اسناد توسط مدیر برای افزایش اعتماد.</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="step-card h-100">
                    <div class="step-number">۳</div>
                    <div class="icon-circle mb-3"><i class="fa-solid fa-bullhorn"></i></div>
                    <h4>ثبت آگهی</h4>
                    <p>درخواست یا پیشنهاد وام را با جزییات وارد کنید.</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="step-card h-100">
                    <div class="step-number">۴</div>
                    <div class="icon-circle mb-3"><i class="fa-solid fa-handshake-simple"></i></div>
                    <h4>تسویه امن</h4>
                    <p>پرداخت با سیستم Escrow انجام میشود و معامله امن باقی میماند.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="trust" class="cta-section py-6">
    <div class="container">
        <div class="cta-card text-center py-5 px-4">
            <h2>امنیت کاملا تضمین شده</h2>
            <p class="mt-3">با روشهای مبتنی بر KYC و Escrow معاملهای امن و بدون نگرانی را تجربه کنید.</p>
            <a href="auth.php" class="btn btn-primary btn-lg mt-4">همین حالا شروع کنید</a>
        </div>
    </div>
</section>

<section id="about" class="features-section py-6">
    <div class="container">
        <div class="row align-items-center gy-4">
            <div class="col-lg-6">
                <h2 class="section-title">وام روبیکا چیست</h2>
                <p class="section-description">پلتفرم جامع وام همتا به همتا برای معرفی آگهیهای وام احراز هویت دقیق و مدیریت امن معاملات با تجربهای حرفهای و قابل اعتماد.</p>
                <ul class="list-unstyled mt-4">
                    <li class="mb-3"><i class="fa-solid fa-circle-check text-primary me-2"></i>امنیت بالا برای کاربران وامدهنده و وامگیرنده</li>
                    <li class="mb-3"><i class="fa-solid fa-circle-check text-primary me-2"></i>شفافیت کامل در وضعیت آگهیها و تراکنشها</li>
                    <li class="mb-3"><i class="fa-solid fa-circle-check text-primary me-2"></i>تجربه کاربری حرفهای و ساده</li>
                </ul>
            </div>
            <div class="col-lg-6">
                <div class="card section-card p-4 shadow-lg">
                    <h3 class="mb-3">بهترین راه برای مدیریت وام</h3>
                    <p class="text-muted">با طراحی حرفهای و روند ساده کاربران میتوانند آگهی خود را ثبت کنند KYC را تکمیل کنند و تراکنش خود را تحت نظارت امن Escrow انجام دهند.</p>
                    <div class="row row-cols-1 row-cols-md-2 g-3 mt-4">
                        <div class="col">
                            <div class="p-3 rounded-4 bg-surface-soft">
                                <h5>پشتیبانی KYC</h5>
                                <p class="mb-0 text-muted">سند و هویت همه کاربران بررسی میشود.</p>
                            </div>
                        </div>
                        <div class="col">
                            <div class="p-3 rounded-4 bg-surface-soft">
                                <h5>امتیاز اعتماد</h5>
                                <p class="mb-0 text-muted">کاربران با رفتار بهتر امتیاز بالا میگیرند.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<footer class="py-4 text-center text-muted small-text bg-white border-top">
    <div class="container">© ۱۴۰۵ وام روبیکا. تمامی حقوق محفوظ است.</div>
</footer>

<script src="assets/bootstrap.bundle.min.js"></script>
</body>
</html>
