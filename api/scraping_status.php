<?php
/**
 * API endpoint for getting scraping status
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/api_client.php';

// Set JSON response header
header('Content-Type: application/json');

// Require login
requireLogin();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid sitemap ID']);
    exit;
}

$sitemapId = (int)$_GET['id'];

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Check if user has access to this sitemap (own or admin)
    $whereClause = isAdmin() ? 'WHERE id = ?' : 'WHERE id = ? AND created_by = ?';
    $params = isAdmin() ? [$sitemapId] : [$sitemapId, $_SESSION['user_id']];
    
    $stmt = $conn->prepare("SELECT * FROM sitemap_sources $whereClause");
    $stmt->execute($params);
    $sitemap = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sitemap) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Sitemap not found']);
        exit;
    }
    
    // Get status from Python API
    $apiClient = new ApiClient();
    $apiStatus = $apiClient->getScrapingStatus($sitemapId);
    
    // Update local database with latest status
    if ($apiStatus) {
        $updateStmt = $conn->prepare("
            UPDATE sitemap_sources 
            SET total_pages = ?, scraped_pages = ?, failed_pages = ?, status = ?, last_scraped = ?
            WHERE id = ?
        ");
        $updateStmt->execute([
            $apiStatus['total_pages'] ?? 0,
            $apiStatus['scraped_pages'] ?? 0,
            $apiStatus['failed_pages'] ?? 0,
            $apiStatus['status'] ?? 'pending',
            $apiStatus['last_scraped'] ? date('Y-m-d H:i:s', strtotime($apiStatus['last_scraped'])) : null,
            $sitemapId
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $apiStatus ?: $sitemap
    ]);
    
} catch (Exception $e) {
    error_log("Scraping status API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to get scraping status']);
}
?>
