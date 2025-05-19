<?php
require_once 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $action = isset($_POST['action']) ? $_POST['action'] : 'add';
    
    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Handle different actions
    switch ($action) {
        case 'add':
            if (!isset($_SESSION['cart'][$id])) {
                $_SESSION['cart'][$id] = ['quantity' => 1];
            } else {
                $_SESSION['cart'][$id]['quantity']++;
            }
            break;
            
        case 'decrease':
            if (isset($_SESSION['cart'][$id])) {
                $_SESSION['cart'][$id]['quantity']--;
                if ($_SESSION['cart'][$id]['quantity'] <= 0) {
                    unset($_SESSION['cart'][$id]);
                }
            }
            break;
            
        case 'remove':
            if (isset($_SESSION['cart'][$id])) {
                unset($_SESSION['cart'][$id]);
            }
            break;
    }
    
    // Get updated cart count
    $cartCount = array_sum(array_column($_SESSION['cart'], 'quantity'));
    
    // Generate cart HTML
    $cartHtml = '';
    $total = 0;
    
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $itemId => $item) {
            $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id = ?");
            $stmt->execute([$itemId]);
            $menuItem = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $subtotal = $menuItem['price'] * $item['quantity'];
            $total += $subtotal;
            
            $cartHtml .= '
            <div class="cart-item" data-id="' . $itemId . '">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <h6 class="cart-item-title mb-0">' . htmlspecialchars($menuItem['name']) . '</h6>
                        <small class="text-muted">Rp ' . number_format($menuItem['price'], 0, ',', '.') . ' / item</small>
                    </div>
                    <button class="btn btn-sm btn-danger remove-item" data-id="' . $itemId . '">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="quantity-controls">
                        <button class="btn btn-sm btn-outline-secondary decrease-qty" data-id="' . $itemId . '">
                            <i class="fas fa-minus"></i>
                        </button>
                        <span class="quantity mx-2">' . $item['quantity'] . '</span>
                        <button class="btn btn-sm btn-outline-secondary increase-qty" data-id="' . $itemId . '">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <span class="item-total">Rp ' . number_format($subtotal, 0, ',', '.') . '</span>
                </div>
            </div>';
        }
        
        $cartHtml .= '
        <div class="cart-footer mt-4">
            <div class="form-group mb-3">
                <label for="customerName">Nama Pembeli:</label>
                <input type="text" id="customerName" class="form-control" required>
            </div>
            <div class="form-group mb-3">
                <label for="orderNotes">Catatan:</label>
                <textarea id="orderNotes" class="form-control" rows="2"></textarea>
            </div>
            <div class="d-flex justify-content-between mb-3">
                <h5>Total:</h5>
                <h5 class="cart-total" data-total="' . $total . '">Rp ' . number_format($total, 0, ',', '.') . '</h5>
            </div>
            <button class="btn btn-primary w-100" id="buyButton" onclick="showReceiptPopup()">Buy Now</button>
        </div>';
    } else {
        $cartHtml = '<p class="text-center">Your cart is empty</p>';
    }
    
    echo json_encode([
        'success' => true,
        'count' => $cartCount,
        'cartHtml' => $cartHtml
    ]);
}