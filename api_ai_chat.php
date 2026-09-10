<?php
// api_ai_chat.php - Intelligent ParkBot AI Backend
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/config/config.php';

// Read JSON input
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

$userMessage = isset($input['message']) ? trim($input['message']) : '';

if (empty($userMessage)) {
    echo json_encode(['success' => false, 'reply' => 'Please ask a question about parking, locations, prices, or bookings.']);
    exit();
}

// 1. Fetch live database state
$locations = [];
$res = $conn->query("SELECT * FROM locations ORDER BY available_slots DESC");
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $locations[] = $row;
    }
}

// Prepare summary context for AI
$totalLocations = count($locations);
$totalAvailableSlots = array_sum(array_column($locations, 'available_slots'));
$evLocations = array_filter($locations, function($l) {
    return stripos($l['amenities'], 'EV Charging') !== false;
});
$cheapest2W = null;
$cheapest4W = null;
foreach ($locations as $l) {
    if ($cheapest2W === null || $l['two_wheeler_price'] < $cheapest2W['two_wheeler_price']) {
        $cheapest2W = $l;
    }
    if ($cheapest4W === null || $l['four_wheeler_price'] < $cheapest4W['four_wheeler_price']) {
        $cheapest4W = $l;
    }
}

// 2. Intelligent Local Rule & Intent Engine (Fast & Always Accurate)
$query = strtolower($userMessage);
$localResponse = null;

// Case: Cheapest parking
if (strpos($query, 'cheap') !== false || strpos($query, 'lowest price') !== false || strpos($query, 'affordable') !== false) {
    $localResponse = "🚗 **Most Affordable Parking Options:**\n\n" .
        "• **For 2-Wheelers:** ₹" . number_format($cheapest2W['two_wheeler_price'], 2) . "/hr at **" . $cheapest2W['name'] . "** (" . $cheapest2W['available_slots'] . " slots open)\n" .
        "• **For 4-Wheelers:** ₹" . number_format($cheapest4W['four_wheeler_price'], 2) . "/hr at **" . $cheapest4W['name'] . "** (" . $cheapest4W['available_slots'] . " slots open)\n\n" .
        "💡 *Tip:* You can book directly on the **Find Parking & Map** page!";
}

// Case: EV Charging
elseif (strpos($query, 'ev') !== false || strpos($query, 'electric') !== false || strpos($query, 'charging') !== false) {
    $evList = "";
    foreach ($evLocations as $ev) {
        $evList .= "• **" . $ev['name'] . "** (" . $ev['address'] . ")\n  Slots: " . $ev['available_slots'] . " | Rating: ⭐ " . $ev['rating'] . " | Rate: ₹" . $ev['four_wheeler_price'] . "/hr\n";
    }
    $localResponse = "⚡ **Locations with EV Charging Stations:**\n\n" . ($evList ?: "Currently updating EV stations.") . "\nAll EV spots offer fast charging and 24/7 surveillance.";
}

// Case: Available / Vacant slots
elseif (strpos($query, 'available') !== false || strpos($query, 'vacant') !== false || strpos($query, 'most slot') !== false || strpos($query, 'open slot') !== false) {
    $topSlots = array_slice($locations, 0, 3);
    $slotList = "";
    foreach ($topSlots as $s) {
        $slotList .= "• **" . $s['name'] . "**: **" . $s['available_slots'] . "** open slots out of " . $s['total_slots'] . " (Rate: ₹" . $s['price'] . "/hr)\n";
    }
    $localResponse = "🟢 **Top Parking Lots with Maximum Open Slots Right Now:**\n\n" . $slotList . "\nAcross all our " . $totalLocations . " lots, there are currently **" . $totalAvailableSlots . " vacant slots** ready for booking!";
}

