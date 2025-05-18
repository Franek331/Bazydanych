<?php session_start();?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Serwis Ogłoszeniowy</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        
        body {
            background-color: #f8f9fa;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        /* Header */
        header {
            background-color: #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eaeaea;
        }
        
        .logo-area {
            font-size: 24px;
            font-weight: bold;
        }
        
        .search-area {
            flex-grow: 1;
            margin: 0 20px;
        }
        
        .search-form {
            display: flex;
            width: 100%;
        }
        
        .search-input {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px 0 0 4px;
            font-size: 16px;
        }
        
        .search-button {
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
        }
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eaeaea;
        }
        
        .logo-area {
            font-size: 24px;
            font-weight: bold;
        }
        
        .search-area {
            flex-grow: 1;
            margin: 0 20px;
        }
        
        .search-form {
            display: flex;
            width: 100%;
        }
        
        .search-input {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px 0 0 4px;
            font-size: 16px;
        }
        
        .search-button {
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
        }
        
        .user-area {
            position: relative;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-icon {
            cursor: pointer;
            font-size: 16px;
            padding: 10px;
            border-radius: 50%;
            background-color: #f1f1f1;
        }
        
        .cart-icon {
            position: relative;
            cursor: pointer;
            font-size: 20px;
        }
        
        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 12px;
            font-weight: bold;
        }
        .user-area {
            position: relative;
        }
        
        .user-icon {
            cursor: pointer;
            font-size: 16px;
            padding: 10px;
            border-radius: 50%;
            background-color: #f1f1f1;
        }
        
        .user-menu {
            position: absolute;
            right: 0;
            top: 100%;
            width: 250px;
            background-color: #fff;
            border: 1px solid #eaeaea;
            border-radius: 4px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            z-index: 100;
            display: none;
        }
        a{
            text-decoration: none;
            color: black;
        }
        .user-menu.active {
            display: block;
        }
        
        .menu-item {
            padding: 15px;
            border-bottom: 1px solid #eaeaea;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .menu-item:last-child {
            border-bottom: none;
        }
        
        .menu-item:hover {
            background-color: #f8f9fa;
        }
        
        .menu-item span {
            font-size: 14px;
            color: #666;
        }
        
        /* Main content */
        .main-content {
            padding: 30px 0;
        }
        
        .section-title {
            font-size: 28px;
            margin-bottom: 20px;
            color: #333;
        }
        
        /* Cards layout */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-img {
            height: 200px;
            background-color: #eaeaea;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .card-content {
            padding: 15px;
        }
        
        .card-title {
            font-size: 18px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .card-price {
            font-size: 20px;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 10px;
        }
        
        .card-meta {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #777;
        }
        
        /* Advertisement area */
        .ads-sidebar {
            background-color: #f1f1f1;
            padding: 15px;
            border-radius: 8px;
            margin-left: 20px;
            text-align: center;
        }
        
        /* Categories sidebar */
        .categories-sidebar {
            width: 250px;
            background-color: #fff;
            border-radius: 8px;
            overflow: hidden;
            margin-right: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .category-item {
            padding: 15px;
            border-bottom: 1px solid #eaeaea;
            cursor: pointer;
        }
        
        .category-item:hover {
            background-color: #f8f9fa;
        }
        
        .content-flex {
            display: flex;
        }
        
        .footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 20px 0;
            margin-top: 40px;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .content-flex {
                flex-direction: column;
            }
            
            .categories-sidebar {
                width: 100%;
                margin-right: 0;
                margin-bottom: 20px;
            }
            
            .cards-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }
        
        @media (max-width: 576px) {
            .header-top {
                flex-direction: column;
            }
            
            .search-area {
                margin: 15px 0;
                width: 100%;
            }
            
            .user-area {
                align-self: flex-end;
            }
        }
        
        .search-results {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f1f1f1;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <?php
    // Inicjalizacja sesji

    
    // Sprawdzenie czy użytkownik jest zalogowany
    $is_logged_in = isset($_SESSION['user_id']);
    $username = $is_logged_in ? $_SESSION['username'] : '';
    
    // Połączenie z bazą danych
    $conn = new mysqli("localhost", "root", "", "sklep_internetowy");
    
    // Sprawdzenie połączenia
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Sprawdzenie, czy istnieje zapytanie wyszukiwania
    $search_query = isset($_GET['query']) ? trim($_GET['query']) : '';
    $category_id = isset($_GET['category']) ? (int)$_GET['category'] : null;
    $cart_count = 0;
    if ($is_logged_in) {
        $cart_sql = "SELECT SUM(Quantity) as total_items FROM cart WHERE UserID = ?";
        $stmt = $conn->prepare($cart_sql);
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $cart_result = $stmt->get_result();
        if ($row = $cart_result->fetch_assoc()) {
            $cart_count = $row['total_items'] ? $row['total_items'] : 0;
        }
        $stmt->close();
    }
    // Określenie tytułu sekcji w zależności od zapytania
    $section_title = "Oferty";
    if (!empty($search_query)) {
        $section_title = "Wyniki wyszukiwania dla: \"" . htmlspecialchars($search_query) . "\"";
    } elseif ($category_id) {
        // Pobierz nazwę kategorii
        $cat_sql = "SELECT Name FROM categories WHERE CategoryID = ?";
        $stmt = $conn->prepare($cat_sql);
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $cat_result = $stmt->get_result();
        if ($cat_row = $cat_result->fetch_assoc()) {
            $section_title = "Kategoria: " . htmlspecialchars($cat_row["Name"]);
        }
        $stmt->close();
    }


    ?>

    <header>
        <div class="container">
            <div class="header-top">
                <div class="logo-area">
                    <a href="dashboard.php">Tutaj logo</a>
                </div>
                
                <div class="search-area">
                    <form class="search-form" action="" method="GET">
                        <input type="text" class="search-input" name="query" placeholder="szukanie itp" value="<?php echo htmlspecialchars($search_query); ?>">
                        <?php if ($category_id): ?>
                        <input type="hidden" name="category" value="<?php echo $category_id; ?>">
                        <?php endif; ?>
                        <button type="submit" class="search-button">Szukaj</button>
                    </form>
                </div>
                  <div class="user-area">
                    <?php if ($is_logged_in): ?>
                    <div class="cart-icon" onclick="location.href='cart.php'">
                        🛒 <span class="cart-count" id="cart-count"><?php echo $cart_count; ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="user-icon" onclick="toggleUserMenu()">
                        <?php echo $is_logged_in ? htmlspecialchars($_SESSION['username']) : 'Konto'; ?>
                    </div>
                    <div class="user-menu" id="userMenu">
                        <?php if ($is_logged_in): ?>
                            <div class="menu-item">
                                <a href="account_management.php">Zarządzaj kontem</a>
                            </div>
                            <div class="menu-item">
                                <a href="add-product.php">Moje oferty</a>
                            </div>
                            <div class="menu-item">
                                Moje kupno
                            </div>
                            <div class="menu-item">
                                Wiadomości
                            </div>
                            <div class="menu-item">
                                <a href="logout.php" style="text-decoration: none; color: inherit;">wyloguj</a>
                            </div>
                        <?php else: ?>
                            <div class="menu-item">
                                <a href="login.php" style="text-decoration: none; color: inherit;">Zaloguj się</a>
                            </div>
                            <div class="menu-item">
                                <a href="register.php" style="text-decoration: none; color: inherit;">Zarejestruj się</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="main-content container">
        <div class="section-title"><?php echo $section_title; ?></div>
        
        <div class="content-flex">
            <div class="categories-sidebar">
                <div class="category-item" onclick="location.href='dashboard.php'">Wszystkie kategorie</div>
                
                <?php
                // Pobranie kategorii głównych (bez nadrzędnej kategorii)
                $sql = "SELECT CategoryID, Name FROM categories WHERE ParentCategoryID IS NULL ORDER BY Name";
                $result = $conn->query($sql);
                
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $active = ($category_id == $row["CategoryID"]) ? 'style="background-color: #e9ecef;"' : '';
                        echo '<div class="category-item" ' . $active . ' onclick="location.href=\'?category=' . $row["CategoryID"] . 
                             (empty($search_query) ? '' : '&query=' . urlencode($search_query)) . 
                             '\'">' . htmlspecialchars($row["Name"]) . '</div>';
                    }
                }
                ?>
            </div>
            
            <div style="flex-grow: 1;">
                <div class="cards-grid">
                    <?php
                    // Przygotowanie zapytania SQL z wyszukiwaniem i filtrowaniem kategorii
                    $params = [];
                    $types = "";
                    
                    $sql = "SELECT p.ProductID, p.Title, p.Price, p.PostedDate, p.`Condition`, 
                            c.Name as CategoryName, u.Username as SellerName,
                            (SELECT ImageURL FROM productImages WHERE ProductID = p.ProductID AND IsPrimary = TRUE LIMIT 1) as PrimaryImage
                            FROM products p
                            JOIN categories c ON p.CategoryID = c.CategoryID
                            JOIN users u ON p.SellerID = u.UserID
                            WHERE p.Status = 'active'";
                    
                    // Dodawanie warunku wyszukiwania
                    if (!empty($search_query)) {
                        $sql .= " AND (p.Title LIKE ? OR p.Description LIKE ?)";
                        $search_param = "%" . $search_query . "%";
                        array_push($params, $search_param, $search_param);
                        $types .= "ss";
                    }
                    
                    // Dodawanie warunku kategorii
                    if ($category_id) {
                        // Pobierz wszystkie podkategorie
                        $cat_ids = [$category_id];
                        $get_subcats = function($parent_id) use ($conn, &$cat_ids, &$get_subcats) {
                            $sql = "SELECT CategoryID FROM categories WHERE ParentCategoryID = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $parent_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            while ($row = $result->fetch_assoc()) {
                                $cat_ids[] = $row['CategoryID'];
                                $get_subcats($row['CategoryID']);
                            }
                            $stmt->close();
                        };
                        $get_subcats($category_id);
                        
                        // Zbuduj część zapytania dla kategorii
                        $placeholders = str_repeat('?,', count($cat_ids) - 1) . '?';
                        $sql .= " AND p.CategoryID IN ($placeholders)";
                        foreach ($cat_ids as $id) {
                            array_push($params, $id);
                            $types .= "i";
                        }
                    }
                    
                    $sql .= " ORDER BY p.PostedDate DESC LIMIT 24";
                    
                    // Przygotowanie i wykonanie zapytania
                    $stmt = $conn->prepare($sql);
                    if (!empty($params)) {
                        $stmt->bind_param($types, ...$params);
                    }
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $image = $row["PrimaryImage"] ? $row["PrimaryImage"] : "default-product-image.jpg";
                            $postedDate = new DateTime($row["PostedDate"]);
                            $now = new DateTime();
                            $interval = $postedDate->diff($now);
                            
                            if ($interval->days < 1) {
                                if ($interval->h < 1) {
                                    $timeAgo = $interval->i . " min temu";
                                } else {
                                    $timeAgo = $interval->h . " godz. temu";
                                }
                            } else {
                                $timeAgo = $interval->days . " dni temu";
                            }
                            
                            echo '<div class="card" onclick="location.href=\'product.php?id=' . $row["ProductID"] . '\'">';
                            echo '<div class="card-img">';
                            echo '<img src="' . htmlspecialchars($image) . '" alt="' . htmlspecialchars($row["Title"]) . '" style="max-width: 100%; max-height: 100%;">';
                            echo '</div>';
                            echo '<div class="card-content">';
                            echo '<h3 class="card-title">' . htmlspecialchars($row["Title"]) . '</h3>';
                            echo '<div class="card-price">' . number_format($row["Price"], 2, ',', ' ') . ' zł</div>';
                            echo '<div class="card-meta">';
                            echo '<span>' . htmlspecialchars($row["CategoryName"]) . '</span>';
                            echo '<span>' . $timeAgo . '</span>';
                            echo '</div>';
                            echo '</div>';
                            echo '</div>';
                        }
                    } else {
                        echo '<p>Brak dostępnych ofert spełniających kryteria wyszukiwania.</p>';
                    }
                    
                    $stmt->close();
                    ?>
                </div>
            </div>
            
            <div class="ads-sidebar">
                Nasze reklamy for fun
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Serwis Ogłoszeniowy. Wszelkie prawa zastrzeżone.</p>
        </div>
    </footer>

    <script>
        function toggleUserMenu() {
            document.getElementById('userMenu').classList.toggle('active');
        }
        
        // Zamykanie menu użytkownika po kliknięciu poza nim
        document.addEventListener('click', function(event) {
            const userMenu = document.getElementById('userMenu');
            const userIcon = document.querySelector('.user-icon');
            
            if (!userIcon.contains(event.target) && !userMenu.contains(event.target)) {
                userMenu.classList.remove('active');
            }
        });
    </script>
</body>
</html>
<?php
// Zamknięcie połączenia z bazą danych
$conn->close();
?>