<?php
// api_celebs.php

ob_start(); // Start output buffering

// It's best to set your PHP error reporting in php.ini or globally for development
// error_reporting(E_ALL);
// ini_set('display_errors', 1); // For development only

require_once __DIR__ . '/database.php'; // Ensure correct path
require_once __DIR__ . '/objects/Name.php'; // Ensure correct path

// Default response structure
$response = [
    'error' => null,
    'celebs' => [],
    'totalCount' => 0,
    'offset' => 0,
    'limit' => 10,
    'search' => '',
    'filters' => [], // To reflect applied filters
];
$httpStatusCode = 200;

try {
    // Pagination
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    if ($limit <= 0) $limit = 10;
    if ($offset < 0) $offset = 0;
    if ($limit > 50) $limit = 50; // Max limit

    // Search
    $search_name = $_GET['search'] ?? '';

    // Filters
    $professions_str = $_GET['professions'] ?? null; // e.g., "actor,director"
    $birthYearStart = isset($_GET['birthYearStart']) && is_numeric($_GET['birthYearStart']) ? (int)$_GET['birthYearStart'] : null;
    $birthYearEnd = isset($_GET['birthYearEnd']) && is_numeric($_GET['birthYearEnd']) ? (int)$_GET['birthYearEnd'] : null;

    $response['offset'] = $offset;
    $response['limit'] = $limit;
    $response['search'] = $search_name;
    $response['filters'] = [
        'professions' => $professions_str,
        'birthYearStart' => $birthYearStart,
        'birthYearEnd' => $birthYearEnd,
    ];

    $totalCount = getNamesCount(
        $search_name,
        $professions_str,
        $birthYearStart,
        $birthYearEnd
    );
    $response['totalCount'] = $totalCount;

    if ($totalCount > 0 && $offset < $totalCount) {
        $namesList = getNamesList(
            $offset,
            $limit,
            $search_name,
            $professions_str,
            $birthYearStart,
            $birthYearEnd
        );
        $response['celebs'] = $namesList;
    } else {
        $response['celebs'] = []; // Ensure it's an array
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

// Clean any extraneous output that might have occurred before headers
ob_end_clean();

// Set headers
http_response_code($httpStatusCode);
header("Content-Type: application/json; charset=UTF-8");

// CORS Headers - Adjust http://localhost:5173 to your React app's actual origin
header("Access-Control-Allow-Origin: *"); // Be specific in production
header("Access-Control-Allow-Credentials: true"); // If you use cookies/sessions
header("Access-Control-Allow-Methods: GET, POST, OPTIONS"); // Add POST if you use it
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle OPTIONS pre-flight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
        // Fine, headers are already set
    }
    exit(0);
}

// Send JSON Response
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
// No need for exit() here if it's the last thing, but it's fine.
?>