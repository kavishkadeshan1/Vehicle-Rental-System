<?php
// admin/manage_vehicles.php
session_start();
require '../config.php';

// 🛑 STRICT SECURITY CHECK
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';
if (isset($_SESSION['temp_msg'])) {
    $message = $_SESSION['temp_msg'];
    unset($_SESSION['temp_msg']);
}

// --- 1. Handle Add New Vehicle (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_vehicle'])) {
    $title = trim($_POST['title']);
    $brand = trim($_POST['brand']);
    $model = trim($_POST['model']);
    $year = intval($_POST['year']);
    $price_per_day = floatval($_POST['price_per_day']);
    $location = trim($_POST['location']);
    $description = trim($_POST['description']);

    // Handle Image Upload
    $upload_dir = '../uploads/';
    $image_name = $_FILES['image']['name'];
    $image_tmp = $_FILES['image']['tmp_name'];
    $image_size = $_FILES['image']['size'];
    $image_ext = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
    
    $allowed_exts = array("jpg", "jpeg", "png", "webp");
    
    if (in_array($image_ext, $allowed_exts) && $image_size < 5000000) { // Max 5MB
        // Create a unique file name
        $new_image_name = uniqid('veh_', true) . '.' . $image_ext;
        $destination = $upload_dir . $new_image_name;
        
        if (move_uploaded_file($image_tmp, $destination)) {
            // Insert into database
            $stmt = $conn->prepare("INSERT INTO vehicles (title, brand, model, year, price_per_day, location, description, image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'available')");
            $stmt->bind_param("sssidsss", $title, $brand, $model, $year, $price_per_day, $location, $description, $new_image_name);
            
            if ($stmt->execute()) {
                $_SESSION['temp_msg'] = "<div class='alert alert-success'>Vehicle Added Successfully!</div>";
                header("Location: manage_vehicles.php");
                exit();
            } else {
                $_SESSION['temp_msg'] = "<div class='alert alert-danger'>Database error: Could not add vehicle.</div>";
                header("Location: manage_vehicles.php");
                exit();
            }
            $stmt->close();
        } else {
            $_SESSION['temp_msg'] = "<div class='alert alert-danger'>Failed to upload image. Make sure the 'uploads' folder exists and has write permissions.</div>";
            header("Location: manage_vehicles.php");
            exit();
        }
    } else {
        $_SESSION['temp_msg'] = "<div class='alert alert-danger'>Invalid image format (JPG, PNG, WEBP only) or file too large (Max 5MB).</div>";
        header("Location: manage_vehicles.php");
        exit();
    }
}

// --- 1b. Handle Edit Vehicle (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_vehicle'])) {
    $edit_id = intval($_POST['edit_id']);
    $title = trim($_POST['title']);
    $brand = trim($_POST['brand']);
    $model = trim($_POST['model']);
    $year = intval($_POST['year']);
    $price_per_day = floatval($_POST['price_per_day']);
    $location = trim($_POST['location']);
    $description = trim($_POST['description']);

    // Check if a new image was uploaded
    if (!empty($_FILES['new_image']['name'])) {
        $upload_dir = '../uploads/';
        $image_ext = strtolower(pathinfo($_FILES['new_image']['name'], PATHINFO_EXTENSION));
        $allowed_exts = array("jpg", "jpeg", "png", "webp");
        if (in_array($image_ext, $allowed_exts) && $_FILES['new_image']['size'] < 5000000) {
            $new_image_name = uniqid('veh_', true) . '.' . $image_ext;
            move_uploaded_file($_FILES['new_image']['tmp_name'], $upload_dir . $new_image_name);
            $stmt = $conn->prepare("UPDATE vehicles SET title=?, brand=?, model=?, year=?, price_per_day=?, location=?, description=?, image=? WHERE id=?");
            $stmt->bind_param("sssidsssi", $title, $brand, $model, $year, $price_per_day, $location, $description, $new_image_name, $edit_id);
        } else {
            $_SESSION['temp_msg'] = "<div class='alert alert-danger'>Invalid image. Vehicle data was NOT updated.</div>";
            header("Location: manage_vehicles.php");
            exit();
        }
    } else {
        $stmt = $conn->prepare("UPDATE vehicles SET title=?, brand=?, model=?, year=?, price_per_day=?, location=?, description=? WHERE id=?");
        $stmt->bind_param("sssidssi", $title, $brand, $model, $year, $price_per_day, $location, $description, $edit_id);
    }

    if ($stmt->execute()) {
        $_SESSION['temp_msg'] = "<div class='alert alert-success'>Vehicle updated successfully!</div>";
    } else {
        $_SESSION['temp_msg'] = "<div class='alert alert-danger'>Could not update vehicle: " . $conn->error . "</div>";
    }
    $stmt->close();
    header("Location: manage_vehicles.php");
    exit();
}


