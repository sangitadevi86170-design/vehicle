<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Get Vehicle Parameter
$vehicleNum = $_GET['num'] ?? $_GET['vehicle'] ?? $_GET['query'] ?? $_GET['rc'] ?? $_GET['q'] ?? null;

if (!$vehicleNum) {
    echo json_encode([
        "success" => false,
        "message" => "Please provide a Vehicle Number (e.g. ?num=MH02FZ0555 or ?vehicle=MH02FZ0555)",
        "credit" => "https://t.me/TOXIC_ADMINN",
        "channel" => "https://t.me/botadminshere"
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// Clean Input (Uppercase & Remove Special Chars)
$vehicleNum = strtoupper(trim(preg_replace('/[^a-zA-Z0-9]/', '', $vehicleNum)));

// Target Backend API
$targetApiUrl = "https://bronx-ultra-king.duckdns.org/api/custom/ve2-num?key=999&num=" . urlencode($vehicleNum);

// Fetch Data via cURL
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $targetApiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to connect to backend server",
        "credit" => "https://t.me/TOXIC_ADMINN",
        "channel" => "https://t.me/botadminshere",
        "query" => $vehicleNum
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$data = json_decode($response, true);

// If response is invalid or contains backend error (e.g. 404 not found)
if (!$data || isset($data['error']) || (isset($data['success']) && $data['success'] === false)) {
    echo json_encode([
        "success" => false,
        "message" => "No record found for this vehicle number",
        "credit" => "https://t.me/TOXIC_ADMINN",
        "channel" => "https://t.me/botadminshere",
        "query" => $vehicleNum
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// Remove third-party branding, owner info, and keys
unset($data['powered_by'], $data['api_info'], $data['developer'], $data['notice'], $data['key'], $data['credits']);

// Filter out any third-party promotional usernames or links
function clean_promo($val) {
    if (is_string($val)) {
        $val = preg_replace('/^[^\r\n]*(@Dead_bans|t\.me\/Dead_bans|@BRONX_ULTRA|t\.me\/BRONX_ULTRA)[^\r\n]*(\r?\n)?/mi', '', $val);
        $val = preg_replace('/(https?:\/\/)?(t\.me\/|@)Dead_bans\b/i', '', $val);
        $val = preg_replace('/(https?:\/\/)?(t\.me\/|@)BRONX_ULTRA\b/i', '', $val);
        return preg_replace("/\n{3,}/", "\n\n", trim($val));
    } elseif (is_array($val)) {
        return array_map('clean_promo', $val);
    }
    return $val;
}

$data = clean_promo($data);

// Output response
$output = [
    "success" => true,
    "credit" => "https://t.me/TOXIC_ADMINN",
    "channel" => "https://t.me/botadminshere",
    "query" => $vehicleNum
];

// Add mobile_number directly at top level if present
if (isset($data['mobile_number'])) {
    $output['mobile_number'] = $data['mobile_number'];
}

// Include cleaned data
$output['data'] = $data;

echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>