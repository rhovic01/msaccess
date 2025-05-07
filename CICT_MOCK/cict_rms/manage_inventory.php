<?php
require 'db_connect.php';

// Handle CRUD operations
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_item'])) {
        // Add new item
        $item_name = trim($_POST['item_name']);
        $item_quantity = (int)$_POST['item_quantity'];
        $item_availability = ($item_quantity > 0) ? 'available' : 'unavailable';

        $sql = "INSERT INTO inventory (item_name, item_quantity, item_availability) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sis", $item_name, $item_quantity, $item_availability);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Item added successfully!";
            $_SESSION['alert_type'] = "success";
        } else {
            $_SESSION['message'] = "Error adding item: " . $stmt->error;
            $_SESSION['alert_type'] = "danger";
        }
        $stmt->close();
        header("Location: admin_dashboard.php");
        exit();
    } elseif (isset($_POST['edit_item'])) {
        // Edit existing item
        $id = (int)$_POST['id'];
        $item_name = trim($_POST['item_name']);
        $item_quantity = (int)$_POST['item_quantity'];
        $item_availability = ($item_quantity > 0) ? 'available' : 'unavailable';

        $sql = "UPDATE inventory SET item_name = ?, item_quantity = ?, item_availability = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sisi", $item_name, $item_quantity, $item_availability, $id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Item updated successfully!";
            $_SESSION['alert_type'] = "success";
        } else {
            $_SESSION['message'] = "Error updating item: " . $stmt->error;
            $_SESSION['alert_type'] = "danger";
        }
        $stmt->close();
        header("Location: admin_dashboard.php");
        exit();
    } elseif (isset($_POST['delete_item'])) {
        // Delete item
        $id = (int)$_POST['id'];

        $sql = "DELETE FROM inventory WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Item deleted successfully!";
            $_SESSION['alert_type'] = "success";
        } else {
            $_SESSION['message'] = "Error deleting item: " . $stmt->error;
            $_SESSION['alert_type'] = "danger";
        }
        $stmt->close();
        header("Location: admin_dashboard.php");
        exit();
    }
}

// Pagination
$limit = 10;
$page = isset($_GET['inv_page']) ? (int)$_GET['inv_page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch total number of items
$sql = "SELECT COUNT(*) AS total FROM inventory";
$result = $conn->query($sql);
$totalItems = $result->fetch_assoc()['total'];
$totalPages = ceil($totalItems / $limit);

// Fetch items for the current page
$sql = "SELECT * FROM inventory LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
$items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Manage Inventory</h5>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
            <i class="fas fa-plus"></i> Add Item
        </button>
    </div>
    <div class="card-body">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['alert_type']; ?> alert-dismissible fade show">
                <?php echo $_SESSION['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['message'], $_SESSION['alert_type']); ?>
        <?php endif; ?>

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
                            <td><?php echo $item['item_name']; ?></td>
                            <td><?php echo $item['item_quantity']; ?></td>
                            <td>
                                <span class="status-badge <?php echo $item['item_availability'] === 'available' ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo ucfirst($item['item_availability']); ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm edit-btn action-btn" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editItemModal"
                                        data-id="<?php echo $item['id']; ?>"
                                        data-name="<?php echo $item['item_name']; ?>"
                                        data-quantity="<?php echo $item['item_quantity']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm delete-btn action-btn" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteItemModal"
                                        data-id="<?php echo $item['id']; ?>"
                                        data-name="<?php echo $item['item_name']; ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <nav aria-label="Page navigation">
    <ul class="pagination justify-content-center">
        <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?tab=inventory&inv_page=<?php echo $page - 1; ?>" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>
        <?php endif; ?>
        
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                <a class="page-link" href="?tab=inventory&inv_page=<?php echo $i; ?>"><?php echo $i; ?></a>
            </li>
        <?php endfor; ?>
        
        <?php if ($page < $totalPages): ?>
            <li class="page-item">
                <a class="page-link" href="?tab=inventory&inv_page=<?php echo $page + 1; ?>" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        <?php endif; ?>
    </ul>
</nav>
    </div>
</div>

<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addItemModalLabel">Add New Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="manage_inventory.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="item_name" class="form-label">Item Name</label>
                        <input type="text" class="form-control" id="item_name" name="item_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="item_quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="item_quantity" name="item_quantity" min="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="add_item" class="btn btn-primary">Add Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Item Modal -->
<div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editItemModalLabel">Edit Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="manage_inventory.php">
                <input type="hidden" name="id" id="edit_item_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_item_name" class="form-label">Item Name</label>
                        <input type="text" class="form-control" id="edit_item_name" name="item_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_item_quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="edit_item_quantity" name="item_quantity" min="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="edit_item" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Item Modal -->
<div class="modal fade" id="deleteItemModal" tabindex="-1" aria-labelledby="deleteItemModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteItemModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="manage_inventory.php">
                <input type="hidden" name="id" id="delete_item_id">
                <div class="modal-body">
                    <p>Are you sure you want to delete <strong id="delete_item_name"></strong>?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_item" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Edit Modal Handler
    document.getElementById('editItemModal').addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const id = button.getAttribute('data-id');
        const name = button.getAttribute('data-name');
        const quantity = button.getAttribute('data-quantity');
        
        document.getElementById('edit_item_id').value = id;
        document.getElementById('edit_item_name').value = name;
        document.getElementById('edit_item_quantity').value = quantity;
    });
    
    // Delete Modal Handler
    document.getElementById('deleteItemModal').addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const id = button.getAttribute('data-id');
        const name = button.getAttribute('data-name');
        
        document.getElementById('delete_item_id').value = id;
        document.getElementById('delete_item_name').textContent = name;
    });
</script>