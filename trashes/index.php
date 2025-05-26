<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>IMDb2 - Discover Movies, TV & Celebs</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Your go-to source for movie, TV series, shorts, and celebrity information. Search, discover, and explore the world of entertainment with IMDb2.">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <!-- Font Awesome for Icons (Optional but recommended for search icon) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- Custom CSS -->
    <link href="css/style.css" rel="stylesheet">
    <!-- Favicon -->
    <link rel="icon" href="images/favicon.ico" type="image/x-icon">
</head>
<body data-bs-theme="dark"> <!-- Default to dark theme using Bootstrap's attribute -->

<div class="background-overlay"></div> <!-- Overlay for background image -->

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

<main class="container py-4 content-wrapper"> <!-- content-wrapper for z-index -->
    <header class="text-center mb-4">
        <img class="img-fluid mb-3 site-logo" src="images/IMDB_Logo.png" alt="IMDb2 Logo"> <!-- Keep your original logo if you prefer -->
        <h1 class="display-5 site-title imdb-font">Welcome to IMDb2</h1>
        <p class="lead site-tagline">Explore the world of cinema and television.</p>
    </header>

    <section class="row justify-content-center mb-5">
        <div class="col-lg-8 col-md-10">
            <form id="search-form">
                <div class="input-group input-group-lg">
                    <input id="search-input" class="form-control" type="search" placeholder="Search for Films, Series, People..." aria-label="Search">
                    <button id="search-button" class="btn btn-imdb-primary" type="submit">
                        <i class="fas fa-search d-none d-sm-inline"></i> <!-- Icon hidden on extra small screens -->
                        <span class="d-sm-none">Search</span> <!-- Text "Search" on extra small screens -->
                        <span class="d-none d-sm-inline">Search</span> <!-- Text "Search" on sm and up -->
                    </button>
                </div>
                <div id="search-error-container"></div> <!-- For error messages -->
            </form>
        </div>
    </section>

    <!-- Optional: Placeholder for dynamic content like "Trending", "New Releases" -->
    <section id="featured-content" class="mb-5">
        <h3 class="text-center mb-4 section-title"><span>What's Popular</span></h3>
        <div class="row">
            <!-- Example placeholder cards - replace with dynamic data -->
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card content-card h-100">
                    <img src="https://t4.ftcdn.net/jpg/02/12/52/91/360_F_212529193_YRhcQCaJB9ugv5dFzqK25Uo9Ivm7B9Ca.jpg" class="card-img-top" alt="Trending Movie 1">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">Trending Movie 1</h5>
                        <p class="card-text small">A brief synopsis of the trending movie or show. More text to see how it wraps.</p>
                        <a href="#" class="btn btn-imdb-secondary mt-auto">Learn More</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 mb-4">
                 <div class="card content-card h-100">
                    <img src="https://i.ebayimg.com/images/g/eKEAAOxyOMdS4U2W/s-l1200.jpg" class="card-img-top" alt="Trending TV Show">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">Popular TV Show</h5>
                        <p class="card-text small">Catch up on the latest episodes of this hit series.</p>
                        <a href="#" class="btn btn-imdb-secondary mt-auto">Explore Episodes</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 mb-4 d-md-none d-lg-block"> <!-- Hide on medium, show on large -->
                 <div class="card content-card h-100">
                    <img src="https://i.namu.wiki/i/euH86A9h57zvltz6Vn9PaB-R1lXomWwf2DAMJdUXZvvAMbT9BN4qjXlOLSbXNdVVZwMSPkRLpMTiYekBTf7Uxg.webp" class="card-img-top" alt="Featured Celeb" >
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">Featured Celebrity</h5>
                        <p class="card-text small">Discover the latest work from this talented actor.</p>
                        <a href="#" class="btn btn-imdb-secondary mt-auto">View Profile</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<footer class="py-3 mt-auto imdb-footer content-wrapper"> <!-- content-wrapper for z-index -->
    <div class="container text-center">
        <p class="small mb-1">© <span id="currentYear"></span> IMDb2. A fictional site for demonstration.</p>
        <p class="small">
            <a href="#">About</a> |
            <a href="#">Contact</a> |
            <a href="#">Privacy</a>
        </p>
    </div>
</footer>

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const searchForm = document.getElementById("search-form");
        const searchInput = document.getElementById("search-input");
        const searchErrorContainer = document.getElementById("search-error-container");

        searchForm.addEventListener("submit", function (event) {
            event.preventDefault();
            const keyword = searchInput.value.trim();
            searchErrorContainer.innerHTML = ''; // Clear previous errors
            searchInput.classList.remove('is-invalid');

            if (keyword === "") {
                searchInput.classList.add('is-invalid');
                const errorMsg = document.createElement('div');
                errorMsg.classList.add('text-danger', 'mt-2', 'small'); // Bootstrap error styling
                errorMsg.textContent = 'Please enter something to search!';
                searchErrorContainer.appendChild(errorMsg);
                searchInput.focus();
            } else {
                console.log("Searching for: " + keyword);
                // alert("You searched for: " + keyword); // Keep alert if you like, or remove for redirection
                window.location.href = "titles.php?title=" + encodeURIComponent(keyword);
            }
        });

        searchInput.addEventListener('input', function() {
            if (this.value.trim() !== '') {
                searchInput.classList.remove('is-invalid');
                searchErrorContainer.innerHTML = '';
            }
        });

        // Light/Dark Mode Toggle
        const modeToggle = document.getElementById('modeToggle');
        const body = document.body;
        const htmlElement = document.documentElement; // Target <html> for data-bs-theme

        function applyTheme(theme) {
            htmlElement.setAttribute('data-bs-theme', theme);
            localStorage.setItem('theme', theme);
            modeToggle.checked = (theme === 'light');
        }

        // Check local storage for saved mode
        const savedTheme = localStorage.getItem('theme') || 'dark'; // Default to dark
        applyTheme(savedTheme);


        modeToggle.addEventListener('change', () => {
            applyTheme(modeToggle.checked ? 'light' : 'dark');
        });

        // Set current year in footer
        document.getElementById('currentYear').textContent = new Date().getFullYear();
    });
</script>
</body>
</html>