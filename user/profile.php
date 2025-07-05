<?php
require_once '../includes/auth_check.php';
$page_title = "Profil User - Rate It Up";
include '../includes/header.php';
include '../config/functions.php';

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasi
    if (empty($full_name)) {
        $errors['full_name'] = 'Nama lengkap harus diisi';
    }
    
    if (empty($email)) {
        $errors['email'] = 'Email harus diisi';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email tidak valid';
    } else {
        // Cek apakah email sudah digunakan oleh user lain
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        $result = $stmt->fetch();
        if ($result['count'] > 0) {
            $errors['email'] = 'Email sudah digunakan';
        }
    }
    
    // Jika ada perubahan password
    if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
        if (empty($current_password)) {
            $errors['current_password'] = 'Password saat ini harus diisi';
        } elseif (!password_verify($current_password, $user['password'])) {
            $errors['current_password'] = 'Password saat ini salah';
        }
        
        if (empty($new_password)) {
            $errors['new_password'] = 'Password baru harus diisi';
        } elseif (strlen($new_password) < 6) {
            $errors['new_password'] = 'Password minimal 6 karakter';
        }
        
        if ($new_password !== $confirm_password) {
            $errors['confirm_password'] = 'Konfirmasi password tidak sama';
        }
    }
    
    // Handle file upload
    $profile_picture = $user['profile_picture'];
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadFile($_FILES['profile_picture'], '../assets/images/uploads/profiles/');
        if ($upload_result['success']) {
            $profile_picture = basename($upload_result['path']);
            
            // Hapus foto lama jika bukan default
            if ($user['profile_picture'] !== 'default.jpg') {
                @unlink('../assets/images/uploads/profiles/' . $user['profile_picture']);
            }
        } else {
            $errors['profile_picture'] = $upload_result['message'];
        }
    }
    
    // Jika tidak ada error, update data
    if (empty($errors)) {
        $password_sql = '';
        $params = [$full_name, $email, $profile_picture, $user_id];
        
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $password_sql = ', password = ?';
            array_splice($params, 3, 0, $hashed_password);
        }
        
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, profile_picture = ? $password_sql WHERE id = ?");
        if ($stmt->execute($params)) {
            $_SESSION['full_name'] = $full_name;
            $success = 'Profil berhasil diperbarui!';
            $user = getUserById($user_id); // Refresh user data
        } else {
            $errors['general'] = 'Terjadi kesalahan. Silakan coba lagi.';
        }
    }
}
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
                    <a href="auth/logout.php" class="btn btn-outline-danger btn-sm d-block">
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
            <div class="card">
                <div class="card-body">
                    <h2 class="card-title">Profil Saya</h2>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($errors['general'])): ?>
                        <div class="alert alert-danger"><?= $errors['general'] ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row mb-3">
                            <div class="col-md-4 text-center">
                                <img src="..//assets/images/uploads/profiles/<?= $user['profile_picture'] ?>" class="rounded-circle mb-3" width="150" height="150" alt="Profile Picture">
                                <div class="mb-3">
                                    <label for="profile_picture" class="form-label">Ubah Foto Profil</label>
                                    <input class="form-control <?= isset($errors['profile_picture']) ? 'is-invalid' : '' ?>" type="file" id="profile_picture" name="profile_picture" accept="image/*">
                                    <?php if (isset($errors['profile_picture'])): ?>
                                        <div class="invalid-feedback"><?= $errors['profile_picture'] ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" value="<?= $user['username'] ?>" readonly>
                                </div>
                                <div class="mb-3">
                                    <label for="full_name" class="form-label">Nama Lengkap</label>
                                    <input type="text" class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>" id="full_name" name="full_name" value="<?= $user['full_name'] ?>" required>
                                    <?php if (isset($errors['full_name'])): ?>
                                        <div class="invalid-feedback"><?= $errors['full_name'] ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= $user['email'] ?>" required>
                                    <?php if (isset($errors['email'])): ?>
                                        <div class="invalid-feedback"><?= $errors['email'] ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <h5 class="mt-4 mb-3">Ubah Password</h5>
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Password Saat Ini</label>
                            <input type="password" class="form-control <?= isset($errors['current_password']) ? 'is-invalid' : '' ?>" id="current_password" name="current_password">
                            <?php if (isset($errors['current_password'])): ?>
                                <div class="invalid-feedback"><?= $errors['current_password'] ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Password Baru</label>
                            <input type="password" class="form-control <?= isset($errors['new_password']) ? 'is-invalid' : '' ?>" id="new_password" name="new_password">
                            <?php if (isset($errors['new_password'])): ?>
                                <div class="invalid-feedback"><?= $errors['new_password'] ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>" id="confirm_password" name="confirm_password">
                            <?php if (isset($errors['confirm_password'])): ?>
                                <div class="invalid-feedback"><?= $errors['confirm_password'] ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>