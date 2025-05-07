    <?php
    // Security check - this file should only be included from officer_dashboard.php
    if (!defined('INCLUDED_FROM_DASHBOARD') && !isset($_SESSION['username'])) {
        // Direct access not allowed
        header("Location: login.php");
        exit();
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
            
            // Redirect to refresh the page and prevent form resubmission
            header("Location: officer_dashboard.php?tab=return");
            exit();
        }
    }

    // Search functionality for borrowed items
    $search_query = '';
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search_query = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING);
    }

    // Build the query based on search parameter
    $search_condition = '';
    $query_params = [];

    if (!empty($search_query)) {
        $search_condition = " AND t.student_name LIKE ? ";
        $query_params[] = "%{$search_query}%";
    }

    // Fetch currently borrowed items
    $borrowed_sql = "SELECT t.*, i.item_name 
                    FROM transactions t
                    LEFT JOIN inventory i ON t.item_id = i.id
                    WHERE t.status = 'borrowed'
                    $search_condition
                    ORDER BY t.transaction_date DESC";

    if (!empty($query_params)) {
        $stmt = $conn->prepare($borrowed_sql);
        $stmt->bind_param(str_repeat('s', count($query_params)), ...$query_params);
        $stmt->execute();
        $borrowed_result = $stmt->get_result();
        $borrowed_items = $borrowed_result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        $borrowed_result = $conn->query($borrowed_sql);
        $borrowed_items = $borrowed_result->fetch_all(MYSQLI_ASSOC);
    }
    ?>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">‎ ‎ ‎ </h5>
        </div>
        <div class="card-body">
            <!-- Search Form -->
            <form class="mb-4" method="GET" action="officer_dashboard.php">
                <input type="hidden" name="tab" value="return">
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
                                    <form method="POST" action="officer_dashboard.php?tab=return" class="d-inline">
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

    <script>
        // Add any return-specific JavaScript here
        document.addEventListener('DOMContentLoaded', function() {
            // Confirmation before returning items
            const returnForms = document.querySelectorAll('form[name="return_item"]');
            returnForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    if (!confirm('Are you sure you want to mark this item as returned?')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>