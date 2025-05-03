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
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Manage Users</h5>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAdminModal">
            <i class="fas fa-plus me-2"></i>Add Admin
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

        <div class="row">
            <div class="col-md-8">
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
    </div>
</div>

<!-- Add Admin Modal -->
<div class="modal fade" id="addAdminModal" tabindex="-1" aria-labelledby="addAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addAdminModalLabel">Add New Admin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="register_process.php" method="POST" onsubmit="return validateAdminForm()">
                <input type="hidden" name="role" value="admin">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="admin_first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="admin_first_name" name="first_name" placeholder="First Name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="admin_last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="admin_last_name" name="last_name" placeholder="Last Name" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="admin_username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="admin_username" name="username" placeholder="Username" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="admin_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="admin_email" name="email" placeholder="Email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="admin_contact_number" class="form-label">Phone Number</label>
                        <div class="phone-input">
                            <span class="phone-prefix">+63</span>
                            <input type="text" class="form-control phone-number" id="admin_contact_number" name="contact_number" 
                                placeholder="9123456789" maxlength="10" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="admin_password" class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="admin_password" name="password" placeholder="Password" required>
                            <span class="input-group-text toggle-password" style="cursor: pointer;">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="admin_confirm_password" class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="admin_confirm_password" name="confirm_password" 
                                placeholder="Re-enter Password" required>
                            <span class="input-group-text toggle-password" style="cursor: pointer;">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Register Admin</button>
                </div>
            </form>
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
    // Add validation for the admin form
    function validateAdminForm() {
        let password = document.getElementById("admin_password").value;
        let confirmPassword = document.getElementById("admin_confirm_password").value;

        if (password !== confirmPassword) {
            alert("Passwords do not match!");
            return false;
        }
        return true;
    }

    // Toggle password visibility for all password fields
    document.querySelectorAll('.toggle-password').forEach(function(element) {
        element.addEventListener('click', function() {
            const input = this.parentElement.querySelector('input');
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
    
    // Phone number validation for all contact fields
    document.querySelectorAll('.phone-number').forEach(function(element) {
        element.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    });

    // Edit User Modal Handler (existing code remains the same)
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
