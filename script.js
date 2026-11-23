let cart = [];
let total = 0;

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
    total = 0;
    
    cart.forEach(item => {
        total += item.price_tnd * item.quantity;
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
    totalElement.textContent = total.toFixed(3);
    
    // Update payment amount in modal if it exists
    const paymentAmount = document.getElementById('payment-amount');
    if (paymentAmount) {
        paymentAmount.textContent = total.toFixed(3) + ' TND';
    }
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
    
    // Reset the form and show first step
    resetPaymentSteps();
    
    document.getElementById('paymentModal').style.display = 'block';
    document.querySelector('.overlay').classList.add('active');
}

function closePaymentModal() {
    document.getElementById('paymentModal').style.display = 'none';
    document.querySelector('.overlay').classList.remove('active');
    resetPaymentSteps();
}

// Format card number while typing
document.addEventListener('DOMContentLoaded', function() {
    const cardInput = document.getElementById('card-number');
    if (cardInput) {
        cardInput.addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 16) {
                value = value.slice(0, 16);
            }
            this.value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
        });
    }

    const expiryInput = document.getElementById('expiry');
    if (expiryInput) {
        expiryInput.addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 4) {
                value = value.slice(0, 4);
            }
            if (value.length >= 2) {
                this.value = value.slice(0, 2) + '/' + value.slice(2);
            } else {
                this.value = value;
            }
        });
    }

    const cvvInput = document.getElementById('cvv');
    if (cvvInput) {
        cvvInput.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').slice(0, 3);
        });
    }
});

function proceedToPayment() {
    const name = document.getElementById('name').value.trim();
    const phone = document.getElementById('phone').value.trim();

    // Basic validation
    if (name.length < 3) {
        showNotification('Please enter a valid name', 'error');
        return;
    }
    if (phone.length < 8) {
        showNotification('Please enter a valid phone number', 'error');
        return;
    }

    document.getElementById('details-step').classList.add('hidden');
    document.getElementById('payment-step').classList.remove('hidden');
    document.getElementById('step-1').classList.remove('active');
    document.getElementById('step-2').classList.add('active');
}

function processPayment() {
    const cardNumber = document.getElementById('card-number').value.replace(/\s/g, '');
    const expiry = document.getElementById('expiry').value;
    const cvv = document.getElementById('cvv').value;

    // Validation
    if (cardNumber.length !== 16) {
        showNotification('Please enter a valid 16-digit card number', 'error');
        return;
    }

    if (!expiry.match(/^(0[1-9]|1[0-2])\/\d{2}$/)) {
        showNotification('Please enter a valid expiry date (MM/YY)', 'error');
        return;
    }

    if (cvv.length !== 3) {
        showNotification('Please enter a valid 3-digit CVV', 'error');
        return;
    }

    // Validate expiry date is not in the past
    const [expiryMonth, expiryYear] = expiry.split('/');
    const currentDate = new Date();
    const currentYear = currentDate.getFullYear() % 100;
    const currentMonth = currentDate.getMonth() + 1;

    if (parseInt(expiryYear) < currentYear || 
        (parseInt(expiryYear) === currentYear && parseInt(expiryMonth) < currentMonth)) {
        showNotification('Card has expired', 'error');
        return;
    }

    // Show loading state
    const paymentStep = document.getElementById('payment-step');
    paymentStep.classList.add('loading', 'with-spinner');

    // Simulate payment processing
    setTimeout(() => {
        paymentStep.classList.remove('loading', 'with-spinner');
        
        // Generate collection code (random 6 character alphanumeric)
        const collectionCode = Math.random().toString(36).substring(2, 8).toUpperCase();
        document.getElementById('collection-code').textContent = collectionCode;

        // Show confirmation
        document.getElementById('payment-step').classList.add('hidden');
        document.getElementById('confirmation-step').classList.remove('hidden');
        document.getElementById('step-2').classList.remove('active');
        document.getElementById('step-3').classList.add('active');

        // Clear cart
        cart = [];
        updateCart();
    }, 2000); // 2 second delay to simulate processing
}

