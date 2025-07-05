<?php
require_once 'includes/auth_check.php';
$page_title = "Detail Tempat Kuliner - Rate It Up";
include 'includes/header.php';
include 'config/functions.php';

$user_id = $_SESSION['user_id'] ?? null;
$user = $user_id ? getUserById($user_id) : null;

if (!isset($_GET['id'])) {
    redirect('restaurants.php');
}

$restaurant_id = (int)$_GET['id'];
$restaurant = getRestaurantById($restaurant_id);
$photos = getRestaurantPhotos($restaurant_id);

// Pindahkan ini sebelum penggunaan $reviews
$reviews = getRestaurantReviews($restaurant_id); 
$user_review = null;

if (isLoggedIn()) {
    foreach ($reviews as $review) {
        if ($review['user_id'] == $_SESSION['user_id']) {
            $user_review = $review;
            break;
        }
    }
}

if (!$restaurant) {
    echo '<div class="container mt-5"><div class="alert alert-danger">Tempat kuliner tidak ditemukan.</div></div>';
    include 'includes/footer.php';
    exit;
}

// Get rating data
$rating_data = getRestaurantRatingData($restaurant_id);
$avg_rating = $rating_data['avg_rating'];
$total_reviews = $rating_data['total_reviews'];

// Check if user has checked in
$has_checked_in = false;
if (isLoggedIn()) {
    $has_checked_in = hasUserCheckedIn($_SESSION['user_id'], $restaurant_id);
}

// Handle check-in
if (isLoggedIn() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkin'])) {
    $result = handleCheckIn($_SESSION['user_id'], $restaurant_id);
    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
    } else {
        $_SESSION['error_message'] = $result['message'];
    }
    redirect("restaurant-detail.php?id=$restaurant_id");
}

// Handle review submission
if (isLoggedIn() && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_review'])) {
        $rating = (int)$_POST['rating'];
        $content = sanitize($_POST['content']);
        $review_id = isset($_POST['review_id']) ? (int)$_POST['review_id'] : null;
        
        $result = handleReviewSubmission($_SESSION['user_id'], $restaurant_id, $rating, $content, $review_id);
        
        if ($result['success']) {
            $_SESSION['success_message'] = $result['message'];
            redirect("restaurant-detail.php?id=$restaurant_id");
        } else {
            if (isset($result['errors'])) {
                $_SESSION['errors'] = $result['errors'];
                $_SESSION['old_input'] = $_POST;
            } else {
                $_SESSION['error_message'] = $result['message'];
            }
            redirect("restaurant-detail.php?id=$restaurant_id");
        }
    }
    
    if (isset($_POST['delete_review'])) {
        $review_id = (int)$_POST['review_id'];
        $result = handleDeleteReview($review_id, $_SESSION['user_id']);
        
        if ($result['success']) {
            $_SESSION['success_message'] = $result['message'];
        } else {
            $_SESSION['error_message'] = $result['message'];
        }
        redirect("restaurant-detail.php?id=$restaurant_id");
    }
}

// Handle comment submission
if (isLoggedIn() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    $review_id = (int)$_POST['review_id'];
    $comment_content = sanitize($_POST['comment_content']);

    $result = handleCommentSubmission($_SESSION['user_id'], $review_id, $comment_content);

    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
    } else {
        if (isset($result['errors'])) {
            $_SESSION['errors_comment'] = $result['errors'];
            $_SESSION['old_comment_input_review_id'] = $review_id;
            $_SESSION['old_comment_input_content'] = $comment_content;
        } else {
            $_SESSION['error_message'] = $result['message'];
        }
    }
    redirect("restaurant-detail.php?id=$restaurant_id#review-$review_id");
}
?>

