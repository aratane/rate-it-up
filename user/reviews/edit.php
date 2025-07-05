<?php
require_once '../../includes/auth_check.php';
$page_title = "Edit Review - Rate It Up";
include '../../includes/header.php';
include '../../config/functions.php';

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

if (!isset($_GET['id'])) {
    redirect('/user/reviews/');
}

$review_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Get review data
$stmt = $pdo->prepare("SELECT r.*, res.name as restaurant_name, res.id as restaurant_id 
                      FROM reviews r 
                      JOIN restaurants res ON r.restaurant_id = res.id 
                      WHERE r.id = ? AND r.user_id = ?");
$stmt->execute([$review_id, $user_id]);
$review = $stmt->fetch();

if (!$review) {
    echo '<div class="container mt-5"><div class="alert alert-danger">Review tidak ditemukan atau Anda tidak memiliki akses.</div></div>';
    include '../../../includes/footer.php';
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = (int)$_POST['rating'];
    $content = sanitize($_POST['content']);
    
    if ($rating < 1 || $rating > 5) {
        $errors['rating'] = 'Rating harus antara 1-5';
    }
    
    if (empty($content)) {
        $errors['content'] = 'Review tidak boleh kosong';
    } elseif (strlen($content) < 10) {
        $errors['content'] = 'Review terlalu pendek';
    }
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE reviews SET rating = ?, content = ?, is_approved = ? WHERE id = ?");
        $is_approved = isAdmin() ? 1 : 0; // Admin reviews are auto-approved
        $stmt->execute([$rating, $content, $is_approved, $review_id]);
        
        $_SESSION['success_message'] = isAdmin() ? 'Review Anda telah diperbarui.' : 'Review Anda telah diperbarui dan menunggu persetujuan admin.';
        redirect("/restaurant-detail.php?id={$review['restaurant_id']}#review-$review_id");
    }
}
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
                            <a href="index.php" class="text-decoration-none">Review Saya</a>
                        </li>
                        <li class="list-group-item">
                            <a href="../checkins/" class="text-decoration-none">Check-in Saya</a>
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
                    <h2 class="card-title">Edit Review untuk <?= $review['restaurant_name'] ?></h2>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">Silakan perbaiki error berikut:</div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Rating</label>
                            <div class="rating-input">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="bi bi-star-fill star-icon" data-value="<?= $i ?>" style="cursor: pointer; font-size: 1.5rem;"></i>
                                <?php endfor; ?>
                                <input type="hidden" name="rating" id="rating-value" value="<?= $review['rating'] ?>" required>
                            </div>
                            <?php if (isset($errors['rating'])): ?>
                                <small class="text-danger"><?= $errors['rating'] ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label for="content" class="form-label">Review Anda</label>
                            <textarea class="form-control <?= isset($errors['content']) ? 'is-invalid' : '' ?>" id="content" name="content" rows="8" required><?= $_POST['content'] ?? $review['content'] ?></textarea>
                            <?php if (isset($errors['content'])): ?>
                                <div class="invalid-feedback"><?= $errors['content'] ?></div>
                            <?php endif; ?>
                        </div>
                        <button type="submit" class="btn btn-primary">Perbarui Review</button>
                        <a href="/restaurant-detail.php?id=<?= $review['restaurant_id'] ?>#review-<?= $review_id ?>" class="btn btn-outline-secondary">Batal</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Star rating interaction
document.querySelectorAll('.star-icon').forEach(star => {
    star.addEventListener('click', () => {
        const value = star.getAttribute('data-value');
        document.getElementById('rating-value').value = value;
        
        // Update star display
        document.querySelectorAll('.star-icon').forEach(s => {
            if (s.getAttribute('data-value') <= value) {
                s.classList.add('text-warning');
            } else {
                s.classList.remove('text-warning');
            }
        });
    });
});

// Initialize rating
document.getElementById('rating-value').value = <?= $review['rating'] ?>;
document.querySelectorAll('.star-icon').forEach(star => {
    if (star.getAttribute('data-value') <= <?= $review['rating'] ?>) {
        star.classList.add('text-warning');
    }
});
</script>

<?php include '../../includes/footer.php'; ?>