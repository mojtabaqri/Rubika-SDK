<?php

require_once __DIR__ . '/config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$ad = Ad::findById($id);
if (!$ad) {
    header('HTTP/1.1 404 Not Found');
    echo '<!doctype html><html lang="fa"><head><meta charset="utf-8"><title>آگهی پیدا نشد</title></head><body><div style="font-family: Vazir, sans-serif; padding:40px; text-align:center;"><h1>آگهی پیدا نشد</h1></div></body></html>';
    exit;
}

$user = User::findById((int)($ad['user_id'] ?? 0));
$adTypeLabel = $ad['type'] === 'offer' ? 'ارائه وام' : 'درخواست وام';
$adStatus = Helpers::formatStatus($ad['status']);
$ownerName = htmlspecialchars(trim(($user['name'] ?? '') . ' ' . ($user['lastname'] ?? '')), ENT_QUOTES);
$ownerPhone = htmlspecialchars($user['phone'] ?? 'ثبت نشده', ENT_QUOTES);
$ownerName = $ownerName !== '' ? $ownerName : 'کاربر ثبت نشده';
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>جزئیات آگهی - <?php echo htmlspecialchars($ad['loan_code'], ENT_QUOTES); ?></title>
    <?php require_once __DIR__ . '/header.php'; ?>
    <link rel="stylesheet" href="assets/bootstrap.min.css">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body, .navbar, .btn, h1, h2, h3, h4, h5, h6, p, span, li, a, small, .section-description {
            font-family: 'Vazir', Tahoma, sans-serif !important;
        }
    </style>
</head>
<body>
<header class="navbar navbar-expand-lg site-header py-3 shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bolder text-primary" href="index.php">
            <span class="site-brand-badge">وام روبیکا</span>
        </a>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <a class="nav-link site-nav-link" href="index.php#ads">آگهی‌ها</a>
            <a class="nav-link site-nav-link" href="index.php#how">چگونه کار می‌کند</a>
            <a class="nav-link site-nav-link" href="auth.php">پشتیبانی</a>
            <a class="btn btn-outline-primary btn-sm" href="index.php"><i class="fa-solid fa-arrow-right me-1"></i>بازگشت</a>
            <a class="btn btn-primary btn-sm" href="auth.php"><i class="fa-solid fa-user-circle me-1"></i>ورود / ثبتنام</a>
        </div>
    </div>
</header>

<section class="hero-section">
    <div class="container py-5 py-lg-6">
        <div class="row align-items-stretch gy-4">
            <div class="col-lg-8">
                <div class="card ad-hero-card p-4 p-lg-5 border-0">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                        <span class="badge bg-primary text-white rounded-pill px-3 py-2"><?php echo $adTypeLabel; ?></span>
                        <span class="badge bg-success text-white rounded-pill px-3 py-2"><?php echo $adStatus; ?></span>
                    </div>
                    <h1 class="mb-3" style="font-size: clamp(2rem, 3vw, 2.8rem); font-weight: 800; color: var(--blue);">آگهی <?php echo htmlspecialchars($ad['loan_code'], ENT_QUOTES); ?></h1>
                    <p class="section-description mb-4">این آگهی توسط <?php echo $ownerName; ?> ثبت شده و با اطلاعات شفاف برای برقراری معامله امن در اختیار شما قرار گرفته است.</p>
                    <div class="d-flex flex-wrap gap-3 mb-4">
                        <div class="bg-surface-soft rounded-4 px-4 py-3">
                            <div class="small text-muted mb-1">مبلغ</div>
                            <div class="fw-bold fs-5"><?php echo number_format($ad['amount']); ?> تومان</div>
                        </div>
                        <div class="bg-surface-soft rounded-4 px-4 py-3">
                            <div class="small text-muted mb-1">تاریخ ثبت</div>
                            <div class="fw-bold fs-5"><?php echo htmlspecialchars($ad['created_at'], ENT_QUOTES); ?></div>
                        </div>
                    </div>
                    <a href="auth.php" class="btn btn-primary">برای ثبت آگهی جدید وارد شوید</a>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card ad-summary-card p-4 border-0 h-100">
                    <h3 class="mb-4">خلاصه آگهی</h3>
                    <ul class="list-unstyled ad-summary-list">
                        <li><strong>نام مالک:</strong><br><?php echo $ownerName; ?></li>
                        <li><strong>شماره تماس:</strong><br><?php echo $ownerPhone; ?></li>
                        <li><strong>نوع آگهی:</strong><br><?php echo $adTypeLabel; ?></li>
                        <li><strong>وضعیت:</strong><br><?php echo $adStatus; ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="features-section py-6">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="feature-card h-100">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="icon-circle d-inline-flex align-items-center justify-content-center"><i class="fa-solid fa-file-lines"></i></div>
                        <h3 class="mb-0">توضیحات آگهی</h3>
                    </div>
                    <p class="text-muted mb-0" style="line-height: 2;"><?php echo nl2br(htmlspecialchars($ad['description'], ENT_QUOTES)); ?></p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="feature-card h-100">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="icon-circle d-inline-flex align-items-center justify-content-center"><i class="fa-solid fa-circle-info"></i></div>
                        <h3 class="mb-0">اطلاعات مالک</h3>
                    </div>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3"><strong>نام:</strong> <?php echo $ownerName; ?></li>
                        <li class="mb-3"><strong>شماره تلفن:</strong> <?php echo $ownerPhone; ?></li>
                        <li class="mb-3"><strong>کد آگهی:</strong> <?php echo htmlspecialchars($ad['loan_code'], ENT_QUOTES); ?></li>
                        <li><strong>تاریخ ثبت:</strong> <?php echo htmlspecialchars($ad['created_at'], ENT_QUOTES); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="cta-section py-6">
    <div class="container">
        <div class="cta-card text-center py-5 px-4">
            <h2>برای ادامه معامله آماده اید؟</h2>
            <p class="mt-3">در وام روبیکا میتوانید با خیال راحت آگهی خود را ثبت و معاملات را با امنیت بیشتری انجام دهید.</p>
            <a href="auth.php" class="btn btn-outline-light btn-lg mt-4">ورود و ثبت آگهی</a>
        </div>
    </div>
</section>

<footer class="py-4 text-center text-muted small-text bg-white border-top">
    <div class="container">© ۱۴۰۵ وام روبیکا. تمامی حقوق محفوظ است.</div>
</footer>

<script src="assets/bootstrap.bundle.min.js"></script>
</body>
</html>