<div class="container mt-5 py-3">
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeInDown" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= $_SESSION['success_message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show animate__animated animate__fadeInDown" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $_SESSION['error_message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-4 shadow-lg border-0" data-aos="fade-right">
                <div class="card-body p-4">
                    <h1 class="card-title display-5 fw-bold mb-3"><?= htmlspecialchars($restaurant['name']) ?></h1>

                    <?php if ($avg_rating): ?>
                        <div class="d-flex align-items-center mb-4">
                            <div class="me-3">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= floor($avg_rating)): ?>
                                        <i class="bi bi-star-fill text-warning fs-3"></i>
                                    <?php elseif ($i == ceil($avg_rating) && $avg_rating - floor($avg_rating) >= 0.5): ?>
                                        <i class="bi bi-star-half text-warning fs-3"></i>
                                    <?php else: ?>
                                        <i class="bi bi-star text-warning fs-3"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                            <div>
                                <span class="fs-4 fw-bold text-primary"><?= number_format($avg_rating, 1) ?></span>
                                <span class="text-muted ms-2">(<?= $total_reviews ?> review)</span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-muted mb-4 fs-5">Belum ada rating</div>
                    <?php endif; ?>

                    <?php if (!empty($photos)): ?>
                        <div id="restaurantCarousel" class="carousel slide carousel-fade mb-4 shadow-sm rounded-3 overflow-hidden" data-bs-ride="carousel" data-aos="zoom-in" data-aos-delay="200">
                            <div class="carousel-inner">
                                <?php foreach ($photos as $index => $photo): ?>
                                    <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                        <img src="/assets/images/uploads/restaurants/<?= htmlspecialchars($photo['photo_path']) ?>" class="d-block w-100 rounded" alt="Restaurant Photo" style="height: 500px; object-fit: cover;">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (count($photos) > 1): ?>
                                <button class="carousel-control-prev" type="button" data-bs-target="#restaurantCarousel" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Previous</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#restaurantCarousel" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Next</span>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <img src="/assets/images/default-restaurant.jpg" class="img-fluid rounded-3 mb-4 shadow-sm" alt="Default Restaurant" style="height: 400px; object-fit: cover; width: 100%;" data-aos="zoom-in" data-aos-delay="200">
                    <?php endif; ?>

                    <h4 class="fw-bold mb-3 mt-4 text-primary">Deskripsi</h4>
                    <p class="card-text fs-6 text-muted mb-4 pb-3 border-bottom"><?= nl2br(htmlspecialchars($restaurant['description'])) ?></p>

                    <h4 class="fw-bold mb-3 text-primary">Alamat</h4>
                    <p class="mb-4 fs-6 text-muted pb-3 border-bottom"><?= nl2br(htmlspecialchars($restaurant['address'])) ?></p>

                    <?php if ($restaurant['map_url']): ?>
                        <h4 class="fw-bold mb-3 text-primary">Lokasi di Peta</h4>
                        <div class="ratio ratio-16x9 mb-4 shadow-sm rounded-3 overflow-hidden" data-aos="fade-up" data-aos-delay="300">
                            <iframe src="<?= htmlspecialchars($restaurant['map_url']) ?>" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                        </div>
                    <?php endif; ?>

                    <div class="mt-4 d-flex justify-content-between align-items-center flex-wrap gap-2" data-aos="fade-up" data-aos-delay="400">
                        <?php if (isLoggedIn()): ?>
                            <?php if (!$has_checked_in): ?>
                                <form method="POST">
                                    <button type="submit" name="checkin" class="btn btn-primary btn-lg rounded-pill px-4 shadow">
                                        <i class="bi bi-geo-alt-fill me-2"></i> Check-in di sini
                                    </button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-success btn-lg rounded-pill px-4 shadow" disabled>
                                    <i class="bi bi-check-circle-fill me-2"></i> Sudah Check-in
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="auth/login.php" class="btn btn-outline-primary btn-lg rounded-pill px-4 shadow">
                                <i class="bi bi-box-arrow-in-right me-2"></i> Login untuk Check-in
                            </a>
                        <?php endif; ?>

                        <?php if (isAdmin()): ?>
                            <div class="d-flex gap-2">
                                <a href="admin/restaurants/edit.php?id=<?= $restaurant['id'] ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                                    <i class="bi bi-pencil me-1"></i> Edit
                                </a>
                                <a href="admin/restaurants/delete.php?id=<?= $restaurant['id'] ?>" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="return confirm('Apakah Anda yakin ingin menghapus tempat ini?')">
                                    <i class="bi bi-trash me-1"></i> Hapus
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card mb-4 shadow-lg border-0" data-aos="fade-up" data-aos-delay="500">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="card-title fw-bold mb-0 text-primary">Review Pengguna (<?= $total_reviews ?>)</h2>
                        <?php if (isLoggedIn()): ?>
                            <?php if ($user_review): ?>
                                <button class="btn btn-warning rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#reviewModal">
                                    <i class="bi bi-pencil me-2"></i> Edit Review Anda
                                </button>
                            <?php else: ?>
                                <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#reviewModal">
                                    <i class="bi bi-pencil me-2"></i> Tulis Review
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="auth/login.php" class="btn btn-outline-primary rounded-pill px-4">
                                <i class="bi bi-box-arrow-in-right me-2"></i> Login untuk Menulis Review
                            </a>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($reviews)): ?>
                        <div class="alert alert-info text-center py-3">
                            <i class="bi bi-info-circle-fill me-2"></i> Belum ada review untuk tempat ini. Jadilah yang pertama!
                        </div>
                    <?php else: ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="mb-4 pb-3 border-bottom review-item" id="review-<?= $review['id'] ?>" data-aos="fade-up" data-aos-delay="<?= 50 * ($review['id'] % 10) + 100 ?>">
                                <div class="d-flex align-items-center mb-3">
                                    <img src="assets/images/uploads/profiles/<?= htmlspecialchars($review['profile_picture'] ?? 'default-profile.png') ?>" class="rounded-circle me-3 border border-primary p-1" width="60" height="60" alt="<?= htmlspecialchars($review['username']) ?>" style="object-fit: cover;">
                                    <div>
                                        <h5 class="mb-0 fw-bold"><?= htmlspecialchars($review['username']) ?></h5>
                                        <div class="text-warning">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <?php if ($i <= $review['rating']): ?>
                                                    <i class="bi bi-star-fill"></i>
                                                <?php else: ?>
                                                    <i class="bi bi-star"></i>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                            <span class="text-muted ms-2 small"><?= date('d M Y', strtotime($review['created_at'])) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <p class="mb-3 review-content-text ps-3 border-start border-2 border-secondary"><?= nl2br(htmlspecialchars($review['content'])) ?></p>

                                <?php $comments = getReviewComments($review['id']); ?>
                                <?php if (!empty($comments)): ?>
                                    <div class="ps-4 mt-4 border-start border-info border-2 comment-section">
                                        <h6 class="mb-3 fw-bold text-info"><i class="bi bi-chat-dots me-2"></i> Komentar (<?= count($comments) ?>)</h6>
                                        <?php foreach ($comments as $comment): ?>
                                            <div class="mb-3 p-3 bg-light rounded shadow-sm comment-item">
                                                <div class="d-flex align-items-center mb-2">
                                                    <img src="assets/images/uploads/profiles/<?= htmlspecialchars($comment['profile_picture'] ?? 'default-profile.png') ?>" class="rounded-circle me-2 border border-secondary p-1" width="35" height="35" alt="<?= htmlspecialchars($comment['username']) ?>" style="object-fit: cover;">
                                                    <div>
                                                        <small class="fw-bold text-dark"><?= htmlspecialchars($comment['username']) ?></small>
                                                        <small class="text-muted ms-2"><?= date('d M Y', strtotime($comment['created_at'])) ?></small>
                                                    </div>
                                                </div>
                                                <p class="mb-0 ps-4 small text-dark"><?= nl2br(htmlspecialchars($comment['content'])) ?></p>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (isLoggedIn()): ?>
                                    <form method="POST" class="mt-4 ps-4 pt-3 border-top border-1 border-light">
                                        <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                        <div class="mb-2">
                                            <textarea name="comment_content" class="form-control" rows="2" placeholder="Tulis komentar Anda..." required><?= (isset($_SESSION['old_comment_input_review_id']) && $_SESSION['old_comment_input_review_id'] == $review['id']) ? htmlspecialchars($_SESSION['old_comment_input_content']) : '' ?></textarea>
                                            <?php if (isset($_SESSION['errors_comment']['comment_content']) && isset($_SESSION['old_comment_input_review_id']) && $_SESSION['old_comment_input_review_id'] == $review['id']): ?>
                                                <small class="text-danger animate__animated animate__fadeIn"><?= $_SESSION['errors_comment']['comment_content'] ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <button type="submit" name="submit_comment" class="btn btn-sm btn-outline-info rounded-pill px-3">Kirim Komentar <i class="bi bi-send ms-1"></i></button>
                                    </form>
                                    <?php
                                    // Clear specific comment errors after displaying
                                    if (isset($_SESSION['errors_comment']) && isset($_SESSION['old_comment_input_review_id']) && $_SESSION['old_comment_input_review_id'] == $review['id']) {
                                        unset($_SESSION['errors_comment']);
                                        unset($_SESSION['old_comment_input_review_id']);
                                        unset($_SESSION['old_comment_input_content']);
                                    }
                                    ?>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4 shadow-lg border-0 bg-light" data-aos="fade-left">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold mb-3 text-primary"><i class="bi bi-info-circle-fill me-2"></i> Informasi Singkat</h5>
                    <ul class="list-group list-group-flush border-0">
                        <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 py-2">
                            <span>Total Review</span>
                            <span class="badge bg-primary rounded-pill fs-6"><?= $total_reviews ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 py-2">
                            <span>Rating Rata-rata</span>
                            <span class="badge bg-success rounded-pill fs-6"><?= $avg_rating ? number_format($avg_rating, 1) : 'N/A' ?></span>
                        </li>
                        <li class="list-group-item bg-transparent px-0 py-2">
                            <small class="text-muted"><i class="bi bi-person-fill me-1"></i> Ditambahkan oleh: <span class="fw-bold"><?= htmlspecialchars($restaurant['created_by_username'] ?? 'Admin') ?></span></small>
                        </li>
                        <li class="list-group-item bg-transparent px-0 py-2">
                            <small class="text-muted"><i class="bi bi-calendar-fill me-1"></i> Ditambahkan pada: <span class="fw-bold"><?= date('d M Y', strtotime($restaurant['created_at'])) ?></span></small>
                        </li>
                    </ul>
                </div>
            </div>

            <?php if (isAdmin()): ?>
                <div class="card mb-4 shadow-lg border-0 bg-dark text-white" data-aos="fade-left" data-aos-delay="100">
                    <div class="card-body p-4">
                        <h5 class="card-title fw-bold mb-3"><i class="bi bi-tools me-2"></i> Admin Tools</h5>
                        <a href="admin/restaurants/edit.php?id=<?= $restaurant['id'] ?>" class="btn btn-outline-warning w-100 mb-3 rounded-pill">
                            <i class="bi bi-pencil-square me-2"></i> Edit Tempat Ini
                        </a>
                        <a href="admin/restaurants/delete.php?id=<?= $restaurant['id'] ?>" class="btn btn-outline-danger w-100 rounded-pill" onclick="return confirm('Apakah Anda yakin ingin menghapus tempat ini beserta semua review dan fotonya?')">
                            <i class="bi bi-trash-fill me-2"></i> Hapus Tempat Ini
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card mb-4 shadow-lg border-0 bg-info text-white" data-aos="fade-left" data-aos-delay="200">
                <div class="card-body text-center py-4">
                    <h5 class="card-title fw-bold mb-3"><i class="bi bi-lightbulb-fill me-2"></i> Ingin Tempat Ini Terlihat?</h5>
                    <p class="mb-4">Promosikan tempat kuliner Anda di Rate It Up dan jangkau ribuan pecinta makanan!</p>
                    <a href="#" class="btn btn-outline-light btn-lg rounded-pill px-4">Pelajari Lebih Lanjut <i class="bi bi-arrow-right ms-2"></i></a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (isLoggedIn()): ?>
    <!-- Review Modal -->
    <div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil-square me-2"></i>
                        <?= $user_review ? 'Edit Review Anda' : 'Tulis Review Anda' ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="reviewForm">
                    <input type="hidden" name="review_id" value="<?= $user_review['id'] ?? '' ?>">
                    <div class="modal-body">
                        <div class="mb-4 text-center">
                            <label class="form-label fw-bold d-block mb-3">Berapa Rating Anda?</label>
                            <div class="rating-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="bi bi-star-fill star-rating fs-1 mx-1 <?=
                                                                                    ($user_review && $i <= $user_review['rating']) ||
                                                                                        (isset($_SESSION['old_input']['rating']) && $i <= $_SESSION['old_input']['rating']) ?
                                                                                        'text-warning' : 'text-muted' ?>"
                                        data-rating="<?= $i ?>"></i>
                                <?php endfor; ?>
                                <input type="hidden" name="rating" id="selectedRating"
                                    value="<?= $user_review['rating'] ?? ($_SESSION['old_input']['rating'] ?? '') ?>" required>
                            </div>
                            <?php if (isset($_SESSION['errors']['rating'])): ?>
                                <div class="text-danger mt-2"><?= $_SESSION['errors']['rating'] ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label for="reviewContent" class="form-label fw-bold">Bagaimana Pengalaman Anda?</label>
                            <textarea class="form-control" id="reviewContent" name="content" rows="5" required><?=
                                                                                                                htmlspecialchars($user_review['content'] ?? ($_SESSION['old_input']['content'] ?? ''))
                                                                                                                ?></textarea>
                            <?php if (isset($_SESSION['errors']['content'])): ?>
                                <div class="text-danger mt-2"><?= $_SESSION['errors']['content'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="submit_review" class="btn btn-primary">
                            <?= $user_review ? 'Update Review' : 'Kirim Review' ?>
                        </button>
                        <?php if ($user_review): ?>
                            <button type="submit" name="delete_review" class="btn btn-danger"
                                onclick="return confirm('Apakah Anda yakin ingin menghapus review ini?')">
                                <i class="bi bi-trash"></i> Hapus
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({
        once: true,
        duration: 800,
        easing: 'ease-out-quad',
    });

    // Star rating interaction
    document.querySelectorAll('.star-icon').forEach(star => {
        star.addEventListener('click', () => {
            const value = star.getAttribute('data-value');
            document.getElementById('rating-value').value = value;

            // Update star display
            document.querySelectorAll('.star-icon').forEach(s => {
                if (s.getAttribute('data-value') <= value) {
                    s.classList.add('text-warning');
                    s.classList.remove('text-muted');
                } else {
                    s.classList.remove('text-warning');
                    s.classList.add('text-muted');
                }
            });
        });
    });

    // Initialize rating if there was an error or old input
    <?php if (isset($_SESSION['old_input']['rating'])): ?>
        const initialRating = <?= (int)$_SESSION['old_input']['rating'] ?>;
        document.getElementById('rating-value').value = initialRating;
        document.querySelectorAll('.star-icon').forEach(star => {
            if (star.getAttribute('data-value') <= initialRating) {
                star.classList.add('text-warning');
                star.classList.remove('text-muted');
            } else {
                star.classList.remove('text-warning');
                star.classList.add('text-muted');
            }
        });
        // Remove old input after setting to prevent re-filling on refresh
        <?php unset($_SESSION['old_input']); ?>
    <?php endif; ?>

    document.addEventListener('DOMContentLoaded', function() {
        // Inisialisasi rating stars
        const stars = document.querySelectorAll('.star-rating');
        const ratingInput = document.getElementById('selectedRating');

        stars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = this.getAttribute('data-rating');
                ratingInput.value = rating;

                // Update tampilan bintang
                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.classList.add('text-warning');
                        s.classList.remove('text-muted');
                    } else {
                        s.classList.remove('text-warning');
                        s.classList.add('text-muted');
                    }
                });
            });
        });

        // Auto show modal jika ada error
        <?php if (isset($_SESSION['errors']) && !empty($_SESSION['errors'])): ?>
            var reviewModal = new bootstrap.Modal(document.getElementById('reviewModal'));
            reviewModal.show();
        <?php endif; ?>
    });
</script>