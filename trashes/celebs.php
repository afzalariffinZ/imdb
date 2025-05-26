<?php
// Assume you have a Name class and corresponding DB functions
// require_once './objects/Name.php';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark"> <!-- Default theme, can be overridden by JS -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= isset($_GET["id"]) ? "Celebrity Details" : "Browse Celebrities" ?> - IMDb2</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="css/style.css" rel="stylesheet"> <!-- Global styles -->
    <link href="css/celebs.css" rel="stylesheet"> <!-- Celeb specific styles -->
</head>
<body>
<div class="background-overlay"></div> <!-- For animated background consistency -->

<?php
include_once 'navigation.php'; // Assumes navigation.php is theme-aware
include_once 'database.php';   // Must contain functions for fetching name_basics

// Data Fetching Logic for Celebs
$id = $_GET["id"] ?? null; // nconst
$offset = isset($_GET["offset"]) ? (int)$_GET["offset"] : 0;
$limit = isset($_GET["limit"]) ? (int)$_GET["limit"] : 16; // More celebs per page?
$search_name = $_GET["name"] ?? "";

$celeb_obj = null;
$celebs_list = [];
$count = 0;

if ($id) {
    $celeb_obj = getNameById($id); // NEW FUNCTION you need to create in database.php
} else {
    $count = getNamesCount($search_name);       // NEW FUNCTION
    $celebs_list = getNamesList($offset, $limit, $search_name); // NEW FUNCTION
}
?>

