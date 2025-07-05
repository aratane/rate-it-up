<?php
require_once '../../includes/auth_check.php';
$page_title = "Kelola Review - Rate It Up";
include '../../includes/header.php';
include '../../config/functions.php';

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

$status = isset($_GET['status']) ? sanitize($_GET['status']) : 'pending';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Query untuk mendapatkan total data
$total_query = "SELECT COUNT(*) as total FROM reviews WHERE is_approved = ?";
if ($status === 'all') {
    $total_query = "SELECT COUNT(*) as total FROM reviews";
}

$stmt = $status === 'all' ? $pdo->query($total_query) : $pdo->prepare($total_query);
if ($status !== 'all') {
    $stmt->execute([$status === 'approved' ? 1 : 0]);
}
$total_result = $stmt->fetch();
$total_reviews = $total_result['total'];
$total_pages = ceil($total_reviews / $limit);

// Query untuk mendapatkan data dengan pagination
$query = "SELECT r.*, u.username, u.profile_picture, res.name as restaurant_name, res.id as restaurant_id 
          FROM reviews r 
          JOIN users u ON r.user_id = u.id 
          JOIN restaurants res ON r.restaurant_id = res.id";

if ($status !== 'all') {
    $query .= " WHERE r.is_approved = ?";
}

$query .= " ORDER BY r.created_at DESC LIMIT ? OFFSET ?";

$stmt = $pdo->prepare($query);
if ($status !== 'all') {
    $stmt->bindValue(1, $status === 'approved' ? 1 : 0, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
} else {
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
}
$stmt->execute();
$reviews = $stmt->fetchAll();
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-3">
            <!-- Kartu Profil Admin -->
            <div class="card mb-4">
                <div class="card-body text-center">
                    <img src="../../assets/images/uploads/profiles/<?= $user['profile_picture'] ?>" class="rounded-circle mb-3" width="150" height="150" alt="Profile Picture">
                    <h4><?= $user['full_name'] ?></h4>
                    <p class="text-muted">@<?= $user['username'] ?> (Admin)</p>

                    <!-- Tombol Navigasi -->
                    <a href="../dashboard.php" class="btn btn-outline-secondary btn-sm d-block mb-2 <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <a href="../users/" class="btn btn-outline-secondary btn-sm d-block mb-2 <?= strpos($_SERVER['PHP_SELF'], '/users/') !== false ? 'active' : '' ?>">
                        <i class="bi bi-people"></i> Kelola Pengguna
                    </a>
                    <a href="../restaurants/" class="btn btn-outline-secondary btn-sm d-block mb-2 <?= strpos($_SERVER['PHP_SELF'], '/restaurants/') !== false ? 'active' : '' ?>">
                        <i class="bi bi-shop"></i> Kelola Tempat
                    </a>
                    <a href="index.php" class="btn btn-outline-secondary btn-sm d-block mb-2 <?= strpos($_SERVER['PHP_SELF'], '/reviews/') !== false ? 'active' : '' ?>">
                        <i class="bi bi-star"></i> Kelola Review
                    </a>
                    <a href="../comments/" class="btn btn-outline-secondary btn-sm d-block mb-3 <?= strpos($_SERVER['PHP_SELF'], '/comments/') !== false ? 'active' : '' ?>">
                        <i class="bi bi-chat-left-text"></i> Kelola Komentar
                    </a>

                    <!-- Tombol Logout -->
                    <a href="../../auth/logout.php" class="btn btn-outline-danger btn-sm d-block">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="card-title mb-0">Kelola Review</h2>
                        <div class="btn-group">
                            <a href="?status=all" class="btn btn-sm btn-outline-secondary <?= $status === 'all' ? 'active' : '' ?>">Semua</a>
                            <a href="?status=approved" class="btn btn-sm btn-outline-success <?= $status === 'approved' ? 'active' : '' ?>">Disetujui</a>
                            <a href="?status=pending" class="btn btn-sm btn-outline-warning <?= $status === 'pending' ? 'active' : '' ?>">Menunggu</a>
                        </div>
                    </div>
                    
                    <?php if (empty($reviews)): ?>
                        <div class="alert alert-info">Tidak ada review yang ditemukan.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Tempat</th>
                                        <th>Rating</th>
                                        <th>Status</th>
                                        <th>Tanggal</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reviews as $review): ?>
                                        <tr>
                                            <td>
                                                <img src="../../assets/images/uploads/profiles/<?= $review['profile_picture'] ?>" class="rounded-circle me-2" width="30" height="30" alt="<?= $review['username'] ?>">
                                                <?= $review['username'] ?>
                                            </td>
                                            <td><?= $review['restaurant_name'] ?></td>
                                            <td>
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <?php if ($i <= $review['rating']): ?>
                                                        <i class="bi bi-star-fill text-warning"></i>
                                                    <?php else: ?>
                                                        <i class="bi bi-star text-warning"></i>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </td>
                                            <td>
                                                <?php if ($review['is_approved']): ?>
                                                    <span class="badge bg-success">Disetujui</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Menunggu</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date('d M Y', strtotime($review['created_at'])) ?></td>
                                            <td>
                                                <a href="../../restaurant-detail.php?id=<?= $review['restaurant_id'] ?>#review-<?= $review['id'] ?>" class="btn btn-sm btn-outline-primary me-1">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <?php if (!$review['is_approved']): ?>
                                                    <a href="approve.php?id=<?= $review['id'] ?>" class="btn btn-sm btn-outline-success me-1">
                                                        <i class="bi bi-check-circle"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="delete.php?id=<?= $review['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus review ini?')">
                                                    <i class="bi bi-trash"></i>
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
                                        <a class="page-link" href="?status=<?= $status ?>&page=<?= $page - 1 ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?status=<?= $status ?>&page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?status=<?= $status ?>&page=<?= $page + 1 ?>" aria-label="Next">
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