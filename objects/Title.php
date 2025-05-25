<?php
// Title.php

// Ensure PosterFetcher is loaded. Adjust path if necessary.
// This line should ideally be in a central bootstrap file or at the top of scripts that use Title objects.
// For now, putting it here for simplicity, but be mindful of multiple inclusions.
// If database.php also includes it, ensure it's require_once.
require_once __DIR__ . '/../PosterFetcher.php'; // Adjust path as needed
// We also need openConnection from database.php for the PosterFetcher to work within Title::getImageUrl
// This creates a bit of a circular dependency if PosterFetcher also needs connection.php for its own openConnection.
// A better approach would be to pass the DB connection to PosterFetcher, or have PosterFetcher use its own.
// For now, let's assume PosterFetcher has its own connection setup via connection.php as in Step 1.

class Title implements JsonSerializable
{
    // Properties are populated by PDO FETCH_CLASS
    protected $id; // This will map to tconst from your queries
    protected $image_url; // This will be populated from the 'image_url' column in DB
    protected $title_type;
    protected $primary_title;
    protected $original_title;
    protected $is_adult;
    protected $start_year;
    protected $end_year;
    protected $runtime_minutes;
    protected $rating;
    protected $votes;
    protected $genres;
    protected $directors_count;
    protected $principals_count;
    protected $writers_count;

    // A static PDO instance for the Title class to use for fetching images if not already available
    private static $db_connection = null;


    function __construct()
    {
        // This is taken care of by the PDO data mapping.
        // Properties are set after constructor if PDO::FETCH_PROPS_LATE is used.
    }

    // Helper to get a DB connection, used internally by getImageUrl
    private static function getDb() {
        if (self::$db_connection === null) {
            // This assumes openConnection() is available globally or via an include
            // and that connection.php (which defines CONNECTION_STRING etc.) is included.
            // This is a simplified approach. In a larger app, you'd use dependency injection.
            // Ensure connection.php is included somewhere before this can be called.
            // It's better if openConnection() is defined in connection.php and included globally.
            if (!function_exists('openConnection')) {
                // Attempt to include it if not found, common in script-based setups
                // Adjust path to your database.php or connection.php where openConnection is defined
                $connectionFile = __DIR__ . '/database.php'; // Or connection.php
                if (file_exists($connectionFile)) {
                    require_once $connectionFile;
                } else {
                    error_log("Title class: openConnection() not found and connection file missing.");
                    return null; // Cannot proceed
                }
            }
            try {
                 self::$db_connection = openConnection();
            } catch (PDOException $e) {
                error_log("Title class: Failed to get DB connection for image fetching: " . $e->getMessage());
                return null;
            }
        }
        return self::$db_connection;
    }


    public function jsonSerialize() : mixed
    {
        return [
            'id' => $this->getId(),
            'image_url' => $this->getImageUrl(), // Will trigger fetch if needed
            'title_type' => $this->getTitleType(),
            'primary_title' => $this->getPrimaryTitle(),
            'original_title' => $this->getOriginalTitle(),
            'is_adult' => $this->getIsAdult(),
            'start_year' => $this->getStartYear(),
            'end_year' => $this->getEndYear(),
            'runtime_minutes' => $this->getRuntimeMinutes(),
            'rating' => $this->getRating(),
            'votes' => $this->getVotes(),
            'genres' => $this->getGenres(), // Make sure this is a string or simple array
            'directors_count' => $this->getDirectorsCount(),
            'principals_count' => $this->getPrincipalsCount(),
            'writers_count' => $this->getWritersCount(),
        ];
    }

    /**
     * Gets the image URL.
     * If not already stored in $this->image_url (from DB),
     * it attempts to fetch from IMDb, save to DB, and updates $this->image_url.
     * Returns a placeholder if no image can be found/fetched.
     */
    public function getImageUrl()
    {
        // If image_url is already populated (from DB or previous fetch in this request)
        if (!empty($this->image_url)) {
            return $this->image_url;
        }

        // If no image_url and we have an ID (tconst), try to fetch it
        if (!empty($this->id)) {
            $db = self::getDb(); // Get a DB connection
            if ($db) {
                $fetchedUrl = PosterFetcher::fetchAndSaveImageUrl($this->id, $db);
                if ($fetchedUrl) {
                    $this->image_url = $fetchedUrl; // Update current object's property
                    return $this->image_url;
                }
            } else {
                error_log("Title ({$this->id}): Could not get DB connection to fetch image.");
            }
        }
        
        // Fallback to placeholder if not found in DB, fetch failed, or no ID
        return PosterFetcher::DEFAULT_PLACEHOLDER_IMAGE;
    }

