<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

if (isLoggedIn()) {
    redirect(isAdmin() ? '../admin/dashboard.php' : '../user/dashboard.php');
}

$errors = [];
$success = '';

// Generate CAPTCHA jika belum ada di session
if (!isset($_SESSION['captcha'])) {
    generateCaptcha();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = sanitize($_POST['password']);
    $password_confirm = sanitize($_POST['password_confirm']);
    $full_name = sanitize($_POST['full_name']);

    // Validasi
    if (empty($username)) {
        $errors['username'] = 'Username harus diisi';
    } elseif (strlen($username) < 4) {
        $errors['username'] = 'Username minimal 4 karakter';
    } else {
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

    // Validasi CAPTCHA
    if (empty($_POST['captcha_answer'])) {
        $errors['captcha'] = 'Jawaban CAPTCHA harus diisi';
    } elseif ($_POST['captcha_answer'] != $_SESSION['captcha']['answer']) {
        $errors['captcha'] = 'Jawaban CAPTCHA salah';
    }

    // Jika tidak ada error, simpan ke database
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $profile_picture = 'default.jpg';

        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, profile_picture, role) VALUES (?, ?, ?, ?, ?, 'user')");
        if ($stmt->execute([$username, $email, $hashed_password, $full_name, $profile_picture])) {
            $success = 'Pendaftaran berhasil! Silakan login.';
            $_POST = [];
            generateCaptcha(); // Regenerate CAPTCHA setelah berhasil
        } else {
            $errors['general'] = 'Terjadi kesalahan. Silakan coba lagi.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Rate It Up</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Daftar Akun</h2>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= $success ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($errors['general'])): ?>
                            <div class="alert alert-danger"><?= $errors['general'] ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
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
                                <label for="full_name" class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>" 
                                       id="full_name" name="full_name" value="<?= $_POST['full_name'] ?? '' ?>" required>
                                <?php if (isset($errors['full_name'])): ?>
                                    <div class="invalid-feedback"><?= $errors['full_name'] ?></div>
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
                            <div class="mb-3">
                                <label for="password_confirm" class="form-label">Konfirmasi Password</label>
                                <input type="password" class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>" 
                                       id="password_confirm" name="password_confirm" required>
                                <?php if (isset($errors['password_confirm'])): ?>
                                    <div class="invalid-feedback"><?= $errors['password_confirm'] ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3 d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <label for="captcha_answer" class="form-label">
                                        CAPTCHA: <?= $_SESSION['captcha']['question'] ?> = ?
                                    </label>
                                    <input type="number" class="form-control <?= isset($errors['captcha']) ? 'is-invalid' : '' ?>" 
                                           id="captcha_answer" name="captcha_answer" required>
                                </div>
                                <button type="button" class="btn btn-outline-secondary ms-2" onclick="refreshCaptcha()" style="margin-top: 1.7rem;">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>
                            </div>
                            <?php if (isset($errors['captcha'])): ?>
                                <div class="invalid-feedback d-block"><?= $errors['captcha'] ?></div>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary w-100">Daftar</button>
                        </form>
                        
                        <div class="mt-3 text-center">
                            <p>Sudah punya akun? <a href="login.php">Login disini</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function refreshCaptcha() {
        fetch('../config/refresh_captcha.php')
            .then(response => response.json())
            .then(data => {
                document.querySelector('label[for="captcha_answer"]').innerHTML = 
                    `CAPTCHA: ${data.question} = ?`;
            });
    }
    </script>
</body>
</html>