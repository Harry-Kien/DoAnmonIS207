<?php
// Lấy user_id từ session nếu đã đăng nhập
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
?>

<!-- Chatbot container -->
<div id="chatbot-container" class="chatbot-container">
    <!-- Chatbot header -->
    <div class="chatbot-header">
        <div class="chatbot-title">
            <img src="../assets/images/logo-icon.png" alt="Homseeker" class="chatbot-logo">
            <span>Hỗ trợ Homseeker</span>
        </div>
        <div class="chatbot-actions">
            <button id="chatbot-minimize" class="chatbot-btn">
                <i class="fas fa-minus"></i>
            </button>
            <button id="chatbot-close" class="chatbot-btn">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    
    <!-- Chatbot messages -->
    <div id="chatbot-messages" class="chatbot-messages">
        <div class="chatbot-message bot">
            <div class="chatbot-message-content">
                <p>Xin chào! Tôi là trợ lý ảo của Homseeker. Tôi có thể giúp gì cho bạn?</p>
            </div>
        </div>
    </div>
    
    <!-- Chatbot input -->
    <div class="chatbot-input">
        <input type="text" id="chatbot-input-field" placeholder="Nhập tin nhắn...">
        <button id="chatbot-send">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>
</div>

<!-- Chatbot trigger button -->
<div id="chatbot-trigger" class="chatbot-trigger">
    <i class="fas fa-comments"></i>
</div>

<!-- Chatbot styles -->
<style>
.chatbot-container {
    position: fixed;
    bottom: 80px;
    right: 20px;
    width: 350px;
    height: 450px;
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    display: flex;
    flex-direction: column;
    z-index: 9999;
    overflow: hidden;
    display: none;
    transition: all 0.3s ease;
}

.chatbot-header {
    background-color: #4a6cf7;
    color: white;
    padding: 10px 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chatbot-title {
    display: flex;
    align-items: center;
    font-weight: bold;
}

.chatbot-logo {
    width: 24px;
    height: 24px;
    margin-right: 10px;
    border-radius: 50%;
}

.chatbot-actions {
    display: flex;
}

.chatbot-btn {
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    font-size: 14px;
    margin-left: 10px;
}

.chatbot-messages {
    flex-grow: 1;
    padding: 15px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
}

.chatbot-message {
    margin-bottom: 10px;
    display: flex;
    flex-direction: column;
}

.chatbot-message.user {
    align-items: flex-end;
}

.chatbot-message.bot {
    align-items: flex-start;
}

.chatbot-message-content {
    max-width: 80%;
    padding: 10px 15px;
    border-radius: 15px;
}

.chatbot-message.user .chatbot-message-content {
    background-color: #4a6cf7;
    color: white;
}

.chatbot-message.bot .chatbot-message-content {
    background-color: #f1f1f1;
    color: #333;
}

.chatbot-message-content p {
    margin: 0;
}

.chatbot-input {
    display: flex;
    padding: 10px;
    border-top: 1px solid #e6e6e6;
}

#chatbot-input-field {
    flex-grow: 1;
    border: 1px solid #ddd;
    border-radius: 20px;
    padding: 8px 15px;
    outline: none;
}

#chatbot-send {
    background-color: #4a6cf7;
    color: white;
    border: none;
    border-radius: 50%;
    width: 36px;
    height: 36px;
    margin-left: 10px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.chatbot-trigger {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background-color: #4a6cf7;
    color: white;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    z-index: 9999;
    font-size: 20px;
}

.chatbot-trigger:hover {
    background-color: #3a5bd9;
}

.chatbot-typing {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
    align-self: flex-start;
}

.chatbot-typing .dot {
    width: 8px;
    height: 8px;
    background-color: #999;
    border-radius: 50%;
    margin: 0 2px;
    animation: typing 1s infinite ease-in-out;
}

.chatbot-typing .dot:nth-child(1) {
    animation-delay: 0s;
}

.chatbot-typing .dot:nth-child(2) {
    animation-delay: 0.2s;
}

