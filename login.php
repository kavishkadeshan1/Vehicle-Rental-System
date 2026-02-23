<?php
// login.php
session_start();
require 'config.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, name, password, role, status, profile_pic FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Check if blocked
        if ($user['status'] == 'blocked') {
            $error = "<div class='alert alert-danger'>Your account is blocked. Contact support.</div>";
        } else {
            // Verify Password
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['profile_pic'] = $user['profile_pic'];

                // Redirect based on role
                if ($user['role'] == 'admin') {
                    header("Location: admin/index.php");
                } else {
                    header("Location: customer/index.php");
                }
                exit();
            } else {
                $error = "<div class='alert alert-danger'>Invalid password.</div>";
            }
        }
    } else {
        $error = "<div class='alert alert-danger'>No account found with that email.</div>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Premium Vehicle Rent</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card auth-card">
                    <div class="row g-0">
                        <div class="col-md-6 d-none d-md-block">
                            <div class="auth-image"></div>
                        </div>
                        <div class="col-md-6 p-5 d-flex flex-column justify-content-center bg-white">
                            <div class="text-center mb-4">
                                <h2 class="fw-bold text-gradient mb-2"><i class="fa-solid fa-car-side me-2"></i>VehicleRent</h2>
                                <p class="text-muted">Welcome back! Please login to your account.</p>
                            </div>
                            
                            <?= $error ?>
                            
                            <form method="POST" action="">
                                <div class="mb-4">
                                    <label class="form-label">Email Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="fa-regular fa-envelope text-muted"></i></span>
                                        <input type="email" name="email" class="form-control form-control-premium border-start-0 ps-0" placeholder="name@example.com" required>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-lock text-muted"></i></span>
                                        <input type="password" name="password" class="form-control form-control-premium border-start-0 ps-0" placeholder="••••••••" required>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="remember">
                                        <label class="form-check-label text-muted" for="remember">Remember me</label>
                                    </div>
                                    <a href="#" class="text-decoration-none text-primary fw-medium">Forgot Password?</a>
                                </div>
                                <button type="submit" class="btn btn-premium w-100 py-3 mb-4">Sign In <i class="fa-solid fa-arrow-right ms-2"></i></button>
                            </form>
                            
                            <div class="text-center">
                                <p class="text-muted mb-0">Don't have an account? <a href="register.php" class="text-decoration-none fw-bold text-primary">Create one</a></p>
                            </div>
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