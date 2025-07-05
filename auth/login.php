<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

if (isLoggedIn()) {
    redirect(isAdmin() ? '../admin/dashboard.php' : '../user/dashboard.php');
}

// Generate CAPTCHA jika belum ada di session
if (!isset($_SESSION['captcha'])) {
    generateCaptcha();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = sanitize($_POST['password']);

    // Validasi CAPTCHA
    if (empty($_POST['captcha_answer'])) {
        $error = 'Jawaban CAPTCHA harus diisi';
    } elseif ($_POST['captcha_answer'] != $_SESSION['captcha']['answer']) {
        $error = 'Jawaban CAPTCHA salah';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            
            // Regenerate CAPTCHA setelah login berhasil
            generateCaptcha();
            
            redirect(isAdmin() ? '../admin/dashboard.php' : '../user/dashboard.php');
        } else {
            $error = 'Username atau password salah!';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Rate It Up</title>
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
                        <h2 class="card-title text-center mb-4">Login</h2>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="mb-3 d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <label for="captcha_answer" class="form-label">
                                        CAPTCHA: <?= $_SESSION['captcha']['question'] ?> = ?
                                    </label>
                                    <input type="number" class="form-control" id="captcha_answer" name="captcha_answer" required>
                                </div>
                                <button type="button" class="btn btn-outline-secondary ms-2" onclick="refreshCaptcha()" style="margin-top: 1.7rem;">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </form>
                        
                        <div class="mt-3 text-center">
                            <p>Belum punya akun? <a href="register.php">Daftar disini</a></p>
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