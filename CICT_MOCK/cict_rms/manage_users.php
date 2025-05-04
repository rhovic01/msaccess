<?php
// manage_users.php (or the relevant section of your admin dashboard)
require 'db_connect.php';

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
        <div id="statusChangeAlert" style="display: none;"></div>

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
                                <tr id="user-row-<?php echo $user['id']; ?>">
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
                                        <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>" id="status-badge-<?php echo $user['id']; ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-secondary status-change-btn" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#statusChangeModal"
                                                data-user-id="<?php echo $user['id']; ?>"
                                                data-current-status="<?php echo $user['status']; ?>">
                                            <i class="fas fa-power-off"></i>
                                        </button>
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

<!-- Status Change Modal -->
<div class="modal fade" id="statusChangeModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="statusChangeModalLabel">Change User Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="statusChangeForm" method="POST">
                <input type="hidden" name="user_id" id="modal_user_id">
                <input type="hidden" name="new_status" id="modal_new_status">
                <div class="modal-body">
                    <p>Are you sure you want to <span id="statusActionText"></span> this account?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Confirm</button>
                </div>
            </form>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize modal with user data
    const statusChangeModal = document.getElementById('statusChangeModal');
    
    statusChangeModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const userId = button.getAttribute('data-user-id');
        const currentStatus = button.getAttribute('data-current-status');
        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
        const actionText = currentStatus === 'active' ? 'deactivate' : 'activate';
        
        document.getElementById('modal_user_id').value = userId;
        document.getElementById('modal_new_status').value = newStatus;
        document.getElementById('statusActionText').textContent = actionText;
    });

    // Handle form submission with AJAX
    const statusChangeForm = document.getElementById('statusChangeForm');
    if (statusChangeForm) {
        statusChangeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const userId = formData.get('user_id');
            const submitButton = this.querySelector('button[type="submit"]');
            
            // Disable button during request
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
            
            fetch('update_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Update the status badge
                    const statusBadge = document.getElementById(`status-badge-${userId}`);
                    if (statusBadge) {
                        statusBadge.className = `badge ${data.status_badge_class}`;
                        statusBadge.textContent = data.new_status.charAt(0).toUpperCase() + data.new_status.slice(1);
                    }
                    
                    // Update the button's data attribute
                    const statusButton = document.querySelector(`.status-change-btn[data-user-id="${userId}"]`);
                    if (statusButton) {
                        statusButton.setAttribute('data-current-status', data.new_status);
                    }
                    
                    showAlert('Status updated successfully!', 'success');
                } else {
                    throw new Error(data.error || 'Unknown error occurred');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Failed to update status: ' + error.message, 'danger');
            })
            .finally(() => {
                // Re-enable button
                submitButton.disabled = false;
                submitButton.textContent = 'Confirm';
                
                // Close modal and clean up
                const modal = bootstrap.Modal.getInstance(statusChangeModal);
                modal.hide();
                
                // Remove any lingering backdrop
                setTimeout(() => {
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(backdrop => backdrop.remove());
                    document.body.classList.remove('modal-open');
                    document.body.style.paddingRight = '';
                }, 100);
            });
        });
    }
    
    // Improved alert function
    function showAlert(message, type) {
        const alertDiv = document.getElementById('statusChangeAlert');
        alertDiv.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        alertDiv.style.display = 'block';
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            const alert = alertDiv.querySelector('.alert');
            if (alert) {
                alert.classList.remove('show');
                setTimeout(() => {
                    alertDiv.style.display = 'none';
                }, 150);
            }
        }, 5000);
    }
});

// Your existing admin form validation and other scripts
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
</script>