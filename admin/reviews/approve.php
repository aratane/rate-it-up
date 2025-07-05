<?php
require_once '../../includes/auth_check.php';
include '../../config/functions.php';

if (!isset($_GET['id'])) {
    redirect('index.php');
}

$review_id = (int)$_GET['id'];

// Approve the review
$stmt = $pdo->prepare("UPDATE reviews SET is_approved = 1 WHERE id = ?");
$stmt->execute([$review_id]);

$_SESSION['success_message'] = 'Review telah disetujui.';
redirect('index.php');
?>