<?php
require_once 'includes/header.php';

// Get machine ID from URL if provided
$machine_id = isset($_GET['machine']) ? (int)$_GET['machine'] : null;

// Handle product operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = trim($_POST['name']);
                $price = (float)$_POST['price'];
                $quantity = (int)$_POST['quantity'];
                $machine_id = (int)$_POST['machine_id'];
                $min_stock = (int)$_POST['min_stock'];
                
                // Handle image upload
                $image_url = '';
                if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $upload_dir = '../uploads/products/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $file_name = uniqid('product_') . '.' . $file_extension;
                    $target_path = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                        $image_url = 'uploads/products/' . $file_name;
                    }
                }
                
                $stmt = $conn->prepare("INSERT INTO products (machine_id, name, price_tnd, quantity, image_url, min_stock_alert) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isdisd", $machine_id, $name, $price, $quantity, $image_url, $min_stock);
                $stmt->execute();
                $_SESSION['success'] = "Product added successfully!";
                break;

            case 'edit':
                $id = (int)$_POST['product_id'];
                $name = trim($_POST['name']);
                $price = (float)$_POST['price'];
                $quantity = (int)$_POST['quantity'];
                $min_stock = (int)$_POST['min_stock'];
                $active = isset($_POST['active']) ? 1 : 0;
                
                // Check if new image is uploaded
                if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $upload_dir = '../uploads/products/';
                    $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $file_name = uniqid('product_') . '.' . $file_extension;
                    $target_path = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                        // Delete old image if exists
                        $stmt = $conn->prepare("SELECT image_url FROM products WHERE id = ?");
                        $stmt->bind_param("i", $id);
                        $stmt->execute();
                        $old_image = $stmt->get_result()->fetch_assoc()['image_url'];
                        if ($old_image && file_exists('../' . $old_image)) {
                            unlink('../' . $old_image);
                        }
                        
                        $image_url = 'uploads/products/' . $file_name;
                        $stmt = $conn->prepare("UPDATE products SET name = ?, price_tnd = ?, quantity = ?, min_stock_alert = ?, active = ?, image_url = ? WHERE id = ?");
                        $stmt->bind_param("sdiiisi", $name, $price, $quantity, $min_stock, $active, $image_url, $id);
                    }
                } else {
                    $stmt = $conn->prepare("UPDATE products SET name = ?, price_tnd = ?, quantity = ?, min_stock_alert = ?, active = ? WHERE id = ?");
                    $stmt->bind_param("sdiiii", $name, $price, $quantity, $min_stock, $active, $id);
                }
                
                $stmt->execute();
                $_SESSION['success'] = "Product updated successfully!";
                break;

            case 'update_stock':
                $id = (int)$_POST['product_id'];
                $quantity = (int)$_POST['quantity'];
                $stmt = $conn->prepare("UPDATE products SET quantity = ?, last_restock = NOW() WHERE id = ?");
                $stmt->bind_param("ii", $quantity, $id);
                $stmt->execute();
                
                // Update machine's last_restock time
                $stmt = $conn->prepare("UPDATE vending_machines SET last_restock = NOW() WHERE id = (SELECT machine_id FROM products WHERE id = ?)");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                
                $_SESSION['success'] = "Stock updated successfully!";
                break;

            case 'delete':
                $id = (int)$_POST['product_id'];
                // First check if product has any pending transactions
                $check = $conn->prepare("SELECT COUNT(*) as count FROM transactions WHERE JSON_CONTAINS(items, ?, '$.id') AND status = 'pending'");
                $product_json = json_encode(['id' => $id]);
                $check->bind_param("s", $product_json);
                $check->execute();
                $pending = $check->get_result()->fetch_assoc()['count'];
                
                if ($pending > 0) {
                    $_SESSION['error'] = "Cannot delete product with pending transactions.";
                } else {
                    // Delete image file if exists
                    $stmt = $conn->prepare("SELECT image_url FROM products WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $image_url = $stmt->get_result()->fetch_assoc()['image_url'];
                    if ($image_url && file_exists('../' . $image_url)) {
                        unlink('../' . $image_url);
                    }
                    
                    // Delete product
                    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $_SESSION['success'] = "Product deleted successfully!";
                }
                break;
        }
        
        // Redirect back to the same page with machine filter if it exists
        $redirect = 'products.php';
        if ($machine_id) {
            $redirect .= "?machine=" . $machine_id;
        }
        header("Location: " . $redirect);
        exit();
    }
}

