<?php
require_once '../../includes/auth_check.php';
$page_title = "Tulis Review - Rate It Up";
include '../../includes/header.php';
include '../../config/functions.php';

if (!isset($_GET['restaurant_id'])) {
    redirect('../../restaurants.php');
}

$restaurant_id = (int)$_GET['restaurant_id'];
$restaurant = getRestaurantById($restaurant_id);

if (!$restaurant) {
    echo '<div class="container mt-5"><div class="alert alert-danger">Tempat kuliner tidak ditemukan.</div></div>';
    include '..../includes/footer.php';
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
        $stmt = $pdo->prepare("INSERT INTO reviews (user_id, restaurant_id, rating, content, is_approved) VALUES (?, ?, ?, ?, ?)");
        $is_approved = isAdmin() ? 1 : 0; // Admin reviews are auto-approved
        $stmt->execute([$_SESSION['user_id'], $restaurant_id, $rating, $content, $is_approved]);
        
        $_SESSION['success_message'] = isAdmin() ? 'Review Anda telah diposting.' : 'Review Anda telah dikirim dan menunggu persetujuan admin.';
        redirect("/restaurant-detail.php?id=$restaurant_id");
    }
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-3">
            <?php include '../menu.php'; ?>
        </div>
        
        <div class="col-md-9">
            <div class="card">
                <div class="card-body">
                    <h2 class="card-title">Tulis Review untuk <?= $restaurant['name'] ?></h2>
                    
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
                                <input type="hidden" name="rating" id="rating-value" required>
                            </div>
                            <?php if (isset($errors['rating'])): ?>
                                <small class="text-danger"><?= $errors['rating'] ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label for="content" class="form-label">Review Anda</label>
                            <textarea class="form-control <?= isset($errors['content']) ? 'is-invalid' : '' ?>" id="content" name="content" rows="8" required><?= $_POST['content'] ?? '' ?></textarea>
                            <?php if (isset($errors['content'])): ?>
                                <div class="invalid-feedback"><?= $errors['content'] ?></div>
                            <?php endif; ?>
                        </div>
                        <button type="submit" class="btn btn-primary">Kirim Review</button>
                        <a href="/restaurant-detail.php?id=<?= $restaurant_id ?>" class="btn btn-outline-secondary">Batal</a>
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

// Initialize rating if there was an error
<?php if (isset($_POST['rating'])): ?>
    document.getElementById('rating-value').value = <?= $_POST['rating'] ?>;
    document.querySelectorAll('.star-icon').forEach(star => {
        if (star.getAttribute('data-value') <= <?= $_POST['rating'] ?>) {
            star.classList.add('text-warning');
        }
    });
<?php endif; ?>
</script>

<?php include '../../../includes/footer.php'; ?>