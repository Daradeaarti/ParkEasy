<?php
session_start();
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

require_once "config/config.php";

$user = $_SESSION["user"];
$userEmail = $user["email"];

// Fetch location details from query or defaults
$locationName = isset($_GET['name']) ? trim($_GET['name']) : '';
$passedPrice = isset($_GET['price']) ? (float)$_GET['price'] : 30.00;

$locationInfo = null;
if (!empty($locationName)) {
    $stmtLoc = $conn->prepare("SELECT * FROM locations WHERE name = ? LIMIT 1");
    if ($stmtLoc) {
        $stmtLoc->bind_param("s", $locationName);
        $stmtLoc->execute();
        $resLoc = $stmtLoc->get_result();
        if ($resLoc && $resLoc->num_rows > 0) {
            $locationInfo = $resLoc->fetch_assoc();
        }
        $stmtLoc->close();
    }
}

$twoWheelerRate = $locationInfo && isset($locationInfo['two_wheeler_price']) ? (float)$locationInfo['two_wheeler_price'] : 15.00;
$fourWheelerRate = $locationInfo && isset($locationInfo['four_wheeler_price']) ? (float)$locationInfo['four_wheeler_price'] : ($locationInfo ? (float)$locationInfo['price'] : $passedPrice);
$availableSlots = $locationInfo ? (int)$locationInfo['available_slots'] : 25;
$totalSlots = $locationInfo && isset($locationInfo['total_slots']) ? (int)$locationInfo['total_slots'] : 50;

$errorMessage = "";

