<?php
require 'db_connect.php';

// Check if the database is connected
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Fetch only available items
$query = "SELECT * FROM inventory WHERE LOWER(item_availability) = 'available'";
$result = mysqli_query($conn, $query);

// Check if query execution was successful
if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

// Fetch all items
$items = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<div id="BorrowItem" class="tabcontent">
    <h2>Borrow Item</h2>
    <div class="form-container">
        <form method="POST" action="officer_dashboard.php">
            <select name="item_id" required>
                <option value="">Select Item</option>
                <?php if (!empty($items)): ?>
                    <?php foreach ($items as $item): ?>
                        <option value="<?php echo $item['id']; ?>">
                            <?php echo $item['item_name']; ?> (Qty: <?php echo $item['item_quantity']; ?>)
                        </option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="">No available items</option>
                <?php endif; ?>
            </select>
            <input type="text" name="student_id" placeholder="Student ID" required>
            <input type="text" name="student_name" placeholder="Student Name" required>
            <button type="submit" name="borrow_item">Borrow Item</button>
        </form>
    </div>
</div>
