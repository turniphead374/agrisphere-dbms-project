<?php
/**
 * AI Chat API Endpoint
 * Handles communication with Gemini 2.0 Flash API
 */

define('AGRISPHERE', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

$message = $input['message'] ?? '';
$context = $input['context'] ?? 'general';
$userId = (int)($input['user_id'] ?? 0);

if (empty($message)) {
    echo json_encode(['error' => 'Message is required']);
    exit;
}

// Enhanced system prompts for each context
$systemPrompts = [
    'farmer' => "You are AgriBot, an expert AI farming assistant for AgriSphere platform in Bangladesh. You help farmers with:
- Crop cultivation techniques and best practices
- Current market prices (use Bangladeshi Taka ৳)
- Soil management and fertilizer recommendations
- Pest and disease control methods
- Weather-based farming advice
- Harvest timing and post-harvest handling
- Government agricultural schemes and subsidies

Be practical, concise, and use simple language. Include specific prices when discussing costs. Format responses with bullet points or numbered lists when helpful.",

    'customer' => "You are AgriBot, a friendly AI shopping assistant for AgriSphere platform in Bangladesh. You help customers with:
- Finding fresh farm products
- Nutritional information about fruits and vegetables
- Seasonal produce recommendations
- Storage tips to keep produce fresh
- Healthy eating suggestions
- Price comparisons and best value options
- Cooking tips and recipe suggestions

Use Bangladeshi Taka (৳) for prices. Be friendly, helpful, and encourage healthy eating habits.",

    'admin' => "You are AgriBot, a professional AI business assistant for AgriSphere platform administrators. You help with:
- Platform analytics and performance metrics
- User engagement strategies
- Product quality control recommendations
- Order management optimization
- Inventory management insights
- Business growth strategies
- Customer and farmer retention tips
- Dispute resolution best practices

Be professional, data-focused, and provide actionable recommendations.",

    'general' => "You are AgriBot, the AI assistant for AgriSphere, an agricultural e-commerce platform connecting farmers directly with customers in Bangladesh. Provide helpful, accurate information about agriculture, fresh produce, and the platform."
];

$systemPrompt = $systemPrompts[$context] ?? $systemPrompts['general'];

// Check if Gemini API key is configured
if (empty(GEMINI_API_KEY)) {
    // Return mock response if no API key
    $mockResponses = [
        'price' => "Based on current government rates:\n- Rice (Miniket): ৳68/kg\n- Potato: ৳30/kg\n- Tomato: ৳50/kg\n- Onion: ৳45/kg\n\nPrices may vary slightly based on quality and location.",

        'tomato' => "Best time to plant tomatoes in Bangladesh:\n\n1. **Rabi Season (Oct-Feb)**: Ideal for tomato cultivation\n2. **Temperature**: 20-25°C is optimal\n3. **Soil**: Well-drained, loamy soil with pH 6.0-7.0\n\n**Tips:**\n- Start seedlings in nursery beds\n- Transplant after 25-30 days\n- Ensure proper spacing (60x45 cm)\n- Water regularly but avoid waterlogging",

        'soil' => "To improve soil quality:\n\n1. **Add Organic Matter**: Compost, cow dung, or green manure\n2. **Crop Rotation**: Alternate between legumes and other crops\n3. **Mulching**: Reduces water loss and adds nutrients\n4. **Avoid Over-tilling**: Preserves soil structure\n5. **Test Soil pH**: Adjust with lime or sulfur as needed\n\nRegular soil testing every 2-3 years is recommended.",

        'default' => "Thank you for your question! As your farming assistant, I can help with:\n\n- Current market prices\n- Crop cultivation tips\n- Soil management\n- Pest control methods\n- Weather-based farming advice\n\nPlease feel free to ask specific questions about any of these topics.",

        'admin_performance' => "Platform Performance Summary:\n\nBased on your current metrics, here are key insights:\n\n1. **User Engagement**: Monitor active users vs registered users ratio\n2. **Product Quality**: Review pending products promptly to maintain marketplace freshness\n3. **Order Fulfillment**: Track processing times and delivery success rates\n4. **Revenue Trends**: Compare monthly revenue against targets\n\n**Recommendations:**\n- Set up automated alerts for low stock items\n- Implement a product rating system\n- Consider promotional campaigns during seasonal peaks",

        'admin_engagement' => "Tips to Increase User Engagement:\n\n1. **For Farmers:**\n   - Send price alerts when government rates change\n   - Provide seasonal crop recommendations\n   - Offer incentives for quality products\n\n2. **For Customers:**\n   - Implement loyalty/reward points\n   - Send personalized product recommendations\n   - Offer bundle deals on popular items\n\n3. **Platform-wide:**\n   - Add product reviews and ratings\n   - Create a newsletter with farming tips\n   - Host seasonal promotions",

        'admin_disputes' => "Handling Product Disputes - Best Practices:\n\n1. **Prevention:**\n   - Clear product listing guidelines\n   - Mandatory product images\n   - Accurate weight/quantity verification\n\n2. **Resolution Process:**\n   - Respond within 24 hours\n   - Gather evidence from both parties\n   - Offer fair compensation (refund/replacement)\n\n3. **Follow-up:**\n   - Document all cases\n   - Identify repeat offenders\n   - Update policies based on patterns",

        'admin_orders' => "Order Management Best Practices:\n\n1. **Processing:**\n   - Set clear SLAs (e.g., process within 24h)\n   - Prioritize perishable items\n   - Batch similar orders for efficiency\n\n2. **Tracking:**\n   - Update status at each stage\n   - Notify customers proactively\n   - Handle delays transparently\n\n3. **Quality Control:**\n   - Verify product quality before dispatch\n   - Use proper packaging for freshness\n   - Collect delivery feedback",

        'admin_default' => "As your platform assistant, I can help with:\n\n- Platform analytics and performance insights\n- User engagement strategies\n- Product quality control recommendations\n- Order management optimization\n- Business growth strategies\n\nPlease ask specific questions about any of these topics!"
    ];

    // Simple keyword matching for mock responses
    $lowerMessage = strtolower($message);

    if ($context === 'admin') {
        // Admin-specific responses
        $response = $mockResponses['admin_default'];

        if (strpos($lowerMessage, 'performance') !== false || strpos($lowerMessage, 'summary') !== false || strpos($lowerMessage, 'analytics') !== false) {
            $response = $mockResponses['admin_performance'];
        } elseif (strpos($lowerMessage, 'engagement') !== false || strpos($lowerMessage, 'increase') !== false) {
            $response = $mockResponses['admin_engagement'];
        } elseif (strpos($lowerMessage, 'dispute') !== false || strpos($lowerMessage, 'complaint') !== false) {
            $response = $mockResponses['admin_disputes'];
        } elseif (strpos($lowerMessage, 'order') !== false || strpos($lowerMessage, 'management') !== false) {
            $response = $mockResponses['admin_orders'];
        }
    } elseif ($context === 'customer') {
        // Customer-specific responses
        $customerResponses = [
            'season' => "Seasonal Produce in Bangladesh:\n\n**Winter (Nov-Feb):**\n- Vegetables: Cauliflower, Cabbage, Carrots, Radish, Tomatoes\n- Fruits: Oranges, Grapes, Strawberries\n\n**Summer (Mar-Jun):**\n- Vegetables: Bitter Gourd, Pointed Gourd, Okra\n- Fruits: Mango, Jackfruit, Lychee, Watermelon\n\n**Monsoon (Jul-Oct):**\n- Vegetables: Pumpkin, Eggplant, Green Beans\n- Fruits: Guava, Papaya, Jamun\n\nBuying seasonal produce ensures freshness and better prices!",

            'diabetic' => "Healthy Fruits for Diabetics:\n\n**Best Choices (Low Glycemic Index):**\n1. **Guava** - High fiber, low GI\n2. **Papaya** - Moderate portions, rich in fiber\n3. **Apple** - Good fiber content\n4. **Pear** - Low GI, nutritious\n5. **Orange** - Vitamin C, moderate sugar\n\n**Tips:**\n- Eat whole fruits, not juices\n- Control portion sizes\n- Pair with protein (nuts) to slow sugar absorption\n- Avoid overripe fruits (higher sugar)\n\nConsult your doctor for personalized advice.",

            'storage' => "How to Store Fresh Produce:\n\n**Refrigerator (2-7 days):**\n- Leafy greens in damp paper towel\n- Tomatoes at room temp (fridge only when ripe)\n- Carrots, beans in airtight containers\n\n**Room Temperature:**\n- Potatoes, onions in cool, dark place\n- Bananas on counter (separate from others)\n- Mangoes until ripe, then refrigerate\n\n**Tips:**\n- Don't wash until ready to use\n- Store fruits and veggies separately\n- Check daily and remove spoiled items\n- Use oldest items first",

            'default' => "Hello! I'm your shopping assistant. I can help with:\n\n- Seasonal produce recommendations\n- Nutritional information\n- Storage and freshness tips\n- Current market prices\n- Healthy eating suggestions\n\nWhat would you like to know about?"
        ];

        $response = $customerResponses['default'];

        if (strpos($lowerMessage, 'season') !== false || strpos($lowerMessage, 'vegetable') !== false) {
            $response = $customerResponses['season'];
        } elseif (strpos($lowerMessage, 'diabetic') !== false || strpos($lowerMessage, 'sugar') !== false || strpos($lowerMessage, 'healthy') !== false) {
            $response = $customerResponses['diabetic'];
        } elseif (strpos($lowerMessage, 'store') !== false || strpos($lowerMessage, 'storage') !== false || strpos($lowerMessage, 'fresh') !== false) {
            $response = $customerResponses['storage'];
        } elseif (strpos($lowerMessage, 'price') !== false) {
            $response = $mockResponses['price'];
        }
    } else {
        // Farmer responses
        $response = $mockResponses['default'];

        if (strpos($lowerMessage, 'price') !== false) {
            $response = $mockResponses['price'];
        } elseif (strpos($lowerMessage, 'tomato') !== false) {
            $response = $mockResponses['tomato'];
        } elseif (strpos($lowerMessage, 'soil') !== false) {
            $response = $mockResponses['soil'];
        }
    }

} else {
    // Call Gemini 2.0 Flash API
    $apiUrl = GEMINI_API_URL . '?key=' . GEMINI_API_KEY;

    // Build the request body for Gemini 2.0 Flash
    $requestBody = [
        'contents' => [
            [
                'role' => 'user',
                'parts' => [
                    ['text' => $systemPrompt . "\n\n---\nUser Question: " . $message . "\n\nPlease provide a helpful response:"]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'topK' => 40,
            'topP' => 0.95,
            'maxOutputTokens' => 2048,
            'responseMimeType' => 'text/plain'
        ],
        'safetySettings' => [
            [
                'category' => 'HARM_CATEGORY_HARASSMENT',
                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
            ],
            [
                'category' => 'HARM_CATEGORY_HATE_SPEECH',
                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
            ],
            [
                'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
            ],
            [
                'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
            ]
        ]
    ];

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        $response = 'Network error: Unable to connect to AI service. Please check your internet connection.';
    } elseif ($httpCode === 200) {
        $data = json_decode($result, true);

        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            $response = $data['candidates'][0]['content']['parts'][0]['text'];
        } elseif (isset($data['error'])) {
            $response = 'API Error: ' . ($data['error']['message'] ?? 'Unknown error occurred.');
        } else {
            $response = 'Sorry, I could not generate a response. Please try again.';
        }
    } elseif ($httpCode === 400) {
        $data = json_decode($result, true);
        $response = 'Request error: ' . ($data['error']['message'] ?? 'Invalid request.');
    } elseif ($httpCode === 403) {
        $response = 'API access denied. Please check the API key configuration.';
    } elseif ($httpCode === 429) {
        $response = 'Too many requests. Please wait a moment and try again.';
    } else {
        $response = 'Sorry, the AI service is temporarily unavailable (Error: ' . $httpCode . '). Please try again later.';
    }
}

// Log the conversation (with error handling)
if ($userId > 0) {
    $stmt = $conn->prepare("INSERT INTO ai_log (user_id, user_type, query, response, timestamp) VALUES (?, ?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("isss", $userId, $context, $message, $response);
        $stmt->execute();
    }
}

echo json_encode([
    'success' => true,
    'response' => $response
]);
