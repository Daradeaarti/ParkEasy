<?php
session_start();
require_once 'config/config.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['user'];
$userEmail = $user['email'];

$alertMessage = "";
$alertType = "";

// Handle Cancellation
if (isset($_POST['cancel_booking']) && isset($_POST['booking_id'])) {
    $bookingId = (int)$_POST['booking_id'];

    // First fetch booking details to find location name
    $chkStmt = $conn->prepare("SELECT location_name, status FROM bookings WHERE id = ? AND email = ?");
    if ($chkStmt) {
        $chkStmt->bind_param("is", $bookingId, $userEmail);
        $chkStmt->execute();
        $chkRes = $chkStmt->get_result();

        if ($chkRes && $chkRes->num_rows > 0) {
            $bRow = $chkRes->fetch_assoc();
            $locName = $bRow['location_name'];

            if ($bRow['status'] !== 'Cancelled') {
                // Update booking status to Cancelled
                $updateStmt = $conn->prepare("UPDATE bookings SET status = 'Cancelled' WHERE id = ? AND email = ?");
                if ($updateStmt) {
                    $updateStmt->bind_param("is", $bookingId, $userEmail);
                    if ($updateStmt->execute()) {
                        // Release slot back to location
                        $slotStmt = $conn->prepare("UPDATE locations SET available_slots = available_slots + 1 WHERE name = ?");
                        if ($slotStmt) {
                            $slotStmt->bind_param("s", $locName);
                            $slotStmt->execute();
                            $slotStmt->close();
                        }
                        $alertMessage = "Booking cancelled successfully. Your spot has been released.";
                        $alertType = "success";
                    } else {
                        $alertMessage = "Error updating booking: " . $conn->error;
                        $alertType = "error";
                    }
                    $updateStmt->close();
                }
            } else {
                $alertMessage = "This booking is already cancelled.";
                $alertType = "info";
            }
        }
        $chkStmt->close();
    }
}

