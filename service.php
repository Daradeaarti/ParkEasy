<?php
session_start();
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

require_once 'config/config.php';

$user = $_SESSION['user'];
$userName = !empty($user['full_name']) ? $user['full_name'] : 'Valued Driver';
$userEmail = $user['email'];

// Fetch quick live stats
$totalLots = 0;
$totalOpenSlots = 0;
$resLots = $conn->query("SELECT COUNT(*) as total_lots, SUM(available_slots) as total_open FROM locations");
if ($resLots && $row = $resLots->fetch_assoc()) {
    $totalLots = (int)$row['total_lots'];
    $totalOpenSlots = (int)$row['total_open'];
}

$userBookingsCount = 0;
$resBk = $conn->query("SELECT COUNT(*) as cnt FROM bookings WHERE email = '$userEmail' AND status != 'Cancelled'");
if ($resBk && $rowBk = $resBk->fetch_assoc()) {
    $userBookingsCount = (int)$rowBk['cnt'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ParkEasy</title>
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

        /* Dashboard Hero */
        .dashboard-hero {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: white;
            padding: 40px 32px 60px 32px;
            text-align: center;
            position: relative;
        }

        .hero-greeting {
            font-family: 'Outfit', sans-serif;
            font-size: 34px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .hero-greeting span {
            color: #f84646;
        }

        .hero-sub {
            color: #94a3b8;
            font-size: 16px;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Stats Bar */
        .stats-bar-wrapper {
            max-width: 960px;
            margin: -35px auto 40px auto;
            padding: 0 20px;
            position: relative;
            z-index: 10;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .stat-card {
            background: white;
            border-radius: 18px;
            padding: 22px 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .stat-number {
            font-family: 'Outfit', sans-serif;
            font-size: 26px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.1;
        }

        .stat-label {
            font-size: 13px;
            color: #64748b;
            font-weight: 600;
            margin-top: 2px;
        }

        /* Features Section */
        .services-container {
            max-width: 1040px;
            margin: 0 auto 60px auto;
            padding: 0 20px;
        }

        .section-heading {
            font-family: 'Outfit', sans-serif;
            font-size: 24px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 24px;
            text-align: center;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }

        .feature-box {
            background: white;
            border-radius: 20px;
            padding: 28px 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.25s ease;
        }

        .feature-box:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 36px rgba(248, 70, 70, 0.12);
            border-color: #f84646;
        }

        .feature-img-wrap {
            height: 140px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }

        .feature-img-wrap img {
            max-height: 120px;
            max-width: 100%;
            object-fit: contain;
        }

        .feature-title {
            font-family: 'Outfit', sans-serif;
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .feature-desc {
            font-size: 13.5px;
            color: #64748b;
            line-height: 1.5;
            margin-bottom: 22px;
            flex-grow: 1;
        }

        .btn-feature {
            background: #f84646;
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            display: block;
            transition: all 0.2s;
        }

        .btn-feature:hover {
            background: #dc2626;
        }

        @media (max-width: 860px) {
            .stats-grid, .features-grid {
                grid-template-columns: 1fr;
            }
            .stats-bar-wrapper {
                margin-top: -20px;
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
            <a href="service.php" class="nav-link active">Dashboard</a>
            <a href="map.php" class="nav-link">Find Parking</a>
            <a href="mybookings.php" class="nav-link">My Bookings</a>
            <a href="logout.php" class="nav-link nav-link-btn">Logout (<?= htmlspecialchars($userName) ?>)</a>
        </nav>
    </header>

    <!-- Dashboard Hero Banner -->
    <section class="dashboard-hero">
        <h1 class="hero-greeting">Welcome back, <span><?= htmlspecialchars($userName) ?></span>!</h1>
        <p class="hero-sub">Manage your reservations, explore live parking availability, and check in with your digital QR pass.</p>
    </section>

    <!-- Real-time Stats -->
    <div class="stats-bar-wrapper">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: #eff6ff; color: #2563eb;">📍</div>
                <div>
                    <div class="stat-number"><?= $totalLots ?></div>
                    <div class="stat-label">Active Parking Lots</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: #ecfdf5; color: #10b981;">🟢</div>
                <div>
                    <div class="stat-number"><?= $totalOpenSlots ?></div>
                    <div class="stat-label">Real-time Open Slots</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: #fef2f2; color: #f84646;">🎫</div>
                <div>
                    <div class="stat-number"><?= $userBookingsCount ?></div>
                    <div class="stat-label">Active Passes</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Features Grid -->
    <main class="services-container">
        <h2 class="section-heading">Smart Urban Mobility Services</h2>

        <div class="features-grid">
            <!-- Feature 1: Interactive Map -->
            <div class="feature-box">
                <div class="feature-img-wrap">
                    <img src="assets/images/car-book.png" alt="Interactive Map" />
                </div>
                <h3 class="feature-title">Find Parking &amp; Live Map</h3>
                <p class="feature-desc">
                    Search open parking spots on our interactive Leaflet &amp; OpenStreetMap radar. Use AI voice search, GPS locator, and color-coded slot counters.
                </p>
                <a href="map.php" class="btn-feature">Explore Live Radar</a>
            </div>

            <!-- Feature 2: My Bookings -->
            <div class="feature-box">
                <div class="feature-img-wrap">
                    <img src="assets/images/car-search.png" alt="My Bookings" />
                </div>
                <h3 class="feature-title">My Bookings &amp; Passes</h3>
                <p class="feature-desc">
                    Access your digital QR ticket, verify gate entry passes, view reservation details, and cancel anytime with instant slot release.
                </p>
                <a href="mybookings.php" class="btn-feature">View Active Passes</a>
            </div>

            <!-- Feature 3: ParkBot AI Assistant -->
            <div class="feature-box">
                <div class="feature-img-wrap">
                    <img src="assets/images/car-location.png" alt="ParkBot AI" />
                </div>
                <h3 class="feature-title">ParkBot AI Assistant</h3>
                <p class="feature-desc">
                    Ask our intelligent AI assistant to find the cheapest spots, EV charging lots, predict rush hour congestion, and guide you step-by-step.
                </p>
                <a href="javascript:void(0)" onclick="document.getElementById('parkbot-btn').click();" class="btn-feature" style="background: #1e293b;">Chat with ParkBot AI</a>
            </div>
        </div>
    </main>

    <!-- AI Assistant Floating Widget -->
    <script src="assets/js/ai_assistant.js"></script>
</body>

</html>