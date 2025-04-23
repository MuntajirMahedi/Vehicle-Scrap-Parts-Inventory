<?php
require_once '../config/database.php';
require_once '../includes/header.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $success = false;
        $error = '';

        switch ($_POST['action']) {
            case 'add':
                $car_id = mysqli_real_escape_string($conn, $_POST['stock_car_id']);
                $part_id = mysqli_real_escape_string($conn, $_POST['stock_part_id']);
                $status = mysqli_real_escape_string($conn, $_POST['stock_status']);
                $exchange = mysqli_real_escape_string($conn, $_POST['stock_exchange_received']);

                // Optional fields
                $customer = isset($_POST['stock_customer']) ? mysqli_real_escape_string($conn, $_POST['stock_customer']) : null;
                $mobile = isset($_POST['stock_customer_mobileno']) ? mysqli_real_escape_string($conn, $_POST['stock_customer_mobileno']) : null;
                $sold_date = ($status === 'sold') ? date('Y-m-d H:i:s') : null;

                // Check if this part is already in stock for this car
                $check_query = "SELECT COUNT(*) as count FROM tbl_stock WHERE stock_car_id = ? AND stock_part_id = ? AND stock_status = 'in_stock'";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bind_param('ii', $car_id, $part_id);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                if ($result->fetch_assoc()['count'] > 0) {
                    $error = "This part is already in stock for this car.";
                    break;
                }

                $query = "INSERT INTO tbl_stock (stock_car_id, stock_part_id, stock_status, stock_exchange_received,
                         stock_customer, stock_customer_mobileno, stock_sold_date) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('iisssss', $car_id, $part_id, $status, $exchange, $customer, $mobile, $sold_date);

                if ($stmt->execute()) {
                    $success = true;
                    $_SESSION['success'] = "Stock entry added successfully.";
                } else {
                    $error = "Failed to add stock entry.";
                }
                break;

            case 'edit':
                $id = mysqli_real_escape_string($conn, $_POST['stock_id']);
                $status = mysqli_real_escape_string($conn, $_POST['stock_status']);
                $exchange = mysqli_real_escape_string($conn, $_POST['stock_exchange_received']);
                $customer = isset($_POST['stock_customer']) ? mysqli_real_escape_string($conn, $_POST['stock_customer']) : null;
                $mobile = isset($_POST['stock_customer_mobileno']) ? mysqli_real_escape_string($conn, $_POST['stock_customer_mobileno']) : null;
                $sold_date = ($status === 'sold') ? date('Y-m-d H:i:s') : null;

                $query = "UPDATE tbl_stock SET
                         stock_status = ?,
                         stock_exchange_received = ?,
                         stock_customer = ?,
                         stock_customer_mobileno = ?,
                         stock_sold_date = ?
                         WHERE stock_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('sssssi', $status, $exchange, $customer, $mobile, $sold_date, $id);

                if ($stmt->execute()) {
                    $success = true;
                    $_SESSION['success'] = "Stock entry updated successfully.";
                } else {
                    $error = "Failed to update stock entry.";
                }
                break;

            case 'delete':
                $id = mysqli_real_escape_string($conn, $_POST['stock_id']);

                $query = "DELETE FROM tbl_stock WHERE stock_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('i', $id);

                if ($stmt->execute()) {
                    $success = true;
                    $_SESSION['success'] = "Stock entry deleted successfully.";
                } else {
                    $error = "Failed to delete stock entry.";
                }
                break;
        }

        if ($error) {
            $_SESSION['error'] = $error;
        }

        // Redirect to prevent form resubmission
        header('Location: stock.php');
        exit();
    }
}

