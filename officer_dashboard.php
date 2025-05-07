<?php
session_start();
require 'db_connect.php';

// Enhanced security check
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'officer') {
    header("Location: login.php");
    exit();
}

// Handle updating item availability
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_availability'])) {
    $item_id = filter_input(INPUT_POST, 'item_id', FILTER_SANITIZE_NUMBER_INT);
    $new_availability = filter_input(INPUT_POST, 'new_availability', FILTER_SANITIZE_STRING);
    
    if ($item_id && in_array($new_availability, ['available', 'unavailable'])) {
        $sql = "UPDATE inventory SET item_availability = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $new_availability, $item_id);
        
        if ($stmt->execute()) {
            $_SESSION['flash_message'] = "Item availability updated successfully!";
            $_SESSION['flash_type'] = "success";
        } else {
            $_SESSION['flash_message'] = "Error updating availability: " . $stmt->error;
            $_SESSION['flash_type'] = "danger";
        }
        $stmt->close();
    }
}

// Handle borrowing items
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['borrow_item'])) {
    $item_id = filter_input(INPUT_POST, 'item_id', FILTER_SANITIZE_NUMBER_INT);
    $student_id = filter_input(INPUT_POST, 'student_id', FILTER_SANITIZE_STRING);
    $student_name = filter_input(INPUT_POST, 'student_name', FILTER_SANITIZE_STRING);
    $student_section = filter_input(INPUT_POST, 'student_section', FILTER_SANITIZE_STRING);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    $verified_by = $_SESSION['username'];

    // Calculate due date - set as 5:00 PM today or tomorrow if after 5 PM
    $now = new DateTime();
    $due_date = new DateTime();
    $due_date->setTime(17, 0, 0); // 5:00 PM
    
    if ($now->format('H:i') >= '17:00') {
        $due_date->modify('+1 day');
    }
    
    $due_date_formatted = $due_date->format('Y-m-d H:i:s');

    if ($item_id && $student_id && $student_name && $student_section && $quantity > 0) {
        $conn->begin_transaction();
        
        try {
            // Check item availability first
            $check_sql = "SELECT item_quantity, item_availability FROM inventory WHERE id = ? FOR UPDATE";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("i", $item_id);
            $check_stmt->execute();
            $item = $check_stmt->get_result()->fetch_assoc();
            $check_stmt->close();
            
            if ($item && $item['item_quantity'] >= $quantity) {
                // Insert the borrow transaction
                $sql = "INSERT INTO transactions (item_id, student_id, student_name, section, quantity, transaction_type, verified_by, status, due_date) 
                        VALUES (?, ?, ?, ?, ?, 'borrowed', ?, 'borrowed', ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isssiss", $item_id, $student_id, $student_name, $student_section, $quantity, $verified_by, $due_date_formatted);
                $stmt->execute();
                $stmt->close();

                // Update item availability
                $update_sql = "UPDATE inventory SET 
                                item_quantity = item_quantity - ?, 
                                item_availability = IF(item_quantity - ? <= 0, 'unavailable', 'available') 
                                WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("iii", $quantity, $quantity, $item_id);
                $update_stmt->execute();
                $update_stmt->close();

                $conn->commit();
                $_SESSION['flash_message'] = "Item borrowed successfully!";
                $_SESSION['flash_type'] = "success";
            } else {
                $conn->rollback();
                $_SESSION['flash_message'] = "Item is not available in sufficient quantity for borrowing.";
                $_SESSION['flash_type'] = "danger";
            }
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['flash_message'] = "Error processing borrow: " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
        }
    }
}

// Handle returning items
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['return_item'])) {
    $transaction_id = filter_input(INPUT_POST, 'transaction_id', FILTER_SANITIZE_NUMBER_INT);
    $item_id = filter_input(INPUT_POST, 'item_id', FILTER_SANITIZE_NUMBER_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    $verified_by = $_SESSION['username'];

    if ($transaction_id && $item_id && $quantity > 0) {
        $conn->begin_transaction();

        try {
            // Verify the transaction exists and is still borrowed
            $sql = "SELECT id, quantity FROM transactions 
                    WHERE id = ? AND status = 'borrowed' 
                    LIMIT 1 FOR UPDATE";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $transaction_id);
            $stmt->execute();
            $transaction = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($transaction) {
                // Update the transaction to 'returned'
                $sql = "UPDATE transactions SET 
                        status = 'returned',
                        transaction_type = 'borrowed',
                        verified_by = ?
                        WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $verified_by, $transaction_id);
                $stmt->execute();
                $stmt->close();

                // Update item availability and quantity
                $sql = "UPDATE inventory SET 
                        item_quantity = item_quantity + ?, 
                        item_availability = 'available'
                        WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $transaction['quantity'], $item_id);
                $stmt->execute();
                $stmt->close();

                $conn->commit();
                $_SESSION['flash_message'] = "Item successfully returned.";
                $_SESSION['flash_type'] = "success";
            } else {
                $conn->rollback();
                $_SESSION['flash_message'] = "Transaction not found or already returned.";
                $_SESSION['flash_type'] = "danger";
            }
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['flash_message'] = "Error processing return: " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
        }
    }
}

