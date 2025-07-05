<nav class="navbar navbar-expand-lg navbar-dark bg-dark py-3 sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold fs-4" href="index.php">
            <i class="fas fa-utensils me-2"></i>Rate It Up
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="index.php"><i class="bi bi-house me-1"></i> Beranda</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="restaurants.php"><i class="bi bi-shop me-1"></i> Tempat Kuliner</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="reviews.php"><i class="bi bi-chat-left-text me-1"></i> Review</a>
                </li>
            </ul>
            <div class="d-flex align-items-center">
                <button id="dark-mode-toggle" class="btn btn-sm btn-outline-light me-2">🌓</button>
                <form class="d-flex" action="restaurants.php" method="GET">
                    <input class="form-control me-2" type="search" id="search-input" name="search" placeholder="Cari..." aria-label="Search">
                </form>
            </div>
        </div>
    </div>
</nav>

<!-- Mobile Bottom Nav -->
<div class="mobile-bottom-nav d-lg-none">
    <a href="index.php" class="nav-link"><i class="bi bi-house"></i></a>
    <a href="restaurants.php" class="nav-link active"><i class="bi bi-shop"></i></a>
    <a href="reviews.php" class="nav-link"><i class="bi bi-chat-left-text"></i></a>
</div>