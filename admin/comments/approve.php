<?php
require_once '../../includes/auth_check.php';
include '../../config/functions.php';

if (!isset($_GET['id'])) {
    redirect('index.php');
}

$comment_id = (int)$_GET['id'];

// Approve the comment
$stmt = $pdo->prepare("UPDATE comments SET is_approved = 1 WHERE id = ?");
$stmt->execute([$comment_id]);

$_SESSION['success_message'] = 'Komentar telah disetujui.';
redirect('index.php');
?>