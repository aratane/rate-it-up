<?php
require_once 'includes/auth_check.php';
$page_title = "Review Terbaru - Rate It Up";
include 'includes/header.php';
include 'config/functions.php';

$user_id = $_SESSION['user_id'] ?? null;
$user = $user_id ? getUserById($user_id) : null;

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Query untuk mendapatkan total data
$total_query = "SELECT COUNT(*) as total FROM reviews WHERE is_approved = 1";
$stmt = $pdo->query($total_query);
$total_result = $stmt->fetch();
$total_reviews = $total_result['total'];
$total_pages = ceil($total_reviews / $limit);

// Query untuk mendapatkan data dengan pagination
$query = "SELECT r.*, u.username, u.profile_picture, res.name as restaurant_name, res.id as restaurant_id
          FROM reviews r
          JOIN users u ON r.user_id = u.id
          JOIN restaurants res ON r.restaurant_id = res.id
          WHERE r.is_approved = 1
          ORDER BY r.created_at DESC
          LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($query);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$reviews = $stmt->fetchAll();

$top_restaurants = getTopRatedRestaurants(3); // Assuming this function exists and fetches top 3
?>

<div class="container mt-5">
    <div class="row mb-4" data-aos="fade-down">
        <div class="col text-center">
            <h1 class="display-4 fw-bold">Review Terbaru dari Komunitas</h1>
            <p class="lead text-muted">Dengar langsung dari para pecinta kuliner kami. Temukan inspirasi untuk petualangan rasa Anda berikutnya!</p>
        </div>
    </div>

    <?php if (empty($reviews)): ?>
        <div class="alert alert-info text-center py-4" data-aos="fade-up">
            <i class="bi bi-info-circle-fill fs-3 mb-2"></i>
            <h4 class="mb-2">Belum ada review yang tersedia.</h4>
            <p class="mb-0">Jadilah yang pertama menulis review untuk tempat favorit Anda!</p>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-lg-8">
                <?php foreach ($reviews as $review): ?>
                    <div class="card mb-4 shadow-sm border-0 review-item" data-aos="fade-up" data-aos-delay="<?= 100 * ($review['id'] % 5) + 50 ?>">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <img src="assets/images/uploads/profiles/<?= $review['profile_picture'] ?? 'default-profile.png' ?>" class="rounded-circle me-3 border border-primary p-1" width="60" height="60" alt="<?= htmlspecialchars($review['username']) ?>" style="object-fit: cover;">
                                <div>
                                    <h5 class="mb-0 fw-bold"><?= htmlspecialchars($review['username']) ?></h5>
                                    <div class="d-flex align-items-center">
                                        <div class="text-warning me-2">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <?php if ($i <= $review['rating']): ?>
                                                    <i class="bi bi-star-fill"></i>
                                                <?php else: ?>
                                                    <i class="bi bi-star"></i>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                        </div>
                                        <a href="restaurant-detail.php?id=<?= $review['restaurant_id'] ?>" class="text-decoration-none fw-bold text-primary">
                                            <?= htmlspecialchars($review['restaurant_name']) ?>
                                        </a>
                                    </div>
                                    <small class="text-muted"><i class="bi bi-clock me-1"></i><?= date('d M Y H:i', strtotime($review['created_at'])) ?></small>
                                </div>
                            </div>
                            <p class="mb-3 review-content"><?= nl2br(htmlspecialchars($review['content'])) ?></p>
                            <div class="d-flex justify-content-end">
                                <a href="restaurant-detail.php?id=<?= $review['restaurant_id'] ?>#review-<?= $review['id'] ?>" class="btn btn-outline-primary btn-sm rounded-pill">
                                    Baca Selengkapnya & Komentar <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <nav aria-label="Page navigation" class="mt-5" data-aos="fade-up">
                    <ul class="pagination justify-content-center pagination-lg">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4 shadow-sm border-0" data-aos="fade-left">
                    <div class="card-body">
                        <h5 class="card-title fw-bold mb-3">Tempat Kuliner Teratas <i class="bi bi-trophy-fill text-warning"></i></h5>
                        <?php if (!empty($top_restaurants)): ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($top_restaurants as $restaurant): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <a href="restaurant-detail.php?id=<?= $restaurant['id'] ?>" class="text-decoration-none text-dark fw-bold">
                                            <i class="bi bi-dot me-2 text-primary"></i><?= htmlspecialchars($restaurant['name']) ?>
                                        </a>
                                        <span class="badge bg-success rounded-pill p-2 fs-6">
                                            <i class="bi bi-star-fill me-1"></i><?= number_format($restaurant['avg_rating'], 1) ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted">Belum ada data restoran teratas.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card shadow-sm border-0 bg-primary text-white" data-aos="fade-left" data-aos-delay="200">
                    <div class="card-body text-center py-4">
                        <h5 class="card-title fw-bold mb-3">Bagikan Pengalaman Anda!</h5>
                        <p class="mb-4">Tulis review pertama Anda dan dapatkan poin sebagai kontributor aktif.</p>
                        <?php if (!isLoggedIn()): ?>
                            <a href="auth/register.php" class="btn btn-warning text-dark btn-lg rounded-pill w-100 mb-2">
                                <i class="bi bi-person-plus-fill me-2"></i> Daftar Sekarang!
                            </a>
                            <a href="auth/login.php" class="btn btn-outline-light btn-lg rounded-pill w-100">
                                <i class="bi bi-box-arrow-in-right me-2"></i> Login
                            </a>
                        <?php else: ?>
                            <a href="user/reviews/add.php" class="btn btn-warning text-dark btn-lg rounded-pill w-100">
                                <i class="bi bi-pencil-square me-2"></i> Tulis Review
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({
        once: true,
        duration: 800,
        easing: 'ease-out-quad',
    });
</script>