// --- 2. Handle Action commands (Delete, Hide, Unhide) ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $target_id = intval($_GET['id']);
    
    if ($action == 'delete') {
        // 1. Delete associated payments first (if any) because payments depend on bookings
        $del_pay_stmt = $conn->prepare("DELETE FROM payments WHERE booking_id IN (SELECT id FROM bookings WHERE vehicle_id = ?)");
        $del_pay_stmt->bind_param("i", $target_id);
        $del_pay_stmt->execute();
        $del_pay_stmt->close();

        // 2. Delete associated bookings
        $del_book_stmt = $conn->prepare("DELETE FROM bookings WHERE vehicle_id = ?");
        $del_book_stmt->bind_param("i", $target_id);
        $del_book_stmt->execute();
        $del_book_stmt->close();

        // 3. Delete the vehicle itself
        $del_stmt = $conn->prepare("DELETE FROM vehicles WHERE id = ?");
        $del_stmt->bind_param("i", $target_id);
        if ($del_stmt->execute()) {
            $message = "<div class='alert alert-success'>Vehicle and all associated booking records deleted permanently.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Failed to delete vehicle: " . $conn->error . "</div>";
        }
        $del_stmt->close();
    } elseif ($action == 'hide') {
        $stmt = $conn->prepare("UPDATE vehicles SET is_hidden = 1 WHERE id = ?");
        $stmt->bind_param("i", $target_id);
        if ($stmt->execute()) $message = "<div class='alert alert-success'>Vehicle has been hidden from customer feed.</div>";
        $stmt->close();
    } elseif ($action == 'unhide') {
        $stmt = $conn->prepare("UPDATE vehicles SET is_hidden = 0 WHERE id = ?");
        $stmt->bind_param("i", $target_id);
        if ($stmt->execute()) $message = "<div class='alert alert-success'>Vehicle is now visible to customers.</div>";
        $stmt->close();
    }
}

