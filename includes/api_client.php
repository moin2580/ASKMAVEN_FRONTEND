<?php
/**
 * API Client for communicating with Python FastAPI backend
 * Handles all HTTP requests to Python server
 */

require_once __DIR__ . '/../config/config.php';

class ApiClient {
    private $baseUrl;
    private $timeout;
    private $maxRetries;
    
    public function __construct() {
        $this->baseUrl = PYTHON_API_BASE_URL;
        $this->timeout = API_TIMEOUT;
        $this->maxRetries = MAX_RETRIES;
    }
    
    private function makeRequest($method, $endpoint, $data = null, $retries = 0) {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log("cURL Error: $error");
            if ($retries < $this->maxRetries) {
                sleep(1); // Wait 1 second before retry
                return $this->makeRequest($method, $endpoint, $data, $retries + 1);
            }
            throw new Exception("API request failed: $error");
        }
        
        $decodedResponse = json_decode($response, true);
        
        if ($httpCode >= 400) {
            $errorMessage = $decodedResponse['detail'] ?? "HTTP Error $httpCode";
            if ($retries < $this->maxRetries && $httpCode >= 500) {
                sleep(1);
                return $this->makeRequest($method, $endpoint, $data, $retries + 1);
            }
            throw new Exception("API Error: $errorMessage");
        }
        
        return $decodedResponse;
    }
    
    public function get($endpoint) {
        return $this->makeRequest('GET', $endpoint);
    }
    
    public function post($endpoint, $data) {
        return $this->makeRequest('POST', $endpoint, $data);
    }
    
    public function put($endpoint, $data) {
        return $this->makeRequest('PUT', $endpoint, $data);
    }
    
    public function delete($endpoint) {
        return $this->makeRequest('DELETE', $endpoint);
    }
    
    public function scrapeSitemap($sitemapUrl, $userId) {
        return $this->post('/api/scrape-sitemap', [
            'sitemap_url' => $sitemapUrl,
            'user_id' => $userId
        ]);
    }
    
    public function getScrapingStatus($sitemapId) {
        return $this->get("/api/scraping-status/$sitemapId");
    }
    
    public function chat($question, $userId, $contextLimit = 5) {
        return $this->post('/api/chat', [
            'question' => $question,
            'user_id' => $userId,
            'context_limit' => $contextLimit
        ]);
    }
    
    public function getSystemStats() {
        return $this->get('/api/stats');
    }
    
    public function healthCheck() {
        try {
            $response = $this->get('/');
            return $response['status'] === 'healthy';
        } catch (Exception $e) {
            return false;
        }
    }
}
?>
