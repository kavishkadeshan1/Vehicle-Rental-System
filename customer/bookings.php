<?php
// customer/bookings.php
session_start();
require '../config.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user's bookings, joined with vehicle details and payment status
$sql = "SELECT b.*, v.brand, v.model, v.image, p.payment_status 
        FROM bookings b 
        JOIN vehicles v ON b.vehicle_id = v.id 
        LEFT JOIN payments p ON b.id = p.booking_id
        WHERE b.user_id = ? 
        ORDER BY b.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Premium Vehicle Rent</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom sticky-top py-3">
    <div class="container">
        <a class="navbar-brand" href="index.php"><i class="fa-solid fa-car-side text-primary me-2"></i>VehicleRent</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item me-3">
                    <a class="nav-link" href="index.php">Dashboard</a>
                </li>
                <li class="nav-item me-3">
                    <a class="nav-link" href="vehicles.php">Vehicles</a>
                </li>
                <li class="nav-item me-3">
                    <a class="nav-link active" href="bookings.php">My Bookings</a>
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

<div class="hero-section text-center py-5" style="border-radius: 0; margin-bottom: 40px; padding: 60px 0;">
    <div class="container hero-content">
        <h1 class="hero-title mb-2" style="font-size: 2.5rem;">My Bookings</h1>
        <p class="hero-subtitle mb-0">Manage your upcoming trips and past rentals.</p>
    </div>
</div>

<div class="container mb-5">
    <div class="row g-4">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($booking = $result->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card card-premium h-100 border-0">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="bg-light rounded p-2 me-3">
                                        <i class="fa-solid fa-car text-primary fs-4"></i>
                                    </div>
                                    <h5 class="card-title fw-bold mb-0">
                                        <?= htmlspecialchars($booking['brand'] . ' ' . $booking['model']) ?>
                                    </h5>
                                </div>
                                <?php
                                    $badge_class = 'bg-secondary';
                                    $icon = 'fa-clock';
                                    if ($booking['status'] == 'approved') { $badge_class = 'bg-success'; $icon = 'fa-check-circle'; }
                                    if ($booking['status'] == 'rejected') { $badge_class = 'bg-danger'; $icon = 'fa-times-circle'; }
                                    if ($booking['status'] == 'completed') { $badge_class = 'bg-primary'; $icon = 'fa-flag-checkered'; }
                                ?>
                                <span class="badge <?= $badge_class ?> px-3 py-2 rounded-pill"><i class="fa-solid <?= $icon ?> me-1"></i> <?= ucfirst($booking['status']) ?></span>
                            </div>
                            
                            <div class="bg-light rounded p-3 mb-4">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small"><i class="fa-regular fa-calendar me-2"></i>Pick-up</span>
                                    <span class="fw-medium"><?= date('M d, Y', strtotime($booking['start_date'])) ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small"><i class="fa-regular fa-calendar-check me-2"></i>Drop-off</span>
                                    <span class="fw-medium"><?= date('M d, Y', strtotime($booking['end_date'])) ?></span>
                                </div>
                                <hr class="my-2 text-muted">
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <span class="text-muted small fw-bold text-uppercase">Total Price</span>
                                    <span class="text-primary fw-bold fs-5">Rs. <?= number_format($booking['total_price'], 2) ?></span>
                                </div>
                            </div>

                            <div class="mt-auto">
                                <?php if ($booking['status'] == 'approved'): ?>
                                    <?php if ($booking['payment_status'] == 'paid'): ?>
                                        <div class="alert alert-success py-2 mb-0 text-center border-0 fw-medium"><i class="fa-solid fa-shield-check me-2"></i>Paid Securely</div>
                                    <?php else: ?>
                                        <a href="payment.php?booking_id=<?= $booking['id'] ?>" class="btn btn-accent w-100 py-2"><i class="fa-regular fa-credit-card me-2"></i>Pay Now to Confirm</a>
                                    <?php endif; ?>
                                <?php elseif ($booking['status'] == 'pending'): ?>
                                    <div class="alert alert-info py-2 mb-0 text-center border-0 text-info bg-info bg-opacity-10 fw-medium"><i class="fa-solid fa-hourglass-half me-2"></i>Waiting for Approval</div>
                                <?php elseif ($booking['status'] == 'rejected'): ?>
                                    <div class="alert alert-danger py-2 mb-0 text-center border-0 text-danger bg-danger bg-opacity-10 fw-medium"><i class="fa-solid fa-ban me-2"></i>Booking Declined</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="text-center py-5 bg-white rounded-4 shadow-sm border">
                    <div class="text-muted mb-3" style="font-size: 3rem;"><i class="fa-regular fa-calendar-xmark"></i></div>
                    <h4 class="fw-bold text-dark">No bookings yet</h4>
                    <p class="text-muted">You haven't made any vehicle reservations.</p>
                    <a href="vehicles.php" class="btn btn-premium mt-2">Browse Vehicles</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $stmt->close(); ?>