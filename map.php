<?php
// Include the database configuration file 
require_once 'config/config.php';

session_start();
$isLoggedIn = isset($_SESSION["user"]);
$user = $isLoggedIn ? $_SESSION["user"] : null;

// Fetch the marker info from the database with all enriched fields
$sql = "SELECT * FROM locations ORDER BY available_slots DESC";
$result = $conn->query($sql);
$markers = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $markers[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'address' => isset($row['address']) ? $row['address'] : '',
            'latitude' => (float)$row['latitude'],
            'longitude' => (float)$row['longitude'],
            'available_slots' => (int)$row['available_slots'],
            'total_slots' => isset($row['total_slots']) ? (int)$row['total_slots'] : 50,
            'price' => (float)$row['price'],
            'two_wheeler_price' => isset($row['two_wheeler_price']) ? (float)$row['two_wheeler_price'] : 15.00,
            'four_wheeler_price' => isset($row['four_wheeler_price']) ? (float)$row['four_wheeler_price'] : (float)$row['price'],
            'amenities' => isset($row['amenities']) ? $row['amenities'] : 'CCTV, Guard, Covered',
            'rating' => isset($row['rating']) ? (float)$row['rating'] : 4.5,
        ];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Parking & Live Map - ParkEasy</title>
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">

    <!-- Leaflet OpenStreetMap CSS (100% Free, No API Key Required) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: #f8fafc;
            font-family: 'Manrope', sans-serif;
            color: #1e293b;
            min-height: 100vh;
        }

        /* Top Header Navbar */
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
            letter-spacing: -0.5px;
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
            transition: all 0.2s ease;
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

        .nav-link-btn:hover {
            background: #d32f2f;
            transform: translateY(-1px);
        }

        /* Hero Banner */
        .map-hero {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: white;
            padding: 24px 32px;
            border-bottom: 3px solid #f84646;
        }

        .hero-headline {
            font-family: 'Outfit', sans-serif;
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .hero-sub {
            color: #94a3b8;
            font-size: 14.5px;
            margin-bottom: 18px;
        }

        /* Filter & Search Bar */
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
        }

        .search-box-wrapper {
            position: relative;
            flex: 1;
            min-width: 280px;
        }

        .search-box-wrapper input {
            width: 100%;
            background: #334155;
            border: 1px solid #475569;
            color: white;
            padding: 12px 44px 12px 16px;
            border-radius: 12px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }

        .search-box-wrapper input:focus {
            border-color: #f84646;
            box-shadow: 0 0 0 3px rgba(248, 70, 70, 0.25);
        }

        .search-box-wrapper input::placeholder {
            color: #94a3b8;
        }

        .search-voice-btn {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 6px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .search-voice-btn:hover, .search-voice-btn.listening {
            color: #f84646;
            background: rgba(248, 70, 70, 0.15);
        }

        .locate-me-btn {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 13.5px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .locate-me-btn:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }

        .filter-pills {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            width: 100%;
            margin-top: 10px;
        }

        .pill {
            background: rgba(255, 255, 255, 0.08);
            color: #cbd5e1;
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            user-select: none;
        }

        .pill:hover, .pill.active {
            background: #f84646;
            color: white;
            border-color: #f84646;
        }

        /* Main Workspace Container: Map + Cards */
        .workspace-layout {
            display: flex;
            height: calc(100vh - 195px);
            min-height: 580px;
            overflow: hidden;
        }

        /* Map Pane */
        .map-pane {
            flex: 1.3;
            position: relative;
            height: 100%;
        }

        #leaflet-map {
            width: 100%;
            height: 100%;
            z-index: 10;
        }

        .map-legend {
            position: absolute;
            bottom: 24px;
            left: 24px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            padding: 12px 16px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            z-index: 999;
            font-size: 12px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            border: 1px solid rgba(0,0,0,0.06);
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: #475569;
        }

        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        /* Cards Pane */
        .cards-pane {
            flex: 1;
            background: #f8fafc;
            border-left: 1px solid #e2e8f0;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .cards-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 4px;
        }

        .cards-count {
            font-weight: 800;
            color: #334155;
            font-size: 15px;
        }

        .sort-select {
            padding: 6px 12px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            font-size: 12.5px;
            font-weight: 600;
            color: #475569;
            background: white;
            outline: none;
        }

        /* Parking Card */
        .parking-item-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 18px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            transition: all 0.25s ease;
            cursor: pointer;
            position: relative;
        }

        .parking-item-card:hover, .parking-item-card.selected {
            border-color: #f84646;
            box-shadow: 0 8px 24px rgba(248, 70, 70, 0.12);
            transform: translateY(-2px);
        }

        .card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }

        .card-title {
            font-family: 'Outfit', sans-serif;
            font-size: 17px;
            font-weight: 700;
            color: #0f172a;
        }

        .card-rating {
            background: #fef3c7;
            color: #b45309;
            font-weight: 800;
            font-size: 12px;
            padding: 2px 8px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 3px;
        }

        .card-address {
            color: #64748b;
            font-size: 13px;
            line-height: 1.4;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .card-meta-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f1f5f9;
            padding: 10px 14px;
            border-radius: 10px;
            margin-bottom: 12px;
            font-size: 13px;
        }

        .slot-indicator {
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .slot-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
        }

        .price-badge {
            font-weight: 800;
            color: #0f172a;
            font-size: 14px;
        }

        .amenities-list {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 14px;
        }

        .amenity-tag {
            background: #e2e8f0;
            color: #475569;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 6px;
        }

        .amenity-tag.ev {
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }

        .card-actions {
            display: flex;
            gap: 10px;
        }

        .btn-quick-book {
            flex: 1;
            background: #f84646;
            color: white;
            text-align: center;
            text-decoration: none;
            padding: 10px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13.5px;
            transition: all 0.2s;
        }

        .btn-quick-book:hover {
            background: #dc2626;
        }

        .btn-view-map {
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
            padding: 10px 14px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-view-map:hover {
            background: #e2e8f0;
        }

        .distance-tag {
            color: #2563eb;
            font-weight: 700;
            font-size: 12px;
            margin-left: 6px;
        }

        /* Custom Leaflet Markers */
        .custom-pin {
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 11px;
            border-radius: 50% 50% 50% 0;
            transform: rotate(-45deg);
            width: 34px !important;
            height: 34px !important;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            border: 2px solid white;
        }

        .custom-pin span {
            transform: rotate(45deg);
        }

        .pin-green { background: #10b981; }
        .pin-yellow { background: #f59e0b; }
        .pin-red { background: #ef4444; }

        /* Leaflet Popup Styling */
        .leaflet-popup-content-wrapper {
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            padding: 4px;
        }

        .leaflet-popup-content {
            margin: 12px 14px;
            font-family: 'Manrope', sans-serif;
        }

        .popup-title {
            font-family: 'Outfit', sans-serif;
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .popup-meta {
            font-size: 12.5px;
            color: #64748b;
            margin-bottom: 8px;
        }

        .popup-slots {
            background: #f8fafc;
            padding: 8px 10px;
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            font-size: 12.5px;
        }

        .popup-btn {
            display: block;
            background: #f84646;
            color: white !important;
            text-align: center;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13px;
            transition: background 0.2s;
        }

        .popup-btn:hover {
            background: #dc2626;
        }

        @media (max-width: 900px) {
            .workspace-layout {
                flex-direction: column;
                height: auto;
            }
            .map-pane {
                height: 380px;
            }
            .cards-pane {
                height: 500px;
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
            <?php if ($isLoggedIn): ?>
                <a href="service.php" class="nav-link">Dashboard</a>
                <a href="map.php" class="nav-link active">Find Parking</a>
                <a href="mybookings.php" class="nav-link">My Bookings</a>
                <a href="logout.php" class="nav-link nav-link-btn">Logout (<?= htmlspecialchars($user['full_name'] ?: 'User') ?>)</a>
            <?php else: ?>
                <a href="map.php" class="nav-link active">Explore Map</a>
                <a href="login.php" class="nav-link">Login</a>
                <a href="registration.php" class="nav-link nav-link-btn">Register</a>
            <?php endif; ?>
        </nav>
    </header>

    <!-- Hero Search & Filter Section -->
    <section class="map-hero">
        <h1 class="hero-headline">
            <span>🗺️</span> Interactive Parking Radar & Smart Locator
        </h1>
        <p class="hero-sub">
            Real-time open parking spots across Kalyan, Titwala & Ulhasnagar powered by live IoT & AI occupancy forecasts.
        </p>

        <div class="filter-bar">
            <div class="search-box-wrapper">
                <input type="text" id="map-search-input" placeholder="Search by area, lot name, or landmark (e.g. Kalyan Station, Titwala)..." />
                <button class="search-voice-btn" id="voice-search-btn" title="Speak to search (Free AI Voice)">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3m5.3-3c0 3-2.54 5.1-5.3 5.1S6.7 14 6.7 11H5c0 3.41 2.72 6.23 6 6.72V21h2v-3.28c3.28-.48 6-3.3 6-6.72z"/></svg>
                </button>
            </div>

            <button class="locate-me-btn" id="locate-me-btn">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M12 8c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4zm8.94 3A8.994 8.994 0 0 0 13 3.06V1h-2v2.06A8.994 8.994 0 0 0 3.06 11H1v2h2.06A8.994 8.994 0 0 0 11 20.94V23h2v-2.06A8.994 8.994 0 0 0 20.94 13H23v-2h-2.06zM12 19c-3.87 0-7-3.13-7-7s3.13-7 7-7 7 3.13 7 7-3.13 7-7 7z"/></svg>
                Locate Me
            </button>

            <div class="filter-pills">
                <div class="pill active" data-filter="all">All Lots (<?= count($markers) ?>)</div>
                <div class="pill" data-filter="ev">⚡ EV Charging</div>
                <div class="pill" data-filter="high_slots">🟢 High Availability (>20 Slots)</div>
                <div class="pill" data-filter="cheap">💰 Budget Friendly (≤₹30)</div>
                <div class="pill" data-filter="top_rated">⭐ Top Rated (4.5+)</div>
            </div>
        </div>
    </section>

    <!-- Workspace Layout: Map + Cards -->
    <main class="workspace-layout">
        <!-- Interactive Map Pane -->
        <div class="map-pane">
            <div id="leaflet-map"></div>
            <div class="map-legend">
                <div class="legend-item">
                    <span class="legend-color" style="background: #10b981;"></span>
                    <span>High Slots (&gt; 20 available)</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color" style="background: #f59e0b;"></span>
                    <span>Moderate (6 - 20 available)</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color" style="background: #ef4444;"></span>
                    <span>Almost Full (&le; 5 available)</span>
                </div>
            </div>
        </div>

        <!-- Synchronized Parking Cards Pane -->
        <aside class="cards-pane" id="cards-container">
            <div class="cards-header">
                <div class="cards-count" id="spots-count-text">Showing <?= count($markers) ?> Parking Spots</div>
                <select class="sort-select" id="sort-select">
                    <option value="slots_desc">Sort: Most Available Slots</option>
                    <option value="price_asc">Sort: Lowest Price</option>
                    <option value="rating_desc">Sort: Highest Rating</option>
                    <option value="distance_asc" id="sort-distance-opt" disabled>Sort: Nearest to Me (Enable GPS)</option>
                </select>
            </div>

            <div id="cards-list" style="display: flex; flex-direction: column; gap: 14px;">
                <!-- Dynamically rendered and synchronized -->
            </div>
        </aside>
    </main>

    <!-- Leaflet.js Library (100% Free OpenStreetMap client, No Limits) -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    
    <!-- AI Predictor & AI Assistant Scripts -->
    <script src="assets/js/ai_predictor.js"></script>
    <script src="assets/js/ai_assistant.js"></script>

    <script>
        // Database Parking Lots
        const parkingData = <?= json_encode($markers, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        let userCoords = null;
        let activeFilter = 'all';
        let searchQuery = '';
        let mapInstance = null;
        let markersLayer = null;
        let userMarker = null;
        const markerRefs = {};

        // Calculate Haversine distance in km
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371; // Earth's radius in km
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                      Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return (R * c).toFixed(1);
        }

        // Initialize Map
        function initMap() {
            // Center around Kalyan-Dombivli-Titwala area
            mapInstance = L.map('leaflet-map', {
                center: [19.2437, 73.1338],
                zoom: 13,
                zoomControl: true
            });

            // OpenStreetMap free tile server (zero restrictions, no API key)
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(mapInstance);

            markersLayer = L.layerGroup().addTo(mapInstance);
            renderMapMarkersAndCards();
        }

        // Pin color determination
        function getPinColorClass(availableSlots) {
            if (availableSlots > 20) return 'pin-green';
            if (availableSlots > 5) return 'pin-yellow';
            return 'pin-red';
        }

        // Render Markers on Leaflet Map and Cards in Sidebar
        function renderMapMarkersAndCards() {
            markersLayer.clearLayers();
            const cardsContainer = document.getElementById('cards-list');
            cardsContainer.innerHTML = '';

            // Filter logic
            let filtered = parkingData.filter(spot => {
                // Search query match
                const q = searchQuery.toLowerCase();
                const matchesSearch = !q || 
                    spot.name.toLowerCase().includes(q) || 
                    (spot.address && spot.address.toLowerCase().includes(q)) ||
                    (spot.amenities && spot.amenities.toLowerCase().includes(q));

                if (!matchesSearch) return false;

                // Pill filter match
                if (activeFilter === 'ev') return spot.amenities && spot.amenities.toLowerCase().includes('ev charging');
                if (activeFilter === 'high_slots') return spot.available_slots > 20;
                if (activeFilter === 'cheap') return spot.price <= 30 || spot.two_wheeler_price <= 30;
                if (activeFilter === 'top_rated') return spot.rating >= 4.5;
                return true;
            });

            // Update distance if user location known
            if (userCoords) {
                filtered.forEach(spot => {
                    spot.distance = calculateDistance(userCoords.lat, userCoords.lng, spot.latitude, spot.longitude);
                });
            }

            // Sorting
            const sortMode = document.getElementById('sort-select').value;
            if (sortMode === 'slots_desc') {
                filtered.sort((a, b) => b.available_slots - a.available_slots);
            } else if (sortMode === 'price_asc') {
                filtered.sort((a, b) => a.price - b.price);
            } else if (sortMode === 'rating_desc') {
                filtered.sort((a, b) => b.rating - a.rating);
            } else if (sortMode === 'distance_asc' && userCoords) {
                filtered.sort((a, b) => parseFloat(a.distance) - parseFloat(b.distance));
            }

            document.getElementById('spots-count-text').innerText = `Showing ${filtered.length} Parking Spots`;

            if (filtered.length === 0) {
                cardsContainer.innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #64748b;">
                        <p style="font-size: 32px; margin-bottom: 8px;">🔍</p>
                        <h4 style="font-size: 16px; margin-bottom: 6px;">No parking spots found</h4>
                        <p style="font-size: 13px;">Try relaxing your search terms or selecting a different filter.</p>
                    </div>
                `;
                return;
            }

            const bounds = [];

            filtered.forEach(spot => {
                const pinClass = getPinColorClass(spot.available_slots);
                
                // Custom HTML Marker Pin
                const customIcon = L.divIcon({
                    className: 'custom-leaflet-marker',
                    html: `<div class="custom-pin ${pinClass}"><span>${spot.available_slots}</span></div>`,
                    iconSize: [34, 34],
                    iconAnchor: [17, 34],
                    popupAnchor: [0, -32]
                });

                const marker = L.marker([spot.latitude, spot.longitude], { icon: customIcon });

                // AI Prediction
                const ai = window.ParkEasyAI ? window.ParkEasyAI.predictOccupancy(spot.available_slots, spot.total_slots) : { likelihood: 85, status: 'Normal' };

                // Popup Content
                const popupContent = `
                    <div class="popup-title">${spot.name}</div>
                    <div class="popup-meta">📍 ${spot.address || 'Kalyan Region'} • ⭐ ${spot.rating}</div>
                    <div class="popup-slots">
                        <span><strong>${spot.available_slots}</strong> / ${spot.total_slots} Slots</span>
                        <span>🤖 <strong>${ai.likelihood}% Chance</strong></span>
                    </div>
                    <div style="font-size: 12px; margin-bottom: 10px; color: #475569;">
                        2-Wheeler: <strong>₹${spot.two_wheeler_price}/hr</strong> | 4-Wheeler: <strong>₹${spot.four_wheeler_price}/hr</strong>
                    </div>
                    <a href="confirmbook.php?name=${encodeURIComponent(spot.name)}&price=${encodeURIComponent(spot.four_wheeler_price)}" class="popup-btn">
                        ⚡ Quick Book Spot
                    </a>
                `;

                marker.bindPopup(popupContent);
                markersLayer.addLayer(marker);
                bounds.push([spot.latitude, spot.longitude]);
                markerRefs[spot.id] = marker;

                // Create Parking Item Card
                const card = document.createElement('div');
                card.className = 'parking-item-card';
                card.id = `spot-card-${spot.id}`;

                const amenitiesArr = spot.amenities ? spot.amenities.split(',').map(s => s.trim()) : [];
                const amenitiesHtml = amenitiesArr.map(am => {
                    const isEv = am.toLowerCase().includes('ev');
                    return `<span class="amenity-tag ${isEv ? 'ev' : ''}">${isEv ? '⚡ ' : ''}${am}</span>`;
                }).join('');

                const distanceBadge = spot.distance ? `<span class="distance-tag">📍 ${spot.distance} km away</span>` : '';

                card.innerHTML = `
                    <div class="card-top">
                        <div class="card-title">${spot.name}</div>
                        <div class="card-rating">⭐ ${spot.rating}</div>
                    </div>
                    <div class="card-address">
                        <span>📍 ${spot.address || 'Kalyan Region'}</span>
                        ${distanceBadge}
                    </div>
                    <div class="card-meta-row">
                        <div class="slot-indicator">
                            <span class="slot-dot" style="background: ${spot.available_slots > 20 ? '#10b981' : (spot.available_slots > 5 ? '#f59e0b' : '#ef4444')};"></span>
                            <span>${spot.available_slots} Slots Free</span>
                        </div>
                        <div class="price-badge">₹${spot.four_wheeler_price} <span style="font-size: 11px; font-weight: normal; color: #64748b;">/hr</span></div>
                    </div>
                    <div class="amenities-list">
                        ${amenitiesHtml}
                    </div>
                    <div id="ai-badge-${spot.id}"></div>
                    <div class="card-actions">
                        <button class="btn-view-map" onclick="focusMarker(${spot.id}, ${spot.latitude}, ${spot.longitude})">🗺️ View on Map</button>
                        <a href="confirmbook.php?name=${encodeURIComponent(spot.name)}&price=${encodeURIComponent(spot.four_wheeler_price)}" class="btn-quick-book">Book Now</a>
                    </div>
                `;

                cardsContainer.appendChild(card);

                // Render AI occupancy badge into card
                if (window.ParkEasyAI) {
                    window.ParkEasyAI.renderBadge(document.getElementById(`ai-badge-${spot.id}`), spot.available_slots, spot.total_slots);
                }
            });

            if (bounds.length > 0) {
                mapInstance.fitBounds(bounds, { padding: [40, 40], maxZoom: 15 });
            }
        }

        // Focus map and open marker on click
        function focusMarker(id, lat, lng) {
            if (mapInstance && markerRefs[id]) {
                mapInstance.flyTo([lat, lng], 16, { duration: 1.2 });
                markerRefs[id].openPopup();

                // Highlight card
                document.querySelectorAll('.parking-item-card').forEach(c => c.classList.remove('selected'));
                const targetCard = document.getElementById(`spot-card-${id}`);
                if (targetCard) {
                    targetCard.classList.add('selected');
                    targetCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            }
        }

        // Filter Pills Click
        document.querySelectorAll('.pill').forEach(pill => {
            pill.addEventListener('click', () => {
                document.querySelectorAll('.pill').forEach(p => p.classList.remove('active'));
                pill.classList.add('active');
                activeFilter = pill.getAttribute('data-filter');
                renderMapMarkersAndCards();
            });
        });

        // Search Input
        const searchInput = document.getElementById('map-search-input');
        searchInput.addEventListener('input', (e) => {
            searchQuery = e.target.value.trim();
            renderMapMarkersAndCards();
        });

        // Sort dropdown change
        document.getElementById('sort-select').addEventListener('change', () => {
            renderMapMarkersAndCards();
        });

        // HTML5 Geolocation (Free GPS Locator)
        const locateBtn = document.getElementById('locate-me-btn');
        locateBtn.addEventListener('click', () => {
            if ('geolocation' in navigator) {
                locateBtn.innerHTML = '⌛ Locating...';
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        userCoords = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };

                        locateBtn.innerHTML = '📍 Located!';
                        document.getElementById('sort-distance-opt').disabled = false;
                        document.getElementById('sort-select').value = 'distance_asc';

                        // Add or update pulsing user marker
                        if (userMarker) {
                            userMarker.setLatLng([userCoords.lat, userCoords.lng]);
                        } else {
                            const userIcon = L.divIcon({
                                className: 'user-gps-marker',
                                html: `<div style="width: 18px; height: 18px; background: #2563eb; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 0 6px rgba(37,99,235,0.35);"></div>`,
                                iconSize: [18, 18],
                                iconAnchor: [9, 9]
                            });
                            userMarker = L.marker([userCoords.lat, userCoords.lng], { icon: userIcon })
                                .bindPopup('<strong>📍 You are here</strong>')
                                .addTo(mapInstance);
                        }

                        mapInstance.flyTo([userCoords.lat, userCoords.lng], 14);
                        renderMapMarkersAndCards();
                    },
                    (error) => {
                        locateBtn.innerHTML = '📍 Locate Me';
                        alert('Could not obtain your GPS location. Please ensure location services are enabled in your browser settings.');
                    }
                );
            } else {
                alert('Geolocation is not supported by your browser.');
            }
        });

        // Web Speech API Voice Search
        const voiceBtn = document.getElementById('voice-search-btn');
        if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            const recognition = new SpeechRecognition();
            recognition.continuous = false;
            recognition.lang = 'en-IN';

            voiceBtn.addEventListener('click', () => {
                recognition.start();
                voiceBtn.classList.add('listening');
                searchInput.placeholder = 'Listening for location... (e.g. Kalyan, Titwala)';
            });

            recognition.onresult = (event) => {
                const spoken = event.results[0][0].transcript;
                searchInput.value = spoken;
                searchQuery = spoken.trim();
                renderMapMarkersAndCards();
            };

            recognition.onend = () => {
                voiceBtn.classList.remove('listening');
                searchInput.placeholder = 'Search by area, lot name, or landmark...';
            };
        } else {
            voiceBtn.style.display = 'none';
        }

        // Initialize Map on load
        window.addEventListener('DOMContentLoaded', initMap);
    </script>
</body>

</html>