<?php
// customer/vehicles.php
session_start();
require '../config.php'; // Go up one folder to reach config

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

// 1. Base Query: Only show available and non-hidden vehicles
$sql = "SELECT * FROM vehicles WHERE status = 'available' AND is_hidden = 0";
$conditions = [];
$params = [];
$types = "";

// 2. Dynamic Filtering Logic
if (!empty($_GET['location'])) {
    $conditions[] = "location LIKE ?";
    $params[] = "%" . $_GET['location'] . "%";
    $types .= "s"; // string
}

if (!empty($_GET['brand'])) {
    $conditions[] = "brand = ?";
    $params[] = $_GET['brand'];
    $types .= "s"; // string
}

if (!empty($_GET['max_price'])) {
    $conditions[] = "price_per_day <= ?";
    $params[] = $_GET['max_price'];
    $types .= "d"; // double/decimal
}

// Append conditions to the SQL query if any exist
if (count($conditions) > 0) {
    $sql .= " AND " . implode(" AND ", $conditions);
}

// 3. Prepare and Execute
$stmt = $conn->prepare($sql);

// Bind parameters dynamically using the splat operator (...)
if ($types) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// 4. Fetch user's active bookings to prevent double booking
$active_bookings_stmt = $conn->prepare("SELECT vehicle_id FROM bookings WHERE user_id = ? AND status IN ('pending', 'approved')");
$active_bookings_stmt->bind_param("i", $_SESSION['user_id']);
$active_bookings_stmt->execute();
$active_bookings_result = $active_bookings_stmt->get_result();
$user_active_vehicles = [];
while ($row = $active_bookings_result->fetch_assoc()) {
    $user_active_vehicles[] = $row['vehicle_id'];
}
$active_bookings_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Vehicles - Premium Vehicle Rent</title>
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

<div class="hero-section text-center py-5" style="border-radius: 0; margin-bottom: 0; padding: 60px 0;">
    <div class="container hero-content">
        <h1 class="hero-title mb-2" style="font-size: 2.5rem;">Explore Our Vehicles</h1>
        <p class="hero-subtitle mb-0">Find the perfect vehicle for your next rental.</p>
    </div>
</div>

