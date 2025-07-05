<?php
require_once '../../../includes/auth_check.php';
include '../../../config/functions.php';

if (!isset($_GET['id'])) {
    redirect('index.php');
}

$user_id = (int)$_GET['id'];

// Tidak boleh menghapus diri sendiri
if ($user_id == $_SESSION['user_id']) {
    $_SESSION['error_message'] = 'Anda tidak dapat menghapus akun sendiri.';
    redirect('index.php');
}

// Get user data to delete profile picture
$user = getUserById($user_id);
if (!$user) {
    redirect('index.php');
}

// Hapus foto profil jika bukan default
if ($user['profile_picture'] !== 'default.jpg') {
    @unlink('../../../assets/images/uploads/profiles/' . $user['profile_picture']);
}

// Hapus user dari database
$stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
$stmt->execute([$user_id]);

$_SESSION['success_message'] = 'Pengguna berhasil dihapus.';
redirect('index.php');
?>