<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبورد ادمین</title>
    
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.4/dist/tailwind.min.css">
    
    <!-- Vazir Font -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vazir-font@30.1.0/dist/font-face.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    
    <style>
        * {
            font-family: 'Vazir', sans-serif;
        }
        
        body {
            background-color: #f8fafc;
        }
        
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            position: fixed;
            right: 0;
            top: 0;
            width: 250px;
            z-index: 40;
        }
        
        .main-content {
            margin-right: 250px;
            min-height: 100vh;
        }
        
        .nav-item {
            transition: all 0.3s ease;
            border-right: 3px solid transparent;
        }
        
        .nav-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-right-color: white;
        }
        
        .nav-item.active {
            background-color: rgba(255, 255, 255, 0.2);
            border-right-color: white;
        }
        
        .stat-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .tab-button {
            transition: all 0.3s ease;
        }
        
        .tab-button.active {
            border-bottom: 3px solid #667eea;
            color: #667eea;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }
            
            .main-content {
                margin-right: 200px;
            }
        }
        
        @media (max-width: 640px) {
            .sidebar {
                display: none;
            }
            
            .main-content {
                margin-right: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar shadow-lg">
        <div class="p-6 text-white">
            <div class="flex items-center gap-3 mb-2">
                <i class="fas fa-shield-alt text-2xl"></i>
                <h1 class="text-xl font-bold">ادمین</h1>
            </div>
            <p class="text-sm text-purple-100">Vamban Bot</p>
        </div>
        
        <!-- Navigation Menu -->
        <nav class="mt-6">
            <a href="#" onclick="switchTab('dashboard')" class="nav-item active block px-6 py-3 text-white">
                <i class="fas fa-th-large ml-3"></i>داشبورد
            </a>
            <a href="#" onclick="switchTab('kyc')" class="nav-item block px-6 py-3 text-white hover:text-yellow-200">
                <i class="fas fa-id-card ml-3"></i>درخواست‌های KYC
            </a>
            <a href="#" onclick="switchTab('transactions')" class="nav-item block px-6 py-3 text-white hover:text-green-200">
                <i class="fas fa-exchange-alt ml-3"></i>معاملات
            </a>
            <a href="#" onclick="switchTab('users')" class="nav-item block px-6 py-3 text-white hover:text-blue-200">
                <i class="fas fa-users ml-3"></i>کاربران
            </a>
            <a href="#" onclick="switchTab('logs')" class="nav-item block px-6 py-3 text-white hover:text-red-200">
                <i class="fas fa-history ml-3"></i>فعالیت‌های حساس
            </a>
            <a href="#" onclick="logout()" class="nav-item block px-6 py-3 text-white hover:bg-red-500 mt-auto">
                <i class="fas fa-sign-out-alt ml-3"></i>خروج
            </a>
        </nav>
    </div>
    
    <!-- Main Content -->
    <div class="main-content p-6">
        <!-- Top Bar -->
        <div class="bg-white rounded-lg shadow p-4 mb-6 flex items-center justify-between">
            <div>
                <h2 id="pageTitle" class="text-2xl font-bold text-gray-800">داشبورد</h2>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-gray-600">
                    <i class="fas fa-user-circle text-2xl text-purple-600"></i>
                    <span id="adminName" class="ml-2">ادمین</span>
                </div>
            </div>
        </div>
        
        <!-- Dashboard Tab -->
        <div id="dashboard" class="tab-content">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="stat-card bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">کاربران کل</p>
                            <p class="text-3xl font-bold text-gray-800" id="totalUsers">0</p>
                        </div>
                        <i class="fas fa-users text-4xl text-blue-500 opacity-20"></i>
                    </div>
                </div>
                
                <div class="stat-card bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">درخواست‌های معلق</p>
                            <p class="text-3xl font-bold text-gray-800" id="pendingKYC">0</p>
                        </div>
                        <i class="fas fa-hourglass-half text-4xl text-yellow-500 opacity-20"></i>
                    </div>
                </div>
                
                <div class="stat-card bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">معاملات امروز</p>
                            <p class="text-3xl font-bold text-gray-800" id="todayEscrows">0</p>
                        </div>
                        <i class="fas fa-exchange-alt text-4xl text-green-500 opacity-20"></i>
                    </div>
                </div>
                
                <div class="stat-card bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">کمیسیون امروز</p>
                            <p class="text-3xl font-bold text-gray-800" id="todayFees">۰ تومان</p>
                        </div>
                        <i class="fas fa-chart-line text-4xl text-purple-500 opacity-20"></i>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">اقدامات سریع</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <button onclick="switchTab('kyc')" class="bg-gradient-to-r from-yellow-400 to-yellow-500 text-white py-3 px-4 rounded-lg hover:shadow-lg transition">
                        <i class="fas fa-id-card"></i> بررسی KYC
                    </button>
                    <button onclick="switchTab('transactions')" class="bg-gradient-to-r from-green-400 to-green-500 text-white py-3 px-4 rounded-lg hover:shadow-lg transition">
                        <i class="fas fa-exchange-alt"></i> معاملات
                    </button>
                    <button onclick="switchTab('logs')" class="bg-gradient-to-r from-red-400 to-red-500 text-white py-3 px-4 rounded-lg hover:shadow-lg transition">
                        <i class="fas fa-history"></i> لاگ‌ها
                    </button>
                    <button onclick="window.open('/')" class="bg-gradient-to-r from-blue-400 to-blue-500 text-white py-3 px-4 rounded-lg hover:shadow-lg transition">
                        <i class="fas fa-home"></i> صفحه‌ی اصلی
                    </button>
                </div>
            </div>
        </div>
        
        <!-- KYC Tab -->
        <div id="kyc" class="tab-content hidden">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">درخواست‌های احراز هویت</h3>
                <div id="kycList" class="space-y-4">
                    <p class="text-gray-500 text-center py-8">درحال بارگذاری...</p>
                </div>
            </div>
        </div>
        
        <!-- Transactions Tab -->
        <div id="transactions" class="tab-content hidden">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">معاملات</h3>
                <div id="transactionsList" class="space-y-4">
                    <p class="text-gray-500 text-center py-8">درحال بارگذاری...</p>
                </div>
            </div>
        </div>
        
        <!-- Users Tab -->
        <div id="users" class="tab-content hidden">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">کاربران</h3>
                <div id="usersList" class="space-y-4">
                    <p class="text-gray-500 text-center py-8">درحال بارگذاری...</p>
                </div>
            </div>
        </div>
        
        <!-- Logs Tab -->
        <div id="logs" class="tab-content hidden">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">فعالیت‌های حساس</h3>
                <div id="logsList" class="space-y-4">
                    <p class="text-gray-500 text-center py-8">درحال بارگذاری...</p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Get Auth Token
        function getAuthToken() {
            return localStorage.getItem('access_token');
        }
        
        // Check Authentication
        function checkAuth() {
            if (!getAuthToken()) {
                window.location.href = '/admin/login.php';
            } else {
                loadUserInfo();
            }
        }
        
        // Load User Info
        async function loadUserInfo() {
            const token = getAuthToken();
            const userInfo = JSON.parse(localStorage.getItem('user_info') || '{}');
            
            if (userInfo.full_name) {
                document.getElementById('adminName').textContent = userInfo.full_name;
            }
        }
        
        // Switch Tab
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.add('hidden');
            });
            
            // Show selected tab
            document.getElementById(tabName).classList.remove('hidden');
            
            // Update nav items
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
            });
            event.target.closest('.nav-item').classList.add('active');
            
            // Update page title
            const titles = {
                'dashboard': 'داشبورد',
                'kyc': 'درخواست‌های احراز هویت',
                'transactions': 'معاملات',
                'users': 'کاربران',
                'logs': 'فعالیت‌های حساس'
            };
            document.getElementById('pageTitle').textContent = titles[tabName] || 'داشبورد';
            
            // Load data for specific tabs
            if (tabName === 'kyc') loadKYCRequests();
            if (tabName === 'transactions') loadTransactions();
            if (tabName === 'users') loadUsers();
            if (tabName === 'logs') loadLogs();
        }
        
        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/\"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        // Load KYC Requests
        async function loadKYCRequests() {
            const token = getAuthToken();
            const list = document.getElementById('kycList');
            
            try {
                const response = await fetch('/api/admin/kyc/list.php?status=pending', {
                    headers: {
                        'Authorization': `Bearer ${token}`
                    }
                });
                
                const data = await response.json();
                
                if (data.success && Array.isArray(data.data) && data.data.length > 0) {
                    list.innerHTML = data.data.map(kyc => {
                        const nationalId = escapeHtml(kyc.national_id || '-');
                        const phone = escapeHtml(kyc.phone || '-');
                        const address = escapeHtml(kyc.address || '-');
                        const postalCode = escapeHtml(kyc.postal_code || '-');
                        const imageName = escapeHtml(kyc.national_card_back_image || '');
                        const imageLink = imageName
                            ? `<a href="/uploads/kyc/${imageName}" target="_blank" class="text-blue-600 hover:underline">مشاهده عکس</a>`
                            : '<span class="text-gray-500">ثبت نشده</span>';

                        return `
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <p class="font-semibold text-gray-800">${escapeHtml(kyc.full_name || 'نام ثبت نشده')}</p>
                                        <p class="text-sm text-gray-500">#${escapeHtml(kyc.id)}</p>
                                    </div>
                                    <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm">در انتظار</span>
                                </div>
                                <div class="grid gap-2 text-sm text-gray-600 mb-3">
                                    <p><i class="fas fa-phone ml-2"></i>${phone}</p>
                                    <p><i class="fas fa-id-card ml-2"></i>کد ملی: ${nationalId}</p>
                                    <p><i class="fas fa-map-marker-alt ml-2"></i>آدرس: ${address}</p>
                                    <p><i class="fas fa-mailbox ml-2"></i>کد پستی: ${postalCode}</p>
                                    <p><i class="fas fa-image ml-2"></i>پشت کارت ملی: ${imageLink}</p>
                                </div>
                                <button onclick="approveKYC(${kyc.id})" class="bg-green-500 text-white px-3 py-1 text-sm rounded hover:bg-green-600 ml-2">
                                    تایید
                                </button>
                                <button onclick="rejectKYCRequest(${kyc.id})" class="bg-red-500 text-white px-3 py-1 text-sm rounded hover:bg-red-600">
                                    رد
                                </button>
                            </div>
                        `;
                    }).join('');
                } else {
                    list.innerHTML = '<p class="text-gray-500 text-center py-8">درخواست منتظری موجود نیست</p>';
                }
            } catch (error) {
                console.error('خطا:', error);
                list.innerHTML = '<p class="text-red-500 text-center py-8">خطا در بارگذاری</p>';
            }
        }
        
        // Approve KYC
        async function approveKYC(verificationId) {
            const token = getAuthToken();
            const notes = prompt('یادداشت‌ها (اختیاری):');
            
            if (notes !== null) {
                try {
                    const response = await fetch('/api/admin/kyc/approve.php', {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            verification_id: verificationId,
                            notes: notes
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        alert('درخواست تایید شد');
                        loadKYCRequests();
                    } else {
                        alert('خطا: ' + data.error);
                    }
                } catch (error) {
                    console.error('خطا:', error);
                    alert('خطا در ارتباط با سرور');
                }
            }
        }
        
        // Reject KYC
        async function rejectKYCRequest(verificationId) {
            const token = getAuthToken();
            const reason = prompt('دلیل رد کردن:');
            
            if (reason !== null && reason.trim()) {
                try {
                    const response = await fetch('/api/admin/kyc/reject.php', {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            verification_id: verificationId,
                            reason: reason
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        alert('درخواست رد شد');
                        loadKYCRequests();
                    } else {
                        alert('خطا: ' + data.error);
                    }
                } catch (error) {
                    console.error('خطا:', error);
                    alert('خطا در ارتباط با سرور');
                }
            }
        }
        
        // Load Transactions (Placeholder)
        async function loadTransactions() {
            document.getElementById('transactionsList').innerHTML = '<p class="text-gray-500 text-center py-8">به زودی فعال خواهد شد</p>';
        }
        
        // Load Users (Placeholder)
        async function loadUsers() {
            document.getElementById('usersList').innerHTML = '<p class="text-gray-500 text-center py-8">به زودی فعال خواهد شد</p>';
        }
        
        // Load Logs (Placeholder)
        async function loadLogs() {
            document.getElementById('logsList').innerHTML = '<p class="text-gray-500 text-center py-8">به زودی فعال خواهد شد</p>';
        }
        
        // Logout
        function logout() {
            if (confirm('آیا مطمئن هستید که می‌خواهید خروج کنید؟')) {
                localStorage.removeItem('access_token');
                localStorage.removeItem('user_info');
                window.location.href = '/admin/login.php';
            }
        }
        
        // Initialize
        checkAuth();
    </script>
</body>
</html>
