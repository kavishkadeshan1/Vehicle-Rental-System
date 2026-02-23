<?php
$conn = new mysqli('localhost', 'root', '', 'vehicle_rent_system');
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$sql = "ALTER TABLE bookings 
        ADD COLUMN customer_number VARCHAR(20) NULL,
        ADD COLUMN customer_location VARCHAR(255) NULL,
        ADD COLUMN destination VARCHAR(255) NULL,
        ADD COLUMN message TEXT NULL";

if ($conn->query($sql)) {
    echo "Success: customer_number, customer_location, destination, message columns added to bookings table.";
} else {
    echo "Error: " . $conn->error;
}
$conn->close();
?>
