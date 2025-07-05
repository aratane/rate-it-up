<?php
require_once 'database.php';

// Fungsi untuk mencegah XSS
if (!function_exists('sanitize')) {
    function sanitize($data) {
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
}

// Fungsi untuk redirect
if (!function_exists('redirect')) {
    function redirect($location) {
        header("Location: $location");
        exit();
    }
}

// Fungsi untuk mengecek apakah user sudah login
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

// Fungsi untuk mengecek role user
if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
}

// Fungsi untuk mendapatkan data user
if (!function_exists('getUserById')) {
    function getUserById($id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}

if (!function_exists('generateCaptcha')) {
    function generateCaptcha() {
        $num1 = rand(1, 10);
        $num2 = rand(1, 10);
        $_SESSION['captcha'] = [
            'question' => "$num1 + $num2",
            'answer' => $num1 + $num2
        ];
    }
}

// Fungsi untuk upload file
if (!function_exists('uploadFile')) {
    function uploadFile($file, $targetDir, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif']) {
        // Ensure directory ends with a slash
        $targetDir = rtrim($targetDir, '/') . '/';

        // Create directory if it doesn't exist
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0777, true)) {
                return ['success' => false, 'message' => 'Gagal membuat direktori tujuan.'];
            }
        }

        $fileName = basename($file['name']);
        $targetPath = $targetDir . uniqid() . '_' . $fileName;
        $fileType = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));

        // Check if file is a valid type
        if (!in_array($fileType, $allowedTypes)) {
            return ['success' => false, 'message' => 'Hanya file JPG, JPEG, PNG & GIF yang diperbolehkan.'];
        }

        // Check file size (max 5MB)
        if ($file['size'] > 5000000) {
            return ['success' => false, 'message' => 'Ukuran file terlalu besar. Maksimal 5MB.'];
        }

        // Upload file
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return ['success' => true, 'path' => $targetPath];
        } else {
            return ['success' => false, 'message' => 'Terjadi kesalahan saat mengupload file.'];
        }
    }
}

// Fungsi untuk mendapatkan semua tempat kuliner
if (!function_exists('getAllRestaurants')) {
    function getAllRestaurants($limit = null) {
        global $pdo;
        $query = "SELECT r.*, 
                (SELECT photo_path FROM restaurant_photos WHERE restaurant_id = r.id AND is_featured = 1 LIMIT 1) as featured_photo,
                (SELECT AVG(rating) FROM reviews WHERE restaurant_id = r.id AND is_approved = 1) as avg_rating
                FROM restaurants r ORDER BY r.created_at DESC";
        
        if ($limit) {
            $query .= " LIMIT $limit";
        }
        
        $stmt = $pdo->query($query);
        return $stmt->fetchAll();
    }
}

