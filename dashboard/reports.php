<?php
require_once '../config/database.php';
require_once '../includes/header.php';

// Get report type from URL parameter
$report_type = isset($_GET['type']) ? $_GET['type'] : 'stock_status';

// Function to get report data based on type and filters
function getReportData($conn, $type, $filters = []) {
    switch ($type) {
        case 'stock_status':
            $query = "
                SELECT
                    c.category_name,
                    p.part_name,
                    COUNT(CASE WHEN s.stock_status = 'in_stock' THEN 1 END) as in_stock,
                    COUNT(CASE WHEN s.stock_status = 'sold' THEN 1 END) as sold,
                    COUNT(*) as total
                FROM tbl_part p
                LEFT JOIN tbl_category c ON p.part_category_id = c.category_id
                LEFT JOIN tbl_stock s ON p.part_id = s.stock_part_id
                GROUP BY p.part_id
                ORDER BY c.category_name, p.part_name
            ";
            break;

        case 'sales_history':
            $query = "
                SELECT
                    s.stock_sold_date,
                    CONCAT(b.brand_name, ' ', m.model_name, ' (', c.car_year, ')') as car_details,
                    c.car_reg_number,
                    cat.category_name,
                    p.part_name,
                    s.stock_exchange_received,
                    s.stock_customer,
                    s.stock_customer_mobileno
                FROM tbl_stock s
                JOIN tbl_car c ON s.stock_car_id = c.car_id
                JOIN tbl_brand b ON c.car_brand_id = b.brand_id
                JOIN tbl_model m ON c.car_model_id = m.model_id
                JOIN tbl_part p ON s.stock_part_id = p.part_id
                JOIN tbl_category cat ON p.part_category_id = cat.category_id
                WHERE s.stock_status = 'sold'
                ORDER BY s.stock_sold_date DESC
            ";
            break;

        case 'warehouse_inventory':
            $query = "
                SELECT
                    w.warehouse_name,
                    COUNT(DISTINCT c.car_id) as total_cars,
                    COUNT(DISTINCT CASE WHEN s.stock_status = 'in_stock' THEN s.stock_id END) as available_parts,
                    COUNT(DISTINCT CASE WHEN s.stock_status = 'sold' THEN s.stock_id END) as sold_parts
                FROM tbl_warehouse w
                LEFT JOIN tbl_car c ON w.warehouse_id = c.car_warehouse_id
                LEFT JOIN tbl_stock s ON c.car_id = s.stock_car_id
                GROUP BY w.warehouse_id
                ORDER BY w.warehouse_name
            ";
            break;

        case 'category_analysis':
            $query = "
                SELECT
                    c.category_name,
                    COUNT(DISTINCT p.part_id) as total_parts,
                    COUNT(DISTINCT CASE WHEN s.stock_status = 'in_stock' THEN s.stock_id END) as available_stock,
                    COUNT(DISTINCT CASE WHEN s.stock_status = 'sold' THEN s.stock_id END) as sold_items,
                    COUNT(DISTINCT CASE WHEN s.stock_exchange_received = 'yes' THEN s.stock_id END) as exchanges
                FROM tbl_category c
                LEFT JOIN tbl_part p ON c.category_id = p.part_category_id
                LEFT JOIN tbl_stock s ON p.part_id = s.stock_part_id
                GROUP BY c.category_id
                ORDER BY c.category_name
            ";
            break;

        case 'car_statistics':
            $query = "
                SELECT
                    CONCAT(b.brand_name, ' ', m.model_name, ' (', c.car_year, ')') as car_details,
                    c.car_reg_number,
                    w.warehouse_name,
                    COUNT(DISTINCT CASE WHEN s.stock_status = 'in_stock' THEN s.stock_id END) as available_parts,
                    COUNT(DISTINCT CASE WHEN s.stock_status = 'sold' THEN s.stock_id END) as sold_parts,
                    COUNT(DISTINCT CASE WHEN s.stock_exchange_received = 'yes' THEN s.stock_id END) as exchanges
                FROM tbl_car c
                JOIN tbl_brand b ON c.car_brand_id = b.brand_id
                JOIN tbl_model m ON c.car_model_id = m.model_id
                JOIN tbl_warehouse w ON c.car_warehouse_id = w.warehouse_id
                LEFT JOIN tbl_stock s ON c.car_id = s.stock_car_id
                GROUP BY c.car_id
                ORDER BY b.brand_name, m.model_name, c.car_year DESC
            ";
            break;

        case 'exchange_parts':
            $query = "
                SELECT
                    s.stock_created_date,
                    CONCAT(b.brand_name, ' ', m.model_name, ' (', c.car_year, ')') as car_details,
                    c.car_reg_number,
                    cat.category_name,
                    p.part_name,
                    s.stock_customer,
                    s.stock_customer_mobileno
                FROM tbl_stock s
                JOIN tbl_car c ON s.stock_car_id = c.car_id
                JOIN tbl_brand b ON c.car_brand_id = b.brand_id
                JOIN tbl_model m ON c.car_model_id = m.model_id
                JOIN tbl_part p ON s.stock_part_id = p.part_id
                JOIN tbl_category cat ON p.part_category_id = cat.category_id
                WHERE s.stock_exchange_received = 'yes'
                ORDER BY s.stock_created_date DESC
            ";
            break;
    }

    return $conn->query($query);
}

