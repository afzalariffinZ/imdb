<!DOCTYPE html>
<html lang="en" data-bs-theme="dark"> <!-- Set default theme on html -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= isset($_GET["id"]) ? "Title Details" : "Browse Titles" ?> - IMDb2</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">

    <!-- FontAwesome CSS (using official CSS link now) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Custom Global CSS (if you have one from previous example) -->
    <!-- <link href="css/style.css" rel="stylesheet"> Your main style.css -->
    <!-- Page Specific CSS -->
    <link href="css/title.css" rel="stylesheet"> <!-- The new CSS file -->
    <!-- <link href="css/title_style.css" rel="stylesheet"> -- If this contains $title->toHtml() specific styles, keep it or merge -->

</head>
<body>
<div class="background-overlay"></div> <!-- For animated background consistency -->

<!-- Navigation Bar -->
<?php
include_once 'navigation.php'; // Make sure navigation.php also respects data-bs-theme
include_once 'database.php';

// API Link Construction (keep as is)
$path_parts = explode("/", $_SERVER['REQUEST_URI']);
array_pop($path_parts);
$path = implode("/", $path_parts);
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . '://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $path;
$api_link = $base_url . "/api.php";

// Data Fetching Logic
$id = $_GET["id"] ?? null;
$offset = isset($_GET["offset"]) ? (int)$_GET["offset"] : 0;
$limit = isset($_GET["limit"]) ? (int)$_GET["limit"] : 8; // Default to 8 items
$title_str = $_GET["title"] ?? "";
$title_obj = null; // For single title view
$titles_list = []; // For list view
$count = 0; // For list view

if ($id) {
    $title_obj = getTitle($id); // Assumes getTitle returns a single title object or null
} else {
    $count = getTitleCount($title_str);
    $titles_list = getTitles($offset, $limit, $title_str);
}
?>

<!-- Main Container -->
<main role="main" class="container titles-page-container content-wrapper">

    <?php if (!$id): /* LIST VIEW */ ?>
        <h1 class="page-title">
            <?php if (!empty($title_str)): ?>
                Results for "<strong><?= htmlspecialchars($title_str) ?></strong>" (<?= $count ?> found)
            <?php else: ?>
                Browse Titles (<?= $count ?> total)
            <?php endif; ?>
        </h1>

        <!-- Search Bar Section -->
        <div class="search-controls-container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <form id="title-search-form">
                        <label for="title-input" class="form-label visually-hidden">Search by Title:</label>
                        <div class="input-group">
                            <input id="title-input" name="title" type="search" class="form-control form-control-lg" value="<?= htmlspecialchars($title_str) ?>" placeholder="Enter movie title...">
                            <button id="search-btn" class="btn btn-primary btn-lg" type="submit">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Hidden API Link (can be removed if not used by JS) -->
        <!-- <a id="api-link" href="<?= $api_link ?>" hidden>API link</a> -->

        <!-- Titles Grid -->
        <?php if (!empty($titles_list)): ?>
            <div id="title-data" class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3 titles-grid">
                <?php foreach ($titles_list as $title_item): ?>
                    <div class="col">
                        <a class="title-card-link" href="titles.php?id=<?= $title_item->getId() ?>">
                            <div class="card">
                                <?php
                                // Assuming $title_item->toHtml() generates the inner card HTML
                                // e.g., <img class="card-img-top" ...><div class="card-body">...</div>
                                echo $title_item->toHtml();
                                ?>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif (!empty($title_str)): ?>
            <div class="text-center alert alert-warning" role="alert">
                No titles found matching "<?= htmlspecialchars($title_str) ?>".
            </div>
        <?php else: ?>
             <div class="text-center alert alert-info" role="alert">
                No titles to display.
            </div>
        <?php endif; ?>

    <?php elseif ($title_obj): /* DETAIL VIEW */ ?>
        <div class="title-detail-container">
            <div class="title-detail-header">
                <h2><?= htmlspecialchars($title_obj->getPrimaryTitle()) ?></h2> <!-- Use primaryTitle for display -->
                <?php if ($title_obj->getPrimaryTitle() !== $title_obj->getOriginalTitle()): ?>
                    <p class="text-muted">(Original Title: <?= htmlspecialchars($title_obj->getOriginalTitle()) ?>)</p>
                <?php endif; ?>
            </div>
            <div class="title-detail-content">
                <?php
                // Assuming $title_obj->toHtml() generates detailed HTML for a single title
                // This might need to be structured differently for detail view,
                // e.g., $title_obj->toDetailedHtml() or specific getter methods.
                // Example:
                // <div class="row">
                //    <div class="col-md-4 title-detail-poster"><img src="..."></div>
                //    <div class="col-md-8 title-detail-info"><h4>Plot</h4><p>...</p>...</div>
                // </div>
                echo $title_obj->toHtml(); // You might need to adapt this method
                ?>
            </div>
        </div>
    <?php else: /* ID provided but title not found */ ?>
        <h1 class="page-title">Title Not Found</h1>
        <div class="alert alert-danger text-center" role="alert">
            The requested title could not be found. It might have been removed or the ID is incorrect.
        </div>
        <div class="text-center">
            <a href="titles.php" class="btn btn-primary">Back to Titles List</a>
        </div>
    <?php endif; ?>


    <!-- Pagination (Only show if not in detail view AND there are items) -->
    <?php if (!$id && $count > 0): ?>
    <div class="d-flex justify-content-center pagination-controls">
        <a href="?title=<?= urlencode($title_str) ?>&offset=<?= max(0, $offset - $limit) ?>&limit=<?= $limit ?>"
           class="btn btn-secondary me-2 <?= ($offset <= 0) ? 'disabled' : '' ?>">
            <i class="fas fa-chevron-left"></i> Previous
        </a>

        <span class="align-self-center mx-3 text-muted">
            Page <?= floor($offset / $limit) + 1 ?> of <?= ceil($count / $limit) ?>
        </span>

        <a href="?title=<?= urlencode($title_str) ?>&offset=<?= $offset + $limit ?>&limit=<?= $limit ?>"
           class="btn btn-primary <?= (($offset + $limit) >= $count) ? 'disabled' : '' ?>">
            Next <i class="fas fa-chevron-right"></i>
        </a>
    </div>
    <?php endif; ?>

