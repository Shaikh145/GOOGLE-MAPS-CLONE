<?php
require_once 'db.php';
session_start();

// Handle delete request
if (isset($_POST['delete_location']) && isset($_POST['location_id'])) {
    $locationId = $_POST['location_id'];
    
    // Delete from database
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        $stmt = $conn->prepare("DELETE FROM saved_locations WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $locationId, $userId);
        $stmt->execute();
    }
    
    // Redirect to prevent form resubmission
    echo "<script>window.location.href = 'favorites.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Locations - MapExplorer</title>
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
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        header {
            background: linear-gradient(135deg, #4285f4, #34a853);
            color: white;
            padding: 15px 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 24px;
            font-weight: bold;
            text-decoration: none;
            color: white;
        }
        
        .logo-icon {
            font-size: 28px;
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
        
        main {
            flex: 1;
            padding: 30px;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }
        
        .favorites-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        
        .favorites-container h1 {
            margin-bottom: 20px;
            color: #4285f4;
        }
        
        .favorites-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .favorite-card {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .favorite-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .favorite-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #4285f4;
        }
        
        .favorite-address {
            font-size: 14px;
            color: #555;
            margin-bottom: 15px;
            line-height: 1.4;
        }
        
        .favorite-coordinates {
            font-size: 14px;
            color: #777;
            margin-bottom: 15px;
        }
        
        .favorite-actions {
            display: flex;
            gap: 10px;
        }
        
        .favorite-actions button {
            flex: 1;
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        
        .view-on-map {
            background-color: #4285f4;
            color: white;
        }
        
        .view-on-map:hover {
            background-color: #3367d6;
        }
        
        .delete-location {
            background-color: #f1f1f1;
            color: #333;
        }
        
        .delete-location:hover {
            background-color: #e0e0e0;
        }
        
        .no-favorites {
            padding: 30px;
            text-align: center;
            color: #777;
        }
        
        .add-favorite {
            display: block;
            margin: 30px auto 0;
            padding: 12px 20px;
            background-color: #34a853;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .add-favorite:hover {
            background-color: #2d9249;
        }
        
        footer {
            background-color: #333;
            color: white;
            padding: 20px;
            text-align: center;
            margin-top: auto;
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 10px;
            }
            
            .nav-links {
                width: 100%;
                justify-content: space-between;
            }
            
            .favorites-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <a href="index.php" class="logo">
                <span class="logo-icon">🗺️</span>
                <span>MapExplorer</span>
            </a>
            <nav class="nav-links">
                <a href="index.php">Map</a>
                <a href="javascript:void(0)" onclick="redirectToDirections()">Directions</a>
                <a href="favorites.php" class="active">Favorites</a>
                <a href="search.php">Search</a>
            </nav>
        </div>
    </header>
    
    <main>
        <div class="favorites-container">
            <h1>Saved Locations</h1>
            
            <?php
            // Display saved locations from database
            if (isset($_SESSION['user_id'])) {
                $userId = $_SESSION['user_id'];
                $stmt = $conn->prepare("SELECT * FROM saved_locations WHERE user_id = ? ORDER BY created_at DESC");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    echo '<div class="favorites-grid">';
                    while ($row = $result->fetch_assoc()) {
                        echo '<div class="favorite-card">';
                        echo '<div class="favorite-name">' . htmlspecialchars($row['location_name']) . '</div>';
                        
                        // Display address if available
                        if (!empty($row['address'])) {
                            echo '<div class="favorite-address">' . htmlspecialchars($row['address']) . '</div>';
                        }
                        
                        echo '<div class="favorite-coordinates">Lat: ' . $row['latitude'] . ', Lng: ' . $row['longitude'] . '</div>';
                        
                        echo '<div class="favorite-actions">';
                        echo '<button class="view-on-map" onclick="viewOnMap(' . $row['latitude'] . ', ' . $row['longitude'] . ', \'' . htmlspecialchars($row['location_name'], ENT_QUOTES) . '\')">View on Map</button>';
                        
                        echo '<form method="post" style="flex: 1;" onsubmit="return confirm(\'Are you sure you want to delete this location?\')">';
                        echo '<input type="hidden" name="location_id" value="' . $row['id'] . '">';
                        echo '<button type="submit" name="delete_location" class="delete-location">Delete</button>';
                        echo '</form>';
                        
                        echo '</div>';
                        echo '</div>';
                    }
                    echo '</div>';
                } else {
                    echo '<div class="no-favorites">';
                    echo '<p>You haven\'t saved any locations yet.</p>';
                    echo '</div>';
                }
            } else {
                echo '<div class="no-favorites">';
                echo '<p>Sign in to save and view your favorite locations.</p>';
                echo '</div>';
            }
            ?>
            
            <button class="add-favorite" onclick="redirectToMap()">Add New Location</button>
        </div>
    </main>
    
    <footer>
        &copy; <?php echo date('Y'); ?> MapExplorer. All rights reserved.
    </footer>
    
    <script>
        function viewOnMap(lat, lng, name) {
            window.location.href = 'index.php?lat=' + lat + '&lng=' + lng + '&name=' + encodeURIComponent(name);
        }
        
        function redirectToMap() {
            window.location.href = 'index.php';
        }
        
        function redirectToDirections() {
            window.location.href = 'directions.php';
        }
    </script>
</body>
</html>
