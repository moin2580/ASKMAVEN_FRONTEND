<?php
/**
 * Registration page for Hybrid Chatbot System
 */

require_once __DIR__ . '/../includes/database.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirectTo('/AskMaven/index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($name) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'Please fill in all fields.';
    } elseif (!validateEmail($email)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $db = Database::getInstance();
            
            // Check if email already exists
            $existingUser = $db->fetch(
                "SELECT id FROM users WHERE email = ?",
                [$email]
            );
            
            if ($existingUser) {
                $error = 'An account with this email already exists.';
            } else {
                // Create new user
                $userId = $db->insert('users', [
                    'name' => $name,
                    'email' => $email,
                    'password_hash' => hashPassword($password),
                    'role' => 'user',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                if ($userId) {
                    $success = 'Account created successfully! You can now log in.';
                    logActivity($userId, 'register', 'New user registered');
                } else {
                    $error = 'Failed to create account. Please try again.';
                }
            }
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            $error = 'An error occurred. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - AskMaven</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="/AskMaven/assets/css/theme.css" rel="stylesheet">
    <style>
        /* Register Page Specific Styles */
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .register-container {
            min-height: calc(100vh - 60px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 20px 80px 20px;
            position: relative;
        }
        
        .register-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 35px 30px;
            width: 100%;
            max-width: 420px;
            min-height: 520px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            text-align: center;
            animation: fadeIn 1s ease-out;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .register-logo {
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
        
        .register-logo::before {
            content: 'AM';
            color: white;
            font-size: 28px;
            font-weight: bold;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        
        .register-logo::after {
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
        
        .register-title {
            color: white;
            font-size: 1.6rem;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .register-subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
            margin-bottom: 25px;
        }
        
        .form-group {
            margin-bottom: 18px;
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
            padding: 12px 15px 12px 45px;
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
        
        .form-text {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.8rem;
            margin-top: 4px;
        }
        
        .register-btn {
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
        
        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
        }
        
        .register-divider {
            color: rgba(255, 255, 255, 0.6);
            margin: 18px 0 15px 0;
            font-size: 0.9rem;
            text-align: center;
        }
        
        .signin-btn {
            background: linear-gradient(135deg, #00d4ff 0%, #5b73e8 100%);
            box-shadow: 0 8px 25px rgba(0, 212, 255, 0.3);
        }
        
        .signin-btn:hover {
            box-shadow: 0 12px 35px rgba(0, 212, 255, 0.4);
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
        
        .alert-success {
            background: rgba(25, 135, 84, 0.1);
            border: 1px solid rgba(25, 135, 84, 0.2);
            color: #198754;
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
            .register-container {
                padding: 15px 15px 70px 15px;
                min-height: calc(100vh - 60px);
            }
            
            .register-card {
                padding: 25px 20px;
                border-radius: 15px;
                min-height: 450px;
                max-width: 350px;
            }
            
            .register-logo {
                width: 55px;
                height: 55px;
                margin-bottom: 15px;
            }
            
            .register-logo::before {
                font-size: 20px;
            }
            
            .register-title {
                font-size: 1.4rem;
            }
            
            .register-subtitle {
                font-size: 0.85rem;
                margin-bottom: 20px;
            }
            
            .form-control {
                padding: 10px 12px 10px 40px;
                font-size: 0.9rem;
            }
            
            .register-btn {
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
            .register-card {
                padding: 25px 20px;
            }
            
            .register-title {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-logo"></div>
            <h2 class="register-title">Join AskMaven</h2>
            <p class="register-subtitle">Create your AI assistant account</p>
                        
            <?php if ($error): ?>
                <div class="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="name" class="form-label">Full Name</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?php echo htmlspecialchars($name ?? ''); ?>" 
                               placeholder="Enter your full name" required>
                        <i class="fas fa-user input-icon"></i>
                    </div>
                </div>
                
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
                               placeholder="Create a password" minlength="6" required>
                        <i class="fas fa-lock input-icon"></i>
                    </div>
                    <small class="form-text">Password must be at least 6 characters long.</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="confirm_password" 
                               name="confirm_password" placeholder="Confirm your password" required>
                        <i class="fas fa-lock input-icon"></i>
                    </div>
                </div>
                
                <button type="submit" class="register-btn">
                    <i class="fas fa-user-plus me-2"></i>Create Account
                </button>
            </form>
            
            <div class="register-divider">Already have an account?</div>
            
            <a href="/AskMaven/auth/login.php" class="register-btn signin-btn">
                <i class="fas fa-sign-in-alt me-2"></i>Sign In
            </a>
        </div>
    </div>
    
    <!-- Footer -->
    <div class="footer">
        <p class="footer-text">
            Developed with <span class="heart">♥</span> by Moin &copy; 2024 AskMaven. All rights reserved.
        </p>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
    
    <!-- Footer -->
    <footer class="footer-glass">
        <div class="container">
            <div class="footer-content">
                <div class="developer-info">
                    Developed with <span class="heart">♥</span> by Moin
                </div>
                <div class="copyright">
                    <span>&copy; 2024 AskMaven. All rights reserved.</span>
                </div>
            </div>
        </div>
    </footer>
    
    <script src="/AskMaven/assets/js/animations.js"></script>
</body>
</html>
