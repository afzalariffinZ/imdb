<?php

// Function to scrape IMDb for the poster image URL
function scrapeImageUrl($tconst) {
    if (empty($tconst)) {
        return null;
    }

    $imdbUrl = "https://www.imdb.com/title/{$tconst}/";
    $imageUrl = null;

    // Set up stream context to fake a user agent (IMDb might block default PHP User-Agent)
    $options = [
        'http' => [
            'method' => "GET",
            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36\r\n"
        ]
    ];
    $context = stream_context_create($options);

    // error_log("Scraping: " . $imdbUrl); // For debugging

    $html = @file_get_contents($imdbUrl, false, $context); // Use @ to suppress warnings if page not found

    if ($html === false) {
        // error_log("Failed to fetch HTML for $tconst from $imdbUrl");
        return null;
    }

    $doc = new DOMDocument();
    @$doc->loadHTML($html); // Suppress warnings from malformed HTML
    $xpath = new DOMXPath($doc);

    // Try to find the og:image meta tag (usually the most reliable)
    $metaQuery = $xpath->query("//meta[@property='og:image']/@content");
    if ($metaQuery && $metaQuery->length > 0) {
        $imageUrl = $metaQuery->item(0)->nodeValue;
        // error_log("Found og:image for $tconst: " . $imageUrl);
        return $imageUrl;
    }

    // Fallback: Try to find the main poster image via a common class structure (more fragile)
    // Example: IMDb poster is often in <div class="ipc-poster"> <img class="ipc-image" src="...">
    // This query might need adjustment if IMDb changes its HTML structure
    // $imgQuery = $xpath->query('//div[contains(@class, "ipc-poster")]//img[contains(@class, "ipc-image")]/@src');
    // if ($imgQuery && $imgQuery->length > 0) {
    //     $imageUrl = $imgQuery->item(0)->nodeValue;
    //     error_log("Found img src for $tconst: " . $imageUrl);
    //     return $imageUrl;
    // }
    // error_log("No image found for $tconst on page.");
    return null; // Image not found
}

// Function to update the image URL in the database
function updateMovieImageUrlInDb($tconst, $imageUrl, $db) {
    if (empty($tconst) || empty($imageUrl)) {
        return false;
    }
    try {
        $stmt = $db->prepare("UPDATE title_basics_trim SET image_url = :image_url WHERE tconst = :tconst");
        $stmt->bindParam(':image_url', $imageUrl, PDO::PARAM_STR);
        $stmt->bindParam(':tconst', $tconst, PDO::PARAM_STR);
        $stmt->execute();
        // error_log("Updated image_url for $tconst in DB.");
        return true;
    } catch (PDOException $e) {
        // error_log("DB Error updating image_url for $tconst: " . $e->getMessage());
        return false;
    }
}

// ... (rest of your database.php functions like getDB, getMovie, getMovies, etc.)
?>