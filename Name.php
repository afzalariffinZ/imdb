<?php

class Name {
    public string $nconst;
    public string $primaryName;
    public ?int $birthYear = null;     // Can be NULL in the database
    public ?int $deathYear = null;     // Can be NULL
    public ?string $primaryProfession = null; // Comma-separated string, can be NULL
    public ?string $knownForTitles = null;   // Comma-separated tconsts, can be NULL
    public ?string $image_url = null;     // This column you would add manually after scraping

    // Constructor can be empty if using FETCH_PROPS_LATE with fetchAll/fetchObject
    public function __construct() {
        // PDO::FETCH_PROPS_LATE means properties are assigned *after* constructor runs.
        // If you need to initialize based on these, you'd do it here,
        // but usually it's fine for simple data objects.
    }

    // --- Getters ---
    public function getNconst(): string { return $this->nconst; }
    public function getPrimaryName(): string { return $this->primaryName; }

    public function getBirthYear(): ?int {
        // The database might store \N as NULL, which PDO often converts to null.
        // Or it might be an empty string if not properly cleaned.
        // Casting to int handles '1899' correctly.
        // If it's truly NULL from DB, $this->birthYear will be null.
        return $this->birthYear ? (int)$this->birthYear : null;
    }

    public function getDeathYear(): ?int {
        return $this->deathYear ? (int)$this->deathYear : null;
    }

    public function getPrimaryProfessionsArray(): array {
        if (empty($this->primaryProfession) || $this->primaryProfession === '\N') {
            return [];
        }
        // Capitalize first letter of each profession for display
        return array_map('ucfirst', explode(',', $this->primaryProfession));
    }

    public function getKnownForTitlesTconstArray(): array {
        if (empty($this->knownForTitles) || $this->knownForTitles === '\N') {
            return [];
        }
        return explode(',', $this->knownForTitles);
    }

    public function getImageUrl(): string { // Always return a string (placeholder if null)
        return $this->image_url ?: 'https://via.placeholder.com/150x225.png?text=No+Image';
    }

    // --- HTML Rendering Methods (Examples - adapt to your desired card/detail HTML) ---

    /**
     * Generates HTML for a celebrity card in a list view.
     */
    public function toHtmlCard(): string {
        $name = htmlspecialchars($this->getPrimaryName());
        $imageUrl = $this->getImageUrl(); // Already handles placeholder
        $professions = htmlspecialchars(implode(', ', array_slice($this->getPrimaryProfessionsArray(), 0, 2))); // Show first 2

        $html = '<div class="celeb-card-img-wrapper">';
        $html .= '<img src="' . $imageUrl . '" class="card-img-top celeb-image" alt="' . $name . '">';
        $html .= '</div>';
        $html .= '<div class="card-body">';
        $html .= '<h5 class="card-title celeb-name" title="' . $name . '">' . $name . '</h5>';
        if (!empty($professions)) {
            $html .= '<p class="celeb-professions">' . $professions . '</p>';
        }
        // Optionally show a snippet of known for titles
        /*
        $knownForTconsts = $this->getKnownForTitlesTconstArray();
        if (!empty($knownForTconsts)) {
            // In a real app, you'd fetch the title names for these tconsts
            // For simplicity here, we just show the count or first few IDs
            $html .= '<p class="celeb-known-for small text-muted">Known for ' . count($knownForTconsts) . ' titles</p>';
        }
        */
        $html .= '</div>';
        return $html;
    }

    /**
     * Generates HTML for the detailed view of a celebrity.
     * This would be more extensive and likely involve fetching related data (filmography details).
     */
    public function toHtmlDetail(): string {
        $name = htmlspecialchars($this->getPrimaryName());
        $imageUrl = $this->getImageUrl();
        $professions = htmlspecialchars(implode(', ', $this->getPrimaryProfessionsArray()));
        $birthYear = $this->getBirthYear() ?: 'N/A';
        $deathYearHtml = $this->getDeathYear() ? ' | Died: ' . $this->getDeathYear() : '';

        $html = '<div class="celeb-detail-header">';
        $html .= '<div class="celeb-detail-image-wrapper">';
        $html .= '<img src="' . $imageUrl . '" class="celeb-profile-img" alt="Profile image of ' . $name . '">';
        $html .= '</div>';
        $html .= '<div class="celeb-detail-name-prof">';
        $html .= '<h1 class="celeb-name">' . $name . '</h1>';
        if (!empty($professions)) {
            $html .= '<p class="celeb-professions-detail">' . $professions . '</p>';
        }
        $html .= '<p class="small text-muted">Born: ' . $birthYear . $deathYearHtml . '</p>';
        $html .= '</div></div>'; // End celeb-detail-name-prof and celeb-detail-header

        // Placeholder for Biography and Filmography
        // $html .= '<div class="celeb-detail-bio"><h4>Biography</h4><p>Biography details would go here if available...</p></div>';

        $html .= '<div class="celeb-detail-filmography"><h4>Known For</h4>';
        $known_for_tconsts = $this->getKnownForTitlesTconstArray();
        if (!empty($known_for_tconsts)) {
            $html .= "<ul>";
            // Fetching full title details here for each knownFor can be slow.
            // Consider just listing tconsts or pre-fetching a limited number of title names.
            // For this example, we'll just list the first few tconsts.
            // In a real app, you'd link to your titles.php page.
            foreach (array_slice($known_for_tconsts, 0, 5) as $tconst) { // Show up to 5
                 // Ideally, fetch the title name for $tconst
                 // $title_obj = getTitleById($tconst); // This could be slow in a loop
                 // $title_name = $title_obj ? htmlspecialchars($title_obj->getPrimaryTitle()) : htmlspecialchars($tconst);
                $title_name = htmlspecialchars($tconst); // Placeholder
                $html .= '<li><a href="titles.php?id=' . htmlspecialchars($tconst) . '">' . $title_name . '</a></li>';
            }
            if(count($known_for_tconsts) > 5) $html .= "<li>...and more.</li>";
            $html .= "</ul>";
        } else {
            $html .= "<p>No specific filmography information available directly from this record.</p>";
        }
        $html .= '</div>'; // End celeb-detail-filmography

        return $html;
    }
}