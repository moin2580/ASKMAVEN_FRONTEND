<?php
/**
 * Database migration to fix FULLTEXT index for optimized chat search
 */

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'hybrid_chatbot';

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully.\n";
    
    // Step 1: Check existing FULLTEXT indexes
    echo "Checking existing FULLTEXT indexes...\n";
    $stmt = $pdo->query("SHOW INDEX FROM scraped_pages WHERE Index_type = 'FULLTEXT'");
    $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($indexes as $index) {
        echo "Found FULLTEXT index: " . $index['Key_name'] . " on column: " . $index['Column_name'] . "\n";
    }
    
    // Step 2: Drop existing FULLTEXT index if it exists
    echo "Dropping existing FULLTEXT index...\n";
    try {
        $pdo->exec("ALTER TABLE scraped_pages DROP INDEX title");
        echo "Successfully dropped existing FULLTEXT index.\n";
    } catch (PDOException $e) {
        echo "Note: No existing FULLTEXT index named 'title' found or already dropped.\n";
    }
    
    // Step 3: Create new optimized FULLTEXT index
    echo "Creating new optimized FULLTEXT index...\n";
    $pdo->exec("ALTER TABLE scraped_pages ADD FULLTEXT INDEX idx_search_content (title, content, meta_description, keywords)");
    echo "Successfully created new FULLTEXT index: idx_search_content\n";
    
    // Step 4: Verify the new index
    echo "Verifying new FULLTEXT index...\n";
    $stmt = $pdo->query("SHOW INDEX FROM scraped_pages WHERE Key_name = 'idx_search_content'");
    $newIndexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($newIndexes) > 0) {
        echo "✅ FULLTEXT index migration completed successfully!\n";
        echo "New index covers columns: ";
        foreach ($newIndexes as $idx) {
            echo $idx['Column_name'] . " ";
        }
        echo "\n";
    } else {
        echo "❌ Failed to create FULLTEXT index.\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nMigration completed. You can now test the chat functionality.\n";
?>
