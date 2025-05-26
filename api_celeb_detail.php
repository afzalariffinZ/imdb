<?php
// api_celeb_detail.php
header('Access-Control-Allow-Origin: http://localhost:5173'); // YOUR REACT DEV SERVER URL
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0); // Handle preflight CORS request
}
header('Content-Type: application/json');
// ... (CORS headers, includes for database.php, Name.php, PersonImageScraper.php) ...

require_once 'database.php'; // For openConnection() and your data fetching functions
require_once 'objects/Title.php'; // If you construct a Title object
require_once 'objects/Name.php';  // If you want to represent principals as Name objects
require_once 'PosterFetcher.php';

$nconst = $_GET['nconst'] ?? null;

if (!$nconst) {
    http_response_code(400);
    echo json_encode(['error' => 'Celebrity nconst is required.']);
    exit;
}

try {
    $db = openConnection();

    // 1. Fetch Basic Celeb Info from name_basics_trim
    $sqlCeleb = "SELECT nconst, primaryName, birthYear, deathYear, primaryProfession, image_url 
                 FROM name_basics_trim 
                 WHERE nconst = :nconst";
    $stmtCeleb = $db->prepare($sqlCeleb);
    $stmtCeleb->bindParam(':nconst', $nconst, PDO::PARAM_STR);
    $stmtCeleb->execute();
    $celebData = $stmtCeleb->fetch(PDO::FETCH_ASSOC);

    if (!$celebData) {
        http_response_code(404);
        echo json_encode(['error' => 'Celebrity not found.']);
        exit;
    }

    // 1a. Fetch/Scrape image if missing
    if (empty($celebData['image_url'])) {
        if (class_exists('PersonImageScraper')) { // Ensure class is loaded
            $fetchedImgUrl = PersonImageScraper::fetchAndSavePersonImageUrl($nconst, $db);
            if ($fetchedImgUrl) {
                $celebData['image_url'] = $fetchedImgUrl;
            }
        }
    }
    
    // 1b. Resolve Professions (if primaryProfession stores pconsts)
    // Assuming Name class and its methods are available and work as previously discussed
    // This step might be simpler if primaryProfession already stores formatted names.
    $tempNameObj = new Name(); // Temporary object to use resolver
    $tempNameObj->primaryProfession = $celebData['primaryProfession'];
    $tempNameObj->resolveProfessions($db); // This populates $tempNameObj->resolvedProfessions
    $celebData['professions'] = $tempNameObj->resolvedProfessions; // Add to our $celebData array
    unset($celebData['primaryProfession']); // Remove raw pconsts if not needed in final JSON

    // 2. Fetch Associated Titles (Known For) - potentially more than the list view
    $sqlTitles = "SELECT tb.tconst, tb.primaryTitle, tb.startYear, tb.titleType, tp.characters
                  FROM known_for_titles_trim kft
                  JOIN title_basics_trim tb ON kft.tconst = tb.tconst
                  LEFT JOIN title_principals_trim tp ON tp.tconst = tb.tconst AND tp.nconst = kft.nconst -- To get characters
                  LEFT JOIN title_ratings_trim tr ON tb.tconst = tr.tconst
                  WHERE kft.nconst = :nconst
                  ORDER BY IFNULL(tr.numVotes, 0) DESC, tb.startYear DESC
                  LIMIT 10"; // Fetch more for detail page, e.g., top 10
    $stmtTitles = $db->prepare($sqlTitles);
    $stmtTitles->bindParam(':nconst', $nconst, PDO::PARAM_STR);
    $stmtTitles->execute();
    $titles = $stmtTitles->fetchAll(PDO::FETCH_ASSOC);
    $celebData['titlesAssociated'] = $titles ?: [];

    // 3. TODO: Fetch Biography if you have it stored
    // $celebData['biography'] = "A sample biography..."; 


    echo json_encode(['celeb' => $celebData], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    error_log("API Celeb Detail Error for NCONST $nconst: " . $e->getMessage());
}
?>