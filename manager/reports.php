<?php
require_once 'includes/header.php';

// Get date range parameters
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-30 days'));
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
$machine_id = isset($_GET['machine']) ? (int)$_GET['machine'] : null;

// Get all machines for filter
$machines = $conn->query("SELECT id, location FROM vending_machines ORDER BY location");

// Function to get sales data for specified period
function getSalesData($conn, $from, $to, $machine_id = null) {
    $query = "
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as total_transactions,
            SUM(total_amount) as revenue
        FROM transactions
        WHERE status = 'completed'
            AND DATE(created_at) BETWEEN ? AND ?
    ";
    
    if ($machine_id) {
        $query .= " AND machine_id = ?";
    }
    
    $query .= " GROUP BY DATE(created_at)";
    
    $stmt = $conn->prepare($query);
    
    if ($machine_id) {
        $stmt->bind_param("ssi", $from, $to, $machine_id);
    } else {
        $stmt->bind_param("ss", $from, $to);
    }
    
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Function to get top selling products
function getTopProducts($conn, $from, $to, $machine_id = null, $limit = 5) {
    $query = "
        SELECT 
            JSON_EXTRACT(items, '$[*].name') as product_names,
            JSON_EXTRACT(items, '$[*].quantity') as quantities
        FROM transactions
        WHERE status = 'completed'
            AND DATE(created_at) BETWEEN ? AND ?
    ";
    
    if ($machine_id) {
        $query .= " AND machine_id = ?";
    }
    
    $stmt = $conn->prepare($query);
    
    if ($machine_id) {
        $stmt->bind_param("ssi", $from, $to, $machine_id);
    } else {
        $stmt->bind_param("ss", $from, $to);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $names = json_decode($row['product_names']);
        $quantities = json_decode($row['quantities']);
        
        for ($i = 0; $i < count($names); $i++) {
            $name = trim($names[$i], '"');
            if (!isset($products[$name])) {
                $products[$name] = 0;
            }
            $products[$name] += $quantities[$i];
        }
    }
    
    arsort($products);
    return array_slice($products, 0, $limit, true);
}

// Function to get machine performance data
function getMachinePerformance($conn, $from, $to) {
    return $conn->query("
        SELECT 
            v.location,
            COUNT(t.id) as total_transactions,
            SUM(t.total_amount) as total_revenue,
            AVG(t.total_amount) as avg_transaction,
            COUNT(DISTINCT DATE(t.created_at)) as active_days
        FROM vending_machines v
        LEFT JOIN transactions t ON v.id = t.machine_id 
            AND t.status = 'completed'
            AND DATE(t.created_at) BETWEEN '$from' AND '$to'
        GROUP BY v.id
        ORDER BY total_revenue DESC
    ")->fetch_all(MYSQLI_ASSOC);
}

// Get report data
$sales_data = getSalesData($conn, $date_from, $date_to, $machine_id);
$top_products = getTopProducts($conn, $date_from, $date_to, $machine_id);
$machine_performance = getMachinePerformance($conn, $date_from, $date_to);

// Calculate summary statistics
$total_revenue = 0;
$total_transactions = 0;
$avg_daily_sales = 0;

foreach ($sales_data as $day) {
    $total_revenue += $day['revenue'];
    $total_transactions += $day['total_transactions'];
}

$date_diff = (strtotime($date_to) - strtotime($date_from)) / (60 * 60 * 24) + 1;
$avg_daily_sales = $total_revenue / $date_diff;

?>

<div class="page-header">
    <h1>Reports & Analytics</h1>
    
    <div class="date-filter">
        <form method="GET" class="filters-form">
            <?php if ($machine_id): ?>
                <input type="hidden" name="machine" value="<?php echo $machine_id; ?>">
            <?php endif; ?>
            
            <div class="filter-group">
                <label for="machine">Machine</label>
                <select name="machine" id="machine" class="form-select" onchange="this.form.submit()">
                    <option value="">All Machines</option>
                    <?php while ($machine = $machines->fetch_assoc()): ?>
                    <option value="<?php echo $machine['id']; ?>" <?php echo $machine_id == $machine['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($machine['location']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="date_from">From</label>
                <input type="date" id="date_from" name="date_from" value="<?php echo $date_from; ?>" class="form-input">
            </div>
            
            <div class="filter-group">
                <label for="date_to">To</label>
                <input type="date" id="date_to" name="date_to" value="<?php echo $date_to; ?>" class="form-input">
            </div>
            
            <button type="submit" class="btn-primary">Update Report</button>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="summary-cards">
    <div class="summary-card">
        <h3>Total Revenue</h3>
        <div class="value"><?php echo number_format($total_revenue, 3); ?> TND</div>
    </div>
    
    <div class="summary-card">
        <h3>Total Transactions</h3>
        <div class="value"><?php echo $total_transactions; ?></div>
    </div>
    
    <div class="summary-card">
        <h3>Average Daily Sales</h3>
        <div class="value"><?php echo number_format($avg_daily_sales, 3); ?> TND</div>
    </div>
    
    <div class="summary-card">
        <h3>Average Transaction Value</h3>
        <div class="value">
            <?php echo $total_transactions > 0 ? number_format($total_revenue / $total_transactions, 3) : '0.000'; ?> TND
        </div>
    </div>
</div>

<!-- Sales Trend Chart -->
<div class="chart-container">
    <h2>Sales Trend</h2>
    <div id="salesChart" style="height: 400px;"></div>
</div>

<!-- Top Products -->
<div class="report-grid">
    <div class="report-card">
        <h2>Top Selling Products</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Units Sold</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($top_products as $product => $quantity): ?>
                <tr>
                    <td><?php echo htmlspecialchars($product); ?></td>
                    <td><?php echo $quantity; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="report-card">
        <h2>Machine Performance</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Location</th>
                    <th>Transactions</th>
                    <th>Revenue</th>
                    <th>Avg. Transaction</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($machine_performance as $machine): ?>
                <tr>
                    <td><?php echo htmlspecialchars($machine['location']); ?></td>
                    <td><?php echo $machine['total_transactions']; ?></td>
                    <td><?php echo number_format($machine['total_revenue'], 3); ?> TND</td>
                    <td><?php echo number_format($machine['avg_transaction'], 3); ?> TND</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Prepare sales data for chart
const salesData = <?php echo json_encode($sales_data); ?>;
const chartData = salesData.map(day => ({
    date: day.date,
    revenue: parseFloat(day.revenue),
    transactions: parseInt(day.total_transactions)
}));

// Create sales trend chart
const salesChart = new Recharts.ComposedChart({
    width: 800,
    height: 400,
    data: chartData,
    margin: { top: 20, right: 30, left: 20, bottom: 20 }
});

salesChart.addXAxis({ dataKey: 'date' });
salesChart.addYAxis({ 
    yAxisId: 'revenue',
    label: { value: 'Revenue (TND)', angle: -90, position: 'insideLeft' }
});
salesChart.addYAxis({ 
    yAxisId: 'transactions',
    orientation: 'right',
    label: { value: 'Transactions', angle: 90, position: 'insideRight' }
});

salesChart.addLine({
    dataKey: 'revenue',
    stroke: '#967bb6',
    yAxisId: 'revenue'
});

salesChart.addBar({
    dataKey: 'transactions',
    fill: '#c8a2c8',
    yAxisId: 'transactions'
});

salesChart.render('#salesChart');
</script>

<?php require_once 'includes/footer.php'; ?>