.chatbot-typing .dot:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes typing {
    0% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-5px);
    }
    100% {
        transform: translateY(0);
    }
}

@media (max-width: 480px) {
    .chatbot-container {
        width: 300px;
        height: 400px;
        bottom: 70px;
        right: 10px;
    }
}
</style>

<!-- Chatbot script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatbotTrigger = document.getElementById('chatbot-trigger');
    const chatbotContainer = document.getElementById('chatbot-container');
    const chatbotClose = document.getElementById('chatbot-close');
    const chatbotMinimize = document.getElementById('chatbot-minimize');
    const chatbotMessages = document.getElementById('chatbot-messages');
    const chatbotInputField = document.getElementById('chatbot-input-field');
    const chatbotSend = document.getElementById('chatbot-send');
    
    // User ID from PHP
    const userId = <?php echo $user_id; ?>;
    
    // Toggle chatbot visibility
    chatbotTrigger.addEventListener('click', function() {
        chatbotContainer.style.display = 'flex';
        chatbotTrigger.style.display = 'none';
        chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
    });
    
    // Close chatbot
    chatbotClose.addEventListener('click', function() {
        chatbotContainer.style.display = 'none';
        chatbotTrigger.style.display = 'flex';
    });
    
    // Minimize chatbot
    chatbotMinimize.addEventListener('click', function() {
        chatbotContainer.style.display = 'none';
        chatbotTrigger.style.display = 'flex';
    });
    
    // Send message on Enter key
    chatbotInputField.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });
    
    // Send message on button click
    chatbotSend.addEventListener('click', sendMessage);
    
    // Function to send message
    function sendMessage() {
        const message = chatbotInputField.value.trim();
        if (message === '') return;
        
        // Add user message to chat
        addMessage(message, 'user');
        chatbotInputField.value = '';
        
        // Show typing indicator
        showTypingIndicator();
        
        // Send message to server
        fetch('../../backend/api/chatbot.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                message: message,
                user_id: userId
            })
        })
        .then(response => response.json())
        .then(data => {
            // Hide typing indicator
            hideTypingIndicator();
            
            // Add bot response to chat
            if (data.response) {
                addMessage(data.response, 'bot');
            } else if (data.error) {
                addMessage('Xin lỗi, có lỗi xảy ra: ' + data.error, 'bot');
            }
        })
        .catch(error => {
            // Hide typing indicator
            hideTypingIndicator();
            
            // Show error message
            addMessage('Xin lỗi, có lỗi kết nối. Vui lòng thử lại sau.', 'bot');
            console.error('Error:', error);
        });
    }
    
    // Function to add message to chat
    function addMessage(message, sender) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `chatbot-message ${sender}`;
        
        const messageContent = document.createElement('div');
        messageContent.className = 'chatbot-message-content';
        
        const messageParagraph = document.createElement('p');
        messageParagraph.textContent = message;
        
        messageContent.appendChild(messageParagraph);
        messageDiv.appendChild(messageContent);
        
        chatbotMessages.appendChild(messageDiv);
        
        // Scroll to bottom
        chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
    }
    
    // Function to show typing indicator
    function showTypingIndicator() {
        const typingDiv = document.createElement('div');
        typingDiv.className = 'chatbot-message bot chatbot-typing';
        typingDiv.id = 'typing-indicator';
        
        const typingContent = document.createElement('div');
        typingContent.className = 'chatbot-message-content';
        
        const dot1 = document.createElement('span');
        dot1.className = 'dot';
        const dot2 = document.createElement('span');
        dot2.className = 'dot';
        const dot3 = document.createElement('span');
        dot3.className = 'dot';
        
        typingContent.appendChild(dot1);
        typingContent.appendChild(dot2);
        typingContent.appendChild(dot3);
        
        typingDiv.appendChild(typingContent);
        
        chatbotMessages.appendChild(typingDiv);
        chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
    }
    
    // Function to hide typing indicator
    function hideTypingIndicator() {
        const typingIndicator = document.getElementById('typing-indicator');
        if (typingIndicator) {
            typingIndicator.remove();
        }
    }
});
</script> 