<?php
// PosterFetcher.php
// ... (require_once __DIR__ . '/connection.php'; etc. at the top) ...

class PosterFetcher
{
    const DEFAULT_PLACEHOLDER_IMAGE = './images/movie_not_found.jpg';

    public static function fetchAndSaveImageUrl(string $tconst, PDO $db): ?string
    {
        // Log when this specific function is called
        // error_log("PosterFetcher: fetchAndSaveImageUrl called for $tconst");

        $imageUrl = self::scrapeImageUrlFromIMDbViaCurl($tconst); // Call the cURL version

        if ($imageUrl) {
            if (self::updateImageUrlInDb($tconst, $imageUrl, $db)) {
                // error_log("PosterFetcher: Successfully scraped and saved URL for $tconst: $imageUrl");
                return $imageUrl;
            }
            error_log("PosterFetcher: Scraped image for $tconst ($imageUrl) but FAILED to update DB.");
        } else {
            // error_log("PosterFetcher: Scraping failed for $tconst, no URL returned from scrapeImageUrlFromIMDbViaCurl.");
        }
        return null;
    }

    // Renamed to clearly indicate it's using cURL
    private static function scrapeImageUrlFromIMDbViaCurl(string $tconst): ?string
    {
        if (empty($tconst)) {
            return null;
        }
        $imdbUrl = "https://www.imdb.com/title/{$tconst}/";
        $imageUrl = null;

        // error_log("PosterFetcher (cURL): Attempting to fetch $imdbUrl for $tconst");

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $imdbUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);     // Return response as string
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);     // Follow redirects
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.4844.51 Safari/537.36"); // Updated User-Agent
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept-Language: en-US,en;q=0.9"]);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);     // Timeout for establishing connection (seconds)
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);          // Total timeout for the entire cURL operation (seconds) - must be less than PHP's max_execution_time

        // SSL Verification - IMPORTANT for production, ensure openssl.cafile is set in php.ini
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Usually true by default
        // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);   // Usually 2 by default
        // If you still have SSL issues after setting php.ini openssl.cafile, you might try:
        // curl_setopt($ch, CURLOPT_CAINFO, "C:/path/to/your/cacert.pem"); // Absolute path to your CA bundle

        // For debugging SSL in cURL, uncomment these (very verbose):
        // curl_setopt($ch, CURLOPT_VERBOSE, true);
        // $verbose = fopen('php://temp', 'w+');
        // curl_setopt($ch, CURLOPT_STDERR, $verbose);


        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErrorNum = curl_errno($ch);
        $curlErrorMsg = curl_error($ch);

        // if (isset($verbose)) {
        //     rewind($verbose);
        //     $verboseLog = stream_get_contents($verbose);
        //     error_log("PosterFetcher (cURL) Verbose Log for $tconst: " . $verboseLog);
        //     fclose($verbose);
        // }

        curl_close($ch);

        if ($curlErrorNum) {
            error_log("PosterFetcher (cURL) Error for $tconst ($imdbUrl): [$curlErrorNum] " . $curlErrorMsg);
            return null;
        }

        if ($httpCode != 200 || $html === false || empty(trim($html))) {
            error_log("PosterFetcher (cURL): Failed to fetch valid HTML for $tconst. HTTP Code: $httpCode. URL: $imdbUrl. HTML empty: ".(empty(trim($html))?'yes':'no'));
            return null;
        }

        // error_log("PosterFetcher (cURL): Successfully fetched HTML for $tconst (HTTP $httpCode). Length: " . strlen($html));

        $doc = new DOMDocument();
        @$doc->loadHTML('<?xml encoding="utf-8" ?>' . $html); // Add XML encoding to help DOM parser
        $xpath = new DOMXPath($doc);

        $metaQuery = $xpath->query("//meta[@property='og:image']/@content");
        if ($metaQuery && $metaQuery->length > 0) {
            $imageUrl = $metaQuery->item(0)->nodeValue;
            // error_log("PosterFetcher (cURL): Found og:image for $tconst: $imageUrl");
            return $imageUrl;
        }
        error_log("PosterFetcher (cURL): No og:image found for $tconst on page $imdbUrl.");
        return null;
    }


    private static function updateImageUrlInDb(string $tconst, string $imageUrl, PDO $db): bool
    {
        if (empty($tconst) || empty($imageUrl)) {
            return false;
        }
        try {
            $stmt = $db->prepare("UPDATE title_basics_trim SET image_url = :image_url WHERE tconst = :tconst");
            $stmt->bindParam(':image_url', $imageUrl, PDO::PARAM_STR);
            $stmt->bindParam(':tconst', $tconst, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("PosterFetcher: DB Error updating image_url for $tconst: " . $e->getMessage());
            return false;
        }
    }
}
?>