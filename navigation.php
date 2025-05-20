<!--
    This file is not meant to be used on its own.
    Instead, it should/can be included into other files,
    where we want to include a navigation menu.
-->

<nav class="navbar navbar-expand-lg imdb-navbar sticky-top">
    <div class="container-fluid px-md-4 px-2">
        <a class="navbar-brand fw-bold imdb-font" href="index.html">IMDb2</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link active" aria-current="page" href="index.html">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="titles.php">Movies</a></li>
                <li class="nav-item"><a class="nav-link" href="tvseries.php">TV Series</a></li>
                <li class="nav-item"><a class="nav-link" href="shorts.php">Shorts</a></li>
                <li class="nav-item"><a class="nav-link" href="celebs.php">Celebs</a></li>
            </ul>
            <div class="form-check form-switch ms-lg-3 mt-2 mt-lg-0">
                <input class="form-check-input" type="checkbox" role="switch" id="modeToggle">
                <label class="form-check-label" for="modeToggle">Light Mode</label>
            </div>
        </div>
    </div>
</nav>