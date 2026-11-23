<?php
// check_stock.php
header('Content-Type: application/json');
require_once 'manager/config/db.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

try {
    $outOfStockItems = [];

    // Check each item's stock
    foreach ($data['items'] as $item) {
        $stmt = $conn->prepare("
            SELECT name, quantity 
            FROM products 
            WHERE id = ? AND machine_id = ? AND active = 1
        ");
        $stmt->bind_param("ii", $item['id'], $data['machine_id']);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if (!$result || $result['quantity'] < $item['quantity']) {
            $outOfStockItems[] = [
                'id' => $item['id'],
                'name' => $result ? $result['name'] : 'Unknown Product',
                'requested' => $item['quantity'],
                'available' => $result ? $result['quantity'] : 0
            ];
        }
    }

    if (count($outOfStockItems) > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Some items are out of stock',
            'items' => $outOfStockItems
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'All items are in stock'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error checking stock availability'
    ]);
}

$conn->close();
?>