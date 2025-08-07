<?php
/**
 * Website scraping management page
 */

require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/api_client.php';

// Require login
requireLogin();

$apiClient = new ApiClient();
$db = Database::getInstance();

$message = '';
$messageType = '';

// Handle sitemap submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'scrape') {
    $sitemapUrl = sanitizeInput($_POST['sitemap_url'] ?? '');
    
    if (empty($sitemapUrl)) {
        $message = 'Please enter a sitemap URL.';
        $messageType = 'danger';
    } elseif (!filter_var($sitemapUrl, FILTER_VALIDATE_URL)) {
        $message = 'Please enter a valid URL.';
        $messageType = 'danger';
    } else {
        try {
            $response = $apiClient->scrapeSitemap($sitemapUrl, $_SESSION['user_id']);
            $message = 'Scraping started successfully! Check the status below.';
            $messageType = 'success';
            logActivity($_SESSION['user_id'], 'scrape_started', $sitemapUrl);
        } catch (Exception $e) {
            $message = 'Failed to start scraping: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Get sitemap sources for current user or all if admin
$whereClause = isAdmin() ? '' : 'WHERE created_by = ?';
$params = isAdmin() ? [] : [$_SESSION['user_id']];

$sitemapSources = $db->fetchAll(
    "SELECT ss.*, u.name as created_by_name 
     FROM sitemap_sources ss 
     LEFT JOIN users u ON ss.created_by = u.id 
     $whereClause 
     ORDER BY ss.created_at DESC",
    $params
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website Scraping - AskMaven</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="/assets/css/theme.css" rel="stylesheet">
    <style>
        /* Scraping Page Specific Styles */
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
        
        .page-header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            margin-bottom: 30px;
            animation: fadeIn 1s ease-out;
        }
        
        .page-logo {
            width: 60px;
            height: 60px;
            margin: 0 auto 15px;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            box-shadow: 0 8px 25px rgba(255, 107, 107, 0.3);
        }
        
        .page-title {
            color: white;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .page-subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1rem;
            margin-bottom: 0;
        }
        
        .scraping-form {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            animation: slideInUp 0.6s ease-out;
        }
        
        .form-section-title {
            color: white;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .form-section-title i {
            margin-right: 10px;
            color: #ff6b6b;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            color: white;
            font-weight: 500;
            margin-bottom: 8px;
            display: block;
        }
        
        .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            padding: 12px 16px;
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #ff6b6b;
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 20px rgba(255, 107, 107, 0.3);
            color: white;
        }
        
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        
        .scrape-btn {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
            cursor: pointer;
        }
        
        .scrape-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 107, 107, 0.4);
            color: white;
        }
        
        .scrape-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .websites-table {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 30px;
            animation: slideInUp 0.8s ease-out;
        }
        
        .table-title {
            color: white;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .table-title i {
            margin-right: 10px;
            color: #00d4ff;
        }
        
        .table {
            background: transparent;
            margin-bottom: 0;
        }
        
        .table th {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-weight: 600;
            border: none;
            padding: 15px;
            font-size: 0.9rem;
        }
        
        .table td {
            background: rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.9);
            border: none;
            padding: 15px;
            vertical-align: middle;
        }
        
        .table tbody tr {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .table tbody tr:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .status-badge {
            font-size: 0.8rem;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
            display: inline-block;
        }
        
        .status-pending {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }
        
        .status-processing {
            background: rgba(0, 123, 255, 0.2);
            color: #007bff;
            border: 1px solid rgba(0, 123, 255, 0.3);
        }
        
        .status-completed {
            background: rgba(25, 135, 84, 0.2);
            color: #198754;
            border: 1px solid rgba(25, 135, 84, 0.3);
        }
        
        .status-failed {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }
        
        .progress-container {
            position: relative;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            height: 20px;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(135deg, #00d4ff 0%, #5b73e8 100%);
            border-radius: 10px;
            transition: width 0.3s ease;
        }
        
        .progress-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 0.75rem;
            font-weight: 600;
            color: white;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
        }
        
        .action-btn {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.8rem;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            margin: 0 2px;
        }
        
        .action-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            transform: translateY(-1px);
        }
        
        .action-btn.delete {
            background: rgba(220, 53, 69, 0.2);
            border-color: rgba(220, 53, 69, 0.3);
            color: #dc3545;
        }
        
        .action-btn.delete:hover {
            background: rgba(220, 53, 69, 0.3);
            color: #dc3545;
        }
        
        .empty-state {
            text-align: center;
            color: rgba(255, 255, 255, 0.7);
            padding: 40px;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #ff6b6b;
            margin-bottom: 15px;
            opacity: 0.7;
        }
        
        .empty-state h4 {
            color: white;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .empty-state p {
            font-size: 1rem;
            margin-bottom: 0;
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
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
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
            
            .page-header {
                padding: 25px 20px;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
            
            .scraping-form,
            .websites-table {
                padding: 25px 20px;
            }
            
            .table-responsive {
                border-radius: 12px;
            }
            
            .table th,
            .table td {
                padding: 10px 8px;
                font-size: 0.85rem;
            }
        }
        
        @media (max-width: 576px) {
            .page-header {
                padding: 20px 15px;
                margin-bottom: 20px;
            }
            
            .scraping-form,
            .websites-table {
                padding: 20px 15px;
            }
            
            .form-control {
                padding: 10px 12px;
            }
            
            .scrape-btn {
                width: 100%;
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-glass fixed-top">
        <div class="container">
            <a class="navbar-brand" href="/">
                <div class="askmaven-logo"></div>
                AskMaven
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="/">
                    <i class="fas fa-home me-1"></i>Dashboard
                </a>
                <a class="nav-link" href="/chat.php">
                    <i class="fas fa-comments me-1"></i>Chat
                </a>
                <a class="nav-link" href="/auth/logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container main-container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-logo">
                <i class="fas fa-spider"></i>
            </div>
            <h1 class="page-title">Website Scraping</h1>
            <p class="page-subtitle">Add websites to scrape content for AI-powered search</p>
        </div>
        
        <!-- Scraping Form -->
        <div class="scraping-form">
            <h3 class="form-section-title">
                <i class="fas fa-plus-circle"></i>
                Add New Website
            </h3>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?>" style="background: rgba(<?php echo $messageType === 'success' ? '25, 135, 84' : '220, 53, 69'; ?>, 0.2); border: 1px solid rgba(<?php echo $messageType === 'success' ? '25, 135, 84' : '220, 53, 69'; ?>, 0.3); color: <?php echo $messageType === 'success' ? '#198754' : '#dc3545'; ?>; border-radius: 12px; padding: 12px 16px; margin-bottom: 20px;">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="scrape">
                <div class="form-group">
                    <label for="sitemap_url" class="form-label">Sitemap URL</label>
                    <input type="url" class="form-control" id="sitemap_url" name="sitemap_url" 
                           placeholder="https://example.com/sitemap.xml" required>
                    <small style="color: rgba(255, 255, 255, 0.7); font-size: 0.85rem; margin-top: 5px; display: block;">
                        <i class="fas fa-info-circle me-1"></i>
                        Enter the full URL to the website's sitemap.xml file
                    </small>
                </div>
                <button type="submit" class="scrape-btn">
                    <i class="fas fa-spider me-2"></i>Start Scraping
                </button>
            </form>
        </div>

        <!-- Scraped Websites List -->
        <div class="websites-table">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 class="table-title">
                    <i class="fas fa-list"></i>
                    Scraped Websites
                </h3>
                <button class="action-btn" onclick="refreshStatuses()" style="background: rgba(0, 212, 255, 0.2); border-color: rgba(0, 212, 255, 0.3); color: #00d4ff;">
                    <i class="fas fa-sync-alt me-1"></i>Refresh
                </button>
            </div>
            
            <?php if (empty($sitemapSources)): ?>
                <div class="empty-state">
                    <i class="fas fa-spider"></i>
                    <h4>No websites scraped yet</h4>
                    <p>Add a sitemap URL above to get started with content scraping.</p>
                </div>
            <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Domain</th>
                                            <th>Status</th>
                                            <th>Progress</th>
                                            <th>Last Scraped</th>
                                            <?php if (isAdmin()): ?>
                                                <th>Created By</th>
                                            <?php endif; ?>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($sitemapSources as $source): ?>
                                            <tr data-sitemap-id="<?php echo $source['id']; ?>">
                                                <td>
                                                    <strong><?php echo htmlspecialchars($source['domain']); ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        <a href="<?php echo htmlspecialchars($source['sitemap_url']); ?>" 
                                                           target="_blank" class="text-decoration-none">
                                                            <i class="fas fa-external-link-alt me-1"></i>
                                                            View Sitemap
                                                        </a>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php
                                                    $statusClasses = [
                                                        'pending' => 'status-pending',
                                                        'scraping' => 'status-processing',
                                                        'completed' => 'status-completed',
                                                        'failed' => 'status-failed'
                                                    ];
                                                    $statusClass = $statusClasses[$source['status']] ?? 'status-pending';
                                                    ?>
                                                    <span class="status-badge <?php echo $statusClass; ?>">
                                                        <?php echo ucfirst($source['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($source['total_pages'] > 0): ?>
                                                        <?php 
                                                        $percentage = ($source['scraped_pages'] / $source['total_pages']) * 100;
                                                        ?>
                                                        <div class="progress-container">
                                                            <div class="progress-bar" style="width: <?php echo $percentage; ?>%"></div>
                                                            <div class="progress-text">
                                                                <?php echo $source['scraped_pages']; ?>/<?php echo $source['total_pages']; ?>
                                                            </div>
                                                        </div>
                                                        <?php if ($source['failed_pages'] > 0): ?>
                                                            <small style="color: #dc3545; font-size: 0.8rem; margin-top: 5px; display: block;">
                                                                <?php echo $source['failed_pages']; ?> failed
                                                            </small>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span style="color: rgba(255, 255, 255, 0.5);">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($source['last_scraped']): ?>
                                                        <?php echo formatDateTime($source['last_scraped']); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Never</span>
                                                    <?php endif; ?>
                                                </td>
                                                <?php if (isAdmin()): ?>
                                                    <td><?php echo htmlspecialchars($source['created_by_name']); ?></td>
                                                <?php endif; ?>
                                                <td>
                                                    <button class="action-btn" 
                                                            onclick="checkStatus(<?php echo $source['id']; ?>)"
                                                            title="Refresh Status">
                                                        <i class="fas fa-sync-alt"></i>
                                                    </button>
                                                    <a href="/api/export_data.php?domain=<?php echo urlencode($source['domain']); ?>" 
                                                       class="action-btn" 
                                                       target="_blank"
                                                       title="Download PDF Report"
                                                       style="background: rgba(25, 135, 84, 0.2); border-color: rgba(25, 135, 84, 0.3); color: #198754;">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                 </td>
                                             </tr>
                                         <?php endforeach; ?>
                                     </tbody>
                                 </table>
                             </div>
                         <?php endif; ?>
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
        async function checkStatus(sitemapId) {
            try {
                const response = await fetch(`/api/scraping_status.php?id=${sitemapId}`);
                const data = await response.json();
                
                if (data.success) {
                    // Update the row with new data
                    updateRowStatus(sitemapId, data.data);
                } else {
                    console.error('Failed to get status:', data.error);
                }
            } catch (error) {
                console.error('Error checking status:', error);
            }
        }

        function updateRowStatus(sitemapId, data) {
            const row = document.querySelector(`tr[data-sitemap-id="${sitemapId}"]`);
            if (!row) return;

            // Update status badge
            const statusCell = row.cells[1];
            const statusClasses = {
                'pending': 'status-pending',
                'scraping': 'status-processing',
                'completed': 'status-completed',
                'failed': 'status-failed'
            };
            const statusClass = statusClasses[data.status] || 'status-pending';
            statusCell.innerHTML = `<span class="status-badge ${statusClass}">${data.status.charAt(0).toUpperCase() + data.status.slice(1)}</span>`;

            // Update progress
            const progressCell = row.cells[2];
            if (data.total_pages > 0) {
                const percentage = (data.scraped_pages / data.total_pages) * 100;
                let progressHtml = `
                    <div class="progress-container">
                        <div class="progress-bar" style="width: ${percentage}%"></div>
                        <div class="progress-text">${data.scraped_pages}/${data.total_pages}</div>
                    </div>
                `;
                if (data.failed_pages > 0) {
                    progressHtml += `<small style="color: #dc3545; font-size: 0.8rem; margin-top: 5px; display: block;">${data.failed_pages} failed</small>`;
                }
                progressCell.innerHTML = progressHtml;
            }

            // Update last scraped
            const lastScrapedCell = row.cells[3];
            if (data.last_scraped) {
                lastScrapedCell.textContent = new Date(data.last_scraped).toLocaleString();
            }
        }

        function refreshStatuses() {
            const rows = document.querySelectorAll('tr[data-sitemap-id]');
            rows.forEach(row => {
                const sitemapId = row.getAttribute('data-sitemap-id');
                checkStatus(sitemapId);
            });
        }

        // Auto-refresh every 30 seconds for scraping status
        setInterval(refreshStatuses, 30000);
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
    
    <script src="/assets/js/animations.js"></script>
</body>
</html>
