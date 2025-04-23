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
                $brand_id = mysqli_real_escape_string($conn, $_POST['model_brand_id']);
                $name = mysqli_real_escape_string($conn, $_POST['model_name']);
                $status = mysqli_real_escape_string($conn, $_POST['model_status']);

                // Check if model name already exists for this brand
                $check_query = "SELECT COUNT(*) as count FROM tbl_model WHERE model_name = ? AND model_brand_id = ?";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bind_param('si', $name, $brand_id);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                if ($result->fetch_assoc()['count'] > 0) {
                    $error = "Model name already exists for this brand.";
                    break;
                }

                // Handle image upload
                $image = '';
                if (isset($_FILES['model_image']) && $_FILES['model_image']['error'] === 0) {
                    $target_dir = "../uploads/models/";
                    if (!file_exists($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }

                    $file_extension = strtolower(pathinfo($_FILES['model_image']['name'], PATHINFO_EXTENSION));
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

                    if (!in_array($file_extension, $allowed_types)) {
                        $error = "Invalid file type. Only JPG, JPEG, PNG & GIF files are allowed.";
                        break;
                    }

                    $file_name = uniqid() . '.' . $file_extension;
                    $target_file = $target_dir . $file_name;

                    if (move_uploaded_file($_FILES['model_image']['tmp_name'], $target_file)) {
                        $image = 'uploads/models/' . $file_name;
                    } else {
                        $error = "Failed to upload image.";
                        break;
                    }
                }

                $query = "INSERT INTO tbl_model (model_brand_id, model_name, model_image, model_status) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('isss', $brand_id, $name, $image, $status);
                if ($stmt->execute()) {
                    $success = true;
                    $_SESSION['success'] = "Model added successfully.";
                } else {
                    $error = "Failed to add model.";
                }
                break;

            case 'edit':
                $id = mysqli_real_escape_string($conn, $_POST['model_id']);
                $brand_id = mysqli_real_escape_string($conn, $_POST['model_brand_id']);
                $name = mysqli_real_escape_string($conn, $_POST['model_name']);
                $status = mysqli_real_escape_string($conn, $_POST['model_status']);

                // Check if model name already exists for this brand (excluding current model)
                $check_query = "SELECT COUNT(*) as count FROM tbl_model WHERE model_name = ? AND model_brand_id = ? AND model_id != ?";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bind_param('sii', $name, $brand_id, $id);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                if ($result->fetch_assoc()['count'] > 0) {
                    $error = "Model name already exists for this brand.";
                    break;
                }

                // Get current image
                $current_image_query = "SELECT model_image FROM tbl_model WHERE model_id = ?";
                $current_image_stmt = $conn->prepare($current_image_query);
                $current_image_stmt->bind_param('i', $id);
                $current_image_stmt->execute();
                $current_image = $current_image_stmt->get_result()->fetch_assoc()['model_image'];

                // Handle image upload
                $image_update = '';
                if (isset($_FILES['model_image']) && $_FILES['model_image']['error'] === 0) {
                    $target_dir = "../uploads/models/";
                    if (!file_exists($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }

                    $file_extension = strtolower(pathinfo($_FILES['model_image']['name'], PATHINFO_EXTENSION));
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

                    if (!in_array($file_extension, $allowed_types)) {
                        $error = "Invalid file type. Only JPG, JPEG, PNG & GIF files are allowed.";
                        break;
                    }

                    $file_name = uniqid() . '.' . $file_extension;
                    $target_file = $target_dir . $file_name;

                    if (move_uploaded_file($_FILES['model_image']['tmp_name'], $target_file)) {
                        $image = 'uploads/models/' . $file_name;
                        $image_update = ", model_image = ?";

                        // Delete old image if exists
                        if ($current_image && file_exists("../" . $current_image)) {
                            unlink("../" . $current_image);
                        }
                    } else {
                        $error = "Failed to upload image.";
                        break;
                    }
                }

                $query = "UPDATE tbl_model SET model_brand_id = ?, model_name = ?, model_status = ?" . $image_update . " WHERE model_id = ?";
                $stmt = $conn->prepare($query);
                if ($image_update) {
                    $stmt->bind_param('issssi', $brand_id, $name, $status, $image, $id);
                } else {
                    $stmt->bind_param('issi', $brand_id, $name, $status, $id);
                }

                if ($stmt->execute()) {
                    $success = true;
                    $_SESSION['success'] = "Model updated successfully.";
                } else {
                    $error = "Failed to update model.";
                }
                break;

            case 'delete':
                $id = mysqli_real_escape_string($conn, $_POST['model_id']);

                // Check if model has associated cars
                $check_query = "SELECT COUNT(*) as cars_count FROM tbl_car WHERE car_model_id = ?";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bind_param('i', $id);
                $check_stmt->execute();
                $result = $check_stmt->get_result()->fetch_assoc();

                if ($result['cars_count'] > 0) {
                    $error = "Cannot delete model. It has associated cars.";
                    break;
                }

                // Get current image
                $current_image_query = "SELECT model_image FROM tbl_model WHERE model_id = ?";
                $current_image_stmt = $conn->prepare($current_image_query);
                $current_image_stmt->bind_param('i', $id);
                $current_image_stmt->execute();
                $current_image = $current_image_stmt->get_result()->fetch_assoc()['model_image'];

                $query = "DELETE FROM tbl_model WHERE model_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('i', $id);
                if ($stmt->execute()) {
                    // Delete image file if exists
                    if ($current_image && file_exists("../" . $current_image)) {
                        unlink("../" . $current_image);
                    }
                    $success = true;
                    $_SESSION['success'] = "Model deleted successfully.";
                } else {
                    $error = "Failed to delete model.";
                }
                break;
        }

        if ($error) {
            $_SESSION['error'] = $error;
        }

        // Redirect to prevent form resubmission
        header('Location: models.php');
        exit();
    }
}

