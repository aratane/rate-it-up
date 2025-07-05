<?php
require_once '../../includes/auth_check.php';
$page_title = "Kelola Pengguna - Rate It Up";
include '../../includes/header.php';
include '../../config/functions.php';

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Query untuk mendapatkan total data
$total_query = "SELECT COUNT(*) as total FROM users";
$stmt = $pdo->query($total_query);
$total_result = $stmt->fetch();
$total_users = $total_result['total'];
$total_pages = ceil($total_users / $limit);

// Query untuk mendapatkan data dengan pagination
$query = "SELECT * FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($query);
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll();
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
                    <a href="index.php" class="btn btn-outline-secondary btn-sm d-block mb-2 <?= strpos($_SERVER['PHP_SELF'], '/users/') !== false ? 'active' : '' ?>">
                        <i class="bi bi-people"></i> Kelola Pengguna
                    </a>
                    <a href="../restaurants/" class="btn btn-outline-secondary btn-sm d-block mb-2 <?= strpos($_SERVER['PHP_SELF'], '/restaurants/') !== false ? 'active' : '' ?>">
                        <i class="bi bi-shop"></i> Kelola Tempat
                    </a>
                    <a href="../reviews/" class="btn btn-outline-secondary btn-sm d-block mb-2 <?= strpos($_SERVER['PHP_SELF'], '/reviews/') !== false ? 'active' : '' ?>">
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
                        <h2 class="card-title mb-0">Kelola Pengguna</h2>
                        <a href="add.php" class="btn btn-primary">Tambah Pengguna</a>
                    </div>
                    
                    <?php if (empty($users)): ?>
                        <div class="alert alert-info">Belum ada pengguna terdaftar.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Nama Lengkap</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Tanggal Daftar</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td>
                                                <img src="../../assets/images/uploads/profiles/<?= $user['profile_picture'] ?>" class="rounded-circle me-2" width="30" height="30" alt="<?= $user['username'] ?>">
                                                <?= $user['username'] ?>
                                            </td>
                                            <td><?= $user['full_name'] ?></td>
                                            <td><?= $user['email'] ?></td>
                                            <td>
                                                <?php if ($user['role'] === 'admin'): ?>
                                                    <span class="badge bg-danger">Admin</span>
                                                <?php else: ?>
                                                    <span class="badge bg-primary">User</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                                            <td>
                                                <a href="edit.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-secondary me-1">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="delete.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus pengguna ini?')">
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