// --- Fetch All Vehicles ---
$sql = "SELECT * FROM vehicles ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Vehicles - Premium Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
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
                <li class="nav-item me-3"><a class="nav-link active fw-bold" href="manage_vehicles.php">Vehicles</a></li>
                <li class="nav-item me-3"><a class="nav-link" href="manage_bookings.php">Bookings</a></li>
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
            <h2 class="fw-bold mb-1">Manage Vehicles</h2>
            <p class="text-muted">Add, update, or remove vehicles from your fleet.</p>
        </div>
    </div>
    
    <?= $message ?>

    <div class="card card-premium border-0 mb-5">
        <div class="card-header bg-transparent border-bottom-0 pt-4 pb-0">
            <h5 class="fw-bold mb-0"><i class="fa-solid fa-plus-circle text-success me-2"></i>Add New Vehicle</h5>
        </div>
        <div class="card-body p-4">
            <form method="POST" action="manage_vehicles.php" enctype="multipart/form-data">
                <input type="hidden" name="add_vehicle" value="1">
                
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-medium">Display Title</label>
                        <input type="text" name="title" class="form-control form-control-premium" placeholder="e.g. Honda Civic 2022 Auto" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-medium">Brand</label>
                        <select name="brand" class="form-select form-control-premium" required>
                            <option value="">Select Brand</option>
                            <option value="Toyota">Toyota</option>
                            <option value="Suzuki">Suzuki</option>
                            <option value="Honda">Honda</option>
                            <option value="Nissan">Nissan</option>
                            <option value="Mitsubishi">Mitsubishi</option>
                            <option value="Mazda">Mazda</option>
                            <option value="BMW">BMW</option>
                            <option value="Mercedes-Benz">Mercedes-Benz</option>
                            <option value="Audi">Audi</option>
                            <option value="Hyundai">Hyundai</option>
                            <option value="Kia">Kia</option>
                            <option value="Tata">Tata</option>
                            <option value="Micro">Micro</option>
                            <option value="Bajaj">Bajaj</option>
                            <option value="Isuzu">Isuzu</option>
                            <option value="Land Rover">Land Rover</option>
                            <option value="Jeep">Jeep</option>
                            <option value="Ford">Ford</option>
                            <option value="Volkswagen">Volkswagen</option>
                            <option value="Subaru">Subaru</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-medium">Model</label>
                        <input type="text" name="model" class="form-control form-control-premium" placeholder="e.g. Civic" required>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <label class="form-label fw-medium">Year</label>
                        <input type="number" name="year" class="form-control form-control-premium" min="2000" max="<?= date('Y') + 1 ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium">Price Per Day (Rs.)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">Rs.</span>
                            <input type="number" step="0.01" name="price_per_day" class="form-control form-control-premium border-start-0 ps-0" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium">Location</label>
                        <input type="text" name="location" class="form-control form-control-premium" placeholder="e.g. Colombo, NY..." required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium">Vehicle Image</label>
                        <input type="file" name="image" class="form-control form-control-premium" accept=".jpg,.jpeg,.png,.webp" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-medium">Description / Features</label>
                    <textarea name="description" class="form-control form-control-premium" rows="3" placeholder="A/C, 5 Seats, Automatic..." required></textarea>
                </div>

                <button type="submit" class="btn btn-premium px-5 py-2"><i class="fa-solid fa-cloud-arrow-up me-2"></i>Upload & Save Vehicle</button>
            </form>
        </div>
    </div>

    <div class="card card-premium border-0">
        <div class="card-header bg-transparent border-bottom-0 pt-4 pb-0">
            <h5 class="fw-bold mb-0"><i class="fa-solid fa-car-side text-primary me-2"></i>Current Fleet</h5>
        </div>
        <div class="card-body p-4 table-responsive">
            <table class="table table-hover align-middle border-top">
                <thead class="table-light">
                    <tr>
                        <th class="text-muted fw-bold text-uppercase small py-3">Image</th>
                        <th class="text-muted fw-bold text-uppercase small py-3">Title / Details</th>
                        <th class="text-muted fw-bold text-uppercase small py-3">Price/Day</th>
                        <th class="text-muted fw-bold text-uppercase small py-3">Location</th>
                        <th class="text-muted fw-bold text-uppercase small py-3">Status</th>
                        <th class="text-muted fw-bold text-uppercase small py-3 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($vehicle = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <img src="../uploads/<?= htmlspecialchars($vehicle['image']) ?>" alt="Car" class="rounded shadow-sm" style="width: 80px; height: 50px; object-fit: cover;">
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($vehicle['title']) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($vehicle['brand']) ?> | <?= $vehicle['year'] ?></div>
                                </td>
                                <td class="fw-bold text-primary">Rs. <?= htmlspecialchars($vehicle['price_per_day']) ?></td>
                                <td><i class="fa-solid fa-location-dot text-danger me-1"></i> <?= htmlspecialchars($vehicle['location']) ?></td>
                                <td>
                                    <?php if (isset($vehicle['is_hidden']) && $vehicle['is_hidden']): ?>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-3 py-2"><i class="fa-solid fa-eye-slash me-1"></i>Hidden</span>
                                    <?php elseif ($vehicle['status'] == 'available'): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-2"><i class="fa-solid fa-check-circle me-1"></i>Available</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-3 py-2"><i class="fa-solid fa-lock me-1"></i>Booked</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <?php
                                            $vData = htmlspecialchars(json_encode([
                                                'id'          => $vehicle['id'],
                                                'title'       => $vehicle['title'],
                                                'brand'       => $vehicle['brand'],
                                                'model'       => $vehicle['model'],
                                                'year'        => $vehicle['year'],
                                                'price'       => $vehicle['price_per_day'],
                                                'location'    => $vehicle['location'],
                                                'description' => $vehicle['description'],
                                            ]));
                                        ?>
                                        <button type="button" class="btn btn-sm btn-warning" title="Edit Vehicle" onclick="openEditModal(this)" data-vehicle="<?= $vData ?>">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <?php if (isset($vehicle['is_hidden']) && $vehicle['is_hidden']): ?>
                                            <a href="manage_vehicles.php?action=unhide&id=<?= $vehicle['id'] ?>" class="btn btn-sm btn-success" title="Show in Feed"><i class="fa-solid fa-eye"></i></a>
                                        <?php else: ?>
                                            <a href="manage_vehicles.php?action=hide&id=<?= $vehicle['id'] ?>" class="btn btn-sm btn-secondary" title="Hide from Feed"><i class="fa-solid fa-eye-slash"></i></a>
                                        <?php endif; ?>
                                        <a href="manage_vehicles.php?action=delete&id=<?= $vehicle['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this vehicle permanently?');" title="Delete"><i class="fa-solid fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted">No vehicles in the system yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Edit Vehicle Modal -->
