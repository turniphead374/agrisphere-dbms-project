<?php
/**
 * Customer - AI Assistant
 * Product recommendations and shopping assistance
 */

$pageTitle = 'AI Assistant';
$currentPage = 'ai_assistant';

require_once __DIR__ . '/../includes/header.php';

// Check if user is logged in and is a customer
if (!isLoggedIn() || !isCustomer()) {
    setFlashMessage('error', 'Please login as a customer to access this page');
    redirect(BASE_URL . 'auth/login.php?role=customer');
}

// Check if user is blocked
if (isUserBlocked($conn, getCurrentUserId())) {
    session_destroy();
    setFlashMessage('error', 'Your account has been blocked. Please contact support.');
    redirect(BASE_URL . 'auth/login.php');
}

$customerId = getCurrentUserId();

// Get chat history (with error handling)
$chatHistory = [];
$stmt = $conn->prepare("
    SELECT * FROM ai_log
    WHERE user_id = ? AND user_type = 'customer'
    ORDER BY timestamp DESC
    LIMIT 20
");
if ($stmt) {
    $stmt->bind_param("i", $customerId);
    $stmt->execute();
    $chatHistory = array_reverse($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}

// Include sidebar
include __DIR__ . '/../includes/sidebar_customer.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">AI Shopping Assistant</h1>
    <p class="page-subtitle">Get product recommendations, nutrition tips, and shopping advice.</p>
</div>

<div class="chat-container">
    <!-- Suggested Questions -->
    <div class="chat-suggestions">
        <div class="chat-suggestions-title">Suggested Questions</div>
        <div class="chat-suggestion-chips">
            <span class="chat-suggestion-chip" onclick="askQuestion(this.textContent)">What vegetables are in season?</span>
            <span class="chat-suggestion-chip" onclick="askQuestion(this.textContent)">Healthy fruits for diabetics?</span>
            <span class="chat-suggestion-chip" onclick="askQuestion(this.textContent)">How to store fresh produce?</span>
            <span class="chat-suggestion-chip" onclick="askQuestion(this.textContent)">Current price of rice?</span>
        </div>
    </div>

    <!-- Chat Messages -->
    <div class="chat-messages" id="chatMessages">
        <?php if (empty($chatHistory)): ?>
            <div class="chat-message ai">
                <div class="chat-avatar"><i class="fas fa-robot"></i></div>
                <div class="chat-bubble">
                    Hello! I'm your AI shopping assistant. I can help you with:
                    <ul style="margin: 12px 0 0 16px;">
                        <li>Product recommendations based on your needs</li>
                        <li>Nutritional information and health tips</li>
                        <li>Current market prices</li>
                        <li>Storage and cooking tips</li>
                        <li>Seasonal produce suggestions</li>
                    </ul>
                    What would you like to know?
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($chatHistory as $chat): ?>
                <div class="chat-message user">
                    <div class="chat-avatar"><?php echo strtoupper(substr(getCurrentUserName(), 0, 1)); ?></div>
                    <div class="chat-bubble"><?php echo nl2br(sanitize($chat['query'])); ?></div>
                </div>
                <div class="chat-message ai">
                    <div class="chat-avatar"><i class="fas fa-robot"></i></div>
                    <div class="chat-bubble"><?php echo nl2br(sanitize($chat['response'])); ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Chat Input -->
    <div class="chat-input-container">
        <form id="chatForm" style="display: flex; gap: 12px; width: 100%;">
            <input type="text" id="chatInput" class="chat-input"
                   placeholder="Ask about products, nutrition, recipes, or storage tips..." required>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-paper-plane"></i>
            </button>
        </form>
    </div>
</div>

<script>
const chatMessages = document.getElementById('chatMessages');
const chatForm = document.getElementById('chatForm');
const chatInput = document.getElementById('chatInput');

// Scroll to bottom
function scrollToBottom() {
    chatMessages.scrollTop = chatMessages.scrollHeight;
}
scrollToBottom();

// Ask question from suggestion
function askQuestion(question) {
    chatInput.value = question;
    chatForm.dispatchEvent(new Event('submit'));
}

// Handle form submission
chatForm.addEventListener('submit', async function(e) {
    e.preventDefault();

    const question = chatInput.value.trim();
    if (!question) return;

    // Add user message
    const userMessage = document.createElement('div');
    userMessage.className = 'chat-message user';
    userMessage.innerHTML = `
        <div class="chat-avatar"><?php echo strtoupper(substr(getCurrentUserName(), 0, 1)); ?></div>
        <div class="chat-bubble">${escapeHtml(question)}</div>
    `;
    chatMessages.appendChild(userMessage);

    // Clear input
    chatInput.value = '';
    scrollToBottom();

    // Add loading message
    const loadingMessage = document.createElement('div');
    loadingMessage.className = 'chat-message ai';
    loadingMessage.id = 'loadingMessage';
    loadingMessage.innerHTML = `
        <div class="chat-avatar"><i class="fas fa-robot"></i></div>
        <div class="chat-bubble"><i class="fas fa-spinner fa-spin"></i> Thinking...</div>
    `;
    chatMessages.appendChild(loadingMessage);
    scrollToBottom();

    try {
        // Send to API
        const response = await fetch('<?php echo BASE_URL; ?>api/ai_chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                message: question,
                context: 'customer',
                user_id: <?php echo $customerId; ?>
            })
        });

        const data = await response.json();

        // Remove loading message
        document.getElementById('loadingMessage').remove();

        // Add AI response
        const aiMessage = document.createElement('div');
        aiMessage.className = 'chat-message ai';
        aiMessage.innerHTML = `
            <div class="chat-avatar"><i class="fas fa-robot"></i></div>
            <div class="chat-bubble">${data.response ? data.response.replace(/\n/g, '<br>') : 'Sorry, I could not process your request.'}</div>
        `;
        chatMessages.appendChild(aiMessage);
        scrollToBottom();

    } catch (error) {
        // Remove loading message
        document.getElementById('loadingMessage')?.remove();

        // Show error
        const errorMessage = document.createElement('div');
        errorMessage.className = 'chat-message ai';
        errorMessage.innerHTML = `
            <div class="chat-avatar"><i class="fas fa-robot"></i></div>
            <div class="chat-bubble" style="color: var(--error);">
                Sorry, there was an error processing your request. Please try again.
            </div>
        `;
        chatMessages.appendChild(errorMessage);
        scrollToBottom();
    }
});

// Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
