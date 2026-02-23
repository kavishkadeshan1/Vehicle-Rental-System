<?php
// register.php
session_start();
require 'config.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, 'customer')");
    $stmt->bind_param("ssss", $name, $email, $phone, $hashed_password);

    if ($stmt->execute()) {
        $message = "<div class='alert alert-success'>Registration successful! <a href='login.php'>Login here</a></div>";
    } else {
        $message = "<div class='alert alert-danger'>Error: Email might already exist.</div>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Premium Vehicle Rent</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-wrapper py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card auth-card">
                    <div class="row g-0">
                        <div class="col-md-6 p-5 d-flex flex-column justify-content-center bg-white order-2 order-md-1">
                            <div class="text-center mb-4">
                                <h2 class="fw-bold text-gradient mb-2"><i class="fa-solid fa-car-side me-2"></i>VehicleRent</h2>
                                <p class="text-muted">Create an account to start booking premium rides.</p>
                            </div>
                            
                            <?= $message ?>
                            
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label class="form-label">Full Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="fa-regular fa-user text-muted"></i></span>
                                        <input type="text" name="name" class="form-control form-control-premium border-start-0 ps-0" placeholder="John Doe" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="fa-regular fa-envelope text-muted"></i></span>
                                        <input type="email" name="email" class="form-control form-control-premium border-start-0 ps-0" placeholder="name@example.com" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-phone text-muted"></i></span>
                                        <input type="text" name="phone" class="form-control form-control-premium border-start-0 ps-0" placeholder="+1 234 567 890" required>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-lock text-muted"></i></span>
                                        <input type="password" name="password" class="form-control form-control-premium border-start-0 ps-0" placeholder="••••••••" required>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-premium w-100 py-3 mb-4">Create Account <i class="fa-solid fa-user-plus ms-2"></i></button>
                            </form>
                            
                            <div class="text-center">
                                <p class="text-muted mb-0">Already have an account? <a href="login.php" class="text-decoration-none fw-bold text-primary">Sign In</a></p>
                            </div>
                        </div>
                        <div class="col-md-6 d-none d-md-block order-1 order-md-2">
                            <div class="auth-image" style="background-image: url('https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D');"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>