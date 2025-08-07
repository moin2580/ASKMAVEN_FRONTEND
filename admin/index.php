<?php
/**
 * Admin dashboard for Hybrid Chatbot System
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/api_client.php';

// Require admin access
requireAdmin();

$apiClient = new ApiClient();
$db = Database::getInstance();

// Get system statistics
try {
    $apiStats = $apiClient->getSystemStats();
} catch (Exception $e) {
    $apiStats = [];
}

// Get database statistics
$dbStats = [
    'total_users' => $db->fetch("SELECT COUNT(*) as count FROM users")['count'] ?? 0,
    'total_pages' => $db->fetch("SELECT COUNT(*) as count FROM scraped_pages WHERE status = 'scraped'")['count'] ?? 0,
    'total_chats' => $db->fetch("SELECT COUNT(*) as count FROM chat_history")['count'] ?? 0,
    'total_sitemaps' => $db->fetch("SELECT COUNT(*) as count FROM sitemap_sources")['count'] ?? 0,
    'recent_users' => $db->fetch("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['count'] ?? 0,
    'recent_chats' => $db->fetch("SELECT COUNT(*) as count FROM chat_history WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")['count'] ?? 0
];

// Get recent activity
$recentUsers = $db->fetchAll(
    "SELECT name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 10"
);

$recentChats = $db->fetchAll(
    "SELECT ch.question, ch.answer, ch.timestamp, u.name as user_name 
     FROM chat_history ch 
     JOIN users u ON ch.user_id = u.id 
     ORDER BY ch.timestamp DESC LIMIT 10"
);

$activeSitemaps = $db->fetchAll(
    "SELECT ss.domain, ss.status, ss.total_pages, ss.scraped_pages, ss.last_scraped, u.name as created_by
     FROM sitemap_sources ss
     JOIN users u ON ss.created_by = u.id
     ORDER BY ss.created_at DESC LIMIT 10"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - AskMaven</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="/AskMaven/assets/css/theme.css" rel="stylesheet">
    <style>
        .main-container { margin-top: 100px; }
        .stat-icon { font-size: 2rem; opacity: 0.9; color: white; }
        .admin-badge { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%); }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-glass fixed-top">
        <div class="container">
            <a class="navbar-brand" href="/AskMaven/admin/">
                <div class="askmaven-logo"></div>
                <span class="admin-badge px-2 py-1 rounded-pill ms-2 small">Admin</span>
                AskMaven
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="/AskMaven/">
                    <i class="fas fa-home me-1"></i>Main Site
                </a>
                <a class="nav-link" href="/AskMaven/auth/logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container main-container">
        <!-- Page Header -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="glass-card p-4 text-center fade-in">
                    <div class="feature-icon-glass mx-auto mb-3">
                        <i class="fas fa-tachometer-alt"></i>
                    </div>
                    <h1 class="text-white mb-2">Admin Dashboard</h1>
                    <p class="text-light">Monitor and manage the AskMaven system</p>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card stat-card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h3 class="mb-0"><?php echo number_format($dbStats['total_users']); ?></h3>
                                <p class="mb-0">Total Users</p>
                                <small>+<?php echo $dbStats['recent_users']; ?> this week</small>
                            </div>
                            <i class="fas fa-users stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card stat-card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h3 class="mb-0"><?php echo number_format($dbStats['total_pages']); ?></h3>
                                <p class="mb-0">Scraped Pages</p>
                                <small><?php echo $dbStats['total_sitemaps']; ?> websites</small>
                            </div>
                            <i class="fas fa-file-alt stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card stat-card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h3 class="mb-0"><?php echo number_format($dbStats['total_chats']); ?></h3>
                                <p class="mb-0">Total Chats</p>
                                <small><?php echo $dbStats['recent_chats']; ?> today</small>
                            </div>
                            <i class="fas fa-comments stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card stat-card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h3 class="mb-0"><?php echo count($activeSitemaps); ?></h3>
                                <p class="mb-0">Active Sitemaps</p>
                                <small>Scraping sources</small>
                            </div>
                            <i class="fas fa-spider stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row">
            <!-- Recent Users -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-plus me-2"></i>Recent Users
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentUsers)): ?>
                            <p class="text-muted">No users yet</p>
                        <?php else: ?>
                            <?php foreach ($recentUsers as $user): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                        <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?> ms-1">
                                            <?php echo $user['role']; ?>
                                        </span>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo timeAgo($user['created_at']); ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Chats -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-comments me-2"></i>Recent Chats
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentChats)): ?>
                            <p class="text-muted">No chats yet</p>
                        <?php else: ?>
                            <?php foreach ($recentChats as $chat): ?>
                                <div class="mb-3 pb-2 border-bottom">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <strong class="small"><?php echo htmlspecialchars($chat['user_name']); ?></strong>
                                        <small class="text-muted"><?php echo timeAgo($chat['timestamp']); ?></small>
                                    </div>
                                    <p class="small mb-1">
                                        <i class="fas fa-question-circle text-primary me-1"></i>
                                        <?php echo htmlspecialchars(substr($chat['question'], 0, 60)); ?>
                                        <?php if (strlen($chat['question']) > 60) echo '...'; ?>
                                    </p>
                                    <p class="small text-muted mb-0">
                                        <i class="fas fa-robot me-1"></i>
                                        <?php echo htmlspecialchars(substr($chat['answer'], 0, 80)); ?>
                                        <?php if (strlen($chat['answer']) > 80) echo '...'; ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Active Sitemaps -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-spider me-2"></i>Active Sitemaps
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($activeSitemaps)): ?>
                            <p class="text-muted">No sitemaps yet</p>
                        <?php else: ?>
                            <?php foreach ($activeSitemaps as $sitemap): ?>
                                <div class="mb-3 pb-2 border-bottom">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong><?php echo htmlspecialchars($sitemap['domain']); ?></strong>
                                            <br>
                                            <span class="badge bg-<?php 
                                                echo $sitemap['status'] === 'completed' ? 'success' : 
                                                    ($sitemap['status'] === 'scraping' ? 'info' : 
                                                    ($sitemap['status'] === 'failed' ? 'danger' : 'warning')); 
                                            ?>">
                                                <?php echo ucfirst($sitemap['status']); ?>
                                            </span>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo $sitemap['scraped_pages']; ?>/<?php echo $sitemap['total_pages']; ?> pages
                                                • by <?php echo htmlspecialchars($sitemap['created_by']); ?>
                                            </small>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo $sitemap['last_scraped'] ? timeAgo($sitemap['last_scraped']) : 'Never'; ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Health -->
        <div class="row">
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5 class="card-title">Active Sitemaps</h5>
                        <h2><?php echo $stats['active_sitemaps']; ?></h2>
                    </div>
                </div>
            </div>
        </div>
            
        <!-- Export Data Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Data Export</h5>
                    </div>
                    <div class="card-body">
                        <p>Download a comprehensive report of all crawled website data.</p>
                        <a href="../api/export_data.php" class="btn btn-success" target="_blank">
                            <i class="fas fa-download"></i> Download PDF Report
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Health -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-heartbeat me-2"></i>System Health
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Database Connection</h6>
                                <?php $dbHealthy = $db->testConnection(); ?>
                                <span class="badge bg-<?php echo $dbHealthy ? 'success' : 'danger'; ?>">
                                    <?php echo $dbHealthy ? 'Connected' : 'Disconnected'; ?>
                                </span>
                            </div>
                            <div class="col-md-6">
                                <h6>Python API</h6>
                                <?php $apiHealthy = $apiClient->healthCheck(); ?>
                                <span class="badge bg-<?php echo $apiHealthy ? 'success' : 'danger'; ?>">
                                    <?php echo $apiHealthy ? 'Online' : 'Offline'; ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if (!$dbHealthy || !$apiHealthy): ?>
                            <div class="alert alert-warning mt-3">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Some system components are not functioning properly. Please check the configuration.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/AskMaven/assets/js/animations.js"></script>
</body>
</html>
