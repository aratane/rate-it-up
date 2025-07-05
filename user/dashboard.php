<?php
require_once '../includes/auth_check.php';
$page_title = "Dashboard User - Rate It Up";
include '../includes/header.php';
include '../config/functions.php';

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

// Get user's reviews
$stmt = $pdo->prepare("SELECT r.*, res.name as restaurant_name, res.id as restaurant_id 
                      FROM reviews r 
                      JOIN restaurants res ON r.restaurant_id = res.id 
                      WHERE r.user_id = ? 
                      ORDER BY r.created_at DESC 
                      LIMIT 5");
$stmt->execute([$user_id]);
$reviews = $stmt->fetchAll();

// Get user's check-ins
$stmt = $pdo->prepare("SELECT c.*, res.name as restaurant_name, res.id as restaurant_id 
                      FROM checkins c 
                      JOIN restaurants res ON c.restaurant_id = res.id 
                      WHERE c.user_id = ? 
                      ORDER BY c.checkin_date DESC 
                      LIMIT 5");
$stmt->execute([$user_id]);
$checkins = $stmt->fetchAll();
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <img src="../assets/images/uploads/profiles/<?= $user['profile_picture'] ?>" class="rounded-circle mb-3" width="150" height="150" alt="Profile Picture">
                    <h4><?= $user['full_name'] ?></h4>
                    <p class="text-muted">@<?= $user['username'] ?></p>

                    <!-- Tombol Dashboard -->
                    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm d-block mb-2">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>

                    <!-- Tombol Edit Profil -->
                    <a href="profile.php" class="btn btn-outline-primary btn-sm d-block mb-2">
                        <i class="bi bi-pencil-square"></i> Edit Profil
                    </a>

                    <!-- Tombol Logout -->
                    <a href="../auth/logout.php" class="btn btn-outline-danger btn-sm d-block">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Menu</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <a href="reviews/" class="text-decoration-none">Review Saya</a>
                        </li>
                        <li class="list-group-item">
                            <a href="checkins/" class="text-decoration-none">Check-in Saya</a>
                        </li>
                        <li class="list-group-item">
                            <a href="../restaurants.php" class="text-decoration-none">Jelajahi Tempat</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="card-title">Dashboard</h2>
                    <p class="text-muted">Selamat datang kembali, <?= $user['full_name'] ?>!</p>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">Review Terbaru</h5>
                        <a href="reviews/" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                    </div>
                    
                    <?php if (empty($reviews)): ?>
                        <div class="alert alert-info">Anda belum menulis review. <a href="/restaurants.php" class="alert-link">Jelajahi tempat kuliner</a> dan tulis review pertama Anda!</div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($reviews as $review): ?>
                                <a href="../restaurant-detail.php?id=<?= $review['restaurant_id'] ?>#review-<?= $review['id'] ?>" class="list-group-item list-group-item-action">
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
                                    <small class="text-muted">Status: <?= $review['is_approved'] ? 'Disetujui' : 'Menunggu Persetujuan' ?></small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">Check-in Terakhir</h5>
                        <a href="checkins/" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                    </div>
                    
                    <?php if (empty($checkins)): ?>
                        <div class="alert alert-info">Anda belum melakukan check-in. <a href="/restaurants.php" class="alert-link">Jelajahi tempat kuliner</a> dan lakukan check-in pertama Anda!</div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($checkins as $checkin): ?>
                                <a href="../restaurant-detail.php?id=<?= $checkin['restaurant_id'] ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?= $checkin['restaurant_name'] ?></h6>
                                        <small><?= date('d M Y', strtotime($checkin['checkin_date'])) ?></small>
                                    </div>
                                    <small class="text-muted"><?= date('H:i', strtotime($checkin['checkin_date'])) ?></small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>