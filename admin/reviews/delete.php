<?php
require_once '../../includes/auth_check.php';
include '../../config/functions.php';

if (!isset($_GET['id'])) {
    redirect('index.php');
}

$review_id = (int)$_GET['id'];

// Delete the review
$stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
$stmt->execute([$review_id]);

$_SESSION['success_message'] = 'Review telah dihapus.';
redirect('index.php');
?>