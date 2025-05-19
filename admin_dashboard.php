<?php
session_start();
require_once 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Get category counts
$stmt = $pdo->query("SELECT category, COUNT(*) as count FROM menu_items GROUP BY category");
$categoryCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get selected category from query parameter
$selectedCategory = $_GET['category'] ?? 'all';

// Fetch menu items based on selected category
if ($selectedCategory === 'all') {
    $stmt = $pdo->query("SELECT * FROM menu_items ORDER BY category, name");
} else {
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE category = ? ORDER BY name");
    $stmt->execute([$selectedCategory]);
}
$menuItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - DeCoffee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #6F4E37;
            --secondary-color: #C4A484;
            --light-color: #F5F5DC;
            --dark-color: #3E2723;
        }

        body {
            background-color: var(--light-color);
            padding-top: 2rem;
        }

        .stats-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .category-icon {
            font-size: 2rem;
            color: var(--primary-color);
        }

        .stats-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--dark-color);
        }

        .nav-pills .nav-link.active {
            background-color: var(--primary-color);
        }

        .nav-pills .nav-link {
            color: var(--dark-color);
        }

        .menu-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Admin Dashboard</h2>
            <div>
                <a href="up_menu.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add New Menu Item
                </a>
                <a href="save_order.php" class="btn btn-secondary">
                    <i class="fas fa-save me-2"></i>Save order
                </a>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-home me-2"></i>Back to Site
                </a>

            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <?php foreach ($categoryCounts as $category): ?>
            <div class="col-md-3 mb-3">
                <div class="stats-card p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0"><?= htmlspecialchars($category['category']) ?></h6>
                            <span class="stats-number"><?= $category['count'] ?></span>
                            <p class="mb-0 text-muted">Items</p>
                        </div>
                        <div class="category-icon">
                            <i class="fas <?= getCategoryIcon($category['category']) ?>"></i>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Category Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link <?= $selectedCategory === 'all' ? 'active' : '' ?>" 
                           href="?category=all">All Items</a>
                    </li>
                    <?php foreach ($categoryCounts as $category): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $selectedCategory === $category['category'] ? 'active' : '' ?>" 
                           href="?category=<?= urlencode($category['category']) ?>">
                           <?= htmlspecialchars($category['category']) ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Menu Items Table -->
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Menu Items</h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($menuItems as $item): ?>
                            <tr>
                                <td>
                                    <img src="<?= htmlspecialchars($item['image_path'] ?: 'https://via.placeholder.com/60x60?text=No+Image') ?>" 
                                         class="menu-image" 
                                         alt="<?= htmlspecialchars($item['name']) ?>">
                                </td>
                                <td><?= htmlspecialchars($item['name']) ?></td>
                                <td><?= htmlspecialchars($item['category']) ?></td>
                                <td>Rp <?= number_format($item['price'], 0, ',', '.') ?></td>
                                <td><?= substr(htmlspecialchars($item['description']), 0, 50) ?>...</td>
                                <td>
                                    <a href="edit_menu.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete_menu.php?id=<?= $item['id'] ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Are you sure you want to delete this item?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
function getCategoryIcon($category) {
    switch ($category) {
        case 'Hot Coffee':
            return 'fa-mug-hot';
        case 'Iced Coffee':
            return 'fa-cube'; // for ice cube
        case 'Pastries':
            return 'fa-bread-slice';
        case 'Snacks':
            return 'fa-cookie';
        default:
            return 'fa-utensils';
    }
}
?>