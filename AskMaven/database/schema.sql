-- Hybrid Chatbot System Database Schema
-- Compatible with MySQL 8.0+ (XAMPP)

CREATE DATABASE IF NOT EXISTS hybrid_chatbot;

-- Users table for authentication and role management
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
);

-- Scraped pages table for storing website content
CREATE TABLE scraped_pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    url VARCHAR(2048) NOT NULL UNIQUE,
    title VARCHAR(500),
    content LONGTEXT,
    headings TEXT,
    image_url VARCHAR(2048),
    meta_description TEXT,
    keywords TEXT,
    scraped_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('pending', 'scraped', 'failed') DEFAULT 'pending',
    INDEX idx_url (url(255)),
    INDEX idx_status (status),
    INDEX idx_scraped_at (scraped_at),
    FULLTEXT(title, content, headings, meta_description, keywords)
);

-- Chat history table for storing conversations
CREATE TABLE chat_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    question TEXT NOT NULL,
    answer LONGTEXT NOT NULL,
    source_url VARCHAR(2048),
    context_used TEXT,
    response_time_ms INT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_timestamp (timestamp),
    INDEX idx_source_url (source_url(255))
);

-- Sitemap sources table for tracking scraped websites
CREATE TABLE sitemap_sources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sitemap_url VARCHAR(2048) NOT NULL,
    domain VARCHAR(255) NOT NULL,
    total_pages INT DEFAULT 0,
    scraped_pages INT DEFAULT 0,
    failed_pages INT DEFAULT 0,
    last_scraped TIMESTAMP NULL,
    status ENUM('pending', 'scraping', 'completed', 'failed') DEFAULT 'pending',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_domain (domain),
    INDEX idx_status (status),
    INDEX idx_created_by (created_by)
);

-- Sessions table for PHP session management
CREATE TABLE sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
);

-- API logs table for tracking PHP-Python communication
CREATE TABLE api_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    endpoint VARCHAR(255) NOT NULL,
    method VARCHAR(10) NOT NULL,
    request_data JSON,
    response_data JSON,
    status_code INT,
    response_time_ms INT,
    ip_address VARCHAR(45),
    user_id INT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_endpoint (endpoint),
    INDEX idx_timestamp (timestamp),
    INDEX idx_user_id (user_id)
);

-- Insert default admin user (password: admin123)
INSERT INTO users (name, email, password_hash, role) VALUES 
('Admin User', 'admin@hybridchatbot.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Create indexes for better performance
CREATE INDEX idx_chat_history_composite ON chat_history(user_id, timestamp DESC);
CREATE INDEX idx_scraped_pages_content ON scraped_pages(title(100), content(500));
-- Create content_chunks table for better search functionality
-- Run this SQL in your MySQL database

CREATE TABLE IF NOT EXISTS content_chunks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_id INT NOT NULL,
    chunk_text TEXT NOT NULL,
    chunk_type ENUM('title', 'heading', 'content') NOT NULL DEFAULT 'content',
    priority INT NOT NULL DEFAULT 5,
    chunk_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign key constraint
    FOREIGN KEY (page_id) REFERENCES scraped_pages(id) ON DELETE CASCADE,
    
    -- Indexes for better performance
    INDEX idx_page_id (page_id),
    INDEX idx_chunk_type (chunk_type),
    INDEX idx_priority (priority),
    INDEX idx_chunk_order (chunk_order),
    
    -- Full-text index for search
    FULLTEXT INDEX ft_chunk_text (chunk_text)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add some additional indexes for the existing scraped_pages table if not present
-- First check if columns exist, then add indexes
ALTER TABLE scraped_pages 
ADD INDEX IF NOT EXISTS idx_status (status),
ADD INDEX IF NOT EXISTS idx_url (url(255));

-- Only add created_at index if the column exists
-- ALTER TABLE scraped_pages ADD INDEX IF NOT EXISTS idx_created_at (created_at);

-- Optional: Add full-text index to existing scraped_pages if not present
-- ALTER TABLE scraped_pages ADD FULLTEXT INDEX ft_content_search (title, content, headings, meta_description, keywords);
