<?php
session_start();
require_once '../config/database.php';

// Initialize response array
$response = array(
    'status' => 'error',
    'message' => 'An error occurred.',
    'redirect' => ''
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // Updated SQL query to match tbl_admin column names
    $query = "SELECT * FROM tbl_admin WHERE admin_email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();

        if (password_verify($password, $admin['admin_password'])) {
            // Update last login date
            $update_query = "UPDATE tbl_admin SET admin_last_login_date = NOW() WHERE admin_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param('i', $admin['admin_id']);
            $update_stmt->execute();

            // Set session variables using correct column names
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_name'] = $admin['admin_fname'] . ' ' . $admin['admin_lname'];
            $_SESSION['admin_email'] = $admin['admin_email'];

            // Return success response for AJAX
            echo json_encode(['status' => 'success', 'message' => 'Login successful']);
            exit();
        }
    }

    // Return error response for AJAX
    echo json_encode(['status' => 'error', 'message' => 'Invalid email or password']);
    exit();
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