    // This function is for the card view in lists
    public function toHtml()
    {
        $imageUrl = htmlspecialchars($this->getImageUrl()); // Trigger fetch if needed
        $altText = htmlspecialchars($this->getPrimaryTitle() ?: $this->getOriginalTitle()) . " Poster";
        $titleDisplay = htmlspecialchars($this->getPrimaryTitle() ?: $this->getOriginalTitle());
        $year = htmlspecialchars($this->getStartYear());
        $rating = $this->getRating() ? htmlspecialchars($this->getRating()) . '/10' : 'N/A';
        $runtime = $this->getRuntimeMinutes() ? htmlspecialchars($this->getRuntimeMinutes()) . ' min.' : 'N/A';

        // Card structure similar to your titles.php, but using Bootstrap card classes properly
        return '
            <img src="' . $imageUrl . '" class="card-img-top title-poster-img" alt="' . $altText . '">
            <div class="card-body">
                <h5 class="card-title mb-1">' . $titleDisplay . ' (' . $year . ')</h5>
                <p class="card-text mb-1"><small class="text-muted">Rating: ' . $rating . '</small></p>
                <p class="card-text mb-0"><small class="text-muted">Runtime: ' . $runtime . '</small></p>
            </div>
            <div class="card-footer text-center">
                 <a href="titles.php?id=' . htmlspecialchars($this->getId()) . '" class="btn btn-sm btn-outline-light">Details</a>
            </div>';
    }

    // This function is for the detailed view page
    public function toDetailedHtml()
    {
        $imageUrl = htmlspecialchars($this->getImageUrl()); // Trigger fetch if needed
        $altText = htmlspecialchars($this->getPrimaryTitle()) . " Poster";
        $primaryTitle = htmlspecialchars($this->getPrimaryTitle());
        $originalTitle = htmlspecialchars($this->getOriginalTitle());
        $year = htmlspecialchars($this->getStartYear());
        $genres = htmlspecialchars(is_array($this->getGenres()) ? implode(', ', $this->getGenres()) : $this->getGenres());
        $runtime = $this->getRuntimeMinutes() ? htmlspecialchars($this->getRuntimeMinutes()) . ' minutes' : 'N/A';
        $rating = $this->getRating() ? htmlspecialchars($this->getRating()) . '/10 (' . htmlspecialchars($this->getVotes()) . ' votes)' : 'Not Rated';
        $isAdult = $this->getIsAdult() ? 'Yes' : 'No';

        $html = '<div class="row g-4">'; // Bootstrap row with gutter
        $html .= '  <div class="col-md-4 text-center text-md-start">';
        $html .= '    <img src="' . $imageUrl . '" class="img-fluid rounded shadow-sm title-detail-poster" alt="' . $altText . '">';
        $html .= '  </div>';
        $html .= '  <div class="col-md-8 title-detail-info">';
        // Title is already in the H2 above this content, but if original is different:
        if ($this->getPrimaryTitle() !== $this->getOriginalTitle() && !empty($this->getOriginalTitle())) {
            // This is already handled by titles.php, but keeping it here if toDetailedHtml becomes more standalone
            // $html .= '    <p class="text-muted small mb-2">(Original Title: ' . $originalTitle . ')</p>';
        }
        $html .= '    <p><strong>Year:</strong> ' . $year . '</p>';
        if ($this->getEndYear() && $this->getEndYear() != $this->getStartYear()) {
            $html .= '    <p><strong>End Year:</strong> ' . htmlspecialchars($this->getEndYear()) . '</p>';
        }
        $html .= '    <p><strong>Type:</strong> ' . htmlspecialchars(ucfirst($this->getTitleType())) . '</p>';
        $html .= '    <p><strong>Genres:</strong> ' . $genres . '</p>';
        $html .= '    <p><strong>Runtime:</strong> ' . $runtime . '</p>';
        $html .= '    <p><strong>Rating:</strong> ' . $rating . '</p>';
        $html .= '    <p><strong>Adult:</strong> ' . $isAdult . '</p>';
        // You can add director/writer/principal counts here if desired
        $html .= '    <p><strong>Directors:</strong> ' . htmlspecialchars($this->getDirectorsCount()) . '</p>';
        $html .= '    <p><strong>Writers:</strong> ' . htmlspecialchars($this->getWritersCount()) . '</p>';
        $html .= '    <p><strong>Key Cast/Crew:</strong> ' . htmlspecialchars($this->getPrincipalsCount()) . '</p>';
        // Add more details here as needed (e.g., plot summary if you add it to DB/class)
        $html .= '  </div>';
        $html .= '</div>';
        return $html;
    }


    // Getters - ensure property names match what PDO populates
    public function getId() { return $this->id; } // tconst is aliased to 'id' in your queries
    // public function getImageUrl() { /* Defined above */ }
    public function getTitleType() { return $this->title_type; }
    public function getPrimaryTitle() { return $this->primary_title; }
    public function getOriginalTitle() { return $this->original_title; }
    public function getIsAdult() { return $this->is_adult; }
    public function getStartYear() { return $this->start_year; }
    public function getEndYear() { return $this->end_year; }
    public function getRuntimeMinutes() { return $this->runtime_minutes; }
    public function getRating() { return $this->rating; }
    public function getVotes() { return $this->votes; }
    public function getGenres() { return $this->genres; } // This should be a string from DB
    public function getDirectorsCount() { return $this->directors_count; }
    public function getPrincipalsCount() { return $this->principals_count; }
    public function getWritersCount() { return $this->writers_count; }
}
?>