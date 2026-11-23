<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Get Vehicle Parameter
$vehicleNum = $_GET['vehicle'] ?? $_GET['q'] ?? $_GET['query'] ?? $_GET['rc'] ?? $_GET['num'] ?? null;

if (!$vehicleNum) {
    echo json_encode([
        "success" => false,
        "message" => "Please provide a Vehicle Number (e.g. ?vehicle=UP70DN1860 or ?q=UP70DN1860)"
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// Clean Input (Uppercase & Remove Special Chars)
$vehicleNum = strtoupper(trim(preg_replace('/[^a-zA-Z0-9]/', '', $vehicleNum)));

// Target Backend API
$targetApiUrl = "https://altaf-api-maker.base44.app/functions/lookup?tool=veh2num&key=Demo&q=" . urlencode($vehicleNum);

// Fetch Data via cURL
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $targetApiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_TIMEOUT => 45,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || $httpCode !== 200) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch vehicle data from server",
        "query" => $vehicleNum
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$data = json_decode($response, true);

if (!$data) {
    echo json_encode([
        "success" => false,
        "message" => "No record found or invalid response format",
        "raw_response" => $response
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// Remove third-party branding / internal keys
unset($data['developer'], $data['notice'], $data['key'], $data['credits'], $data['powered_by'], $data['api_info']);

// Filter out promo handles (@Dead_bans, @Your_father_786k, etc.)
function clean_promo($val) {
    if (is_string($val)) {
        $val = preg_replace('/^[^\r\n]*(@Dead_bans|t\.me\/Dead_bans|@Your_father_786k|t\.me\/Your_father_786k)[^\r\n]*(\r?\n)?/mi', '', $val);
        $val = preg_replace('/(https?:\/\/)?(t\.me\/|@)Dead_bans\b/i', '', $val);
        $val = preg_replace('/(https?:\/\/)?(t\.me\/|@)Your_father_786k\b/i', '', $val);
        return preg_replace("/\n{3,}/", "\n\n", trim($val));
    } elseif (is_array($val)) {
        return array_map('clean_promo', $val);
    }
    return $val;
}

$data = clean_promo($data);

// Simple & Clean API Output
$output = [
    "success" => $data['success'] ?? true,
    "query" => $vehicleNum,
    "result" => $data['result'] ?? null,
    "data" => $data
];

echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>