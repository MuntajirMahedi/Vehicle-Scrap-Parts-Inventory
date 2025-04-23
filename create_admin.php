<?php
require_once 'config/database.php';

// Admin user details
$admin = [
    'fname' => 'Admin',
    'lname' => 'User',
    'email' => 'admin@example.com',
    'phone' => '1234567890',
    'password' => password_hash('admin123', PASSWORD_DEFAULT)
];

// Check if admin already exists
$check_query = "SELECT admin_id FROM tbl_admin WHERE admin_email = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param('s', $admin['email']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Insert admin user
    $insert_query = "INSERT INTO tbl_admin (admin_fname, admin_lname, admin_email, admin_phone, admin_password)
                     VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param('sssss',
        $admin['fname'],
        $admin['lname'],
        $admin['email'],
        $admin['phone'],
        $admin['password']
    );

    if ($stmt->execute()) {
        echo "Admin user created successfully!\n";
        echo "Email: " . $admin['email'] . "\n";
        echo "Password: admin123\n";
    } else {
        echo "Error creating admin user: " . $conn->error . "\n";
    }
} else {
    echo "Admin user already exists!\n";
}

$stmt->close();
$conn->close();
?>