// Get all active cars for dropdowns
$cars = $conn->query("
    SELECT c.car_id,
           CONCAT(b.brand_name, ' ', m.model_name, ' (', c.car_year, ') - ', c.car_reg_number) as car_details
    FROM tbl_car c
    JOIN tbl_brand b ON c.car_brand_id = b.brand_id
    JOIN tbl_model m ON c.car_model_id = m.model_id
    ORDER BY b.brand_name, m.model_name, c.car_year DESC
");

// Get all active parts for dropdowns
$parts = $conn->query("
    SELECT p.part_id,
           CONCAT(c.category_name, ' - ', p.part_name) as part_details
    FROM tbl_part p
    JOIN tbl_category c ON p.part_category_id = c.category_id
    WHERE p.part_status = 'active'
    ORDER BY c.category_name, p.part_name
");

// Get all stock entries with relationships
$stock = $conn->query("
    SELECT s.*,
           CONCAT(b.brand_name, ' ', m.model_name, ' (', c.car_year, ') - ', c.car_reg_number) as car_details,
           CONCAT(cat.category_name, ' - ', p.part_name) as part_details
    FROM tbl_stock s
    JOIN tbl_car c ON s.stock_car_id = c.car_id
    JOIN tbl_brand b ON c.car_brand_id = b.brand_id
    JOIN tbl_model m ON c.car_model_id = m.model_id
    JOIN tbl_part p ON s.stock_part_id = p.part_id
    JOIN tbl_category cat ON p.part_category_id = cat.category_id
    ORDER BY s.stock_created_date DESC
");
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Manage Stock/Inventory</h1>
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addStockModal">
        <i class="fas fa-plus mr-2"></i>Add New Stock Entry
    </button>
</div>

<!-- Success/Error Messages -->
<?php if (isset($_SESSION['success'])): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php
    echo $_SESSION['success'];
    unset($_SESSION['success']);
    ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php
    echo $_SESSION['error'];
    unset($_SESSION['error']);
    ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php endif; ?>

<!-- Stock List -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped" id="stockTable">
                <thead>
                    <tr>
                        <th>Car Details</th>
                        <th>Part Details</th>
                        <th>Status</th>
                        <th>Exchange</th>
                        <th>Customer</th>
                        <th>Mobile</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($item = $stock->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['car_details']); ?></td>
                        <td><?php echo htmlspecialchars($item['part_details']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $item['stock_status'] === 'in_stock' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $item['stock_status'])); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $item['stock_exchange_received'] === 'yes' ? 'info' : 'secondary'; ?>">
                                <?php echo ucfirst($item['stock_exchange_received']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($item['stock_customer'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars($item['stock_customer_mobileno'] ?: '-'); ?></td>
                        <td>
                            <?php
                            echo date('d M Y', strtotime($item['stock_created_date']));
                            if ($item['stock_sold_date']) {
                                echo '<br><small class="text-muted">Sold: ' . date('d M Y', strtotime($item['stock_sold_date'])) . '</small>';
                            }
                            ?>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-info edit-stock"
                                    data-id="<?php echo $item['stock_id']; ?>"
                                    data-car="<?php echo htmlspecialchars($item['car_details']); ?>"
                                    data-part="<?php echo htmlspecialchars($item['part_details']); ?>"
                                    data-status="<?php echo $item['stock_status']; ?>"
                                    data-exchange="<?php echo $item['stock_exchange_received']; ?>"
                                    data-customer="<?php echo htmlspecialchars($item['stock_customer']); ?>"
                                    data-mobile="<?php echo htmlspecialchars($item['stock_customer_mobileno']); ?>"
                                    data-toggle="modal" data-target="#editStockModal">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger delete-stock"
                                    data-id="<?php echo $item['stock_id']; ?>"
                                    data-car="<?php echo htmlspecialchars($item['car_details']); ?>"
                                    data-part="<?php echo htmlspecialchars($item['part_details']); ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Stock Modal -->
<div class="modal fade" id="addStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Stock Entry</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="stock.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label for="stock_car_id">Car</label>
                        <select class="form-control" id="stock_car_id" name="stock_car_id" required>
                            <option value="">Select Car</option>
                            <?php while ($car = $cars->fetch_assoc()): ?>
                            <option value="<?php echo $car['car_id']; ?>">
                                <?php echo htmlspecialchars($car['car_details']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="stock_part_id">Part</label>
                        <select class="form-control" id="stock_part_id" name="stock_part_id" required>
                            <option value="">Select Part</option>
                            <?php while ($part = $parts->fetch_assoc()): ?>
                            <option value="<?php echo $part['part_id']; ?>">
                                <?php echo htmlspecialchars($part['part_details']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="stock_status">Status</label>
                        <select class="form-control" id="stock_status" name="stock_status" required>
                            <option value="in_stock">In Stock</option>
                            <option value="sold">Sold</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="stock_exchange_received">Exchange Received</label>
                        <select class="form-control" id="stock_exchange_received" name="stock_exchange_received" required>
                            <option value="no">No</option>
                            <option value="yes">Yes</option>
                        </select>
                    </div>
                    <div id="customer_details" style="display: none;">
                        <div class="form-group">
                            <label for="stock_customer">Customer Name</label>
                            <input type="text" class="form-control" id="stock_customer" name="stock_customer">
                        </div>
                        <div class="form-group">
                            <label for="stock_customer_mobileno">Customer Mobile</label>
                            <input type="text" class="form-control" id="stock_customer_mobileno" name="stock_customer_mobileno">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Stock Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Stock Modal -->
<div class="modal fade" id="editStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Stock Entry</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="stock.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="stock_id" id="edit_stock_id">
                    <div class="form-group">
                        <label>Car</label>
                        <p class="form-control-static" id="edit_car_details"></p>
                    </div>
                    <div class="form-group">
                        <label>Part</label>
                        <p class="form-control-static" id="edit_part_details"></p>
                    </div>
                    <div class="form-group">
                        <label for="edit_stock_status">Status</label>
                        <select class="form-control" id="edit_stock_status" name="stock_status" required>
                            <option value="in_stock">In Stock</option>
                            <option value="sold">Sold</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_stock_exchange_received">Exchange Received</label>
                        <select class="form-control" id="edit_stock_exchange_received" name="stock_exchange_received" required>
                            <option value="no">No</option>
                            <option value="yes">Yes</option>
                        </select>
                    </div>
                    <div id="edit_customer_details" style="display: none;">
                        <div class="form-group">
                            <label for="edit_stock_customer">Customer Name</label>
                            <input type="text" class="form-control" id="edit_stock_customer" name="stock_customer">
                        </div>
                        <div class="form-group">
                            <label for="edit_stock_customer_mobileno">Customer Mobile</label>
                            <input type="text" class="form-control" id="edit_stock_customer_mobileno" name="stock_customer_mobileno">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Stock Form (Hidden) -->
<form id="deleteStockForm" action="stock.php" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="stock_id" id="delete_stock_id">
</form>

<script>
// Toggle customer details based on status
function toggleCustomerDetails(status, prefix = '') {
    const detailsDiv = $(`#${prefix}customer_details`);
    if (status === 'sold') {
        detailsDiv.slideDown();
        detailsDiv.find('input').prop('required', true);
    } else {
        detailsDiv.slideUp();
        detailsDiv.find('input').prop('required', false);
    }
}

// Add form status change
$('#stock_status').on('change', function() {
    toggleCustomerDetails($(this).val());
});

// Edit form status change
$('#edit_stock_status').on('change', function() {
    toggleCustomerDetails($(this).val(), 'edit_');
});

// Edit stock
$('.edit-stock').on('click', function() {
    const id = $(this).data('id');
    const car = $(this).data('car');
    const part = $(this).data('part');
    const status = $(this).data('status');
    const exchange = $(this).data('exchange');
    const customer = $(this).data('customer');
    const mobile = $(this).data('mobile');

    $('#edit_stock_id').val(id);
    $('#edit_car_details').text(car);
    $('#edit_part_details').text(part);
    $('#edit_stock_status').val(status);
    $('#edit_stock_exchange_received').val(exchange);
    $('#edit_stock_customer').val(customer);
    $('#edit_stock_customer_mobileno').val(mobile);

    toggleCustomerDetails(status, 'edit_');
});

// Delete stock
$('.delete-stock').on('click', function() {
    const id = $(this).data('id');
    const car = $(this).data('car');
    const part = $(this).data('part');

    if (confirm(`Are you sure you want to delete the stock entry for:\nCar: ${car}\nPart: ${part}`)) {
        $('#delete_stock_id').val(id);
        $('#deleteStockForm').submit();
    }
});

// Initialize DataTable
$(document).ready(function() {
    $('#stockTable').DataTable({
        "order": [[6, "desc"]],
        "pageLength": 25
    });

    // Initialize select2 for dropdowns
    $('#stock_car_id, #stock_part_id').select2({
        theme: 'bootstrap4',
        width: '100%'
    });
});

// Reset form when modal is closed
$('#addStockModal').on('hidden.bs.modal', function() {
    $(this).find('form')[0].reset();
    $('#customer_details').hide();
    $('#customer_details input').prop('required', false);
});

$('#editStockModal').on('hidden.bs.modal', function() {
    $('#edit_customer_details').hide();
    $('#edit_customer_details input').prop('required', false);
});
</script>

<?php
require_once '../includes/footer.php';
?>
