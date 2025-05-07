<?php
    session_start();
    require 'db_connect.php';

    if (!isset($_SESSION['username'])) {
        header("Location: login.php");
        exit();
    }

    // Check if user is admin
    $isAdmin = ($_SESSION['role'] === 'admin');
    $conn->close();
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Dashboard</title>
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
                --success-color: #28a745;
                --danger-color: #dc3545;
            }
            
            body {
                font-family: 'Poppins', sans-serif;
                background-color: #f5f5f5;
            }
            
            .sidebar {
                background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
                color: white;
                height: 100vh;
                position: fixed;
                width: 250px;
                transition: all 0.3s;
                z-index: 1000;
            }
            
            .sidebar-header {
                padding: 20px;
                background: rgba(0, 0, 0, 0.2);
            }
            
            .sidebar-menu {
                padding: 0;
                list-style: none;
            }
            
            .sidebar-menu li {
                padding: 10px 20px;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                transition: all 0.3s;
            }
            
            .sidebar-menu li:hover {
                background: rgba(255, 255, 255, 0.1);
            }
            
            .sidebar-menu li a {
                color: white;
                text-decoration: none;
                display: block;
            }
            
            .sidebar-menu li i {
                margin-right: 10px;
            }
            
            .main-content {
                margin-left: 250px;
                padding: 20px;
                transition: all 0.3s;
            }
            
            .navbar-custom {
                background-color: white;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            }
            
            .card {
                border: none;
                border-radius: 10px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                margin-bottom: 20px;
                transition: transform 0.3s;
            }
            
            .card:hover {
                transform: translateY(-5px);
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
            
            .status-active {
                background-color: rgba(40, 167, 69, 0.2);
                color: var(--success-color);
            }
            
            .status-inactive {
                background-color: rgba(220, 53, 69, 0.2);
                color: var(--danger-color);
            }
            
            .table-responsive {
                border-radius: 10px;
                overflow: hidden;
            }
            
            .table th {
                background-color: var(--primary-color);
                color: white;
            }
            
            .action-btn {
                padding: 5px 10px;
                margin: 0 3px;
                border-radius: 5px;
                font-size: 12px;
            }
            
            .edit-btn {
                background-color: var(--primary-color);
                color: white;
            }
            
            .delete-btn {
                background-color: var(--danger-color);
                color: white;
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
            
            @media (max-width: 768px) {
                .sidebar {
                    width: 80px;
                    overflow: hidden;
                }
                
                .sidebar-header h3, 
                .sidebar-menu li span {
                    display: none;
                }
                
                .sidebar-menu li {
                    text-align: center;
                }
                
                .sidebar-menu li i {
                    margin-right: 0;
                    font-size: 1.2rem;
                }
                
                .main-content {
                    margin-left: 80px;
                }
            }
        </style>
    </head>
    <body>
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>CICT Admin</h3>
            </div>
            <ul class="sidebar-menu">
                <li>
                    <a href="#" class="tablink" onclick="openTab(event, 'ManageInventory', 'manage_inventory.php')">
                        <i class="fas fa-boxes"></i>
                        <span>Inventory</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="tablink" onclick="openTab(event, 'Transactions', 'transaction_history.php')">
                        <i class="fas fa-exchange-alt"></i>
                        <span>Transactions</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="tablink" onclick="openTab(event, 'Reports', 'reports.php')">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                </li>
                <?php if ($isAdmin): ?>
                <li>
                    <a href="#" class="tablink" onclick="openTab(event, 'ManageUsers', 'manage_users.php')">
                        <i class="fas fa-users-cog"></i>
                        <span>Manage Users</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Navbar -->
            <nav class="navbar navbar-expand-lg navbar-custom">
                <div class="container-fluid">
                    <div class="d-flex align-items-center">
                        <div class="user-avatar me-2">
                            <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                        </div>
                        <span class="navbar-brand mb-0">Welcome, <?php echo $_SESSION['name']; ?></span>
                    </div>
                    <div>
                        <a href="logout.php" class="btn btn-outline-danger">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </nav>

            <!-- Tab Content -->
        <div id="ManageInventory" class="tabcontent" style="display: none;">
            <?php include 'manage_inventory.php'; ?>
        </div>
        <div id="Transactions" class="tabcontent" style="display: none;">
            <?php include 'transaction_history.php'; ?>
        </div>
        <div id="Reports" class="tabcontent" style="display: none;">
            <?php include 'reports.php'; ?>
        </div>
        <?php if ($isAdmin): ?>
        <div id="ManageUsers" class="tabcontent" style="display: none;">
            <?php include 'manage_users.php'; ?>
        </div>
        <?php endif; ?>
        </div>

        <!-- Bootstrap JS Bundle with Popper -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            function openTab(event, tabName, targetFile) {
                // Hide all tab content
                const tabcontent = document.querySelectorAll(".tabcontent");
                tabcontent.forEach(tab => tab.style.display = "none");

                // Remove the "active" class from all tab links
                const tablinks = document.querySelectorAll(".tablink");
                tablinks.forEach(tab => tab.classList.remove("active"));

                // Show the current tab
                const targetPane = document.getElementById(tabName);
                targetPane.style.display = "block";
                if (event) event.currentTarget.classList.add("active");

                // Update URL with tab parameter
                const tabParam = tabName.toLowerCase().replace('manage', '');
                updateUrlParameter('tab', tabParam);
            }

            // Function to update URL parameters
            function updateUrlParameter(key, value) {
                const url = new URL(window.location.href);
                url.searchParams.set(key, value);
                window.history.pushState({}, '', url);
            }

            document.addEventListener('DOMContentLoaded', function () {
                // Get the active tab from URL parameter
                const urlParams = new URLSearchParams(window.location.search);
                const activeTab = urlParams.get('tab') || 'inventory';
                
                // Map URL parameter to tab names
                const tabMap = {
                    'inventory': 'ManageInventory',
                    'transactions': 'Transactions',
                    'reports': 'Reports',
                    'users': 'ManageUsers'
                };
                
                const tabName = tabMap[activeTab] || 'ManageInventory';
                
                // Find and click the corresponding tab
                const tabLink = Array.from(document.querySelectorAll('.tablink'))
                    .find(link => link.onclick.toString().includes(`'${tabName}'`));
                    
                if (tabLink) tabLink.click();
            });
        </script>
    </body>
    </html>