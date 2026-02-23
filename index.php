<?php
session_start();
// If already logged in, show Dashboard link instead of Login
$isLoggedIn = isset($_SESSION['user_id']);
$dashboardLink = 'login.php';
if ($isLoggedIn) {
    if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
        $dashboardLink = 'admin/index.php';
    } else {
        $dashboardLink = 'customer/index.php';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Premium Vehicle Rent</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .intro-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }
        .intro-card {
            border: none;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            background: white;
            text-align: center;
        }
        .intro-image {
            background: url('https://images.unsplash.com/photo-1565043666747-69f6646db940?q=80&w=1974&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D') center/cover no-repeat;
            min-height: 100%;
            height: 100%;
            border-radius: 0 24px 24px 0;
        }
        .intro-btn {
            background-color: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 14px 24px;
            font-weight: 600;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            font-size: 1.1rem;
        }
        .intro-btn:hover {
            background-color: #1e293b;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(15, 23, 42, 0.3);
            color: #fff;
        }
        .intro-btn-outline {
            background-color: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }
        .intro-btn-outline:hover {
            background-color: var(--primary-color);
            color: white;
        }
    </style>
</head>
<body>

<div class="intro-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="intro-card">
                    <div class="row g-0">
                        <div class="col-md-6 p-5 d-flex flex-column justify-content-center bg-white">
                            <div class="text-center mb-5">
                                <h1 class="fw-bold text-gradient mb-2">VehicleRent</h1>
                                <p class="text-muted text-uppercase fw-semibold" style="letter-spacing: 1px; font-size: 0.9rem;">Welcome</p>
                            </div>
                            
                            <p class="text-center text-muted mb-4">Are you ready to experience the best car rental service?</p>
                            
                            <div class="d-grid gap-3">
                                <?php if ($isLoggedIn): ?>
                                    <a href="<?= $dashboardLink ?>" class="intro-btn">
                                        Go to Dashboard <i class="fa-solid fa-arrow-right"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="login.php" class="intro-btn">
                                        <i class="fa-solid fa-right-to-bracket"></i> Sign In to Account
                                    </a>
                                    <a href="register.php" class="intro-btn intro-btn-outline">
                                        <i class="fa-solid fa-user-plus"></i> Create New Account
                                    </a>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mt-5 pt-4 text-center border-top border-light">
                                <div class="d-flex justify-content-center gap-4 text-muted" style="font-size: 0.85rem;">
                                    <span><i class="fa-solid fa-shield-halved text-success me-1"></i> Secure</span>
                                    <span><i class="fa-solid fa-tags text-primary me-1"></i> Best Prices</span>
                                    <span><i class="fa-solid fa-headset text-info me-1"></i> 24/7 Support</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 d-none d-md-block p-0">
                            <div class="intro-image"></div>
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
