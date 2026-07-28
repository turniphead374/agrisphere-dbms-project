<?php
require_once "../includes/auth.php";
require_login();
block_check_or_exit($conn);

require_once "../includes/layout.php";
render_topbar("AgriSphere AI");

$q = isset($_SESSION['last_ai_q']) ? $_SESSION['last_ai_q'] : "";
if ($q === "") {
    echo "<div class='card'>No question found.</div>";
    render_footer();
    exit();
}

/*
Gemini key:
- Put your key in a safe place (env var is best)
- For lab demo, hardcoding is okay but risky.
*/
$GEMINI_API_KEY = "PASTE_YOUR_GEMINI_API_KEY_HERE";

if ($GEMINI_API_KEY === "PASTE_YOUR_GEMINI_API_KEY_HERE") {
    echo "<div class='card'>⚠️ Gemini API key not set. Add your key in <b>ai/gemini.php</b>.</div>";
    echo "<div class='card'><b>Your question:</b><br>".htmlspecialchars($q)."</div>";
    render_footer();
    exit();
}

// Gemini REST (Generative Language API)
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . urlencode($GEMINI_API_KEY);

$payload = [
  "contents" => [
    ["parts" => [
      ["text" => "You are AgriSphere AI. Answer clearly, briefly, and practically.\n\nUser question: " . $q]
    ]]
  ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    echo "<div class='card'>❌ Gemini error: ".htmlspecialchars($err)."</div>";
    render_footer();
    exit();
}

$data = json_decode($response, true);

// Parse output safely
$text = "";
if (isset($data["candidates"][0]["content"]["parts"][0]["text"])) {
    $text = $data["candidates"][0]["content"]["parts"][0]["text"];
}

echo "<div class='card'><b>Question:</b><br>".htmlspecialchars($q)."</div>";
echo "<div class='card'><b>Answer:</b><br>".nl2br(htmlspecialchars($text))."</div>";

render_footer();
