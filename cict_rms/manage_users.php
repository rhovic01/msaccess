<?php
require 'db_connect.php';

// Handle status change
if (isset($_POST['change_status'])) {
    $userId = (int)$_POST['user_id'];
    $newStatus = $_POST['new_status'];

    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $newStatus, $userId);
    if ($stmt->execute()) {
        $_SESSION['message'] = "User status updated successfully!";
        $_SESSION['alert_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating user status: " . $stmt->error;
        $_SESSION['alert_type'] = "danger";
    }
    $stmt->close();
    header("Location: admin_dashboard.php#ManageUsers");
    exit();
}

// Handle role change
if (isset($_POST['change_role'])) {
    $userId = (int)$_POST['user_id'];
    $newRole = $_POST['new_role'];

    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->bind_param("si", $newRole, $userId);
    if ($stmt->execute()) {
        $_SESSION['message'] = "User role updated successfully!";
        $_SESSION['alert_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating user role: " . $stmt->error;
        $_SESSION['alert_type'] = "danger";
    }
    $stmt->close();
    header("Location: admin_dashboard.php#ManageUsers");
    exit();
}

// Fetch all users
$users = [];
$sql = "SELECT id, username, role, first_name, last_name, email, contact_number, status FROM users ORDER BY role, last_name";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

$conn->close();
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Manage Users</h5>
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
                        <th>Username</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Contact</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo $user['username']; ?></td>
                            <td><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></td>
                            <td><?php echo $user['email']; ?></td>
                            <td><?php echo $user['contact_number']; ?></td>
                            <td>
                                <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'primary' : 'info'; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" id="userActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-cog"></i>
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="userActionsDropdown">
                                        <li>
                                            <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editUserModal" 
                                                data-id="<?php echo $user['id']; ?>"
                                                data-username="<?php echo $user['username']; ?>"
                                                data-firstname="<?php echo $user['first_name']; ?>"
                                                data-lastname="<?php echo $user['last_name']; ?>"
                                                data-email="<?php echo $user['email']; ?>"
                                                data-contact="<?php echo $user['contact_number']; ?>">
                                                <i class="fas fa-edit me-2"></i>Edit
                                            </button>
                                        </li>
                                        <li>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="new_status" value="<?php echo $user['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                                <button type="submit" name="change_status" class="dropdown-item">
                                                    <i class="fas fa-power-off me-2"></i>
                                                    <?php echo $user['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                                </button>
                                            </form>
                                        </li>
                                        <li>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="new_role" value="<?php echo $user['role'] === 'admin' ? 'officer' : 'admin'; ?>">
                                                <button type="submit" name="change_role" class="dropdown-item">
                                                    <i class="fas fa-user-shield me-2"></i>
                                                    Make <?php echo $user['role'] === 'admin' ? 'Officer' : 'Admin'; ?>
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="update_user.php">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="edit_username" name="username" readonly>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_contact" class="form-label">Contact Number</label>
                        <input type="text" class="form-control" id="edit_contact" name="contact_number" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Edit User Modal Handler
    document.getElementById('editUserModal').addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        document.getElementById('edit_user_id').value = button.getAttribute('data-id');
        document.getElementById('edit_username').value = button.getAttribute('data-username');
        document.getElementById('edit_first_name').value = button.getAttribute('data-firstname');
        document.getElementById('edit_last_name').value = button.getAttribute('data-lastname');
        document.getElementById('edit_email').value = button.getAttribute('data-email');
        document.getElementById('edit_contact').value = button.getAttribute('data-contact');
    });
</script>