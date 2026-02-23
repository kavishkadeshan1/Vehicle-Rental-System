<?php
// customer/profile.php
session_start();

// Security Check: Kick out unauthorized users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

require '../config.php';

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $profile_pic = $_SESSION['profile_pic'] ?? null;

    // Handle File Upload
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/profiles/';
        
        // Ensure directory exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileInfo = pathinfo($_FILES['profile_pic']['name']);
        $extension = strtolower($fileInfo['extension']);
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($extension, $allowedExtensions)) {
            $newFileName = 'user_' . $user_id . '_' . time() . '.' . $extension;
            $uploadPath = $uploadDir . $newFileName;

            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $uploadPath)) {
                // Remove old profile picture if it exists
                if ($profile_pic && file_exists('../uploads/profiles/' . $profile_pic)) {
                    unlink('../uploads/profiles/' . $profile_pic);
                }
                $profile_pic = $newFileName;
            } else {
                $error = 'Failed to upload image. Check permissions.';
            }
        } else {
            $error = 'Invalid file type. Only JPG, PNG, and GIF are allowed.';
        }
    }

    if (empty($error)) {
        // Update database
        $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, profile_pic = ? WHERE id = ?");
        $stmt->bind_param("sssi", $name, $phone, $profile_pic, $user_id);
        
        if ($stmt->execute()) {
            $success = 'Profile updated successfully!';
            $_SESSION['name'] = $name;
            $_SESSION['profile_pic'] = $profile_pic;
        } else {
            $error = 'Failed to update profile to database.';
        }
        $stmt->close();
    }
}

// Fetch current user data
$stmt = $conn->prepare("SELECT name, email, phone, profile_pic FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
$stmt->close();

// Update session variable just in case
$_SESSION['profile_pic'] = $userData['profile_pic'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Premium Vehicle Rent</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .profile-pic-container {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto 20px;
            border: 4px solid var(--border-color);
            background-color: var(--bg-light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: var(--text-muted);
        }
        .profile-pic-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
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
                    <a class="nav-link" href="vehicles.php">Vehicles</a>
                </li>
                <li class="nav-item me-3">
                    <a class="nav-link" href="bookings.php">My Bookings</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center active" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
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
                        <li><a class="dropdown-item py-2 active" href="profile.php"><i class="fa-regular fa-id-badge me-2 text-muted"></i> Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item py-2 text-danger" href="../logout.php"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card card-premium">
                <div class="card-header bg-white text-center pb-0 border-0 pt-4">
                    <h2 class="fw-bold mb-0">My Profile</h2>
                </div>
                <div class="card-body p-5 pt-4">
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <form action="profile.php" method="POST" enctype="multipart/form-data">
                        
                        <div class="text-center mb-4">
                            <div class="profile-pic-container shadow-sm">
                                <?php if (!empty($userData['profile_pic'])): ?>
                                    <img src="../uploads/profiles/<?= htmlspecialchars($userData['profile_pic']) ?>" alt="Profile Picture">
                                <?php else: ?>
                                    <i class="fa-solid fa-user"></i>
                                <?php endif; ?>
                            </div>
                            <label for="profile_pic" class="btn btn-outline-premium btn-sm">
                                <i class="fa-solid fa-camera me-1"></i> Change Picture
                            </label>
                            <input type="file" id="profile_pic" name="profile_pic" class="d-none" accept="image/jpeg, image/png, image/gif">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fa-regular fa-user text-muted"></i></span>
                                <input type="text" name="name" class="form-control form-control-premium border-start-0 ps-0" value="<?= htmlspecialchars($userData['name']) ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email Address (Read-only)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fa-regular fa-envelope text-muted"></i></span>
                                <input type="email" class="form-control form-control-premium border-start-0 ps-0 bg-light" value="<?= htmlspecialchars($userData['email']) ?>" readonly>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-phone text-muted"></i></span>
                                <input type="text" name="phone" class="form-control form-control-premium border-start-0 ps-0" value="<?= htmlspecialchars($userData['phone']) ?>" required>
                            </div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-premium py-2">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Preview image before upload
    document.getElementById('profile_pic').addEventListener('change', function(event) {
        if (event.target.files && event.target.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                var container = document.querySelector('.profile-pic-container');
                container.innerHTML = '<img src="' + e.target.result + '" alt="Profile Picture">';
            }
            reader.readAsDataURL(event.target.files[0]);
        }
    });
</script>
</body>
</html>
