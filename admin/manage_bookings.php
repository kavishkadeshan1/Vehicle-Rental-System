<?php
// admin/manage_bookings.php
session_start();
require '../config.php';

// 🛑 STRICT SECURITY CHECK
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';

// --- Handle Status Updates ---
if (isset($_GET['action']) && isset($_GET['id']) && isset($_GET['vid'])) {
    $action = $_GET['action'];
    $booking_id = intval($_GET['id']);
    $vehicle_id = intval($_GET['vid']);

    if ($action == 'approve') {
        // Update booking to approved, vehicle to booked
        $conn->query("UPDATE bookings SET status = 'approved' WHERE id = $booking_id");
        $conn->query("UPDATE vehicles SET status = 'booked' WHERE id = $vehicle_id");
        $message = "<div class='alert alert-success'>Booking #$booking_id Approved! Vehicle is now locked.</div>";
    } 
    elseif ($action == 'reject') {
        // Update booking to rejected
        $conn->query("UPDATE bookings SET status = 'rejected' WHERE id = $booking_id");
        $message = "<div class='alert alert-danger'>Booking #$booking_id Rejected.</div>";
    } 
    elseif ($action == 'complete') {
        // Update booking to completed, vehicle back to available
        $conn->query("UPDATE bookings SET status = 'completed' WHERE id = $booking_id");
        $conn->query("UPDATE vehicles SET status = 'available' WHERE id = $vehicle_id");
        $message = "<div class='alert alert-primary'>Booking #$booking_id Completed! Vehicle is available again.</div>";
    }
}

// --- Fetch All Bookings ---
// Using JOIN to get user names, vehicle details, and payment status in one query
$sql = "SELECT b.*, u.name AS customer_name, u.email, v.brand, v.model, p.payment_status 
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN vehicles v ON b.vehicle_id = v.id
        LEFT JOIN payments p ON b.id = p.booking_id
        ORDER BY b.created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - Premium Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .detail-row {
            margin-bottom: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #dee2e6;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #6c757d;
            font-size: 0.85rem;
            text-transform: uppercase;
        }
        .detail-value {
            font-weight: 500;
            color: #212529;
        }
    </style>
