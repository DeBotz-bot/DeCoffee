<?php
session_start();
require_once 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $category = $_POST['category'];
    
    // Handle image upload
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $image_path = $target_dir . uniqid() . '.' . $extension;
        
        // Only allow certain file formats
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            move_uploaded_file($_FILES["image"]["tmp_name"], $image_path);
        }
    }
    
    // Insert into database
    $stmt = $pdo->prepare("INSERT INTO menu_items (name, description, price, category, image_path) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $description, $price, $category, $image_path]);
    
    $success_message = "Menu item added successfully!";
}

// Fetch existing menu items
$stmt = $pdo->query("SELECT * FROM menu_items ORDER BY category, name");
$menuItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Menu - Brew Haven</title>
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
            padding-top: 20px;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border: none;
        }
        
        .btn-primary:hover {
            background-color: var(--dark-color);
        }
        
        .menu-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 10px;
        }
        // Add to the existing <style> section in the head
.nav-pills .nav-link {
    color: var(--dark-color);
    border-radius: 20px;
    padding: 8px 20px;
    margin: 0 5px;
    transition: all 0.3s ease;
}

.nav-pills .nav-link.active {
    background-color: var(--primary-color);
    color: white;
}

.nav-pills .nav-link:hover:not(.active) {
    background-color: var(--secondary-color);
    color: white;
}

.table {
    vertical-align: middle;
}

.badge {
    font-weight: 500;
    padding: 6px 12px;
    border-radius: 20px;
}

.btn-group {
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    border-radius: 6px;
    overflow: hidden;
}

.btn-group .btn {
    border: none;
    padding: 8px 12px;
}
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <h3 class="card-title mb-4">Add New Menu Item</h3>
                        
                        <?php if (isset($success_message)): ?>
                            <div class="alert alert-success">
                                <?= $success_message ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Price (Rp)</label>
                                <input type="text" name="price" class="form-control" required 
                                    placeholder="Example: 25.000">
                                <small class="text-muted">Price will be automatically formatted</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-control" required>
                                    <option value="Hot Coffee">Hot Coffee</option>
                                    <option value="Iced Coffee">Iced Coffee</option>
                                    <option value="Pastries">Pastries</option>
                                    <option value="Snacks">Snacks</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Image</label>
                                <input type="file" name="image" class="form-control" accept="image/*">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Add Menu Item</button>
                            <a href="admin_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                        </form>
                    </div>
                </div>
                
                <!-- Existing Menu Items -->

            </div>
        </div>
    </div>

      <div class="card" style="margin: 0 60px;">
    <div class="card-body">
        <h3 class="card-title mb-4">Existing Menu Items</h3>
        
        <!-- Category tabs -->
        <ul class="nav nav-pills mb-4">
            <li class="nav-item">
                <a class="nav-link active" href="#all" data-bs-toggle="pill">All Items</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#hot-coffee" data-bs-toggle="pill">Hot Coffee</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#iced-coffee" data-bs-toggle="pill">Iced Coffee</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#pastries" data-bs-toggle="pill">Pastries</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#snacks" data-bs-toggle="pill">Snacks</a>
            </li>
        </ul>

        <!-- Tab content -->
        <div class="tab-content">
            <div class="tab-pane fade show active" id="all">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($menuItems as $item): ?>
                            <tr>
                                <td>
                                    <img src="<?= htmlspecialchars($item['image_path'] ?: 'https://via.placeholder.com/100x100?text=No+Image') ?>" 
                                         class="menu-image" 
                                         alt="<?= htmlspecialchars($item['name']) ?>">
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($item['name']) ?></strong>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars($item['description']) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($item['category']) ?></span>
                                </td>
                                <td>
                                    <strong>Rp <?= number_format($item['price'], 0, ',', '.') ?></strong>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="edit_menu.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete_menu.php?id=<?= $item['id'] ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Are you sure you want to delete this item?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php
            // Group menu items by category
            $menuByCategory = [];
            foreach ($menuItems as $item) {
                $category = $item['category'];
                if (!isset($menuByCategory[$category])) {
                    $menuByCategory[$category] = [];
                }
                $menuByCategory[$category][] = $item;
            }
            
            // Create tabs for each category
            $categoryIds = [
                'Hot Coffee' => 'hot-coffee',
                'Iced Coffee' => 'iced-coffee',
                'Pastries' => 'pastries',
                'Snacks' => 'snacks'
            ];
            
            foreach ($categoryIds as $category => $id):
            ?>
            <div class="tab-pane fade" id="<?= $id ?>">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Price</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if (isset($menuByCategory[$category])):
                                foreach ($menuByCategory[$category] as $item): 
                            ?>
                            <tr>
                                <td>
                                    <img src="<?= htmlspecialchars($item['image_path'] ?: 'https://via.placeholder.com/100x100?text=No+Image') ?>" 
                                         class="menu-image" 
                                         alt="<?= htmlspecialchars($item['name']) ?>">
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($item['name']) ?></strong>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars($item['description']) ?></small>
                                </td>
                                <td>
                                    <strong>Rp <?= number_format($item['price'], 0, ',', '.') ?></strong>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="edit_menu.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete_menu.php?id=<?= $item['id'] ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Are you sure you want to delete this item?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php 
                                endforeach;
                            endif;
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Format price input with thousand separator
    document.querySelector('input[name="price"]').addEventListener('input', function(e) {
        // Remove existing dots and non-digit characters
        let value = this.value.replace(/\D/g, '');
        
        // Format with thousand separator
        if (value !== '') {
            value = parseInt(value).toLocaleString('id-ID');
            
            // Remove thousand separator when sending to server
            let hiddenInput = document.querySelector('input[name="real_price"]');
            if (!hiddenInput) {
                hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'real_price';
                this.parentNode.appendChild(hiddenInput);
            }
            hiddenInput.value = this.value.replace(/\./g, '');
        }
        
        // Update display value with dots
        this.value = value;
    });

    // Modify form submission to use the real price value
    document.querySelector('form').addEventListener('submit', function(e) {
        const priceInput = document.querySelector('input[name="price"]');
        const realPrice = priceInput.value.replace(/\./g, '');
        priceInput.value = realPrice;
    });
</script>
</body>
</html>