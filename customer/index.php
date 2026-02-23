<?php
// customer/index.php
session_start();

// Security Check: Kick out unauthorized users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Premium Vehicle Rent</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom sticky-top py-3">
    <div class="container">
        <a class="navbar-brand" href="index.php"><i class="fa-solid fa-car-side text-primary me-2"></i>VehicleRent</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item me-3">
                    <a class="nav-link active" href="index.php">Dashboard</a>
                </li>
                <li class="nav-item me-3">
                    <a class="nav-link" href="vehicles.php">Vehicles</a>
                </li>
                <li class="nav-item me-3">
                    <a class="nav-link" href="bookings.php">My Bookings</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center me-2 overflow-hidden" style="width: 35px; height: 35px;">
                            <?php if (!empty($_SESSION['profile_pic'])): ?>
                                <img src="../uploads/profiles/<?= htmlspecialchars($_SESSION['profile_pic']) ?>" alt="Profile Picture" class="w-100 h-100" style="object-fit: cover;">
                            <?php else: ?>
                                <i class="fa-solid fa-user"></i>
                            <?php endif; ?>
                        </div>
                        <span class="fw-medium text-dark"><?= htmlspecialchars($_SESSION['name']); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow-sm mt-2">
                        <li><a class="dropdown-item py-2" href="profile.php"><i class="fa-regular fa-id-badge me-2 text-muted"></i> Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item py-2 text-danger" href="../logout.php"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="hero-section text-center">
    <div class="container hero-content">
        <h1 class="hero-title">Welcome back, <?= htmlspecialchars(explode(' ', trim($_SESSION['name']))[0]); ?>!</h1>
        <p class="hero-subtitle">Ready to rent a vehicle? Explore our premium selection and book your rental.</p>
        <a href="vehicles.php" class="btn btn-accent btn-lg px-5 py-3 rounded-pill shadow-lg">Browse Vehicles <i class="fa-solid fa-arrow-right ms-2"></i></a>
    </div>
</div>

<div class="container mb-5" style="margin-top: -60px; position: relative; z-index: 10;">
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card card-premium h-100 text-center p-4">
                <div class="card-body d-flex flex-column justify-content-center align-items-center">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex justify-content-center align-items-center mb-4" style="width: 80px; height: 80px; font-size: 2rem;">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>
                    <h3 class="fw-bold mb-3">Rent a Vehicle</h3>
                    <p class="text-muted mb-4">Search our extensive selection of premium vehicles, from sleek sedans to spacious SUVs, and book your next rental instantly.</p>
                    <a href="vehicles.php" class="btn btn-premium mt-auto px-4 py-2">Search Vehicles</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card card-premium h-100 text-center p-4">
                <div class="card-body d-flex flex-column justify-content-center align-items-center">
                    <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex justify-content-center align-items-center mb-4" style="width: 80px; height: 80px; font-size: 2rem;">
                        <i class="fa-regular fa-calendar-check"></i>
                    </div>
                    <h3 class="fw-bold mb-3">My Bookings</h3>
                    <p class="text-muted mb-4">View your booking history, check current statuses, manage upcoming trips, and handle payments securely.</p>
                    <a href="bookings.php" class="btn btn-outline-premium mt-auto px-4 py-2">View Bookings</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>