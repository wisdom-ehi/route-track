<?php
// api/save.php
// Save or update a tracking record (admin use).
// Accepts POST with JSON body or form-encoded fields.
// Returns JSON { success: true, tracking_number: '...' }

header('Content-Type: application/json; charset=utf-8');
// Adjust this for production to restrict allowed origin
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$raw = file_get_contents('php://input');
$data = [];

// Try parse JSON body
if ($raw) {
    $parsed = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $data = $parsed;
    }
}

// Fallback to form fields
if (empty($data) && !empty($_POST)) {
    $data = $_POST;
}

// If still empty return error
if (empty($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'No data provided']);
    exit;
}

// Generate or sanitize tracking number
if (empty($data['tracking_number'])) {
    // simple random number like RT-ABCDEFG (7 chars)
    $rand = strtoupper(substr(bin2hex(random_bytes(4)), 0, 7));
    $tracking_number = 'RT-' . $rand;
} else {
    $tracking_number = preg_replace('/[^A-Za-z0-9\-_]/', '', $data['tracking_number']);
    if (!$tracking_number) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid tracking number']);
        exit;
    }
}

// Build the JSON structure expected by the frontend
// You can accept fields from $data or fallback to defaults
$record = [
    'tracking_number' => $tracking_number,
    'status' => $data['status'] ?? 'pending', // pending | in_transit | out_for_delivery | delivered | exception
    'description' => $data['description'] ?? '',
    'current_location' => $data['current_location'] ?? ($data['location'] ?? ''),
    'last_updated' => $data['last_updated'] ?? date('c'),
    'estimated_delivery' => $data['estimated_delivery'] ?? date('Y-m-d', strtotime('+2 days')),
    'estimated_time' => $data['estimated_time'] ?? '',
    'latitude' => isset($data['latitude']) ? floatval($data['latitude']) : null,
    'longitude' => isset($data['longitude']) ? floatval($data['longitude']) : null,
    'route_coordinates' => $data['route_coordinates'] ?? [], // array of [lat, lng]
    'progress_steps' => $data['progress_steps'] ?? [], // array of {title, location, date, time, status}
    'package_details' => $data['package_details'] ?? [], // weight, dimensions, etc.
    'history' => $data['history'] ?? [], // array of events
];

// Ensure directories exist
$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) {
    if (!mkdir($dataDir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['error' => 'Unable to create data directory']);
        exit;
    }
}

// Save file
$file = "$dataDir/tracking_{$tracking_number}.json";
$json = json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if (file_put_contents($file, $json) === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Unable to save tracking data']);
    exit;
}

echo json_encode(['success' => true, 'tracking_number' => $tracking_number]);
exit;
