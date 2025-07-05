<?php
require_once '../../includes/auth_check.php';
include '../../config/functions.php';

if (!isset($_GET['id'])) {
    redirect('/user/reviews/');
}

$review_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Get review data to redirect back to restaurant page
$stmt = $pdo->prepare("SELECT restaurant_id FROM reviews WHERE id = ? AND user_id = ?");
$stmt->execute([$review_id, $user_id]);
$review = $stmt->fetch();

if (!$review) {
    $_SESSION['error_message'] = 'Review tidak ditemukan atau Anda tidak memiliki akses.';
    redirect('/user/reviews/');
}

// Delete the review
$stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
$stmt->execute([$review_id]);

$_SESSION['success_message'] = 'Review berhasil dihapus.';
redirect("../../restaurant-detail.php?id={$review['restaurant_id']}");
?>