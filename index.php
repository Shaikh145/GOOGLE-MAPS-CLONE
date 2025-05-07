<?php
require_once 'db.php';

// Start session to manage user data
session_start();

// Initialize favorites array if not exists
if (!isset($_SESSION['favorites'])) {
    $_SESSION['favorites'] = [];
}

// Handle saving a location
if (isset($_POST['save_location']) && isset($_POST['location_name']) && isset($_POST['lat']) && isset($_POST['lng'])) {
    $locationName = $_POST['location_name'];
    $lat = $_POST['lat'];
    $lng = $_POST['lng'];
    
    // Insert into database
    $stmt = $conn->prepare("INSERT INTO saved_locations (user_id, location_name, latitude, longitude) VALUES (?, ?, ?, ?)");
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; // Default to 1 if not logged in
    $stmt->bind_param("isdd", $userId, $locationName, $lat, $lng);
    $stmt->execute();
    
    // Also save to session for immediate use
    $_SESSION['favorites'][] = [
        'name' => $locationName,
        'lat' => $lat,
        'lng' => $lng
    ];
    
    // Redirect to prevent form resubmission
    echo "<script>window.location.href = 'index.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MapExplorer - Your Google Maps Alternative</title>
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <!-- Leaflet Routing Machine CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            color: #333;
            overflow-x: hidden;
        }
        
        .container {
            display: flex;
            flex-direction: column;
            height: 100vh;
        }
        
        /* Header Styles */
        header {
            background: linear-gradient(135deg, #4285f4, #34a853);
            color: white;
            padding: 15px 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 24px;
            font-weight: bold;
        }
        
        .logo-icon {
            font-size: 28px;
            color: #fff;
        }
        
        .nav-links {
            display: flex;
            gap: 20px;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .nav-links a:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .nav-links a.active {
            background-color: rgba(255, 255, 255, 0.3);
            font-weight: bold;
        }
        
        /* Main Content Styles */
        .main-content {
            display: flex;
            flex: 1;
            position: relative;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: 350px;
            background-color: white;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            overflow-y: auto;
            z-index: 900;
            transition: transform 0.3s ease;
        }
        
        .sidebar-toggle {
            position: absolute;
            top: 10px;
            left: 360px;
            background-color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            cursor: pointer;
            z-index: 901;
        }
        
        .sidebar-hidden {
            transform: translateX(-350px);
        }
        
        .sidebar-toggle-hidden {
            left: 10px;
        }
        
        .search-box {
            margin-bottom: 20px;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            outline: none;
            transition: border-color 0.3s;
        }
        
        .search-box input:focus {
            border-color: #4285f4;
            box-shadow: 0 0 0 2px rgba(66, 133, 244, 0.2);
        }
        
        .search-box button {
            width: 100%;
            padding: 12px;
            margin-top: 10px;
            background-color: #4285f4;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .search-box button:hover {
            background-color: #3367d6;
        }
        
        .directions-box {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .directions-box h3 {
            margin-bottom: 15px;
            color: #4285f4;
        }
        
        .directions-input {
            margin-bottom: 10px;
        }
        
        .directions-input input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            margin-bottom: 10px;
            outline: none;
        }
        
        .directions-input input:focus {
            border-color: #4285f4;
            box-shadow: 0 0 0 2px rgba(66, 133, 244, 0.2);
        }
        
        .directions-box button {
            width: 100%;
            padding: 12px;
            background-color: #34a853;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .directions-box button:hover {
            background-color: #2d9249;
        }
        
        .favorites-box {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .favorites-box h3 {
            margin-bottom: 15px;
            color: #ea4335;
        }
        
        .favorites-list {
            max-height: 200px;
            overflow-y: auto;
        }
        
        .favorite-item {
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 4px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .favorite-item:hover {
            background-color: #f0f0f0;
        }
        
        .save-location-box {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .save-location-box h3 {
            margin-bottom: 15px;
            color: #fbbc05;
        }
        
        .save-location-form input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            margin-bottom: 10px;
            outline: none;
        }
        
        .save-location-form button {
            width: 100%;
            padding: 12px;
            background-color: #fbbc05;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .save-location-form button:hover {
            background-color: #f0b400;
        }
        
        /* Map Container Styles */
        .map-container {
            flex: 1;
            position: relative;
        }
        
        #map {
            width: 100%;
            height: 100%;
            z-index: 1;
        }
        
        .map-controls {
            position: absolute;
            bottom: 20px;
            right: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 800;
        }
        
        .map-control-btn {
            width: 40px;
            height: 40px;
            background-color: white;
            border: none;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .map-control-btn:hover {
            background-color: #f5f5f5;
        }
        
        /* Location Info Popup */
        .location-info {
            position: absolute;
            bottom: 20px;
            left: 20px;
            background-color: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            max-width: 300px;
            z-index: 800;
            display: none;
        }
        
        .location-info h3 {
            margin-bottom: 10px;
            color: #4285f4;
        }
        
        .location-info p {
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .location-info button {
            padding: 8px 12px;
            background-color: #4285f4;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        
        .location-info button:hover {
            background-color: #3367d6;
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .sidebar {
                width: 280px;
            }
            
            .sidebar-hidden {
                transform: translateX(-280px);
            }
            
            .sidebar-toggle {
                left: 290px;
            }
            
            .header-content {
                flex-direction: column;
                gap: 10px;
            }
            
            .nav-links {
                width: 100%;
                justify-content: space-between;
            }
            
            .location-info {
                left: 10px;
                right: 10px;
                max-width: calc(100% - 20px);
            }
        }
        
        /* Loading Indicator */
        .loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            display: none;
        }
        
        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #4285f4;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="header-content">
                <div class="logo">
                    <span class="logo-icon">🗺️</span>
                    <span>MapExplorer</span>
                </div>
                <nav class="nav-links">
                    <a href="index.php" class="active">Map</a>
                    <a href="javascript:void(0)" onclick="showDirections()">Directions</a>
                    <a href="javascript:void(0)" onclick="showFavorites()">Favorites</a>
                </nav>
            </div>
        </header>
        
        <div class="main-content">
            <div class="sidebar" id="sidebar">
                <div class="search-box">
                    <h3>Search Location</h3>
                    <input type="text" id="search-input" placeholder="Enter a location...">
                    <button id="search-button">Search</button>
                </div>
                
                <div class="directions-box" id="directions-box">
                    <h3>Get Directions</h3>
                    <div class="directions-input">
                        <input type="text" id="start-location" placeholder="Starting point...">
                        <input type="text" id="end-location" placeholder="Destination...">
                    </div>
                    <button id="get-directions">Get Directions</button>
                </div>
                
                <div class="favorites-box">
                    <h3>Saved Locations</h3>
                    <div class="favorites-list" id="favorites-list">
                        <?php
                        // Display saved locations from session
                        if (!empty($_SESSION['favorites'])) {
                            foreach ($_SESSION['favorites'] as $favorite) {
                                echo '<div class="favorite-item" data-lat="' . $favorite['lat'] . '" data-lng="' . $favorite['lng'] . '">';
                                echo $favorite['name'];
                                echo '</div>';
                            }
                        } else {
                            echo '<p>No saved locations yet.</p>';
                        }
                        ?>
                    </div>
                </div>
                
                <div class="save-location-box">
                    <h3>Save Current Location</h3>
                    <form class="save-location-form" id="save-location-form" method="post">
                        <input type="text" id="location-name" name="location_name" placeholder="Enter a name for this location" required>
                        <input type="hidden" id="lat" name="lat">
                        <input type="hidden" id="lng" name="lng">
                        <button type="submit" name="save_location">Save Location</button>
                    </form>
                </div>
            </div>
            
            <button class="sidebar-toggle" id="sidebar-toggle">
                ≡
            </button>
            
            <div class="map-container">
                <div id="map"></div>
                
                <div class="map-controls">
                    <button class="map-control-btn" id="zoom-in">+</button>
                    <button class="map-control-btn" id="zoom-out">-</button>
                    <button class="map-control-btn" id="current-location">📍</button>
                </div>
                
                <div class="location-info" id="location-info">
                    <h3 id="location-name">Location Name</h3>
                    <p id="location-address">Address information will appear here</p>
                    <p id="location-coordinates">Coordinates: 0.000, 0.000</p>
                    <button id="save-this-location">Save This Location</button>
                </div>
                
                <div class="loading" id="loading">
                    <div class="loading-spinner"></div>
                    <p>Loading...</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- Leaflet Routing Machine JS -->
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
    
    <script>
        // Initialize map
        const map = L.map('map').setView([0, 0], 2);
        
        // Add OpenStreetMap tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(map);
        
        // Initialize variables
        let currentMarker = null;
        let routingControl = null;
        let currentPosition = null;
        
        // Get user's current location on load
        navigator.geolocation.getCurrentPosition(
            function(position) {
                currentPosition = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                
                // Center map on user's location
                map.setView([currentPosition.lat, currentPosition.lng], 13);
                
                // Add marker for current location
                currentMarker = L.marker([currentPosition.lat, currentPosition.lng])
                    .addTo(map)
                    .bindPopup('Your current location')
                    .openPopup();
                
                // Update hidden form fields
                document.getElementById('lat').value = currentPosition.lat;
                document.getElementById('lng').value = currentPosition.lng;
                
                // Reverse geocode to get address
                reverseGeocode(currentPosition.lat, currentPosition.lng);
            },
            function(error) {
                console.error('Error getting location:', error);
                // Default to a central location if geolocation fails
                map.setView([40.7128, -74.0060], 13); // New York City
            }
        );
        
        // Sidebar toggle functionality
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebar-toggle');
        
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('sidebar-hidden');
            sidebarToggle.classList.toggle('sidebar-toggle-hidden');
        });
        
        // Search functionality
        const searchButton = document.getElementById('search-button');
        const searchInput = document.getElementById('search-input');
        
        searchButton.addEventListener('click', function() {
            const query = searchInput.value.trim();
            if (query) {
                showLoading();
                searchLocation(query);
            }
        });
        
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const query = searchInput.value.trim();
                if (query) {
                    showLoading();
                    searchLocation(query);
                }
            }
        });
        
        // Function to search for a location
        function searchLocation(query) {
            // Use Nominatim API for geocoding
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    if (data && data.length > 0) {
                        const location = data[0];
                        const lat = parseFloat(location.lat);
                        const lng = parseFloat(location.lon);
                        
                        // Center map on found location
                        map.setView([lat, lng], 15);
                        
                        // Remove previous marker if exists
                        if (currentMarker) {
                            map.removeLayer(currentMarker);
                        }
                        
                        // Add new marker
                        currentMarker = L.marker([lat, lng])
                            .addTo(map)
                            .bindPopup(location.display_name)
                            .openPopup();
                        
                        // Update hidden form fields
                        document.getElementById('lat').value = lat;
                        document.getElementById('lng').value = lng;
                        
                        // Show location info
                        showLocationInfo(location.display_name, lat, lng);
                    } else {
                        alert('Location not found. Please try a different search term.');
                    }
                })
                .catch(error => {
                    hideLoading();
                    console.error('Error searching location:', error);
                    alert('An error occurred while searching. Please try again.');
                });
        }
        
        // Function to reverse geocode (get address from coordinates)
        function reverseGeocode(lat, lng) {
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                .then(response => response.json())
                .then(data => {
                    if (data && data.display_name) {
                        // Show location info
                        showLocationInfo(data.display_name, lat, lng);
                    }
                })
                .catch(error => {
                    console.error('Error reverse geocoding:', error);
                });
        }
        
        // Function to show location info popup
        function showLocationInfo(name, lat, lng) {
            const locationInfo = document.getElementById('location-info');
            const locationName = document.getElementById('location-name');
            const locationAddress = document.getElementById('location-address');
            const locationCoordinates = document.getElementById('location-coordinates');
            
            locationName.textContent = name.split(',')[0]; // First part of the address
            locationAddress.textContent = name;
            locationCoordinates.textContent = `Coordinates: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
            
            locationInfo.style.display = 'block';
        }
        
        // Save this location button
        document.getElementById('save-this-location').addEventListener('click', function() {
            const locationName = document.getElementById('location-name').textContent;
            document.getElementById('location-name').value = locationName;
            document.getElementById('save-location-form').submit();
        });
        
        // Directions functionality
        const getDirectionsButton = document.getElementById('get-directions');
        const startLocationInput = document.getElementById('start-location');
        const endLocationInput = document.getElementById('end-location');
        
        getDirectionsButton.addEventListener('click', function() {
            const startLocation = startLocationInput.value.trim();
            const endLocation = endLocationInput.value.trim();
            
            if (startLocation && endLocation) {
                showLoading();
                getDirections(startLocation, endLocation);
            } else {
                alert('Please enter both starting point and destination.');
            }
        });
        
        // Function to get directions
        function getDirections(startLocation, endLocation) {
            // First, geocode the start location
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(startLocation)}`)
                .then(response => response.json())
                .then(startData => {
                    if (startData && startData.length > 0) {
                        const startLat = parseFloat(startData[0].lat);
                        const startLng = parseFloat(startData[0].lon);
                        
                        // Then, geocode the end location
                        return fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(endLocation)}`)
                            .then(response => response.json())
                            .then(endData => {
                                if (endData && endData.length > 0) {
                                    const endLat = parseFloat(endData[0].lat);
                                    const endLng = parseFloat(endData[0].lon);
                                    
                                    // Remove previous routing control if exists
                                    if (routingControl) {
                                        map.removeControl(routingControl);
                                    }
                                    
                                    // Create new routing control
                                    routingControl = L.Routing.control({
                                        waypoints: [
                                            L.latLng(startLat, startLng),
                                            L.latLng(endLat, endLng)
                                        ],
                                        routeWhileDragging: true,
                                        lineOptions: {
                                            styles: [{ color: '#4285f4', opacity: 0.7, weight: 6 }]
                                        },
                                        createMarker: function(i, waypoint, n) {
                                            const marker = L.marker(waypoint.latLng);
                                            marker.bindPopup(i === 0 ? 'Start: ' + startLocation : 'End: ' + endLocation);
                                            return marker;
                                        }
                                    }).addTo(map);
                                    
                                    hideLoading();
                                } else {
                                    hideLoading();
                                    alert('Destination not found. Please try a different location.');
                                }
                            });
                    } else {
                        hideLoading();
                        alert('Starting point not found. Please try a different location.');
                    }
                })
                .catch(error => {
                    hideLoading();
                    console.error('Error getting directions:', error);
                    alert('An error occurred while getting directions. Please try again.');
                });
        }
        
        // Map control buttons
        document.getElementById('zoom-in').addEventListener('click', function() {
            map.zoomIn();
        });
        
        document.getElementById('zoom-out').addEventListener('click', function() {
            map.zoomOut();
        });
        
        document.getElementById('current-location').addEventListener('click', function() {
            if (currentPosition) {
                map.setView([currentPosition.lat, currentPosition.lng], 15);
            } else {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        currentPosition = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };
                        
                        map.setView([currentPosition.lat, currentPosition.lng], 15);
                        
                        // Add marker for current location if not exists
                        if (!currentMarker) {
                            currentMarker = L.marker([currentPosition.lat, currentPosition.lng])
                                .addTo(map)
                                .bindPopup('Your current location')
                                .openPopup();
                        } else {
                            currentMarker.setLatLng([currentPosition.lat, currentPosition.lng]);
                        }
                        
                        // Update hidden form fields
                        document.getElementById('lat').value = currentPosition.lat;
                        document.getElementById('lng').value = currentPosition.lng;
                        
                        // Reverse geocode to get address
                        reverseGeocode(currentPosition.lat, currentPosition.lng);
                    },
                    function(error) {
                        console.error('Error getting location:', error);
                        alert('Unable to get your current location. Please check your browser permissions.');
                    }
                );
            }
        });
        
        // Click on favorite location
        document.querySelectorAll('.favorite-item').forEach(item => {
            item.addEventListener('click', function() {
                const lat = parseFloat(this.getAttribute('data-lat'));
                const lng = parseFloat(this.getAttribute('data-lng'));
                
                map.setView([lat, lng], 15);
                
                // Remove previous marker if exists
                if (currentMarker) {
                    map.removeLayer(currentMarker);
                }
                
                // Add new marker
                currentMarker = L.marker([lat, lng])
                    .addTo(map)
                    .bindPopup(this.textContent)
                    .openPopup();
                
                // Update hidden form fields
                document.getElementById('lat').value = lat;
                document.getElementById('lng').value = lng;
                
                // Reverse geocode to get address
                reverseGeocode(lat, lng);
            });
        });
        
        // Map click event to add marker
        map.on('click', function(e) {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;
            
            // Remove previous marker if exists
            if (currentMarker) {
                map.removeLayer(currentMarker);
            }
            
            // Add new marker
            currentMarker = L.marker([lat, lng])
                .addTo(map)
                .bindPopup('Selected location')
                .openPopup();
            
            // Update hidden form fields
            document.getElementById('lat').value = lat;
            document.getElementById('lng').value = lng;
            
            // Reverse geocode to get address
            reverseGeocode(lat, lng);
        });
        
        // Navigation functions
        function showDirections() {
            document.getElementById('directions-box').scrollIntoView({ behavior: 'smooth' });
        }
        
        function showFavorites() {
            document.getElementById('favorites-list').scrollIntoView({ behavior: 'smooth' });
        }
        
        // Loading indicator functions
        function showLoading() {
            document.getElementById('loading').style.display = 'block';
        }
        
        function hideLoading() {
            document.getElementById('loading').style.display = 'none';
        }
    </script>
</body>
</html>