// Get report data
$report_data = getReportData($conn, $report_type);
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Reports</h1>
    <!-- Fix for Export Report button -->
    <div class="dropdown">
        <button class="btn btn-primary dropdown-toggle" type="button" id="exportDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-download mr-2"></i>Export Report
        </button>
        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="exportDropdown">
            <a class="dropdown-item" href="javascript:void(0);" onclick="exportReport('excel')">
                <i class="fas fa-file-excel mr-2"></i>Export as Excel
            </a>
            <a class="dropdown-item" href="javascript:void(0);" onclick="exportReport('pdf')">
                <i class="fas fa-file-pdf mr-2"></i>Export as PDF
            </a>
        </div>
    </div>
</div>

<!-- Report Type Selection -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-12">
                <div class="btn-group btn-group-lg w-100">
                    <a href="?type=stock_status" class="btn btn-outline-primary <?php echo $report_type === 'stock_status' ? 'active' : ''; ?>">
                        Stock Status
                    </a>
                    <a href="?type=sales_history" class="btn btn-outline-primary <?php echo $report_type === 'sales_history' ? 'active' : ''; ?>">
                        Sales History
                    </a>
                    <a href="?type=warehouse_inventory" class="btn btn-outline-primary <?php echo $report_type === 'warehouse_inventory' ? 'active' : ''; ?>">
                        Warehouse Inventory
                    </a>
                    <a href="?type=category_analysis" class="btn btn-outline-primary <?php echo $report_type === 'category_analysis' ? 'active' : ''; ?>">
                        Category Analysis
                    </a>
                    <a href="?type=car_statistics" class="btn btn-outline-primary <?php echo $report_type === 'car_statistics' ? 'active' : ''; ?>">
                        Car Statistics
                    </a>
                    <a href="?type=exchange_parts" class="btn btn-outline-primary <?php echo $report_type === 'exchange_parts' ? 'active' : ''; ?>">
                        Exchange Parts
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Report Content -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped" id="reportTable">
                <thead>
                    <tr>
                        <?php switch ($report_type):
                            case 'stock_status': ?>
                                <th>Category</th>
                                <th>Part Name</th>
                                <th>In Stock</th>
                                <th>Sold</th>
                                <th>Total</th>
                            <?php break;

                            case 'sales_history': ?>
                                <th>Date</th>
                                <th>Car Details</th>
                                <th>Reg Number</th>
                                <th>Category</th>
                                <th>Part</th>
                                <th>Exchange</th>
                                <th>Customer</th>
                                <th>Mobile</th>
                            <?php break;

                            case 'warehouse_inventory': ?>
                                <th>Warehouse</th>
                                <th>Total Cars</th>
                                <th>Available Parts</th>
                                <th>Sold Parts</th>
                            <?php break;

                            case 'category_analysis': ?>
                                <th>Category</th>
                                <th>Total Parts</th>
                                <th>Available Stock</th>
                                <th>Sold Items</th>
                                <th>Exchanges</th>
                            <?php break;

                            case 'car_statistics': ?>
                                <th>Car Details</th>
                                <th>Reg Number</th>
                                <th>Warehouse</th>
                                <th>Available Parts</th>
                                <th>Sold Parts</th>
                                <th>Exchanges</th>
                            <?php break;

                            case 'exchange_parts': ?>
                                <th>Date</th>
                                <th>Car Details</th>
                                <th>Reg Number</th>
                                <th>Category</th>
                                <th>Part</th>
                                <th>Customer</th>
                                <th>Mobile</th>
                            <?php break;
                        endswitch; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $report_data->fetch_assoc()): ?>
                        <tr>
                            <?php switch ($report_type):
                                case 'stock_status': ?>
                                    <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['part_name']); ?></td>
                                    <td><?php echo $row['in_stock']; ?></td>
                                    <td><?php echo $row['sold']; ?></td>
                                    <td><?php echo $row['total']; ?></td>
                                <?php break;

                                case 'sales_history': ?>
                                    <td><?php echo date('d M Y', strtotime($row['stock_sold_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['car_details']); ?></td>
                                    <td><?php echo htmlspecialchars($row['car_reg_number']); ?></td>
                                    <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['part_name']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $row['stock_exchange_received'] === 'yes' ? 'info' : 'secondary'; ?>">
                                            <?php echo ucfirst($row['stock_exchange_received']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['stock_customer']); ?></td>
                                    <td><?php echo htmlspecialchars($row['stock_customer_mobileno']); ?></td>
                                <?php break;

                                case 'warehouse_inventory': ?>
                                    <td><?php echo htmlspecialchars($row['warehouse_name']); ?></td>
                                    <td><?php echo $row['total_cars']; ?></td>
                                    <td><?php echo $row['available_parts']; ?></td>
                                    <td><?php echo $row['sold_parts']; ?></td>
                                <?php break;

                                case 'category_analysis': ?>
                                    <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                    <td><?php echo $row['total_parts']; ?></td>
                                    <td><?php echo $row['available_stock']; ?></td>
                                    <td><?php echo $row['sold_items']; ?></td>
                                    <td><?php echo $row['exchanges']; ?></td>
                                <?php break;

                                case 'car_statistics': ?>
                                    <td><?php echo htmlspecialchars($row['car_details']); ?></td>
                                    <td><?php echo htmlspecialchars($row['car_reg_number']); ?></td>
                                    <td><?php echo htmlspecialchars($row['warehouse_name']); ?></td>
                                    <td><?php echo $row['available_parts']; ?></td>
                                    <td><?php echo $row['sold_parts']; ?></td>
                                    <td><?php echo $row['exchanges']; ?></td>
                                <?php break;

                                case 'exchange_parts': ?>
                                    <td><?php echo date('d M Y', strtotime($row['stock_created_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['car_details']); ?></td>
                                    <td><?php echo htmlspecialchars($row['car_reg_number']); ?></td>
                                    <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['part_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['stock_customer']); ?></td>
                                    <td><?php echo htmlspecialchars($row['stock_customer_mobileno']); ?></td>
                                <?php break;
                            endswitch; ?>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function exportReport(format) {
    const urlParams = new URLSearchParams(window.location.search);
    const reportType = urlParams.get('type') || 'stock_status';
    const exportUrl = `export_report.php?type=${reportType}&format=${format}`;
    window.open(exportUrl, '_blank');
}

// Initialize Bootstrap components
$(document).ready(function() {
    // Initialize dropdowns
    $('.dropdown-toggle').dropdown();
});
</script>

<?php
require_once '../includes/footer.php';
?>
