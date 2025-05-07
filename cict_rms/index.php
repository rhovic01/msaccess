<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CICT Resources Inventory Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color:rgb(0, 0, 0);
            --secondary-color: #2575FC;
            --dark-color: #343a40;
            --light-color: #f8f9fa;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            color: white;
        }
        
        .navbar {
            background-color: rgba(0, 0, 0, 0.8);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand img {
            height: 40px;
        }
        
        .nav-link {
            color: white !important;
            padding: 8px 15px !important;
            margin: 0 5px;
            border-radius: 4px;
            transition: all 0.3s;
        }
        
        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .hero-section {
            min-height: calc(100vh - 72px);
            background: linear-gradient(rgba(106, 17, 203, 0.8), rgba(37, 117, 252, 0.8)), 
                        url('pexels-markusspiske-131773.jpg');
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
        }
        
        .hero-content {
            max-width: 800px;
        }
        
        .btn-primary {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            border: none;
            padding: 10px 25px;
        }
        
        .btn-outline-light {
            border: 2px solid white;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">Signup</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container text-center">
            <div class="hero-content mx-auto">
                <h1 class="display-4 fw-bold mb-4">Welcome to the CICT</h1>
                <h2 class="mb-4">Resources Inventory Management System</h2>
                <p class="lead mb-5">Streamlining resources management for the College of Information Technology and Communication</p>
                
                <div class="d-grid gap-3 d-sm-flex justify-content-sm-center">
                    <a href="login.php" class="btn btn-primary btn-lg px-4">Login</a>
                    <a href="register.php" class="btn btn-outline-light btn-lg px-4">Register</a>
                </div>
                
                <div class="mt-5 pt-4">
                    <h3 class="h4">GRANBY COLLEGES OF SCIENCE AND TECHNOLOGY</h3>
                    <p class="mb-0">COLLEGE OF INFORMATION TECHNOLOGY AND COMMUNICATION</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>