<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $action = $_POST['action'];
    
    if (!isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id] = ['quantity' => 0];
    }
    
    if ($action === 'increase') {
        $_SESSION['cart'][$id]['quantity']++;
    } else {
        $_SESSION['cart'][$id]['quantity']--;
        if ($_SESSION['cart'][$id]['quantity'] <= 0) {
            unset($_SESSION['cart'][$id]);
        }
    }
    
    // Generate new cart HTML
    ob_start();
    include 'cart_html.php';
    $cartHtml = ob_get_clean();
    
    echo json_encode([
        'success' => true,
        'cartHtml' => $cartHtml,
        'count' => array_sum(array_column($_SESSION['cart'], 'quantity'))
    ]);
}