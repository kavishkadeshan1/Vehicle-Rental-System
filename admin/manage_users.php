<?php
// admin/manage_users.php
session_start();
require '../config.php';

// 🛑 STRICT SECURITY CHECK
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';

// --- Handle User Actions (Block, Activate, Delete) ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $target_user_id = intval($_GET['id']);

    // Prevent the admin from blocking/deleting themselves
    if ($target_user_id == $_SESSION['user_id']) {
        $message = "<div class='alert alert-warning'>You cannot perform actions on your own account.</div>";
    } else {
        if ($action == 'block') {
            $stmt = $conn->prepare("UPDATE users SET status = 'blocked' WHERE id = ?");
            $stmt->bind_param("i", $target_user_id);
            if ($stmt->execute()) $message = "<div class='alert alert-warning'>User #$target_user_id has been blocked.</div>";
            $stmt->close();
        } 
        elseif ($action == 'activate') {
            $stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE id = ?");
            $stmt->bind_param("i", $target_user_id);
            if ($stmt->execute()) $message = "<div class='alert alert-success'>User #$target_user_id is now active.</div>";
            $stmt->close();
        } 
        elseif ($action == 'delete') {
            // Note: If a user has bookings, deleting them might fail due to database foreign key constraints. 
            // In a real production app, it is usually better to just 'block' them.
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $target_user_id);
            try {
                if ($stmt->execute()) {
                    $message = "<div class='alert alert-danger'>User #$target_user_id permanently deleted.</div>";
                }
            } catch (mysqli_sql_exception $e) {
                $message = "<div class='alert alert-danger'>Cannot delete this user because they have associated bookings. Please 'Block' them instead.</div>";
            }
            $stmt->close();
        }
    }
}

// --- Fetch All Users ---
// We generally only need to manage 'customer' roles, but we will fetch all to see the whole system.
$sql = "SELECT * FROM users ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Premium Admin</title>
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
                <li class="nav-item me-3"><a class="nav-link active fw-bold" href="manage_users.php">Users</a></li>
                <li class="nav-item me-3"><a class="nav-link" href="manage_vehicles.php">Vehicles</a></li>
                <li class="nav-item me-3"><a class="nav-link" href="manage_bookings.php">Bookings</a></li>
                <li class="nav-item me-3"><a class="nav-link" href="view_payments.php">Payments</a></li>
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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Manage Users</h2>
            <p class="text-muted">View, block, or delete customer accounts.</p>
        </div>
    </div>
    
    <?= $message ?>

    <div class="card card-premium border-0">
        <div class="card-header bg-transparent border-bottom-0 pt-4 pb-0">
            <h5 class="fw-bold mb-0"><i class="fa-solid fa-users-gear text-primary me-2"></i>All Users</h5>
        </div>
        <div class="card-body p-4 table-responsive">
            <table class="table table-hover align-middle border-top">
                <thead class="table-light">
                    <tr>
                        <th class="text-muted fw-bold text-uppercase small py-3">ID</th>
                        <th class="text-muted fw-bold text-uppercase small py-3">User Info</th>
                        <th class="text-muted fw-bold text-uppercase small py-3">Contact</th>
                        <th class="text-muted fw-bold text-uppercase small py-3">Role</th>
                        <th class="text-muted fw-bold text-uppercase small py-3">Status</th>
                        <th class="text-muted fw-bold text-uppercase small py-3">Joined</th>
                        <th class="text-muted fw-bold text-uppercase small py-3 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($user = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="fw-bold text-muted">#<?= str_pad($user['id'], 4, '0', STR_PAD_LEFT) ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3 overflow-hidden" style="width: 40px; height: 40px;">
                                            <?php if (!empty($user['profile_pic'])): ?>
                                                <img src="../uploads/profiles/<?= htmlspecialchars($user['profile_pic']) ?>" alt="Profile" class="w-100 h-100" style="object-fit: cover;">
                                            <?php else: ?>
                                                <i class="fa-solid fa-user"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($user['name']) ?></div>
                                            <div class="small text-muted"><?= htmlspecialchars($user['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><i class="fa-solid fa-phone text-muted me-2"></i><?= htmlspecialchars($user['phone']) ?></td>
                                <td>
                                    <?php if ($user['role'] == 'admin'): ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3 py-2"><i class="fa-solid fa-shield-halved me-1"></i>Admin</span>
                                    <?php else: ?>
                                        <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-3 py-2"><i class="fa-solid fa-user me-1"></i>Customer</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['status'] == 'active'): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-2"><i class="fa-solid fa-check-circle me-1"></i>Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-3 py-2"><i class="fa-solid fa-ban me-1"></i>Blocked</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted small"><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                <td class="text-end">
                                    <?php if ($user['id'] != $_SESSION['user_id'] && $user['role'] != 'admin'): ?>
                                        <div class="btn-group">
                                            <?php if ($user['status'] == 'active'): ?>
                                                <a href="manage_users.php?action=block&id=<?= $user['id'] ?>" class="btn btn-sm btn-warning" onclick="return confirm('Block this user? They will not be able to log in.');" title="Block"><i class="fa-solid fa-ban"></i></a>
                                            <?php else: ?>
                                                <a href="manage_users.php?action=activate&id=<?= $user['id'] ?>" class="btn btn-sm btn-success" title="Activate"><i class="fa-solid fa-check"></i></a>
                                            <?php endif; ?>
                                            <a href="manage_users.php?action=delete&id=<?= $user['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('WARNING: Are you sure you want to permanently delete this user?');" title="Delete"><i class="fa-solid fa-trash"></i></a>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small fst-italic">No Actions</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center py-5 text-muted">No users found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>