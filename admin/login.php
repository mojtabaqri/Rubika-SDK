<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود پنل ادمین</title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.4/dist/tailwind.min.css">
    
    <!-- Vazir Font (فارسی) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vazir-font@30.1.0/dist/font-face.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    
    <style>
        * {
            font-family: 'Vazir', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .login-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .btn-login {
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .input-field {
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        
        .input-field:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .error-message {
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .loading {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <!-- Logo/Header -->
            <div class="text-center mb-8">
                <div class="inline-block bg-white rounded-full p-4 mb-4">
                    <i class="fas fa-shield-alt text-4xl text-purple-600"></i>
                </div>
                <h1 class="text-4xl font-bold text-white mb-2">پنل ادمین</h1>
                <p class="text-purple-100">سیستم مدیریت Vamban Bot</p>
            </div>
            
            <!-- Login Card -->
            <div class="login-card rounded-2xl p-8 mb-6">
                <form id="loginForm" class="space-y-6">
                    <!-- Username Field -->
                    <div>
                        <label for="username" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-user ml-2"></i>نام کاربری
                        </label>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="w-full input-field rounded-lg px-4 py-3 focus:outline-none"
                            placeholder="نام کاربری خود را وارد کنید"
                            required
                            autocomplete="username"
                        >
                    </div>
                    
                    <!-- Password Field -->
                    <div>
                        <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-lock ml-2"></i>رمز عبور
                        </label>
                        <div class="relative">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="w-full input-field rounded-lg px-4 py-3 focus:outline-none"
                                placeholder="رمز عبور خود را وارد کنید"
                                required
                                autocomplete="current-password"
                            >
                            <button
                                type="button"
                                class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                onclick="togglePasswordVisibility()"
                            >
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Error Message -->
                    <div id="errorMessage" class="hidden error-message bg-red-50 border border-red-200 rounded-lg p-4 text-red-700">
                        <i class="fas fa-exclamation-circle ml-2"></i>
                        <span id="errorText"></span>
                    </div>
                    
                    <!-- Submit Button -->
                    <button
                        type="submit"
                        class="w-full btn-login bg-gradient-to-r from-purple-600 to-purple-700 text-white font-semibold py-3 rounded-lg hover:from-purple-700 hover:to-purple-800 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span id="buttonText">ورود به سیستم</span>
                        <i id="buttonIcon" class="fas fa-sign-in-alt"></i>
                    </button>
                </form>
            </div>
            
            <!-- Footer -->
            <div class="text-center">
                <p class="text-purple-100 text-sm">
                    <i class="fas fa-info-circle ml-1"></i>
                    برای دسترسی غیرمجاز، تلاش‌های ناموفق ثبت می‌شود
                </p>
            </div>
        </div>
    </div>
    
    <script>
        // Toggle Password Visibility
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = event.target.closest('button').querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }
        
        // Handle Login Form
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            const errorMessage = document.getElementById('errorMessage');
            const button = e.target.querySelector('button[type="submit"]');
            const buttonText = document.getElementById('buttonText');
            const buttonIcon = document.getElementById('buttonIcon');
            
            // Clear previous errors
            errorMessage.classList.add('hidden');
            
            // Disable button
            button.disabled = true;
            buttonText.innerHTML = 'درحال بررسی...';
            buttonIcon.classList.add('loading');
            
            try {
                const response = await fetch('/api/auth/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        username,
                        password,
                        is_admin: true
                    })
                });
                
                const data = await response.json();
                
                if (data.success && data.data && data.data.access_token) {
                    // Store token in localStorage
                    localStorage.setItem('access_token', data.data.access_token);
                    localStorage.setItem('user_info', JSON.stringify(data.data));
                    
                    // Redirect to dashboard
                    window.location.href = '/admin/dashboard.php';
                } else {
                    // Show error
                    const errorText = document.getElementById('errorText');
                    errorText.textContent = data.error || 'خطای نامشخص';
                    errorMessage.classList.remove('hidden');
                }
            } catch (error) {
                console.error('خطا:', error);
                const errorText = document.getElementById('errorText');
                errorText.textContent = 'خطای اتصال به سرور';
                errorMessage.classList.remove('hidden');
            } finally {
                // Re-enable button
                button.disabled = false;
                buttonText.innerHTML = 'ورود به سیستم';
                buttonIcon.classList.remove('loading');
            }
        });
        
        // Check if already logged in
        if (localStorage.getItem('access_token')) {
            window.location.href = '/admin/dashboard.php';
        }
    </script>
</body>
</html>
