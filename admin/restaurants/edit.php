<?php
require_once '../../includes/auth_check.php';
$page_title = "Edit Tempat Kuliner - Rate It Up";
include '../../includes/header.php';
include '../../config/functions.php';

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

if (!isset($_GET['id'])) {
    redirect('index.php');
}

$restaurant_id = (int)$_GET['id'];
$restaurant = getRestaurantById($restaurant_id);
$photos = getRestaurantPhotos($restaurant_id);

if (!$restaurant) {
    redirect('index.php');
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $address = sanitize($_POST['address']);
    $map_url = sanitize($_POST['map_url']);
    $description = sanitize($_POST['description']);
    
    // Validasi
    if (empty($name)) {
        $errors['name'] = 'Nama tempat harus diisi';
    }
    
    if (empty($address)) {
        $errors['address'] = 'Alamat harus diisi';
    }
    
    if (empty($description)) {
        $errors['description'] = 'Deskripsi harus diisi';
    }
    
    // Handle file uploads for new photos
    $new_photos = [];
    $featured_set = false;
    
    if (isset($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) {
        foreach ($_FILES['photos']['name'] as $key => $value) {
            if ($_FILES['photos']['error'][$key] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $_FILES['photos']['name'][$key],
                    'type' => $_FILES['photos']['type'][$key],
                    'tmp_name' => $_FILES['photos']['tmp_name'][$key],
                    'error' => $_FILES['photos']['error'][$key],
                    'size' => $_FILES['photos']['size'][$key]
                ];
                
                $upload_result = uploadFile($file, '../../assets/images/uploads/restaurants/');
                if ($upload_result['success']) {
                    $new_photos[] = [
                        'path' => basename($upload_result['path']),
                        'is_featured' => isset($_POST['featured_photo']) && $_POST['featured_photo'] == 'new_' . $key
                    ];
                    
                    if (isset($_POST['featured_photo']) && $_POST['featured_photo'] == 'new_' . $key) {
                        $featured_set = true;
                    }
                } else {
                    $errors['photos'] = $upload_result['message'];
                }
            }
        }
    }
    
    // Handle featured photo selection
    if (isset($_POST['featured_photo'])) {
        if (strpos($_POST['featured_photo'], 'new_') === 0) {
            $featured_set = true;
        } else {
            // Update featured status for existing photos
            $pdo->prepare("UPDATE restaurant_photos SET is_featured = 0 WHERE restaurant_id = ?")->execute([$restaurant_id]);
            $pdo->prepare("UPDATE restaurant_photos SET is_featured = 1 WHERE id = ?")->execute([(int)str_replace('existing_', '', $_POST['featured_photo'])]);
            $featured_set = true;
        }
    }
    
    // Jika tidak ada error, update data
    if (empty($errors)) {
        $pdo->beginTransaction();
        
        try {
            // Update restaurant data
            $stmt = $pdo->prepare("UPDATE restaurants SET name = ?, address = ?, map_url = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $address, $map_url, $description, $restaurant_id]);
            
            // Insert new photos
            if (!empty($new_photos)) {
                $stmt = $pdo->prepare("INSERT INTO restaurant_photos (restaurant_id, photo_path, is_featured) VALUES (?, ?, ?)");
                foreach ($new_photos as $photo) {
                    $is_featured = $photo['is_featured'] || (!$featured_set && empty($photos) && empty($new_photos));
                    $stmt->execute([$restaurant_id, $photo['path'], $is_featured ? 1 : 0]);
                    
                    if ($is_featured) {
                        $featured_set = true;
                    }
                }
            }
            
            // If no featured photo is set, set the first photo as featured
            if (!$featured_set && (!empty($photos) || !empty($new_photos))) {
                $first_photo_id = !empty($photos) ? $photos[0]['id'] : $pdo->lastInsertId();
                $pdo->prepare("UPDATE restaurant_photos SET is_featured = 1 WHERE id = ?")->execute([$first_photo_id]);
            }
            
            // Delete selected photos
            if (isset($_POST['delete_photos'])) {
                foreach ($_POST['delete_photos'] as $photo_id) {
                    $photo_id = (int)$photo_id;
                    $photo = array_filter($photos, function($p) use ($photo_id) { return $p['id'] == $photo_id; });
                    $photo = reset($photo);
                    
                    $pdo->prepare("DELETE FROM restaurant_photos WHERE id = ?")->execute([$photo_id]);
                    @unlink('../../assets/images/uploads/restaurants/' . $photo['photo_path']);
                }
            }
            
            $pdo->commit();
            $_SESSION['success_message'] = 'Tempat kuliner berhasil diperbarui!';
            redirect('index.php');
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors['general'] = 'Terjadi kesalahan. Silakan coba lagi.';
            
            // Hapus file yang sudah diupload jika terjadi error
            foreach ($new_photos as $photo) {
                @unlink('../../assets/images/uploads/restaurants/' . $photo['path']);
            }
        }
    }
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-3">
            <!-- Kartu Profil Admin -->
            <div class="card mb-4">
                <div class="card-body text-center">
                    <img src="../../assets/images/uploads/profiles/<?= $user['profile_picture'] ?>" class="rounded-circle mb-3" width="150" height="150" alt="Profile Picture">
                    <h4><?= $user['full_name'] ?></h4>
                    <p class="text-muted">@<?= $user['username'] ?> (Admin)</p>

                    <!-- Tombol Navigasi -->
                    <a href="../dashboard.php" class="btn btn-outline-secondary btn-sm d-block mb-2 <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <a href="../users/" class="btn btn-outline-secondary btn-sm d-block mb-2 <?= strpos($_SERVER['PHP_SELF'], '/users/') !== false ? 'active' : '' ?>">
                        <i class="bi bi-people"></i> Kelola Pengguna
                    </a>
                    <a href="index.php" class="btn btn-outline-secondary btn-sm d-block mb-2 <?= strpos($_SERVER['PHP_SELF'], '/restaurants/') !== false ? 'active' : '' ?>">
                        <i class="bi bi-shop"></i> Kelola Tempat
                    </a>
                    <a href="../reviews/" class="btn btn-outline-secondary btn-sm d-block mb-2 <?= strpos($_SERVER['PHP_SELF'], '/reviews/') !== false ? 'active' : '' ?>">
                        <i class="bi bi-star"></i> Kelola Review
                    </a>
                    <a href="../comments/" class="btn btn-outline-secondary btn-sm d-block mb-3 <?= strpos($_SERVER['PHP_SELF'], '/comments/') !== false ? 'active' : '' ?>">
                        <i class="bi bi-chat-left-text"></i> Kelola Komentar
                    </a>

                    <!-- Tombol Logout -->
                    <a href="../../auth/logout.php" class="btn btn-outline-danger btn-sm d-block">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="card">
                <div class="card-body">
                    <h2 class="card-title">Edit Tempat Kuliner</h2>
                    
                    <?php if (isset($errors['general'])): ?>
                        <div class="alert alert-danger"><?= $errors['general'] ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nama Tempat</label>
                            <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" 
                                   id="name" name="name" value="<?= $_POST['name'] ?? $restaurant['name'] ?>" required>
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback"><?= $errors['name'] ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Alamat Lengkap</label>
                            <textarea class="form-control <?= isset($errors['address']) ? 'is-invalid' : '' ?>" 
                                      id="address" name="address" rows="3" required><?= $_POST['address'] ?? $restaurant['address'] ?></textarea>
                            <?php if (isset($errors['address'])): ?>
                                <div class="invalid-feedback"><?= $errors['address'] ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label for="map_url" class="form-label">Google Maps URL</label>
                            <input type="url" class="form-control" id="map_url" name="map_url" value="<?= $_POST['map_url'] ?? $restaurant['map_url'] ?>">
                            <small class="text-muted">Salin URL dari Google Maps dan tempel di sini</small>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Deskripsi</label>
                            <textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>" 
                                      id="description" name="description" rows="5" required><?= $_POST['description'] ?? $restaurant['description'] ?></textarea>
                            <?php if (isset($errors['description'])): ?>
                                <div class="invalid-feedback"><?= $errors['description'] ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Foto Saat Ini</label>
                            <small class="d-block text-muted mb-2">Centang untuk menghapus, pilih satu sebagai featured</small>
                            
                            <?php if (empty($photos)): ?>
                                <div class="alert alert-info">Belum ada foto</div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($photos as $photo): ?>
                                        <div class="col-md-4 mb-3">
                                            <div class="card">
                                                <img src="/assets/images/uploads/restaurants/<?= $photo['photo_path'] ?>" class="card-img-top" alt="Restaurant Photo" style="height: 150px; object-fit: cover;">
                                                <div class="card-body">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="delete_photos[]" value="<?= $photo['id'] ?>" id="delete_photo_<?= $photo['id'] ?>">
                                                        <label class="form-check-label" for="delete_photo_<?= $photo['id'] ?>">
                                                            Hapus Foto
                                                        </label>
                                                    </div>
                                                    <div class="form-check mt-2">
                                                        <input class="form-check-input featured-radio" type="radio" name="featured_photo" value="existing_<?= $photo['id'] ?>" id="featured_photo_<?= $photo['id'] ?>" <?= $photo['is_featured'] ? 'checked' : '' ?>>
                                                        <label class="form-check-label" for="featured_photo_<?= $photo['id'] ?>">
                                                            Featured
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Foto Baru</label>
                            <small class="d-block text-muted mb-2">Upload foto baru (maksimal 5 foto)</small>
                            
                            <div id="photo-upload-container">
                                <div class="mb-3 photo-upload-item">
                                    <div class="input-group">
                                        <input type="file" class="form-control <?= isset($errors['photos']) ? 'is-invalid' : '' ?>" 
                                               name="photos[]" accept="image/*">
                                        <div class="input-group-text">
                                            <input class="form-check-input mt-0 featured-radio" type="radio" name="featured_photo" value="new_0">
                                            <label class="form-check-label ms-2">Featured</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (isset($errors['photos'])): ?>
                                <div class="text-danger"><?= $errors['photos'] ?></div>
                            <?php endif; ?>
                            
                            <button type="button" id="add-photo-btn" class="btn btn-sm btn-outline-secondary mt-2">
                                <i class="bi bi-plus"></i> Tambah Foto
                            </button>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        <a href="index.php" class="btn btn-outline-secondary">Kembali</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const photoUploadContainer = document.getElementById('photo-upload-container');
    const addPhotoBtn = document.getElementById('add-photo-btn');
    let photoCount = 1;
    
    addPhotoBtn.addEventListener('click', function() {
        if (photoCount >= 5) {
            alert('Maksimal 5 foto');
            return;
        }
        
        const newItem = document.createElement('div');
        newItem.className = 'mb-3 photo-upload-item';
        newItem.innerHTML = `
            <div class="input-group">
                <input type="file" class="form-control" name="photos[]" accept="image/*">
                <div class="input-group-text">
                    <input class="form-check-input mt-0 featured-radio" type="radio" name="featured_photo" value="new_${photoCount}">
                    <label class="form-check-label ms-2">Featured</label>
                </div>
                <button type="button" class="btn btn-outline-danger remove-photo-btn">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;
        
        photoUploadContainer.appendChild(newItem);
        photoCount++;
        
        // Add event listener to remove button
        newItem.querySelector('.remove-photo-btn').addEventListener('click', function() {
            // If this is the featured photo, set the first existing photo as featured
            if (this.parentElement.querySelector('.featured-radio').checked) {
                const firstExistingRadio = document.querySelector('.featured-radio[value^="existing_"]');
                if (firstExistingRadio) {
                    firstExistingRadio.checked = true;
                } else {
                    const firstNewRadio = photoUploadContainer.querySelector('.featured-radio[value^="new_"]');
                    if (firstNewRadio) {
                        firstNewRadio.checked = true;
                    }
                }
            }
            
            photoUploadContainer.removeChild(newItem);
            photoCount--;
        });
    });
});
</script>

<?php include '../../includes/footer.php'; ?>