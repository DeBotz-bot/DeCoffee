<?php
require_once 'config.php';
session_start();

// Fetch menu items grouped by category
$stmt = $pdo->query("SELECT * FROM menu_items ORDER BY category, name");
$menuItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group items by category
$menuByCategory = [];
foreach ($menuItems as $item) {
    $category = $item['category'];
    if (!isset($menuByCategory[$category])) {
        $menuByCategory[$category] = [];
    }
    $menuByCategory[$category][] = $item;
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Calculate cart count
$cartCount = array_sum($_SESSION['cart']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DeCoffee - Modern Coffee Shop</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/home.css">
     <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="#">DeCoffee</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#menu">Menu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" id="adminLogin">Admin</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h1 class="hero-title">DeCoffee</h1>
            <p class="hero-subtitle">Artisanal Coffee & Handcrafted Delights</p>
            <a href="#menu" class="btn btn-hero">Explore Our Menu</a>
        </div>
    </section>

    <!-- Menu Section -->
    <section id="menu" class="container py-5">
        <h2 class="text-center section-title">Our Menu</h2>
        
        <?php foreach ($menuByCategory as $category => $items): ?>
        <h3 class="category-title"><?= htmlspecialchars($category) ?></h3>
        <div class="row">
            <?php foreach ($items as $item): ?>
            <div class="col-lg-4 col-md-6">
                <div class="menu-card">
                    <img src="<?= htmlspecialchars($item['image_path'] ?: 'https://via.placeholder.com/300x200?text=Coffee') ?>" class="card-img-top" alt="<?= htmlspecialchars($item['name']) ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($item['name']) ?></h5>
                        <p class="card-text"><?= htmlspecialchars($item['description']) ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="price">Rp <?= number_format($item['price'], 0, ',', '.') ?></span>
                            <button class="btn add-to-cart text-white" 
                                    data-id="<?= $item['id'] ?>" 
                                    data-name="<?= htmlspecialchars($item['name']) ?>" 
                                    data-price="<?= $item['price'] ?>">
                                <i class="fas fa-plus me-1"></i> Add
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </section>

    <!-- Cart Icon -->
    <div class="cart-icon" id="cartIcon">
        <i class="fas fa-shopping-cart"></i>
        <span class="cart-count" id="cartCount"><?= $cartCount ?></span>
    </div>

    <!-- Cart Sidebar -->
    <div class="cart-overlay" id="cartOverlay"></div>
    <div class="cart-sidebar" id="cartSidebar">
        <div class="close-cart" id="closeCart">&times;</div>
        <h3 class="mb-4">Your Order</h3>
        <div id="cartItems">
            <?php if (empty($_SESSION['cart'])): ?>
                <p class="text-center">Your cart is empty</p>
            <?php else: ?>
                <?php $total = 0; ?>
                <?php foreach ($_SESSION['cart'] as $id => $quantity): 
                    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id = ?");
                    $stmt->execute([$id]);
                    $item = $stmt->fetch(PDO::FETCH_ASSOC);
                    $subtotal = $item['price'] * $quantity;
                    $total += $subtotal;
                ?>
                    <div class="cart-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="cart-item-title"><?= htmlspecialchars($item['name']) ?></h6>
                                <small class="cart-item-price">Rp <?= number_format($item['price'], 0, ',', '.') ?> × <?= $quantity ?></small>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="cart-item-price">Rp <?= number_format($subtotal, 0, ',', '.') ?></span>
                                <button class="btn remove-item" data-id="<?= $id ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="mt-4 pt-3 border-top">
                    <div class="d-flex justify-content-between">
                        <h5 class="cart-total">Total:</h5>
                        <h5 class="cart-total">Total: Rp <?= number_format($total, 0, ',', '.') ?></h5>
                    </div>
                    <button class="btn checkout-btn text-white" id="checkoutBtn">
                        <i class="fas fa-credit-card me-2"></i>Checkout
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- About Section -->
    <section id="about" class="about-section">
        <div class="container">
            <h2 class="text-center section-title">Our Story</h2>
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <img src="https://images.unsplash.com/photo-1517248135467-4c7edcad34c4" class="img-fluid about-img" alt="Coffee Shop Interior">
                </div>
                <div class="col-lg-6">
                    <h3 class="about-title">DeCoffee</h3>
                    <p>Berdiri di sebuah lingkungan kecil, DeCoffee telah berkembang menjadi destinasi favorit para pencinta kopi. Kami memilih biji kopi terbaik dari perkebunan berkelanjutan di berbagai penjuru dunia dan memanggangnya secara sempurna di tempat kami sendiri.</p>
                    <p>Barista kami terlatih dalam seni meracik kopi, memastikan setiap cangkir yang disajikan adalah sebuah mahakarya. Selain kopi, kami juga menawarkan aneka kue rumahan dan hidangan ringan yang dibuat dari bahan-bahan lokal pilihan.</p>
                    <p>Di DeCoffee, kami percaya akan pentingnya menciptakan suasana hangat dan ramah, di mana setiap pelanggan merasa seperti di rumah sendiri. Misi kami adalah menyajikan kopi yang istimewa sekaligus membangun koneksi yang erat dengan komunitas.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact-section">
        <div class="container">
            <h2 class="text-center section-title">Visit Us</h2>
            <div class="row">
                <div class="col-md-6 mb-5 mb-md-0">
                    <h3 class="mb-4"><i class="fas fa-map-marker-alt contact-icon"></i>Location</h3>
                    <p class="contact-info">Di atas tanah Di bawah langit</p>
                    
                    <h3 class="mb-4"><i class="fas fa-clock contact-icon"></i>Hours</h3>
                    <p class="contact-info">Buka Setiap hari Jika ingin</p>
                    <p class="contact-info">kecuali malam</p>
                </div>
                <div class="col-md-6">
                    <h3 class="mb-4"><i class="fas fa-phone contact-icon"></i>Contact</h3>
                    <p class="contact-info"><i class="fas fa-phone-alt me-2"></i> (+62) 1234-56789</p>
                    <p class="contact-info"><i class="fas fa-envelope me-2"></i> decoffee@gmail.com</p>
                    
                    <h3 class="mb-4"><i class="fas fa-hashtag contact-icon"></i>Follow Us</h3>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-tiktok"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p class="footer-text">&copy; <?= date('Y') ?> DeCoffee. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Toggle cart sidebar
            $('#cartIcon, #closeCart, #cartOverlay').click(function() {
                $('#cartSidebar, #cartOverlay').toggleClass('active');
            });
            
            // Add to cart
            $('.add-to-cart').click(function() {
                const id = $(this).data('id');
                updateCart(id, 'add');
            });

            // Increase quantity
            $(document).on('click', '.increase-qty', function(e) {
                e.stopPropagation();
                const id = $(this).data('id');
                updateCart(id, 'add');
            });

            // Decrease quantity
            $(document).on('click', '.decrease-qty', function(e) {
                e.stopPropagation();
                const id = $(this).data('id');
                updateCart(id, 'decrease');
            });

            // Remove item
            $(document).on('click', '.remove-item', function(e) {
                e.stopPropagation();
                const id = $(this).data('id');
                
                // Show confirmation dialog
                Swal.fire({
                    title: 'Remove Item',
                    text: 'Are you sure you want to remove this item?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, remove it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        updateCart(id, 'remove');
                    }
                });
            });

            // Function to update cart
            function updateCart(id, action) {
                $.ajax({
                    url: 'add_to_cart.php',
                    method: 'POST',
                    data: {
                        id: id,
                        action: action
                    },
                    success: function(response) {
                        const data = JSON.parse(response);
                        $('#cartCount').text(data.count);
                        $('#cartItems').html(data.cartHtml);

                        // Show notification based on action
                        let message = '';
                        switch(action) {
                            case 'add':
                                message = 'Item added to cart';
                                break;
                            case 'decrease':
                                message = 'Item quantity decreased';
                                break;
                            case 'remove':
                                message = 'Item removed from cart';
                                break;
                        }

                        // Show toast notification
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: message,
                            showConfirmButton: false,
                            timer: 1500
                        });
                    },
                    error: function() {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'error',
                            title: 'Failed to update cart',
                            showConfirmButton: false,
                            timer: 1500
                        });
                    }
                });
            }
        });

