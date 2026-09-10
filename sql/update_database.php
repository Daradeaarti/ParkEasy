<?php
// update_database.php - Run once to migrate and enrich the parkeasy database
require_once __DIR__ . '/../config/config.php';

echo "=== Starting ParkEasy Database Migration ===\n";

// Helper function to check if column exists
function columnExists($conn, $table, $column) {
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && $result->num_rows > 0;
}

// 1. Update `locations` table
echo "1. Checking and updating `locations` table...\n";

if (!columnExists($conn, 'locations', 'total_slots')) {
    $conn->query("ALTER TABLE `locations` ADD COLUMN `total_slots` INT NOT NULL DEFAULT 50 AFTER `available_slots`");
    echo " - Added `total_slots` column.\n";
}

if (!columnExists($conn, 'locations', 'address')) {
    $conn->query("ALTER TABLE `locations` ADD COLUMN `address` VARCHAR(255) DEFAULT '' AFTER `name`");
    echo " - Added `address` column.\n";
}

if (!columnExists($conn, 'locations', 'amenities')) {
    $conn->query("ALTER TABLE `locations` ADD COLUMN `amenities` VARCHAR(255) DEFAULT 'CCTV, 24/7 Guard, Covered' AFTER `total_slots`");
    echo " - Added `amenities` column.\n";
}

if (!columnExists($conn, 'locations', 'rating')) {
    $conn->query("ALTER TABLE `locations` ADD COLUMN `rating` DECIMAL(2,1) DEFAULT 4.5 AFTER `amenities`");
    echo " - Added `rating` column.\n";
}

if (!columnExists($conn, 'locations', 'two_wheeler_price')) {
    $conn->query("ALTER TABLE `locations` ADD COLUMN `two_wheeler_price` DECIMAL(10,2) DEFAULT 15.00 AFTER `price`");
    echo " - Added `two_wheeler_price` column.\n";
}

if (!columnExists($conn, 'locations', 'four_wheeler_price')) {
    $conn->query("ALTER TABLE `locations` ADD COLUMN `four_wheeler_price` DECIMAL(10,2) DEFAULT 30.00 AFTER `two_wheeler_price`");
    echo " - Added `four_wheeler_price` column.\n";
}

// Update existing locations with rich information
$locationsData = [
    1 => ['total_slots' => 60, 'address' => 'Near Kalyan Station Platform 1, Station Road, Kalyan West', 'amenities' => 'CCTV, EV Charging, Covered, 24/7 Guard', 'rating' => 4.6, 'two_wheeler_price' => 20.00, 'four_wheeler_price' => 55.00],
    6 => ['total_slots' => 70, 'address' => 'Shivaji Chowk, Agra Road, Kalyan West', 'amenities' => 'CCTV, Covered, Valet Assistance', 'rating' => 4.2, 'two_wheeler_price' => 10.00, 'four_wheeler_price' => 25.00],
    7 => ['total_slots' => 50, 'address' => 'Near Ganesh Mandir Complex, Titwala East', 'amenities' => 'CCTV, Spacious, Open Air, Security Guard', 'rating' => 4.7, 'two_wheeler_price' => 20.00, 'four_wheeler_price' => 50.00],
    8 => ['total_slots' => 40, 'address' => 'Station Road Market, Titwala West', 'amenities' => 'CCTV, Covered, Fast Entry/Exit', 'rating' => 4.3, 'two_wheeler_price' => 15.00, 'four_wheeler_price' => 40.00],
    9 => ['total_slots' => 35, 'address' => 'Opposite Lotus Tower, Ganpati Mandir Marg, Titwala', 'amenities' => 'CCTV, EV Charging, Security Guard', 'rating' => 4.4, 'two_wheeler_price' => 15.00, 'four_wheeler_price' => 40.00],
    10 => ['total_slots' => 45, 'address' => 'Valdhuni Bridge Road, Kalyan East', 'amenities' => 'CCTV, 24/7 Security', 'rating' => 4.0, 'two_wheeler_price' => 10.00, 'four_wheeler_price' => 20.00],
    11 => ['total_slots' => 80, 'address' => 'KDMC Headquarters, Shankarrao Chowk, Kalyan West', 'amenities' => 'CCTV, EV Charging, Covered Multi-Level, Security Guard', 'rating' => 4.8, 'two_wheeler_price' => 15.00, 'four_wheeler_price' => 35.00],
    12 => ['total_slots' => 65, 'address' => 'Railway Colony Entrance, Kalyan Railway Station East', 'amenities' => 'CCTV, 24/7 Guard, Fast Tag Entry', 'rating' => 4.5, 'two_wheeler_price' => 10.00, 'four_wheeler_price' => 25.00],
    13 => ['total_slots' => 30, 'address' => 'Subhash Nagar, Near Ram Baug, Kalyan West', 'amenities' => 'Covered, CCTV, Friendly Staff', 'rating' => 4.1, 'two_wheeler_price' => 10.00, 'four_wheeler_price' => 20.00],
    14 => ['total_slots' => 55, 'address' => 'Netaji Chowk, Ulhasnagar 4', 'amenities' => 'CCTV, EV Charging, 24/7 Guard, Covered', 'rating' => 4.6, 'two_wheeler_price' => 30.00, 'four_wheeler_price' => 80.00],
    15 => ['total_slots' => 40, 'address' => 'Gol Maidan Road, Ulhasnagar 2', 'amenities' => 'CCTV, Security Guard', 'rating' => 4.3, 'two_wheeler_price' => 15.00, 'four_wheeler_price' => 30.00],
    16 => ['total_slots' => 50, 'address' => 'Opposite CHM College, Station Road, Ulhasnagar 3', 'amenities' => 'CCTV, Student Discount, Covered, Guard', 'rating' => 4.5, 'two_wheeler_price' => 15.00, 'four_wheeler_price' => 40.00],
];

