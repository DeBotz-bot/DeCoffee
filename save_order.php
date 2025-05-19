<?php
require_once 'config.php';
session_start();

// Remove any whitespace or output before PHP opening tag
// Make sure there are no echo statements or HTML outside the JSON response

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerName = $_POST['customerName'];
    $orderNotes = $_POST['orderNotes'];
    $items = $_POST['items'];
    $total = $_POST['total'];
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Insert order
        $stmt = $pdo->prepare("INSERT INTO orders (customer_name, notes, total, order_date) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$customerName, $orderNotes, $total]);
        $orderId = $pdo->lastInsertId();
        
        // Insert order items
        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, price) VALUES (?, ?, ?, ?)");
        foreach ($items as $item) {
            $stmt->execute([$orderId, $item['id'], $item['quantity'], $item['price']]);
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Clear cart
        $_SESSION['cart'] = [];
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Order saved successfully'
        ]);
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'Error saving order'
        ]);
        exit;
    }
}

// Fetch all orders with their items
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
    GROUP BY o.id
    ORDER BY o.order_date DESC
");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order List - DeCoffee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary-color: #6F4E37;
            --secondary-color: #C4A484;
            --light-color: #F5F5DC;
            --dark-color: #3E2723;
            --accent-color: #D2B48C;
        }

        body {
            background-color: var(--light-color);
            font-family: 'Poppins', sans-serif;
            padding-top: 2rem;
        }

        .page-header {
            background: var(--primary-color);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .stats-card {
            background: linear-gradient(145deg, var(--primary-color), var(--dark-color));
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }

        .stats-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }

        .order-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
            border: none;
        }

        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }

        .order-header {
            background: linear-gradient(145deg, var(--primary-color), var(--dark-color));
            color: white;
            padding: 1.2rem;
            border-radius: 15px 15px 0 0;
        }

        .order-items {
            max-height: 120px;
            overflow-y: auto;
            padding: 0.5rem;
            background: rgba(0,0,0,0.02);
            border-radius: 8px;
            margin-bottom: 1rem;
        }