</main>



<footer class="footer-spacing py-3 mt-10 imdb-footer content-wrapper"> <!-- content-wrapper for z-index -->
    <div class="container text-center">
        <p class="small mb-1">© <span id="currentYear"></span> IMDb2. A fictional site for demonstration.</p>
        <p class="small">
            <a href="#">About</a> |
            <a href="#">Contact</a> |
            <a href="#">Privacy</a>
        </p>
    </div>
</footer>

<!-- JS Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
<!-- FontAwesome JS (no longer needed if you use the CSS version only for icons) -->
<!-- <script src="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.7.2/js/all.min.js" integrity="sha256-BAR0H3Qu2PCfoVr6CtZrcnbK3VKenmUF9C6IqgsNsNU=" crossorigin="anonymous"></script> -->
<script>
    // Ensure this script runs after the DOM is ready
    $(document).ready(function() {
        // Search button click
        $('#title-search-form').on('submit', function(e) { // Listen to form submit
            e.preventDefault(); // Prevent default form submission
            const query = $('#title-input').val().trim();
            const url = new URL(window.location.pathname, window.location.origin); // Use pathname to build clean URL
            url.searchParams.set('title', query);
            // id is not relevant for a new search for list view
            url.searchParams.set('offset', '0'); // reset offset to first page
            // limit can be kept or reset as well, e.g. url.searchParams.set('limit', '8');
            window.location.href = url.toString();
        });

        // Theme toggle (if not handled globally in navigation.php or another script)
        const modeToggle = document.getElementById('modeToggle'); // Assuming this ID is in navigation.php
        if (modeToggle) {
            const htmlElement = document.documentElement;

            function applyTheme(theme) {
                htmlElement.setAttribute('data-bs-theme', theme);
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
<!-- Your custom titles.js if needed -->
<!-- <script src="js/titles.js"></script> -->
</body>
</html>