<?php
/**
 * Chat interface for Hybrid Chatbot System
 */

require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/api_client.php';

// Require login
requireLogin();

$apiClient = new ApiClient();
$db = Database::getInstance();

// Handle AJAX chat request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'chat') {
    header('Content-Type: application/json');
    
    $question = sanitizeInput($_POST['question'] ?? '');
    
    if (empty($question)) {
        echo json_encode(['success' => false, 'error' => 'Please enter a question.']);
        exit;
    }
    
    try {
        $response = $apiClient->chat($question, $_SESSION['user_id']);
        echo json_encode([
            'success' => true,
            'answer' => $response['answer'],
            'response_time_ms' => $response['response_time_ms'] ?? 0,
            'context_found' => $response['context_found'] ?? false
        ]);
    } catch (Exception $e) {
        error_log("Chat API error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Failed to get response. Please try again.']);
    }
    exit;
}

// Get recent chat history for sidebar
$recentChats = $db->fetchAll(
    "SELECT id, question, timestamp FROM chat_history 
     WHERE user_id = ? ORDER BY timestamp DESC LIMIT 10",
    [$_SESSION['user_id']]
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="/AskMaven/assets/css/theme.css" rel="stylesheet">
    <style>
        /* Chat Page Specific Styles */
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            height: 100vh;
            overflow: hidden;
        }
        
        .chat-container {
            height: calc(100vh - 80px);
            display: flex;
            margin-top: 80px;
            gap: 20px;
            padding: 20px;
        }
        
        .chat-sidebar {
            width: 300px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 20px;
            overflow-y: auto;
            animation: slideInLeft 0.6s ease-out;
        }
        
        .sidebar-header {
            color: white;
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-header i {
            margin-right: 10px;
            color: #00d4ff;
        }
        
        .chat-history-item {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .chat-history-item:hover {
            background: rgba(255, 255, 255, 0.1);
            border-left-color: #00d4ff;
            transform: translateX(5px);
        }
        
        .chat-history-question {
            color: white;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 5px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .chat-history-time {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.8rem;
        }
        
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            overflow: hidden;
            animation: slideInRight 0.6s ease-out;
        }
        
        .chat-header {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .chat-title {
            color: white;
            font-size: 1.3rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
        }
        
        .chat-title i {
            margin-right: 10px;
            color: #00d4ff;
        }
        
        .chat-status {
            display: flex;
            align-items: center;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
        }
        
        .status-dot {
            width: 8px;
            height: 8px;
            background: #00ff88;
            border-radius: 50%;
            margin-right: 8px;
            animation: pulse 2s infinite;
        }
        
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .message {
            max-width: 80%;
            animation: messageSlideIn 0.4s ease-out;
        }
        
        .message.user {
            align-self: flex-end;
        }
        
        .message.assistant {
            align-self: flex-start;
        }
        
        .message-content {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 18px;
            padding: 15px 20px;
            color: white;
            line-height: 1.5;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .message.user .message-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .message.assistant .message-content {
            background: rgba(255, 255, 255, 0.15);
            border-left: 3px solid #00d4ff;
        }
        
        .message-meta {
            display: flex;
            align-items: center;
            margin-top: 8px;
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.6);
        }
        
        .message.user .message-meta {
            justify-content: flex-end;
        }
        
        .message-avatar {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            margin-right: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }
        
        .message.user .message-avatar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            margin-right: 0;
            margin-left: 8px;
        }
        
        .message.assistant .message-avatar {
            background: #00d4ff;
            color: white;
        }
        
       
        .chat-input-container {
            padding: 20px;
            background: rgba(255, 255, 255, 0.1);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .chat-input-form {
            display: flex;
            gap: 15px;
            align-items: flex-end;
        }
        
        .chat-input-group {
            flex: 1;
            position: relative;
        }
        
        .chat-input {
            width: 100%;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            padding: 15px 20px;
            color: white;
            font-size: 1rem;
            resize: none;
            min-height: 50px;
            max-height: 120px;
            transition: all 0.3s ease;
        }
        
        .chat-input:focus {
            outline: none;
            border-color: #00d4ff;
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 20px rgba(0, 212, 255, 0.3);
        }
        
        .chat-input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        
        .chat-send-btn {
            background: linear-gradient(135deg, #00d4ff 0%, #5b73e8 100%);
            border: none;
            border-radius: 15px;
            padding: 15px 20px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 212, 255, 0.3);
            min-width: 120px;
        }
        
        .chat-send-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 212, 255, 0.4);
        }
        
        .chat-send-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .typing-indicator {
            display: none;
            align-items: center;
            color: rgba(255, 255, 255, 0.7);
            font-style: italic;
            margin: 10px 0;
        }
        
        .typing-dots {
            display: inline-flex;
            margin-left: 10px;
        }
        
        .typing-dot {
            width: 6px;
            height: 6px;
            background: #00d4ff;
            border-radius: 50%;
            margin: 0 2px;
            animation: typingDots 1.4s infinite;
        }
        
        .typing-dot:nth-child(2) {
            animation-delay: 0.2s;
        }
        
        .typing-dot:nth-child(3) {
            animation-delay: 0.4s;
        }
        
        .empty-state {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: rgba(255, 255, 255, 0.7);
            padding: 40px;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #00d4ff;
            margin-bottom: 20px;
            opacity: 0.7;
        }
        
        .empty-state h3 {
            color: white;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            font-size: 1rem;
            margin-bottom: 0;
        }
        
        /* Animations */
        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes messageSlideIn {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.95);
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
        
        @keyframes typingDots {
            0%, 60%, 100% {
                transform: translateY(0);
            }
            30% {
                transform: translateY(-10px);
            }
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .chat-container {
                flex-direction: column;
                padding: 10px;
                gap: 10px;
            }
            
            .chat-sidebar {
                width: 100%;
                height: 200px;
                order: 2;
            }
            
            .chat-main {
                order: 1;
                height: calc(100vh - 300px);
            }
            
            .message {
                max-width: 95%;
            }
            
            .chat-input-form {
                flex-direction: column;
                gap: 10px;
            }
            
            .chat-send-btn {
                width: 100%;
            }
        }
        
        @media (max-width: 576px) {
            .chat-container {
                padding: 5px;
                margin-top: 70px;
                height: calc(100vh - 70px);
            }
            
            .chat-header {
                padding: 15px;
            }
            
            .chat-title {
                font-size: 1.1rem;
            }
            
            .chat-messages {
                padding: 15px;
            }
            
            .chat-input-container {
                padding: 15px;
            }
            
            .message-content {
                padding: 12px 15px;
            }
        }
        
        /* Scrollbar Styling */
        .chat-messages::-webkit-scrollbar,
        .chat-sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .chat-messages::-webkit-scrollbar-track,
        .chat-sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
        }
        
        .chat-messages::-webkit-scrollbar-thumb,
        .chat-sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }
        
        .chat-messages::-webkit-scrollbar-thumb:hover,
        .chat-sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
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
                        <a class="nav-link" href="/AskMaven/">
                            <i class="fas fa-home me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/AskMaven/chat.php">
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
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/AskMaven/auth/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="chat-container">
        <!-- Sidebar -->
        <div class="chat-sidebar">
            <div class="sidebar-header">
                <i class="fas fa-history"></i>
                Recent Chats
            </div>
            
            <?php if (empty($recentChats)): ?>
                <div style="text-align: center; color: rgba(255, 255, 255, 0.6); padding: 20px;">
                    <i class="fas fa-comments" style="font-size: 2rem; margin-bottom: 10px;"></i>
                    <p>No chat history yet</p>
                </div>
            <?php else: ?>
                <?php foreach ($recentChats as $chat): ?>
                    <div class="chat-history-item" onclick="loadChat(<?php echo $chat['id']; ?>)">
                        <div class="chat-history-question">
                            <?php echo htmlspecialchars(substr($chat['question'], 0, 60)); ?>
                            <?php if (strlen($chat['question']) > 60) echo '...'; ?>
                        </div>
                        <div class="chat-history-time">
                            <?php echo timeAgo($chat['timestamp']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Main Chat Area -->
        <div class="chat-main">
            <!-- Chat Header -->
            <div class="chat-header">
                <h2 class="chat-title">
                    <i class="fas fa-robot"></i>
                    AskMaven AI Assistant
                </h2>
                <div class="chat-status">
                    <div class="status-dot"></div>
                    Online
                </div>
            </div>
            
            <!-- Messages Area -->
            <div class="chat-messages" id="chatMessages">
                <div class="empty-state">
                    <i class="fas fa-comments"></i>
                    <h3>Welcome to AskMaven Chat!</h3>
                    <p>Ask me anything about the scraped website content.</p>
                </div>
            </div>
            
            <!-- Typing Indicator -->
            <div class="typing-indicator" id="typingIndicator">
                <i class="fas fa-robot"></i>
                AskMaven is typing
                <div class="typing-dots">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                </div>
            </div>
            
            <!-- Input Area -->
            <div class="chat-input-container">
                <form class="chat-input-form" id="chatForm">
                    <div class="chat-input-group">
                        <textarea 
                            class="chat-input" 
                            id="messageInput" 
                            placeholder="Ask me anything about the scraped content..."
                            rows="1"
                            required
                        ></textarea>
                    </div>
                    <button type="submit" class="chat-send-btn" id="sendButton">
                        <i class="fas fa-paper-plane me-2"></i>Send
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const chatMessages = document.getElementById('chatMessages');
        const messageInput = document.getElementById('messageInput');
        const chatForm = document.getElementById('chatForm');
        const sendButton = document.getElementById('sendButton');
        const typingIndicator = document.getElementById('typingIndicator');

        // Auto-resize textarea
        messageInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        });

        // Auto-scroll to bottom
        function scrollToBottom() {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // Add message to chat
        function addMessage(message, isUser = false, responseTime = 0) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${isUser ? 'user' : 'assistant'}`;
            
            const messageContent = document.createElement('div');
            messageContent.className = 'message-content';
            messageContent.textContent = message;
            
            const messageMeta = document.createElement('div');
            messageMeta.className = 'message-meta';
            
            const avatar = document.createElement('div');
            avatar.className = 'message-avatar';
            avatar.textContent = isUser ? 'U' : 'AI';
            
            const time = document.createElement('span');
            const timeInfo = responseTime > 0 ? ` (${responseTime}ms)` : '';
            time.textContent = new Date().toLocaleTimeString() + timeInfo;
            
            if (isUser) {
                messageMeta.appendChild(time);
                messageMeta.appendChild(avatar);
            } else {
                messageMeta.appendChild(avatar);
                messageMeta.appendChild(time);
            }
            
            messageDiv.appendChild(messageContent);
            messageDiv.appendChild(messageMeta);
            
            
            
            // Remove empty state if it exists
            const emptyState = chatMessages.querySelector('.empty-state');
            if (emptyState) {
                emptyState.remove();
            }
            
            chatMessages.appendChild(messageDiv);
            scrollToBottom();
        }

        // Show/hide typing indicator
        function showTyping() {
            typingIndicator.style.display = 'flex';
            sendButton.disabled = true;
            scrollToBottom();
        }

        function hideTyping() {
            typingIndicator.style.display = 'none';
            sendButton.disabled = false;
        }

        // Handle form submission
        chatForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const message = messageInput.value.trim();
            if (!message) return;

            // Add user message
            addMessage(message, true);
            messageInput.value = '';
            messageInput.style.height = 'auto';
            
            // Disable input and show typing
            messageInput.disabled = true;
            sendButton.disabled = true;
            showTyping();

            try {
                const formData = new FormData();
                formData.append('action', 'chat');
                formData.append('question', message);

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                
                hideTyping();

                if (data.success) {
                    addMessage(data.answer, false,  data.response_time_ms);
                } else {
                    addMessage('Sorry, I encountered an error: ' + data.error, false);
                }
            } catch (error) {
                hideTyping();
                addMessage('Sorry, I encountered a connection error. Please try again.', false);
                console.error('Chat error:', error);
            } finally {
                // Re-enable input
                messageInput.disabled = false;
                sendButton.disabled = false;
                messageInput.focus();
            }
        });

        // Handle Enter key
        messageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                chatForm.dispatchEvent(new Event('submit'));
            }
        });

        function loadChat(chatId) {
            // This function would load a specific chat history
            console.log('Loading chat:', chatId);
        }

        // Focus input on load
        messageInput.focus();
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
