<?php
// api_all.php

ob_start(); // Start output buffering for better error/header control

// CORS Headers - Adjust http://localhost:5173 to your React app's actual origin
header("Access-Control-Allow-Origin: http://localhost:5173"); // Be specific in production
header("Access-Control-Allow-Credentials: true"); // If you use cookies/sessions
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle OPTIONS pre-flight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
        // Fine, headers are already set
    }
    exit(0);
}

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/database.php';

// Default values for pagination and search
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 8;
$searchTerm = $_GET['search'] ?? $_GET['title'] ?? "";

// Filter parameters from GET request
$minRating = $_GET['minRating'] ?? null;
$maxRating = $_GET['maxRating'] ?? null;
$genres_str = $_GET['genres'] ?? null;          // Comma-separated string
$startYearFrom = $_GET['startYearFrom'] ?? null;  // For title's startYear
$startYearTo = $_GET['startYearTo'] ?? null;    // For title's startYear
$isAdult = $_GET['isAdult'] ?? null;            // '0' or '1'
$titleTypes_str = $_GET['titleTypes'] ?? null;  // Comma-separated string e.g. "movie,tvSeries"

// Validate limit and offset
if ($limit > 50) $limit = 50;
if ($limit < 1) $limit = 1;
if ($offset < 0) $offset = 0;

$response = [
    'items' => [], // Changed from 'movies' to 'items' for generic "all" endpoint
    'totalCount' => 0,
    'error' => null,
    'filters' => [], // To reflect applied filters
];
$httpStatusCode = 200;

try {
    $response['filters'] = [
        'search' => $searchTerm,
        'minRating' => $minRating,
        'maxRating' => $maxRating,
        'genres' => $genres_str,
        'startYearFrom' => $startYearFrom,
        'startYearTo' => $startYearTo,
        'isAdult' => $isAdult,
        'titleTypes' => $titleTypes_str,
        'offset' => $offset,
        'limit' => $limit,
    ];

    $totalCount = getAllCounts(
        $searchTerm,
        $minRating,
        $maxRating,
        $genres_str,
        $startYearFrom,
        $startYearTo,
        $isAdult,
        $titleTypes_str
    );
    $response['totalCount'] = (int)$totalCount;

    $itemsList = [];
    if ($totalCount > 0 && $offset < $totalCount) {
        $itemsList = getAll(
            $offset,
            $limit,
            $searchTerm,
            $minRating,
            $maxRating,
            $genres_str,
            $startYearFrom,
            $startYearTo,
            $isAdult,
            $titleTypes_str
        );
    }
    
    $response['items'] = $itemsList;

} catch (PDOException $e) {
    $httpStatusCode = 500;
    $response['error'] = "A database error occurred: " . $e->getMessage();
    error_log("API All (PDOException): " . $e->getMessage());
} catch (Throwable $th) { // Changed to Throwable to catch more error types
    $httpStatusCode = 500;
    $response['error'] = "An unexpected error occurred: " . $th->getMessage();
    error_log("API All (Throwable): " . $th->getMessage());
}

ob_end_clean(); // Clean buffer before sending response

http_response_code($httpStatusCode);
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>