<div class="modal fade" id="editVehicleModal" tabindex="-1" aria-labelledby="editVehicleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header bg-light border-bottom border-light pt-4 pb-3 px-4">
                <h5 class="modal-title fw-bold" id="editVehicleModalLabel"><i class="fa-solid fa-pen-to-square text-warning me-2"></i>Edit Vehicle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST" action="manage_vehicles.php" enctype="multipart/form-data">
                    <input type="hidden" name="edit_vehicle" value="1">
                    <input type="hidden" name="edit_id" id="edit_id">

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Display Title</label>
                            <input type="text" name="title" id="edit_title" class="form-control form-control-premium" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Brand</label>
                            <select name="brand" id="edit_brand" class="form-select form-control-premium" required>
                                <option value="">Select Brand</option>
                                <?php
                                $brands = ['Toyota','Suzuki','Honda','Nissan','Mitsubishi','Mazda','BMW','Mercedes-Benz','Audi','Hyundai','Kia','Tata','Micro','Bajaj','Isuzu','Land Rover','Jeep','Ford','Volkswagen','Subaru','Other'];
                                foreach ($brands as $b) echo "<option value='$b'>$b</option>";
                                ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Model</label>
                            <input type="text" name="model" id="edit_model" class="form-control form-control-premium" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label fw-medium">Year</label>
                            <input type="number" name="year" id="edit_year" class="form-control form-control-premium" min="2000" max="<?= date('Y') + 1 ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-medium">Price Per Day (Rs.)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">Rs.</span>
                                <input type="number" step="0.01" name="price_per_day" id="edit_price" class="form-control form-control-premium border-start-0 ps-0" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Location</label>
                            <input type="text" name="location" id="edit_location" class="form-control form-control-premium" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Description / Features</label>
                        <textarea name="description" id="edit_description" class="form-control form-control-premium" rows="3" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Replace Image <span class="text-muted small fw-normal">(Leave empty to keep current image)</span></label>
                        <input type="file" name="new_image" class="form-control form-control-premium" accept=".jpg,.jpeg,.png,.webp">
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary px-4 rounded-pill" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning px-4 rounded-pill fw-bold"><i class="fa-solid fa-save me-2"></i>Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openEditModal(btn) {
    const v = JSON.parse(btn.getAttribute('data-vehicle'));
    document.getElementById('edit_id').value          = v.id;
    document.getElementById('edit_title').value       = v.title;
    document.getElementById('edit_model').value       = v.model;
    document.getElementById('edit_year').value        = v.year;
    document.getElementById('edit_price').value       = v.price;
    document.getElementById('edit_location').value   = v.location;
    document.getElementById('edit_description').value = v.description;
    // Set brand dropdown
    const brandSelect = document.getElementById('edit_brand');
    for (let opt of brandSelect.options) {
        opt.selected = (opt.value === v.brand);
    }
    new bootstrap.Modal(document.getElementById('editVehicleModal')).show();
}
</script>

</body>
</html>