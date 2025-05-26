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
            width: 20%;
        }
        
        /* Filters sidebar */
        .filters-sidebar {
            width: 250px;
            background-color: #fff;
            border-radius: 8px;
            overflow: hidden;
            margin-right: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .filter-section {
            padding: 15px;
            border-bottom: 1px solid #eaeaea;
        }
        
        .filter-section h3 {
            margin-bottom: 15px;
            font-size: 16px;
            color: #333;
        }
        
        .filter-section:last-child {
            border-bottom: none;
        }
        
        .price-inputs {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            margin-bottom: 10px;
        }
        
        .price-inputs input {
            width: 45%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .range-slider {
            width: 100%;
            margin-top: 5px;
            margin-bottom: 15px;
        }
        
        .filter-option {
            margin-bottom: 10px;
        }
        
        .filter-option label {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        
        .filter-option input[type="radio"],
        .filter-option input[type="checkbox"] {
            margin-right: 10px;
        }
        
        .filter-button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
            font-weight: bold;
        }
        
        .filter-button:hover {
            background-color: #2980b9;
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
            
            .filters-sidebar {
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
        
        /* Badge dla stanu produktu */
        .condition-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .condition-new {
            background-color: #2ecc71;
            color: white;
        }
        
        .condition-used {
            background-color: #f39c12;
            color: white;
        }
        
        .condition-refurbished {
            background-color: #9b59b6;
            color: white;
        }
        
        /* Wyczyść filtry link */
        .clear-filters {
            text-align: center;
            margin-top: 10px;
            font-size: 14px;
        }
        
        .clear-filters a {
            color: #e74c3c;
            text-decoration: underline;
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
    
    // Pobierz minimum i maksimum ceny dla suwaka
    $price_sql = "SELECT MIN(Price) as min_price, MAX(Price) as max_price FROM products WHERE Status = 'active'";
    $price_result = $conn->query($price_sql);
    $price_range = $price_result->fetch_assoc();
    $min_price = floor($price_range['min_price']);
    $max_price = ceil($price_range['max_price']);
    
    // Inicjalizacja filtrów z parametrów GET
    $search_query = isset($_GET['query']) ? trim($_GET['query']) : '';
    $category_id = isset($_GET['category']) ? (int)$_GET['category'] : null;
    $min_price_filter = isset($_GET['min_price']) ? (int)$_GET['min_price'] : $min_price;
    $max_price_filter = isset($_GET['max_price']) ? (int)$_GET['max_price'] : $max_price;
    $sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'newest';
    $condition_filter = isset($_GET['condition']) ? $_GET['condition'] : [];
    
    // Pobierz wszystkie kategorie dla filtra
    $categories_sql = "SELECT CategoryID, Name FROM categories ORDER BY Name";
    $categories_result = $conn->query($categories_sql);
    $categories = [];
    while ($cat_row = $categories_result->fetch_assoc()) {
        $categories[$cat_row['CategoryID']] = $cat_row['Name'];
    }
    
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
                    <a href="dashboard.php"><img src="logo-sklepu.png" alt="logo"></a>
                </div>
                
                <div class="search-area">
                    <form class="search-form" action="" method="GET" id="searchForm">
                        <input type="text" class="search-input" name="query" placeholder="szukanie itp" value="<?php echo htmlspecialchars($search_query); ?>">
                        <?php if ($category_id): ?>
                        <input type="hidden" name="category" value="<?php echo $category_id; ?>">
                        <?php endif; ?>
                        <!-- Ukryte pola dla zachowania filtrów przy wyszukiwaniu -->
                        <input type="hidden" name="min_price" value="<?php echo $min_price_filter; ?>">
                        <input type="hidden" name="max_price" value="<?php echo $max_price_filter; ?>">
                        <input type="hidden" name="sort_by" value="<?php echo $sort_by; ?>">
                        <?php if (!empty($condition_filter)): ?>
                            <?php foreach($condition_filter as $cond): ?>
                            <input type="hidden" name="condition[]" value="<?php echo htmlspecialchars($cond); ?>">
                            <?php endforeach; ?>
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
                                <a href="account_management.php">👤Zarządzaj kontem</a>
                            </div>
                            <div class="menu-item">
                                <a href="add-product.php">📦Moje oferty</a>
                            </div>
                            <div class="menu-item">
                                <a href="purchase-history.php">🛒Moje kupno</a>
                            </div>
                            <div class="menu-item">
                                <a style="text-decoration: none; color: inherit;" href="messages.php">💬Wiadomości</a>
                            </div>
                            <div class="menu-item">
                                <a style="text-decoration: none; color: inherit;" href="help.php">ℹ️O stronie</a>
                            </div>
                            <div class="menu-item">
                                <a href="logout.php" style="text-decoration: none; color: inherit;">🚪Wyloguj</a>
                            </div>
                        <?php else: ?>
                            <div class="menu-item">
                                <a href="login.php" style="text-decoration: none; color: inherit;">🔐Zaloguj się</a>
                            </div>
                            <div class="menu-item">
                                <a href="register.php" style="text-decoration: none; color: inherit;">🧾Zarejestruj się</a>
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
            <!-- Sekcja filtrów -->
            <div class="filters-sidebar">
                <form action="" method="GET" id="filterForm">
                    <!-- Zachowanie wyszukiwania -->
                    <?php if (!empty($search_query)): ?>
                    <input type="hidden" name="query" value="<?php echo htmlspecialchars($search_query); ?>">
                    <?php endif; ?>
                    
                    <!-- Zachowanie kategorii -->
                    <?php if ($category_id): ?>
                    <input type="hidden" name="category" value="<?php echo $category_id; ?>">
                    <?php endif; ?>
                    
                    <!-- Filtr cenowy -->
                    <div class="filter-section">
                        <h3>Przedział cenowy</h3>
                        <div class="price-inputs">
                            <input type="number" name="min_price" id="minPrice" min="<?php echo $min_price; ?>" max="<?php echo $max_price; ?>" value="<?php echo $min_price_filter; ?>" step="1">
                            <input type="number" name="max_price" id="maxPrice" min="<?php echo $min_price; ?>" max="<?php echo $max_price; ?>" value="<?php echo $max_price_filter; ?>" step="1">
                        </div>
                        <input type="range" id="priceRange" class="range-slider" 
                               min="<?php echo $min_price; ?>" 
                               max="<?php echo $max_price; ?>" 
                               value="<?php echo $min_price_filter; ?>"
                               step="1">
                        <div id="priceSliderValue"></div>
                    </div>
                    
                    <!-- Filtr kategorii -->
                    <div class="filter-section">
                        <h3>Kategoria</h3>
                        <?php foreach($categories as $id => $name): ?>
                        <div class="filter-option">
                            <label>
                                <input type="radio" name="category" value="<?php echo $id; ?>" 
                                       <?php echo ($category_id == $id) ? 'checked' : ''; ?>>
                                <?php echo htmlspecialchars($name); ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Filtr stanu produktu -->
                    <div class="filter-section">
                        <h3>Stan produktu</h3>
                        <div class="filter-option">
                            <label>
                                <input type="checkbox" name="condition[]" value="new" 
                                       <?php echo (in_array('new', (array)$condition_filter)) ? 'checked' : ''; ?>>
                                Nowy
                            </label>
                        </div>
                        <div class="filter-option">
                            <label>
                                <input type="checkbox" name="condition[]" value="used" 
                                       <?php echo (in_array('used', (array)$condition_filter)) ? 'checked' : ''; ?>>
                                Używany
                            </label>
                        </div>
                        <div class="filter-option">
                            <label>
                                <input type="checkbox" name="condition[]" value="refurbished" 
                                       <?php echo (in_array('refurbished', (array)$condition_filter)) ? 'checked' : ''; ?>>
                                Odnowiony
                            </label>
                        </div>
                    </div>
                    
                    <!-- Filtr sortowania -->
                    <div class="filter-section">
                        <h3>Sortuj według</h3>
                        <div class="filter-option">
                            <label>
                                <input type="radio" name="sort_by" value="newest" 
                                       <?php echo ($sort_by == 'newest') ? 'checked' : ''; ?>>
                                Najnowsze
                            </label>
                        </div>
                        <div class="filter-option">
                            <label>
                                <input type="radio" name="sort_by" value="price_asc" 
                                       <?php echo ($sort_by == 'price_asc') ? 'checked' : ''; ?>>
                                Cena: od najniższej
                            </label>
                        </div>
                        <div class="filter-option">
                            <label>
                                <input type="radio" name="sort_by" value="price_desc" 
                                       <?php echo ($sort_by == 'price_desc') ? 'checked' : ''; ?>>
                                Cena: od najwyższej
                            </label>
                        </div>
                        <div class="filter-option">
                            <label>
                                <input type="radio" name="sort_by" value="alphabetical" 
                                       <?php echo ($sort_by == 'alphabetical') ? 'checked' : ''; ?>>
                                Alfabetycznie
                            </label>
                        </div>
                    </div>
                    
                    <div class="filter-section">
                        <button type="submit" class="filter-button">Zastosuj filtry</button>
                        <div class="clear-filters">
                            <a href="dashboard.php">Wyczyść filtry</a>
                        </div>
                    </div>
                </form>
            </div>
            
            <div style="flex-grow: 1;">
                <div class="cards-grid">
                    <?php
                    // Przygotowanie zapytania SQL z wyszukiwaniem i filtrowaniem
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
                        $sql .= " AND p.CategoryID = ?";
                        array_push($params, $category_id);
                        $types .= "i";
                    }
                    
                    // Dodawanie warunku przedziału cenowego
                    $sql .= " AND p.Price BETWEEN ? AND ?";
                    array_push($params, $min_price_filter, $max_price_filter);
                    $types .= "dd";
                    
                    // Dodawanie warunku stanu produktu
                    if (!empty($condition_filter)) {
                        $condition_placeholders = implode(',', array_fill(0, count($condition_filter), '?'));
                        $sql .= " AND p.`Condition` IN ($condition_placeholders)";
                        foreach ($condition_filter as $cond) {
                            array_push($params, $cond);
                            $types .= "s";
                        }
                    }
                    
                    // Dodawanie sortowania
                    switch ($sort_by) {
                        case 'price_asc':
                            $sql .= " ORDER BY p.Price ASC";
                            break;
                        case 'price_desc':
                            $sql .= " ORDER BY p.Price DESC";
                            break;
                        case 'alphabetical':
                            $sql .= " ORDER BY p.Title ASC";
                            break;
                        case 'newest':
                        default:
                            $sql .= " ORDER BY p.PostedDate DESC";
                            break;
                    }
                    
                    $sql .= " LIMIT 24";
                    
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
                            
                            // Klasa dla odznaki stanu produktu
                            $conditionClass = "";
                            $conditionText = "";
                            switch ($row["Condition"]) {
                                case "new":
                                    $conditionClass = "condition-new";
                                    $conditionText = "Nowy";
                                    break;
                                case "used":
                                    $conditionClass = "condition-used";
                                    $conditionText = "Używany";
                                    break;
                                case "refurbished":
                                    $conditionClass = "condition-refurbished";
                                    $conditionText = "Odnowiony";
                                    break;
                            }
                            
                            echo '<div class="card" onclick="location.href=\'product.php?id=' . $row["ProductID"] . '\'">';
                            echo '<div class="card-img">';
                            echo '<img src="' . htmlspecialchars($image) . '" alt="' . htmlspecialchars($row["Title"]) . '" style="max-width: 100%; max-height: 100%;">';
                            echo '</div>';
                            echo '<div class="card-content">';
                            echo '<span class="condition-badge ' . $conditionClass . '">' . $conditionText . '</span>';
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
                <img src="reklama1.png" alt="reklama" style="width: 100%; height: auto; border-radius: 8px;">
                <img src="reklama2.png" alt="reklama" style="width: 100%; height: auto; border-radius: 8px; margin-top: 15px;">
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Serwis Ogłoszeniowy. Wszelkie prawa zastrzeżone.</p>
        </div>
        <!-- <p  style="float: left;">Autorzy: Franciszek Karpiuk, Jakub Kaczmarek </p>
        <br>
        <p style="float: left;">Kontakt: </p>
        <br>
        <p style="float: left;">Wersja aplikacji: 1.04.25 Testowa</p> -->
    </footer>

    <script>

        document.addEventListener('DOMContentLoaded', function() {
    // User menu functionality
    function toggleUserMenu() {
        document.getElementById('userMenu').classList.toggle('active');
    }
    
    // Close user menu when clicking outside
    document.addEventListener('click', function(event) {
        const userMenu = document.getElementById('userMenu');
        const userIcon = document.querySelector('.user-icon');
        
        if (userMenu && userIcon && !userIcon.contains(event.target) && !userMenu.contains(event.target)) {
            userMenu.classList.remove('active');
        }
    });
    
    // Price range slider functionality
    const priceRange = document.getElementById('priceRange');
    const minPrice = document.getElementById('minPrice');
    const maxPrice = document.getElementById('maxPrice');
    const priceSliderValue = document.getElementById('priceSliderValue');
    
    if (priceRange && minPrice && maxPrice && priceSliderValue) {
        // Update values after slider movement
        priceRange.addEventListener('input', function() {
            minPrice.value = this.value;
            updatePriceSliderValue();
        });
        
        // Update slider value after min field change
        minPrice.addEventListener('input', function() {
            if (parseInt(this.value) > parseInt(maxPrice.value)) {
                this.value = maxPrice.value;
            }
            priceRange.value = this.value;
            updatePriceSliderValue();
        });
        
        // Update displayed price range value
        function updatePriceSliderValue() {
            priceSliderValue.textContent = `Od ${minPrice.value} do ${maxPrice.value} zł`;
        }
        
        // Initialize slider value display
        updatePriceSliderValue();
    }
    
    // Filter form handling
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        filterForm.addEventListener('change', function(e) {
            // Automatically submit form when sorting options or category changes
            if (e.target.name === 'sort_by' || e.target.name === 'category') {
                this.submit();
            }
        });
    }
    
    // Mobile filters toggle functionality
    const filtersSidebar = document.querySelector('.filters-sidebar');
    const mainContentContainer = document.querySelector('.main-content .container');
    
    if (filtersSidebar && mainContentContainer) {
        const filterToggle = document.createElement('button');
        filterToggle.textContent = 'Pokaż filtry';
        filterToggle.classList.add('filter-button');
        filterToggle.style.marginBottom = '15px';
        filterToggle.style.display = 'none';
        
        // Add button before content
        mainContentContainer.insertBefore(filterToggle, mainContentContainer.firstChild);
        
        // Check screen size and adjust view
        function checkScreenSize() {
            if (window.innerWidth <= 768) {
                filterToggle.style.display = 'block';
                filtersSidebar.style.display = 'none';
                filterToggle.textContent = 'Pokaż filtry';
            } else {
                filterToggle.style.display = 'none';
                filtersSidebar.style.display = 'block';
            }
        }
        
        // Handle filter toggle button click
        filterToggle.addEventListener('click', function() {
            if (filtersSidebar.style.display === 'none' || filtersSidebar.style.display === '') {
                filtersSidebar.style.display = 'block';
                this.textContent = 'Ukryj filtry';
            } else {
                filtersSidebar.style.display = 'none';
                this.textContent = 'Pokaż filtry';
            }
        });
        
        // Check screen size on page load and resize
        window.addEventListener('load', checkScreenSize);
        window.addEventListener('resize', checkScreenSize);
    }
    
    // Cart functionality
    function addToCart(productId, quantity = 1) {
        // Check if user is logged in (PHP variable converted to JS boolean)
        const isLoggedIn = window.isLoggedIn || false; // This should be set elsewhere based on PHP
        
        if (isLoggedIn) {
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&quantity=${quantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const cartCount = document.getElementById('cart-count');
                    if (cartCount) {
                        cartCount.textContent = parseInt(cartCount.textContent) + quantity;
                    }
                    alert('Produkt dodany do koszyka!');
                } else {
                    alert('Wystąpił błąd: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Wystąpił błąd podczas dodawania do koszyka.');
            });
        } else {
            alert('Musisz być zalogowany, aby dodać produkt do koszyka.');
            window.location.href = 'login.php';
        }
    }
    
    // Make addToCart available globally
    window.addToCart = addToCart;
    
    // Card animation handling
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px)';
            this.style.boxShadow = '0 5px 15px rgba(0, 0, 0, 0.2)';
            this.style.transition = 'transform 0.3s ease, box-shadow 0.3s ease';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.1)';
        });
    });
    
    // Make toggleUserMenu available globally
    window.toggleUserMenu = toggleUserMenu;
});
    </script>