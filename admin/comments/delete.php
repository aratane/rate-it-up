<?php
require_once '../../includes/auth_check.php';
include '../../config/functions.php';

if (!isset($_GET['id'])) {
    redirect('index.php');
}

$comment_id = (int)$_GET['id'];

// Delete the comment
$stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
$stmt->execute([$comment_id]);

$_SESSION['success_message'] = 'Komentar telah dihapus.';
redirect('index.php');
?>