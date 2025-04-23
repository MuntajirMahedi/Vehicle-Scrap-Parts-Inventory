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
                $name = mysqli_real_escape_string($conn, $_POST['brand_name']);
                $status = mysqli_real_escape_string($conn, $_POST['brand_status']);

                // Check if brand name already exists
                $check_query = "SELECT COUNT(*) as count FROM tbl_brand WHERE brand_name = ?";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bind_param('s', $name);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                if ($result->fetch_assoc()['count'] > 0) {
                    $error = "Brand name already exists.";
                    break;
                }

                // Handle image upload
                $image = '';
                if (isset($_FILES['brand_image']) && $_FILES['brand_image']['error'] === 0) {
                    $target_dir = "../uploads/brands/";
                    if (!file_exists($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }

                    $file_extension = strtolower(pathinfo($_FILES['brand_image']['name'], PATHINFO_EXTENSION));
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

                    if (!in_array($file_extension, $allowed_types)) {
                        $error = "Invalid file type. Only JPG, JPEG, PNG & GIF files are allowed.";
                        break;
                    }

                    $file_name = uniqid() . '.' . $file_extension;
                    $target_file = $target_dir . $file_name;

                    if (move_uploaded_file($_FILES['brand_image']['tmp_name'], $target_file)) {
                        $image = 'uploads/brands/' . $file_name;
                    } else {
                        $error = "Failed to upload image.";
                        break;
                    }
                }

                $query = "INSERT INTO tbl_brand (brand_name, brand_image, brand_status) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('sss', $name, $image, $status);
                if ($stmt->execute()) {
                    $success = true;
                    $_SESSION['success'] = "Brand added successfully.";
                } else {
                    $error = "Failed to add brand.";
                }
                break;

            case 'edit':
                $id = mysqli_real_escape_string($conn, $_POST['brand_id']);
                $name = mysqli_real_escape_string($conn, $_POST['brand_name']);
                $status = mysqli_real_escape_string($conn, $_POST['brand_status']);

                // Check if brand name already exists for other brands
                $check_query = "SELECT COUNT(*) as count FROM tbl_brand WHERE brand_name = ? AND brand_id != ?";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bind_param('si', $name, $id);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                if ($result->fetch_assoc()['count'] > 0) {
                    $error = "Brand name already exists.";
                    break;
                }

                // Get current image
                $current_image_query = "SELECT brand_image FROM tbl_brand WHERE brand_id = ?";
                $current_image_stmt = $conn->prepare($current_image_query);
                $current_image_stmt->bind_param('i', $id);
                $current_image_stmt->execute();
                $current_image = $current_image_stmt->get_result()->fetch_assoc()['brand_image'];

                // Handle image upload
                $image_update = '';
                if (isset($_FILES['brand_image']) && $_FILES['brand_image']['error'] === 0) {
                    $target_dir = "../uploads/brands/";
                    if (!file_exists($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }

                    $file_extension = strtolower(pathinfo($_FILES['brand_image']['name'], PATHINFO_EXTENSION));
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

                    if (!in_array($file_extension, $allowed_types)) {
                        $error = "Invalid file type. Only JPG, JPEG, PNG & GIF files are allowed.";
                        break;
                    }

                    $file_name = uniqid() . '.' . $file_extension;
                    $target_file = $target_dir . $file_name;

                    if (move_uploaded_file($_FILES['brand_image']['tmp_name'], $target_file)) {
                        $image = 'uploads/brands/' . $file_name;
                        $image_update = ", brand_image = ?";

                        // Delete old image if exists
                        if ($current_image && file_exists("../" . $current_image)) {
                            unlink("../" . $current_image);
                        }
                    } else {
                        $error = "Failed to upload image.";
                        break;
                    }
                }

                $query = "UPDATE tbl_brand SET brand_name = ?, brand_status = ?" . $image_update . " WHERE brand_id = ?";
                $stmt = $conn->prepare($query);
                if ($image_update) {
                    $stmt->bind_param('sssi', $name, $status, $image, $id);
                } else {
                    $stmt->bind_param('ssi', $name, $status, $id);
                }

                if ($stmt->execute()) {
                    $success = true;
                    $_SESSION['success'] = "Brand updated successfully.";
                } else {
                    $error = "Failed to update brand.";
                }
                break;

            case 'delete':
                $id = mysqli_real_escape_string($conn, $_POST['brand_id']);

                // Check if brand has associated models
                $check_query = "SELECT COUNT(*) as count FROM tbl_model WHERE model_brand_id = ?";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bind_param('i', $id);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                if ($result->fetch_assoc()['count'] > 0) {
                    $error = "Cannot delete brand. It has associated models.";
                    break;
                }

                // Get current image
                $current_image_query = "SELECT brand_image FROM tbl_brand WHERE brand_id = ?";
                $current_image_stmt = $conn->prepare($current_image_query);
                $current_image_stmt->bind_param('i', $id);
                $current_image_stmt->execute();
                $current_image = $current_image_stmt->get_result()->fetch_assoc()['brand_image'];

                $query = "DELETE FROM tbl_brand WHERE brand_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('i', $id);
                if ($stmt->execute()) {
                    // Delete image file if exists
                    if ($current_image && file_exists("../" . $current_image)) {
                        unlink("../" . $current_image);
                    }
                    $success = true;
                    $_SESSION['success'] = "Brand deleted successfully.";
                } else {
                    $error = "Failed to delete brand.";
                }
                break;
        }

        if ($error) {
            $_SESSION['error'] = $error;
        }

        // Redirect to prevent form resubmission
        header('Location: brands.php');
        exit();
    }
}

