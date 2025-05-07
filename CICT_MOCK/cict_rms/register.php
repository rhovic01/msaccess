    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Register</title>
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Font Awesome for icons -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            :root {
                --primary-color: #6A11CB;
                --secondary-color: #2575FC;
            }
            
            body {
                font-family: 'Poppins', sans-serif;
                background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
                min-height: 100vh;
                display: flex;
                align-items: center;
            }
            
            .register-container {
                background: white;
                border-radius: 10px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
                padding: 20px;
                max-width: 500px;
                width: 100%;
            }
            
            .btn-primary {
                background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
                border: none;
            }
            
            .form-control:focus {
                border-color: var(--primary-color);
                box-shadow: 0 0 0 0.25rem rgba(106, 17, 203, 0.25);
            }
            
            .phone-input {
                display: flex;
                align-items: center;
            }
            
            .phone-prefix {
                background: #e9ecef;
                padding: 8px 12px;
                border: 1px solid #ced4da;
                border-right: none;
                border-radius: 4px 0 0 4px;
            }
            
            .phone-number {
                border-radius: 0 4px 4px 0 !important;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="register-container">
                        <h2 class="text-center mb-4">Create Account</h2>
                        <form action="register_process.php" method="POST" onsubmit="return validateForm()">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" placeholder="First Name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Last Name" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="contact_number" class="form-label">Phone Number</label>
                                <div class="phone-input">
                                    <span class="phone-prefix">+63</span>
                                    <input type="text" class="form-control phone-number" id="contact_number" name="contact_number" 
                                        placeholder="9123456789" maxlength="10" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                                    <span class="input-group-text toggle-password" style="cursor: pointer;">
                                        <i class="fas fa-eye"></i>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                        placeholder="Re-enter Password" required>
                                    <span class="input-group-text toggle-password" style="cursor: pointer;">
                                        <i class="fas fa-eye"></i>
                                    </span>
                                </div>
                            </div>
                            
                           <input type="hidden" name="role" value="officer">
                            
                            <button type="submit" class="btn btn-primary w-100 mb-3">Register</button>
                            
                            <div class="text-center">
                                <a href="index.php" class="text-decoration-none">Back to Home</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            function validateForm() {
                let password = document.getElementById("password").value;
                let confirmPassword = document.getElementById("confirm_password").value;

                if (password !== confirmPassword) {
                    alert("Passwords do not match!");
                    return false;
                }
                return true;
            }
            
            // Toggle password visibility
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
            
            // Phone number validation
            document.getElementById('contact_number').addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        </script>
    </body>
    </html>