<?php
// api/tracking.php
// Returns JSON for a given tracking number
header('Content-Type: application/json; charset=utf-8');
// Adjust this for security - restrict origins in production
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Support preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Determine tracking number
$tracking = '';
if (!empty($_GET['tracking'])) {
    $tracking = $_GET['tracking'];
} else {
    // try to parse PATH_INFO as fallback
    if (!empty($_SERVER['PATH_INFO'])) {
        $pieces = explode('/', trim($_SERVER['PATH_INFO'], '/'));
        if (!empty($pieces[0])) $tracking = $pieces[0];
    }
}

if (!$tracking) {
    http_response_code(400);
    echo json_encode(['error' => 'No tracking number provided']);
    exit;
}

// sanitize filename (allow alphanumeric, -, _)
$clean = preg_replace('/[^A-Za-z0-9\-_]/', '', $tracking);
$dataDir = __DIR__ . '/data';
$file = "$dataDir/tracking_{$clean}.json";

if (!file_exists($file)) {
    http_response_code(404);
    echo json_encode(['error' => 'Tracking not found']);
    exit;
}

$content = file_get_contents($file);
if ($content === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Unable to read tracking data']);
    exit;
}

// Return stored JSON as-is
echo $content;
exit;
