<?php
require_once '../config/database.php';
require_once '../includes/header.php';

// Get summary statistics
$stats = [
    'total_cars' => $conn->query("SELECT COUNT(*) as count FROM tbl_car")->fetch_assoc()['count'],
    'total_parts' => $conn->query("SELECT COUNT(*) as count FROM tbl_part")->fetch_assoc()['count'],
    'parts_in_stock' => $conn->query("SELECT COUNT(*) as count FROM tbl_stock WHERE stock_status = 'in_stock'")->fetch_assoc()['count'],
    'parts_sold' => $conn->query("SELECT COUNT(*) as count FROM tbl_stock WHERE stock_status = 'sold'")->fetch_assoc()['count'],
    'total_exchanges' => $conn->query("SELECT COUNT(*) as count FROM tbl_stock WHERE stock_exchange_received = 'yes'")->fetch_assoc()['count'],
    'total_warehouses' => $conn->query("SELECT COUNT(*) as count FROM tbl_warehouse")->fetch_assoc()['count']
];

// Get recent sales
$recent_sales = $conn->query("
    SELECT
        s.stock_sold_date,
        CONCAT(b.brand_name, ' ', m.model_name) as car_name,
        c.car_reg_number,
        p.part_name,
        s.stock_customer,
        s.stock_exchange_received
    FROM tbl_stock s
    JOIN tbl_car c ON s.stock_car_id = c.car_id
    JOIN tbl_brand b ON c.car_brand_id = b.brand_id
    JOIN tbl_model m ON c.car_model_id = m.model_id
    JOIN tbl_part p ON s.stock_part_id = p.part_id
    WHERE s.stock_status = 'sold'
    ORDER BY s.stock_sold_date DESC
    LIMIT 5
");

// Get stock by category data for chart
$category_stock = $conn->query("
    SELECT
        c.category_name,
        COUNT(CASE WHEN s.stock_status = 'in_stock' THEN 1 END) as in_stock,
        COUNT(CASE WHEN s.stock_status = 'sold' THEN 1 END) as sold
    FROM tbl_category c
    LEFT JOIN tbl_part p ON c.category_id = p.part_category_id
    LEFT JOIN tbl_stock s ON p.part_id = s.stock_part_id
    GROUP BY c.category_id
    ORDER BY c.category_name
");

// Get monthly sales data for chart
$monthly_sales = $conn->query("
    SELECT
        DATE_FORMAT(stock_sold_date, '%Y-%m') as month,
        COUNT(*) as total_sales,
        COUNT(CASE WHEN stock_exchange_received = 'yes' THEN 1 END) as exchanges
    FROM tbl_stock
    WHERE stock_status = 'sold'
    AND stock_sold_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(stock_sold_date, '%Y-%m')
    ORDER BY month
");

// Get warehouse inventory data for chart
$warehouse_inventory = $conn->query("
    SELECT
        w.warehouse_name,
        COUNT(DISTINCT c.car_id) as cars,
        COUNT(DISTINCT CASE WHEN s.stock_status = 'in_stock' THEN s.stock_id END) as parts
    FROM tbl_warehouse w
    LEFT JOIN tbl_car c ON w.warehouse_id = c.car_warehouse_id
    LEFT JOIN tbl_stock s ON c.car_id = s.stock_car_id
    GROUP BY w.warehouse_id
    ORDER BY w.warehouse_name
");
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Dashboard Overview</h1>
    <div class="btn-group">
        <a href="reports.php" class="btn btn-primary">
            <i class="fas fa-chart-bar mr-2"></i>Detailed Reports
        </a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-2 col-md-4 mb-4">
        <div class="card border-left-primary h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Cars</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_cars']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-car fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 mb-4">
        <div class="card border-left-success h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Parts in Stock</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['parts_in_stock']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-boxes fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 mb-4">
        <div class="card border-left-info h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Parts Sold</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['parts_sold']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 mb-4">
        <div class="card border-left-warning h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Exchanges</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_exchanges']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-exchange-alt fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 mb-4">
        <div class="card border-left-danger h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Parts</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_parts']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-cogs fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 mb-4">
        <div class="card border-left-secondary h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Warehouses</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_warehouses']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-warehouse fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4">
    <!-- Monthly Sales Chart -->
    <div class="col-xl-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Monthly Sales Overview</h6>
            </div>
            <div class="card-body">
                <canvas id="monthlySalesChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Category Stock Chart -->
    <div class="col-xl-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Stock by Category</h6>
            </div>
            <div class="card-body">
                <canvas id="categoryStockChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Sales and Warehouse Overview Row -->
<div class="row">
    <!-- Recent Sales Table -->
    <div class="col-xl-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Recent Sales</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Car</th>
                                <th>Part</th>
                                <th>Customer</th>
                                <th>Exchange</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($sale = $recent_sales->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('d M Y', strtotime($sale['stock_sold_date'])); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($sale['car_name']); ?>
                                    <small class="d-block text-muted"><?php echo htmlspecialchars($sale['car_reg_number']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($sale['part_name']); ?></td>
                                <td><?php echo htmlspecialchars($sale['stock_customer']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $sale['stock_exchange_received'] === 'yes' ? 'info' : 'secondary'; ?>">
                                        <?php echo ucfirst($sale['stock_exchange_received']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Warehouse Overview Chart -->
    <div class="col-xl-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Warehouse Overview</h6>
            </div>
            <div class="card-body">
                <canvas id="warehouseChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Monthly Sales Chart
const monthlySalesCtx = document.getElementById('monthlySalesChart').getContext('2d');
new Chart(monthlySalesCtx, {
    type: 'line',
    data: {
        labels: [<?php
            $labels = [];
            $sales_data = [];
            $exchange_data = [];
            while ($row = $monthly_sales->fetch_assoc()) {
                $labels[] = "'" . date('M Y', strtotime($row['month'] . '-01')) . "'";
                $sales_data[] = $row['total_sales'];
                $exchange_data[] = $row['exchanges'];
            }
            echo implode(',', $labels);
        ?>],
        datasets: [{
            label: 'Total Sales',
            data: [<?php echo implode(',', $sales_data); ?>],
            borderColor: 'rgb(75, 192, 192)',
            tension: 0.1
        }, {
            label: 'Exchanges',
            data: [<?php echo implode(',', $exchange_data); ?>],
            borderColor: 'rgb(255, 159, 64)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

// Category Stock Chart
const categoryStockCtx = document.getElementById('categoryStockChart').getContext('2d');
new Chart(categoryStockCtx, {
    type: 'bar',
    data: {
        labels: [<?php
            $categories = [];
            $in_stock_data = [];
            $sold_data = [];
            while ($row = $category_stock->fetch_assoc()) {
                $categories[] = "'" . $row['category_name'] . "'";
                $in_stock_data[] = $row['in_stock'];
                $sold_data[] = $row['sold'];
            }
            echo implode(',', $categories);
        ?>],
        datasets: [{
            label: 'In Stock',
            data: [<?php echo implode(',', $in_stock_data); ?>],
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            borderColor: 'rgb(75, 192, 192)',
            borderWidth: 1
        }, {
            label: 'Sold',
            data: [<?php echo implode(',', $sold_data); ?>],
            backgroundColor: 'rgba(255, 159, 64, 0.2)',
            borderColor: 'rgb(255, 159, 64)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Warehouse Overview Chart
const warehouseCtx = document.getElementById('warehouseChart').getContext('2d');
new Chart(warehouseCtx, {
    type: 'doughnut',
    data: {
        labels: [<?php
            $warehouses = [];
            $parts_data = [];
            while ($row = $warehouse_inventory->fetch_assoc()) {
                $warehouses[] = "'" . $row['warehouse_name'] . "'";
                $parts_data[] = $row['parts'];
            }
            echo implode(',', $warehouses);
        ?>],
        datasets: [{
            data: [<?php echo implode(',', $parts_data); ?>],
            backgroundColor: [
                'rgba(75, 192, 192, 0.2)',
                'rgba(255, 159, 64, 0.2)',
                'rgba(255, 99, 132, 0.2)',
                'rgba(54, 162, 235, 0.2)',
                'rgba(153, 102, 255, 0.2)'
            ],
            borderColor: [
                'rgb(75, 192, 192)',
                'rgb(255, 159, 64)',
                'rgb(255, 99, 132)',
                'rgb(54, 162, 235)',
                'rgb(153, 102, 255)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});
</script>

<?php
require_once '../includes/footer.php';
?>
