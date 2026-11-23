<?php
require_once 'includes/header.php';

// Handle machine operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $location = trim($_POST['location']);
                $qr_code = uniqid('VM_');
                $stmt = $conn->prepare("INSERT INTO vending_machines (location, qr_code, manager_id) VALUES (?, ?, ?)");
                $stmt->bind_param("ssi", $location, $qr_code, $_SESSION['manager_id']);
                $stmt->execute();
                $_SESSION['success'] = "Machine added successfully!";
                break;

            case 'edit':
                $id = $_POST['machine_id'];
                $location = trim($_POST['location']);
                $status = $_POST['status'];
                $stmt = $conn->prepare("UPDATE vending_machines SET location = ?, status = ? WHERE id = ?");
                $stmt->bind_param("ssi", $location, $status, $id);
                $stmt->execute();
                $_SESSION['success'] = "Machine updated successfully!";
                break;

            case 'delete':
                $id = $_POST['machine_id'];
                // First check if there are any active products or pending transactions
                $check = $conn->prepare("SELECT 
                    (SELECT COUNT(*) FROM products WHERE machine_id = ?) as products,
                    (SELECT COUNT(*) FROM transactions WHERE machine_id = ? AND status = 'pending') as pending_transactions");
                $check->bind_param("ii", $id, $id);
                $check->execute();
                $result = $check->get_result()->fetch_assoc();
                
                if ($result['products'] > 0 || $result['pending_transactions'] > 0) {
                    $_SESSION['error'] = "Cannot delete machine with active products or pending transactions.";
                } else {
                    $stmt = $conn->prepare("DELETE FROM vending_machines WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $_SESSION['success'] = "Machine deleted successfully!";
                }
                break;
        }
        header("Location: machines.php");
        exit();
    }
}

// Get all machines with their stats
$machines_query = "
    SELECT 
        v.*,
        COUNT(DISTINCT p.id) as total_products,
        SUM(p.quantity) as total_items,
        COUNT(DISTINCT CASE WHEN p.quantity <= p.min_stock_alert THEN p.id END) as low_stock_count
    FROM vending_machines v
    LEFT JOIN products p ON v.id = p.machine_id
    GROUP BY v.id
    ORDER BY v.location
";
$machines = $conn->query($machines_query);
?>

<div class="page-header">
    <h1>Vending Machines Management</h1>
    <button onclick="showAddMachineModal()" class="btn-primary">Add New Machine</button>
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

<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>Location</th>
                <th>QR Code</th>
                <th>Status</th>
                <th>Products</th>
                <th>Items in Stock</th>
                <th>Low Stock Alerts</th>
                <th>Last Restocked</th>
                <th>Revenue (TND)</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($machine = $machines->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($machine['location']); ?></td>
                <td>
                    <button onclick="showQRCode('<?php echo $machine['qr_code']; ?>')" class="btn-small">
                        View QR
                    </button>
                </td>
                <td>
                    <span class="status-badge status-<?php echo $machine['status']; ?>">
                        <?php echo ucfirst($machine['status']); ?>
                    </span>
                </td>
                <td><?php echo $machine['total_products']; ?></td>
                <td><?php echo $machine['total_items'] ?? 0; ?></td>
                <td>
                    <?php if ($machine['low_stock_count'] > 0): ?>
                        <span class="alert-badge"><?php echo $machine['low_stock_count']; ?></span>
                    <?php else: ?>
                        0
                    <?php endif; ?>
                </td>
                <td><?php echo $machine['last_restock'] ? date('Y-m-d H:i', strtotime($machine['last_restock'])) : 'Never'; ?></td>
                <td><?php echo number_format($machine['total_revenue'], 3); ?></td>
                <td class="actions">
                    <button onclick="showEditMachineModal(<?php echo htmlspecialchars(json_encode($machine)); ?>)" class="btn-small">
                        Edit
                    </button>
                    <button onclick="showProducts(<?php echo $machine['id']; ?>)" class="btn-small">
                        Products
                    </button>
                    <button onclick="confirmDelete(<?php echo $machine['id']; ?>)" class="btn-small btn-danger">
                        Delete
                    </button>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Add Machine Modal -->
<div id="addMachineModal" class="modal">
    <div class="modal-content">
        <h2>Add New Machine</h2>
        <form action="machines.php" method="POST">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label for="location">Location</label>
                <input type="text" id="location" name="location" required>
            </div>
            
            <div class="form-actions">
                <button type="button" onclick="closeModal('addMachineModal')" class="btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary">Add Machine</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Machine Modal -->
<div id="editMachineModal" class="modal">
    <div class="modal-content">
        <h2>Edit Machine</h2>
        <form action="machines.php" method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="machine_id" id="edit_machine_id">
            
            <div class="form-group">
                <label for="edit_location">Location</label>
                <input type="text" id="edit_location" name="location" required>
            </div>
            
            <div class="form-group">
                <label for="edit_status">Status</label>
                <select id="edit_status" name="status" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="maintenance">Maintenance</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="button" onclick="closeModal('editMachineModal')" class="btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary">Update Machine</button>
            </div>
        </form>
    </div>
</div>

<!-- QR Code Modal -->
<div id="qrModal" class="modal">
    <div class="modal-content">
        <h2>Machine QR Code</h2>
        <div id="qrcode"></div>
        <div class="form-actions">
            <button onclick="closeModal('qrModal')" class="btn-primary">Close</button>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
function showAddMachineModal() {
    document.getElementById('addMachineModal').style.display = 'block';
}

function showEditMachineModal(machine) {
    document.getElementById('edit_machine_id').value = machine.id;
    document.getElementById('edit_location').value = machine.location;
    document.getElementById('edit_status').value = machine.status;
    document.getElementById('editMachineModal').style.display = 'block';
}

function showQRCode(qrCode) {
    const modal = document.getElementById('qrModal');
    const qrContainer = document.getElementById('qrcode');
    qrContainer.innerHTML = '';
    
    // Create a link element
    const link = document.createElement('a');
    link.href = `${window.location.origin}/iss/index.php?machine=${qrCode}`;
    link.target = '_blank';
    
    // Generate QR code
    const qrImage = new QRCode(link, {
        text: `${window.location.origin}/iss/index.php?machine=${qrCode}`,
        width: 256,
        height: 256
    });
    
    // Append the link to the QR code container
    qrContainer.appendChild(link);
    
    modal.style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function confirmDelete(machineId) {
    if (confirm('Are you sure you want to delete this machine? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'machines.php';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete';
        
        const machineInput = document.createElement('input');
        machineInput.type = 'hidden';
        machineInput.name = 'machine_id';
        machineInput.value = machineId;
        
        form.appendChild(actionInput);
        form.appendChild(machineInput);
        document.body.appendChild(form);
        form.submit();
    }
}

function showProducts(machineId) {
    window.location.href = `products.php?machine=${machineId}`;
}

// Close modals when clicking outside
window.onclick = function(event) {
    const modals = document.getElementsByClassName('modal');
    for (let modal of modals) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>