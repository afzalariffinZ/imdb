<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>IMDB Shorts</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">

    <!-- FontAwesome CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.7.2/css/fontawesome.min.css" integrity="sha256-TBe0l9PhFaVR3DwHmA2jQbUf1y6yQ22RBgJKKkNkC50=" crossorigin="anonymous">

    <!-- Custom CSS -->
    <link href="css/style.css" rel="stylesheet">
    <link href="css/title_style.css" rel="stylesheet">
</head>
<body>

<!-- Main Container -->
<main role="main" class="container">

    <!-- Navigation Bar -->
    <?php
    include_once 'navigation.php';
    include_once 'database.php';

    // Build base API link
    $path_parts = explode("/", $_SERVER['REQUEST_URI']);
    array_pop($path_parts);
    $path = implode("/", $path_parts);
    $base_url = 'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $path;
    $api_link = $base_url . "/api.php";

    // Query params
    $id = $_GET["id"] ?? null;
    $offset = isset($_GET["offset"]) ? (int)$_GET["offset"] : 0;
    $limit = isset($_GET["limit"]) ? (int)$_GET["limit"] : 8;
    $title_str = $_GET["title"] ?? "";

    if ($id) {
        // Fetch one short by id
        $title = getShort($id);
    } else {
        // Fetch count and list of shorts with optional title filter
        $count = getShortCount($title_str);
        $titles = getShorts($offset, $limit, $title_str);
    }
    ?>

    <?php if (!$id): ?>
        <h1 id="title_count" class="text-center mt-3"><?= $count ?> Shorts Found</h1>

        <!-- Search Bar -->
        <div class="container my-4">
            <div class="row">
                <div class="col-md-4">
                    <label for="title-input" class="form-label">Search by Title:</label>
                    <input id="title-input" type="text" class="form-control" value="<?= htmlspecialchars($title_str) ?>" placeholder="Enter short film title...">
                </div>
            </div>
        </div>

        <!-- Hidden API Link -->
        <a id="api-link" href="<?= htmlspecialchars($api_link) ?>" hidden>API link</a>

        <!-- Shorts Grid -->
        <div id="title-data" class="row row-cols-1 row-cols-md-4 g-3">
            <?php foreach ($titles as $title): ?>
                <a class="col p-2 text-decoration-none" href="shorts.php?id=<?= htmlspecialchars($title->getId()) ?>">
                    <div class="card h-100">
                        <?= $title->toHtml(); ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <div class="container mt-3">
            <div class="row">
                <h2><?= htmlspecialchars($title->getOriginalTitle()) ?></h2>
                <?= $title->toHtml(); ?>
            </div>
        </div>
    <?php endif; ?>

</main>

<!-- JS Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.7.2/js/all.min.js" integrity="sha256-BAR0H3Qu2PCfoVr6CtZrcnbK3VKenmUF9C6IqgsNsNU=" crossorigin="anonymous"></script>
<script src="js/titles.js"></script>

</body>
</html>
