<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Scrap Parts Inventory - Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- Bootstrap 4 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap 4 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="p-3">
                <h4 class="text-white">Auto Scrap Parts</h4>
                <hr class="bg-light">
            </div>
            <nav class="nav flex-column">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">
                    <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                </a>
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'brands.php' ? 'active' : ''; ?>" href="brands.php">
                    <i class="fas fa-trademark mr-2"></i> Brands
                </a>
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'models.php' ? 'active' : ''; ?>" href="models.php">
                    <i class="fas fa-car mr-2"></i> Models
                </a>
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>" href="categories.php">
                    <i class="fas fa-list mr-2"></i> Categories
                </a>
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'warehouses.php' ? 'active' : ''; ?>" href="warehouses.php">
                    <i class="fas fa-warehouse mr-2"></i> Warehouses
                </a>
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'parts.php' ? 'active' : ''; ?>" href="parts.php">
                    <i class="fas fa-cogs mr-2"></i> Parts
                </a>
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'cars.php' ? 'active' : ''; ?>" href="cars.php">
                    <i class="fas fa-car-side mr-2"></i> Cars
                </a>
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'stock.php' ? 'active' : ''; ?>" href="stock.php">
                    <i class="fas fa-boxes mr-2"></i> Stock
                </a>
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                    <i class="fas fa-chart-bar mr-2"></i> Reports
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-grow-1">
            <!-- Top Navigation -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
                <div class="container-fluid">
                    <!-- <button class="btn btn-link sidebar-toggle">
                        <i class="fas fa-bars"></i>
                    </button> -->
                    <div class="ml-auto d-flex align-items-center">
                        <div class="dropdown">
                            <button class="btn btn-link dropdown-toggle" type="button" id="userDropdown" data-toggle="dropdown">
                                <i class="fas fa-user-circle mr-2"></i><?php echo htmlspecialchars($_SESSION['admin_name']); ?>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right">
                                <a class="dropdown-item" href="profile.php">Profile</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="../auth/logout.php">Logout</a>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Content -->
            <div class="container-fluid py-4">
