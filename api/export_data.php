<?php
require_once '../includes/database.php';
require_once '../config/config.php';

// Check if user is logged in
requireLogin();

// Get database connection
$db = Database::getInstance()->getConnection();

try {
    // Check if domain filter is requested
    $domain_filter = isset($_GET['domain']) ? $_GET['domain'] : null;
    
    // Get scraped data (all or filtered by domain)
    if ($domain_filter) {
        $stmt = $db->prepare("
            SELECT sp.url, sp.title, sp.content, sp.headings, sp.meta_description, 
                   sp.scraped_at, ss.domain, ss.sitemap_url
            FROM scraped_pages sp
            JOIN sitemap_sources ss ON SUBSTRING_INDEX(sp.url, '/', 3) = CONCAT('https://', ss.domain)
            WHERE sp.status = 'scraped' AND ss.domain = :domain
            ORDER BY sp.scraped_at DESC
        ");
        $stmt->bindParam(':domain', $domain_filter);
    } else {
        $stmt = $db->prepare("
            SELECT sp.url, sp.title, sp.content, sp.headings, sp.meta_description, 
                   sp.scraped_at, ss.domain, ss.sitemap_url
            FROM scraped_pages sp
            JOIN sitemap_sources ss ON SUBSTRING_INDEX(sp.url, '/', 3) = CONCAT('https://', ss.domain)
            WHERE sp.status = 'scraped'
            ORDER BY ss.domain, sp.scraped_at DESC
        ");
    }
    $stmt->execute();
    $pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get summary statistics
    $stats_stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_pages,
            COUNT(DISTINCT SUBSTRING_INDEX(url, '/', 3)) as total_domains,
            MIN(scraped_at) as first_scraped,
            MAX(scraped_at) as last_scraped
        FROM scraped_pages 
        WHERE status = 'scraped'
    ");
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Generate HTML content for PDF
    $html = generatePDFContent($pages, $stats, $domain_filter);
    
    // Set headers for HTML that can be printed as PDF
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: inline; filename="crawled_data_report_' . date('Y-m-d_H-i-s') . '.html"');
    
    echo $html;
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to export data: ' . $e->getMessage()]);
}

function generatePDFContent($pages, $stats, $domain_filter = null) {
    $title = $domain_filter ? "/ Data Report - " . $domain_filter : "/ Complete Data Report";
    
    $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . $title . '</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Modern / Report Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .report-header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            margin-bottom: 30px;
            color: white;
            animation: fadeIn 1s ease-out;
        }
        
        .report-logo {
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
        
        .report-logo::before {
            content: "/";
            color: white;
            font-size: 32px;
            font-weight: bold;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        
        .report-logo::after {
            content: "";
            position: absolute;
            top: 12px;
            right: 12px;
            width: 10px;
            height: 10px;
            background: #00d4ff;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        .report-title {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: white;
        }
        
        .report-subtitle {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 20px;
        }
        
        .report-meta {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.95rem;
        }
        
        .meta-item i {
            margin-right: 8px;
            color: #00d4ff;
        }
        
        .print-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .action-btn {
            background: linear-gradient(135deg, #00d4ff 0%, #5b73e8 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 212, 255, 0.3);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            font-size: 0.95rem;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 212, 255, 0.4);
            color: white;
        }
        
        .action-btn i {
            margin-right: 8px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 25px;
            text-align: center;
            color: white;
            animation: slideInUp 0.6s ease-out;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            margin: 0 auto 15px;
            background: linear-gradient(135deg, #00d4ff 0%, #5b73e8 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
            box-shadow: 0 8px 25px rgba(0, 212, 255, 0.3);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: white;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .content-section {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            animation: slideInUp 0.8s ease-out;
        }
        
        .section-title {
            color: white;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 12px;
            color: #00d4ff;
        }
        
        .domain-header {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin: 30px 0 20px 0;
            box-shadow: 0 8px 25px rgba(255, 107, 107, 0.3);
        }
        
        .domain-title {
            font-size: 1.3rem;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .domain-title i {
            margin-right: 10px;
        }
        
        .page-item {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            border-left: 4px solid #00d4ff;
            transition: all 0.3s ease;
        }
        
        .page-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }
        
        .page-title {
            font-weight: 600;
            color: white;
            font-size: 1.2rem;
            margin-bottom: 10px;
            line-height: 1.4;
        }
        
        .page-url {
            color: #00d4ff;
            font-size: 0.9rem;
            margin-bottom: 15px;
            word-break: break-all;
            background: rgba(0, 212, 255, 0.1);
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid rgba(0, 212, 255, 0.2);
        }
        
        .page-content {
            margin: 15px 0;
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.95rem;
            line-height: 1.6;
        }
        
        .content-label {
            color: #00d4ff;
            font-weight: 600;
            margin-bottom: 5px;
            display: block;
        }
        
        .page-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.85rem;
        }
        
        .meta-date {
            display: flex;
            align-items: center;
        }
        
        .meta-date i {
            margin-right: 5px;
            color: #00d4ff;
        }
        
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .no-data i {
            font-size: 4rem;
            color: #00d4ff;
            margin-bottom: 20px;
            opacity: 0.7;
        }
        
        .no-data h3 {
            color: white;
            font-size: 1.5rem;
            margin-bottom: 10px;
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
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        /* Print Styles */
        @media print {
            body {
                background: white !important;
                color: #333 !important;
            }
            
            .print-actions {
                display: none !important;
            }
            
            .report-header,
            .stat-card,
            .content-section,
            .page-item {
                background: white !important;
                border: 1px solid #ddd !important;
                color: #333 !important;
            }
            
            .report-title,
            .section-title,
            .page-title {
                color: #333 !important;
            }
            
            .page-item {
                page-break-inside: avoid;
                margin-bottom: 15px;
            }
            
            .domain-header {
                background: #f8f9fa !important;
                color: #333 !important;
                border: 1px solid #ddd !important;
            }
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .report-header {
                padding: 30px 20px;
            }
            
            .report-title {
                font-size: 1.8rem;
            }
            
            .report-meta {
                flex-direction: column;
                gap: 15px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .print-actions {
                flex-direction: column;
            }
            
            .action-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="report-header">
            <div class="report-logo"></div>
            <h1 class="report-title">' . $title . '</h1>
            <p class="report-subtitle">Comprehensive Web Scraping Analytics Report</p>
            <div class="report-meta">
                <div class="meta-item">
                    <i class="fas fa-calendar-alt"></i>
                    Generated on ' . date('F j, Y \a\t g:i A') . '
                </div>
                ' . ($domain_filter ? '<div class="meta-item"><i class="fas fa-globe"></i>Domain: ' . htmlspecialchars($domain_filter) . '</div>' : '') . '
                <div class="meta-item">
                    <i class="fas fa-user"></i>
                    / Analytics
                </div>
            </div>
        </div>
        
        <div class="print-actions">
            <button class="action-btn" onclick="window.print()">
                <i class="fas fa-print"></i>
                Print as PDF
            </button>
            <button class="action-btn" onclick="window.history.back()">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </button>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-number">' . number_format($stats['total_pages']) . '</div>
                <div class="stat-label">Total Pages Crawled</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-globe"></i>
                </div>
                <div class="stat-number">' . number_format($stats['total_domains']) . '</div>
                <div class="stat-label">Unique Domains</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number">' . date('M j', strtotime($stats['first_scraped'])) . '</div>
                <div class="stat-label">First Crawled</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-number">' . date('M j', strtotime($stats['last_scraped'])) . '</div>
                <div class="stat-label">Last Updated</div>
            </div>
        </div>
        
        <div class="content-section">
            <h2 class="section-title">
                <i class="fas fa-list-alt"></i>
                Crawled Content Details
            </h2>';
    
    if (empty($pages)) {
        $html .= '
            <div class="no-data">
                <i class="fas fa-search"></i>
                <h3>No Data Available</h3>
                <p>No crawled pages found for the selected criteria.</p>
            </div>
        ';
    } else {
        $current_domain = '';
        foreach ($pages as $page) {
            $domain = parse_url($page['url'], PHP_URL_HOST);
            
            if ($domain !== $current_domain) {
                $html .= '<div class="domain-header">
                    <div class="domain-title">
                        <i class="fas fa-globe"></i>
                        ' . htmlspecialchars($domain) . '
                    </div>
                </div>';
                $current_domain = $domain;
            }
            
            $html .= '<div class="page-item">
                <div class="page-title">' . htmlspecialchars($page['title'] ?: 'Untitled Page') . '</div>
                <div class="page-url">' . htmlspecialchars($page['url']) . '</div>';
                
            if ($page['meta_description']) {
                $html .= '<div class="page-content">
                    <span class="content-label">Description:</span>
                    ' . htmlspecialchars(substr($page['meta_description'], 0, 200)) . '
                </div>';
            }
            
            if ($page['headings']) {
                $html .= '<div class="page-content">
                    <span class="content-label">Key Headings:</span>
                    ' . htmlspecialchars(substr($page['headings'], 0, 300)) . '
                </div>';
            }
            
            if ($page['content']) {
                $html .= '<div class="page-content">
                    <span class="content-label">Content Preview:</span>
                    ' . htmlspecialchars(substr(strip_tags($page['content']), 0, 500)) . '...
                </div>';
            }
            
            $html .= '<div class="page-meta">
                <div class="meta-date">
                    <i class="fas fa-clock"></i>
                    Scraped: ' . date('M j, Y g:i A', strtotime($page['scraped_at'])) . '
                </div>
            </div>
            </div>';
        }
    }
    
    $html .= '
        </div>
    </div>
    
    <script>
        // Add smooth scrolling and print functionality
        document.addEventListener("DOMContentLoaded", function() {
            // Animate elements on load
            const elements = document.querySelectorAll(".stat-card, .page-item");
            elements.forEach((el, index) => {
                el.style.animationDelay = (index * 0.1) + "s";
            });
            
            // Add hover effects to page items
            const pageItems = document.querySelectorAll(".page-item");
            pageItems.forEach(item => {
                item.addEventListener("mouseenter", function() {
                    this.style.transform = "translateY(-2px)";
                });
                item.addEventListener("mouseleave", function() {
                    this.style.transform = "translateY(0)";
                });
            });
        });
    </script>
</body>
</html>';
    return $html;
}
?>
