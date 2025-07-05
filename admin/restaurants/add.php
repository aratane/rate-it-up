<?php
require_once '../../includes/auth_check.php';
$page_title = "Tambah Tempat Kuliner - Rate It Up";
include '../../includes/header.php';
include '../../config/functions.php';

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

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
    
    // Handle file uploads
    $photos = [];
    $featured_set = false;
    
    if (isset($_FILES['photos'])) {
        foreach ($_FILES['photos']['name'] as $key => $value) {
            if ($_FILES['photos']['error'][$key] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $_FILES['photos']['name'][$key],
                    'type' => $_FILES['photos']['type'][$key],
                    'tmp_name' => $_FILES['photos']['tmp_name'][$key],
                    'error' => $_FILES['photos']['error'][$key],
                    'size' => $_FILES['photos']['size'][$key]
                ];
                
                $upload_result = uploadFile($file, '../../../assets/images/uploads/restaurants/');
                if ($upload_result['success']) {
                    $photos[] = [
                        'path' => basename($upload_result['path']),
                        'is_featured' => isset($_POST['featured_photo']) && $_POST['featured_photo'] == $key
                    ];
                    
                    if (isset($_POST['featured_photo']) && $_POST['featured_photo'] == $key) {
                        $featured_set = true;
                    }
                } else {
                    $errors['photos'] = $upload_result['message'];
                }
            }
        }
    }
    
    if (empty($photos)) {
        $errors['photos'] = 'Minimal upload 1 foto';
    } elseif (!$featured_set) {
        // Jika tidak ada foto yang dipilih sebagai featured, set foto pertama sebagai featured
        $photos[0]['is_featured'] = true;
    }
    
    // Jika tidak ada error, simpan ke database
    if (empty($errors)) {
        $pdo->beginTransaction();
        
        try {
            // Insert restaurant data
            $stmt = $pdo->prepare("INSERT INTO restaurants (name, address, map_url, description, created_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $address, $map_url, $description, $_SESSION['user_id']]);
            $restaurant_id = $pdo->lastInsertId();
            
            // Insert photos
            $stmt = $pdo->prepare("INSERT INTO restaurant_photos (restaurant_id, photo_path, is_featured) VALUES (?, ?, ?)");
            foreach ($photos as $photo) {
                $stmt->execute([$restaurant_id, $photo['path'], $photo['is_featured'] ? 1 : 0]);
            }
            
            $pdo->commit();
            $_SESSION['success_message'] = 'Tempat kuliner berhasil ditambahkan!';
            redirect('index.php');
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors['general'] = 'Terjadi kesalahan. Silakan coba lagi.';
            
            // Hapus file yang sudah diupload jika terjadi error
            foreach ($photos as $photo) {
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
                    <h2 class="card-title">Tambah Tempat Kuliner</h2>
                    
                    <?php if (isset($errors['general'])): ?>
                        <div class="alert alert-danger"><?= $errors['general'] ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nama Tempat</label>
                            <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" 
                                   id="name" name="name" value="<?= $_POST['name'] ?? '' ?>" required>
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback"><?= $errors['name'] ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Alamat Lengkap</label>
                            <textarea class="form-control <?= isset($errors['address']) ? 'is-invalid' : '' ?>" 
                                      id="address" name="address" rows="3" required><?= $_POST['address'] ?? '' ?></textarea>
                            <?php if (isset($errors['address'])): ?>
                                <div class="invalid-feedback"><?= $errors['address'] ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label for="map_url" class="form-label">Google Maps URL</label>
                            <input type="url" class="form-control" id="map_url" name="map_url" value="<?= $_POST['map_url'] ?? '' ?>">
                            <small class="text-muted">Salin URL dari Google Maps dan tempel di sini</small>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Deskripsi</label>
                            <textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>" 
                                      id="description" name="description" rows="5" required><?= $_POST['description'] ?? '' ?></textarea>
                            <?php if (isset($errors['description'])): ?>
                                <div class="invalid-feedback"><?= $errors['description'] ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Foto Tempat</label>
                            <small class="d-block text-muted mb-2">Upload minimal 1 foto, maksimal 5 foto</small>
                            
                            <div id="photo-upload-container">
                                <div class="mb-3 photo-upload-item">
                                    <div class="input-group">
                                        <input type="file" class="form-control <?= isset($errors['photos']) ? 'is-invalid' : '' ?>" 
                                               name="photos[]" accept="image/*" required>
                                        <div class="input-group-text">
                                            <input class="form-check-input mt-0 featured-radio" type="radio" name="featured_photo" value="0" checked>
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
                        
                        <button type="submit" class="btn btn-primary">Tambah Tempat</button>
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
                <input type="file" class="form-control" name="photos[]" accept="image/*" required>
                <div class="input-group-text">
                    <input class="form-check-input mt-0 featured-radio" type="radio" name="featured_photo" value="${photoCount}">
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
            // If this is the featured photo, set the first photo as featured
            if (this.parentElement.querySelector('.featured-radio').checked) {
                const firstRadio = photoUploadContainer.querySelector('.featured-radio');
                if (firstRadio) {
                    firstRadio.checked = true;
                }
            }
            
            photoUploadContainer.removeChild(newItem);
            photoCount--;
        });
    });
});
</script>

<?php include '../../includes/footer.php'; ?>