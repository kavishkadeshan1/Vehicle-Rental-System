<?php
// customer/payment.php
session_start();
require '../config.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
$user_id = $_SESSION['user_id'];
$message = '';

// 1. Verify this booking belongs to the user and is Approved
$stmt = $conn->prepare("SELECT * FROM bookings WHERE id = ? AND user_id = ? AND status = 'approved'");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    die("<div class='alert alert-danger text-center mt-5'>Invalid booking or not ready for payment. <a href='bookings.php'>Go back</a></div>");
}

// 2. Process Payment Form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $payment_method = $_POST['payment_method'];
    $amount = $booking['total_price'];
    
    // Generate a mock transaction ID (e.g., TXN-64a5b2c9d)
    $transaction_id = 'TXN-' . strtoupper(uniqid());

    // Insert into payments table
    $pay_stmt = $conn->prepare("INSERT INTO payments (booking_id, amount, payment_method, payment_status, transaction_id) VALUES (?, ?, ?, 'paid', ?)");
    $pay_stmt->bind_param("idss", $booking_id, $amount, $payment_method, $transaction_id);
    
    if ($pay_stmt->execute()) {
        $message = "<div class='alert alert-success'>Payment successful! Transaction ID: <strong>$transaction_id</strong>. <a href='bookings.php'>Back to Bookings</a></div>";
        // Hide the form after successful payment
        $booking = null; 
    } else {
        $message = "<div class='alert alert-danger'>Payment failed. Please try again.</div>";
    }
    $pay_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make Payment - Premium Vehicle Rent</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<style>
    /* Virtual Credit Card Styles */
    .credit-card-container {
        perspective: 1000px;
        margin: 20px auto;
        width: 100%;
        max-width: 400px;
    }
    .credit-card {
        width: 100%;
        height: 250px;
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        border-radius: 20px;
        padding: 25px;
        color: white;
        position: relative;
        box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        overflow: hidden;
        transition: transform 0.6s cubic-bezier(0.4, 0.0, 0.2, 1);
        transform-style: preserve-3d;
    }
    .credit-card::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
        transform: rotate(30deg);
        pointer-events: none;
    }
    .card-chip {
        width: 50px;
        height: 40px;
        background: linear-gradient(135deg, #eab308 0%, #ca8a04 100%);
        border-radius: 8px;
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
    }
    .card-chip::after {
        content: '';
        position: absolute;
        top: 10px; bottom: 10px;
        left: 0; right: 0;
        border-top: 1px solid rgba(0,0,0,0.2);
        border-bottom: 1px solid rgba(0,0,0,0.2);
    }
    .card-number-display {
        font-family: 'Courier New', Courier, monospace;
        font-size: 1.6rem;
        letter-spacing: 2px;
        margin-bottom: 20px;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
    }
    .card-details-row {
        display: flex;
        justify-content: space-between;
        font-family: 'Courier New', Courier, monospace;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
    }
    .card-label {
        font-size: 0.65rem;
        text-transform: uppercase;
        color: rgba(255,255,255,0.6);
        letter-spacing: 1px;
        font-family: var(--font-body);
    }
    
    /* Loading Overlay */
    .payment-overlay {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(255,255,255,0.95);
        z-index: 9999;
        display: none;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        backdrop-filter: blur(5px);
    }
    .spinner-border-xl {
        width: 4rem;
        height: 4rem;
        border-width: 0.3rem;
    }
</style>
<body>

<nav class="navbar navbar-expand-lg navbar-custom sticky-top py-3">
    <div class="container">
        <a class="navbar-brand" href="index.php"><i class="fa-solid fa-car-side text-primary me-2"></i>VehicleRent</a>
        <div class="d-flex align-items-center">
            <a href="bookings.php" class="btn btn-outline-premium btn-sm"><i class="fa-solid fa-arrow-left me-2"></i>Back to Bookings</a>
        </div>
    </div>
</nav>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="card card-premium border-0">
                <div class="card-header bg-transparent border-bottom-0 pt-5 pb-0 text-center">
                    <div class="bg-success bg-opacity-10 text-success rounded-circle d-inline-flex justify-content-center align-items-center mb-3" style="width: 70px; height: 70px; font-size: 1.8rem;">
                        <i class="fa-solid fa-shield-check"></i>
                    </div>
                    <h3 class="fw-bold">Secure Checkout</h3>
                    <p class="text-muted">Complete your payment to confirm the reservation.</p>
                </div>
                <div class="card-body p-5 pt-4">
                    <?= $message ?>
                    
                    <?php if ($booking): ?>
                        <div class="bg-light rounded-4 p-4 mb-4 text-center border">
                            <p class="text-muted mb-1 text-uppercase fw-bold small">Total Amount Due</p>
                            <h1 class="text-primary fw-bold mb-0">Rs. <?= number_format($booking['total_price'], 2) ?></h1>
                            <p class="text-muted small mt-2 mb-0">Booking Reference: #<?= str_pad($booking['id'], 6, '0', STR_PAD_LEFT) ?></p>
                        </div>

                        <form method="POST" action="">
                            <div class="mb-4">
                                <label class="form-label fw-medium">Select Payment Method</label>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <input type="radio" class="btn-check" name="payment_method" id="method_card" value="Credit Card" required checked>
                                        <label class="btn btn-outline-premium w-100 text-start p-3 d-flex align-items-center border-primary" for="method_card">
                                            <i class="fa-regular fa-credit-card fs-4 me-3 text-primary"></i>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold">Credit / Debit Card</div>
                                                <div class="small text-muted">Pay securely with your card</div>
                                            </div>
                                            <i class="fa-solid fa-circle-check text-primary fs-5"></i>
                                        </label>
                                    </div>
                                    <div class="col-12 opacity-50 pe-none">
                                        <input type="radio" class="btn-check" name="payment_method" id="method_bank" value="Bank Transfer" disabled>
                                        <label class="btn btn-outline-secondary w-100 text-start p-3 d-flex align-items-center bg-light" for="method_bank">
                                            <i class="fa-solid fa-building-columns fs-4 me-3"></i>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold">Bank Transfer</div>
                                                <div class="small text-muted">Not available for this demo</div>
                                            </div>
                                            <i class="fa-solid fa-lock text-muted fs-5"></i>
                                        </label>
                                    </div>
                                    <div class="col-12 opacity-50 pe-none">
                                        <input type="radio" class="btn-check" name="payment_method" id="method_cash" value="Cash on Pickup" disabled>
                                        <label class="btn btn-outline-secondary w-100 text-start p-3 d-flex align-items-center bg-light" for="method_cash">
                                            <i class="fa-solid fa-money-bill-wave fs-4 me-3"></i>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold">Pay on Pickup</div>
                                                <div class="small text-muted">Not available for this demo</div>
                                            </div>
                                            <i class="fa-solid fa-lock text-muted fs-5"></i>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Credit Card Input Section -->
                            <div class="card-details-section bg-light rounded-4 p-4 border mb-4">
                                <div class="alert alert-info border-0 bg-info bg-opacity-10 d-flex align-items-start mb-4">
                                    <i class="fa-solid fa-circle-info mt-1 me-3 fs-5"></i>
                                    <div>
                                        <strong class="d-block mb-1">Demo Environment Active</strong>
                                        <span class="small text-muted">Please use the following test card details:</span><br>
                                        <span class="small font-monospace bg-white px-2 py-1 rounded border d-inline-block mt-2">Card: 4242 4242 4242 4242</span>
                                        <span class="small font-monospace bg-white px-2 py-1 rounded border d-inline-block mt-2">Exp: 12/28</span>
                                        <span class="small font-monospace bg-white px-2 py-1 rounded border d-inline-block mt-2">CVC: 123</span>
                                    </div>
                                </div>
                            
                                <div class="credit-card-container">
                                    <div class="credit-card" id="virtualCard">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="card-chip"></div>
                                            <i class="fa-brands fa-cc-visa fs-1 text-white opacity-75"></i>
                                        </div>
                                        <div class="card-number-display" id="displayCardNumber">#### #### #### ####</div>
                                        <div class="card-details-row">
                                            <div>
                                                <div class="card-label">Card Holder</div>
                                                <div class="fw-bold text-uppercase text-truncate" style="max-width: 200px;" id="displayCardName">YOUR NAME</div>
                                            </div>
                                            <div class="text-end">
                                                <div class="card-label">Expires</div>
                                                <div class="fw-bold" id="displayCardExpiry">MM/YY</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label fw-medium small text-muted text-uppercase">Card Number</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-end-0"><i class="fa-regular fa-credit-card text-muted"></i></span>
                                            <input type="text" class="form-control border-start-0 ps-0" id="inputCardNumber" placeholder="0000 0000 0000 0000" maxlength="19" required>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-medium small text-muted text-uppercase">Cardholder Name</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-end-0"><i class="fa-regular fa-user text-muted"></i></span>
                                            <input type="text" class="form-control border-start-0 ps-0" id="inputCardName" placeholder="JOHN DOE" required>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-medium small text-muted text-uppercase">Expiry Date</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-end-0"><i class="fa-regular fa-calendar-alt text-muted"></i></span>
                                            <input type="text" class="form-control border-start-0 ps-0" id="inputCardExpiry" placeholder="MM/YY" maxlength="5" required>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-medium small text-muted text-uppercase">CVC / CVV</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-lock text-muted"></i></span>
                                            <input type="text" class="form-control border-start-0 ps-0" id="inputCardCvc" placeholder="123" maxlength="4" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="button" id="payBtn" class="btn btn-premium w-100 py-3 fs-5 mb-3"><i class="fa-solid fa-lock me-2"></i>Pay Rs. <?= number_format($booking['total_price'], 2) ?></button>
                            <div class="text-center">
                                <small class="text-muted"><i class="fa-solid fa-lock me-1"></i> Payments are processed via a secure AES-256 encrypted connection.</small>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div class="payment-overlay" id="paymentOverlay">
    <div class="spinner-border text-primary spinner-border-xl mb-4" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
    <h3 class="fw-bold text-dark">Processing Payment</h3>
    <p class="text-muted" id="paymentStatusText">Contacting secure gateway...</p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // --- Virtual Credit Card Interactivity ---
    const inputCardNumber = document.getElementById('inputCardNumber');
    const inputCardName = document.getElementById('inputCardName');
    const inputCardExpiry = document.getElementById('inputCardExpiry');
    
    const displayCardNumber = document.getElementById('displayCardNumber');
    const displayCardName = document.getElementById('displayCardName');
    const displayCardExpiry = document.getElementById('displayCardExpiry');

    // Format Card Number (groups of 4)
    inputCardNumber?.addEventListener('input', function (e) {
        let value = e.target.value.replace(/\D/g, '');
        let formattedValue = '';
        for (let i = 0; i < value.length; i++) {
            if (i > 0 && i % 4 === 0) formattedValue += ' ';
            formattedValue += value[i];
        }
        e.target.value = formattedValue;
        
        // Update display
        let displayStr = formattedValue;
        if(displayStr.length === 0) displayStr = '#### #### #### ####';
        displayCardNumber.innerText = displayStr;
    });

    // Format Name
    inputCardName?.addEventListener('input', function (e) {
        let value = e.target.value.toUpperCase();
        if(value.length === 0) value = 'YOUR NAME';
        displayCardName.innerText = value;
    });

    // Format Expiry (MM/YY)
    inputCardExpiry?.addEventListener('input', function (e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 2) {
            value = value.substring(0, 2) + '/' + value.substring(2, 4);
        }
        e.target.value = value;
        
        let displayStr = value;
        if(displayStr.length === 0) displayStr = 'MM/YY';
        displayCardExpiry.innerText = displayStr;
    });

    // --- Loading Simulation ---
    document.getElementById('payBtn')?.addEventListener('click', function() {
        // Basic validation
        if(!inputCardNumber.value || !inputCardName.value || !inputCardExpiry.value || !document.getElementById('inputCardCvc').value) {
            alert('Please fill in all credit card details.');
            return;
        }

        const overlay = document.getElementById('paymentOverlay');
        const statusText = document.getElementById('paymentStatusText');
        
        // Show overlay
        overlay.style.display = 'flex';
        
        // Simulate progress
        setTimeout(() => {
            statusText.innerText = "Verifying card details...";
        }, 1000);
        
        setTimeout(() => {
            statusText.innerText = "Authorizing transaction...";
        }, 2500);
        
        // Submit form after 4 seconds
        setTimeout(() => {
            statusText.innerText = "Payment Successful! Redirecting...";
            document.querySelector('form').submit();
        }, 4000);
    });
</script>
</body>
</html>