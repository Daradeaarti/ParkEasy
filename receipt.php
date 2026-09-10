<?php
session_start();
require_once 'config/config.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['user'];

if (isset($_GET['booking_id']) && isset($_GET['user_email'])) {
    $bookingId = $_GET['booking_id'];
    $userEmail = $_GET['user_email'];

    $stmt = $conn->prepare("SELECT * FROM bookings WHERE id = ? AND email = ?");
    if ($stmt) {
        $stmt->bind_param("is", $bookingId, $userEmail);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $bookingCode = !empty($row['booking_code']) ? $row['booking_code'] : ('PE-' . strtoupper(substr(md5($row['id']), 0, 6)));
            $status = !empty($row['status']) ? $row['status'] : 'Confirmed';
            $totalAmount = isset($row['total_amount']) ? (float)$row['total_amount'] : 50.00;
        } else {
            die("Booking not found.");
        }
        $stmt->close();
    } else {
        die("Error: " . $conn->error);
    }
} else {
    die("Booking ID and user email are required.");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Parking Pass - <?= htmlspecialchars($bookingCode) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- QRCode.js (Free Client-side QR generator, No external limits) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

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

        /* Digital Pass Container */
        .ticket-page-wrapper {
            max-width: 520px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .digital-ticket-card {
            background: #ffffff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
            border: 1px solid #e2e8f0;
        }

        .ticket-header {
            background: linear-gradient(135deg, #1e293b, #0f172a);
            color: white;
            padding: 24px 28px;
            position: relative;
            border-bottom: 3px solid #f84646;
        }

        .ticket-header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .ticket-brand {
            font-family: 'Outfit', sans-serif;
            font-size: 20px;
            font-weight: 800;
        }

        .ticket-brand span {
            color: #f84646;
        }

        .status-pill {
            background: #10b981;
            color: white;
            font-size: 11.5px;
            font-weight: 800;
            padding: 4px 10px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pill.cancelled {
            background: #ef4444;
        }

        .ticket-location-name {
            font-family: 'Outfit', sans-serif;
            font-size: 22px;
            font-weight: 800;
            line-height: 1.3;
        }

        .ticket-code-sub {
            font-size: 13px;
            color: #94a3b8;
            margin-top: 4px;
            font-family: monospace;
        }

        /* Ticket Body */
        .ticket-body {
            padding: 24px 28px;
        }

        .ticket-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 24px;
        }

        .ticket-item span {
            display: block;
            font-size: 11.5px;
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .ticket-item strong {
            font-size: 15px;
            color: #0f172a;
        }

        /* Perforated Divider */
        .ticket-divider {
            position: relative;
            border-top: 2px dashed #cbd5e1;
            margin: 16px -28px 24px -28px;
        }

        .ticket-divider::before, .ticket-divider::after {
            content: '';
            position: absolute;
            top: -12px;
            width: 24px;
            height: 24px;
            background: #f1f5f9;
            border-radius: 50%;
        }

        .ticket-divider::before { left: -12px; }
        .ticket-divider::after { right: -12px; }

        /* QR Code Container */
        .qr-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 10px 0 20px 0;
        }

        #qrcode-canvas {
            padding: 12px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 12px;
        }

        .qr-instruction {
            font-size: 12.5px;
            color: #64748b;
            text-align: center;
        }

        /* Action Buttons */
        .ticket-buttons-row {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

        .btn-action {
            flex: 1;
            padding: 12px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: all 0.2s;
            border: none;
        }

        .btn-print {
            background: #0f172a;
            color: white;
        }

        .btn-print:hover {
            background: #334155;
        }

        .btn-back {
            background: #e2e8f0;
            color: #334155;
        }

        .btn-back:hover {
            background: #cbd5e1;
        }

        /* Print Media Styles */
        @media print {
            .navbar-top, .ticket-buttons-row, .parkbot-widget-btn, .parkbot-modal {
                display: none !important;
            }
            body {
                background: white;
            }
            .ticket-page-wrapper {
                margin: 0 auto;
                box-shadow: none;
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
            <a href="logout.php" class="nav-link">Logout</a>
        </nav>
    </header>

    <div class="ticket-page-wrapper">
        <div class="digital-ticket-card">
            <div class="ticket-header">
                <div class="ticket-header-top">
                    <div class="ticket-brand">Park<span>Easy</span> Digital Pass</div>
                    <span class="status-pill <?= ($status === 'Cancelled') ? 'cancelled' : '' ?>">
                        ● <?= htmlspecialchars($status) ?>
                    </span>
                </div>
                <div class="ticket-location-name"><?= htmlspecialchars($row['location_name']) ?></div>
                <div class="ticket-code-sub">Pass ID: <?= htmlspecialchars($bookingCode) ?></div>
            </div>

            <div class="ticket-body">
                <div class="ticket-grid">
                    <div class="ticket-item">
                        <span>Customer Email</span>
                        <strong><?= htmlspecialchars($row['email']) ?></strong>
                    </div>
                    <div class="ticket-item">
                        <span>Reservation Date</span>
                        <strong><?= htmlspecialchars($row['date']) ?></strong>
                    </div>
                    <div class="ticket-item">
                        <span>Entry Window</span>
                        <strong><?= htmlspecialchars($row['entry_time']) ?></strong>
                    </div>
                    <div class="ticket-item">
                        <span>Exit Window</span>
                        <strong><?= htmlspecialchars($row['exit_time']) ?></strong>
                    </div>
                    <div class="ticket-item">
                        <span>Vehicle Plate</span>
                        <strong style="color: #f84646; font-family: monospace; font-size: 16px;"><?= htmlspecialchars($row['vehicle_number']) ?></strong>
                    </div>
                    <div class="ticket-item">
                        <span>Vehicle Category</span>
                        <strong><?= ($row['vehicle_type'] === 'two_wheeler') ? 'Two-Wheeler' : 'Four-Wheeler' ?></strong>
                    </div>
                    <div class="ticket-item">
                        <span>Payment Mode</span>
                        <strong style="text-transform: uppercase;"><?= htmlspecialchars($row['payment_method']) ?></strong>
                    </div>
                    <div class="ticket-item">
                        <span>Total Paid</span>
                        <strong style="color: #10b981; font-size: 17px;">₹<?= number_format($totalAmount, 2) ?></strong>
                    </div>
                </div>

                <div class="ticket-divider"></div>

                <!-- Dynamic QR Code -->
                <div class="qr-section">
                    <div id="qrcode-canvas"></div>
                    <p class="qr-instruction">Scan at barrier gate or present to the parking attendant</p>
                </div>
            </div>
        </div>

        <div class="ticket-buttons-row">
            <button class="btn-action btn-print" onclick="window.print()">
                🖨️ Print / Save as PDF
            </button>
            <a href="mybookings.php" class="btn-action btn-back">
                📋 All Bookings
            </a>
        </div>
    </div>

    <!-- AI Assistant Widget -->
    <script src="assets/js/ai_assistant.js"></script>

    <script>
        // Generate QR code on client side (100% free, unlimited, no network dependency)
        const qrPayload = JSON.stringify({
            app: "ParkEasy",
            code: "<?= htmlspecialchars($bookingCode) ?>",
            loc: "<?= htmlspecialchars($row['location_name']) ?>",
            plate: "<?= htmlspecialchars($row['vehicle_number']) ?>",
            date: "<?= htmlspecialchars($row['date']) ?>"
        });

        new QRCode(document.getElementById("qrcode-canvas"), {
            text: qrPayload,
            width: 140,
            height: 140,
            colorDark: "#0f172a",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
    </script>
</body>

</html>
