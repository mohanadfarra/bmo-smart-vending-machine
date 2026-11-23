<?php
require_once 'includes/header.php';

// Handle transaction status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $transaction_id = (int)$_POST['transaction_id'];
        $status = $_POST['status'];
        
        $stmt = $conn->prepare("UPDATE transactions SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $transaction_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Transaction status updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update transaction status.";
        }
        
        header("Location: transactions.php");
        exit();
    }
}

// Get filter parameters
$machine_id = isset($_GET['machine']) ? (int)$_GET['machine'] : null;
$status = isset($_GET['status']) ? $_GET['status'] : null;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : null;
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : null;

// Build query conditions
$conditions = [];
$params = [];
$types = "";

if ($machine_id) {
    $conditions[] = "t.machine_id = ?";
    $params[] = $machine_id;
    $types .= "i";
}

if ($status) {
    $conditions[] = "t.status = ?";
    $params[] = $status;
    $types .= "s";
}

if ($date_from) {
    $conditions[] = "DATE(t.created_at) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if ($date_to) {
    $conditions[] = "DATE(t.created_at) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

// Build the query
$query = "
    SELECT 
        t.*,
        v.location as machine_location
    FROM transactions t
    JOIN vending_machines v ON t.machine_id = v.id
";

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " ORDER BY t.created_at DESC";

// Get all machines for filter
$machines = $conn->query("SELECT id, location FROM vending_machines ORDER BY location");

// Prepare and execute the transaction query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$transactions = $stmt->get_result();

// Calculate totals
$total_amount = 0;
$status_counts = ['pending' => 0, 'completed' => 0, 'failed' => 0];
?>

<div class="page-header">
    <h1>Transactions History</h1>
</div>

<!-- Filters -->
<div class="filters-section">
    <form method="GET" class="filters-form">
        <div class="filter-group">
            <label for="machine">Machine</label>
            <select name="machine" id="machine" class="form-select">
                <option value="">All Machines</option>
                <?php while ($machine = $machines->fetch_assoc()): ?>
                <option value="<?php echo $machine['id']; ?>" <?php echo $machine_id == $machine['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($machine['location']); ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="filter-group">
            <label for="status">Status</label>
            <select name="status" id="status" class="form-select">
                <option value="">All Status</option>
                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="failed" <?php echo $status === 'failed' ? 'selected' : ''; ?>>Failed</option>
            </select>
        </div>

        <div class="filter-group">
            <label for="date_from">Date From</label>
            <input type="date" name="date_from" id="date_from" value="<?php echo $date_from; ?>" class="form-input">
        </div>

        <div class="filter-group">
            <label for="date_to">Date To</label>
            <input type="date" name="date_to" id="date_to" value="<?php echo $date_to; ?>" class="form-input">
        </div>

        <button type="submit" class="btn-primary">Apply Filters</button>
        <a href="transactions.php" class="btn-secondary">Clear Filters</a>
    </form>
</div>

<!-- Transactions Summary -->
<div class="summary-cards">
    <?php
    $transactions_data = $transactions->fetch_all(MYSQLI_ASSOC);
    foreach ($transactions_data as $transaction) {
        $total_amount += $transaction['total_amount'];
        $status_counts[$transaction['status']]++;
    }
    ?>
    <div class="summary-card">
        <h3>Total Revenue</h3>
        <div class="value"><?php echo number_format($total_amount, 3); ?> TND</div>
    </div>
    <div class="summary-card">
        <h3>Total Transactions</h3>
        <div class="value"><?php echo count($transactions_data); ?></div>
    </div>
    <div class="summary-card">
        <h3>Pending</h3>
        <div class="value"><?php echo $status_counts['pending']; ?></div>
    </div>
    <div class="summary-card">
        <h3>Completed</h3>
        <div class="value"><?php echo $status_counts['completed']; ?></div>
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

<!-- Transactions Table -->
<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Machine</th>
                <th>Items</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Code</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions_data as $transaction): ?>
            <tr>
                <td>#<?php echo $transaction['id']; ?></td>
                <td><?php echo htmlspecialchars($transaction['machine_location']); ?></td>
                <td>
                    <button onclick="showItems('<?php echo htmlspecialchars($transaction['items']); ?>')" class="btn-small">
                        View Items
                    </button>
                </td>
                <td><?php echo number_format($transaction['total_amount'], 3); ?> TND</td>
                <td>
                    <span class="status-badge status-<?php echo $transaction['status']; ?>">
                        <?php echo ucfirst($transaction['status']); ?>
                    </span>
                </td>
                <td><?php echo $transaction['unique_code']; ?></td>
                <td><?php echo date('Y-m-d H:i', strtotime($transaction['created_at'])); ?></td>
                <td>
                    <?php if ($transaction['status'] === 'pending'): ?>
                    <button onclick="updateStatus(<?php echo $transaction['id']; ?>)" class="btn-small">
                        Update Status
                    </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Items Modal -->
<div id="itemsModal" class="modal">
    <div class="modal-content">
        <h2>Transaction Items</h2>
        <div id="itemsList"></div>
        <div class="form-actions">
            <button onclick="closeModal('itemsModal')" class="btn-primary">Close</button>
        </div>
    </div>
</div>

<!-- Status Update Modal -->
<div id="statusModal" class="modal">
    <div class="modal-content">
        <h2>Update Transaction Status</h2>
        <form action="transactions.php" method="POST">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="transaction_id" id="status_transaction_id">
            
            <div class="form-group">
                <label for="new_status">New Status</label>
                <select name="status" id="new_status" required class="form-select">
                    <option value="completed">Completed</option>
                    <option value="failed">Failed</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="button" onclick="closeModal('statusModal')" class="btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary">Update Status</button>
            </div>
        </form>
    </div>
</div>

<script>
function showItems(itemsJson) {
    const items = JSON.parse(itemsJson);
    const itemsList = document.getElementById('itemsList');
    
    let html = '<table class="items-table">';
    html += '<thead><tr><th>Product</th><th>Quantity</th><th>Price</th><th>Total</th></tr></thead>';
    html += '<tbody>';
    
    items.forEach(item => {
        html += `<tr>
            <td>${item.name}</td>
            <td>${item.quantity}</td>
            <td>${item.price.toFixed(3)} TND</td>
            <td>${(item.price * item.quantity).toFixed(3)} TND</td>
        </tr>`;
    });
    
    html += '</tbody></table>';
    itemsList.innerHTML = html;
    document.getElementById('itemsModal').style.display = 'block';
}

function updateStatus(transactionId) {
    document.getElementById('status_transaction_id').value = transactionId;
    document.getElementById('statusModal').style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Close modals when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}

// Initialize date filters with default values if empty
document.addEventListener('DOMContentLoaded', function() {
    const dateFrom = document.getElementById('date_from');
    const dateTo = document.getElementById('date_to');
    
    if (!dateFrom.value) {
        const lastMonth = new Date();
        lastMonth.setMonth(lastMonth.getMonth() - 1);
        dateFrom.value = lastMonth.toISOString().split('T')[0];
    }
    
    if (!dateTo.value) {
        const today = new Date();
        dateTo.value = today.toISOString().split('T')[0];
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>