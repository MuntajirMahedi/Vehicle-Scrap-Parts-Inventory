<?php
require_once '../config/database.php';
require_once '../includes/header.php';
// This line appears to be an unintended 'or' statement and should be removed
// Get current admin's information
$admin_id = $_SESSION['admin_id'];
$admin_query = "SELECT * FROM tbl_admin WHERE admin_id = ?";
$stmt = $conn->prepare($admin_query);
$stmt->bind_param('i', $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $success = false;
        $error = '';

        switch ($_POST['action']) {
            case 'update_profile':
                $fname = mysqli_real_escape_string($conn, $_POST['admin_fname']);
                $lname = mysqli_real_escape_string($conn, $_POST['admin_lname']);
                $email = mysqli_real_escape_string($conn, $_POST['admin_email']);
                $phone = mysqli_real_escape_string($conn, $_POST['admin_phone']);

                // Check if email exists for other admins
                $check_query = "SELECT COUNT(*) as count FROM tbl_admin WHERE admin_email = ? AND admin_id != ?";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bind_param('si', $email, $admin_id);
                $check_stmt->execute();
                if ($check_stmt->get_result()->fetch_assoc()['count'] > 0) {
                    $error = "Email already exists.";
                    break;
                }

                // Update admin information
                $update_query = "UPDATE tbl_admin SET
                    admin_fname = ?,
                    admin_lname = ?,
                    admin_email = ?,
                    admin_phone = ?
                    WHERE admin_id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param('ssssi', $fname, $lname, $email, $phone, $admin_id);

                if ($update_stmt->execute()) {
                    $_SESSION['admin_name'] = $fname . ' ' . $lname;
                    $_SESSION['admin_email'] = $email;
                    $success = true;
                } else {
                    $error = "Failed to update profile.";
                }
                break;

            case 'change_password':
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];

                // Verify current password
                if (!password_verify($current_password, $admin['admin_password'])) {
                    $error = "Current password is incorrect.";
                    break;
                }

                // Verify new passwords match
                if ($new_password !== $confirm_password) {
                    $error = "New passwords do not match.";
                    break;
                }

                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $password_query = "UPDATE tbl_admin SET admin_password = ? WHERE admin_id = ?";
                $password_stmt = $conn->prepare($password_query);
                $password_stmt->bind_param('si', $hashed_password, $admin_id);

                if ($password_stmt->execute()) {
                    $success = true;
                    $_SESSION['success'] = "Password has been updated successfully!";
                    header('Location: profile.php');
                    exit();
                } else {
                    $error = "Failed to update password.";
                }
                break;
        }

        // Return JSON response for AJAX requests
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            echo json_encode(['success' => $success, 'error' => $error]);
            exit;
        }
    }
}
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Profile Management</h1>
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

<!-- Profile Content -->
<div class="row">
    <!-- Profile Information -->
    <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Profile Information</h6>
            </div>
            <div class="card-body">
                <form action="profile.php" method="POST">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="form-group">
                        <label for="admin_fname">First Name</label>
                        <input type="text" class="form-control" id="admin_fname" name="admin_fname"
                               value="<?php echo htmlspecialchars($admin['admin_fname']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="admin_lname">Last Name</label>
                        <input type="text" class="form-control" id="admin_lname" name="admin_lname"
                               value="<?php echo htmlspecialchars($admin['admin_lname']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="admin_email">Email Address</label>
                        <input type="email" class="form-control" id="admin_email" name="admin_email"
                               value="<?php echo htmlspecialchars($admin['admin_email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="admin_phone">Phone Number</label>
                        <input type="text" class="form-control" id="admin_phone" name="admin_phone"
                               value="<?php echo htmlspecialchars($admin['admin_phone']); ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        Update Profile
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Change Password -->
    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Change Password</h6>
            </div>
            <div class="card-body">
                <form action="profile.php" method="POST">
                    <input type="hidden" name="action" value="change_password">

                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" class="form-control" id="current_password"
                               name="current_password" required>
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" class="form-control" id="new_password"
                               name="new_password" required>
                        <small class="form-text text-muted">
                            Password must be at least 6 characters long.
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password"
                               name="confirm_password" required>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        Change Password
                    </button>
                </form>
            </div>
        </div>

        <!-- Last Login Info -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Account Information</h6>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <strong>Last Login:</strong><br>
                    <?php echo $admin['admin_last_login_date'] ? date('d M Y H:i:s', strtotime($admin['admin_last_login_date'])) : 'Never'; ?>
                </div>
                <div class="mb-2">
                    <strong>Account Created:</strong><br>
                    <?php echo date('d M Y', strtotime($admin['admin_created_date'])); ?>
                </div>
                <div>
                    <strong>Account Status:</strong><br>
                    <span class="badge badge-<?php echo $admin['admin_status'] === 'active' ? 'success' : 'danger'; ?>">
                        <?php echo ucfirst($admin['admin_status']); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Profile image preview
document.getElementById('profileImage').addEventListener('change', function(e) {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profilePreview').src = e.target.result;
        }
        reader.readAsDataURL(file);
    }
});

// Password validation
document.querySelector('form[action="profile.php"]').addEventListener('submit', function(e) {
    if (this.querySelector('input[name="action"]').value === 'change_password') {
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;

        if (newPassword.length < 6) {
            e.preventDefault();
            alert('New password must be at least 6 characters long.');
            return;
        }

        if (newPassword !== confirmPassword) {
            e.preventDefault();
            alert('New passwords do not match.');
            return;
        }
    }
});
</script>

<?php
require_once '../includes/footer.php';
?>
