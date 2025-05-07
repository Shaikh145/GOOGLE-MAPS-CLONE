<?php
require_once 'db.php';
session_start();

// Handle directions request
if (isset($_GET['start']) && isset($_GET['end'])) {
    $start = $_GET['start'];
    $end = $_GET['end'];
    
    // Save route to history if user is logged in
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        $stmt = $conn->prepare("INSERT INTO route_history (user_id, start_location, end_location) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $userId, $start, $end);
        $stmt->execute();
    }
    
    // Redirect back to index with directions parameters
    echo "<script>window.location.href = 'index.php?start=" . urlencode($start) . "&end=" . urlencode($end) . "';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Directions - MapExplorer</title>
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
        
        .directions-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .directions-container h1 {
            margin-bottom: 20px;
            color: #4285f4;
        }
        
        .directions-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .directions-form .input-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .directions-form .input-group label {
            width: 100px;
            font-weight: 500;
        }
        
        .directions-form .input-group input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            outline: none;
        }
        
        .directions-form .input-group input:focus {
            border-color: #4285f4;
            box-shadow: 0 0 0 2px rgba(66, 133, 244, 0.2);
        }
        
        .directions-form .swap-button {
            align-self: center;
            background-color: #f1f1f1;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 20px;
            transition: background-color 0.3s;
        }
        
        .directions-form .swap-button:hover {
            background-color: #e0e0e0;
        }
        
        .directions-form .options {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }
        
        .directions-form .option {
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
        }
        
        .directions-form .get-directions {
            padding: 12px 20px;
            background-color: #4285f4;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 10px;
        }
        
        .directions-form .get-directions:hover {
            background-color: #3367d6;
        }
        
        .route-history {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        
        .route-history h2 {
            margin-bottom: 20px;
            color: #34a853;
        }
        
        .history-list {
            list-style: none;
        }
        
        .history-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .history-item:last-child {
            border-bottom: none;
        }
        
        .route-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .route-locations {
            flex: 1;
        }
        
        .route-from, .route-to {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 5px;
        }
        
        .route-label {
            font-weight: 500;
            color: #777;
            width: 50px;
        }
        
        .route-value {
            font-weight: 500;
        }
        
        .route-time {
            color: #777;
            font-size: 14px;
        }
        
        .route-actions {
            display: flex;
            gap: 10px;
        }
        
        .route-actions button {
            padding: 8px 12px;
            background-color: #f1f1f1;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .route-actions button:hover {
            background-color: #e0e0e0;
        }
        
        .route-actions .use-route {
            background-color: #4285f4;
            color: white;
        }
        
        .route-actions .use-route:hover {
            background-color: #3367d6;
        }
        
        .no-history {
            padding: 20px;
            text-align: center;
            color: #777;
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
            
            .directions-form .input-group {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .directions-form .input-group label {
                width: 100%;
                margin-bottom: 5px;
            }
            
            .directions-form .options {
                flex-direction: column;
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
                <a href="directions.php" class="active">Directions</a>
                <a href="javascript:void(0)" onclick="redirectToFavorites()">Favorites</a>
                <a href="search.php">Search</a>
            </nav>
        </div>
    </header>
    
    <main>
        <div class="directions-container">
            <h1>Get Directions</h1>
            <form class="directions-form" method="get">
                <div class="input-group">
                    <label for="start">From:</label>
                    <input type="text" id="start" name="start" placeholder="Enter starting point..." required>
                </div>
                
                <button type="button" class="swap-button" onclick="swapLocations()">⇅</button>
                
                <div class="input-group">
                    <label for="end">To:</label>
                    <input type="text" id="end" name="end" placeholder="Enter destination..." required>
                </div>
                
                <div class="options">
                    <label class="option">
                        <input type="radio" name="travel_mode" value="driving" checked>
                        <span>Driving</span>
                    </label>
                    
                    <label class="option">
                        <input type="radio" name="travel_mode" value="walking">
                        <span>Walking</span>
                    </label>
                    
                    <label class="option">
                        <input type="radio" name="travel_mode" value="cycling">
                        <span>Cycling</span>
                    </label>
                </div>
                
                <button type="submit" class="get-directions">Get Directions</button>
            </form>
        </div>
        
        <div class="route-history">
            <h2>Recent Routes</h2>
            <?php
            // Display route history if user is logged in
            if (isset($_SESSION['user_id'])) {
                $userId = $_SESSION['user_id'];
                $stmt = $conn->prepare("SELECT * FROM route_history WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    echo '<ul class="history-list">';
                    while ($row = $result->fetch_assoc()) {
                        echo '<li class="history-item">';
                        echo '<div class="route-details">';
                        echo '<div class="route-locations">';
                        echo '<div class="route-from"><span class="route-label">From:</span> <span class="route-value">' . htmlspecialchars($row['start_location']) . '</span></div>';
                        echo '<div class="route-to"><span class="route-label">To:</span> <span class="route-value">' . htmlspecialchars($row['end_location']) . '</span></div>';
                        echo '</div>';
                        echo '<span class="route-time">' . date('M j, Y g:i A', strtotime($row['created_at'])) . '</span>';
                        echo '</div>';
                        echo '<div class="route-actions">';
                        echo '<button class="use-route" onclick="useRoute(\'' . htmlspecialchars($row['start_location']) . '\', \'' . htmlspecialchars($row['end_location']) . '\')">Use This Route</button>';
                        echo '</div>';
                        echo '</li>';
                    }
                    echo '</ul>';
                } else {
                    echo '<p class="no-history">No route history yet. Get directions to see your history here.</p>';
                }
            } else {
                echo '<p class="no-history">Sign in to view and save your route history.</p>';
            }
            ?>
        </div>
    </main>
    
    <footer>
        &copy; <?php echo date('Y'); ?> MapExplorer. All rights reserved.
    </footer>
    
    <script>
        function swapLocations() {
            const startInput = document.getElementById('start');
            const endInput = document.getElementById('end');
            const temp = startInput.value;
            startInput.value = endInput.value;
            endInput.value = temp;
        }
        
        function useRoute(start, end) {
            document.getElementById('start').value = start;
            document.getElementById('end').value = end;
            document.querySelector('.directions-form').submit();
        }
        
        function redirectToFavorites() {
            window.location.href = 'index.php#favorites';
        }
    </script>
</body>
</html>
