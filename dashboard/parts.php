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
                $category_id = mysqli_real_escape_string($conn, $_POST['part_category_id']);
                $name = mysqli_real_escape_string($conn, $_POST['part_name']);
                $status = mysqli_real_escape_string($conn, $_POST['part_status']);

                // Handle image upload
                $image_path = '';
                if (isset($_FILES['part_image']) && $_FILES['part_image']['error'] === 0) {
                    $upload_dir = '../uploads/parts/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    $file_extension = strtolower(pathinfo($_FILES['part_image']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');

                    if (!in_array($file_extension, $allowed_extensions)) {
                        $error = "Invalid file type. Only JPG, JPEG, PNG & GIF files are allowed.";
                        break;
                    }

                    $image_path = 'uploads/parts/' . uniqid() . '.' . $file_extension;
                    if (!move_uploaded_file($_FILES['part_image']['tmp_name'], '../' . $image_path)) {
                        $error = "Failed to upload image.";
                        break;
                    }
                }

                // Check if part name already exists in the same category
                $check_query = "SELECT COUNT(*) as count FROM tbl_part WHERE part_name = ? AND part_category_id = ?";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bind_param('si', $name, $category_id);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                if ($result->fetch_assoc()['count'] > 0) {
                    $error = "Part name already exists in this category.";
                    // Delete uploaded image if exists
                    if ($image_path && file_exists('../' . $image_path)) {
                        unlink('../' . $image_path);
                    }
                    break;
                }

                $query = "INSERT INTO tbl_part (part_category_id, part_name, part_image, part_status) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('isss', $category_id, $name, $image_path, $status);
                if ($stmt->execute()) {
                    $success = true;
                    $_SESSION['success'] = "Part added successfully.";
                } else {
                    $error = "Failed to add part.";
                    // Delete uploaded image if exists
                    if ($image_path && file_exists('../' . $image_path)) {
                        unlink('../' . $image_path);
                    }
                }
                break;

            case 'edit':
                $id = mysqli_real_escape_string($conn, $_POST['part_id']);
                $category_id = mysqli_real_escape_string($conn, $_POST['part_category_id']);
                $name = mysqli_real_escape_string($conn, $_POST['part_name']);
                $status = mysqli_real_escape_string($conn, $_POST['part_status']);

                // Get current image path
                $current_image_query = "SELECT part_image FROM tbl_part WHERE part_id = ?";
                $current_image_stmt = $conn->prepare($current_image_query);
                $current_image_stmt->bind_param('i', $id);
                $current_image_stmt->execute();
                $current_image = $current_image_stmt->get_result()->fetch_assoc()['part_image'];

                // Handle image upload
                $image_path = $current_image;
                if (isset($_FILES['part_image']) && $_FILES['part_image']['error'] === 0) {
                    $upload_dir = '../uploads/parts/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    $file_extension = strtolower(pathinfo($_FILES['part_image']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');

                    if (!in_array($file_extension, $allowed_extensions)) {
                        $error = "Invalid file type. Only JPG, JPEG, PNG & GIF files are allowed.";
                        break;
                    }

                    $image_path = 'uploads/parts/' . uniqid() . '.' . $file_extension;
                    if (!move_uploaded_file($_FILES['part_image']['tmp_name'], '../' . $image_path)) {
                        $error = "Failed to upload image.";
                        break;
                    }

                    // Delete old image if exists
                    if ($current_image && file_exists('../' . $current_image)) {
                        unlink('../' . $current_image);
                    }
                }

                // Check if part name already exists in the same category for other parts
                $check_query = "SELECT COUNT(*) as count FROM tbl_part WHERE part_name = ? AND part_category_id = ? AND part_id != ?";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bind_param('sii', $name, $category_id, $id);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                if ($result->fetch_assoc()['count'] > 0) {
                    $error = "Part name already exists in this category.";
                    // Delete newly uploaded image if exists
                    if ($image_path !== $current_image && file_exists('../' . $image_path)) {
                        unlink('../' . $image_path);
                    }
                    break;
                }

                $query = "UPDATE tbl_part SET part_category_id = ?, part_name = ?, part_image = ?, part_status = ? WHERE part_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('isssi', $category_id, $name, $image_path, $status, $id);
                if ($stmt->execute()) {
                    $success = true;
                    $_SESSION['success'] = "Part updated successfully.";
                } else {
                    $error = "Failed to update part.";
                    // Delete newly uploaded image if exists
                    if ($image_path !== $current_image && file_exists('../' . $image_path)) {
                        unlink('../' . $image_path);
                    }
                }
                break;

            case 'delete':
                $id = mysqli_real_escape_string($conn, $_POST['part_id']);

                // Check if part has associated stock entries
                $check_query = "SELECT COUNT(*) as stock_count FROM tbl_stock WHERE stock_part_id = ?";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bind_param('i', $id);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                if ($result->fetch_assoc()['stock_count'] > 0) {
                    $error = "Cannot delete part. It has associated stock entries.";
                    break;
                }

                // Get image path before deleting
                $image_query = "SELECT part_image FROM tbl_part WHERE part_id = ?";
                $image_stmt = $conn->prepare($image_query);
                $image_stmt->bind_param('i', $id);
                $image_stmt->execute();
                $image_path = $image_stmt->get_result()->fetch_assoc()['part_image'];

                $query = "DELETE FROM tbl_part WHERE part_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('i', $id);
                if ($stmt->execute()) {
                    // Delete image file if exists
                    if ($image_path && file_exists('../' . $image_path)) {
                        unlink('../' . $image_path);
                    }
                    $success = true;
                    $_SESSION['success'] = "Part deleted successfully.";
                } else {
                    $error = "Failed to delete part.";
                }
                break;
        }

        if ($error) {
            $_SESSION['error'] = $error;
        }

        // Redirect to prevent form resubmission
        header('Location: parts.php');
        exit();
    }
}

