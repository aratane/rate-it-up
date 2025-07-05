<?php
require_once '../../../includes/auth_check.php';
include '../../../config/functions.php';

if (!isset($_GET['id'])) {
    redirect('index.php');
}

$restaurant_id = (int)$_GET['id'];

// Get restaurant photos to delete files
$photos = getRestaurantPhotos($restaurant_id);

// Begin transaction
$pdo->beginTransaction();

try {
    // Delete restaurant photos
    foreach ($photos as $photo) {
        @unlink('../../../assets/images/uploads/restaurants/' . $photo['photo_path']);
    }
    
    // Delete restaurant from database
    $stmt = $pdo->prepare("DELETE FROM restaurants WHERE id = ?");
    $stmt->execute([$restaurant_id]);
    
    $pdo->commit();
    $_SESSION['success_message'] = 'Tempat kuliner berhasil dihapus.';
} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['error_message'] = 'Terjadi kesalahan. Silakan coba lagi.';
}

redirect('index.php');
?>