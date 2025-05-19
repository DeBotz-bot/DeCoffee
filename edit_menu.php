<?php
session_start();
require_once 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Get menu item
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    
    if (!$item) {
        header('Location: admin_dashboard.php');
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $category = $_POST['category'];
    
    try {
        // Handle image upload if new image is selected
        $image_path = $item['image_path']; // Keep existing image path by default
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = "uploads/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
            $new_image_path = $target_dir . uniqid() . '.' . $extension;
            
            // Only allow certain file formats
            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $new_image_path)) {
                    // Delete old image if it exists
                    if ($image_path && file_exists($image_path)) {
                        unlink($image_path);
                    }
                    $image_path = $new_image_path;
                }
            }
        }
        
        // Update database
        $stmt = $pdo->prepare("UPDATE menu_items SET name = ?, description = ?, price = ?, category = ?, image_path = ? WHERE id = ?");
        $stmt->execute([$name, $description, $price, $category, $image_path, $id]);
        
        $_SESSION['success_message'] = "Menu item updated successfully!";
        header('Location: admin_dashboard.php');
        exit;
        
    } catch (PDOException $e) {
        $error_message = "Error updating menu item: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Menu Item - DeCoffee</title>
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
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border: none;
        }
        
        .current-image {
            max-width: 200px;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title mb-4">Edit Menu Item</h3>
                        
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger">
                                <?= $error_message ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($item['name']) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3" required><?= htmlspecialchars($item['description']) ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Price (Rp)</label>
                                <input type="text" name="price" class="form-control" 
                                    value="<?= number_format($item['price'], 0, ',', '.') ?>" required>
                                <small class="text-muted">Price will be automatically formatted</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-control" required>
                                    <option value="Hot Coffee" <?= $item['category'] == 'Hot Coffee' ? 'selected' : '' ?>>Hot Coffee</option>
                                    <option value="Iced Coffee" <?= $item['category'] == 'Iced Coffee' ? 'selected' : '' ?>>Iced Coffee</option>
                                    <option value="Pastries" <?= $item['category'] == 'Pastries' ? 'selected' : '' ?>>Pastries</option>
                                    <option value="Snacks" <?= $item['category'] == 'Snacks' ? 'selected' : '' ?>>Snacks</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Current Image</label><br>
                                <?php if ($item['image_path']): ?>
                                    <img src="<?= htmlspecialchars($item['image_path']) ?>" class="current-image mb-2" alt="Current Image">
                                <?php else: ?>
                                    <p class="text-muted">No image currently set</p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">New Image (leave empty to keep current)</label>
                                <input type="file" name="image" class="form-control" accept="image/*">
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-primary">Update Menu Item</button>
                                <a href="admin_dashboard.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
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