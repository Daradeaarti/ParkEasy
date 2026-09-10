<?php
session_start();
require_once "config/config.php";

$booking = isset($_SESSION["last_booking"]) ? $_SESSION["last_booking"] : null;
$userEmail = isset($_SESSION["user"]) ? $_SESSION["user"]["email"] : '';

// If session last_booking is missing, fetch the user's latest booking from database
if (!$booking && !empty($userEmail)) {
    $res = $conn->query("SELECT * FROM bookings WHERE email = '$userEmail' ORDER BY id DESC LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $booking = [
            'id' => $row['id'],
            'code' => $row['booking_code'],
            'location' => $row['location_name'],
            'date' => $row['date'],
            'entry_time' => $row['entry_time'],
            'exit_time' => $row['exit_time'],
            'vehicle_number' => $row['vehicle_number'],
            'vehicle_type' => $row['vehicle_type'],
            'total_amount' => $row['total_amount'],
            'payment_method' => $row['payment_method']
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Booking Confirmed - ParkEasy</title>
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
            background-color: #f8fafc;
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

        /* Success Container */
        .success-wrapper {
            max-width: 620px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .success-card {
            background: white;
            border-radius: 24px;
            padding: 40px 32px;
            text-align: center;
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
        }

        /* Animated Checkmark */
        .checkmark-circle {
            width: 76px;
            height: 76px;
            border-radius: 50%;
            background: #10b981;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px auto;
            box-shadow: 0 8px 24px rgba(16, 185, 129, 0.35);
            animation: bounceIn 0.6s ease;
        }

        @keyframes bounceIn {
            0% { transform: scale(0); opacity: 0; }
            70% { transform: scale(1.15); opacity: 1; }
            100% { transform: scale(1); }
        }

        .checkmark-circle svg {
            width: 40px;
            height: 40px;
            stroke: white;
            stroke-width: 3;
            stroke-linecap: round;
            stroke-linejoin: round;
            fill: none;
        }

        .success-title {
            font-family: 'Outfit', sans-serif;
            font-size: 26px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .success-subtitle {
            font-size: 14.5px;
            color: #64748b;
            margin-bottom: 24px;
        }

        /* Booking Details Box */
        .ticket-summary-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 28px;
            text-align: left;
        }

        .code-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px dashed #cbd5e1;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }

        .booking-badge {
            font-family: monospace;
            background: #eff6ff;
            color: #2563eb;
            font-weight: 800;
            font-size: 16px;
            padding: 4px 12px;
            border-radius: 8px;
            border: 1px solid #bfdbfe;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            font-size: 13.5px;
        }

        .info-grid div span {
            display: block;
            color: #64748b;
            font-size: 12px;
            margin-bottom: 2px;
        }

        .info-grid div strong {
            color: #1e293b;
        }

        /* Action Buttons */
        .actions-row {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .btn-ticket {
            display: block;
            background: #f84646;
            color: white;
            text-decoration: none;
            padding: 14px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 15px;
            transition: all 0.2s;
            box-shadow: 0 4px 14px rgba(248, 70, 70, 0.3);
        }

        .btn-ticket:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        .btn-secondary-link {
            display: inline-block;
            color: #475569;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            padding: 8px;
            transition: color 0.2s;
        }

        .btn-secondary-link:hover {
            color: #0f172a;
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

    <div class="success-wrapper">
        <div class="success-card">
            <div class="checkmark-circle">
                <svg viewBox="0 0 24 24">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </div>

            <h1 class="success-title">Parking Reservation Confirmed!</h1>
            <p class="success-subtitle">Your parking spot has been secured. Your digital parking pass is ready.</p>

            <?php if ($booking): ?>
                <div class="ticket-summary-box">
                    <div class="code-row">
                        <div>
                            <span style="font-size: 12px; color: #64748b; font-weight: 600;">BOOKING PASS CODE</span>
                            <div class="booking-badge"><?= htmlspecialchars($booking['code'] ?: 'PE-CONFIRMED') ?></div>
                        </div>
                        <div style="text-align: right;">
                            <span style="font-size: 12px; color: #64748b; font-weight: 600;">AMOUNT PAID</span>
                            <div style="font-size: 18px; font-weight: 800; color: #10b981;">₹<?= number_format((float)$booking['total_amount'], 2) ?></div>
                        </div>
                    </div>

                    <div class="info-grid">
                        <div>
                            <span>Location</span>
                            <strong><?= htmlspecialchars($booking['location']) ?></strong>
                        </div>
                        <div>
                            <span>Date</span>
                            <strong><?= htmlspecialchars($booking['date']) ?></strong>
                        </div>
                        <div>
                            <span>Entry &amp; Exit</span>
                            <strong><?= htmlspecialchars($booking['entry_time']) ?> - <?= htmlspecialchars($booking['exit_time']) ?></strong>
                        </div>
                        <div>
                            <span>Vehicle</span>
                            <strong><?= htmlspecialchars($booking['vehicle_number']) ?> (<?= ($booking['vehicle_type'] === 'two_wheeler') ? '2W' : '4W' ?>)</strong>
                        </div>
                    </div>
                </div>

                <div class="actions-row">
                    <a href="receipt.php?booking_id=<?= urlencode($booking['id']) ?>&user_email=<?= urlencode($userEmail) ?>" class="btn-ticket">
                        🎟️ View &amp; Download Digital QR Pass
                    </a>
                    <a href="mybookings.php" class="btn-secondary-link">
                        📋 View All My Bookings
                    </a>
                    <a href="map.php" class="btn-secondary-link">
                        🗺️ Return to Parking Map
                    </a>
                </div>
            <?php else: ?>
                <p>Reservation recorded. Click below to view your bookings.</p>
                <a href="mybookings.php" class="btn-ticket" style="margin-top: 18px;">Go to My Bookings</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- AI Assistant Widget -->
    <script src="assets/js/ai_assistant.js"></script>
</body>

</html>