$('#adminLogin').click(function(e) {
    e.preventDefault();
    Swal.fire({
        title: 'Admin Login',
        html: `
            <input type="text" id="username" class="swal2-input" placeholder="Username">
            <input type="password" id="password" class="swal2-input" placeholder="Password">
        `,
        confirmButtonText: 'Login',
        showCancelButton: true,
        focusConfirm: false,
        preConfirm: () => {
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            if (!username || !password) {
                Swal.showValidationMessage('Please enter username and password');
            }
            return { username, password }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Send AJAX request to check_admin.php
            $.ajax({
                url: 'check_admin.php',
                method: 'POST',
                data: {
                    username: result.value.username,
                    password: result.value.password
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Login Successful',
                            text: 'Opening admin panel...',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = 'up_menu.php';
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Access Denied',
                            text: 'Invalid username or password!'
                        });
                    }
                }
            });
        }
    });
});

// Add this to your index.php script section
function showReceiptPopup() {
    const customerName = $('#customerName').val();
    if (!customerName) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Please enter customer name'
        });
        return;
    }

    // Collect cart items
    const items = [];
    $('.cart-item').each(function() {
        const id = $(this).data('id');
        const name = $(this).find('.cart-item-title').text();
        const quantity = parseInt($(this).find('.quantity').text());
        const price = parseInt($(this).find('.item-total').text().replace(/[^0-9]/g, ''));
        items.push({ id, name, quantity, price: price/quantity });
    });

    const total = parseInt($('.cart-total').data('total'));
    const orderNotes = $('#orderNotes').val();

    // Generate receipt HTML
    let receiptHtml = `
        <div class="receipt">
            <h4 class="mb-3">Order Receipt</h4>
            <p><strong>Customer:</strong> ${customerName}</p>
            <p><strong>Date:</strong> ${new Date().toLocaleString()}</p>
            <hr>
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
    `;

    items.forEach(item => {
        receiptHtml += `
            <tr>
                <td>${item.name}</td>
                <td>${item.quantity}</td>
                <td>Rp ${number_format(item.price)}</td>
                <td>Rp ${number_format(item.price * item.quantity)}</td>
            </tr>
        `;
    });

    receiptHtml += `
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3">Total</th>
                        <th>Rp ${number_format(total)}</th>
                    </tr>
                </tfoot>
            </table>
            ${orderNotes ? `<p><strong>Notes:</strong> ${orderNotes}</p>` : ''}
        </div>
    `;

    Swal.fire({
        title: 'Order Summary',
        html: receiptHtml,
        showCancelButton: true,
        confirmButtonText: 'Confirm Order',
        cancelButtonText: 'Cancel',
        width: '600px'
    }).then((result) => {
        if (result.isConfirmed) {
            // Save order
           $.ajax({
                url: 'save_order.php',
                method: 'POST',
                data: {
                    customerName: customerName,
                    orderNotes: orderNotes,
                    items: items,
                    total: total
                },
                dataType: 'json', // Add this line to ensure JSON parsing
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: 'Order has been saved',
                            timer: 2000
                        }).then(() => {
                            // Clear cart display
                            $('#cartItems').html('<p class="text-center">Your cart is empty</p>');
                            $('#cartCount').text('0');
                            $('#cartSidebar, #cartOverlay').removeClass('active');
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'An error occurred'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Response:', xhr.responseText);
                    console.error('Status:', status);
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to process order'
                    });
                }
            });
        }
    });
}

function number_format(number) {
    return new Intl.NumberFormat('id-ID').format(number);
}
    </script>
</body>
</html>