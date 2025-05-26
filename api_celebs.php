<?php
// api_celebs.php

ob_start(); // Start output buffering at the VERY TOP

// Suppress notices/deprecations ONLY IF step 1 absolutely doesn't work for some reason.
// It's better to fix the root cause.
// error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

require_once 'database.php';
require_once 'objects/Name.php';

// Default response structure
$response = [
    'error' => null,
    'celebs' => [],
    'totalCount' => 0,
    'offset' => 0, // Will be overridden
    'limit' => 10, // Will be overridden
    'search' => '', // Will be overridden
];
$httpStatusCode = 200; // Default to OK

try {
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $search_name = $_GET['search'] ?? '';

    if ($limit <= 0) $limit = 10;
    if ($offset < 0) $offset = 0;
    if ($limit > 50) $limit = 50;

    $response['offset'] = $offset;
    $response['limit'] = $limit;
    $response['search'] = $search_name;

    $totalCount = getNamesCount($search_name);
    $response['totalCount'] = $totalCount;

    if ($totalCount > 0 && $offset < $totalCount) {
        $namesList = getNamesList($offset, $limit, $search_name);
        $response['celebs'] = $namesList;
    } else {
        $response['celebs'] = []; // Ensure it's an array if no results
    }

} catch (PDOException $e) {
    $httpStatusCode = 500;
    $response['error'] = "Database error: " . $e->getMessage();
    error_log("API Celebs (PDOException): " . $e->getMessage());
} catch (Exception $e) {
    $httpStatusCode = 500;
    $response['error'] = "An unexpected error occurred: " . $e->getMessage();
    error_log("API Celebs (Exception): " . $e->getMessage());
}

// Clean (erase) the output buffer and turn off output buffering
// This discards any premature output like deprecation notices.
ob_end_clean();

// Now send headers
http_response_code($httpStatusCode);
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *"); // For development
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle OPTIONS pre-flight request for CORS (should ideally be before logic, but after ob_start)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    // Headers already sent by this point in this restructured flow.
    // For a pure OPTIONS request, you might exit earlier after sending CORS headers.
    // This is a simplified handling.
    exit();
}

// Send JSON Response
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
exit(); // Ensure no further output
?>