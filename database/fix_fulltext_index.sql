-- Fix FULLTEXT index for optimized chat search
-- This migration updates the FULLTEXT index to match the columns used in the optimized chat query

USE hybrid_chatbot;

-- Drop the existing FULLTEXT index
ALTER TABLE scraped_pages DROP INDEX title;

-- Create new FULLTEXT index matching the optimized search columns
-- (title, content, meta_description, keywords - without headings)
ALTER TABLE scraped_pages ADD FULLTEXT INDEX idx_search_content (title, content, meta_description, keywords);

-- Verify the index was created
SHOW INDEX FROM scraped_pages WHERE Key_name = 'idx_search_content';
