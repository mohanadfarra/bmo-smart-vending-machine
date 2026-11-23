<?php
require_once 'manager/config/db.php';

// Get machine ID from URL
$machine_code = isset($_GET['machine']) ? $_GET['machine'] : null;

if (!$machine_code) {
    die("Please scan a valid QR code to access the vending machine.");
}

// Get machine ID based on the machine code
$stmt = $conn->prepare("SELECT id FROM vending_machines WHERE qr_code = ? AND status = 'active'");
$stmt->bind_param("s", $machine_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("This vending machine is currently unavailable.");
}

$machine = $result->fetch_assoc();
$machine_id = $machine['id'];

// Get machine information
$stmt = $conn->prepare("
    SELECT id, location, status 
    FROM vending_machines 
    WHERE id = ? AND status = 'active'
");
$stmt->bind_param("i", $machine_id);
$stmt->execute();
$machine = $stmt->get_result()->fetch_assoc();

if (!$machine) {
    die("This vending machine is currently unavailable.");
}

// Get products for this machine
$stmt = $conn->prepare("
    SELECT id, name, price_tnd, quantity, image_url 
    FROM products 
    WHERE machine_id = ? AND active = 1 AND quantity > 0
    ORDER BY name
");
$stmt->bind_param("i", $machine_id);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vending Machine - <?php echo htmlspecialchars($machine['location']); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Vending Machine</h1>
        <div class="location-info">
            <span><?php echo htmlspecialchars($machine['location']); ?></span>
        </div>
        <div class="cart-icon" onclick="toggleCart()">
            <span id="cart-count">0</span>
            <svg viewBox="0 0 24 24">
                <path d="M17,18C15.89,18 15,18.89 15,20A2,2 0 0,0 17,22A2,2 0 0,0 19,20C19,18.89 18.1,18 17,18M1,2V4H3L6.6,11.59L5.24,14.04C5.09,14.32 5,14.65 5,15A2,2 0 0,0 7,17H19V15H7.42A0.25,0.25 0 0,1 7.17,14.75C7.17,14.7 7.18,14.66 7.2,14.63L8.1,13H15.55C16.3,13 16.96,12.58 17.3,11.97L20.88,5.5C20.95,5.34 21,5.17 21,5A1,1 0 0,0 20,4H5.21L4.27,2M7,18C5.89,18 5,18.89 5,20A2,2 0 0,0 7,22A2,2 0 0,0 9,20C9,18.89 8.1,18 7,18Z" />
            </svg>
        </div>
    </header>

    <main>
        <section class="products">
            <?php foreach ($products as $product): ?>
            <div class="product-card" data-id="<?php echo $product['id']; ?>">
                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>">
                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                <p><?php echo number_format($product['price_tnd'], 3); ?> TND</p>
                <button class="add-to-cart" onclick="addToCart(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                    Add to Cart
                </button>
            </div>
            <?php endforeach; ?>
        </section>

        <!-- Side Cart -->
        <aside class="side-cart">
        <div class="close-cart" onclick="toggleCart()">&times;</div>
            <h2>Your Cart</h2>
            <div id="cart-items"></div>
            <div class="cart-total">
                <p>Total: <span id="total">0.000</span> TND</p>
            </div>
            <button id="checkout-btn" onclick="showPaymentModal()" class="checkout-button">
                Proceed to Payment
            </button>
        </aside>


        <!-- Payment Modal -->

<div id="paymentModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closePaymentModal()">&times;</span>
        <h2>Payment Details</h2>
        <div class="payment-steps">
            <div class="step active" id="step-1">
                <span class="step-number">1</span>
                <span class="step-text">Details</span>
            </div>
            <div class="step" id="step-2">
                <span class="step-number">2</span>
                <span class="step-text">Payment</span>
            </div>
            <div class="step" id="step-3">
                <span class="step-number">3</span>
                <span class="step-text">Confirmation</span>
            </div>
        </div>

        <!-- Step 1: Customer Details -->
        <div class="payment-step" id="details-step">
            <form id="customer-details-form">
                <div class="form-group">
                    <label for="customer-name">Full Name</label>
                    <input type="text" id="customer-name" name="customer-name" required>
                </div>
                <div class="form-group">
                    <label for="customer-phone">Phone Number</label>
                    <input type="tel" id="customer-phone" name="customer-phone" required>
                </div>
                <button type="button" class="btn-primary" onclick="proceedToPayment()">Continue to Payment</button>
            </form>
        </div>

        <!-- Step 2: Payment Information -->
        <div class="payment-step hidden" id="payment-step">
            <form id="payment-form">
                <div class="form-group">
                    <label for="card-number">Card Number</label>
                    <input type="text" id="card-number" name="card-number" required placeholder="1234 5678 9012 3456">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="expiry">Expiry Date</label>
                        <input type="text" id="expiry" name="expiry" placeholder="MM/YY" required>
                    </div>
                    <div class="form-group">
                        <label for="cvv">CVV</label>
                        <input type="text" id="cvv" name="cvv" placeholder="123" required>
                    </div>
                </div>
                <div class="amount-display">
                    <span>Total Amount:</span>
                    <span class="amount" id="payment-amount">0.000 TND</span>
                </div>
                <button type="button" class="btn-primary" onclick="processPayment()">Pay Now</button>
            </form>
        </div>

        <!-- Step 3: Confirmation -->
        <div class="payment-step hidden" id="confirmation-step">
            <div class="success-animation">
                <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                    <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/>
                    <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                </svg>
            </div>
            <h3>Payment Successful!</h3>
            <div class="collection-code">
                <p>Your Collection Code:</p>
                <div class="code" id="collection-code"></div>
            </div>
            <p class="instructions">
                Enter this code on the vending machine keypad to collect your items.
                <br>This code will expire in 15 minutes.
            </p>
        </div>
    </div>
</div>

        <div class="overlay"></div>
    </main>
    <script>

var machineId = <?php echo $machine_id; ?>;

    function addToCart(product) {
    const existingProduct = cart.find(item => item.id === product.id);
    if (existingProduct) {
        existingProduct.quantity += 1;
    } else {
        cart.push({
            ...product,
            quantity: 1,
            price_tnd: parseFloat(product.price_tnd)
        });
    }
    updateCart();
    showNotification(`${product.name} added to cart!`);
}

function updateCart() {
    const cartItems = document.getElementById('cart-items');
    const cartCount = document.getElementById('cart-count');
    const totalElement = document.getElementById('total');
    
    cartItems.innerHTML = '';
    
    cart.forEach(item => {
        const itemElement = document.createElement('div');
        itemElement.className = 'cart-item';
        itemElement.innerHTML = `
            <img src="${item.image_url}" alt="${item.name}">
            <div class="cart-item-details">
                <div class="cart-item-name">${item.name}</div>
                <div class="cart-item-price">${item.price_tnd.toFixed(3)} TND</div>
                <div class="cart-item-quantity">
                    <button onclick="updateQuantity(${item.id}, -1)">-</button>
                    <span>${item.quantity}</span>
                    <button onclick="updateQuantity(${item.id}, 1)">+</button>
                </div>
            </div>
            <div class="cart-item-remove" onclick="removeFromCart(${item.id})">&times;</div>
        `;
        cartItems.appendChild(itemElement);
    });

    cartCount.textContent = cart.reduce((sum, item) => sum + item.quantity, 0);
    totalElement.textContent = cart.reduce((sum, item) => sum + item.price_tnd * item.quantity, 0).toFixed(3);
}

    function updateQuantity(productId, change) {
        const product = cart.find(item => item.id === productId);
        if (product) {
            product.quantity += change;
            if (product.quantity <= 0) {
                removeFromCart(productId);
            } else {
                updateCart();
            }
        }
    }

    function removeFromCart(productId) {
        cart = cart.filter(item => item.id !== productId);
        updateCart();
    }

    function toggleCart() {
        document.querySelector('.side-cart').classList.toggle('open');
        document.querySelector('.overlay').classList.toggle('active');
    }

    function showPaymentModal() {
        if (cart.length === 0) {
            showNotification('Please add items to your cart first!', 'error');
            return;
        }
        document.getElementById('paymentModal').style.display = 'block';
        document.querySelector('.overlay').classList.add('active');
    }

    function closePaymentModal() {
        document.getElementById('paymentModal').style.display = 'none';
        document.querySelector('.overlay').classList.remove('active');
        resetPaymentSteps();
    }

    function proceedToPayment() {
        document.getElementById('details-step').classList.add('hidden');
        document.getElementById('payment-step').classList.remove('hidden');
        document.getElementById('step-1').classList.remove('active');
        document.getElementById('step-2').classList.add('active');
    }

    function processPayment() {
        const machineId = <?php echo $machine_id; ?>;
        const customerName = document.getElementById('customer-name').value.trim();
        // Simulate payment processing
        const paymentData = {
    machine_id: machineId,
    items: cart,
    total_amount: cart.reduce((sum, item) => sum + item.price_tnd * item.quantity, 0), // Calculate total correctly
    customer_name: document.getElementById('customer-name').value,
    customer_phone: document.getElementById('customer-phone').value
};

        // In a real application, send this to your server
        fetch('process_payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(paymentData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showConfirmation(data.collection_code);
            } else {
                showNotification('Payment failed. Please try again.', 'error');
            }
        })
        .catch(error => {
            showNotification('An error occurred. Please try again.', 'error');
        });
    }

    function showConfirmation(collectionCode) {
        document.getElementById('payment-step').classList.add('hidden');
        document.getElementById('confirmation-step').classList.remove('hidden');
        document.getElementById('step-2').classList.remove('active');
        document.getElementById('step-3').classList.add('active');
        document.getElementById('collection-code').textContent = collectionCode;
        
        // Clear cart after successful payment
        cart = [];
        updateCart();
    }

    function resetPaymentSteps() {
        document.querySelectorAll('.payment-step').forEach(step => {
            step.classList.add('hidden');
        });
        document.getElementById('details-step').classList.remove('hidden');
        document.querySelectorAll('.step').forEach(step => {
            step.classList.remove('active');
        });
        document.getElementById('step-1').classList.add('active');
        document.getElementById('customer-details-form').reset();
        document.getElementById('payment-form').reset();
    }

    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.classList.add('show');
        }, 10);

        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }
</script>

<script src="script.js"></script>

</body>
</html>