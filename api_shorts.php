<?php
// api_movies.php

// Set headers for JSON output and CORS (if needed for development)

header('Access-Control-Allow-Origin: http://localhost:5173'); // YOUR REACT DEV SERVER URL
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0); // Handle preflight CORS request
}
header('Content-Type: application/json');

// Include your database functions and classes
// Adjust the path if api_movies.php is not in the same directory as database.php
require_once __DIR__ . '/database.php'; // This should include connection.php and Title.php

// Default values for pagination and search
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 8; // Default limit matches your React page
$searchTerm = $_GET['search'] ?? $_GET['title'] ?? ""; // Allow 'search' or 'title' as query param for search term

// Validate limit to prevent excessively large requests
if ($limit > 50) {
    $limit = 50; // Max limit
}
if ($limit < 1) {
    $limit = 1;
}
if ($offset < 0) {
    $offset = 0;
}

$response = [
    'movies' => [],
    'totalCount' => 0,
    'error' => null,
];

try {
    // Get total count of movies matching the search term (for pagination)
    // Assuming getMoviesCount expects the search term as its parameter
    $totalCount = getTitleCount($searchTerm, $type = 'short');
    $response['totalCount'] = (int)$totalCount;

    $moviesList = [];
    if ($totalCount > 0 && $offset < $totalCount) {
        // Fetch the paginated list of movies
        // Pass $searchTerm to your getMovies function
        $moviesList = getTitles($offset, $limit, $searchTerm, $type = 'short');
    }
    
    // The $moviesList from getMovies already contains Title objects.
    // Title objects implement JsonSerializable, so they will be correctly converted.
    $response['movies'] = $moviesList;

} catch (PDOException $e) {
    error_log("API PDOException: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    $response['error'] = "A database error occurred: " . $e->getMessage();
} catch (Throwable $th) {
    error_log("API Throwable: " . $th->getMessage());
    http_response_code(500); // Internal Server Error
    $response['error'] = "An unexpected error occurred: " . $th->getMessage();
}

// Send the JSON response
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

?>