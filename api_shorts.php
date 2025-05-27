<?php
// api_series.php

header('Access-Control-Allow-Origin: *'); // YOUR REACT DEV SERVER URL
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0); // Handle preflight CORS request
}
header('Content-Type: application/json');

require_once __DIR__ . '/database.php'; 

// Default values for pagination and search
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 8;
$searchTerm = $_GET['search'] ?? $_GET['title'] ?? "";
$type = 'short'; // Define type for this API, e.g. 'movie'

// Filter parameters
$minRating = $_GET['minRating'] ?? null;
$maxRating = $_GET['maxRating'] ?? null;
$genres_str = $_GET['genres'] ?? null; // Expects comma-separated string like "Action,Drama"
$startYear = $_GET['startYear'] ?? null;
$endYear = $_GET['endYear'] ?? null;
$isAdult = $_GET['isAdult'] ?? null; // Expects '0' or '1', or null/empty for any

// Validate limit and offset
if ($limit > 50) $limit = 50;
if ($limit < 1) $limit = 1;
if ($offset < 0) $offset = 0;

$response = [
    'movies' => [],
    'totalCount' => 0,
    'error' => null,
];

try {
    // Get total count of movies matching the search term and filters
    $totalCount = getTitleCount(
        $searchTerm,
        $type,
        $minRating,
        $maxRating,
        $genres_str,
        $startYear,
        $endYear,
        $isAdult
    );
    $response['totalCount'] = (int)$totalCount;

    $moviesList = [];
    if ($totalCount > 0 && $offset < $totalCount) {
        // Fetch the paginated list of movies with search term and filters
        $moviesList = getTitles(
            $offset,
            $limit,
            $searchTerm,
            $type,
            $minRating,
            $maxRating,
            $genres_str,
            $startYear,
            $endYear,
            $isAdult
        );
    }
    
    $response['movies'] = $moviesList;

} catch (PDOException $e) {
    error_log("API PDOException: " . $e->getMessage());
    http_response_code(500); 
    $response['error'] = "A database error occurred: " . $e->getMessage();
} catch (Throwable $th) {
    error_log("API Throwable: " . $th->getMessage());
    http_response_code(500); 
    $response['error'] = "An unexpected error occurred: " . $th->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>