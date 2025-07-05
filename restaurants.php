<?php
$page_title = "Tempat Kuliner - Rate It Up";
include 'includes/header.php';
include 'config/functions.php';

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$category = isset($_GET['category']) ? sanitize($_GET['category']) : ''; // New category filter
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 9;
$offset = ($page - 1) * $limit;

// Base query for total count
$total_query = "SELECT COUNT(DISTINCT r.id) as total FROM restaurants r";
$join_conditions = "";
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "r.name LIKE :search";
    $params[':search'] = "%$search%";
}
// Add category filtering
if ($category) {
    // Assuming 'categories' table and 'restaurant_categories' join table exist
    $join_conditions .= " JOIN restaurant_categories rc ON r.id = rc.restaurant_id JOIN categories c ON rc.category_id = c.id";
    $where_conditions[] = "c.name = :category";
    $params[':category'] = $category;
}

if (!empty($where_conditions)) {
    $total_query .= $join_conditions . " WHERE " . implode(" AND ", $where_conditions);
}

$stmt = $pdo->prepare($total_query);
$stmt->execute($params);
$total_result = $stmt->fetch();
$total_restaurants = $total_result['total'];
$total_pages = ceil($total_restaurants / $limit);

// Base query for restaurants with details
$query = "SELECT r.*,
          (SELECT photo_path FROM restaurant_photos WHERE restaurant_id = r.id AND is_featured = 1 LIMIT 1) as featured_photo,
          (SELECT AVG(rating) FROM reviews WHERE restaurant_id = r.id AND is_approved = 1) as avg_rating
          FROM restaurants r";

if (!empty($join_conditions)) {
    $query .= $join_conditions;
}
if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(" AND ", $where_conditions);
}

$query .= " GROUP BY r.id ORDER BY r.created_at DESC LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute($params); // Execute with the collected parameters
$restaurants = $stmt->fetchAll();

// Fetch all categories for filter dropdown
$all_categories = getTopRatedRestaurants(); // You need to implement this function
?>

<div class="container mt-5">
    <div class="row mb-4 align-items-center" data-aos="fade-down">
        <div class="col-lg-6">
            <h1 class="display-4 fw-bold">Tempat Kuliner Pilihan</h1>
            <p class="lead text-muted">Jelajahi berbagai pilihan tempat makan terbaik di Indonesia.</p>
        </div>
        <div class="col-lg-6">
            <form method="GET" class="d-flex flex-column flex-md-row gap-2">
                <input type="text" name="search" class="form-control form-control-lg flex-grow-1" placeholder="Cari nama tempat..." value="<?= htmlspecialchars($search) ?>">
                <select name="category" class="form-select form-select-lg w-md-auto">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($all_categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat['name']) ?>" <?= ($category == $cat['name']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary btn-lg px-4"><i class="bi bi-search me-2"></i> Cari</button>
            </form>
        </div>
    </div>

    <?php if (empty($restaurants)): ?>
        <div class="alert alert-info text-center py-4" data-aos="fade-up">
            <i class="bi bi-info-circle-fill fs-3 mb-2"></i>
            <h4 class="mb-2">Tidak ada tempat kuliner yang ditemukan.</h4>
            <p class="mb-0">Coba cari dengan kata kunci lain atau pilih kategori yang berbeda.</p>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($restaurants as $restaurant): ?>
                <div class="col" data-aos="fade-up" data-aos-delay="<?= 100 * ($restaurant['id'] % 3) + 100 ?>">
                    <div class="card h-100 shadow-sm border-0 restaurant-card">
                        <div class="position-relative">
                            <?php if ($restaurant['featured_photo']): ?>
                                <img src="/assets/images/uploads/restaurants/<?= $restaurant['featured_photo'] ?>" class="card-img-top restaurant-img" alt="<?= htmlspecialchars($restaurant['name']) ?>">
                            <?php else: ?>
                                <img src="/assets/images/default-restaurant.jpg" class="card-img-top restaurant-img" alt="Default Restaurant">
                            <?php endif; ?>
                            <div class="card-img-overlay d-flex justify-content-end align-items-start p-2">
                                <?php if ($restaurant['avg_rating']): ?>
                                    <span class="badge bg-warning text-dark p-2 rounded-pill shadow-sm">
                                        <i class="bi bi-star-fill me-1"></i><?= number_format($restaurant['avg_rating'], 1) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary p-2 rounded-pill shadow-sm">Belum ada rating</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title mb-2 text-truncate"><?= htmlspecialchars($restaurant['name']) ?></h5>
                            <p class="card-text text-muted flex-grow-1 text-truncate-3-lines"><?= htmlspecialchars($restaurant['description']) ?></p>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <small class="text-muted"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($restaurant['address'] ?? 'Alamat tidak tersedia') ?></small>
                                <a href="restaurant-detail.php?id=<?= $restaurant['id'] ?>" class="btn btn-primary btn-sm rounded-pill">Lihat Detail <i class="bi bi-arrow-right ms-1"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <nav aria-label="Page navigation" class="mt-5" data-aos="fade-up" data-aos-delay="300">
            <ul class="pagination justify-content-center pagination-lg">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= htmlspecialchars($search) ?>&category=<?= htmlspecialchars($category) ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&search=<?= htmlspecialchars($search) ?>&category=<?= htmlspecialchars($category) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= htmlspecialchars($search) ?>&category=<?= htmlspecialchars($category) ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({
        once: true,
        duration: 800,
        easing: 'ease-out-quad',
    });
</script>