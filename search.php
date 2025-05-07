<?php
require_once 'db.php';
session_start();

// Handle search request
if (isset($_GET['q'])) {
    $query = $_GET['q'];
    
    // Save search to history if user is logged in
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        $stmt = $conn->prepare("INSERT INTO search_history (user_id, search_query) VALUES (?, ?)");
        $stmt->bind_param("is", $userId, $query);
        $stmt->execute();
    }
    
    // Redirect back to index with search query
    echo "<script>window.location.href = 'index.php?search=" . urlencode($query) . "';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search - MapExplorer</title>
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
        
        .search-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .search-container h1 {
            margin-bottom: 20px;
            color: #4285f4;
        }
        
        .search-form {
            display: flex;
            gap: 10px;
        }
        
        .search-form input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            outline: none;
        }
        
        .search-form input:focus {
            border-color: #4285f4;
            box-shadow: 0 0 0 2px rgba(66, 133, 244, 0.2);
        }
        
        .search-form button {
            padding: 12px 20px;
            background-color: #4285f4;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .search-form button:hover {
            background-color: #3367d6;
        }
        
        .search-history {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        
        .search-history h2 {
            margin-bottom: 20px;
            color: #34a853;
        }
        
        .history-list {
            list-style: none;
        }
        
        .history-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .history-item:last-child {
            border-bottom: none;
        }
        
        .history-query {
            font-weight: 500;
        }
        
        .history-time {
            color: #777;
            font-size: 14px;
        }
        
        .history-actions {
            display: flex;
            gap: 10px;
        }
        
        .history-actions button {
            padding: 8px 12px;
            background-color: #f1f1f1;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .history-actions button:hover {
            background-color: #e0e0e0;
        }
        
        .history-actions .search-again {
            background-color: #4285f4;
            color: white;
        }
        
        .history-actions .search-again:hover {
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
            
            .search-form {
                flex-direction: column;
            }
            
            .search-form button {
                width: 100%;
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
                <a href="javascript:void(0)" onclick="redirectToFavorites()">Favorites</a>
                <a href="search.php" class="active">Search</a>
            </nav>
        </div>
    </header>
    
    <main>
        <div class="search-container">
            <h1>Search Locations</h1>
            <form class="search-form" method="get">
                <input type="text" name="q" placeholder="Enter a location, address, or landmark..." required>
                <button type="submit">Search</button>
            </form>
        </div>
        
        <div class="search-history">
            <h2>Recent Searches</h2>
            <?php
            // Display search history if user is logged in
            if (isset($_SESSION['user_id'])) {
                $userId = $_SESSION['user_id'];
                $stmt = $conn->prepare("SELECT * FROM search_history WHERE user_id = ? ORDER BY searched_at DESC LIMIT 10");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    echo '<ul class="history-list">';
                    while ($row = $result->fetch_assoc()) {
                        echo '<li class="history-item">';
                        echo '<span class="history-query">' . htmlspecialchars($row['search_query']) . '</span>';
                        echo '<span class="history-time">' . date('M j, Y g:i A', strtotime($row['searched_at'])) . '</span>';
                        echo '<div class="history-actions">';
                        echo '<button class="search-again" onclick="searchAgain(\'' . htmlspecialchars($row['search_query']) . '\')">Search Again</button>';
                        echo '</div>';
                        echo '</li>';
                    }
                    echo '</ul>';
                } else {
                    echo '<p class="no-history">No search history yet. Start searching to see your history here.</p>';
                }
            } else {
                echo '<p class="no-history">Sign in to view and save your search history.</p>';
            }
            ?>
        </div>
    </main>
    
    <footer>
        &copy; <?php echo date('Y'); ?> MapExplorer. All rights reserved.
    </footer>
    
    <script>
        function searchAgain(query) {
            window.location.href = 'search.php?q=' + encodeURIComponent(query);
        }
        
        function redirectToDirections() {
            window.location.href = 'index.php#directions';
        }
        
        function redirectToFavorites() {
            window.location.href = 'index.php#favorites';
        }
    </script>
</body>
</html>
