<?php
session_start();
ob_start();
require 'db_connect.php';

// Enhanced security check
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'officer') {
    header("Location: login.php");
    exit();
}

// Determine which tab is active
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'borrow';

// Set tab file based on active tab
$tab_file = '';
switch ($active_tab) {
    case 'return':
        $tab_file = 'return_item.php';
        break;
    case 'history':
        $tab_file = 'officer_transaction_history.php';
        break;
    default:
        $tab_file = 'borrow_item.php';
        break;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officer Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #6A11CB;
            --secondary-color: #2575FC;
            --dark-color: #2C2C2C;
            --light-color: #f8f9fa;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f5f5;
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .nav-tabs .nav-link {
            color: var(--dark-color);
            font-weight: 500;
            border: none;
            padding: 12px 20px;
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            background-color: white;
            border-bottom: 3px solid var(--primary-color);
        }
        
        .nav-tabs .nav-link:hover:not(.active) {
            color: var(--primary-color);
            background-color: rgba(106, 17, 203, 0.1);
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .card-header {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        
        .btn-primary {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            border: none;
        }
        
        .btn-primary:hover {
            opacity: 0.9;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-available {
            background-color: rgba(40, 167, 69, 0.2);
            color: #28a745;
        }
        
        .status-borrowed {
            background-color: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }
        
        .status-returned {
            background-color: rgba(23, 162, 184, 0.2);
            color: #17a2b8;
        }
        
        .status-unavailable {
            background-color: rgba(220, 53, 69, 0.2);
            color: #dc3545;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .search-bar {
            max-width: 400px;
            margin-bottom: 20px;
        }
        
        .due-date-alert {
            font-weight: bold;
        }
        
        .due-date-passed {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="user-avatar me-3">
                        <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                    </div>
                    <h4 class="mb-0">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></h4>
                </div>
                <a href="logout.php" class="btn btn-outline-light">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Flash Messages -->
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show">
                <?php echo $_SESSION['flash_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="officerTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link <?php echo $active_tab == 'borrow' ? 'active' : ''; ?>" href="officer_dashboard.php?tab=borrow">
                    <i class="fas fa-hand-holding me-2"></i>Borrow Item
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?php echo $active_tab == 'return' ? 'active' : ''; ?>" href="officer_dashboard.php?tab=return">
                    <i class="fas fa-undo me-2"></i>Return Item
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?php echo $active_tab == 'history' ? 'active' : ''; ?>" href="officer_dashboard.php?tab=history">
                    <i class="fas fa-history me-2"></i>Transaction History
                </a>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="officerTabsContent">
            <!-- Include the active tab file -->
            <?php include $tab_file; ?>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php ob_end_flush();