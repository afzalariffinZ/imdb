<?php
// api_movie_detail.php


header('Access-Control-Allow-Origin: *'); // YOUR REACT DEV SERVER URL
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0); // Handle preflight CORS request
}
header('Content-Type: application/json');
// ... CORS headers ...

require_once 'database.php'; // For openConnection() and your data fetching functions
require_once 'objects/Title.php'; // If you construct a Title object
require_once 'objects/Name.php';  // If you want to represent principals as Name objects
require_once 'PosterFetcher.php';

$movieId = $_GET['id'] ?? null; // This should be the tconst

if (!$movieId) { /* ... handle error ... */ exit; }

try {
    $db = openConnection();

    // 1. Fetch Basic Movie Info (from title_basics_trim, title_ratings_trim)
    //    Make sure to get plot, tagline if they are in title_basics_trim or another table
    $sqlMovie = "SELECT t.*, r.averageRating as rating, r.numVotes as votes /*, plot_table.plot, tagline_table.tagline */
                 FROM title_basics_trim t
                 LEFT JOIN title_ratings_trim r ON t.tconst = r.tconst
                 /* LEFT JOIN plot_table ON t.tconst = plot_table.tconst ... */
                 WHERE t.tconst = :movieId";
    $stmtMovie = $db->prepare($sqlMovie);
    $stmtMovie->bindParam(':movieId', $movieId, PDO::PARAM_STR);
    $stmtMovie->execute();
    $movieData = $stmtMovie->fetch(PDO::FETCH_ASSOC);

    if (!$movieData) { /* ... handle 404 movie not found ... */ exit; }

    // --- Integrate PosterFetcher ---
    // If image_url is NULL or empty in the database, try to fetch it
    if (empty($movieData['image_url'])) {
        error_log("API Movie Detail: image_url for {$movieId} is empty in DB. Attempting to fetch with PosterFetcher.");
        $fetchedPosterUrl = PosterFetcher::fetchAndSaveImageUrl($movieId, $db); // $movieId is the tconst
        if ($fetchedPosterUrl) {
            $movieData['image_url'] = $fetchedPosterUrl; // Update movieData for the current response
            error_log("API Movie Detail: Successfully fetched and updated image_url for {$movieId} to: {$fetchedPosterUrl}");
        } else {
            error_log("API Movie Detail: PosterFetcher failed to get image_url for {$movieId}.");
            // movieData['image_url'] will remain null or empty, frontend will use placeholder
        }
    }
    // --- End PosterFetcher Integration ---


    // 2. Fetch Principals (cast, directors, writers)
    //    Join with name_basics_trim to get names and potentially their image URLs
    $sqlPrincipals = "SELECT tp.nconst, tp.category, tp.job, tp.characters, 
                             nb.primaryName as name, nb.image_url as imageUrl 
                      FROM title_principals_trim tp
                      JOIN name_basics_trim nb ON tp.nconst = nb.nconst
                      WHERE tp.tconst = :movieId
                      ORDER BY tp.ordering"; // Or some other relevant order
    $stmtPrincipals = $db->prepare($sqlPrincipals);
    $stmtPrincipals->bindParam(':movieId', $movieId, PDO::PARAM_STR);
    $stmtPrincipals->execute();
    $principals = $stmtPrincipals->fetchAll(PDO::FETCH_ASSOC);

    // --- Iterate through principals to fetch their images if missing ---
    if ($principals) {
        foreach ($principals as $key => $principal) {
            if (empty($principal['personImageUrl']) && !empty($principal['nconst'])) {
                error_log("API Movie Detail: personImageUrl for {$principal['nconst']} ({$principal['name']}) is empty. Attempting to fetch.");
                $fetchedPersonImgUrl = PersonImageScraper::fetchAndSavePersonImageUrl($principal['nconst'], $db);
                if ($fetchedPersonImgUrl) {
                    $principals[$key]['personImageUrl'] = $fetchedPersonImgUrl; // Update the array
                    error_log("API Movie Detail: Successfully fetched personImageUrl for {$principal['nconst']}: {$fetchedPersonImgUrl}");
                } else {
                     error_log("API Movie Detail: PersonImageScraper failed for {$principal['nconst']}.");
                }
            }
        }
    }
    // --- End person image fetching ---

    // Add principals to the movie data
    $movieData['principals'] = $principals ?: [];

    // If you have separate tables/columns for director_names, writer_names, you might fetch those too,
    // but the principals array is more comprehensive.

    // If genres is a comma-separated string, convert to array for consistency (optional)
    if (isset($movieData['genres']) && is_string($movieData['genres'])) {
        $movieData['genres'] = array_map('trim', explode(',', $movieData['genres']));
    }


    // You might want to map $movieData to a Title object for consistency
    // Or just return the associative array. For this example, let's assume direct return.
    // If using a Title object, ensure its jsonSerialize includes plot, tagline, and principals.
     // For debugging, remove in production
    echo json_encode(['movie' => $movieData]); // Or just json_encode($movieData)

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    error_log("API Movie Detail Error for ID $movieId: " . $e->getMessage());
}
?>