// Process Booking Submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["location_name"], $_POST["date"], $_POST["entry_time"], $_POST["exit_time"], $_POST["vehicle_number"], $_POST["vehicle_type"], $_POST["payment_method"])) {
    $location_name = trim($_POST["location_name"]);
    $date = $_POST["date"];
    $entry_time = $_POST["entry_time"];
    $exit_time = $_POST["exit_time"];
    $vehicle_number = strtoupper(trim($_POST["vehicle_number"]));
    $vehicle_type = $_POST["vehicle_type"];
    $payment_method = $_POST["payment_method"];

    // Compute duration in hours
    $entryTimestamp = strtotime("$date $entry_time");
    $exitTimestamp = strtotime("$date $exit_time");
    if ($exitTimestamp <= $entryTimestamp) {
        $exitTimestamp += 86400; // Next day
    }
    $diffHours = max(1, ceil(($exitTimestamp - $entryTimestamp) / 3600));

    // Determine hourly rate and total
    $hourlyRate = ($vehicle_type === "two_wheeler") ? $twoWheelerRate : $fourWheelerRate;
    $total_amount = $diffHours * $hourlyRate;

    // Generate unique booking code (e.g. PE-84920)
    $booking_code = 'PE-' . strtoupper(substr(uniqid(), -5));
    $status = 'Confirmed';

    // Insert into bookings table
    $sql = "INSERT INTO bookings (booking_code, email, location_name, date, entry_time, exit_time, vehicle_number, vehicle_type, payment_method, total_amount, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("sssssssssds", $booking_code, $userEmail, $location_name, $date, $entry_time, $exit_time, $vehicle_number, $vehicle_type, $payment_method, $total_amount, $status);

        if ($stmt->execute()) {
            $newBookingId = $stmt->insert_id;

            // Decrement available slots in location atomically
            $updateSlot = $conn->prepare("UPDATE locations SET available_slots = GREATEST(available_slots - 1, 0) WHERE name = ?");
            if ($updateSlot) {
                $updateSlot->bind_param("s", $location_name);
                $updateSlot->execute();
                $updateSlot->close();
            }

            // Store in session for quick receipt access
            $_SESSION["last_booking"] = [
                'id' => $newBookingId,
                'code' => $booking_code,
                'location' => $location_name,
                'date' => $date,
                'entry_time' => $entry_time,
                'exit_time' => $exit_time,
                'vehicle_number' => $vehicle_number,
                'vehicle_type' => $vehicle_type,
                'total_amount' => $total_amount,
                'payment_method' => $payment_method
            ];

            $stmt->close();
            $conn->close();
            header("Location: booked.php");
            exit();
        } else {
            $errorMessage = "Could not store booking: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $errorMessage = "Database prepare error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Booking - ParkEasy</title>
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Manrope', sans-serif;
            background-color: #f1f5f9;
            color: #1e293b;
            min-height: 100vh;
        }

        /* Top Navbar */
        .navbar-top {
            background: #2b2b2b;
            padding: 12px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 15px rgba(0,0,0,0.12);
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .navbar-logo {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            object-fit: cover;
        }

        .brand-text {
            font-family: 'Outfit', sans-serif;
            font-size: 24px;
            font-weight: 800;
            color: #ffffff;
        }

        .brand-text span {
            color: #f84646;
        }

        .navbar-links {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .nav-link {
            color: #e2e8f0;
            text-decoration: none;
            font-size: 14.5px;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
        }

        .nav-link-btn {
            background: #f84646;
            color: #ffffff !important;
            border-radius: 20px;
            padding: 8px 18px;
        }

        /* Page Layout */
        .booking-page-container {
            max-width: 900px;
            margin: 36px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 28px;
        }

        .booking-card {
            background: white;
            border-radius: 20px;
            padding: 32px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
        }

        .booking-title {
            font-family: 'Outfit', sans-serif;
            font-size: 24px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 6px;
        }

        .booking-subtitle {
            font-size: 13.5px;
            color: #64748b;
            margin-bottom: 24px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
            outline: none;
            transition: border-color 0.2s;
            background: #ffffff;
        }

        .form-control:focus {
            border-color: #f84646;
            box-shadow: 0 0 0 3px rgba(248, 70, 70, 0.15);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        /* Summary Sidebar */
        .summary-card {
            background: white;
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
            height: fit-content;
        }

        .summary-header {
            font-family: 'Outfit', sans-serif;
            font-size: 19px;
            font-weight: 800;
            color: #0f172a;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 14px;
            margin-bottom: 16px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13.5px;
            margin-bottom: 12px;
            color: #475569;
        }

        .summary-row strong {
            color: #0f172a;
        }

        .summary-total {
            border-top: 2px dashed #e2e8f0;
            padding-top: 14px;
            margin-top: 16px;
            display: flex;
            justify-content: space-between;
            align-items: baseline;
        }

        .total-amount-display {
            font-size: 28px;
            font-weight: 800;
            color: #f84646;
            font-family: 'Outfit', sans-serif;
        }

        .payment-details {
            display: none;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            margin-top: 12px;
        }

        .qr-preview-box {
            text-align: center;
            padding: 10px;
        }

        .qr-preview-box img {
            max-width: 160px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
        }

        .btn-confirm-submit {
            width: 100%;
            background: #f84646;
            color: white;
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 20px;
            font-family: 'Outfit', sans-serif;
            letter-spacing: 0.5px;
        }

        .btn-confirm-submit:hover {
            background: #dc2626;
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(248, 70, 70, 0.3);
        }

        .error-alert {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            padding: 12px;
            border-radius: 10px;
            font-size: 13.5px;
            margin-bottom: 16px;
        }

        @media (max-width: 768px) {
            .booking-page-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <!-- Top Navbar -->
    <header class="navbar-top">
        <a href="index.php" class="navbar-brand">
            <img src="assets/images/logo.jpg" alt="ParkEasy Logo" class="navbar-logo" />
            <div class="brand-text">Park<span>Easy</span></div>
        </a>
        <nav class="navbar-links">
            <a href="index.php" class="nav-link">Home</a>
            <a href="service.php" class="nav-link">Dashboard</a>
            <a href="map.php" class="nav-link">Find Parking</a>
            <a href="mybookings.php" class="nav-link">My Bookings</a>
            <a href="logout.php" class="nav-link nav-link-btn">Logout</a>
        </nav>
    </header>

    <div class="booking-page-container">
        <!-- Main Form Card -->
        <div class="booking-card">
            <h1 class="booking-title">Reserve Your Parking Spot</h1>
            <p class="booking-subtitle">Fast check-in, guaranteed slot reservation, and instant digital QR pass.</p>

            <?php if (!empty($errorMessage)): ?>
                <div class="error-alert"><?= htmlspecialchars($errorMessage) ?></div>
            <?php endif; ?>

            <form action="confirmbook.php?name=<?= urlencode($locationName) ?>&price=<?= urlencode($fourWheelerRate) ?>" method="post" id="booking-form">
                <input type="hidden" name="location_name" id="location_name" value="<?= htmlspecialchars($locationName) ?>">

                <div class="form-group">
                    <label>Selected Parking Location</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($locationName ?: 'Standard Lot') ?>" readonly style="background: #f8fafc; font-weight: 700; color: #0f172a;">
                </div>

                <div class="form-group">
                    <label for="date">Reservation Date</label>
                    <input type="date" class="form-control" id="date" name="date" required value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="entry_time">Entry Time</label>
                        <input type="time" class="form-control" id="entry_time" name="entry_time" required value="<?= date('H:i') ?>">
                    </div>
                    <div class="form-group">
                        <label for="exit_time">Exit Time</label>
                        <input type="time" class="form-control" id="exit_time" name="exit_time" required value="<?= date('H:i', strtotime('+2 hours')) ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="vehicle_type">Vehicle Type</label>
                        <select class="form-control" id="vehicle_type" name="vehicle_type" required>
                            <option value="four_wheeler" selected>Four-Wheeler (Car / SUV) - ₹<?= number_format($fourWheelerRate, 2) ?>/hr</option>
                            <option value="two_wheeler">Two-Wheeler (Bike / Scooter) - ₹<?= number_format($twoWheelerRate, 2) ?>/hr</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="vehicle_number">Vehicle Plate Number</label>
                        <input type="text" class="form-control" id="vehicle_number" name="vehicle_number" placeholder="e.g. MH 05 AB 1234" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="payment-method">Payment Method</label>
                    <select class="form-control" name="payment_method" id="payment-method" required>
                        <option value="upi" selected>UPI (GPay / PhonePe / Paytm)</option>
                        <option value="qr_code">Scan QR Code at Check-in</option>
                        <option value="credit_card">Credit Card</option>
                        <option value="debit_card">Debit Card</option>
                    </select>
                </div>

                <!-- Card Details Section -->
                <div id="card-details" class="payment-details">
                    <div class="form-group">
                        <label>Card Number</label>
                        <input type="text" class="form-control" placeholder="4532 •••• •••• 8921" maxlength="19">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Expiry (MM/YY)</label>
                            <input type="text" class="form-control" placeholder="12/28" maxlength="5">
                        </div>
                        <div class="form-group">
                            <label>CVV</label>
                            <input type="password" class="form-control" placeholder="•••" maxlength="3">
                        </div>
                    </div>
                </div>

                <!-- UPI Details Section -->
                <div id="upi-details" class="payment-details" style="display: block;">
                    <div class="form-group">
                        <label>Virtual Payment Address (VPA / UPI ID)</label>
                        <input type="text" class="form-control" placeholder="yourname@okhdfcbank" value="<?= htmlspecialchars(explode('@', $userEmail)[0]) ?>@okaxis">
                    </div>
                </div>

                <!-- QR Details Section -->
                <div id="qr-details" class="payment-details">
                    <div class="qr-preview-box">
                        <img src="assets/images/qr.png" alt="Scan to Pay">
                        <p style="font-size: 12.5px; color: #64748b; margin-top: 6px;">Scan with any UPI app to pay upon arrival</p>
                    </div>
                </div>

                <button type="submit" class="btn-confirm-submit">
                    Confirm &amp; Generate Digital Ticket
                </button>
            </form>
        </div>

        <!-- Sidebar Summary Card -->
        <aside class="summary-card">
            <h2 class="summary-header">Booking Fare Breakdown</h2>

            <div class="summary-row">
                <span>Location</span>
                <strong><?= htmlspecialchars($locationName ?: 'Kalyan Parking') ?></strong>
            </div>
            <div class="summary-row">
                <span>Vehicle Class</span>
                <strong id="summary-vehicle-text">Four-Wheeler</strong>
            </div>
            <div class="summary-row">
                <span>Rate per Hour</span>
                <strong id="summary-rate-text">₹<?= number_format($fourWheelerRate, 2) ?></strong>
            </div>
            <div class="summary-row">
                <span>Estimated Duration</span>
                <strong id="summary-duration-text">2 Hours</strong>
            </div>

            <!-- AI Occupancy Badge Container -->
            <div id="ai-booking-forecast" style="margin: 18px 0;"></div>

            <div class="summary-total">
                <div>
                    <span style="font-size: 13px; font-weight: 700; color: #475569;">Total Payable</span>
                    <p style="font-size: 11.5px; color: #94a3b8;">Taxes &amp; Slot Guarantee Included</p>
                </div>
                <div class="total-amount-display" id="summary-total-display">₹<?= number_format($fourWheelerRate * 2, 2) ?></div>
            </div>
        </aside>
    </div>

    <!-- AI Scripts -->
    <script src="assets/js/ai_predictor.js"></script>
    <script src="assets/js/ai_assistant.js"></script>

    <script>
        const twoWheelerPrice = <?= $twoWheelerRate ?>;
        const fourWheelerPrice = <?= $fourWheelerRate ?>;
        const availableSlots = <?= $availableSlots ?>;
        const totalSlots = <?= $totalSlots ?>;

        const entryInput = document.getElementById('entry_time');
        const exitInput = document.getElementById('exit_time');
        const vehicleSelect = document.getElementById('vehicle_type');
        const paymentSelect = document.getElementById('payment-method');

        const cardDetails = document.getElementById('card-details');
        const upiDetails = document.getElementById('upi-details');
        const qrDetails = document.getElementById('qr-details');

        function recalculate() {
            const entry = entryInput.value;
            const exit = exitInput.value;
            const vehicle = vehicleSelect.value;

            const calc = window.ParkEasyAI.calculateDurationAndFee(entry, exit, vehicle, twoWheelerPrice, fourWheelerPrice);

            document.getElementById('summary-vehicle-text').innerText = (vehicle === 'two_wheeler') ? 'Two-Wheeler' : 'Four-Wheeler';
            document.getElementById('summary-rate-text').innerText = '₹' + calc.hourlyRate.toFixed(2);
            document.getElementById('summary-duration-text').innerText = calc.hours + (calc.hours === 1 ? ' Hour' : ' Hours');
            document.getElementById('summary-total-display').innerText = '₹' + calc.total.toFixed(2);

            // Update AI Forecast based on chosen time
            const dateVal = document.getElementById('date').value;
            const forecastContainer = document.getElementById('ai-booking-forecast');
            if (forecastContainer && window.ParkEasyAI) {
                const pred = window.ParkEasyAI.predictOccupancy(availableSlots, totalSlots, dateVal, entry);
                forecastContainer.innerHTML = `
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid ${pred.color}; border-radius: 12px; padding: 12px; font-size: 12.5px;">
                        <div style="font-weight: 800; color: #334155; margin-bottom: 2px;">🤖 AI Traffic Forecast</div>
                        <div style="color: ${pred.color}; font-weight: 700; margin-bottom: 4px;">${pred.status} (${pred.likelihood}% Spot Probability)</div>
                        <div style="color: #64748b; font-size: 11.5px; line-height: 1.4;">${pred.recommendation}</div>
                    </div>
                `;
            }
        }

        entryInput.addEventListener('change', recalculate);
        exitInput.addEventListener('change', recalculate);
        vehicleSelect.addEventListener('change', recalculate);
        document.getElementById('date').addEventListener('change', recalculate);

        // Payment Method toggle
        paymentSelect.addEventListener('change', function() {
            cardDetails.style.display = 'none';
            upiDetails.style.display = 'none';
            qrDetails.style.display = 'none';

            if (this.value === 'credit_card' || this.value === 'debit_card') {
                cardDetails.style.display = 'block';
            } else if (this.value === 'upi') {
                upiDetails.style.display = 'block';
            } else if (this.value === 'qr_code') {
                qrDetails.style.display = 'block';
            }
        });

        // Run initial calculation on page ready
        window.addEventListener('DOMContentLoaded', recalculate);
    </script>
</body>

</html>