// Fungsi untuk mendapatkan detail tempat kuliner
if (!function_exists('getRestaurantById')) {
    function getRestaurantById($id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT r.*, u.username as created_by_username 
                            FROM restaurants r 
                            JOIN users u ON r.created_by = u.id 
                            WHERE r.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}

// Fungsi untuk mendapatkan foto tempat kuliner
if (!function_exists('getRestaurantPhotos')) {
    function getRestaurantPhotos($restaurant_id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM restaurant_photos WHERE restaurant_id = ?");
        $stmt->execute([$restaurant_id]);
        return $stmt->fetchAll();
    }
}

// Fungsi untuk mendapatkan review tempat kuliner
if (!function_exists('getRestaurantReviews')) {
    function getRestaurantReviews($restaurant_id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT r.*, u.username, u.profile_picture 
                            FROM reviews r 
                            JOIN users u ON r.user_id = u.id 
                            WHERE r.restaurant_id = ? AND r.is_approved = 1 
                            ORDER BY r.created_at DESC");
        $stmt->execute([$restaurant_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}

// Fungsi untuk mendapatkan komentar review
if (!function_exists('getReviewComments')) {
    function getReviewComments($review_id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT c.*, u.username, u.profile_picture 
                            FROM comments c 
                            JOIN users u ON c.user_id = u.id 
                            WHERE c.review_id = ? AND c.is_approved = 1 
                            ORDER BY c.created_at ASC");
        $stmt->execute([$review_id]);
        return $stmt->fetchAll();
    }
}

// Fungsi untuk mengecek apakah user sudah check-in di tempat tertentu
if (!function_exists('hasUserCheckedIn')) {
    function hasUserCheckedIn($user_id, $restaurant_id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM checkins WHERE user_id = ? AND restaurant_id = ?");
        $stmt->execute([$user_id, $restaurant_id]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }
}

// Fungsi untuk mendapatkan review terbaru
if (!function_exists('getLatestReviews')) {
    function getLatestReviews($limit = 5) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT r.*, u.username, u.profile_picture, res.name as restaurant_name, res.id as restaurant_id 
                            FROM reviews r 
                            JOIN users u ON r.user_id = u.id 
                            JOIN restaurants res ON r.restaurant_id = res.id 
                            WHERE r.is_approved = 1 
                            ORDER BY r.created_at DESC 
                            LIMIT ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

// Fungsi untuk mendapatkan restoran dengan rating tertinggi
if (!function_exists('getTopRatedRestaurants')) {
    function getTopRatedRestaurants($limit = 5) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT r.*, 
                            (SELECT photo_path FROM restaurant_photos WHERE restaurant_id = r.id AND is_featured = 1 LIMIT 1) as featured_photo,
                            (SELECT AVG(rating) FROM reviews WHERE restaurant_id = r.id AND is_approved = 1) as avg_rating
                            FROM restaurants r 
                            HAVING avg_rating IS NOT NULL 
                            ORDER BY avg_rating DESC 
                            LIMIT ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

// Fungsi untuk menghitung rata-rata rating restoran
if (!function_exists('getRestaurantRatingData')) {
    function getRestaurantRatingData($restaurant_id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE restaurant_id = ? AND is_approved = 1");
        $stmt->execute([$restaurant_id]);
        return $stmt->fetch();
    }
}

// Fungsi untuk menangani proses check-in
if (!function_exists('handleCheckIn')) {
    function handleCheckIn($user_id, $restaurant_id) {
        global $pdo;
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO checkins (user_id, restaurant_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $restaurant_id]);
            
            hasUserCheckedIn($user_id, 'checkin'); 

            $pdo->commit();
            return ['success' => true, 'message' => 'Anda telah berhasil Check-in!'];
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Check-in error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal melakukan check-in. Silakan coba lagi.'];
        }
    }
}

// Fungsi untuk menangani submit review
if (!function_exists('handleReviewSubmission')) {
    function handleReviewSubmission($user_id, $restaurant_id, $rating, $content) {
        global $pdo;
        
        $errors = [];
        
        if ($rating < 1 || $rating > 5) {
            $errors['rating'] = 'Rating harus antara 1-5';
        }
        
        if (empty($content)) {
            $errors['content'] = 'Review tidak boleh kosong';
        } elseif (strlen($content) < 10) {
            $errors['content'] = 'Review terlalu pendek (minimal 10 karakter)';
        }
        
        if (empty($errors)) {
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("INSERT INTO reviews (user_id, restaurant_id, rating, content, is_approved) VALUES (?, ?, ?, ?, ?)");
                $is_approved = isAdmin() ? 1 : 0; 
                $stmt->execute([$user_id, $restaurant_id, $rating, $content, $is_approved]);
                
              
                hasUserCheckedIn($user_id, 'review'); 

                $pdo->commit();
                return [
                    'success' => true, 
                    'message' => isAdmin() ? 'Review Anda telah diposting.' : 'Review Anda telah dikirim dan menunggu persetujuan admin.'
                ];
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Review submission error: " . $e->getMessage());
                return ['success' => false, 'message' => 'Gagal memposting review. Silakan coba lagi.'];
            }
        } else {
            return ['success' => false, 'errors' => $errors];
        }
    }
}

// Fungsi untuk menangani submit komentar
if (!function_exists('handleCommentSubmission')) {
    function handleCommentSubmission($user_id, $review_id, $comment_content) {
        global $pdo;
        
        $errors = [];
        
        if (empty($comment_content)) {
            $errors['comment_content'] = 'Komentar tidak boleh kosong';
        } elseif (strlen($comment_content) < 5) {
            $errors['comment_content'] = 'Komentar terlalu pendek (minimal 5 karakter)';
        }
        
        if (empty($errors)) {
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("INSERT INTO comments (user_id, review_id, content, is_approved) VALUES (?, ?, ?, ?)");
                $is_approved = isAdmin() ? 1 : 0;
                $stmt->execute([$user_id, $review_id, $comment_content, $is_approved]);
                
                hasUserCheckedIn($user_id, 'comment'); 

                $pdo->commit();
                return [
                    'success' => true, 
                    'message' => isAdmin() ? 'Komentar Anda telah diposting.' : 'Komentar Anda telah dikirim dan menunggu persetujuan admin.'
                ];
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Comment submission error: " . $e->getMessage());
                return ['success' => false, 'message' => 'Gagal memposting komentar. Silakan coba lagi.'];
            }
        } else {
            return ['success' => false, 'errors' => $errors];
        }
    }
}

if (!function_exists('handleReviewSubmission')) {
    function handleReviewSubmission($user_id, $restaurant_id, $rating, $content, $review_id = null) {
        global $pdo;
        
        $errors = [];
        
        // Validasi
        if (empty($rating) || $rating < 1 || $rating > 5) {
            $errors['rating'] = 'Rating harus antara 1-5';
        }
        
        if (empty($content) || strlen($content) < 10) {
            $errors['content'] = 'Review harus minimal 10 karakter';
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        try {
            if ($review_id) {
                // Update review yang sudah ada
                $stmt = $pdo->prepare("UPDATE reviews SET rating = ?, content = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
                $stmt->execute([$rating, $content, $review_id, $user_id]);
                
                return [
                    'success' => true,
                    'message' => 'Review Anda berhasil diperbarui!'
                ];
            } else {
                // Cek apakah user sudah pernah review
                $check = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND restaurant_id = ?");
                $check->execute([$user_id, $restaurant_id]);
                
                if ($check->rowCount() > 0) {
                    return [
                        'success' => false,
                        'message' => 'Anda sudah memberikan review untuk restoran ini. Anda hanya bisa mengedit review yang sudah ada.'
                    ];
                }
                
                // Buat review baru
                $stmt = $pdo->prepare("INSERT INTO reviews (user_id, restaurant_id, rating, content) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $restaurant_id, $rating, $content]);
                
                return [
                    'success' => true,
                    'message' => 'Terima kasih! Review Anda telah berhasil disimpan.'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ];
        }
    }
}

if (!function_exists('handleDeleteReview')) {
    function handleDeleteReview($review_id, $user_id) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ? AND user_id = ?");
            $stmt->execute([$review_id, $user_id]);
            
            if ($stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Review berhasil dihapus!'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Review tidak ditemukan atau Anda tidak memiliki izin untuk menghapusnya'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ];
        }
    }
}
?>