foreach ($locationsData as $id => $data) {
    $stmt = $conn->prepare("UPDATE `locations` SET total_slots = ?, address = ?, amenities = ?, rating = ?, two_wheeler_price = ?, four_wheeler_price = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("issdddi", $data['total_slots'], $data['address'], $data['amenities'], $data['rating'], $data['two_wheeler_price'], $data['four_wheeler_price'], $id);
        $stmt->execute();
        $stmt->close();
    }
}
echo " - Enriched existing locations with addresses, ratings, amenities, and multi-vehicle pricing.\n";


// 2. Update `bookings` table
echo "2. Checking and updating `bookings` table...\n";

if (!columnExists($conn, 'bookings', 'booking_code')) {
    $conn->query("ALTER TABLE `bookings` ADD COLUMN `booking_code` VARCHAR(30) DEFAULT NULL AFTER `id`");
    echo " - Added `booking_code` column.\n";
}

if (!columnExists($conn, 'bookings', 'total_amount')) {
    $conn->query("ALTER TABLE `bookings` ADD COLUMN `total_amount` DECIMAL(10,2) DEFAULT 0.00 AFTER `payment_method`");
    echo " - Added `total_amount` column.\n";
}

if (!columnExists($conn, 'bookings', 'status')) {
    $conn->query("ALTER TABLE `bookings` ADD COLUMN `status` VARCHAR(20) DEFAULT 'Confirmed' AFTER `total_amount`");
    echo " - Added `status` column.\n";
}

if (!columnExists($conn, 'bookings', 'created_at')) {
    $conn->query("ALTER TABLE `bookings` ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `status`");
    echo " - Added `created_at` column.\n";
}

// Generate booking codes for any existing rows that don't have one
$res = $conn->query("SELECT id FROM `bookings` WHERE booking_code IS NULL OR booking_code = ''");
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $code = 'PE-' . strtoupper(substr(md5($row['id'] . time()), 0, 6));
        $conn->query("UPDATE `bookings` SET booking_code = '$code', status = 'Confirmed' WHERE id = " . $row['id']);
    }
    echo " - Generated unique booking codes for existing bookings.\n";
}

echo "=== ParkEasy Database Migration Completed Successfully! ===\n";
$conn->close();
?>
