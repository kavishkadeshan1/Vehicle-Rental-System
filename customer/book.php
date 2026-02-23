<?php
// customer/book.php
session_start();
require '../config.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$vehicle_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];

// Get user info for defaults
$user_stmt = $conn->prepare("SELECT phone FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_phone = $user_stmt->get_result()->fetch_assoc()['phone'] ?? '';
$user_stmt->close();


// 1. Fetch Vehicle Details
$stmt = $conn->prepare("SELECT * FROM vehicles WHERE id = ? AND status = 'available' AND is_hidden = 0");
$stmt->bind_param("i", $vehicle_id);
$stmt->execute();
$vehicle = $stmt->get_result()->fetch_assoc();
$stmt->close();

// If vehicle doesn't exist or isn't available, redirect
if (!$vehicle) {
    header("Location: vehicles.php");
    exit();
}

// Prevent double booking for the same vehicle
$check_stmt = $conn->prepare("SELECT id FROM bookings WHERE user_id = ? AND vehicle_id = ? AND status IN ('pending', 'approved')");
$check_stmt->bind_param("ii", $user_id, $vehicle_id);
$check_stmt->execute();
if ($check_stmt->get_result()->num_rows > 0) {
    echo "<script>alert('You already have an active booking for this vehicle.'); window.location.href='bookings.php';</script>";
    exit();
}
$check_stmt->close();

// 2. Handle Booking Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    $customer_number = $_POST['customer_number'];
    $customer_location = $_POST['customer_location'];
    $destination = $_POST['destination'];
    $customer_message = $_POST['message'];
    
    // Calculate Days and Total Price
    $start_timestamp = strtotime($start_date);
    $end_timestamp = strtotime($end_date);
    $days = ($end_timestamp - $start_timestamp) / (60 * 60 * 24);
    
    if ($days < 1) {
        $message = "<div class='alert alert-danger'>End date must be after the start date.</div>";
    } else {
        $total_price = $days * $vehicle['price_per_day'];
        
        // 3. Handle License File Upload Securely
        $upload_dir = '../uploads/';
        $file_name = $_FILES['license']['name'];
        $file_tmp = $_FILES['license']['tmp_name'];
        $file_size = $_FILES['license']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $allowed_exts = array("jpg", "jpeg", "png", "pdf");
        
        if (in_array($file_ext, $allowed_exts) && $file_size < 5000000) { // Max 5MB
            // Create a unique file name to prevent overwriting
            $new_file_name = uniqid('license_', true) . '.' . $file_ext;
            $destination_path = $upload_dir . $new_file_name;
            
            if (move_uploaded_file($file_tmp, $destination_path)) {
                
                // 4. Insert Booking into Database
                $insert_stmt = $conn->prepare("INSERT INTO bookings (vehicle_id, user_id, start_date, end_date, total_price, license_file, customer_number, customer_location, destination, message, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                $insert_stmt->bind_param("iissdsssss", $vehicle_id, $user_id, $start_date, $end_date, $total_price, $new_file_name, $customer_number, $customer_location, $destination, $customer_message);
                
                if ($insert_stmt->execute()) {
                    $message = "<div class='alert alert-success'>Booking request submitted! Waiting for Admin approval. <a href='bookings.php'>View Bookings</a></div>";
                } else {
                    $message = "<div class='alert alert-danger'>Database error occurred.</div>";
                }
                $insert_stmt->close();
                
            } else {
                $message = "<div class='alert alert-danger'>Failed to upload license file.</div>";
            }
        } else {
            $message = "<div class='alert alert-danger'>Invalid file format (only JPG, PNG, PDF allowed) or file too large (Max 5MB).</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Vehicle - Premium Vehicle Rent</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .step-container {
            display: none;
            animation: fadeIn 0.4s ease-in-out;
        }
        .step-container.active {
            display: block;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .progress-indicators {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-bottom: 2rem;
        }
        .progress-indicators::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background-color: #e9ecef;
            z-index: 1;
        }
        .step-indicator {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e9ecef;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            z-index: 2;
            transition: all 0.3s;
        }
        .step-indicator.active {
            background-color: var(--primary-color);
            color: white;
            box-shadow: 0 0 0 4px rgba(var(--primary-rgb), 0.2);
        }
        .step-indicator.completed {
            background-color: #198754;
            color: white;
        }
    </style>
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
                    <a class="nav-link active" href="vehicles.php">Vehicles</a>
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

<div class="container mt-5 mb-5">
    <div class="mb-4">
        <a href="vehicles.php" class="text-decoration-none text-muted fw-medium"><i class="fa-solid fa-arrow-left me-2"></i>Back to Vehicles</a>
    </div>
    
    <?= $message ?>
    
    <div class="row g-5">
        <div class="col-lg-5">
            <div class="card card-premium border-0 sticky-top" style="top: 100px;">
                <div class="vehicle-img-container" style="height: 250px;">
                    <img src="../uploads/<?= htmlspecialchars($vehicle['image'] ?: 'default.jpg') ?>" alt="<?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?>">
                </div>
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h3 class="fw-bold mb-1"><?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?></h3>
                            <span class="badge bg-light text-dark border"><?= htmlspecialchars($vehicle['year']) ?></span>
                        </div>
                        <div class="text-end">
                            <h3 class="text-primary fw-bold mb-0" id="price_per_day" data-price="<?= $vehicle['price_per_day'] ?>">Rs. <?= htmlspecialchars($vehicle['price_per_day']) ?></h3>
                            <small class="text-muted">/ day</small>
                        </div>
                    </div>
                    
                    <hr class="my-4 text-muted">
                    
                    <h6 class="fw-bold mb-3">Vehicle Details</h6>
                    <p class="text-muted small lh-lg mb-0"><?= htmlspecialchars($vehicle['description']) ?></p>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card card-premium border-0">
                <div class="card-body p-5">
                    <h4 class="fw-bold mb-4">Complete Your Booking</h4>
                    
                    <div class="progress-indicators" id="progressIndicators">
                        <div class="step-indicator active" id="indicator1">1</div>
                        <div class="step-indicator" id="indicator2">2</div>
                        <div class="step-indicator" id="indicator3">3</div>
                    </div>

                    <form method="POST" action="" enctype="multipart/form-data" id="bookingForm">
                        
                        <!-- STEP 1: Dates -->
                        <div class="step-container active" id="step1">
                            <h5 class="fw-bold mb-3"><i class="fa-regular fa-calendar-alt text-primary me-2"></i>Select Dates</h5>
                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">Pick-up Date <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="fa-regular fa-calendar text-muted"></i></span>
                                        <input type="date" name="start_date" id="start_date" class="form-control form-control-premium border-start-0 ps-0" required min="<?= date('Y-m-d') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">Drop-off Date <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="fa-regular fa-calendar-check text-muted"></i></span>
                                        <input type="date" name="end_date" id="end_date" class="form-control form-control-premium border-start-0 ps-0" required min="<?= date('Y-m-d') ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end mt-4">
                                <button type="button" class="btn btn-premium px-4" onclick="nextStep(2)">Next Step <i class="fa-solid fa-arrow-right ms-2"></i></button>
                            </div>
                        </div>

                        <!-- STEP 2: Customer Details -->
                        <div class="step-container" id="step2">
                            <h5 class="fw-bold mb-3"><i class="fa-solid fa-map-location-dot text-primary me-2"></i>Trip & Contact Details</h5>
                            
                            <div class="row g-4 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">Contact Number <span class="text-danger">*</span></label>
                                    <input type="text" name="customer_number" id="customer_number" class="form-control form-control-premium" value="<?= htmlspecialchars($user_phone) ?>" required placeholder="+1 234 567 890">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">Pickup Location <span class="text-danger">*</span></label>
                                    <input type="text" name="customer_location" id="customer_location" class="form-control form-control-premium" required placeholder="Airport, Hotel, etc.">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">Destination <span class="text-danger">*</span></label>
                                <input type="text" name="destination" id="destination" class="form-control form-control-premium" required placeholder="Where will you be driving?">
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-medium">Special Requests or Message <span class="text-muted small fw-normal">(Optional)</span></label>
                                <textarea name="message" class="form-control form-control-premium" rows="3" placeholder="Any specific requirements..."></textarea>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-outline-secondary px-4" onclick="prevStep(1)"><i class="fa-solid fa-arrow-left me-2"></i> Back</button>
                                <button type="button" class="btn btn-premium px-4" onclick="validateStep2()">Next Step <i class="fa-solid fa-arrow-right ms-2"></i></button>
                            </div>
                        </div>

                        <!-- STEP 3: License & Submit -->
                        <div class="step-container" id="step3">
                            <h5 class="fw-bold mb-3"><i class="fa-solid fa-id-card text-primary me-2"></i>Document Verification</h5>
                            
                            <div class="mb-5">
                                <label class="form-label fw-medium">Driving License <span class="text-danger">*</span> <span class="text-muted small fw-normal">(JPG, PNG, PDF)</span></label>
                                <input type="file" name="license" id="license" class="form-control form-control-premium" accept=".jpg,.jpeg,.png,.pdf" required>
                                <div class="form-text mt-2"><i class="fa-solid fa-shield-halved me-1 text-success"></i> Your documents are securely stored and encrypted.</div>
                            </div>

                            <div class="bg-light rounded-4 p-4 mb-4 border shadow-sm">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-dark fs-5">Estimated Total</span>
                                    <span class="text-primary fw-bold fs-3">Rs. <span id="total_display">0.00</span></span>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-outline-secondary px-4" onclick="prevStep(2)"><i class="fa-solid fa-arrow-left me-2"></i> Back</button>
                                <button type="submit" class="btn btn-premium px-4 fs-5" id="submitBtn">Submit Booking Request <i class="fa-solid fa-check ms-2"></i></button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const totalDisplay = document.getElementById('total_display');
    const pricePerDay = parseFloat(document.getElementById('price_per_day').getAttribute('data-price'));

    function calculateTotal() {
        const start = new Date(startDateInput.value);
        const end = new Date(endDateInput.value);
        
        if (start && end && end > start) {
            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 
            const total = diffDays * pricePerDay;
            totalDisplay.innerText = total.toFixed(2);
            return true;
        } else {
            totalDisplay.innerText = "0.00";
            return false;
        }
    }

    startDateInput.addEventListener('change', calculateTotal);
    endDateInput.addEventListener('change', calculateTotal);

    // Form Steps logic
    function nextStep(step) {
        if (step === 2) {
            // validate step 1
            if (!startDateInput.value || !endDateInput.value) {
                alert("Please select both pick-up and drop-off dates.");
                return;
            }
            if (!calculateTotal()) {
                alert("End date must be after pick-up date.");
                return;
            }
        }
        showStep(step);
    }

    function prevStep(step) {
        showStep(step);
    }
    
    function validateStep2() {
        if (!document.getElementById('customer_number').value || 
            !document.getElementById('customer_location').value || 
            !document.getElementById('destination').value) {
            alert("Please fill in all required fields (Contact Number, Location, Destination).");
            return;
        }
        nextStep(3);
    }

    function showStep(step) {
        // hide all
        document.getElementById('step1').classList.remove('active');
        document.getElementById('step2').classList.remove('active');
        document.getElementById('step3').classList.remove('active');
        
        // update indicators
        document.getElementById('indicator1').classList.remove('active', 'completed');
        document.getElementById('indicator2').classList.remove('active', 'completed');
        document.getElementById('indicator3').classList.remove('active', 'completed');
        
        if (step > 1) document.getElementById('indicator1').classList.add('completed');
        if (step > 2) document.getElementById('indicator2').classList.add('completed');
        
        // show target
        document.getElementById('step' + step).classList.add('active');
        document.getElementById('indicator' + step).classList.add('active');
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>