<?php
// Security check - this file should only be included from officer_dashboard.php
if (!defined('INCLUDED_FROM_DASHBOARD') && !isset($_SESSION['username'])) {
    // Direct access not allowed
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
        
        // Redirect to refresh the page and prevent form resubmission
        header("Location: officer_dashboard.php?tab=borrow");
        exit();
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
        
        // Redirect to refresh the page and prevent form resubmission
        header("Location: officer_dashboard.php?tab=borrow");
        exit();
    }
}

// Fetch all items
$sql = "SELECT * FROM inventory";
$result = $conn->query($sql);
$items = $result->fetch_all(MYSQLI_ASSOC);
?>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">‎ ‎ ‎ </h5>
    </div>
    <div class="card-body">
        <form method="POST" action="officer_dashboard.php?tab=borrow">
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
                                <form method="POST" action="officer_dashboard.php?tab=borrow" class="d-inline">
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

<script>
    // Add any borrow-specific JavaScript here
    document.addEventListener('DOMContentLoaded', function() {
        // Validation for quantity input
        const quantityInput = document.getElementById('quantity');
        const itemSelect = document.getElementById('itemSelect');
        
        if (quantityInput && itemSelect) {
            itemSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const availableText = selectedOption.text;
                const qtyMatch = availableText.match(/Qty: (\d+)/);
                
                if (qtyMatch && qtyMatch[1]) {
                    const maxQty = parseInt(qtyMatch[1]);
                    quantityInput.max = maxQty;
                    
                    // Update quantity if it exceeds the available amount
                    if (parseInt(quantityInput.value) > maxQty) {
                        quantityInput.value = maxQty;
                    }
                }
            });
        }
    });
</script>