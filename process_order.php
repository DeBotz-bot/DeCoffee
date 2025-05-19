<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerName = $_POST['customerName'];
    $notes = $_POST['notes'];
    $orderDate = date('Y-m-d H:i:s');
    $total = 0;
    
    // Calculate total and prepare items for receipt
    $items = [];
    foreach ($_SESSION['cart'] as $id => $item) {
        $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id = ?");
        $stmt->execute([$id]);
        $menuItem = $stmt->fetch();
        
        $subtotal = $menuItem['price'] * $item['quantity'];
        $total += $subtotal;
        
        $items[] = [
            'name' => $menuItem['name'],
            'quantity' => $item['quantity'],
            'price' => $menuItem['price'],
            'subtotal' => $subtotal
        ];
    }
    
    // Save order to database
    $stmt = $pdo->prepare("INSERT INTO orders (customer_name, total_amount, notes) VALUES (?, ?, ?)");
    $stmt->execute([$customerName, $total, $notes]);
    $orderId = $pdo->lastInsertId();
    
    // Generate receipt HTML
    $receiptHtml = "
        <div class='receipt'>
            <h4>DeCoffee</h4>
            <p>Order #$orderId</p>
            <p>Customer: $customerName</p>
            <p>Date: " . date('d/m/Y H:i') . "</p>
            <hr>
            <div class='items'>";
    
    foreach ($items as $item) {
        $receiptHtml .= "
            <div class='item'>
                <p>{$item['name']} × {$item['quantity']}</p>
                <p>Rp " . number_format($item['subtotal'], 0, ',', '.') . "</p>
            </div>";
    }
    
    $receiptHtml .= "
            </div>
            <hr>
            <div class='total'>
                <h5>Total: Rp " . number_format($total, 0, ',', '.') . "</h5>
            </div>";
    
    if ($notes) {
        $receiptHtml .= "<p class='notes'>Notes: $notes</p>";
    }
    
    $receiptHtml .= "</div>";
    
    // Clear cart
    $_SESSION['cart'] = [];
    
    echo json_encode([
        'success' => true,
        'receiptHtml' => $receiptHtml
    ]);
}