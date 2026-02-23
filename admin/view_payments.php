<?php
// admin/view_payments.php
session_start();
require '../config.php';

// 🛑 STRICT SECURITY CHECK
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// --- Fetch All Payments with Details ---
// Joining 4 tables to get a complete picture of the transaction
$sql = "SELECT p.*, 
               b.start_date, b.end_date, 
               u.name AS customer_name, u.email, 
               v.brand, v.model 
        FROM payments p
        JOIN bookings b ON p.booking_id = b.id
        JOIN users u ON b.user_id = u.id
        JOIN vehicles v ON b.vehicle_id = v.id
        ORDER BY p.created_at DESC";

$result = $conn->query($sql);

// Calculate total revenue for a quick summary banner
$revenue_query = $conn->query("SELECT SUM(amount) AS total FROM payments WHERE payment_status = 'paid'");
$total_revenue = $revenue_query->fetch_assoc()['total'] ?? 0.00;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Payments - Premium Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-custom sticky-top py-3">
    <div class="container">
        <a class="navbar-brand" href="index.php"><i class="fa-solid fa-shield-halved text-primary me-2"></i>Admin Portal</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item me-3"><a class="nav-link" href="index.php">Dashboard</a></li>
                <li class="nav-item me-3"><a class="nav-link" href="manage_users.php">Users</a></li>
                <li class="nav-item me-3"><a class="nav-link" href="manage_vehicles.php">Vehicles</a></li>
                <li class="nav-item me-3"><a class="nav-link" href="manage_bookings.php">Bookings</a></li>
                <li class="nav-item me-3"><a class="nav-link active fw-bold" href="view_payments.php">Payments</a></li>
                <li class="nav-item dropdown ms-2">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                        <div class="bg-dark text-white rounded-circle d-flex justify-content-center align-items-center me-2" style="width: 35px; height: 35px;">
                            <i class="fa-solid fa-user-tie"></i>
                        </div>
                        <span class="fw-medium text-dark"><?= htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow-sm mt-2">
                        <li><a class="dropdown-item py-2 text-danger" href="../logout.php"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-5 mb-5">
    <div class="row align-items-center mb-4">
        <div class="col-md-8">
            <h2 class="fw-bold mb-1">Payment History</h2>
            <p class="text-muted">Track and review all customer transactions.</p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <div class="card card-premium border-0 bg-primary text-white d-inline-block px-4 py-3">
                <div class="small text-white-50 text-uppercase fw-bold mb-1">Total Revenue</div>
                <h3 class="mb-0 fw-bold">Rs. <?= number_format($total_revenue, 2) ?></h3>
            </div>
        </div>
    </div>

    <div class="card card-premium border-0">
        <div class="card-header bg-transparent border-bottom-0 pt-4 pb-0">
            <h5 class="fw-bold mb-0"><i class="fa-solid fa-money-bill-transfer text-primary me-2"></i>All Transactions</h5>
        </div>
        <div class="card-body p-4 table-responsive">
            <table class="table table-hover align-middle border-top">
                <thead class="table-light">
                    <tr>
                        <th class="text-muted fw-bold text-uppercase small py-3">Transaction ID</th>
                        <th class="text-muted fw-bold text-uppercase small py-3">Date & Time</th>
                        <th class="text-muted fw-bold text-uppercase small py-3">Customer</th>
                        <th class="text-muted fw-bold text-uppercase small py-3">Vehicle Booked</th>
                        <th class="text-muted fw-bold text-uppercase small py-3">Method</th>
                        <th class="text-muted fw-bold text-uppercase small py-3">Amount</th>
                        <th class="text-muted fw-bold text-uppercase small py-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($payment = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark"><i class="fa-solid fa-hashtag text-muted me-1"></i><?= htmlspecialchars($payment['transaction_id']) ?></div>
                                    <div class="small text-muted">Booking #<?= str_pad($payment['booking_id'], 4, '0', STR_PAD_LEFT) ?></div>
                                </td>
                                <td>
                                    <div class="fw-medium"><?= date('M d, Y', strtotime($payment['created_at'])) ?></div>
                                    <div class="small text-muted"><?= date('h:i A', strtotime($payment['created_at'])) ?></div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($payment['customer_name']) ?></div>
                                    <div class="small text-muted"><i class="fa-regular fa-envelope me-1"></i><?= htmlspecialchars($payment['email']) ?></div>
                                </td>
                                <td>
                                    <div class="fw-medium"><?= htmlspecialchars($payment['brand'] . ' ' . $payment['model']) ?></div>
                                    <div class="small text-muted"><?= date('M d', strtotime($payment['start_date'])) ?> - <?= date('M d, Y', strtotime($payment['end_date'])) ?></div>
                                </td>
                                <td>
                                    <?php
                                        $method_icon = 'fa-credit-card';
                                        if (strtolower($payment['payment_method']) == 'paypal') $method_icon = 'fa-paypal';
                                        if (strtolower($payment['payment_method']) == 'cash') $method_icon = 'fa-money-bill-wave';
                                    ?>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-3 py-2"><i class="fa-brands <?= $method_icon ?> me-1"></i><?= ucfirst(htmlspecialchars($payment['payment_method'])) ?></span>
                                </td>
                                <td class="fw-bold text-primary fs-5">Rs. <?= number_format($payment['amount'], 2) ?></td>
                                <td>
                                    <?php if ($payment['payment_status'] == 'paid'): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-2"><i class="fa-solid fa-check-circle me-1"></i>PAID</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3 py-2"><i class="fa-solid fa-circle-xmark me-1"></i>UNPAID</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center py-5 text-muted">No payments recorded yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>