// Get all machines for the filter dropdown
$machines = $conn->query("SELECT id, location FROM vending_machines ORDER BY location");

// Build the products query
$products_query = "
    SELECT 
        p.*,
        v.location as machine_location,
        CASE 
            WHEN p.quantity <= p.min_stock_alert THEN 1 
            ELSE 0 
        END as low_stock
    FROM products p
    JOIN vending_machines v ON p.machine_id = v.id
";

if ($machine_id) {
    $products_query .= " WHERE p.machine_id = " . $machine_id;
}

$products_query .= " ORDER BY p.machine_id, p.name";
$products = $conn->query($products_query);
?>

<div class="page-header">
    <h1>Products Management</h1>
    <div class="header-actions">
        <select id="machine-filter" class="form-select" onchange="filterByMachine(this.value)">
            <option value="">All Machines</option>
            <?php while ($machine = $machines->fetch_assoc()): ?>
            <option value="<?php echo $machine['id']; ?>" <?php echo $machine_id == $machine['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($machine['location']); ?>
            </option>
            <?php endwhile; ?>
        </select>
        <button onclick="showAddProductModal()" class="btn-primary">Add New Product</button>
    </div>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <?php 
        echo $_SESSION['success']; 
        unset($_SESSION['success']);
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-error">
        <?php 
        echo $_SESSION['error']; 
        unset($_SESSION['error']);
        ?>
    </div>
<?php endif; ?>

<div class="products-grid">
    <?php while ($product = $products->fetch_assoc()): ?>
    <div class="product-card <?php echo $product['active'] ? '' : 'inactive'; ?>">
        <div class="product-image">
            <?php if ($product['image_url']): ?>
                <img src="../<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
            <?php else: ?>
                <div class="no-image">No Image</div>
            <?php endif; ?>
        </div>
        
        <div class="product-info">
            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
            <p class="price"><?php echo number_format($product['price_tnd'], 3); ?> TND</p>
            <p class="location">Machine: <?php echo htmlspecialchars($product['machine_location']); ?></p>
            
            <div class="stock-info <?php echo $product['low_stock'] ? 'low-stock' : ''; ?>">
                <span class="stock-label">In Stock:</span>
                <span class="stock-value"><?php echo $product['quantity']; ?></span>
                <?php if ($product['low_stock']): ?>
                    <span class="stock-alert">Low Stock Alert!</span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="product-actions">
            <button onclick="showEditProductModal(<?php echo htmlspecialchars(json_encode($product)); ?>)" class="btn-small">
                Edit
            </button>
            <button onclick="showUpdateStockModal(<?php echo $product['id']; ?>, <?php echo $product['quantity']; ?>)" class="btn-small">
                Update Stock
            </button>
            <button onclick="confirmDelete(<?php echo $product['id']; ?>)" class="btn-small btn-danger">
                Delete
            </button>
        </div>
    </div>
    <?php endwhile; ?>
</div>

<!-- Add Product Modal -->
<div id="addProductModal" class="modal">
    <div class="modal-content">
        <h2>Add New Product</h2>
        <form action="products.php<?php echo $machine_id ? "?machine=$machine_id" : ''; ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label for="machine_id">Machine</label>
                <select id="machine_id" name="machine_id" required>
                    <?php 
                    $machines->data_seek(0);
                    while ($machine = $machines->fetch_assoc()): 
                    ?>
                    <option value="<?php echo $machine['id']; ?>" <?php echo $machine_id == $machine['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($machine['location']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="name">Product Name</label>
                <input type="text" id="name" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="price">Price (TND)</label>
                <input type="number" id="price" name="price" step="0.001" min="0" required>
            </div>
            
            <div class="form-group">
                <label for="quantity">Initial Stock</label>
                <input type="number" id="quantity" name="quantity" min="0" required>
            </div>
            
            <div class="form-group">
                <label for="min_stock">Minimum Stock Alert</label>
                <input type="number" id="min_stock" name="min_stock" min="0" required>
            </div>
            
            <div class="form-group">
                <label for="image">Product Image</label>
                <input type="file" id="image" name="image" accept="image/*">
            </div>
            
            <div class="form-actions">
                <button type="button" onclick="closeModal('addProductModal')" class="btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary">Add Product</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Product Modal -->
<div id="editProductModal" class="modal">
    <div class="modal-content">
        <h2>Edit Product</h2>
        <form action="products.php<?php echo $machine_id ? "?machine=$machine_id" : ''; ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="product_id" id="edit_product_id">
            
            <div class="form-group">
                <label for="edit_name">Product Name</label>
                <input type="text" id="edit_name" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="edit_price">Price (TND)</label>
                <input type="number" id="edit_price" name="price" step="0.001" min="0" required>
            </div>
            
            <div class="form-group">
                <label for="edit_quantity">Current Stock</label>
                <input type="number" id="edit_quantity" name="quantity" min="0" required>
            </div>
            
            <div class="form-group">
                <label for="edit_min_stock">Minimum Stock Alert</label>
                <input type="number" id="edit_min_stock" name="min_stock" min="0" required>
            </div>
            
            <div class="form-group">
                <label for="edit_image">Product Image</label>
                <input type="file" id="edit_image" name="image" accept="image/*">
                <div id="current_image"></div>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="active" id="edit_active" value="1">
                    Product Active
                </label>
            </div>
            
            <div class="form-actions">
                <button type="button" onclick="closeModal('editProductModal')" class="btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary">Update Product</button>
            </div>
        </form>
    </div>
</div>

<!-- Update Stock Modal -->
<div id="updateStockModal" class="modal">
    <div class="modal-content">
        <h2>Update Stock</h2>
        <form action="products.php<?php echo $machine_id ? "?machine=$machine_id" : ''; ?>" method="POST">
            <input type="hidden" name="action" value="update_stock">
            <input type="hidden" name="product_id" id="stock_product_id">
            
            <div class="form-group">
                <label for="stock_quantity">New Stock Quantity</label>
                <input type="number" id="stock_quantity" name="quantity" min="0" required>
            </div>
            
            <div class="form-actions">
                <button type="button" onclick="closeModal('updateStockModal')" class="btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary">Update Stock</button>
            </div>
        </form>
    </div>
</div>

<script>
function filterByMachine(machineId) {
    window.location.href = machineId ? `products.php?machine=${machineId}` : 'products.php';
}

function showAddProductModal() {
    document.getElementById('addProductModal').style.display = 'block';
}

function showEditProductModal(product) {
    document.getElementById('edit_product_id').value = product.id;
    document.getElementById('edit_name').value = product.name;
    document.getElementById('edit_price').value = product.price_tnd;
    document.getElementById('edit_quantity').value = product.quantity;
    document.getElementById('edit_min_stock').value = product.min_stock_alert;
    document.getElementById('edit_active').checked = product.active == 1;
    
    const currentImage = document.getElementById('current_image');
    if (product.image_url) {
        currentImage.innerHTML = `<img src="../${product.image_url}" alt="${product.name}" style="max-width: 100px; margin-top: 10px;">`;
    } else {
        currentImage.innerHTML = '<p>No current image</p>';
    }
    
    document.getElementById('editProductModal').style.display = 'block';
}

function showUpdateStockModal(productId, currentQuantity) {
    document.getElementById('stock_product_id').value = productId;
    document.getElementById('stock_quantity').value = currentQuantity;
    document.getElementById('updateStockModal').style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function confirmDelete(productId) {
    if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'products.php<?php echo $machine_id ? "?machine=$machine_id" : ''; ?>';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete';
        
        const productInput = document.createElement('input');
        productInput.type = 'hidden';
        productInput.name = 'product_id';
        productInput.value = productId;
        
        form.appendChild(actionInput);
        form.appendChild(productInput);
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modals when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>