// Fetch all bookings for the logged-in user
$bookings = [];
$stmt = $conn->prepare("SELECT * FROM bookings WHERE email = ? ORDER BY id DESC");
if ($stmt) {
    $stmt->bind_param("s", $userEmail);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        while ($r = $result->fetch_assoc()) {
            $bookings[] = $r;
        }
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - ParkEasy</title>
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

        .nav-link:hover, .nav-link.active {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
        }

        .nav-link-btn {
            background: #f84646;
            color: #ffffff !important;
            border-radius: 20px;
            padding: 8px 18px;
        }

        /* Content Container */
        .bookings-container {
            max-width: 1060px;
            margin: 36px auto;
            padding: 0 20px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 14px;
        }

        .page-title {
            font-family: 'Outfit', sans-serif;
            font-size: 28px;
            font-weight: 800;
            color: #0f172a;
        }

        .btn-new-booking {
            background: #f84646;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-new-booking:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        /* Alert Notification */
        .toast-alert {
            padding: 14px 20px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .toast-success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .toast-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* Bookings Table / Cards */
        .table-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
        }

        .custom-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 13.5px;
        }

        .custom-table th {
            background: #f8fafc;
            color: #475569;
            font-weight: 700;
            padding: 16px 20px;
            border-bottom: 1px solid #e2e8f0;
            text-transform: uppercase;
            font-size: 11.5px;
            letter-spacing: 0.5px;
        }

        .custom-table td {
            padding: 18px 20px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .custom-table tr:hover {
            background: #fdfdfd;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11.5px;
            font-weight: 800;
        }

        .status-confirmed {
            background: #ecfdf5;
            color: #059669;
        }

        .status-cancelled {
            background: #f1f5f9;
            color: #94a3b8;
        }

        .pass-code {
            font-family: monospace;
            background: #eff6ff;
            color: #2563eb;
            font-weight: 700;
            font-size: 13px;
            padding: 2px 8px;
            border-radius: 6px;
        }

        .btn-view-pass {
            background: #0f172a;
            color: white;
            text-decoration: none;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 12.5px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s;
        }

        .btn-view-pass:hover {
            background: #334155;
        }

        .btn-cancel-action {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fca5a5;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 12.5px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-cancel-action:hover {
            background: #ef4444;
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 12px;
        }

        @media (max-width: 860px) {
            .table-card {
                overflow-x: auto;
            }
            .custom-table {
                min-width: 700px;
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
            <a href="mybookings.php" class="nav-link active">My Bookings</a>
            <a href="logout.php" class="nav-link nav-link-btn">Logout</a>
        </nav>
    </header>

    <div class="bookings-container">
        <div class="page-header">
            <div>
                <h1 class="page-title">My Parking Reservations</h1>
                <p style="color: #64748b; font-size: 14px;">Manage your active parking passes, view QR tickets, and check receipts.</p>
            </div>
            <a href="map.php" class="btn-new-booking">
                <span>🗺️</span> Find &amp; Book New Spot
            </a>
        </div>

        <?php if (!empty($alertMessage)): ?>
            <div class="toast-alert <?= ($alertType === 'success') ? 'toast-success' : 'toast-error' ?>">
                <?= htmlspecialchars($alertMessage) ?>
            </div>
        <?php endif; ?>

        <div class="table-card">
            <?php if (!empty($bookings)): ?>
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Pass Code</th>
                            <th>Parking Lot</th>
                            <th>Date &amp; Window</th>
                            <th>Vehicle</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Digital Ticket</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $b): 
                            $bStatus = !empty($b['status']) ? $b['status'] : 'Confirmed';
                            $bCode = !empty($b['booking_code']) ? $b['booking_code'] : ('PE-' . strtoupper(substr(md5($b['id']), 0, 5)));
                        ?>
                            <tr>
                                <td>
                                    <span class="pass-code"><?= htmlspecialchars($bCode) ?></span>
                                </td>
                                <td>
                                    <strong style="color: #0f172a;"><?= htmlspecialchars($b['location_name']) ?></strong>
                                </td>
                                <td>
                                    <div><?= htmlspecialchars($b['date']) ?></div>
                                    <div style="font-size: 12px; color: #64748b;"><?= htmlspecialchars($b['entry_time']) ?> - <?= htmlspecialchars($b['exit_time']) ?></div>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($b['vehicle_number']) ?></strong>
                                    <div style="font-size: 11.5px; color: #64748b;"><?= ($b['vehicle_type'] === 'two_wheeler') ? '2-Wheeler' : '4-Wheeler' ?></div>
                                </td>
                                <td>
                                    <strong style="color: #10b981;">₹<?= number_format((float)($b['total_amount'] ?? 0), 2) ?></strong>
                                </td>
                                <td>
                                    <span class="status-badge <?= ($bStatus === 'Confirmed') ? 'status-confirmed' : 'status-cancelled' ?>">
                                        <?= htmlspecialchars($bStatus) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="receipt.php?booking_id=<?= urlencode($b['id']) ?>&user_email=<?= urlencode($userEmail) ?>" class="btn-view-pass">
                                        🎟️ View Pass
                                    </a>
                                </td>
                                <td>
                                    <?php if ($bStatus === 'Confirmed'): ?>
                                        <form method="post" onsubmit="return confirm('Are you sure you want to cancel this booking? Your slot will be released.');" style="display:inline;">
                                            <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                            <button type="submit" name="cancel_booking" class="btn-cancel-action">
                                                Cancel
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span style="font-size: 12px; color: #94a3b8;">Cancelled</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🅿️</div>
                    <h3 style="font-family: 'Outfit', sans-serif; font-size: 18px; margin-bottom: 6px; color: #0f172a;">No bookings found yet</h3>
                    <p style="font-size: 14px; margin-bottom: 20px;">You have not made any parking reservations yet. Find an open spot on our live map!</p>
                    <a href="map.php" class="btn-new-booking">Explore Parking Radar</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- AI Assistant Widget -->
    <script src="assets/js/ai_assistant.js"></script>
</body>

</html>