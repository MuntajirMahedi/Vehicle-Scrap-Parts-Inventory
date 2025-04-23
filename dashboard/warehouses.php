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
                $name = mysqli_real_escape_string($conn, $_POST['warehouse_name']);
                $address = mysqli_real_escape_string($conn, $_POST['warehouse_address']);
                $status = mysqli_real_escape_string($conn, $_POST['warehouse_status']);

                // Check if warehouse name already exists
                $check_query = "SELECT COUNT(*) as count FROM tbl_warehouse WHERE warehouse_name = ?";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bind_param('s', $name);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                if ($result->fetch_assoc()['count'] > 0) {
                    $error = "Warehouse name already exists.";
                    break;
                }

                $query = "INSERT INTO tbl_warehouse (warehouse_name, warehouse_address, warehouse_status) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('sss', $name, $address, $status);
                if ($stmt->execute()) {
                    $success = true;
                    $_SESSION['success'] = "Warehouse added successfully.";
                } else {
                    $error = "Failed to add warehouse.";
                }
                break;

            case 'edit':
                $id = mysqli_real_escape_string($conn, $_POST['warehouse_id']);
                $name = mysqli_real_escape_string($conn, $_POST['warehouse_name']);
                $address = mysqli_real_escape_string($conn, $_POST['warehouse_address']);
                $status = mysqli_real_escape_string($conn, $_POST['warehouse_status']);

                // Check if warehouse name already exists for other warehouses
                $check_query = "SELECT COUNT(*) as count FROM tbl_warehouse WHERE warehouse_name = ? AND warehouse_id != ?";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bind_param('si', $name, $id);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                if ($result->fetch_assoc()['count'] > 0) {
                    $error = "Warehouse name already exists.";
                    break;
                }

                $query = "UPDATE tbl_warehouse SET warehouse_name = ?, warehouse_address = ?, warehouse_status = ? WHERE warehouse_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('sssi', $name, $address, $status, $id);
                if ($stmt->execute()) {
                    $success = true;
                    $_SESSION['success'] = "Warehouse updated successfully.";
                } else {
                    $error = "Failed to update warehouse.";
                }
                break;

            case 'delete':
                $id = mysqli_real_escape_string($conn, $_POST['warehouse_id']);

                // Check if warehouse has associated cars
                $check_query = "SELECT COUNT(*) as cars_count FROM tbl_car WHERE car_warehouse_id = ?";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bind_param('i', $id);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                if ($result->fetch_assoc()['cars_count'] > 0) {
                    $error = "Cannot delete warehouse. It has associated cars.";
                    break;
                }

                $query = "DELETE FROM tbl_warehouse WHERE warehouse_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('i', $id);
                if ($stmt->execute()) {
                    $success = true;
                    $_SESSION['success'] = "Warehouse deleted successfully.";
                } else {
                    $error = "Failed to delete warehouse.";
                }
                break;
        }

        if ($error) {
            $_SESSION['error'] = $error;
        }

        // Redirect to prevent form resubmission
        header('Location: warehouses.php');
        exit();
    }
}

// Get all warehouses with car counts
$warehouses = $conn->query("
    SELECT w.*, COUNT(c.car_id) as cars_count
    FROM tbl_warehouse w
    LEFT JOIN tbl_car c ON w.warehouse_id = c.car_warehouse_id
    GROUP BY w.warehouse_id
    ORDER BY w.warehouse_name
");
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Manage Warehouses</h1>
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addWarehouseModal">
        <i class="fas fa-plus mr-2"></i>Add New Warehouse
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

<!-- Warehouses List -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped" id="warehousesTable">
                <thead>
                    <tr>
                        <th>Warehouse Name</th>
                        <th>Address</th>
                        <th>Cars Count</th>
                        <th>Status</th>
                        <th>Created Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($warehouse = $warehouses->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($warehouse['warehouse_name']); ?></td>
                        <td><?php echo htmlspecialchars($warehouse['warehouse_address']); ?></td>
                        <td>
                            <span class="badge badge-info">
                                <?php echo $warehouse['cars_count']; ?> cars
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $warehouse['warehouse_status'] === 'active' ? 'success' : 'danger'; ?>">
                                <?php echo ucfirst($warehouse['warehouse_status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('d M Y', strtotime($warehouse['warehouse_created_date'])); ?></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-info edit-warehouse"
                                    data-id="<?php echo $warehouse['warehouse_id']; ?>"
                                    data-name="<?php echo htmlspecialchars($warehouse['warehouse_name']); ?>"
                                    data-address="<?php echo htmlspecialchars($warehouse['warehouse_address']); ?>"
                                    data-status="<?php echo $warehouse['warehouse_status']; ?>"
                                    data-toggle="modal" data-target="#editWarehouseModal">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger delete-warehouse"
                                    data-id="<?php echo $warehouse['warehouse_id']; ?>"
                                    data-name="<?php echo htmlspecialchars($warehouse['warehouse_name']); ?>"
                                    data-cars="<?php echo $warehouse['cars_count']; ?>">
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

<!-- Add Warehouse Modal -->
<div class="modal fade" id="addWarehouseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Warehouse</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="warehouses.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label for="warehouse_name">Warehouse Name</label>
                        <input type="text" class="form-control" id="warehouse_name" name="warehouse_name" required>
                    </div>
                    <div class="form-group">
                        <label for="warehouse_address">Address</label>
                        <textarea class="form-control" id="warehouse_address" name="warehouse_address" rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="warehouse_status">Status</label>
                        <select class="form-control" id="warehouse_status" name="warehouse_status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Warehouse</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Warehouse Modal -->
<div class="modal fade" id="editWarehouseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Warehouse</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="warehouses.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="warehouse_id" id="edit_warehouse_id">
                    <div class="form-group">
                        <label for="edit_warehouse_name">Warehouse Name</label>
                        <input type="text" class="form-control" id="edit_warehouse_name" name="warehouse_name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_warehouse_address">Address</label>
                        <textarea class="form-control" id="edit_warehouse_address" name="warehouse_address" rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="edit_warehouse_status">Status</label>
                        <select class="form-control" id="edit_warehouse_status" name="warehouse_status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
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

<!-- Delete Warehouse Form (Hidden) -->
<form id="deleteWarehouseForm" action="warehouses.php" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="warehouse_id" id="delete_warehouse_id">
</form>

<script>
// Edit warehouse
$('.edit-warehouse').on('click', function() {
    const id = $(this).data('id');
    const name = $(this).data('name');
    const address = $(this).data('address');
    const status = $(this).data('status');

    $('#edit_warehouse_id').val(id);
    $('#edit_warehouse_name').val(name);
    $('#edit_warehouse_address').val(address);
    $('#edit_warehouse_status').val(status);
});

// Delete warehouse
$('.delete-warehouse').on('click', function() {
    const id = $(this).data('id');
    const name = $(this).data('name');
    const cars = $(this).data('cars');

    if (cars > 0) {
        alert(`Cannot delete warehouse "${name}". It has ${cars} associated cars.`);
        return;
    }

    if (confirm(`Are you sure you want to delete the warehouse "${name}"?`)) {
        $('#delete_warehouse_id').val(id);
        $('#deleteWarehouseForm').submit();
    }
});

// Initialize DataTable
$(document).ready(function() {
    $('#warehousesTable').DataTable({
        "order": [[0, "asc"]],
        "pageLength": 25
    });
});
</script>

<?php
require_once '../includes/footer.php';
?>