// Case: Specific areas (Kalyan, Titwala, Ulhasnagar, Station)
elseif (strpos($query, 'kalyan') !== false || strpos($query, 'titwala') !== false || strpos($query, 'ulhasnagar') !== false || strpos($query, 'station') !== false) {
    $matched = [];
    $searchKey = '';
    if (strpos($query, 'kalyan') !== false) $searchKey = 'kalyan';
    elseif (strpos($query, 'titwala') !== false) $searchKey = 'titwala';
    elseif (strpos($query, 'ulhasnagar') !== false) $searchKey = 'ulhasnagar';
    else $searchKey = 'station';

    foreach ($locations as $l) {
        if (stripos($l['name'], $searchKey) !== false || stripos($l['address'], $searchKey) !== false) {
            $matched[] = $l;
        }
    }

    if (!empty($matched)) {
        $list = "";
        foreach (array_slice($matched, 0, 4) as $m) {
            $list .= "• **" . $m['name'] . "**\n  " . $m['address'] . "\n  Slots: " . $m['available_slots'] . " available | Price: ₹" . $m['four_wheeler_price'] . " (4W) / ₹" . $m['two_wheeler_price'] . " (2W)\n";
        }
        $localResponse = "📍 **Parking Lots Found for '" . ucfirst($searchKey) . "':**\n\n" . $list;
    }
}

// Case: How to cancel / refund
elseif (strpos($query, 'cancel') !== false || strpos($query, 'refund') !== false) {
    $localResponse = "🔄 **Cancellation Policy:**\n\nYou can easily cancel any active booking:\n1. Go to **My Bookings** from the top menu.\n2. Locate your booking and click the **Cancel** button.\n3. Your slot will be instantly released back to the system, and your status will update to 'Cancelled'.";
}

// Case: How to book / steps
elseif (strpos($query, 'how to book') !== false || strpos($query, 'steps') !== false || strpos($query, 'reserve') !== false) {
    $localResponse = "📱 **How to Book a Spot with ParkEasy:**\n\n1. Click **Book Now** or open the **Find Parking & Map** page.\n2. Choose a lot from the interactive map or list.\n3. Click **Book Now** to select your date, entry & exit times.\n4. ParkEasy calculates the exact duration and dynamic fare.\n5. Select your payment method (UPI, QR Code, Card) and get your digital QR ticket!";
}

// 3. Optional: Call Free AI Provider (Pollinations AI) with 3.5s timeout, fallback to local NLP
$aiReply = null;
if (!$localResponse) {
    $systemContext = "You are ParkBot, the helpful and friendly AI assistant for ParkEasy, an urban smart parking reservation platform. " .
        "Real-time data: There are $totalLocations parking lots with $totalAvailableSlots available spots. " .
        "Locations include: Kalyan Jn Parking Area (12 slots, ₹55/hr, EV Charging), Pay And Park (50 slots, ₹25/hr), " .
        "KDMC Parking (45 slots, ₹35/hr, EV Charging), Shri Ganesh Mandir Titwala (28 slots, ₹50/hr), " .
        "Lotus Pay and Park (13 slots, ₹40/hr, EV), Central Railway Pay and Park (25 slots, ₹25/hr), " .
        "CHM College Parking (14 slots, ₹40/hr), Welcome Pay N Park (30 slots, ₹80/hr, EV). " .
        "Provide concise, polite, and practical responses with emojis. Answer the user query directly:";

    $promptText = $systemContext . "\nUser: " . $userMessage . "\nParkBot:";

    // Fast curl to Pollinations AI
    $ch = curl_init('https://text.pollinations.ai/' . urlencode($promptText));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 4);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && !empty($response)) {
        $aiReply = trim($response);
    }
}

$finalReply = $aiReply ?: ($localResponse ?: "👋 Hello! I am **ParkBot AI**, your smart parking assistant.\n\nI can help you with:\n• Finding the cheapest or nearest parking spot\n• Checking real-time slot availability\n• Finding locations with **EV Charging**\n• Estimating parking fees and booking guidance\n\nWhat would you like to know today?");

echo json_encode([
    'success' => true,
    'reply' => $finalReply,
    'timestamp' => date('H:i:s')
]);
?>
