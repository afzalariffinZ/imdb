<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>IMDB 2</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-black text-white">
<nav class="navbar navbar-expand-lg navbar-dark bg-maroon border-bottom border-2 border-maroon px-4">
    <a class="navbar-brand fw-bold imdb-font" href="#">IMDB2</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-between" id="navbarNav">
        <ul class="navbar-nav">
            <li class="nav-item"><a class="nav-link" href="titles.php">Movies</a></li>
            <li class="nav-item"><a class="nav-link" href="tvseries.php">TV Series</a></li>
            <li class="nav-item"><a class="nav-link" href="shorts.php">Shorts</a></li>
            <li class="nav-item"><a class="nav-link" href="celebs.php">Celebs</a></li>
        </ul>
        <div class="form-check form-switch text-white">
            <input class="form-check-input" type="checkbox" id="modeToggle">
            <label class="form-check-label" for="modeToggle">Light Mode</label>
        </div>
    </div>
</nav>



<main class="container py-4">
    <div class="row justify-content-center mb-3">
        <img class="img-thumbnail img-banner border border-2 border-maroon" src="images/IMDB_Logo.png" alt="Yoda">
        <h4 class="text-center mt-3">Welcome to IMDB ...  Page!</h4>
    </div>

    <div class="row align-items-center mb-4">
        <div class="col-md-8 offset-md-2">
            <div class="input-group">
                <input id="search-input" class="form-control bg-black text-white border border-2 border-maroon" type="text" placeholder="Search for a Film, Series, Person...">
                <button id="search-button" class="btn btn-maroon text-white border border-2 border-maroon">Search</button>
            </div>
        </div>
    </div>
</main>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const searchBtn = document.getElementById("search-button");
        const searchInput = document.getElementById("search-input");

        searchBtn.addEventListener("click", function () {
            const keyword = searchInput.value.trim();

            if (keyword === "") {
                alert("Please enter something to search!");
            } else {
                alert("You searched for: " + keyword);

                // Optional: redirect or use the input for something
                location.href = "titles.php"+ encodeURIComponent(keyword);
            }
        });
    });
</script>
<!-- Bootstrap & Script -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const toggle = document.getElementById('modeToggle');
    toggle.addEventListener('change', () => {
        document.body.classList.toggle('bg-black');
        document.body.classList.toggle('bg-light');
        document.body.classList.toggle('text-white');
        document.body.classList.toggle('text-dark');
    });
</script>
</body>
</html>
