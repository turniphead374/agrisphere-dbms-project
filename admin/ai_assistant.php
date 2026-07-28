<?php
/**
 * Admin - AI Assistant
 * Platform management and analytics assistance
 */

$pageTitle = 'AI Assistant';
$currentPage = 'ai_assistant';

require_once __DIR__ . '/../includes/header.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('error', 'Please login as admin to access this page');
    redirect(BASE_URL . 'auth/login.php?role=admin');
}

$adminId = getCurrentUserId();

// Get chat history (with error handling)
$chatHistory = [];
$stmt = $conn->prepare("
    SELECT * FROM ai_log
    WHERE user_id = ? AND user_type = 'admin'
    ORDER BY timestamp DESC
    LIMIT 20
");
if ($stmt) {
    $stmt->bind_param("i", $adminId);
    $stmt->execute();
    $chatHistory = array_reverse($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}

// Get platform stats for context (with error handling)
$usersResult = $conn->query("SELECT COUNT(*) as count FROM user WHERE User_type != 'Admin'");
$totalUsers = $usersResult ? ($usersResult->fetch_assoc()['count'] ?? 0) : 0;

$productsResult = $conn->query("SELECT COUNT(*) as count FROM farm_product WHERE status = 'approved'");
$totalProducts = $productsResult ? ($productsResult->fetch_assoc()['count'] ?? 0) : 0;

$ordersResult = $conn->query("SELECT COUNT(*) as count FROM customer_order");
$totalOrders = $ordersResult ? ($ordersResult->fetch_assoc()['count'] ?? 0) : 0;

$revenueResult = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM customer_order WHERE status = 'Delivered'");
$totalRevenue = $revenueResult ? ($revenueResult->fetch_assoc()['total'] ?? 0) : 0;

// Include sidebar
include __DIR__ . '/../includes/sidebar_admin.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">AI Platform Assistant</h1>
    <p class="page-subtitle">Get insights on platform analytics, user management, and business trends.</p>
</div>

<!-- Quick Stats for Context -->
<div class="stats-grid mb-4" style="grid-template-columns: repeat(4, 1fr);">
    <div class="mini-stat">
        <span class="mini-stat-value"><?php echo $totalUsers; ?></span>
        <span class="mini-stat-label">Users</span>
    </div>
    <div class="mini-stat">
        <span class="mini-stat-value"><?php echo $totalProducts; ?></span>
        <span class="mini-stat-label">Products</span>
    </div>
    <div class="mini-stat">
        <span class="mini-stat-value"><?php echo $totalOrders; ?></span>
        <span class="mini-stat-label">Orders</span>
    </div>
    <div class="mini-stat">
        <span class="mini-stat-value"><?php echo formatCurrency($totalRevenue); ?></span>
        <span class="mini-stat-label">Revenue</span>
    </div>
</div>

<div class="chat-container">
    <!-- Suggested Questions -->
    <div class="chat-suggestions">
        <div class="chat-suggestions-title">Suggested Questions</div>
        <div class="chat-suggestion-chips">
            <span class="chat-suggestion-chip" onclick="askQuestion(this.textContent)">Summarize platform performance</span>
            <span class="chat-suggestion-chip" onclick="askQuestion(this.textContent)">Tips to increase user engagement</span>
            <span class="chat-suggestion-chip" onclick="askQuestion(this.textContent)">How to handle product disputes?</span>
            <span class="chat-suggestion-chip" onclick="askQuestion(this.textContent)">Best practices for order management</span>
        </div>
    </div>

    <!-- Chat Messages -->
    <div class="chat-messages" id="chatMessages">
        <?php if (empty($chatHistory)): ?>
            <div class="chat-message ai">
                <div class="chat-avatar"><i class="fas fa-robot"></i></div>
                <div class="chat-bubble">
                    Hello, Administrator! I'm your AI platform assistant. I can help you with:
                    <ul style="margin: 12px 0 0 16px;">
                        <li>Platform analytics and performance insights</li>
                        <li>User management recommendations</li>
                        <li>Product quality control suggestions</li>
                        <li>Order and logistics optimization</li>
                        <li>Business growth strategies</li>
                    </ul>
                    How can I assist you today?
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
                   placeholder="Ask about platform management, analytics, or best practices..." required>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-paper-plane"></i>
            </button>
        </form>
    </div>
</div>

<style>
.mini-stat {
    background: white;
    padding: 16px;
    border-radius: var(--radius);
    text-align: center;
    box-shadow: var(--shadow-sm);
}

.mini-stat-value {
    display: block;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--primary);
}

.mini-stat-label {
    font-size: 0.75rem;
    color: var(--gray-500);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
</style>

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
                context: 'admin',
                user_id: <?php echo $adminId; ?>,
                platform_stats: {
                    users: <?php echo $totalUsers; ?>,
                    products: <?php echo $totalProducts; ?>,
                    orders: <?php echo $totalOrders; ?>,
                    revenue: <?php echo $totalRevenue; ?>
                }
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