function resetPaymentSteps() {
    // Reset all forms
    document.getElementById('customer-details-form').reset();
    document.getElementById('payment-form').reset();

    // Hide all steps except the first
    document.querySelectorAll('.payment-step').forEach(step => {
        step.classList.add('hidden');
    });
    document.getElementById('details-step').classList.remove('hidden');

    // Reset step indicators
    document.querySelectorAll('.step').forEach(step => {
        step.classList.remove('active');
    });
    document.getElementById('step-1').classList.add('active');
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

// Close cart when clicking outside
document.addEventListener('click', (e) => {
    const sideCart = document.querySelector('.side-cart');
    const cartIcon = document.querySelector('.cart-icon');
    if (!sideCart.contains(e.target) && !cartIcon.contains(e.target)) {
        sideCart.classList.remove('open');
        document.querySelector('.overlay').classList.remove('active');
    }
});
// Add this updated JavaScript code to your existing script.js file
document.addEventListener('DOMContentLoaded', function() {
  // Input formatting for card number
  const cardInput = document.getElementById('card-number');
  if (cardInput) {
      cardInput.addEventListener('input', function(e) {
          let value = this.value.replace(/\D/g, '');
          if (value.length > 16) {
              value = value.slice(0, 16);
          }
          // Add space after every 4 digits
          value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
          this.value = value;
      });
  }

  // Input formatting for expiry date
  const expiryInput = document.getElementById('expiry');
  if (expiryInput) {
      expiryInput.addEventListener('input', function(e) {
          let value = this.value.replace(/\D/g, '');
          if (value.length > 4) {
              value = value.slice(0, 4);
          }
          if (value.length >= 2) {
              value = value.slice(0, 2) + (value.length > 2 ? '/' + value.slice(2) : '');
          }
          this.value = value;
      });
  }

  // Input formatting for CVV
  const cvvInput = document.getElementById('cvv');
  if (cvvInput) {
      cvvInput.addEventListener('input', function(e) {
          this.value = this.value.replace(/\D/g, '').slice(0, 3);
      });
  }
});

function proceedToPayment() {
  const name = document.getElementById('customer-name').value.trim();
  const phone = document.getElementById('customer-phone').value.trim();

  // Validate name (at least 3 characters)
  if (name.length < 3) {
      showNotification('Please enter a valid name (minimum 3 characters)', 'error');
      return;
  }

  // Validate phone (at least 8 digits)
  const phoneDigits = phone.replace(/\D/g, '');
  if (phoneDigits.length < 8) {
      showNotification('Please enter a valid phone number (minimum 8 digits)', 'error');
      return;
  }

  // Proceed to payment step
  document.getElementById('details-step').classList.add('hidden');
  document.getElementById('payment-step').classList.remove('hidden');
  document.getElementById('step-1').classList.remove('active');
  document.getElementById('step-2').classList.add('active');
}

// Add this function to check stock before processing payment
function validateStock() {
    // Show loading state
    const paymentStep = document.getElementById('payment-step');
    paymentStep.classList.add('loading', 'with-spinner');

    // Prepare stock check data
    const stockCheckData = {
        machine_id: machineId,
        items: cart.map(item => ({
            id: item.id,
            quantity: item.quantity
        }))
    };

    // Check stock availability first
    return fetch('check_stock.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(stockCheckData)
    })
    .then(response => response.json())
    .then(data => {
        paymentStep.classList.remove('loading', 'with-spinner');
        
        if (!data.success) {
            const outOfStockItems = data.items.map(item => item.name).join(', ');
            showNotification(`Some items are out of stock: ${outOfStockItems}`, 'error');
            return false;
        }
        return true;
    })
    .catch(error => {
        paymentStep.classList.remove('loading', 'with-spinner');
        showNotification('Error checking stock availability', 'error');
        console.error('Stock check error:', error);
        return false;
    });
}

// Updated payment processing function
function processPayment() {
    const cardNumber = document.getElementById('card-number').value.replace(/\s/g, '');
    const expiry = document.getElementById('expiry').value;
    const cvv = document.getElementById('cvv').value;
    const customerName = document.getElementById('customer-name').value.trim();
    const customerPhone = document.getElementById('customer-phone').value.trim();

    // Basic validations
    if (cardNumber.length !== 16) {
        showNotification('Please enter a valid 16-digit card number', 'error');
        return;
    }

    if (!expiry.match(/^(0[1-9]|1[0-2])\/\d{2}$/)) {
        showNotification('Please enter a valid expiry date (MM/YY)', 'error');
        return;
    }

    // Validate expiry date is not in the past
    const [expiryMonth, expiryYear] = expiry.split('/');
    const currentDate = new Date();
    const currentYear = currentDate.getFullYear() % 100;
    const currentMonth = currentDate.getMonth() + 1;

    if (parseInt(expiryYear) < currentYear || 
        (parseInt(expiryYear) === currentYear && parseInt(expiryMonth) < currentMonth)) {
        showNotification('Card has expired', 'error');
        return;
    }

    if (cvv.length !== 3) {
        showNotification('Please enter a valid 3-digit CVV', 'error');
        return;
    }

    // First validate stock
    validateStock().then(stockAvailable => {
        if (!stockAvailable) {
            return;
        }

        // Show loading state
        const paymentStep = document.getElementById('payment-step');
        paymentStep.classList.add('loading', 'with-spinner');

        // Prepare payment data
        const paymentData = {
            machine_id: machineId,
            items: cart,
            total_amount: cart.reduce((sum, item) => sum + item.price_tnd * item.quantity, 0),
            customer_name: customerName,
            customer_phone: customerPhone
        };

        // Process payment
        fetch('payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(paymentData)
        })
        .then(response => response.json())
        .then(data => {
            paymentStep.classList.remove('loading', 'with-spinner');

            if (data.success) {
                // Show confirmation
                document.getElementById('collection-code').textContent = data.collection_code;
                document.getElementById('payment-step').classList.add('hidden');
                document.getElementById('confirmation-step').classList.remove('hidden');
                document.getElementById('step-2').classList.remove('active');
                document.getElementById('step-3').classList.add('active');

                // Clear cart
                cart = [];
                updateCart();

                showNotification('Payment successful!', 'success');
            } else {
                showNotification(data.message || 'Payment failed. Please try again.', 'error');
            }
        })
        .catch(error => {
            paymentStep.classList.remove('loading', 'with-spinner');
            showNotification('An error occurred. Please try again.', 'error');
            console.error('Payment error:', error);
        });
    });
}

// Prevent cart menu from closing when interacting with cart items
document.querySelector('.side-cart').addEventListener('click', (e) => {
    e.stopPropagation();
});