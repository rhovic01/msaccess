<?php
require 'db_connect.php';

// Check if database connection is successful
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Fetch items that are currently borrowed with student information
$query = "
    SELECT 
        t.id AS transaction_id,
        i.id AS item_id, 
        i.item_name,
        t.student_id,
        t.student_name,
        t.transaction_date
    FROM transactions t
    JOIN inventory i ON i.id = t.item_id
    WHERE t.status = 'borrowed'
    ORDER BY i.item_name, t.student_name
";
$result = mysqli_query($conn, $query);

// Check for query errors
if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

// Fetch all borrowed items
$borrowed_items = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<div id="ReturnItem" class="tabcontent">
    <h2>Return Item</h2>
    <div class="form-container">
        <form method="POST" action="officer_dashboard.php" onsubmit="return validateReturnForm()">
            <select name="transaction_id" id="transaction_id" required>
                <option value="">Select Borrowed Item</option>
                <?php if (!empty($borrowed_items)): ?>
                    <?php foreach ($borrowed_items as $item): ?>
                        <option value="<?php echo htmlspecialchars($item['transaction_id']); ?>"
                                data-item-id="<?php echo htmlspecialchars($item['item_id']); ?>"
                                data-student-id="<?php echo htmlspecialchars($item['student_id']); ?>"
                                data-student-name="<?php echo htmlspecialchars($item['student_name']); ?>">
                            <?php echo htmlspecialchars($item['item_name']); ?> - 
                            Borrowed by: <?php echo htmlspecialchars($item['student_name']); ?> 
                            (ID: <?php echo htmlspecialchars($item['student_id']); ?>)
                        </option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="">No items currently borrowed</option>
                <?php endif; ?>
            </select>
            <input type="hidden" name="item_id" id="item_id">
            <input type="text" name="student_id" id="student_id" placeholder="Student ID" required readonly>
            <input type="text" name="student_name" id="student_name" placeholder="Student Name" required readonly>
            <button type="submit" name="return_item">Return Item</button>
        </form>
    </div>
</div>

<script>
    // Auto-fill student info when item is selected
    document.getElementById('transaction_id').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        document.getElementById('item_id').value = selectedOption.dataset.itemId || '';
        document.getElementById('student_id').value = selectedOption.dataset.studentId || '';
        document.getElementById('student_name').value = selectedOption.dataset.studentName || '';
    });

    function validateReturnForm() {
        const transactionId = document.getElementById('transaction_id').value;
        
        if (!transactionId) {
            alert('Please select a borrowed item to return');
            return false;
        }
        return true;
    }
</script>