// Search functionality for borrowed items
$search_query = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_query = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING);
}

// Fetch all items
$sql = "SELECT * FROM inventory";
$result = $conn->query($sql);
$items = $result->fetch_all(MYSQLI_ASSOC);

// Fetch all transactions (paginated)
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build the query based on search parameter
$search_condition = '';
$count_params = [];
$query_params = [];

if (!empty($search_query)) {
    $search_condition = " WHERE t.student_name LIKE ? ";
    $search_param = "%{$search_query}%";
    $count_params[] = $search_param;
    $query_params[] = $search_param;
}

// Count total items for pagination
$count_sql = "SELECT COUNT(*) AS total FROM transactions t" . $search_condition;
if (!empty($count_params)) {
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param(str_repeat('s', count($count_params)), ...$count_params);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
} else {
    $count_result = $conn->query($count_sql);
}

$total_items = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_items / $limit);

// Fetch transactions with search filter
$sql = "SELECT t.*, i.item_name 
        FROM transactions t
        LEFT JOIN inventory i ON t.item_id = i.id
        $search_condition
        ORDER BY t.transaction_date DESC
        LIMIT ? OFFSET ?";

$types = str_repeat('s', count($query_params)) . 'ii';
$query_params[] = $limit;
$query_params[] = $offset;

$stmt = $conn->prepare($sql);
if (!empty($query_params)) {
    $stmt->bind_param($types, ...$query_params);
}
$stmt->execute();
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch currently borrowed items for the return tab
$borrowed_sql = "SELECT t.*, i.item_name 
                FROM transactions t
                LEFT JOIN inventory i ON t.item_id = i.id
                WHERE t.status = 'borrowed'
                ORDER BY t.transaction_date DESC";
$borrowed_result = $conn->query($borrowed_sql);
$borrowed_items = $borrowed_result->fetch_all(MYSQLI_ASSOC);