// Get all models with brand names and counts
$models = $conn->query("
    SELECT m.*, b.brand_name,
           (SELECT COUNT(*) FROM tbl_part p
            INNER JOIN tbl_car c ON c.car_model_id = m.model_id
            WHERE p.part_category_id IN (
                SELECT category_id FROM tbl_category WHERE category_id IN (
                    SELECT part_category_id FROM tbl_part WHERE part_id IN (
                        SELECT part_id FROM tbl_stock WHERE car_id = c.car_id
                    )
                )
            )) as parts_count,
           (SELECT COUNT(*) FROM tbl_car WHERE car_model_id = m.model_id) as cars_count
    FROM tbl_model m
    JOIN tbl_brand b ON m.model_brand_id = b.brand_id
    ORDER BY b.brand_name, m.model_name
");

// Get all active brands for the dropdown
$brands = $conn->query("SELECT brand_id, brand_name FROM tbl_brand WHERE brand_status = 'active' ORDER BY brand_name");
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Manage Models</h1>
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addModelModal">
        <i class="fas fa-plus mr-2"></i>Add New Model
    </button>
</div>

<!-- After page header, before models list -->
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

<!-- Models List -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped" id="modelsTable">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Brand</th>
                        <th>Model Name</th>
                        <th>Status</th>
                        <th>Created Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($model = $models->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?php if ($model['model_image']): ?>
                                <img src="../<?php echo htmlspecialchars($model['model_image']); ?>"
                                     alt="<?php echo htmlspecialchars($model['model_name']); ?>"
                                     class="img-thumbnail"
                                     style="max-width: 50px;">
                            <?php else: ?>
                                <div class="text-muted">No image</div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($model['brand_name']); ?></td>
                        <td><?php echo htmlspecialchars($model['model_name']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $model['model_status'] === 'active' ? 'success' : 'danger'; ?>">
                                <?php echo ucfirst($model['model_status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('d M Y', strtotime($model['model_created_date'])); ?></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-info edit-model"
                                    data-id="<?php echo $model['model_id']; ?>"
                                    data-brand-id="<?php echo $model['model_brand_id']; ?>"
                                    data-name="<?php echo htmlspecialchars($model['model_name']); ?>"
                                    data-status="<?php echo $model['model_status']; ?>"
                                    data-toggle="modal" data-target="#editModelModal">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger delete-model"
                                    data-id="<?php echo $model['model_id']; ?>"
                                    data-name="<?php echo htmlspecialchars($model['model_name']); ?>"
                                    data-parts="<?php echo $model['parts_count']; ?>"
                                    data-cars="<?php echo $model['cars_count']; ?>">
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

<!-- Add Model Modal -->
<div class="modal fade" id="addModelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Model</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="models.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label for="model_brand_id">Brand</label>
                        <select class="form-control" id="model_brand_id" name="model_brand_id" required>
                            <option value="">Select Brand</option>
                            <?php while ($brand = $brands->fetch_assoc()): ?>
                                <option value="<?php echo $brand['brand_id']; ?>">
                                    <?php echo htmlspecialchars($brand['brand_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="model_name">Model Name</label>
                        <input type="text" class="form-control" id="model_name" name="model_name" required>
                    </div>
                    <div class="form-group">
                        <label for="model_image">Model Image</label>
                        <input type="file" class="form-control-file" id="model_image" name="model_image" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label for="model_status">Status</label>
                        <select class="form-control" id="model_status" name="model_status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Model</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Model Modal -->
<div class="modal fade" id="editModelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Model</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="models.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="model_id" id="edit_model_id">
                    <div class="form-group">
                        <label for="edit_model_brand_id">Brand</label>
                        <select class="form-control" id="edit_model_brand_id" name="model_brand_id" required>
                            <?php
                            // Reset pointer to beginning of brands result
                            $brands->data_seek(0);
                            while ($brand = $brands->fetch_assoc()):
                            ?>
                                <option value="<?php echo $brand['brand_id']; ?>">
                                    <?php echo htmlspecialchars($brand['brand_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_model_name">Model Name</label>
                        <input type="text" class="form-control" id="edit_model_name" name="model_name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_model_image">Model Image</label>
                        <input type="file" class="form-control-file" id="edit_model_image" name="model_image" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label for="edit_model_status">Status</label>
                        <select class="form-control" id="edit_model_status" name="model_status">
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

<!-- Delete Model Form (Hidden) -->
<form id="deleteModelForm" action="models.php" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="model_id" id="delete_model_id">
</form>

<script>
// Edit model
$('.edit-model').on('click', function() {
    const id = $(this).data('id');
    const brandId = $(this).data('brand-id');
    const name = $(this).data('name');
    const status = $(this).data('status');

    $('#edit_model_id').val(id);
    $('#edit_model_brand_id').val(brandId);
    $('#edit_model_name').val(name);
    $('#edit_model_status').val(status);
});

// Delete model
$('.delete-model').on('click', function() {
    const id = $(this).data('id');
    const name = $(this).data('name');
    const cars = $(this).data('cars');

    if (cars > 0) {
        alert(`Cannot delete model "${name}". It has ${cars} associated cars.`);
        return;
    }

    if (confirm(`Are you sure you want to delete the model "${name}"?`)) {
        $('#delete_model_id').val(id);
        $('#deleteModelForm').submit();
    }
});

// Initialize DataTable
$(document).ready(function() {
    $('#modelsTable').DataTable({
        "order": [[1, "asc"], [2, "asc"]],
        "pageLength": 25
    });
});
</script>

<?php
require_once '../includes/footer.php';
?>
