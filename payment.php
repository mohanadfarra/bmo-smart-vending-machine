<?php
header('Content-Type: application/json');
require_once 'manager/config/db.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Generate unique collection code
    function generateCollectionCode() {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $code;
    }

    // Keep generating until we get a unique code
    do {
        $collection_code = generateCollectionCode();
        $check = $conn->prepare("SELECT id FROM transactions WHERE unique_code = ? AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
        $check->bind_param("s", $collection_code);
        $check->execute();
    } while ($check->get_result()->num_rows > 0);

    // Verify available stock for all items
    foreach ($data['items'] as $item) {
        $stmt = $conn->prepare("SELECT quantity FROM products WHERE id = ? AND machine_id = ? AND active = 1");
        $stmt->bind_param("ii", $item['id'], $data['machine_id']);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if (!$result || $result['quantity'] < $item['quantity']) {
            throw new Exception("Insufficient stock for one or more items");
        }
    }

    // Create session ID for the transaction
    $session_id = uniqid('sess_');

// Insert transaction record
$stmt = $conn->prepare("
    INSERT INTO transactions (
        machine_id, 
        session_id, 
        total_amount, 
        status, 
        unique_code, 
        items
    ) VALUES (?, ?, ?, 'pending', ?, ?)
");

$items_json = json_encode($data['items']);
$stmt->bind_param(
    "isdss", 
    $data['machine_id'],
    $session_id,
    $data['total_amount'],
    $collection_code,
    $items_json
);
    $stmt->execute();
    $transaction_id = $conn->insert_id;

    // Update product quantities
    foreach ($data['items'] as $item) {
        $stmt = $conn->prepare("
            UPDATE products 
            SET quantity = quantity - ? 
            WHERE id = ? AND machine_id = ?
        ");
        $stmt->bind_param("iii", $item['quantity'], $item['id'], $data['machine_id']);
        $stmt->execute();
    }

    // Check if any items are running low on stock
    $low_stock_check = $conn->prepare("
        SELECT id, name, quantity, min_stock_alert 
        FROM products 
        WHERE machine_id = ? AND quantity <= min_stock_alert
    ");
    $low_stock_check->bind_param("i", $data['machine_id']);
    $low_stock_check->execute();
    $low_stock_items = $low_stock_check->get_result()->fetch_all(MYSQLI_ASSOC);

    // If items are low on stock, notify manager (implement your notification system here)
    if (count($low_stock_items) > 0) {
        // Example: Insert into notifications table
/*         foreach ($low_stock_items as $item) {
            $notify_stmt = $conn->prepare("
                INSERT INTO manager_notifications (
                    machine_id, 
                    type, 
                    message, 
                    status
                ) VALUES (?, 'low_stock', ?, 'unread')
            ");
            $message = "Low stock alert: {$item['name']} (Quantity: {$item['quantity']})";
            $notify_stmt->bind_param("is", $data['machine_id'], $message);
            $notify_stmt->execute();
        } */
    }

    // Commit transaction
    $conn->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'collection_code' => $collection_code,
        'expires_in' => '15 minutes',
        'transaction_id' => $transaction_id
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    // Log error (implement your error logging system here)
    error_log("Payment processing error: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while processing your payment. Please try again.'
    ]);
}

// Close database connection
$conn->close();