</head>
<body>

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
                <li class="nav-item me-3"><a class="nav-link active fw-bold" href="manage_bookings.php">Bookings</a></li>
                <li class="nav-item me-3"><a class="nav-link" href="view_payments.php">Payments</a></li>
                <li class="nav-item dropdown ms-2">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                        <div class="bg-dark text-white rounded-circle d-flex justify-content-center align-items-center me-2" style="width: 35px; height: 35px;">
                            <i class="fa-solid fa-user-tie"></i>
                        </div>
                        <span class="fw-medium text-dark"><?= htmlspecialchars($_SESSION['name']); ?></span>
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
            <h2 class="fw-bold mb-1">Manage Bookings</h2>
            <p class="text-muted">Review, approve, or reject customer booking requests.</p>
        </div>
    </div>
    
    <?= $message ?>

    <div class="card card-premium border-0">
        <div class="card-header bg-transparent border-bottom-0 pt-4 pb-0">
            <h5 class="fw-bold mb-0"><i class="fa-solid fa-list-check text-primary me-2"></i>All Bookings</h5>
        </div>
        <div class="card-body p-4 table-responsive">
            <table class="table table-hover align-middle border-top">
                <thead class="table-light">
                    <tr>
                        <th class="text-muted fw-bold text-uppercase small py-3">ID</th>
                        <th class="text-muted fw-bold text-uppercase small py-3">Customer</th>
                        <th class="text-muted fw-bold text-uppercase small py-3">Vehicle</th>
                        <th class="text-muted fw-bold text-uppercase small py-3">Dates</th>
                        <th class="text-muted fw-bold text-uppercase small py-3">Total (Rs.)</th>
                        <th class="text-muted fw-bold text-uppercase small py-3">Details</th>
                        <th class="text-muted fw-bold text-uppercase small py-3">Status</th>
                        <th class="text-muted fw-bold text-uppercase small py-3 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="fw-bold text-muted">#<?= str_pad($row['id'], 4, '0', STR_PAD_LEFT) ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($row['customer_name']) ?></div>
                                    <div class="small text-muted"><i class="fa-regular fa-envelope me-1"></i><?= htmlspecialchars($row['email']) ?></div>
                                    <?php if(!empty($row['customer_number'])): ?>
                                        <div class="small text-muted"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($row['customer_number']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-medium"><?= htmlspecialchars($row['brand'] . ' ' . $row['model']) ?></td>
                                <td>
                                    <div class="small"><span class="text-muted">Start:</span> <span class="fw-medium"><?= date('M d, Y', strtotime($row['start_date'])) ?></span></div>
                                    <div class="small"><span class="text-muted">End:</span> <span class="fw-medium"><?= date('M d, Y', strtotime($row['end_date'])) ?></span></div>
                                </td>
                                <td class="fw-bold text-primary">Rs. <?= number_format($row['total_price'], 2) ?></td>
                                
                                <td>
                                    <?php 
                                        $bookingData = htmlspecialchars(json_encode([
                                            'id' => $row['id'],
                                            'customer_name' => $row['customer_name'],
                                            'email' => $row['email'],
                                            'phone' => $row['customer_number'],
                                            'location' => $row['customer_location'],
                                            'destination' => $row['destination'],
                                            'message' => $row['message'],
                                            'vehicle' => $row['brand'] . ' ' . $row['model'],
                                            'license' => '../uploads/' . $row['license_file']
                                        ])); 
                                    ?>
                                    <button onclick="showDetailsModal(this)" data-booking="<?= $bookingData ?>" class="btn btn-sm btn-outline-premium rounded-pill px-3">
                                        <i class="fa-solid fa-eye me-1"></i> View More
                                    </button>
                                </td>
                                <td>
                                    <?php
                                        $badge = 'bg-secondary bg-opacity-10 text-secondary';
                                        $icon = 'fa-circle-info';
                                        if ($row['status'] == 'pending') { $badge = 'bg-warning bg-opacity-10 text-warning'; $icon = 'fa-hourglass-half'; }
                                        if ($row['status'] == 'approved') { $badge = 'bg-success bg-opacity-10 text-success'; $icon = 'fa-check-circle'; }
                                        if ($row['status'] == 'rejected') { $badge = 'bg-danger bg-opacity-10 text-danger'; $icon = 'fa-ban'; }
                                        if ($row['status'] == 'completed') { $badge = 'bg-primary bg-opacity-10 text-primary'; $icon = 'fa-flag-checkered'; }
                                    ?>
                                    <span class="badge <?= $badge ?> rounded-pill px-3 py-2"><i class="fa-solid <?= $icon ?> me-1"></i><?= ucfirst($row['status']) ?></span>
                                </td>
                                <td class="text-end">
                                    <?php if ($row['status'] == 'pending'): ?>
                                        <div class="btn-group">
                                            <a href="manage_bookings.php?action=approve&id=<?= $row['id'] ?>&vid=<?= $row['vehicle_id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Approve this booking?');" title="Approve"><i class="fa-solid fa-check"></i></a>
                                            <a href="manage_bookings.php?action=reject&id=<?= $row['id'] ?>&vid=<?= $row['vehicle_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Reject this booking?');" title="Reject"><i class="fa-solid fa-xmark"></i></a>
                                        </div>
                                    <?php elseif ($row['status'] == 'approved'): ?>
                                        <a href="manage_bookings.php?action=complete&id=<?= $row['id'] ?>&vid=<?= $row['vehicle_id'] ?>" class="btn btn-sm btn-primary rounded-pill px-3" onclick="return confirm('Mark as Completed? This will make the vehicle available again.');"><i class="fa-solid fa-flag-checkered me-1"></i>Complete</a>
                                    <?php else: ?>
                                        <span class="text-muted small fst-italic">No Actions</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="9" class="text-center py-5 text-muted">No bookings found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header bg-light border-bottom border-light pt-4 pb-3 px-4">
                <h5 class="modal-title fw-bold text-dark" id="detailsModalLabel"><i class="fa-solid fa-circle-info text-primary me-2"></i>Booking Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-white">
                <div class="row g-4">
                    <div class="col-md-6">
                        <h6 class="fw-bold mb-3 text-primary border-bottom pb-2">Customer Info</h6>
                        <div class="detail-row">
                            <div class="detail-label">Name</div>
                            <div class="detail-value" id="mdl_name">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Email</div>
                            <div class="detail-value" id="mdl_email">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Contact Number</div>
                            <div class="detail-value" id="mdl_phone">-</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold mb-3 text-primary border-bottom pb-2">Trip Requirements</h6>
                        <div class="detail-row">
                            <div class="detail-label">Vehicle Requested</div>
                            <div class="detail-value" id="mdl_vehicle">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Pickup Location</div>
                            <div class="detail-value" id="mdl_location">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Destination</div>
                            <div class="detail-value" id="mdl_destination">-</div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-2">
                    <div class="col-12">
                        <div class="detail-row border-0">
                            <div class="detail-label">Special Requests / Message</div>
                            <div class="detail-value text-muted fst-italic bg-light p-3 rounded" id="mdl_message">No message provided.</div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12 text-center">
                        <button onclick="openLicenseFromDetails()" class="btn btn-outline-premium px-4 rounded-pill">
                            <i class="fa-solid fa-id-card me-2"></i>View Driving License
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light py-3 px-4">
                <button type="button" class="btn btn-secondary px-4 rounded-pill shadow-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- License Modal (unchanged style, just JS trigger changed) -->
<div class="modal fade" id="licenseModal" tabindex="-1" aria-labelledby="licenseModalLabel" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg bg-dark text-light" style="border-radius: 12px; overflow: hidden; height: 90vh;">
            <div class="modal-header border-0 py-3 position-absolute top-0 w-100 z-3" style="background: linear-gradient(to bottom, rgba(0,0,0,0.8), transparent);">
                <h5 class="modal-title fw-bold text-white shadow-sm" id="licenseModalLabel"><i class="fa-solid fa-id-card text-primary me-2"></i>Customer License Document</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="position-absolute top-50 end-0 translate-middle-y me-3 z-3 d-flex flex-column gap-2 bg-dark bg-opacity-75 p-2 rounded-pill shadow-lg border border-secondary border-opacity-50" style="backdrop-filter: blur(5px);">
                <button class="btn btn-sm btn-outline-light rounded-circle" onclick="zoomIn()" style="width: 36px; height: 36px;" title="Zoom In"><i class="fa-solid fa-plus"></i></button>
                <button class="btn btn-sm btn-outline-light rounded-circle" onclick="resetZoom()" style="width: 36px; height: 36px;" title="Reset Zoom"><i class="fa-solid fa-rotate-left"></i></button>
                <button class="btn btn-sm btn-outline-light rounded-circle" onclick="zoomOut()" style="width: 36px; height: 36px;" title="Zoom Out"><i class="fa-solid fa-minus"></i></button>
            </div>

            <div class="modal-body p-0 text-center bg-black d-flex align-items-center justify-content-center" style="position: relative; overflow: hidden;" id="imageContainer">
                <div class="position-absolute top-50 start-50 translate-middle text-white-50" id="imageSpinner">
                    <div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div>
                </div>
                <img id="licenseImage" src="" style="max-width: 100%; max-height: 100%; object-fit: contain; cursor: grab; transition: transform 0.1s ease-out;" onload="document.getElementById('imageSpinner').style.display='none'" onerror="this.onerror=null; this.src='../assets/images/placeholder.jpg';">
                <iframe id="licenseIframe" src="" style="width: 100%; height: 100%; border: none; display: none; background-color: white;"></iframe>
            </div>
            <div class="modal-footer border-0 bg-dark py-3 position-absolute bottom-0 w-100 z-3" style="background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);">
                <a id="licenseDownloadBtn" href="#" download class="btn btn-primary px-4 rounded-pill shadow"><i class="fa-solid fa-download me-2"></i>Save Copy</a>
                <button type="button" class="btn btn-light px-4 rounded-pill shadow" data-bs-dismiss="modal">Close Document</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    let currentLicenseUrl = '';

    function showDetailsModal(btn) {
        const data = JSON.parse(btn.getAttribute('data-booking'));
        
        document.getElementById('mdl_name').innerText = data.customer_name || '-';
        document.getElementById('mdl_email').innerText = data.email || '-';
        document.getElementById('mdl_phone').innerText = data.phone || '-';
        document.getElementById('mdl_vehicle').innerText = data.vehicle || '-';
        document.getElementById('mdl_location').innerText = data.location || '-';
        document.getElementById('mdl_destination').innerText = data.destination || '-';
        document.getElementById('mdl_message').innerText = data.message || 'No message provided.';
        
        currentLicenseUrl = data.license;

        var detailsModal = new bootstrap.Modal(document.getElementById('detailsModal'));
        detailsModal.show();
    }

    function openLicenseFromDetails() {
        // We can keep Details Modal open underneath, or close it. Let's keep it open or just layer above.
        showLicenseModal(currentLicenseUrl);
    }
    
    // Zoom Logic
    let scale = 1, pointX = 0, pointY = 0, start = { x: 0, y: 0 }, isDragging = false;
    const imgContainer = document.getElementById('imageContainer');
    const img = document.getElementById('licenseImage');
    const iframe = document.getElementById('licenseIframe');

    function setTransform() { img.style.transform = `translate(${pointX}px, ${pointY}px) scale(${scale})`; }
    function zoomIn() { scale *= 1.2; setTransform(); }
    function zoomOut() { scale = Math.max(0.1, scale / 1.2); setTransform(); }
    function resetZoom() { scale = 1; pointX = 0; pointY = 0; setTransform(); }

    imgContainer.onwheel = function (e) {
        if(iframe.style.display !== 'none') return; 
        e.preventDefault();
        const xs = (e.clientX - pointX) / scale, ys = (e.clientY - pointY) / scale;
        const delta = (e.wheelDelta ? e.wheelDelta : -e.deltaY);
        (delta > 0) ? (scale *= 1.1) : (scale /= 1.1);
        scale = Math.max(0.1, scale); 
        pointX = e.clientX - xs * scale; pointY = e.clientY - ys * scale;
        setTransform();
    }

    img.onmousedown = function (e) { e.preventDefault(); start = { x: e.clientX - pointX, y: e.clientY - pointY }; isDragging = true; img.style.cursor = 'grabbing'; }
    imgContainer.onmouseup = function (e) { isDragging = false; img.style.cursor = 'grab'; }
    imgContainer.onmouseleave = function (e) { isDragging = false; img.style.cursor = 'grab'; }
    imgContainer.onmousemove = function (e) { if (!isDragging) return; e.preventDefault(); pointX = (e.clientX - start.x); pointY = (e.clientY - start.y); setTransform(); }

    function showLicenseModal(url) {
        document.getElementById('licenseDownloadBtn').href = url;
        document.getElementById('imageSpinner').style.display = 'block';
        resetZoom();

        if (url.toLowerCase().endsWith('.pdf')) {
            img.style.display = 'none'; iframe.style.display = 'block'; iframe.src = url; document.getElementById('imageSpinner').style.display = 'none';
        } else {
            iframe.style.display = 'none'; iframe.src = ''; img.style.display = 'block'; img.src = url;
        }
        var licenseModal = new bootstrap.Modal(document.getElementById('licenseModal'));
        licenseModal.show();
    }
    
    document.getElementById('licenseModal').addEventListener('hidden.bs.modal', function () {
        iframe.src = ""; img.src = ""; resetZoom();
    });
</script>
</body>
</html>