// Get all active categories for dropdowns
$categories = $conn->query("SELECT category_id, category_name FROM tbl_category WHERE category_status = 'active' ORDER BY category_name");

// Get all parts with their categories and stock counts
$parts = $conn->query("
    SELECT p.*, c.category_name,
           COUNT(s.stock_id) as stock_count,
           SUM(CASE WHEN s.stock_status = 'in_stock' THEN 1 ELSE 0 END) as available_stock
    FROM tbl_part p
    LEFT JOIN tbl_category c ON p.part_category_id = c.category_id
    LEFT JOIN tbl_stock s ON p.part_id = s.stock_part_id
    GROUP BY p.part_id
    ORDER BY c.category_name, p.part_name
");
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Manage Parts</h1>
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addPartModal">
        <i class="fas fa-plus mr-2"></i>Add New Part
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

<!-- Parts List -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped" id="partsTable">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Part Name</th>
                        <th>Category</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Created Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($part = $parts->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?php if ($part['part_image']): ?>
                            <img src="../<?php echo htmlspecialchars($part['part_image']); ?>"
                                 alt="<?php echo htmlspecialchars($part['part_name']); ?>"
                                 class="img-thumbnail"
                                 style="max-width: 50px;">
                            <?php else: ?>
                            <img src="../assets/img/no-image.png"
                                 alt="No Image"
                                 class="img-thumbnail"
                                 style="max-width: 50px;">
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($part['part_name']); ?></td>
                        <td><?php echo htmlspecialchars($part['category_name']); ?></td>
                        <td>
                            <span class="badge badge-info">
                                <?php echo $part['available_stock']; ?> available
                            </span>
                            <span class="badge badge-secondary">
                                <?php echo $part['stock_count']; ?> total
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $part['part_status'] === 'active' ? 'success' : 'danger'; ?>">
                                <?php echo ucfirst($part['part_status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('d M Y', strtotime($part['part_created_date'])); ?></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-info edit-part"
                                    data-id="<?php echo $part['part_id']; ?>"
                                    data-name="<?php echo htmlspecialchars($part['part_name']); ?>"
                                    data-category="<?php echo $part['part_category_id']; ?>"
                                    data-status="<?php echo $part['part_status']; ?>"
                                    data-image="<?php echo htmlspecialchars($part['part_image']); ?>"
                                    data-toggle="modal" data-target="#editPartModal">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger delete-part"
                                    data-id="<?php echo $part['part_id']; ?>"
                                    data-name="<?php echo htmlspecialchars($part['part_name']); ?>"
                                    data-stock="<?php echo $part['stock_count']; ?>">
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

<!-- Add Part Modal -->
<div class="modal fade" id="addPartModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Part</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="parts.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label for="part_category_id">Category</label>
                        <select class="form-control" id="part_category_id" name="part_category_id" required>
                            <option value="">Select Category</option>
                            <?php while ($category = $categories->fetch_assoc()): ?>
                            <option value="<?php echo $category['category_id']; ?>">
                                <?php echo htmlspecialchars($category['category_name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="part_name">Part Name</label>
                        <input type="text" class="form-control" id="part_name" name="part_name" required>
                    </div>
                    <div class="form-group">
                        <label for="part_image">Image</label>
                        <input type="file" class="form-control-file" id="part_image" name="part_image" accept="image/*">
                        <small class="form-text text-muted">Allowed formats: JPG, JPEG, PNG, GIF</small>
                    </div>
                    <div class="form-group">
                        <label for="part_status">Status</label>
                        <select class="form-control" id="part_status" name="part_status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Part</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Part Modal -->
<div class="modal fade" id="editPartModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Part</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="parts.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="part_id" id="edit_part_id">
                    <div class="form-group">
                        <label for="edit_part_category_id">Category</label>
                        <select class="form-control" id="edit_part_category_id" name="part_category_id" required>
                            <option value="">Select Category</option>
                            <?php
                            // Reset categories result pointer
                            $categories->data_seek(0);
                            while ($category = $categories->fetch_assoc()): ?>
                            <option value="<?php echo $category['category_id']; ?>">
                                <?php echo htmlspecialchars($category['category_name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_part_name">Part Name</label>
                        <input type="text" class="form-control" id="edit_part_name" name="part_name" required>
                    </div>
                    <div class="form-group">
                        <label>Current Image</label>
                        <div id="current_image_preview" class="mb-2"></div>
                    </div>
                    <div class="form-group">
                        <label for="edit_part_image">New Image</label>
                        <input type="file" class="form-control-file" id="edit_part_image" name="part_image" accept="image/*">
                        <small class="form-text text-muted">Leave empty to keep current image. Allowed formats: JPG, JPEG, PNG, GIF</small>
                    </div>
                    <div class="form-group">
                        <label for="edit_part_status">Status</label>
                        <select class="form-control" id="edit_part_status" name="part_status">
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

<!-- Delete Part Form (Hidden) -->
<form id="deletePartForm" action="parts.php" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="part_id" id="delete_part_id">
</form>

<script>
// Edit part
$('.edit-part').on('click', function() {
    const id = $(this).data('id');
    const name = $(this).data('name');
    const category = $(this).data('category');
    const status = $(this).data('status');
    const image = $(this).data('image');

    $('#edit_part_id').val(id);
    $('#edit_part_name').val(name);
    $('#edit_part_category_id').val(category);
    $('#edit_part_status').val(status);

    // Update image preview
    const imagePreview = $('#current_image_preview');
    if (image) {
        imagePreview.html(`<img src="../${image}" alt="${name}" class="img-thumbnail" style="max-width: 200px;">`);
    } else {
        imagePreview.html(`<img src="../assets/img/no-image.png" alt="No Image" class="img-thumbnail" style="max-width: 200px;">`);
    }
});

// Delete part
$('.delete-part').on('click', function() {
    const id = $(this).data('id');
    const name = $(this).data('name');
    const stock = $(this).data('stock');

    if (stock > 0) {
        alert(`Cannot delete part "${name}". It has ${stock} associated stock entries.`);
        return;
    }

    if (confirm(`Are you sure you want to delete the part "${name}"?`)) {
        $('#delete_part_id').val(id);
        $('#deletePartForm').submit();
    }
});

// Initialize DataTable
$(document).ready(function() {
    $('#partsTable').DataTable({
        "order": [[2, "asc"], [1, "asc"]],
        "pageLength": 25
    });
});

// Image preview for add form
$('#part_image').on('change', function() {
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
$('#edit_part_image').on('change', function() {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            $('#current_image_preview').html(`<img src="${e.target.result}" class="img-thumbnail" style="max-width: 200px;">`);
        }
        reader.readAsDataURL(file);
    }
});
</script>

<?php
require_once '../includes/footer.php';
?>
