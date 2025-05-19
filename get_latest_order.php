<?php
require_once 'config.php';

header('Content-Type: application/json');

// Fetch latest order
$stmt = $pdo->query("
    SELECT 
        o.id,
        o.customer_name,
        o.notes,
        o.total,
        o.order_date,
        GROUP_CONCAT(
            CONCAT('- ', m.name, ' (', oi.quantity, ')') 
            SEPARATOR '\n'
        ) as items
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN menu_items m ON oi.menu_item_id = m.id
    WHERE o.id = (SELECT MAX(id) FROM orders)
    GROUP BY o.id
");

$order = $stmt->fetch(PDO::FETCH_ASSOC);

if ($order) {
    $html = generateOrderHtml($order);
    echo json_encode([
        'success' => true,
        'order' => $order,
        'html' => $html
    ]);
} else {
    echo json_encode(['success' => false]);
}

function generateOrderHtml($order) {
    return '<div class="col-md-6">
        <div class="order-card">
            <div class="order-header">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">'.htmlspecialchars($order['customer_name']).'</h5>
                    <div>
                        <span class="order-number me-2">#'.$order['id'].'</span>
                        <button class="btn btn-sm btn-danger delete-order" data-id="'.$order['id'].'">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="order-date">
                        <i class="far fa-clock me-1"></i>
                        '.date('d M Y H:i', strtotime($order['order_date'])).'
                    </span>
                    <span class="order-total">
                        Rp '.number_format($order['total'], 0, ',', '.').'
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="order-items mb-3">
                    <strong><i class="fas fa-utensils me-2"></i>Items:</strong><br>
                    <pre class="mb-0 mt-1" style="font-family: inherit; background: none; padding: 0;">'.htmlspecialchars($order['items']).'</pre>
                </div>
                '.($order['notes'] ? '<div class="order-notes">
                    <strong><i class="fas fa-note-sticky me-2"></i>Notes:</strong><br>
                    '.htmlspecialchars($order['notes']).'
                </div>' : '').'
            </div>
        </div>
    </div>';
}