// Get all brands with model counts
$brands = $conn->query("
    SELECT b.*, COUNT(m.model_id) as models_count
    FROM tbl_brand b
    LEFT JOIN tbl_model m ON b.brand_id = m.model_brand_id
    GROUP BY b.brand_id
    ORDER BY b.brand_name
");
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Manage Brands</h1>
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addBrandModal">
        <i class="fas fa-plus mr-2"></i>Add New Brand
    </button>
</div>

<!-- After page header, before brands list -->
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

<!-- Brands List -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped" id="brandsTable">
                <thead>
                    <tr>
                        <th>Logo</th>
                        <th>Brand Name</th>
                        <th>Status</th>
                        <th>Created Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($brand = $brands->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?php if ($brand['brand_image']): ?>
                                <img src="../<?php echo htmlspecialchars($brand['brand_image']); ?>"
                                     alt="<?php echo htmlspecialchars($brand['brand_name']); ?>"
                                     class="img-thumbnail"
                                     style="max-width: 50px;">
                            <?php else: ?>
                                <div class="text-muted">No image</div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($brand['brand_name']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $brand['brand_status'] === 'active' ? 'success' : 'danger'; ?>">
                                <?php echo ucfirst($brand['brand_status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('d M Y', strtotime($brand['brand_created_date'])); ?></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-info edit-brand"
                                    data-id="<?php echo $brand['brand_id']; ?>"
                                    data-name="<?php echo htmlspecialchars($brand['brand_name']); ?>"
                                    data-status="<?php echo $brand['brand_status']; ?>"
                                    data-toggle="modal" data-target="#editBrandModal">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger delete-brand"
                                    data-id="<?php echo $brand['brand_id']; ?>"
                                    data-name="<?php echo htmlspecialchars($brand['brand_name']); ?>"
                                    data-models="<?php echo $brand['models_count']; ?>">
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

<!-- Add Brand Modal -->
<div class="modal fade" id="addBrandModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Brand</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="brands.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label for="brand_name">Brand Name</label>
                        <input type="text" class="form-control" id="brand_name" name="brand_name" required>
                    </div>
                    <div class="form-group">
                        <label for="brand_image">Brand Logo</label>
                        <input type="file" class="form-control-file" id="brand_image" name="brand_image" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label for="brand_status">Status</label>
                        <select class="form-control" id="brand_status" name="brand_status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Brand</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Brand Modal -->
<div class="modal fade" id="editBrandModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Brand</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="brands.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="brand_id" id="edit_brand_id">
                    <div class="form-group">
                        <label for="edit_brand_name">Brand Name</label>
                        <input type="text" class="form-control" id="edit_brand_name" name="brand_name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_brand_image">Brand Logo</label>
                        <input type="file" class="form-control-file" id="edit_brand_image" name="brand_image" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label for="edit_brand_status">Status</label>
                        <select class="form-control" id="edit_brand_status" name="brand_status">
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

<!-- Delete Brand Form (Hidden) -->
<form id="deleteBrandForm" action="brands.php" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="brand_id" id="delete_brand_id">
</form>

<script>
// Edit brand
$('.edit-brand').on('click', function() {
    const id = $(this).data('id');
    const name = $(this).data('name');
    const status = $(this).data('status');

    $('#edit_brand_id').val(id);
    $('#edit_brand_name').val(name);
    $('#edit_brand_status').val(status);
});

// Delete brand
$('.delete-brand').on('click', function() {
    const id = $(this).data('id');
    const name = $(this).data('name');
    const models = $(this).data('models');

    if (models > 0) {
        alert(`Cannot delete brand "${name}". It has ${models} associated models.`);
        return;
    }

    if (confirm(`Are you sure you want to delete the brand "${name}"?`)) {
        $('#delete_brand_id').val(id);
        $('#deleteBrandForm').submit();
    }
});

// Initialize DataTable
$(document).ready(function() {
    $('#brandsTable').DataTable({
        "order": [[1, "asc"]],
        "pageLength": 25
    });
});
</script>

<?php
require_once '../includes/footer.php';
?>
