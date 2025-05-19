<?php
session_start();
require_once 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        // Get image path before deletion
        $stmt = $pdo->prepare("SELECT image_path FROM menu_items WHERE id = ?");
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        
        // Delete the menu item
        $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ?");
        $stmt->execute([$id]);
        
        // Delete the image file if it exists
        if ($item && $item['image_path'] && file_exists($item['image_path'])) {
            unlink($item['image_path']);
        }
        
        $_SESSION['success_message'] = "Menu item deleted successfully!";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error deleting menu item: " . $e->getMessage();
    }
}

// Redirect back to admin dashboard
header('Location: admin_dashboard.php');
exit;