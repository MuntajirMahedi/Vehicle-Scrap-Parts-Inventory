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
                $warehouse_id = mysqli_real_escape_string($conn, $_POST['car_warehouse_id']);
                $brand_id = mysqli_real_escape_string($conn, $_POST['car_brand_id']);
                $model_id = mysqli_real_escape_string($conn, $_POST['car_model_id']);
                $year = mysqli_real_escape_string($conn, $_POST['car_year']);
                $vin = mysqli_real_escape_string($conn, $_POST['car_vin']);
                $reg_number = mysqli_real_escape_string($conn, $_POST['car_reg_number']);

                // Handle image upload
                $image_path = '';
                if (isset($_FILES['car_image']) && $_FILES['car_image']['error'] === 0) {
                    $upload_dir = '../uploads/cars/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    $file_extension = strtolower(pathinfo($_FILES['car_image']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');

                    if (!in_array($file_extension, $allowed_extensions)) {
                        $error = "Invalid file type. Only JPG, JPEG, PNG & GIF files are allowed.";
                        break;
                    }

                    $image_path = 'uploads/cars/' . uniqid() . '.' . $file_extension;
                    if (!move_uploaded_file($_FILES['car_image']['tmp_name'], '../' . $image_path)) {
                        $error = "Failed to upload image.";
                        break;
                    }
                }

                // Check if VIN already exists
                $check_query = "SELECT COUNT(*) as count FROM tbl_car WHERE car_vin = ?";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bind_param('s', $vin);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                if ($result->fetch_assoc()['count'] > 0) {
                    $error = "VIN number already exists.";
                    // Delete uploaded image if exists
                    if ($image_path && file_exists('../' . $image_path)) {
                        unlink('../' . $image_path);
                    }
                    break;
                }

                // Check if registration number already exists
                $check_query = "SELECT COUNT(*) as count FROM tbl_car WHERE car_reg_number = ?";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bind_param('s', $reg_number);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                if ($result->fetch_assoc()['count'] > 0) {
                    $error = "Registration number already exists.";
                    // Delete uploaded image if exists
                    if ($image_path && file_exists('../' . $image_path)) {
                        unlink('../' . $image_path);
                    }
                    break;
                }

                $query = "INSERT INTO tbl_car (car_warehouse_id, car_brand_id, car_model_id, car_year, car_vin, car_reg_number, car_image)
                         VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('iiissss', $warehouse_id, $brand_id, $model_id, $year, $vin, $reg_number, $image_path);
                if ($stmt->execute()) {
                    $success = true;
                    $_SESSION['success'] = "Car added successfully.";
                } else {
                    $error = "Failed to add car.";
                    // Delete uploaded image if exists
                    if ($image_path && file_exists('../' . $image_path)) {
                        unlink('../' . $image_path);
                    }
                }
                break;

            case 'edit':
                $id = mysqli_real_escape_string($conn, $_POST['car_id']);
                $warehouse_id = mysqli_real_escape_string($conn, $_POST['car_warehouse_id']);
                $brand_id = mysqli_real_escape_string($conn, $_POST['car_brand_id']);
                $model_id = mysqli_real_escape_string($conn, $_POST['car_model_id']);
                $year = mysqli_real_escape_string($conn, $_POST['car_year']);
                $vin = mysqli_real_escape_string($conn, $_POST['car_vin']);
                $reg_number = mysqli_real_escape_string($conn, $_POST['car_reg_number']);

                // Get current image path
                $current_image_query = "SELECT car_image FROM tbl_car WHERE car_id = ?";
                $current_image_stmt = $conn->prepare($current_image_query);
                $current_image_stmt->bind_param('i', $id);
                $current_image_stmt->execute();
                $current_image = $current_image_stmt->get_result()->fetch_assoc()['car_image'];

                // Handle image upload
                $image_path = $current_image;
                if (isset($_FILES['car_image']) && $_FILES['car_image']['error'] === 0) {
                    $upload_dir = '../uploads/cars/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    $file_extension = strtolower(pathinfo($_FILES['car_image']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');

                    if (!in_array($file_extension, $allowed_extensions)) {
                        $error = "Invalid file type. Only JPG, JPEG, PNG & GIF files are allowed.";
                        break;
                    }

                    $image_path = 'uploads/cars/' . uniqid() . '.' . $file_extension;
                    if (!move_uploaded_file($_FILES['car_image']['tmp_name'], '../' . $image_path)) {
                        $error = "Failed to upload image.";
                        break;
                    }

                    // Delete old image if exists
                    if ($current_image && file_exists('../' . $current_image)) {
                        unlink('../' . $current_image);
                    }
                }

                // Check if VIN already exists for other cars
                $check_query = "SELECT COUNT(*) as count FROM tbl_car WHERE car_vin = ? AND car_id != ?";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bind_param('si', $vin, $id);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                if ($result->fetch_assoc()['count'] > 0) {
                    $error = "VIN number already exists.";
                    // Delete newly uploaded image if exists
                    if ($image_path !== $current_image && file_exists('../' . $image_path)) {
                        unlink('../' . $image_path);
                    }
                    break;
                }

                // Check if registration number already exists for other cars
                $check_query = "SELECT COUNT(*) as count FROM tbl_car WHERE car_reg_number = ? AND car_id != ?";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bind_param('si', $reg_number, $id);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                if ($result->fetch_assoc()['count'] > 0) {
                    $error = "Registration number already exists.";
                    // Delete newly uploaded image if exists
                    if ($image_path !== $current_image && file_exists('../' . $image_path)) {
                        unlink('../' . $image_path);
                    }
                    break;
                }

                $query = "UPDATE tbl_car SET
                         car_warehouse_id = ?,
                         car_brand_id = ?,
                         car_model_id = ?,
                         car_year = ?,
                         car_vin = ?,
                         car_reg_number = ?,
                         car_image = ?
                         WHERE car_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('iiissssi', $warehouse_id, $brand_id, $model_id, $year, $vin, $reg_number, $image_path, $id);
                if ($stmt->execute()) {
                    $success = true;
                    $_SESSION['success'] = "Car updated successfully.";
                } else {
                    $error = "Failed to update car.";
                    // Delete newly uploaded image if exists
                    if ($image_path !== $current_image && file_exists('../' . $image_path)) {
                        unlink('../' . $image_path);
                    }
                }
                break;

            case 'delete':
                $id = mysqli_real_escape_string($conn, $_POST['car_id']);

                // Check if car has associated stock entries
                $check_query = "SELECT COUNT(*) as stock_count FROM tbl_stock WHERE stock_car_id = ?";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bind_param('i', $id);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                if ($result->fetch_assoc()['stock_count'] > 0) {
                    $error = "Cannot delete car. It has associated stock entries.";
                    break;
                }

                // Get image path before deleting
                $image_query = "SELECT car_image FROM tbl_car WHERE car_id = ?";
                $image_stmt = $conn->prepare($image_query);
                $image_stmt->bind_param('i', $id);
                $image_stmt->execute();
                $image_path = $image_stmt->get_result()->fetch_assoc()['car_image'];

                $query = "DELETE FROM tbl_car WHERE car_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('i', $id);
                if ($stmt->execute()) {
                    // Delete image file if exists
                    if ($image_path && file_exists('../' . $image_path)) {
                        unlink('../' . $image_path);
                    }
                    $success = true;
                    $_SESSION['success'] = "Car deleted successfully.";
                } else {
                    $error = "Failed to delete car.";
                }
                break;
        }

        if ($error) {
            $_SESSION['error'] = $error;
        }

        // Redirect to prevent form resubmission
        header('Location: cars.php');
        exit();
    }
}