<div class="container mt-5 mb-5">
    <div class="card card-premium mb-5 border-0 shadow-sm" style="margin-top: -40px; position: relative; z-index: 10;">
        <div class="card-body p-4">
            <form method="GET" action="vehicles.php" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label text-muted small fw-bold text-uppercase"><i class="fa-solid fa-location-dot me-2"></i>Location</label>
                    <input type="text" name="location" class="form-control form-control-premium" placeholder="City / Location" value="<?= htmlspecialchars($_GET['location'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted small fw-bold text-uppercase"><i class="fa-solid fa-car me-2"></i>Brand</label>
                    <select name="brand" class="form-select form-control-premium">
                        <option value="">All Brands</option>
                        <option value="Toyota" <?= (isset($_GET['brand']) && $_GET['brand'] == 'Toyota') ? 'selected' : '' ?>>Toyota</option>
                        <option value="Suzuki" <?= (isset($_GET['brand']) && $_GET['brand'] == 'Suzuki') ? 'selected' : '' ?>>Suzuki</option>
                        <option value="Honda" <?= (isset($_GET['brand']) && $_GET['brand'] == 'Honda') ? 'selected' : '' ?>>Honda</option>
                        <option value="Nissan" <?= (isset($_GET['brand']) && $_GET['brand'] == 'Nissan') ? 'selected' : '' ?>>Nissan</option>
                        <option value="Mitsubishi" <?= (isset($_GET['brand']) && $_GET['brand'] == 'Mitsubishi') ? 'selected' : '' ?>>Mitsubishi</option>
                        <option value="Mazda" <?= (isset($_GET['brand']) && $_GET['brand'] == 'Mazda') ? 'selected' : '' ?>>Mazda</option>
                        <option value="BMW" <?= (isset($_GET['brand']) && $_GET['brand'] == 'BMW') ? 'selected' : '' ?>>BMW</option>
                        <option value="Mercedes-Benz" <?= (isset($_GET['brand']) && $_GET['brand'] == 'Mercedes-Benz') ? 'selected' : '' ?>>Mercedes-Benz</option>
                        <option value="Audi" <?= (isset($_GET['brand']) && $_GET['brand'] == 'Audi') ? 'selected' : '' ?>>Audi</option>
                        <option value="Hyundai" <?= (isset($_GET['brand']) && $_GET['brand'] == 'Hyundai') ? 'selected' : '' ?>>Hyundai</option>
                        <option value="Kia" <?= (isset($_GET['brand']) && $_GET['brand'] == 'Kia') ? 'selected' : '' ?>>Kia</option>
                        <option value="Tata" <?= (isset($_GET['brand']) && $_GET['brand'] == 'Tata') ? 'selected' : '' ?>>Tata</option>
                        <option value="Micro" <?= (isset($_GET['brand']) && $_GET['brand'] == 'Micro') ? 'selected' : '' ?>>Micro</option>
                        <option value="Bajaj" <?= (isset($_GET['brand']) && $_GET['brand'] == 'Bajaj') ? 'selected' : '' ?>>Bajaj</option>
                        <option value="Isuzu" <?= (isset($_GET['brand']) && $_GET['brand'] == 'Isuzu') ? 'selected' : '' ?>>Isuzu</option>
                        <option value="Land Rover" <?= (isset($_GET['brand']) && $_GET['brand'] == 'Land Rover') ? 'selected' : '' ?>>Land Rover</option>
                        <option value="Jeep" <?= (isset($_GET['brand']) && $_GET['brand'] == 'Jeep') ? 'selected' : '' ?>>Jeep</option>
                        <option value="Ford" <?= (isset($_GET['brand']) && $_GET['brand'] == 'Ford') ? 'selected' : '' ?>>Ford</option>
                        <option value="Volkswagen" <?= (isset($_GET['brand']) && $_GET['brand'] == 'Volkswagen') ? 'selected' : '' ?>>Volkswagen</option>
                        <option value="Subaru" <?= (isset($_GET['brand']) && $_GET['brand'] == 'Subaru') ? 'selected' : '' ?>>Subaru</option>
                        <option value="Other" <?= (isset($_GET['brand']) && $_GET['brand'] == 'Other') ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted small fw-bold text-uppercase"><i class="fa-solid fa-tag me-2"></i>Max Price</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">Rs.</span>
                        <input type="number" name="max_price" class="form-control form-control-premium border-start-0 ps-0" placeholder="Per Day" value="<?= htmlspecialchars($_GET['max_price'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-premium w-100 py-2"><i class="fa-solid fa-filter me-2"></i>Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($vehicle = $result->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card card-premium h-100 border-0">
                        <div class="vehicle-img-container">
                            <span class="vehicle-badge"><i class="fa-solid fa-star text-warning me-1"></i>Premium</span>
                            <img src="../uploads/<?= htmlspecialchars($vehicle['image'] ?: 'default.jpg') ?>" alt="<?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?>">
                        </div>
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h5 class="card-title fw-bold mb-1"><?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?></h5>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($vehicle['year']) ?></span>
                                </div>
                                <div class="text-end">
                                    <h4 class="text-primary fw-bold mb-0">Rs. <?= htmlspecialchars($vehicle['price_per_day']) ?></h4>
                                    <small class="text-muted">/ day</small>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-center text-muted mb-3 mt-2 small">
                                <i class="fa-solid fa-location-dot me-2 text-danger"></i> <?= htmlspecialchars($vehicle['location']) ?>
                            </div>
                            
                            <p class="card-text text-muted small mb-4 flex-grow-1"><?= htmlspecialchars(substr($vehicle['description'], 0, 90)) ?>...</p>
                            
                            <div class="mt-auto">
                                <?php if (in_array($vehicle['id'], $user_active_vehicles)): ?>
                                    <a href="bookings.php" class="btn btn-secondary w-100 py-2 border-0">Already Booked <i class="fa-solid fa-check ms-2"></i></a>
                                <?php else: ?>
                                    <a href="book.php?id=<?= $vehicle['id'] ?>" class="btn btn-premium w-100 py-2">Book Now <i class="fa-solid fa-arrow-right ms-2"></i></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="text-center py-5 bg-white rounded-4 shadow-sm border">
                    <div class="text-muted mb-3" style="font-size: 3rem;"><i class="fa-solid fa-car-burst"></i></div>
                    <h4 class="fw-bold text-dark">No vehicles found</h4>
                    <p class="text-muted">We couldn't find any vehicles matching your search criteria.</p>
                    <a href="vehicles.php" class="btn btn-outline-premium mt-2">Clear Filters</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $stmt->close(); ?>