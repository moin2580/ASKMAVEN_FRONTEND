<?php
/**
 * Login page for Hybrid Chatbot System
 */

require_once __DIR__ . '/../includes/database.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirectTo('/index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Check if this is an AJAX request
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } elseif (!validateEmail($email)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            $db = Database::getInstance();
            $user = $db->fetch(
                "SELECT id, name, email, password_hash, role FROM users WHERE email = ?",
                [$email]
            );
            
            if ($user && verifyPassword($password, $user['password_hash'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['login_time'] = time();
                
                // Log activity
                logActivity($user['id'], 'login', 'User logged in successfully');
                
                // Handle AJAX vs normal requests differently
                if ($isAjax) {
                    // Return JSON response for AJAX
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Login successful',
                        'redirect' => '/index.php'
                    ]);
                    exit;
                } else {
                    // Normal redirect for non-AJAX requests
                    redirectTo('/index.php');
                }
            } else {
                $error = 'Invalid email or password.';
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'An error occurred. Please try again.';
        }
    }
    
    // Handle AJAX error responses
    if ($isAjax && !empty($error)) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $error
        ]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="/assets/css/theme.css" rel="stylesheet">
    <style>
        /* Login Page Specific Styles */
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .login-container {
            min-height: calc(100vh - 60px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 20px 80px 20px;
            position: relative;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 35px 30px;
            width: 100%;
            max-width: 420px;
            min-height: 480px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            text-align: center;
            animation: fadeIn 1s ease-out;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .login-logo {
            width: 70px;
            height: 70px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .login-logo::before {
            content: 'AM';
            color: white;
            font-size: 28px;
            font-weight: bold;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        
        .login-logo::after {
            content: '';
            position: absolute;
            top: 10px;
            right: 10px;
            width: 8px;
            height: 8px;
            background: #00d4ff;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        .login-title {
            color: white;
            font-size: 1.6rem;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .login-subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
            margin-bottom: 25px;
        }
        
        .form-group {
            margin-bottom: 20px;
            text-align: left;
            position: relative;
        }
        
        .form-label {
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 6px;
            display: block;
            padding-left: 2px;
        }
        
        .input-group {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
            z-index: 2;
            pointer-events: none;
        }
        
        .form-control {
            width: 100%;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            padding: 13px 15px 13px 45px;
            color: white;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
            margin: 0;
        }
        
        .form-control:focus + .input-icon {
            color: rgba(255, 255, 255, 0.8);
        }
        
        .form-control:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.4);
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.1);
            outline: none;
        }
        
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        
        .login-btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            color: white;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
            cursor: pointer;
            margin: 8px 0 15px 0;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
        }
        
        .login-divider {
            color: rgba(255, 255, 255, 0.6);
            margin: 18px 0 15px 0;
            font-size: 0.9rem;
            text-align: center;
        }
        
        .create-btn {
            background: linear-gradient(135deg, #00d4ff 0%, #5b73e8 100%);
            box-shadow: 0 8px 25px rgba(0, 212, 255, 0.3);
        }
        
        .create-btn:hover {
            box-shadow: 0 12px 35px rgba(0, 212, 255, 0.4);
        }
        
        .demo-info {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.8rem;
            margin-top: 20px;
        }
        
        /* Loading Spinner Styles */
        .loading-spinner {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
        }
        
        .spinner-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            animation: clockwiseSpin 1.5s linear infinite;
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.5);
        }
        
        .spinner-logo::before {
            content: "AM";
            color: white;
            font-size: 48px;
            font-weight: 900;
            text-shadow: 0 3px 15px rgba(0, 0, 0, 0.4);
            letter-spacing: -2px;
        }
        

        
        @keyframes clockwiseSpin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.3; transform: scale(0.7); }
        }

        .login-btn.loading {
            opacity: 0.7;
            pointer-events: none;
        }
        
        .alert {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.2);
            color: #dc3545;
            padding: 12px 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            backdrop-filter: blur(10px);
        }
        
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            padding: 12px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            width: 100%;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .footer-text {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.85rem;
            margin: 0;
            white-space: nowrap;
        }
        
        .heart {
            color: #ff6b6b;
            animation: heartbeat 2s ease-in-out infinite;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        @keyframes heartbeat {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        /* Responsive Design */
        @media (max-width: 576px) {
            .login-container {
                padding: 15px 15px 70px 15px;
                min-height: calc(100vh - 60px);
            }
            
            .login-card {
                padding: 25px 20px;
                border-radius: 15px;
                min-height: 400px;
                max-width: 350px;
            }
            
            .login-logo {
                width: 55px;
                height: 55px;
                margin-bottom: 15px;
            }
            
            .login-logo::before {
                font-size: 20px;
            }
            
            .login-title {
                font-size: 1.4rem;
            }
            
            .login-subtitle {
                font-size: 0.85rem;
                margin-bottom: 20px;
            }
            
            .form-control {
                padding: 10px 12px;
                font-size: 0.9rem;
            }
            
            .login-btn {
                padding: 12px;
                font-size: 0.95rem;
            }
            
            .footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                margin-top: 0;
                border-top: 1px solid rgba(255, 255, 255, 0.1);
                text-align: center;
                width: 100%;
                padding: 12px 15px;
            }
        }
        
        @media (max-width: 400px) {
            .login-card {
                padding: 25px 20px;
            }
            
            .login-title {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-logo"></div>
            <h2 class="login-title">Welcome to AskMaven</h2>
            <p class="login-subtitle">Sign in to your AI assistant</p>
            
            <?php if ($error): ?>
                <div class="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($email ?? ''); ?>" 
                               placeholder="Enter your email" required>
                        <i class="fas fa-envelope input-icon"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Enter your password" required>
                        <i class="fas fa-lock input-icon"></i>
                    </div>
                </div>
                
                <button type="submit" class="login-btn" id="loginBtn">
                    <i class="fas fa-sign-in-alt me-2"></i>Sign In
                </button>
            </form>
            
            <div class="login-divider">Don't have an account?</div>
            
            <a href="/auth/register.php" class="login-btn create-btn">
                <i class="fas fa-user-plus me-2"></i>Create Account
            </a>
            
            <div class="demo-info">
                <i class="fas fa-info-circle me-1"></i>
                Demo: admin@hybridchatbot.com / admin123
        </div>
    </div>
    
    <!-- Loading Spinner -->
    <div class="loading-spinner" id="loadingSpinner">
        <div class="spinner-logo"></div>
    </div>
    
    <!-- Footer -->
    <div class="footer">
        <p class="footer-text">
            Developed with <span class="heart">♥</span> by Moin &copy; 2024 AskMaven. All rights reserved.
        </p>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/animations.js"></script>
    
    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
        <div id="networkToast" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body" id="toastMessage">
                    <!-- Toast message will be inserted here -->
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
    
    <script>
        // Enhanced login form handler with network error detection and performance optimization
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent default form submission
            
            const spinner = document.getElementById('loadingSpinner');
            const loginBtn = document.getElementById('loginBtn');
            const form = this;
            const formData = new FormData(form);
            
            // Performance optimization: Start timer
            const startTime = Date.now();
            
            // Show loading state
            spinner.style.display = 'flex';
            loginBtn.classList.add('loading');
            loginBtn.disabled = true;
            
            // Network timeout handler (8 seconds)
            const timeoutId = setTimeout(() => {
                showToast('Network timeout. Please check your connection and try again.', 'error');
                hideLoading();
            }, 8000);
            
            // Optimized fetch with timeout and error handling
            fetch(form.action || window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: AbortSignal.timeout(10000) // 10 second timeout
            })
            .then(response => {
                clearTimeout(timeoutId);
                const responseTime = Date.now() - startTime;
                
                // Check for slow response (over 3 seconds)
                if (responseTime > 3000) {
                    showToast('Slow network detected. Login may take longer than usual.', 'warning');
                }
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                // Check if response is JSON
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json();
                } else {
                    return response.text();
                }
            })
            .then(data => {
                // Handle JSON response (AJAX) vs HTML response (fallback)
                if (typeof data === 'object' && data !== null) {
                    // JSON response from PHP
                    if (data.success) {
                        showToast('Login successful! Redirecting...', 'success');
                        setTimeout(() => {
                            window.location.href = data.redirect || '/index.php';
                        }, 1000);
                    } else {
                        showToast(data.message || 'Login failed. Please try again.', 'error');
                        hideLoading();
                    }
                } else {
                    // HTML response (fallback) - parse as before
                    const html = data;
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    
                    // Check if we got redirected or if there's an error message
                    const errorElement = doc.querySelector('.alert-danger, .error, .text-danger');
                    const successElement = doc.querySelector('.alert-success, .success');
                    
                    if (errorElement) {
                        // Login failed - show error message
                        const errorMessage = errorElement.textContent.trim() || 'Login failed. Please check your credentials.';
                        showToast(errorMessage, 'error');
                        hideLoading();
                    } else if (html.includes('<!DOCTYPE html>') && !html.includes('login-card')) {
                        // Successfully redirected to another page (not login page)
                        showToast('Login successful! Redirecting...', 'success');
                        setTimeout(() => {
                            window.location.href = '/index.php';
                        }, 1000);
                    } else if (successElement) {
                        // Explicit success message found
                        showToast('Login successful! Redirecting...', 'success');
                        setTimeout(() => {
                            window.location.href = '/index.php';
                        }, 1000);
                    } else {
                        // Check if we're still on login page (login failed)
                        if (html.includes('login-card') || html.includes('loginForm')) {
                            showToast('Login failed. Please check your credentials.', 'error');
                            hideLoading();
                        } else {
                            // Assume success if we're not on login page anymore
                            showToast('Login successful! Redirecting...', 'success');
                            setTimeout(() => {
                                window.location.href = '/index.php';
                            }, 1000);
                        }
                    }
                }
            })
            .catch(error => {
                clearTimeout(timeoutId);
                console.error('Login error:', error);
                
                let errorMessage = 'Network error. Please try again.';
                
                if (error.name === 'AbortError' || error.message.includes('timeout')) {
                    errorMessage = 'Request timed out. Please check your internet connection.';
                } else if (error.message.includes('Failed to fetch') || error.message.includes('NetworkError')) {
                    errorMessage = 'Network connection failed. Please check your internet and try again.';
                } else if (error.message.includes('HTTP 5')) {
                    errorMessage = 'Server error. Please try again in a moment.';
                } else if (error.message.includes('HTTP 4')) {
                    errorMessage = 'Authentication error. Please check your credentials.';
                }
                
                showToast(errorMessage, 'error');
                hideLoading();
            });
        });
        
        function showToast(message, type = 'info') {
            const toast = document.getElementById('networkToast');
            const toastMessage = document.getElementById('toastMessage');
            
            // Set message
            toastMessage.innerHTML = `<i class="fas fa-${getToastIcon(type)} me-2"></i>${message}`;
            
            // Set toast color based on type
            toast.className = `toast align-items-center text-white border-0 bg-${getToastColor(type)}`;
            
            // Show toast
            const bsToast = new bootstrap.Toast(toast, {
                autohide: type === 'success' ? true : false,
                delay: type === 'success' ? 3000 : 5000
            });
            bsToast.show();
        }
        
        function getToastIcon(type) {
            switch(type) {
                case 'success': return 'check-circle';
                case 'error': return 'exclamation-triangle';
                case 'warning': return 'exclamation-circle';
                default: return 'info-circle';
            }
        }
        
        function getToastColor(type) {
            switch(type) {
                case 'success': return 'success';
                case 'error': return 'danger';
                case 'warning': return 'warning';
                default: return 'info';
            }
        }
        
        function hideLoading() {
            const spinner = document.getElementById('loadingSpinner');
            const loginBtn = document.getElementById('loginBtn');
            
            spinner.style.display = 'none';
            loginBtn.classList.remove('loading');
            loginBtn.disabled = false;
        }
        
        // Performance monitoring
        window.addEventListener('load', function() {
            // Check if page loaded slowly
            if (performance.timing.loadEventEnd - performance.timing.navigationStart > 5000) {
                showToast('Slow page load detected. This may be due to network issues.', 'warning');
            }
        });
        
        // Network status monitoring
        window.addEventListener('online', function() {
            showToast('Connection restored.', 'success');
        });
        
        window.addEventListener('offline', function() {
            showToast('No internet connection. Please check your network.', 'error');
        });
    </script>
</body>
</html>