<main role="main" class="container celebs-page-container content-wrapper">

    <?php if (!$id): /* LIST VIEW */ ?>
        <h1 class="page-title-celebs">
            <?php if (!empty($search_name)): ?>
                Results for "<strong><?= htmlspecialchars($search_name) ?></strong>" (<?= $count ?> found)
            <?php else: ?>
                Browse Celebrities (<?= $count ?> total)
            <?php endif; ?>
        </h1>

        <div class="search-controls-celebs">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <form id="celebs-search-form">
                        <label for="celebs-search-input" class="form-label visually-hidden">Search Celebrities:</label>
                        <div class="input-group">
                            <input id="celebs-search-input" name="name" type="search" class="form-control form-control-lg" value="<?= htmlspecialchars($search_name) ?>" placeholder="Enter celebrity name...">
                            <button id="celebs-search-btn" class="btn btn-celeb-search btn-lg" type="submit">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php if (!empty($celebs_list)): ?>
            <div id="celebs-data" class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3 celebs-grid">
                <?php foreach ($celebs_list as $celeb_item): ?>
                    <div class="col">
                        <a class="celeb-card-link" href="celebs.php?id=<?= $celeb_item->getNconst() // Assuming method getNconst() ?>">
                            <div class="card">
                                <?php // Example of how $celeb_item->toHtmlCard() might render
                                    // This needs to be implemented in your Name/Celeb class
                                ?>
                                <div class="celeb-card-img-wrapper">
                                    <img src="<?= $celeb_item->getImageUrl() ?: 'https://via.placeholder.com/150x150.png?text=No+Image' ?>"
                                         class="card-img-top celeb-image" alt="<?= htmlspecialchars($celeb_item->getPrimaryName()) ?>">
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title celeb-name" title="<?= htmlspecialchars($celeb_item->getPrimaryName()) ?>">
                                        <?= htmlspecialchars($celeb_item->getPrimaryName()) ?>
                                    </h5>
                                    <p class="celeb-professions">
                                        <?= htmlspecialchars(implode(', ', $celeb_item->getPrimaryProfessionsArray())) ?>
                                    </p>
                                    <?php
                                        /* $knownForTitles = $celeb_item->getKnownForTitlesArray(); // Array of title names or IDs
                                        if (!empty($knownForTitles)): ?>
                                            <p class="celeb-known-for">
                                                Known for: <?= htmlspecialchars(implode(', ', array_slice($knownForTitles, 0, 2))) ?>
                                            </p>
                                    <?php endif; */?>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif (!empty($search_name)): ?>
            <div class="text-center alert alert-warning mt-4" role="alert">
                No celebrities found matching "<?= htmlspecialchars($search_name) ?>".
            </div>
        <?php else: ?>
             <div class="text-center alert alert-info mt-4" role="alert">
                No celebrities to display.
            </div>
        <?php endif; ?>

    <?php elseif ($celeb_obj): /* DETAIL VIEW */ ?>
        <div class="celeb-detail-container">
            <div class="celeb-detail-header">
                <div class="celeb-detail-image-wrapper">
                     <img src="<?= $celeb_obj->getImageUrl() ?: 'https://via.placeholder.com/150x225.png?text=No+Profile+Image' ?>"
                          class="celeb-profile-img" alt="Profile image of <?= htmlspecialchars($celeb_obj->getPrimaryName()) ?>">
                </div>
                <div class="celeb-detail-name-prof">
                    <h1 class="celeb-name"><?= htmlspecialchars($celeb_obj->getPrimaryName()) ?></h1>
                    <p class="celeb-professions-detail">
                        <?= htmlspecialchars(implode(', ', $celeb_obj->getPrimaryProfessionsArray())) ?>
                    </p>
                    <p class="small text-muted">
                        Born: <?= $celeb_obj->getBirthYear() ?: 'N/A' ?>
                        <?php if ($celeb_obj->getDeathYear()): ?>
                            | Died: <?= $celeb_obj->getDeathYear() ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <?php /*
            // Placeholder for Biography - you would need to fetch this if available
            $bio = $celeb_obj->getBiography(); // Hypothetical method
            if ($bio): ?>
            <div class="celeb-detail-bio">
                <h4>Biography</h4>
                <p><?= nl2br(htmlspecialchars($bio)) ?></p>
            </div>
            <?php endif; */ ?>

            <div class="celeb-detail-filmography">
                <h4>Known For</h4>
                <?php
                    $known_for_tconsts = $celeb_obj->getKnownForTitlesTconstArray(); // e.g., ['tt000001', 'tt000002']
                    if (!empty($known_for_tconsts)) {
                        echo "<ul>";
                        foreach ($known_for_tconsts as $tconst) {
                            $title = getTitleById($tconst); // Fetch title details using existing function
                            if ($title) {
                                echo '<li><a href="titles.php?id=' . htmlspecialchars($tconst) . '">' . htmlspecialchars($title->getPrimaryTitle()) . '</a> <span class="film-year">(' . $title->getStartYear() . ')</span></li>';
                                // You could add role info here if you fetch it from title_principals
                            }
                        }
                        echo "</ul>";
                    } else {
                        echo "<p>No specific filmography information available.</p>";
                    }
                ?>
            </div>
        </div>

    <?php else: /* ID provided but celeb not found */ ?>
        <h1 class="page-title-celebs">Celebrity Not Found</h1>
        <div class="alert alert-danger text-center" role="alert">
            The requested celebrity could not be found.
        </div>
        <div class="text-center">
            <a href="celebs.php" class="btn btn-primary" style="background-color: var(--celeb-primary-red); border-color: var(--celeb-primary-red);">Back to Celebrities List</a>
        </div>
    <?php endif; ?>


    <!-- Pagination -->
    <?php if (!$id && $count > 0): ?>
    <div class="d-flex justify-content-center pagination-controls">
        <a href="?name=<?= urlencode($search_name) ?>&offset=<?= max(0, $offset - $limit) ?>&limit=<?= $limit ?>"
           class="btn btn-secondary me-2 <?= ($offset <= 0) ? 'disabled' : '' ?>">
            <i class="fas fa-chevron-left"></i> Previous
        </a>
        <span class="align-self-center mx-3 text-muted">
            Page <?= floor($offset / $limit) + 1 ?> of <?= ceil($count / $limit) ?>
        </span>
        <a href="?name=<?= urlencode($search_name) ?>&offset=<?= $offset + $limit ?>&limit=<?= $limit ?>"
           class="btn btn-primary <?= (($offset + $limit) >= $count) ? 'disabled' : '' ?>"> <!-- Primary uses celeb theme -->
            Next <i class="fas fa-chevron-right"></i>
        </a>
    </div>
    <?php endif; ?>

</main>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function() {
        $('#celebs-search-form').on('submit', function(e) {
            e.preventDefault();
            const query = $('#celebs-search-input').val().trim();
            const url = new URL(window.location.pathname, window.location.origin);
            url.searchParams.set('name', query); // Use 'name' parameter
            url.searchParams.set('offset', '0');
            window.location.href = url.toString();
        });

        // Global Theme Toggle (if not handled elsewhere, or ensure it's compatible)
        const modeToggle = document.getElementById('modeToggle'); // Assumes this ID is in navigation.php
        if (modeToggle) {
            const htmlElement = document.documentElement;
            function applyTheme(theme) {
                htmlElement.setAttribute('data-bs-theme', theme); // This sets the global theme
                localStorage.setItem('theme', theme);
                modeToggle.checked = (theme === 'light');
            }
            const savedTheme = localStorage.getItem('theme') || 'dark';
            applyTheme(savedTheme);

            modeToggle.addEventListener('change', () => {
                applyTheme(modeToggle.checked ? 'light' : 'dark');
            });
        }
    });
</script>
</body>
</html>