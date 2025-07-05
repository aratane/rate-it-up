<?php
require_once '../../includes/auth_check.php';
$page_title = "Tambah Pengguna - Rate It Up";
include '../../includes/header.php';
include '../../config/functions.php';

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = sanitize($_POST['password']);
    $password_confirm = sanitize($_POST['password_confirm']);
    $full_name = sanitize($_POST['full_name']);
    $role = sanitize($_POST['role']);

    // Validasi
    if (empty($username)) {
        $errors['username'] = 'Username harus diisi';
    } elseif (strlen($username) < 4) {
        $errors['username'] = 'Username minimal 4 karakter';
    } else {
        // Cek apakah username sudah ada
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $result = $stmt->fetch();
        if ($result['count'] > 0) {
            $errors['username'] = 'Username sudah digunakan';
        }
    }

    if (empty($email)) {
        $errors['email'] = 'Email harus diisi';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email tidak valid';
    } else {
        // Cek apakah email sudah ada
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $result = $stmt->fetch();
        if ($result['count'] > 0) {
            $errors['email'] = 'Email sudah digunakan';
        }
    }

    if (empty($password)) {
        $errors['password'] = 'Password harus diisi';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Password minimal 6 karakter';
    } elseif ($password !== $password_confirm) {
        $errors['password_confirm'] = 'Password tidak sama';
    }

    if (empty($full_name)) {
        $errors['full_name'] = 'Nama lengkap harus diisi';
    }

    if (empty($role)) {
        $errors['role'] = 'Role harus dipilih';
    }

    // Handle file upload
    $profile_picture = 'default.jpg';
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadFile($_FILES['profile_picture'], '../../../assets/images/uploads/profiles/');
        if ($upload_result['success']) {
            $profile_picture = basename($upload_result['path']);
        } else {
            $errors['profile_picture'] = $upload_result['message'];
        }
    }

    // Jika tidak ada error, simpan ke database
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, profile_picture, role) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$username, $email, $hashed_password, $full_name, $profile_picture, $role])) {
            $success = 'Pengguna berhasil ditambahkan!';
            $_POST = []; // Clear form
        } else {
            $errors['general'] = 'Terjadi kesalahan. Silakan coba lagi.';
        }
    }
}
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
                    <h2 class="card-title">Tambah Pengguna</h2>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($errors['general'])): ?>
                        <div class="alert alert-danger"><?= $errors['general'] ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>" 
                                           id="username" name="username" value="<?= $_POST['username'] ?? '' ?>" required>
                                    <?php if (isset($errors['username'])): ?>
                                        <div class="invalid-feedback"><?= $errors['username'] ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                                           id="email" name="email" value="<?= $_POST['email'] ?? '' ?>" required>
                                    <?php if (isset($errors['email'])): ?>
                                        <div class="invalid-feedback"><?= $errors['email'] ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" 
                                           id="password" name="password" required>
                                    <?php if (isset($errors['password'])): ?>
                                        <div class="invalid-feedback"><?= $errors['password'] ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password_confirm" class="form-label">Konfirmasi Password</label>
                                    <input type="password" class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>" 
                                           id="password_confirm" name="password_confirm" required>
                                    <?php if (isset($errors['password_confirm'])): ?>
                                        <div class="invalid-feedback"><?= $errors['password_confirm'] ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="mb-3">
                                    <label for="full_name" class="form-label">Nama Lengkap</label>
                                    <input type="text" class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>" 
                                           id="full_name" name="full_name" value="<?= $_POST['full_name'] ?? '' ?>" required>
                                    <?php if (isset($errors['full_name'])): ?>
                                        <div class="invalid-feedback"><?= $errors['full_name'] ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="mb-3">
                                    <label for="role" class="form-label">Role</label>
                                    <select class="form-select <?= isset($errors['role']) ? 'is-invalid' : '' ?>" id="role" name="role" required>
                                        <option value="">Pilih Role</option>
                                        <option value="user" <?= isset($_POST['role']) && $_POST['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                        <option value="admin" <?= isset($_POST['role']) && $_POST['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                    <?php if (isset($errors['role'])): ?>
                                        <div class="invalid-feedback"><?= $errors['role'] ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="profile_picture" class="form-label">Foto Profil</label>
                            <input type="file" class="form-control <?= isset($errors['profile_picture']) ? 'is-invalid' : '' ?>" 
                                   id="profile_picture" name="profile_picture" accept="image/*">
                            <?php if (isset($errors['profile_picture'])): ?>
                                <div class="invalid-feedback"><?= $errors['profile_picture'] ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Tambah Pengguna</button>
                        <a href="index.php" class="btn btn-outline-secondary">Kembali</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>