<?php
require_once '../config/database.php';
require_once '../includes/header.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = mysqli_real_escape_string($conn, $_POST['category_name']);
                $status = mysqli_real_escape_string($conn, $_POST['category_status']);

                // Handle image upload
                $image = '';
                if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] === 0) {
                    $target_dir = "../uploads/categories/";
                    if (!file_exists($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }

                    $file_extension = strtolower(pathinfo($_FILES['category_image']['name'], PATHINFO_EXTENSION));
                    $file_name = uniqid() . '.' . $file_extension;
                    $target_file = $target_dir . $file_name;

                    if (move_uploaded_file($_FILES['category_image']['tmp_name'], $target_file)) {
                        $image = 'uploads/categories/' . $file_name;
                    }
                }

                $query = "INSERT INTO tbl_category (category_name, category_image, category_status) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('sss', $name, $image, $status);
                $stmt->execute();
                break;

            case 'edit':
                $id = mysqli_real_escape_string($conn, $_POST['category_id']);
                $name = mysqli_real_escape_string($conn, $_POST['category_name']);
                $status = mysqli_real_escape_string($conn, $_POST['category_status']);

                // Handle image upload
                $image_query = '';
                if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] === 0) {
                    $target_dir = "../uploads/categories/";
                    if (!file_exists($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }

                    $file_extension = strtolower(pathinfo($_FILES['category_image']['name'], PATHINFO_EXTENSION));
                    $file_name = uniqid() . '.' . $file_extension;
                    $target_file = $target_dir . $file_name;

                    if (move_uploaded_file($_FILES['category_image']['tmp_name'], $target_file)) {
                        $image = 'uploads/categories/' . $file_name;
                        $image_query = ", category_image = '$image'";
                    }
                }

                $query = "UPDATE tbl_category SET category_name = ?, category_status = ? $image_query WHERE category_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('ssi', $name, $status, $id);
                $stmt->execute();
                break;

            case 'delete':
                $id = mysqli_real_escape_string($conn, $_POST['category_id']);

                // Check if category has parts
                $check_query = "SELECT COUNT(*) as count FROM tbl_part WHERE part_category_id = ?";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bind_param('i', $id);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                $count = $result->fetch_assoc()['count'];

                if ($count > 0) {
                    $_SESSION['error'] = "Cannot delete category. It has associated parts.";
                } else {
                    $query = "DELETE FROM tbl_category WHERE category_id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param('i', $id);
                    $stmt->execute();
                }
                break;
        }

        // Redirect to prevent form resubmission
        header('Location: categories.php');
        exit();
    }
}

// Get all categories with parts count
$categories = $conn->query("
    SELECT c.*, COUNT(p.part_id) as parts_count
    FROM tbl_category c
    LEFT JOIN tbl_part p ON c.category_id = p.part_category_id
    GROUP BY c.category_id
    ORDER BY c.category_name
");
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Manage Categories</h1>
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addCategoryModal">
        <i class="fas fa-plus mr-2"></i>Add New Category
    </button>
</div>

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

<!-- Categories List -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped" id="categoriesTable">
                <thead>
                    <tr>
                        <th>Icon</th>
                        <th>Category Name</th>
                        <th>Parts Count</th>
                        <th>Status</th>
                        <th>Created Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($category = $categories->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?php if ($category['category_image']): ?>
                                <img src="../<?php echo htmlspecialchars($category['category_image']); ?>"
                                     alt="<?php echo htmlspecialchars($category['category_name']); ?>"
                                     class="img-thumbnail"
                                     style="max-width: 50px;">
                            <?php else: ?>
                                <div class="text-muted">No image</div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                        <td>
                            <span class="badge badge-info">
                                <?php echo $category['parts_count']; ?> parts
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $category['category_status'] === 'active' ? 'success' : 'danger'; ?>">
                                <?php echo ucfirst($category['category_status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('d M Y', strtotime($category['category_created_date'])); ?></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-info edit-category"
                                    data-id="<?php echo $category['category_id']; ?>"
                                    data-name="<?php echo htmlspecialchars($category['category_name']); ?>"
                                    data-status="<?php echo $category['category_status']; ?>"
                                    data-toggle="modal" data-target="#editCategoryModal">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger delete-category"
                                    data-id="<?php echo $category['category_id']; ?>"
                                    data-name="<?php echo htmlspecialchars($category['category_name']); ?>"
                                    data-parts="<?php echo $category['parts_count']; ?>">
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

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Category</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="categories.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label for="category_name">Category Name</label>
                        <input type="text" class="form-control" id="category_name" name="category_name" required>
                    </div>
                    <div class="form-group">
                        <label for="category_image">Category Icon</label>
                        <input type="file" class="form-control-file" id="category_image" name="category_image" accept="image/*">
                        <small class="form-text text-muted">Recommended size: 100x100 pixels</small>
                    </div>
                    <div class="form-group">
                        <label for="category_status">Status</label>
                        <select class="form-control" id="category_status" name="category_status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Category</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="categories.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="category_id" id="edit_category_id">
                    <div class="form-group">
                        <label for="edit_category_name">Category Name</label>
                        <input type="text" class="form-control" id="edit_category_name" name="category_name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_category_image">Category Icon</label>
                        <input type="file" class="form-control-file" id="edit_category_image" name="category_image" accept="image/*">
                        <small class="form-text text-muted">Leave empty to keep current image</small>
                    </div>
                    <div class="form-group">
                        <label for="edit_category_status">Status</label>
                        <select class="form-control" id="edit_category_status" name="category_status">
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

<!-- Delete Category Form (Hidden) -->
<form id="deleteCategoryForm" action="categories.php" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="category_id" id="delete_category_id">
</form>

<script>
// Edit category
$('.edit-category').on('click', function() {
    const id = $(this).data('id');
    const name = $(this).data('name');
    const status = $(this).data('status');

    $('#edit_category_id').val(id);
    $('#edit_category_name').val(name);
    $('#edit_category_status').val(status);
});

// Delete category
$('.delete-category').on('click', function() {
    const id = $(this).data('id');
    const name = $(this).data('name');
    const parts = $(this).data('parts');

    if (parts > 0) {
        alert(`Cannot delete category "${name}". It has ${parts} associated parts.`);
        return;
    }

    if (confirm(`Are you sure you want to delete the category "${name}"?`)) {
        $('#delete_category_id').val(id);
        $('#deleteCategoryForm').submit();
    }
});

// Initialize DataTable
$(document).ready(function() {
    $('#categoriesTable').DataTable({
        "order": [[1, "asc"]],
        "pageLength": 25
    });
});
</script>

<?php
require_once '../includes/footer.php';
?>
