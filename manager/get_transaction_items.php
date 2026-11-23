<?php
// get_transaction_items.php
// Fetches items for a specific transaction ID and returns as JSON.

header('Content-Type: application/json');
require_once 'config/db.php'; // Adjust path if needed
require_once 'includes/auth.php'; // Ensure manager is logged in

// Check if user is logged in
requireLogin();

// Get transaction ID from the request
$transaction_id = isset($_GET['transaction_id']) ? (int)$_GET['transaction_id'] : 0;

if ($transaction_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid Transaction ID']);
    exit;
}

try {
    // Prepare statement to fetch items
    $stmt = $conn->prepare("
        SELECT
            ti.quantity,
            ti.price_at_transaction,
            p.name as product_name
        FROM transaction_items ti
        JOIN products p ON ti.product_id = p.id
        WHERE ti.transaction_id = ?
        ORDER BY p.name
    ");
    $stmt->bind_param("i", $transaction_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = $result->fetch_all(MYSQLI_ASSOC);

    // Return items as JSON
    echo json_encode(['success' => true, 'items' => $items]);

} catch (Exception $e) {
    // Log error (implement proper logging if needed)
    error_log("Error fetching transaction items: " . $e->getMessage());

    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching transaction items.'
    ]);
}

// Close connection
$conn->close();
?>