.order-items pre {
    white-space: pre-wrap;
    word-wrap: break-word;
    font-size: 0.9rem;
    line-height: 1.5;
    color: inherit;
}
        .order-notes {
            background: rgba(0,0,0,0.02);
            padding: 0.5rem;
            border-radius: 8px;
        }

        .back-btn {
            background-color: var(--secondary-color);
            border: none;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background-color: var(--dark-color);
            transform: translateX(-5px);
        }

        /* Custom Scrollbar */
        .order-items::-webkit-scrollbar {
            width: 6px;
        }

        .order-items::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .order-items::-webkit-scrollbar-thumb {
            background: var(--secondary-color);
            border-radius: 10px;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 600;
        }

        .order-date {
            font-size: 0.85rem;
            opacity: 0.9;
        }

        .order-total {
            font-weight: 600;
            color: var(--accent-color);
        }

        .order-number {
            background: rgba(255,255,255,0.2);
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->


        <!-- Order Statistics -->
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="stats-card h-100 text-white p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Total Orders</div>
                            <div class="stat-value"><?= count($orders) ?></div>
                        </div>
                        <i class="fas fa-shopping-bag stats-icon"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card h-100 text-white p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Today's Orders</div>
                            <div class="stat-value"><?= count(array_filter($orders, fn($order) => 
                                date('Y-m-d', strtotime($order['order_date'])) === date('Y-m-d')
                            )) ?></div>
                        </div>
                        <i class="fas fa-calendar-day stats-icon"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card h-100 text-white p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Total Revenue</div>
                            <div class="stat-value">Rp <?= number_format(array_sum(array_column($orders, 'total')), 0, ',', '.') ?></div>
                        </div>
                        <i class="fas fa-coins stats-icon"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Back Button -->
        <div class="text-end mb-4">
            <a href="admin_dashboard.php" class="btn back-btn text-white">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>

        <!-- Orders List -->
       <div class="row" id="ordersList">
            <?php foreach ($orders as $order): ?>
            <div class="col-md-6">
                <div class="order-card">
                    <div class="order-header">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="mb-0"><?= htmlspecialchars($order['customer_name']) ?></h5>
                            <div>
                                <span class="order-number me-2">#<?= $order['id'] ?></span>
                                <button class="btn btn-sm btn-danger delete-order" data-id="<?= $order['id'] ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="order-date">
                                <i class="far fa-clock me-1"></i>
                                <?= date('d M Y H:i', strtotime($order['order_date'])) ?>
                            </span>
                            <span class="order-total">
                                Rp <?= number_format($order['total'], 0, ',', '.') ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="order-items mb-3">
                            <strong><i class="fas fa-utensils me-2"></i>Items:</strong><br>
                            <pre class="mb-0 mt-1" style="font-family: inherit; background: none; padding: 0;"><?= htmlspecialchars($order['items']) ?></pre>
                        </div>
                        <?php if ($order['notes']): ?>
                        <div class="order-notes">
                            <strong><i class="fas fa-note-sticky me-2"></i>Notes:</strong><br>
                            <?= htmlspecialchars($order['notes']) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Initialize delete order handlers
    $(document).on('click', '.delete-order', function() {
        const orderId = $(this).data('id');
        const orderCard = $(this).closest('.col-md-6');
        
        Swal.fire({
            title: 'Delete Order',
            text: 'Are you sure you want to delete this order?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'delete_order.php',
                    method: 'POST',
                    data: { order_id: orderId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Remove order card with animation
                            orderCard.fadeOut(500, function() {
                                $(this).remove();
                                // Update stats after removal
                                updateStats();
                            });
                            
                            // Show success message
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: 'Order has been deleted',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message || 'Failed to delete order'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Delete error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to delete order. Please try again.'
                        });
                    }
                });
            }
        });
    });
    // Add this function in save_order.php
    function loadLatestOrder() {
        return $.ajax({
            url: 'get_latest_order.php',
            method: 'GET',
            dataType: 'json'
        });
    }

 $(document).ready(function() {
    // Start polling for new orders
    setInterval(checkForNewOrders, 5000); // Check every 5 seconds
    
    function checkForNewOrders() {
        $.ajax({
            url: 'get_latest_order.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Check if this is a new order we haven't displayed yet
                    const existingOrder = $(`.order-number:contains("#${response.order.id}")`);
                    if (existingOrder.length === 0) {
                        // Prepend new order with animation
                        $(response.html)
                            .hide()
                            .prependTo('#ordersList')
                            .fadeIn(500);
                            
                        // Update statistics
                        updateStats();
                        
                        // Show notification
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: 'New order received!',
                            showConfirmButton: false,
                            timer: 3000
                        });
                    }
                }
            }
        });
    }

    // Initialize delete order handlers
    $(document).on('click', '.delete-order', function() {
        const orderId = $(this).data('id');
        const orderCard = $(this).closest('.col-md-6');
        
        Swal.fire({
            title: 'Delete Order',
            text: 'Are you sure you want to delete this order?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'delete_order.php',
                    method: 'POST',
                    data: { order_id: orderId },
                    success: function(response) {
                        if (response.success) {
                            // Remove order card from UI
                            orderCard.fadeOut(500, function() {
                                $(this).remove();
                                
                                // Update stats
                                updateStats();
                            });
                            
                            // Show success message
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: 'Order has been deleted',
                                timer: 2000
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message || 'An error occurred'
                            });
                        }
                    }
                });
            }
        });
    });
}); // Close $(document).ready()

// Define updateStats globally
function updateStats() {
    // Get all orders
    const totalOrders = $('.order-card').length;
    $('.stat-value').first().text(totalOrders);
    
    // Get today's date
    const today = new Date().toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
    
    // Count today's orders
    const todayOrders = $('.order-date').filter(function() {
        return $(this).text().trim().includes(today);
    }).length;
    $('.stat-value').eq(1).text(todayOrders);
    
    // Calculate total revenue
    let totalRevenue = 0;
    $('.order-total').each(function() {
        const amount = parseInt($(this).text().replace(/[^0-9]/g, ''));
        if (!isNaN(amount)) {
            totalRevenue += amount;
        }
    });
    
    // Format and update total revenue
    $('.stat-value').last().text('Rp ' + new Intl.NumberFormat('id-ID').format(totalRevenue));
}
});
</script>
</body>
</html>