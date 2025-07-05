<?php
session_start();
$page_title = "Rate It Up - Temukan Tempat Kuliner Terbaik di Indonesia";
include 'includes/header.php';
include 'config/functions.php';

$featured_restaurants = getAllRestaurants(6); 
$latest_reviews = getLatestReviews(4); 
$top_categories = getTopRatedRestaurants(5); 
?>

<div class="main-content">
    <section class="hero-section bg-primary text-white text-center py-5 py-lg-6 d-flex align-items-center position-relative overflow-hidden">
        <div class="container z-index-1" data-aos="fade-up" data-aos-duration="1000">
            <div class="row align-items-center">
                <div class="col-lg-7 mx-auto">
                    <h1 class="display-3 fw-bold mb-4 animate__animated animate__fadeInDown">Jelajahi Dunia Kuliner Bersama <span class="text-warning">Rate It Up</span></h1>
                    <p class="lead mb-5 animate__animated animate__fadeInUp animate__delay-1s">
                        Temukan, Ulas, dan Bagikan pengalaman kuliner terbaik Anda dari ribuan tempat di seluruh Indonesia.
                    </p>
                    <div class="d-flex justify-content-center flex-column flex-sm-row animate__animated animate__zoomIn animate__delay-2s">
                        <a href="restaurants.php" class="btn btn-light btn-lg rounded-pill px-4 py-2 me-sm-3 mb-3 mb-sm-0 shadow-lg">
                            <i class="bi bi-search me-2"></i> Jelajahi Tempat Kuliner
                        </a>
                        <?php if (!isset($_SESSION['user_id'])): ?>
                            <a href="auth/register.php" class="btn btn-outline-light btn-lg rounded-pill px-4 py-2 shadow-lg">
                                <i class="bi bi-person-plus me-2"></i> Bergabung Sekarang
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <img src="assets/images/food-hero.png" alt="Food Illustration" class="img-fluid position-absolute bottom-0 end-0 d-none d-lg-block animate__animated animate__fadeInRight" style="max-height: 80%; opacity: 0.8;">
    </section>

    <?php if (isset($_SESSION['user_id'])): ?>
    <div class="container mt-4" data-aos="fade-up" data-aos-delay="200">
        <div class="card shadow-sm border-0 animate__animated animate__slideInUp">
            <div class="card-body py-3">
                <h4 class="card-title text-center mb-3">Menu Cepat Anda</h4>
                <div class="d-flex flex-wrap justify-content-center gap-3">
                    <a href="<?= isAdmin() ? 'admin/dashboard.php' : 'user/dashboard.php' ?>" class="btn btn-outline-primary btn-hover-scale">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <a href="user/profile.php" class="btn btn-outline-info btn-hover-scale">
                        <i class="bi bi-person"></i> Profil Saya
                    </a>
                    <?php if (isAdmin()): ?>
                        <a href="admin/restaurants/" class="btn btn-outline-danger btn-hover-scale">
                            <i class="bi bi-shop"></i> Kelola Tempat
                        </a>
                    <?php endif; ?>
                    <a href="restaurants.php" class="btn btn-outline-success btn-hover-scale">
                        <i class="bi bi-search"></i> Cari Tempat
                    </a>
                    <a href="user/reviews/add.php" class="btn btn-outline-warning btn-hover-scale">
                        <i class="bi bi-pencil-square"></i> Tulis Review
                    </a>
                    <a href="auth/logout.php" class="btn btn-outline-secondary btn-hover-scale">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <section class="featured-restaurants-section py-5 bg-light">
        <div class="container">
            <div class="row text-center mb-5" data-aos="fade-up">
                <div class="col-lg-8 mx-auto">
                    <h2 class="display-5 fw-bold mb-3">Tempat Kuliner Pilihan Kami</h2>
                    <p class="lead text-muted">Jelajahi rekomendasi terbaik dari komunitas kami. Tempat-tempat ini wajib Anda kunjungi!</p>
                </div>
            </div>

            <?php if (empty($featured_restaurants)): ?>
                <div class="alert alert-info text-center" data-aos="fade-up" data-aos-delay="200">Belum ada tempat kuliner unggulan yang tersedia.</div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php foreach ($featured_restaurants as $restaurant): ?>
                        <div class="col" data-aos="fade-up" data-aos-delay="<?= 100 * ($restaurant['id'] % 3) + 100 ?>">
                            <div class="card h-100 shadow-sm border-0 restaurant-card animate__animated animate__fadeInUp">
                                <?php if ($restaurant['featured_photo']): ?>
                                    <img src="/assets/images/uploads/restaurants/<?= $restaurant['featured_photo'] ?>" class="card-img-top restaurant-img" alt="<?= $restaurant['name'] ?>">
                                <?php else: ?>
                                    <img src="/assets/images/default-restaurant.jpg" class="card-img-top restaurant-img" alt="Default Restaurant">
                                <?php endif; ?>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title text-truncate mb-2"><?= $restaurant['name'] ?></h5>
                                    <?php if ($restaurant['avg_rating']): ?>
                                        <div class="mb-2">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <?php if ($i <= floor($restaurant['avg_rating'])): ?>
                                                    <i class="bi bi-star-fill text-warning"></i>
                                                <?php elseif ($i == ceil($restaurant['avg_rating']) && $restaurant['avg_rating'] - floor($restaurant['avg_rating']) >= 0.5): ?>
                                                    <i class="bi bi-star-half text-warning"></i>
                                                <?php else: ?>
                                                    <i class="bi bi-star text-warning"></i>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                            <span class="ms-1 fw-bold"><?= number_format($restaurant['avg_rating'], 1) ?></span>
                                        </div>
                                    <?php else: ?>
                                        <div class="mb-2 text-muted">Belum ada rating</div>
                                    <?php endif; ?>
                                    <p class="card-text text-muted flex-grow-1 text-truncate-3-lines"><?= $restaurant['description'] ?></p>
                                    <div class="mt-auto">
                                        <a href="restaurant-detail.php?id=<?= $restaurant['id'] ?>" class="btn btn-outline-primary btn-sm w-100">Lihat Detail <i class="bi bi-arrow-right"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="text-center mt-5" data-aos="fade-up" data-aos-delay="400">
                    <a href="restaurants.php" class="btn btn-primary btn-lg rounded-pill px-4 py-2 shadow">
                        <i class="bi bi-grid me-2"></i> Lihat Semua Tempat Kuliner
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="latest-reviews-section py-5">
        <div class="container">
            <div class="row text-center mb-5" data-aos="fade-up">
                <div class="col-lg-8 mx-auto">
                    <h2 class="display-5 fw-bold mb-3">Review Terbaru dari Komunitas</h2>
                    <p class="lead text-muted">Dengar langsung dari para pecinta kuliner kami. Temukan inspirasi untuk petualangan rasa Anda berikutnya!</p>
                </div>
            </div>

            <?php if (empty($latest_reviews)): ?>
                <div class="alert alert-info text-center" data-aos="fade-up" data-aos-delay="200">Belum ada review terbaru yang tersedia.</div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
                    <?php foreach ($latest_reviews as $review): ?>
                        <div class="col" data-aos="fade-up" data-aos-delay="<?= 100 * ($review['id'] % 4) + 100 ?>">
                            <div class="card h-100 shadow-sm border-0 review-card animate__animated animate__fadeInUp">
                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex align-items-center mb-3">
                                        <img src="assets/images/uploads/profiles/<?= $review['profile_picture'] ?? 'default-profile.png' ?>" class="rounded-circle me-3 border border-primary p-1" width="50" height="50" alt="<?= $review['username'] ?>" style="object-fit: cover;">
                                        <div>
                                            <h6 class="mb-0 fw-bold"><?= $review['username'] ?></h6>
                                            <small class="text-muted"><i class="bi bi-clock me-1"></i><?= date('d M Y', strtotime($review['created_at'])) ?></small>
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= $review['rating']): ?>
                                                <i class="bi bi-star-fill text-warning"></i>
                                            <?php else: ?>
                                                <i class="bi bi-star text-warning"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                    <p class="card-text text-truncate-3-lines flex-grow-1 mb-3"><?= $review['content'] ?></p>
                                    <div class="mt-auto">
                                        <a href="restaurant-detail.php?id=<?= $review['restaurant_id'] ?>" class="btn btn-sm btn-outline-success w-100">
                                            Review tentang <span class="fw-bold"><?= $review['restaurant_name'] ?></span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="text-center mt-5" data-aos="fade-up" data-aos-delay="400">
                    <a href="reviews.php" class="btn btn-outline-primary btn-lg rounded-pill px-4 py-2 shadow">
                        <i class="bi bi-chat-left-text me-2"></i> Lihat Semua Review
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="how-it-works-section bg-primary text-white py-5">
        <div class="container">
            <div class="row text-center mb-5" data-aos="fade-up">
                <div class="col-lg-8 mx-auto">
                    <h2 class="display-5 fw-bold mb-3">Bagaimana Cara Kerja Rate It Up?</h2>
                    <p class="lead">Sangat mudah untuk memulai petualangan kuliner Anda.</p>
                </div>
            </div>
            <div class="row justify-content-center text-center g-4">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="p-4 bg-white rounded shadow-sm text-dark h-100 d-flex flex-column justify-content-center">
                        <div class="icon-circle bg-warning text-dark mx-auto mb-4">
                            <i class="fas fa-search fa-2x"></i>
                        </div>
                        <h4 class="fw-bold mb-3">1. Temukan Tempat</h4>
                        <p class="mb-0">Gunakan fitur pencarian kami untuk menemukan tempat makan yang sesuai dengan selera Anda.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="p-4 bg-white rounded shadow-sm text-dark h-100 d-flex flex-column justify-content-center">
                        <div class="icon-circle bg-warning text-dark mx-auto mb-4">
                            <i class="fas fa-star fa-2x"></i>
                        </div>
                        <h4 class="fw-bold mb-3">2. Baca Review</h4>
                        <p class="mb-0">Lihat review dari pengguna lain untuk mendapatkan gambaran nyata tentang sebuah tempat.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="p-4 bg-white rounded shadow-sm text-dark h-100 d-flex flex-column justify-content-center">
                        <div class="icon-circle bg-warning text-dark mx-auto mb-4">
                            <i class="fas fa-pencil-alt fa-2x"></i>
                        </div>
                        <h4 class="fw-bold mb-3">3. Bagikan Pengalaman</h4>
                        <p class="mb-0">Daftar dan tulis review Anda sendiri untuk membantu pecinta kuliner lainnya.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="call-to-action-section py-5 bg-light">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-lg-6" data-aos="fade-right">
                    <div class="card h-100 shadow-lg border-0 bg-white p-4">
                        <div class="card-body text-center">
                            <i class="bi bi-people-fill display-2 text-primary mb-3 icon-bounce"></i>
                            <h3 class="fw-bold mb-3">Bergabunglah dengan Komunitas Kami!</h3>
                            <p class="lead text-muted mb-4">Jadilah bagian dari komunitas pecinta kuliner terbesar di Indonesia. Dapatkan akses ke fitur eksklusif, simpan tempat favorit Anda, dan terhubung dengan pengguna lain.</p>
                            <?php if (!isLoggedIn()): ?>
                                <a href="auth/register.php" class="btn btn-primary btn-lg rounded-pill px-4 py-2 shadow-sm">
                                    <i class="bi bi-person-plus me-2"></i> Daftar Sekarang!
                                </a>
                            <?php else: ?>
                                <a href="restaurants.php" class="btn btn-outline-primary btn-lg rounded-pill px-4 py-2">
                                    <i class="bi bi-search me-2"></i> Jelajahi Sekarang
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                    <div class="card h-100 shadow-lg border-0 bg-white p-4">
                        <div class="card-body text-center">
                            <i class="bi bi-pencil-square display-2 text-success mb-3 icon-spin"></i>
                            <h3 class="fw-bold mb-3">Bagikan Pengalaman Kuliner Anda!</h3>
                            <p class="lead text-muted mb-4">Sudah menemukan tempat makan favorit? Tulis review Anda dan bantu pengguna lain membuat keputusan cerdas. Setiap review Anda bernilai!</p>
                            <?php if (!isLoggedIn()): ?>
                                <a href="auth/login.php" class="btn btn-success btn-lg rounded-pill px-4 py-2 shadow-sm">
                                    <i class="bi bi-box-arrow-in-right me-2"></i> Login untuk Menulis Review
                                </a>
                            <?php else: ?>
                                <a href="user/reviews/add.php" class="btn btn-success btn-lg rounded-pill px-4 py-2 shadow-sm">
                                    <i class="bi bi-pencil me-2"></i> Tulis Review Sekarang
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

</div> <?php include 'includes/footer.php'; ?>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({
        once: true, 
        duration: 800,
        easing: 'ease-out-quad',
    });
</script>
<script src="assets/js/script.js"></script>