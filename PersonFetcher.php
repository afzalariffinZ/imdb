<?php
// PersonImageScraper.php (or part of a general Scraper.php)

// require_once __DIR__ . '/connection.php'; // If needed for DB updates directly here

class PersonImageScraper
{
    /**
     * Fetches image URL from IMDb person page, saves it to the database, and returns the URL.
     *
     * @param string $nconst The IMDb Name ID (e.g., nm0000151)
     * @param PDO $db The database connection object
     * @return string|null The image URL if found and saved, or null on failure.
     */
    public static function fetchAndSavePersonImageUrl(string $nconst, PDO $db): ?string
    {
        if (empty($nconst)) {
            return null;
        }

        // --- STEP 1: Check database first ---
        try {
            $stmt = $db->prepare("SELECT image_url FROM name_basics_trim WHERE nconst = :nconst");
            $stmt->bindParam(':nconst', $nconst, PDO::PARAM_STR);
            $stmt->execute();
            $existingUrl = $stmt->fetchColumn(); // Fetches the first column of the first row

            if ($existingUrl !== false && !empty($existingUrl)) {
                // Found a non-empty URL in the database
                error_log("PersonImageScraper: Found existing image_url for $nconst in DB: $existingUrl");
                return $existingUrl; // Return it, no need to scrape
            }
            // If $existingUrl is false (no row) or empty, proceed to scrape.
            if ($existingUrl === '') {
                error_log("PersonImageScraper: Existing image_url for $nconst is an empty string. Will attempt to re-scrape.");
                // Or, if an empty string means "no image available, don't try again", you could return null here.
                // For now, let's assume an empty string means we can try again.
            }

        } catch (PDOException $e) {
            error_log("PersonImageScraper: DB Error checking existing image_url for $nconst: " . $e->getMessage());
            // Proceed to scrape if DB check fails, or handle error differently
        }
        // --- END STEP 1 ---

        // --- STEP 2: Scrape if not found or if DB check failed ---
        error_log("PersonImageScraper: No valid existing image_url for $nconst in DB, proceeding to scrape.");
        $scrapedImageUrl = self::scrapePersonImageUrlFromIMDb($nconst); // This is your existing scraping logic

        if ($scrapedImageUrl) {
            if (self::updatePersonImageUrlInDb($nconst, $scrapedImageUrl, $db)) {
                return $scrapedImageUrl;
            }
            error_log("PersonImageScraper: Scraped image for $nconst ($scrapedImageUrl) but failed to update DB.");
            return $scrapedImageUrl; // Return scraped URL even if DB update fails, so it's used for current request
        }
        
        // Optional: If scraping fails, you might want to update the DB with an empty string
        // to prevent repeated scrape attempts for known unfindable images.
        // else {
        //     self::updatePersonImageUrlInDb($nconst, '', $db); // Mark as "tried, not found"
        // }

        return null; // Scraping failed
    }

    /**
     * Scrapes IMDb for the person's primary image URL.
     */
    private static function scrapePersonImageUrlFromIMDb(string $nconst): ?string
    {
        // ... (initial checks, $imdbUrl, $options, $context - no change) ...
        if (empty($nconst)) {
            return null;
        }

        $imdbUrl = "https://www.imdb.com/name/{$nconst}/";
        $imageUrl = null;

        $options = [ /* ... your options ... */ ];
        $context = stream_context_create($options);

        error_log("PersonImageScraper: Scraping $imdbUrl for $nconst");
        $html = file_get_contents($imdbUrl, false, $context); // Consider removing @ for better error logs during debug

        if ($html === false) {
            $error = error_get_last();
            error_log("PersonImageScraper: file_get_contents FAILED for $nconst ($imdbUrl). Error details: " . print_r($error, true));
            return null;
        }
        // error_log("PersonImageScraper: Successfully fetched HTML for $nconst. Length: " . strlen($html));


        $doc = new DOMDocument();
        @$doc->loadHTML($html);
        $xpath = new DOMXPath($doc);

        $metaQuery = $xpath->query("//meta[@property='og:image']/@content");
        if ($metaQuery && $metaQuery->length > 0) {
            $rawImageUrl = $metaQuery->item(0)->nodeValue;
            error_log("PersonImageScraper: Raw og:image content for $nconst: \"$rawImageUrl\"");

            // --- CLEAN THE EXTRACTED URL ---
            $cleanedImageUrl = trim($rawImageUrl); // Remove leading/trailing whitespace

            // Remove trailing quote if it exists
            if (substr($cleanedImageUrl, -1) === '"') {
                $cleanedImageUrl = substr($cleanedImageUrl, 0, -1);
                error_log("PersonImageScraper: Removed trailing quote. Cleaned URL: \"$cleanedImageUrl\"");
            }
            // Remove leading quote if it exists (less common for content attribute but good to check)
            if (substr($cleanedImageUrl, 0, 1) === '"') {
                $cleanedImageUrl = substr($cleanedImageUrl, 1);
                 error_log("PersonImageScraper: Removed leading quote. Cleaned URL: \"$cleanedImageUrl\"");
            }
            
            // You might also want to ensure it's a valid URL structure
            if (filter_var($cleanedImageUrl, FILTER_VALIDATE_URL)) {
                error_log("PersonImageScraper: Final cleaned og:image for $nconst: \"$cleanedImageUrl\"");
                return $cleanedImageUrl;
            } else {
                error_log("PersonImageScraper: Cleaned URL is not valid for $nconst: \"$cleanedImageUrl\". Raw was: \"$rawImageUrl\"");
                return null; // Or return rawImageUrl if you prefer to attempt saving it anyway
            }
        }
        
        error_log("PersonImageScraper: No og:image found for $nconst on page $imdbUrl.");
        return null;
    }

    /**
     * Updates the image URL in the name_basics_trim table.
     */
    private static function updatePersonImageUrlInDb(string $nconst, string $imageUrl, PDO $db): bool
    {
        if (empty($nconst) || empty($imageUrl)) {
            return false;
        }
        try {
            $stmt = $db->prepare("UPDATE name_basics_trim SET image_url = :image_url WHERE nconst = :nconst");
            $stmt->bindParam(':image_url', $imageUrl, PDO::PARAM_STR);
            $stmt->bindParam(':nconst', $nconst, PDO::PARAM_STR);
            $stmt->execute();
            error_log("PersonImageScraper: Updated image_url for $nconst in DB. Rows affected: " . $stmt->rowCount());
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("PersonImageScraper: DB Error updating image_url for $nconst: " . $e->getMessage());
            return false;
        }
    }
}
?>