// Get all active warehouses for dropdowns
$warehouses = $conn->query("SELECT warehouse_id, warehouse_name FROM tbl_warehouse WHERE warehouse_status = 'active' ORDER BY warehouse_name");

// Get all active brands for dropdowns
$brands = $conn->query("SELECT brand_id, brand_name FROM tbl_brand WHERE brand_status = 'active' ORDER BY brand_name");

// Get all cars with their relationships
$cars = $conn->query("
    SELECT c.*,
           w.warehouse_name,
           b.brand_name,
           m.model_name,
           COUNT(s.stock_id) as parts_count
    FROM tbl_car c
    LEFT JOIN tbl_warehouse w ON c.car_warehouse_id = w.warehouse_id
    LEFT JOIN tbl_brand b ON c.car_brand_id = b.brand_id
    LEFT JOIN tbl_model m ON c.car_model_id = m.model_id
    LEFT JOIN tbl_stock s ON c.car_id = s.stock_car_id
    GROUP BY c.car_id
    ORDER BY b.brand_name, m.model_name, c.car_year DESC
");
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Manage Cars</h1>
    <button type="button" class="btn btn-primary" onclick="openAddCarModal()">
        <i class="fas fa-plus mr-2"></i>Add New Car
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

<!-- Cars List -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped" id="carsTable">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Brand</th>
                        <th>Model</th>
                        <th>Year</th>
                        <th>VIN</th>
                        <th>Reg Number</th>
                        <th>Warehouse</th>
                        <th>Parts</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($car = $cars->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?php if ($car['car_image']): ?>
                            <img src="../<?php echo htmlspecialchars($car['car_image']); ?>"
                                 alt="<?php echo htmlspecialchars($car['brand_name'] . ' ' . $car['model_name']); ?>"
                                 class="img-thumbnail"
                                 style="max-width: 50px;">
                            <?php else: ?>
                            <img src="../assets/img/no-image.png"
                                 alt="No Image"
                                 class="img-thumbnail"
                                 style="max-width: 50px;">
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($car['brand_name']); ?></td>
                        <td><?php echo htmlspecialchars($car['model_name']); ?></td>
                        <td><?php echo $car['car_year']; ?></td>
                        <td><?php echo htmlspecialchars($car['car_vin']); ?></td>
                        <td><?php echo htmlspecialchars($car['car_reg_number']); ?></td>
                        <td><?php echo htmlspecialchars($car['warehouse_name']); ?></td>
                        <td>
                            <span class="badge badge-info">
                                <?php echo $car['parts_count']; ?> parts
                            </span>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-info edit-car"
                                    data-id="<?php echo $car['car_id']; ?>"
                                    data-warehouse="<?php echo $car['car_warehouse_id']; ?>"
                                    data-brand="<?php echo $car['car_brand_id']; ?>"
                                    data-model="<?php echo $car['car_model_id']; ?>"
                                    data-year="<?php echo $car['car_year']; ?>"
                                    data-vin="<?php echo htmlspecialchars($car['car_vin']); ?>"
                                    data-reg="<?php echo htmlspecialchars($car['car_reg_number']); ?>"
                                    data-image="<?php echo htmlspecialchars($car['car_image']); ?>"
                                    data-toggle="modal" data-target="#editCarModal">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger delete-car"
                                    data-id="<?php echo $car['car_id']; ?>"
                                    data-name="<?php echo htmlspecialchars($car['brand_name'] . ' ' . $car['model_name']); ?>"
                                    data-parts="<?php echo $car['parts_count']; ?>">
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

<!-- Add Car Modal -->
<div class="modal fade" id="addCarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Car</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="cars.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">

                    <!-- Brand Dropdown -->
                    <div class="form-group">
                        <label for="car_brand_id">Brand</label>
                        <select class="form-control" id="car_brand_id" name="car_brand_id" required>
                            <option value="">Select Brand</option>
                            <?php
                            // Reset brands result pointer
                            $brands->data_seek(0);
                            while ($brand = $brands->fetch_assoc()): ?>
                            <option value="<?php echo $brand['brand_id']; ?>">
                                <?php echo htmlspecialchars($brand['brand_name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Model Dropdown -->
                    <div class="form-group">
                        <label for="car_model_id">Model</label>
                        <select class="form-control" id="car_model_id" name="car_model_id" required>
                            <option value="">Select Brand First</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="car_warehouse_id">Warehouse</label>
                        <select class="form-control" id="car_warehouse_id" name="car_warehouse_id" required>
                            <option value="">Select Warehouse</option>
                            <?php while ($warehouse = $warehouses->fetch_assoc()): ?>
                            <option value="<?php echo $warehouse['warehouse_id']; ?>">
                                <?php echo htmlspecialchars($warehouse['warehouse_name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="car_year">Year</label>
                        <select class="form-control" id="car_year" name="car_year" required>
                            <option value="">Select Year</option>
                            <?php
                            $current_year = date('Y');
                            for ($year = $current_year; $year >= 1900; $year--) {
                                echo "<option value=\"$year\">$year</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="car_vin">VIN Number</label>
                        <input type="text" class="form-control" id="car_vin" name="car_vin" required
                               pattern="[A-HJ-NPR-Z0-9]{17}"
                               title="Please enter a valid 17-character VIN (excluding I, O, Q)">
                        <small class="form-text text-muted">17 characters, no I, O, or Q allowed</small>
                    </div>
                    <div class="form-group">
                        <label for="car_reg_number">Registration Number</label>
                        <input type="text" class="form-control" id="car_reg_number" name="car_reg_number" required>
                    </div>
                    <div class="form-group">
                        <label for="car_image">Image</label>
                        <input type="file" class="form-control-file" id="car_image" name="car_image" accept="image/*">
                        <small class="form-text text-muted">Allowed formats: JPG, JPEG, PNG, GIF</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Car</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Car Modal -->
<div class="modal fade" id="editCarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Car</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="cars.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="car_id" id="edit_car_id">
                    <div class="form-group">
                        <label for="edit_car_warehouse_id">Warehouse</label>
                        <select class="form-control" id="edit_car_warehouse_id" name="car_warehouse_id" required>
                            <option value="">Select Warehouse</option>
                            <?php
                            // Reset warehouses result pointer
                            $warehouses->data_seek(0);
                            while ($warehouse = $warehouses->fetch_assoc()): ?>
                            <option value="<?php echo $warehouse['warehouse_id']; ?>">
                                <?php echo htmlspecialchars($warehouse['warehouse_name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_car_brand_id">Brand</label>
                        <select class="form-control" id="edit_car_brand_id" name="car_brand_id" required>
                            <option value="">Select Brand</option>
                            <?php
                            // Reset brands result pointer
                            $brands->data_seek(0);
                            while ($brand = $brands->fetch_assoc()): ?>
                            <option value="<?php echo $brand['brand_id']; ?>">
                                <?php echo htmlspecialchars($brand['brand_name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_car_model_id">Model</label>
                        <select class="form-control" id="edit_car_model_id" name="car_model_id" required>
                            <option value="">Select Brand First</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_car_year">Year</label>
                        <select class="form-control" id="edit_car_year" name="car_year" required>
                            <option value="">Select Year</option>
                            <?php
                            $current_year = date('Y');
                            for ($year = $current_year; $year >= 1900; $year--) {
                                echo "<option value=\"$year\">$year</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_car_vin">VIN Number</label>
                        <input type="text" class="form-control" id="edit_car_vin" name="car_vin" required
                               pattern="[A-HJ-NPR-Z0-9]{17}"
                               title="Please enter a valid 17-character VIN (excluding I, O, Q)">
                        <small class="form-text text-muted">17 characters, no I, O, or Q allowed</small>
                    </div>
                    <div class="form-group">
                        <label for="edit_car_reg_number">Registration Number</label>
                        <input type="text" class="form-control" id="edit_car_reg_number" name="car_reg_number" required>
                    </div>
                    <div class="form-group">
                        <label>Current Image</label>
                        <div id="current_image_preview" class="mb-2"></div>
                    </div>
                    <div class="form-group">
                        <label for="edit_car_image">New Image</label>
                        <input type="file" class="form-control-file" id="edit_car_image" name="car_image" accept="image/*">
                        <small class="form-text text-muted">Leave empty to keep current image. Allowed formats: JPG, JPEG, PNG, GIF</small>
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

<!-- Delete Car Form (Hidden) -->
<form id="deleteCarForm" action="cars.php" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="car_id" id="delete_car_id">
</form>

<script>
// Load models based on selected brand
function loadModels(brandId, targetSelect, selectedModelId = '') {
    console.log('Loading models for brand:', brandId); // Debug log

    if (!brandId) {
        $(targetSelect).html('<option value="">Select Brand First</option>');
        return;
    }

    $.ajax({
        url: 'ajax/get_models.php',
        type: 'POST',
        data: { brand_id: brandId },
        success: function(response) {
            try {
                const models = JSON.parse(response);
                const select = $(targetSelect);
                select.empty();
                select.append('<option value="">Select Model</option>');

                models.forEach(function(model) {
                    const option = $('<option></option>')
                        .val(model.model_id)
                        .text(model.model_name);
                    if (model.model_id == selectedModelId) {
                        option.prop('selected', true);
                    }
                    select.append(option);
                });
            } catch (e) {
                console.error('Error parsing models:', e);
                $(targetSelect).html('<option value="">Error loading models</option>');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            $(targetSelect).html('<option value="">Error loading models</option>');
        }
    });
}

// Brand change event for add form
$('#car_brand_id').on('change', function() {
    const brandId = $(this).val();
    console.log('Selected Brand ID:', brandId); // Debug log
    loadModels(brandId, '#car_model_id');
});

// Brand change event for edit form
$('#edit_car_brand_id').on('change', function() {
    const brandId = $(this).val();
    loadModels(brandId, '#edit_car_model_id');
});

// Edit car
$('.edit-car').on('click', function() {
    const id = $(this).data('id');
    const warehouse = $(this).data('warehouse');
    const brand = $(this).data('brand');
    const model = $(this).data('model');
    const year = $(this).data('year');
    const vin = $(this).data('vin');
    const reg = $(this).data('reg');
    const image = $(this).data('image');

    $('#edit_car_id').val(id);
    $('#edit_car_warehouse_id').val(warehouse);
    $('#edit_car_brand_id').val(brand);
    loadModels(brand, '#edit_car_model_id', model);
    $('#edit_car_year').val(year);
    $('#edit_car_vin').val(vin);
    $('#edit_car_reg_number').val(reg);

    // Update image preview
    const imagePreview = $('#current_image_preview');
    if (image) {
        imagePreview.html(`<img src="../${image}" alt="Car Image" class="img-thumbnail" style="max-width: 200px;">`);
    } else {
        imagePreview.html(`<img src="../assets/img/no-image.png" alt="No Image" class="img-thumbnail" style="max-width: 200px;">`);
    }
});

// Delete car
$('.delete-car').on('click', function() {
    const id = $(this).data('id');
    const name = $(this).data('name');
    const parts = $(this).data('parts');

    if (parts > 0) {
        alert(`Cannot delete car "${name}". It has ${parts} associated parts.`);
        return;
    }

    if (confirm(`Are you sure you want to delete the car "${name}"?`)) {
        $('#delete_car_id').val(id);
        $('#deleteCarForm').submit();
    }
});

// Initialize DataTable
$(document).ready(function() {
    $('#carsTable').DataTable({
        "order": [[1, "asc"], [2, "asc"], [3, "desc"]],
        "pageLength": 25
    });

    // Initialize Bootstrap modals
    $('.modal').modal({
        keyboard: true,
        backdrop: 'static'
    });

    // Add New Car button click handler
    $('.btn-primary[data-bs-toggle="modal"]').on('click', function() {
        $('#addCarModal').modal('show');
    });

    // Alternative button click handler
    $(document).on('click', '[data-bs-toggle="modal"]', function() {
        var target = $(this).data('bs-target');
        $(target).modal('show');
    });

    // Reset model dropdown when modal is opened
    $('#addCarModal').on('show.bs.modal', function() {
        $('#car_model_id').html('<option value="">Select Brand First</option>');
    });

    // Reset form when modal is closed
    $('#addCarModal').on('hidden.bs.modal', function() {
        $(this).find('form')[0].reset();
        $('#car_model_id').html('<option value="">Select Brand First</option>');
    });
});

// Image preview for add form
$('#car_image').on('change', function() {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            $('#current_image_preview').html(`<img src="${e.target.result}" class="img-thumbnail" style="max-width: 200px;">`);
        }
        reader.readAsDataURL(file);
    }
});

// Image preview for edit form
$('#edit_car_image').on('change', function() {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            $('#current_image_preview').html(`<img src="${e.target.result}" class="img-thumbnail" style="max-width: 200px;">`);
        }
        reader.readAsDataURL(file);
    }
});

function openAddCarModal() {
    $('#addCarModal').modal('show');
}

// Make sure modals are properly initialized
$(document).ready(function() {
    // Initialize modals
    if (typeof bootstrap !== 'undefined') {
        // Bootstrap 5
        var modals = document.querySelectorAll('.modal');
        modals.forEach(function(modal) {
            new bootstrap.Modal(modal);
        });
    } else {
        // Bootstrap 4
        $('.modal').modal({
            keyboard: true,
            backdrop: 'static'
        });
    }
});
</script>
