<?php
/**
 * Main dashboard for Hybrid Chatbot System
 */

require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/api_client.php';

// Require login
requireLogin();

$apiClient = new ApiClient();
$db = Database::getInstance();

// Get user's recent chat history
$recentChats = $db->fetchAll(
    "SELECT question, answer, timestamp FROM chat_history 
     WHERE user_id = ? ORDER BY timestamp DESC LIMIT 5",
    [$_SESSION['user_id']]
);

// Check Python API status
$apiHealthy = $apiClient->healthCheck();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="/AskMaven/assets/css/theme.css" rel="stylesheet">
    <style>
        /* Dashboard Page Specific Styles */
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
        }
        
        .main-container {
            padding-top: 100px;
            padding-bottom: 80px;
            min-height: calc(100vh - 60px);
        }
        
        .welcome-section {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            margin-bottom: 30px;
            animation: fadeIn 1s ease-out;
        }
        
        .welcome-logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .welcome-logo::before {
            content: 'AM';
            color: white;
            font-size: 32px;
            font-weight: bold;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        
        .welcome-logo::after {
            content: '';
            position: absolute;
            top: 12px;
            right: 12px;
            width: 10px;
            height: 10px;
            background: #00d4ff;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        .welcome-title {
            color: white;
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .welcome-subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.1rem;
            margin-bottom: 20px;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            background: rgba(25, 135, 84, 0.2);
            color: #198754;
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 500;
            border: 1px solid rgba(25, 135, 84, 0.3);
        }
        
        .status-badge i {
            margin-right: 8px;
            animation: pulse 2s infinite;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .action-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            animation: staggerFadeIn 0.6s ease-out forwards;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            background: rgba(255, 255, 255, 0.15);
        }
        
        .action-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .action-card:nth-child(2) .action-icon {
            background: linear-gradient(135deg, #00d4ff 0%, #5b73e8 100%);
            box-shadow: 0 8px 25px rgba(0, 212, 255, 0.3);
        }
        
        .action-card:nth-child(3) .action-icon {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            box-shadow: 0 8px 25px rgba(255, 107, 107, 0.3);
        }
        
        .action-title {
            color: white;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .action-description {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.95rem;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        
        .action-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .info-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .info-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 25px;
            animation: staggerFadeIn 0.8s ease-out forwards;
        }
        
        .info-card h5 {
            color: white;
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .info-card h5 i {
            margin-right: 10px;
            color: #00d4ff;
        }
        
        .chat-item {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 3px solid #00d4ff;
        }
        
        .chat-question {
            color: white;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        
        .chat-answer {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
            margin-bottom: 8px;
        }
        
        .chat-time {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.8rem;
        }
        
        .system-info {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
        }
        
        .system-info .badge {
            background: rgba(25, 135, 84, 0.2);
            color: #198754;
            border: 1px solid rgba(25, 135, 84, 0.3);
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
        
        @keyframes staggerFadeIn {
            from {
                opacity: 0;
                transform: translateY(50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
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
        @media (max-width: 768px) {
            .main-container {
                padding-top: 80px;
                padding-bottom: 70px;
            }
            
            .welcome-section {
                padding: 30px 20px;
            }
            
            .welcome-logo {
                width: 60px;
                height: 60px;
            }
            
            .welcome-logo::before {
                font-size: 24px;
            }
            
            .welcome-title {
                font-size: 1.8rem;
            }
            
            .welcome-subtitle {
                font-size: 1rem;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .action-card {
                padding: 25px 20px;
            }
            
            .action-icon {
                width: 50px;
                height: 50px;
                font-size: 20px;
            }
            
            .action-title {
                font-size: 1.1rem;
            }
            
            .info-section {
                grid-template-columns: 1fr;
            }
            
            .info-card {
                padding: 20px;
            }
        }
        
        @media (max-width: 576px) {
            .welcome-section {
                padding: 25px 15px;
                margin-bottom: 20px;
            }
            
            .action-card {
                padding: 20px 15px;
            }
            
            .info-card {
                padding: 18px 15px;
            }
            
            .chat-item {
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-glass fixed-top">
        <div class="container">
            <a class="navbar-brand" href="/AskMaven/">
                <div class="askmaven-logo"></div>
                AskMaven
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="/AskMaven/">
                            <i class="fas fa-home me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/AskMaven/chat.php">
                            <i class="fas fa-comments me-1"></i>Chat
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/AskMaven/scraping.php">
                            <i class="fas fa-spider me-1"></i>Website Scraping
                        </a>
                    </li>
                    <?php if (isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/AskMaven/admin/">
                            <i class="fas fa-cog me-1"></i>Admin
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i>
                            <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/AskMaven/profile.php">
                                <i class="fas fa-user-edit me-2"></i>Profile
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/AskMaven/auth/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container main-container">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="welcome-logo"></div>
            <h1 class="welcome-title">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
            <p class="welcome-subtitle">
                Your AskMaven AI assistant is ready to help you find information from scraped websites.
            </p>
            <div class="status-badge">
                <i class="fas fa-circle"></i>
                Python API Status: <?php echo $apiHealthy ? 'Online' : 'Offline'; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <div class="action-card">
                <div class="action-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <h3 class="action-title">Start Chatting</h3>
                <p class="action-description">Ask questions and get AI-powered answers based on scraped website content.</p>
                <a href="/AskMaven/chat.php" class="action-btn">
                    <i class="fas fa-arrow-right me-2"></i>Open Chat
                </a>
            </div>
            
            <div class="action-card">
                <div class="action-icon">
                    <i class="fas fa-spider"></i>
                </div>
                <h3 class="action-title">Scrape Website</h3>
                <p class="action-description">Add new websites by providing their sitemap URL for content extraction.</p>
                <a href="/AskMaven/scraping.php" class="action-btn">
                    <i class="fas fa-plus me-2"></i>Add Website
                </a>
            </div>
            
            <div class="action-card">
                <div class="action-icon">
                    <i class="fas fa-history"></i>
                </div>
                <h3 class="action-title">Chat History</h3>
                <p class="action-description">View your previous conversations and AI responses.</p>
                <a href="/AskMaven/chat.php" class="action-btn">
                    <i class="fas fa-list me-2"></i>View History
                </a>
            </div>
        </div>

        <!-- Information Section -->
        <div class="info-section">
            <div class="info-card">
                <h5><i class="fas fa-clock"></i>Recent Conversations</h5>
                <?php if (empty($recentChats)): ?>
                    <div class="text-center" style="color: rgba(255, 255, 255, 0.6); padding: 30px;">
                        <i class="fas fa-comments" style="font-size: 3rem; margin-bottom: 15px;"></i>
                        <p>No conversations yet. Start chatting to see your history here!</p>
                        <a href="/AskMaven/chat.php" class="action-btn" style="margin-top: 15px;">
                            <i class="fas fa-comments me-2"></i>Start Your First Chat
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentChats as $chat): ?>
                        <div class="chat-item">
                            <div class="chat-question">
                                <i class="fas fa-question-circle me-2"></i>
                                <?php echo htmlspecialchars(substr($chat['question'], 0, 80)); ?>
                                <?php if (strlen($chat['question']) > 80) echo '...'; ?>
                            </div>
                            <div class="chat-answer">
                                <i class="fas fa-robot me-2"></i>
                                <?php echo htmlspecialchars(substr($chat['answer'], 0, 120)); ?>
                                <?php if (strlen($chat['answer']) > 120) echo '...'; ?>
                            </div>
                            <div class="chat-time">
                                <?php echo timeAgo($chat['timestamp']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="text-center" style="margin-top: 20px;">
                        <a href="/AskMaven/chat.php" class="action-btn" style="font-size: 0.9rem; padding: 8px 16px;">
                            <i class="fas fa-list me-2"></i>View All Conversations
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="info-card">
                <h5><i class="fas fa-info-circle"></i>System Info</h5>
                    <div style="margin-bottom: 15px;">
                        <strong>Account Type:</strong>
                        <span class="badge <?php echo isAdmin() ? 'bg-danger' : 'bg-primary'; ?>" style="margin-left: 8px;">
                            <?php echo ucfirst($_SESSION['user_role']); ?>
                        </span>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <strong>Member Since:</strong><br>
                        <span style="color: rgba(255, 255, 255, 0.7); font-size: 0.9rem;">
                            <?php 
                            $user = $db->fetch("SELECT created_at FROM users WHERE id = ?", [$_SESSION['user_id']]);
                            echo formatDateTime($user['created_at']);
                            ?>
                        </span>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <strong>API Status:</strong><br>
                        <span class="badge <?php echo $apiHealthy ? 'bg-success' : 'bg-danger'; ?>" style="margin-top: 5px;">
                            <?php echo $apiHealthy ? 'Connected' : 'Disconnected'; ?>
                        </span>
                    </div>
                    
                    <?php if (!$apiHealthy): ?>
                        <div style="background: rgba(255, 193, 7, 0.2); border: 1px solid rgba(255, 193, 7, 0.3); border-radius: 8px; padding: 12px; margin-top: 15px;">
                            <i class="fas fa-exclamation-triangle" style="color: #ffc107; margin-right: 8px;"></i>
                            <span style="color: rgba(255, 255, 255, 0.9); font-size: 0.9rem;">Python API is offline. Some features may not work.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p class="footer-text">
            Developed with <span class="heart">♥</span> by Moin &copy; 2024 AskMaven. All rights reserved.
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/AskMaven/assets/js/animations.js"></script>
</body>
</html>
