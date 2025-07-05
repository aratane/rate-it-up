<?php
require_once '../../includes/auth_check.php';
$page_title = "Check-in Saya - Rate It Up";
include '../../includes/header.php';
include '../../config/functions.php';

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Query untuk mendapatkan total data
$total_query = "SELECT COUNT(*) as total FROM checkins WHERE user_id = ?";
$stmt = $pdo->prepare($total_query);
$stmt->execute([$user_id]);
$total_result = $stmt->fetch();
$total_checkins = $total_result['total'];
$total_pages = ceil($total_checkins / $limit);

// Query untuk mendapatkan data dengan pagination
$query = "SELECT c.*, res.name as restaurant_name, res.id as restaurant_id 
          FROM checkins c 
          JOIN restaurants res ON c.restaurant_id = res.id 
          WHERE c.user_id = ? 
          ORDER BY c.checkin_date DESC 
          LIMIT ? OFFSET ?";

$stmt = $pdo->prepare($query);
$stmt->bindValue(1, $user_id, PDO::PARAM_INT);
$stmt->bindValue(2, $limit, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$checkins = $stmt->fetchAll();
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <img src="../../assets/images/uploads/profiles/<?= $user['profile_picture'] ?>" class="rounded-circle mb-3" width="150" height="150" alt="Profile Picture">
                    <h4><?= $user['full_name'] ?></h4>
                    <p class="text-muted">@<?= $user['username'] ?></p>

                    <!-- Tombol Dashboard -->
                    <a href="../dashboard.php" class="btn btn-outline-secondary btn-sm d-block mb-2">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>

                    <!-- Tombol Edit Profil -->
                    <a href="../profile.php" class="btn btn-outline-primary btn-sm d-block mb-2">
                        <i class="bi bi-pencil-square"></i> Edit Profil
                    </a>

                    <!-- Tombol Logout -->
                    <a href="../../auth/logout.php" class="btn btn-outline-danger btn-sm d-block">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Menu</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <a href="../reviews/" class="text-decoration-none">Review Saya</a>
                        </li>
                        <li class="list-group-item">
                            <a href="index.php" class="text-decoration-none">Check-in Saya</a>
                        </li>
                        <li class="list-group-item">
                            <a href="../../restaurants.php" class="text-decoration-none">Jelajahi Tempat</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="card-title mb-0">Check-in Saya</h2>
                        <a href="../../restaurants.php" class="btn btn-primary">Check-in Baru</a>
                    </div>
                    
                    <?php if (empty($checkins)): ?>
                        <div class="alert alert-info">Anda belum melakukan check-in. <a href="/restaurants.php" class="alert-link">Jelajahi tempat kuliner</a> dan lakukan check-in pertama Anda!</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Tempat</th>
                                        <th>Tanggal</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($checkins as $checkin): ?>
                                        <tr>
                                            <td>
                                                <a href="/restaurant-detail.php?id=<?= $checkin['restaurant_id'] ?>" class="text-decoration-none">
                                                    <?= $checkin['restaurant_name'] ?>
                                                </a>
                                            </td>
                                            <td><?= date('d M Y H:i', strtotime($checkin['checkin_date'])) ?></td>
                                            <td>
                                                <a href="../../restaurant-detail.php?id=<?= $checkin['restaurant_id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i> Lihat
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
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
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>