$conn->close();
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
                <button class="nav-link active" id="borrow-tab" data-bs-toggle="tab" data-bs-target="#borrow" type="button" role="tab">
                    <i class="fas fa-hand-holding me-2"></i>Borrow Item
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="return-tab" data-bs-toggle="tab" data-bs-target="#return" type="button" role="tab">
                    <i class="fas fa-undo me-2"></i>Return Item
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab">
                    <i class="fas fa-history me-2"></i>Transaction History
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="officerTabsContent">
            <!-- Borrow Item Tab -->
            <div class="tab-pane fade show active" id="borrow" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Borrow Item</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="itemSelect" class="form-label">Select Item</label>
                                    <select class="form-select" id="itemSelect" name="item_id" required>
                                        <option value="" selected disabled>Select an item</option>
                                        <?php foreach ($items as $item): ?>
                                            <?php if ($item['item_availability'] === 'available'): ?>
                                                <option value="<?php echo $item['id']; ?>">
                                                    <?php echo htmlspecialchars($item['item_name']); ?> 
                                                    (Qty: <?php echo $item['item_quantity']; ?>)
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="quantity" class="form-label">Quantity</label>
                                    <input type="number" class="form-control" id="quantity" name="quantity" min="1" value="1" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="studentId" class="form-label">Student ID</label>
                                    <input type="text" class="form-control" id="studentId" name="student_id" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="studentName" class="form-label">Student Name</label>
                                    <input type="text" class="form-control" id="studentName" name="student_name" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="studentSection" class="form-label">Section</label>
                                    <input type="text" class="form-control" id="studentSection" name="student_section" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <button type="submit" name="borrow_item" class="btn btn-primary float-end">
                                        <i class="fas fa-hand-holding me-2"></i>Process Borrow
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Available Inventory</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Item Name</th>
                                        <th>Quantity</th>
                                        <th>Availability</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                        <tr>
                                            <td><?php echo $item['id']; ?></td>
                                            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                            <td><?php echo $item['item_quantity']; ?></td>
                                            <td>
                                                <span class="status-badge <?php echo $item['item_availability'] === 'available' ? 'status-available' : 'status-unavailable'; ?>">
                                                    <?php echo ucfirst($item['item_availability']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                    <input type="hidden" name="new_availability" value="<?php echo $item['item_availability'] === 'available' ? 'unavailable' : 'available'; ?>">
                                                    <button type="submit" name="update_availability" class="btn btn-sm btn-<?php echo $item['item_availability'] === 'available' ? 'warning' : 'success'; ?>">
                                                        <?php echo $item['item_availability'] === 'available' ? 'Mark Unavailable' : 'Mark Available'; ?>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Return Item Tab -->
            <div class="tab-pane fade" id="return" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Borrowed Items</h5>
                    </div>
                    <div class="card-body">
                        <!-- Search Form -->
                        <form class="mb-4">
                            <div class="input-group search-bar ms-auto">
                                <input type="text" class="form-control" placeholder="Search by borrower name" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                                <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                            </div>
                        </form>
                    
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Borrower Name</th>
                                        <th>Item Name</th>
                                        <th>Quantity</th>
                                        <th>Date Borrowed</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $current_time = new DateTime();
                                    foreach ($borrowed_items as $item): 
                                        $due_date = new DateTime($item['due_date']);
                                        $is_overdue = $current_time > $due_date;
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td><?php echo date('M d, Y h:i A', strtotime($item['transaction_date'])); ?></td>
                                            <td class="<?php echo $is_overdue ? 'due-date-passed' : ''; ?>">
                                                <?php echo date('M d, Y h:i A', strtotime($item['due_date'])); ?>
                                                <?php if($is_overdue): ?>
                                                    <span class="badge bg-danger">Overdue</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="status-badge status-borrowed">
                                                    Borrowed
                                                </span>
                                            </td>
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="transaction_id" value="<?php echo $item['id']; ?>">
                                                    <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                                    <input type="hidden" name="quantity" value="<?php echo $item['quantity']; ?>">
                                                    <button type="submit" name="return_item" class="btn btn-sm btn-success">
                                                        <i class="fas fa-undo me-1"></i> Return
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (count($borrowed_items) == 0): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No borrowed items found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transaction History Tab -->
            <div class="tab-pane fade" id="history" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Transaction History</h5>
                    </div>
                    <div class="card-body">
                        <!-- Search Form -->
                        <form class="mb-4">
                            <input type="hidden" name="tab" value="history">
                            <div class="input-group search-bar ms-auto">
                                <input type="text" class="form-control" placeholder="Search by borrower name" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                                <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                            </div>
                        </form>
                        
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Item</th>
                                        <th>Student</th>
                                        <th>Section</th>
                                        <th>Quantity</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Date Borrowed</th>
                                        <th>Due Date</th>
                                        <th>Verified By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $transaction): 
                                        $due_date = new DateTime($transaction['due_date']);
                                        $is_overdue = $current_time > $due_date && $transaction['status'] === 'borrowed';
                                    ?>
                                        <tr>
                                            <td><?php echo $transaction['id']; ?></td>
                                            <td><?php echo htmlspecialchars($transaction['item_name']); ?></td>
                                            <td><?php echo htmlspecialchars($transaction['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($transaction['section']); ?></td>
                                            <td><?php echo $transaction['quantity']; ?></td>
                                            <td><?php echo ucfirst($transaction['transaction_type']); ?></td>
                                            <td>
                                                <span class="status-badge 
                                                    <?php echo $transaction['status'] === 'borrowed' ? 'status-borrowed' : 
                                                          ($transaction['status'] === 'returned' ? 'status-returned' : ''); ?>">
                                                    <?php echo ucfirst($transaction['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y h:i A', strtotime($transaction['transaction_date'])); ?></td>
                                            <td class="<?php echo $is_overdue ? 'due-date-passed' : ''; ?>">
                                                <?php echo date('M d, Y h:i A', strtotime($transaction['due_date'])); ?>
                                                <?php if($is_overdue): ?>
                                                    <span class="badge bg-danger">Overdue</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($transaction['verified_by']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (count($transactions) == 0): ?>
                                        <tr>
                                            <td colspan="10" class="text-center">No transactions found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?tab=history&page=<?php echo $page - 1; ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?tab=history&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?tab=history&page=<?php echo $page + 1; ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set the item ID when selecting a transaction for return
        document.getElementById('transactionSelect').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            document.getElementById('returnItemId').value = selectedOption.dataset.itemId;
        });
        
        // Initialize the first tab as active
        document.addEventListener('DOMContentLoaded', function() {
            const firstTab = new bootstrap.Tab(document.getElementById('borrow-tab'));
            firstTab.show();
        });
        // Handle tab switching based on URL parameter
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('tab');
            
            // Map URL tab parameters to tab IDs
            const tabMap = {
                'borrow': 'borrow-tab',
                'return': 'return-tab',
                'history': 'history-tab'
            };
            
            // Determine which tab to show
            let tabToShow = 'borrow-tab'; // Default tab
            
            if (activeTab && tabMap[activeTab]) {
                tabToShow = tabMap[activeTab];
            }
            
            // Show the correct tab
            const tabTrigger = document.getElementById(tabToShow);
            if (tabTrigger) {
                const tab = new bootstrap.Tab(tabTrigger);
                tab.show();
                
                // Update the URL to maintain state if it wasn't already set
                if (!activeTab && tabToShow === 'history-tab') {
                    const newUrl = new URL(window.location.href);
                    newUrl.searchParams.set('tab', 'history');
                    window.history.replaceState({}, '', newUrl);
                }
            }
        });

        // Update URL when tabs are clicked manually
        document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
            tab.addEventListener('click', function() {
                const tabId = this.id;
                const tabMap = {
                    'borrow-tab': 'borrow',
                    'return-tab': 'return',
                    'history-tab': 'history'
                };
                
                if (tabMap[tabId]) {
                    const newUrl = new URL(window.location.href);
                    newUrl.searchParams.set('tab', tabMap[tabId]);
                    window.history.pushState({}, '', newUrl);
                }
            });
        });
        document.getElementById('viewTransactionModal').addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const transactionId = button.getAttribute('data-id');
        
        // Load transaction details via AJAX
        fetch(`get_transaction_details.php?id=${transactionId}`)
            .then(response => response.text())
            .then(data => {
                document.getElementById('transactionDetails').innerHTML = data;
            });
    });
    </script>
</body>
</html>