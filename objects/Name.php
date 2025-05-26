<?php
// objects/Name.php
#[\AllowDynamicProperties]
class Name implements JsonSerializable {
    public $nconst;
    public $primaryName;
    public ?int $birthYear = null;     // Explicitly nullable int
    public ?int $deathYear = null;     // Explicitly nullable int
    public ?string $primaryProfession = null; // Raw pconsts string
    public ?string $imageUrl = null;      // Stored image URL

    public ?array $resolvedProfessions = []; // Array of actual profession strings
    public ?array $titlesAssociated = [];    // Array of associated title objects/data
    // public ?string $professionsText = null; // Alternative if setProfessionsData stores a string

    private static ?PDO $db_connection = null;

    public function __construct() {
        $this->resolvedProfessions = [];
        $this->titlesAssociated = [];
    }

    private static function getDb(): ?PDO { // Return type also nullable
        if (self::$db_connection === null) {
            if (!function_exists('openConnection')) {
                $dbFile = __DIR__ . '/../database.php'; // Adjust path
                if (file_exists($dbFile)) { require_once $dbFile; }
                else { error_log("Name class: openConnection() not found and database.php include failed."); return null; }
            }
            try {
                 self::$db_connection = openConnection();
            } catch (PDOException $e) {
                error_log("Name class: Failed to get DB connection: " . $e->getMessage());
                return null;
            }
        }
        return self::$db_connection;
    }

    public function getImageUrl(): ?string { // Return type also nullable
        if (!empty($this->imageUrl)) {
            return $this->imageUrl;
        }
        if (!empty($this->nconst)) {
            if (!class_exists('PersonImageScraper')) {
                $scraperFile = __DIR__ . '/../PersonImageScraper.php'; // Adjust path
                if (file_exists($scraperFile)) { require_once $scraperFile; }
                else { error_log("Name::getImageUrl - PersonImageScraper class not found."); return null; }
            }

            $db = self::getDb();
            if ($db && class_exists('PersonImageScraper')) {
                $fetchedUrl = PersonImageScraper::fetchAndSavePersonImageUrl($this->nconst, $db);
                if ($fetchedUrl) {
                    $this->imageUrl = $fetchedUrl;
                    return $this->imageUrl;
                }
            }
        }
        return null;
    }

    // For use with the CTE version of getNamesList that returns GROUP_CONCAT
    public function setProfessionsData(?string $commaSeparatedProfessions) {
        if ($commaSeparatedProfessions) {
            $this->resolvedProfessions = explode(',', $commaSeparatedProfessions);
        } else {
            $this->resolvedProfessions = [];
        }
    }

    // For use with the CTE version of getNamesList that returns JSON string
    public function setTitlesData(?string $jsonString) {
        if ($jsonString) {
            $decoded = json_decode($jsonString, true);
            $this->titlesAssociated = is_array($decoded) ? $decoded : [];
        } else {
            $this->titlesAssociated = [];
        }
    }
    
    // This is the method that had the deprecated parameter definition
    // In objects/Name.php
    public function resolveProfessions(?PDO $db = null): void { // $db parameter is no longer strictly needed for this logic
        $this->resolvedProfessions = []; // Default to empty

        if ($this->primaryProfession === null || trim($this->primaryProfession) === '' || $this->primaryProfession === '\N') {
            // error_log("Name::resolveProfessions - nconst {$this->nconst}: primaryProfession is null, empty, or '\\N'. Setting empty resolvedProfessions.");
            return; // No professions to resolve
        }

        // The primaryProfession field directly contains comma-separated profession names
        $professionsArray = array_filter(array_map('trim', explode(',', $this->primaryProfession)), function($value) {
            return $value !== '' && $value !== '\N';
        });

        $this->resolvedProfessions = $professionsArray;
        // error_log("Name::resolveProfessions - nconst {$this->nconst}: Resolved professions directly from string: " . implode(', ', $this->resolvedProfessions));
    }
    
    // This is the other method that had the deprecated parameter definition
    public function fetchAssociatedTitles(?PDO $db = null, int $limit = 3): void { // Explicitly nullable PDO
        if (empty($this->nconst)) {
            $this->titlesAssociated = [];
            return;
        }
        $dbToUse = $db ?? self::getDb();
        if (!$dbToUse) {
            error_log("Name::fetchAssociatedTitles - No DB connection for nconst {$this->nconst}");
            $this->titlesAssociated = []; // Ensure it's an array on failure
            return;
        }

        $sql = "SELECT tb.tconst, tb.primaryTitle, tb.startYear, tb.titleType
                FROM known_for_titles_trim kft
                JOIN title_basics_trim tb ON kft.tconst = tb.tconst
                LEFT JOIN title_ratings_trim tr ON tb.tconst = tr.tconst /* Assuming title_ratings_trim */
                WHERE kft.nconst = :nconst
                ORDER BY tb.startYear DESC, IFNULL(tr.numVotes, 0) DESC 
                LIMIT :limit";
        try {
            $stmt = $dbToUse->prepare($sql);
            $stmt->bindParam(':nconst', $this->nconst, PDO::PARAM_STR);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $this->titlesAssociated = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Name::fetchAssociatedTitles - Error for nconst {$this->nconst}: " . $e->getMessage());
            $this->titlesAssociated = [];
        }
    }

    public function jsonSerialize(): mixed {
        // Attempt to fetch image if not set
        if ($this->imageUrl === null && !empty($this->nconst)) {
            $this->getImageUrl();
        }
        // If using the CTE version of getNamesList, professionsData and titlesData are set directly.
        // If not, you might call resolveProfessions and fetchAssociatedTitles here.
        // For now, assuming getNamesList's loop calls the setters.

        return [
            'nconst' => $this->nconst,
            'primaryName' => $this->primaryName,
            'birthYear' => $this->birthYear,
            'deathYear' => $this->deathYear,
            'imageUrl' => $this->imageUrl, // This will be null if not found/fetched
            // 'rawPconsts' => $this->primaryProfession, // Optionally send raw data
            'professions' => $this->resolvedProfessions, // Populated by setProfessionsData or resolveProfessions
            'titlesAssociated' => $this->titlesAssociated, // Populated by setTitlesData or fetchAssociatedTitles
        ];
    }

    // --- Optional: Getters for direct access if needed ---
    public function getNconst() { return $this->nconst; }
    public function getPrimaryName() { return $this->primaryName; }
    // ... etc.
}
?>