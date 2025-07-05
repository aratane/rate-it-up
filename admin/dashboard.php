<?php
require_once '../includes/auth_check.php';
$page_title = "Dashboard Admin - Rate It Up";
include '../includes/header.php';
include '../config/functions.php';

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

// Get counts for dashboard
$users_count = $pdo->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];
$restaurants_count = $pdo->query("SELECT COUNT(*) as count FROM restaurants")->fetch()['count'];
$reviews_count = $pdo->query("SELECT COUNT(*) as count FROM reviews")->fetch()['count'];
$pending_reviews = $pdo->query("SELECT COUNT(*) as count FROM reviews WHERE is_approved = 0")->fetch()['count'];
$pending_comments = $pdo->query("SELECT COUNT(*) as count FROM comments WHERE is_approved = 0")->fetch()['count'];

// Get latest reviews
$latest_reviews = $pdo->query("SELECT r.*, u.username, res.name as restaurant_name 
                             FROM reviews r 
                             JOIN users u ON r.user_id = u.id 
                             JOIN restaurants res ON r.restaurant_id = res.id 
                             ORDER BY r.created_at DESC 
                             LIMIT 5")->fetchAll();

// Get latest restaurants
$latest_restaurants = $pdo->query("SELECT r.*, u.username as created_by 
                                  FROM restaurants r 
                                  JOIN users u ON r.created_by = u.id 
                                  ORDER BY r.created_at DESC 
                                  LIMIT 5")->fetchAll();
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-3">
            <!-- Kartu Profil Admin -->
            <div class="card mb-4">
                <div class="card-body text-center">
                    <img src="../assets/images/uploads/profiles/<?= $user['profile_picture'] ?>" class="rounded-circle mb-3" width="150" height="150" alt="Profile Picture">
                    <h4><?= $user['full_name'] ?></h4>
                    <p class="text-muted">@<?= $user['username'] ?> (Admin)</p>

                    <!-- Tombol Navigasi -->
                    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm d-block mb-2 <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <a href="users/" class="btn btn-outline-secondary btn-sm d-block mb-2 <?= strpos($_SERVER['PHP_SELF'], '/users/') !== false ? 'active' : '' ?>">
                        <i class="bi bi-people"></i> Kelola Pengguna
                    </a>
                    <a href="restaurants/" class="btn btn-outline-secondary btn-sm d-block mb-2 <?= strpos($_SERVER['PHP_SELF'], '/restaurants/') !== false ? 'active' : '' ?>">
                        <i class="bi bi-shop"></i> Kelola Tempat
                    </a>
                    <a href="reviews/" class="btn btn-outline-secondary btn-sm d-block mb-2 <?= strpos($_SERVER['PHP_SELF'], '/reviews/') !== false ? 'active' : '' ?>">
                        <i class="bi bi-star"></i> Kelola Review
                    </a>
                    <a href="comments/" class="btn btn-outline-secondary btn-sm d-block mb-3 <?= strpos($_SERVER['PHP_SELF'], '/comments/') !== false ? 'active' : '' ?>">
                        <i class="bi bi-chat-left-text"></i> Kelola Komentar
                    </a>

                    <!-- Tombol Logout -->
                    <a href="../auth/logout.php" class="btn btn-outline-danger btn-sm d-block">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="card-title">Dashboard Admin</h2>
                    <p class="text-muted">Selamat datang kembali, <?= $_SESSION['full_name'] ?>!</p>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title">Total Pengguna</h5>
                                    <h2 class="mb-0"><?= $users_count ?></h2>
                                </div>
                                <i class="bi bi-people-fill fs-1"></i>
                            </div>
                            <a href="users/" class="stretched-link text-white text-decoration-none"></a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title">Total Tempat</h5>
                                    <h2 class="mb-0"><?= $restaurants_count ?></h2>
                                </div>
                                <i class="bi bi-shop fs-1"></i>
                            </div>
                            <a href="restaurants/" class="stretched-link text-white text-decoration-none"></a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title">Total Review</h5>
                                    <h2 class="mb-0"><?= $reviews_count ?></h2>
                                </div>
                                <i class="bi bi-star-fill fs-1"></i>
                            </div>
                            <a href="reviews/" class="stretched-link text-white text-decoration-none"></a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <div class="card bg-warning text-dark">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title">Review Menunggu</h5>
                                    <h2 class="mb-0"><?= $pending_reviews ?></h2>
                                </div>
                                <i class="bi bi-hourglass-split fs-1"></i>
                            </div>
                            <a href="reviews/" class="stretched-link text-dark text-decoration-none"></a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title">Komentar Menunggu</h5>
                                    <h2 class="mb-0"><?= $pending_comments ?></h2>
                                </div>
                                <i class="bi bi-chat-left-text fs-1"></i>
                            </div>
                            <a href="comments/" class="stretched-link text-white text-decoration-none"></a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">Review Terbaru</h5>
                                <a href="reviews/" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                            </div>
                            
                            <?php if (empty($latest_reviews)): ?>
                                <div class="alert alert-info">Belum ada review.</div>
                            <?php else: ?>
                                <div class="list-group">
                                    <?php foreach ($latest_reviews as $review): ?>
                                        <a href="/restaurant-detail.php?id=<?= $review['restaurant_id'] ?>#review-<?= $review['id'] ?>" class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1"><?= $review['restaurant_name'] ?></h6>
                                                <small><?= date('d M Y', strtotime($review['created_at'])) ?></small>
                                            </div>
                                            <div class="mb-1">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <?php if ($i <= $review['rating']): ?>
                                                        <i class="bi bi-star-fill text-warning"></i>
                                                    <?php else: ?>
                                                        <i class="bi bi-star text-warning"></i>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </div>
                                            <p class="mb-1"><?= substr($review['content'], 0, 100) ?>...</p>
                                            <small class="text-muted">Oleh: <?= $review['username'] ?></small>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">Tempat Terbaru</h5>
                                <a href="restaurants/" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                            </div>
                            
                            <?php if (empty($latest_restaurants)): ?>
                                <div class="alert alert-info">Belum ada tempat kuliner.</div>
                            <?php else: ?>
                                <div class="list-group">
                                    <?php foreach ($latest_restaurants as $restaurant): ?>
                                        <a href="/restaurant-detail.php?id=<?= $restaurant['id'] ?>" class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1"><?= $restaurant['name'] ?></h6>
                                                <small><?= date('d M Y', strtotime($restaurant['created_at'])) ?></small>
                                            </div>
                                            <p class="mb-1"><?= substr($restaurant['description'], 0, 100) ?>...</p>
                                            <small class="text-muted">Ditambahkan oleh: <?= $restaurant['created_by'] ?></small>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>