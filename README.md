# 🅿️ ParkEasy - Smart Urban Parking & AI Navigation System

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php&logoColor=white)](https://php.net)
[![MySQL Database](https://img.shields.io/badge/Database-MySQL-4479A1?logo=mysql&logoColor=white)](https://mysql.com)
[![Leaflet OSM](https://img.shields.io/badge/Maps-Leaflet%20%2B%20OpenStreetMap-199900?logo=openstreetmap&logoColor=white)](https://leafletjs.com)
[![License](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

**ParkEasy** is a next-generation smart parking management and booking web application. It helps commuters effortlessly discover vacant parking spots in real-time, predict peak-hour traffic, calculate dynamic fares, navigate via an interactive radar map, and check in using digital QR tickets.

---

## 🌟 Key Features

### 1. 🗺️ Interactive Leaflet & OpenStreetMap Radar
- **100% Free, Zero-Restriction Map API**: Powered by Leaflet.js and OpenStreetMap (no API keys, billing, or quotas needed).
- **Color-Coded Live Pins**:
  - 🟢 **Green (>20 slots)**: High availability.
  - 🟡 **Orange (6–20 slots)**: Moderate rush.
  - 🔴 **Red (≤5 slots)**: Almost full.
- **Interactive Marker Popups**: View tariffs for 2-wheelers and 4-wheelers, addresses, ratings, and a direct **"⚡ Quick Book"** action.
- **GPS "Locate Me"**: Automatic user location detection with real-time distance (in km) calculated to every parking lot.
- **Voice Search (Speech-to-Text)**: Speak parking queries directly using the free HTML5 Web Speech API.
- **Smart Filter Pills**: Instant one-click filtering by *EV Charging*, *Budget Friendly (≤₹30)*, *High Availability*, and *Top Rated*.

### 2. 🤖 ParkBot AI Smart Assistant
- Floating assistant available across all pages.
- Analyzes live MySQL database state to answer natural language queries in real time:
  - *"Where is the cheapest parking?"*
  - *"Which lots have EV charging stations?"*
  - *"Where can I find the most open slots?"*
  - *"How do I cancel my reservation?"*
- Powered by an intelligent contextual local database engine + free LLM inference fallback.

### 3. ⏱️ AI Fare & Rush Hour Predictor
- Calculates spot probability and congestion likelihood based on time of arrival.
- Displays recommendation badges (e.g. *94% Spot Probability (Low Rush)* vs *Peak Hour Warning*).
- Dynamic duration calculator based on entry and exit times with vehicle-specific tariffs.

### 4. 🎟️ Digital QR Ticketing & Receipts
- Generates client-side QR codes holding Pass Code, Vehicle Plate, and Location.
- Built-in **"Print / Save as PDF"** action for paperless entry and attendant scanning.

### 5. 🔄 Atomic Real-time Slot Synchronization
- **On Booking**: Automatically reserves and decrements `available_slots` in MySQL.
- **On Cancellation**: Safely releases the slot and increments `available_slots` back into the pool.

---

## 🛠️ Technology Stack

- **Backend:** PHP 8.0+
- **Database:** MySQL / MariaDB (Prepared Statements & PDO/mysqli)
- **Frontend:** HTML5, Modern CSS (Glassmorphism, Flexbox, CSS Grid), Vanilla JavaScript
- **Mapping:** [Leaflet.js](https://leafletjs.com/) & [OpenStreetMap](https://www.openstreetmap.org/)
- **Voice Recognition:** Native Web Speech API
- **QR Code Engine:** QRCode.js (Client-side)
- **Icons & Typography:** Google Fonts (Outfit & Manrope)

---

## 🚀 Quick Setup & Installation

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) (Apache + MySQL + PHP 8.0+)
- Modern web browser (Chrome, Edge, Firefox, Safari)

### 1. Clone or Download Repository
```bash
git clone https://github.com/Daradeaarti/ParkEasy.git
```
Place the project folder into your web server directory (e.g., `C:/xampp/htdocs/ParkEasy`).

### 2. Configure MySQL Database
1. Open XAMPP Control Panel and start **Apache** and **MySQL**.
2. Go to [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
3. Create a new database named `parkeasy`.
4. Import the SQL files from the `sql/` directory:
   - First import `sql/parkeasy.sql`
   - Run `sql/update_database.php` in your browser or CLI (`php sql/update_database.php`) to apply the schema updates and rich location data.

### 3. Verify Database Connection
Check `config/config.php`:
```php
<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "parkeasy";
$conn = mysqli_connect($servername, $username, $password, $dbname);
?>
```

### 4. Run the Website
- **Using PHP Built-in Server:**
  ```bash
  php -S localhost:8080
  ```
  Open `http://localhost:8080/index.php` in your browser.
- **Using XAMPP Apache:**
  Open `http://localhost/ParkEasy/index.php`.

---

## 📁 Project Structure

```
ParkEasy/
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── images/
│   │   ├── logo.jpg
│   │   ├── qr.png
│   │   └── ...
│   └── js/
│       ├── ai_assistant.js       # ParkBot AI chat widget & speech recognition
│       └── ai_predictor.js       # Dynamic pricing & occupancy rush predictor
├── config/
│   └── config.php               # Database configuration
├── sql/
│   ├── parkeasy.sql             # Base database schema
│   └── update_database.php      # Schema upgrade & data migration script
├── api_ai_chat.php              # AI assistant backend query handler
├── booked.php                   # Booking success page with pass summary
├── confirmbook.php              # Reservation form with live fare calculator
├── index.php                    # Home landing page
├── login.php                    # User authentication
├── logout.php                   # Session termination
├── map.php                      # Interactive Leaflet OpenStreetMap parking radar
├── mybookings.php               # Reservation dashboard & cancellation handler
├── receipt.php                  # Digital QR boarding pass & PDF printer
├── registration.php             # User registration
├── service.php                  # User services dashboard
└── README.md                    # Project documentation
```

---

## 📄 License
This project is licensed under the MIT License.