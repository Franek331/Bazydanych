<?php
// Inicjalizacja sesji
session_start();

// Sprawdzenie czy użytkownik jest zalogowany
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=purchase-history.php");
    exit();
}

$is_logged_in = true;
$current_user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Połączenie z bazą danych
$conn = new mysqli("localhost", "root", "", "sklep_internetowy");

// Sprawdzenie połączenia
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Pobieranie zamówień użytkownika
$orders_sql = "SELECT o.*, 
                      COUNT(od.OrderDetailID) as ItemCount, 
                      SUM(od.Quantity) as TotalItems
               FROM orders o
               JOIN orderdetails od ON o.OrderID = od.OrderID
               WHERE o.BuyerID = ?
               GROUP BY o.OrderID
               ORDER BY o.OrderDate DESC";

$stmt = $conn->prepare($orders_sql);
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$orders_result = $stmt->get_result();
$orders = [];
while ($order = $orders_result->fetch_assoc()) {
    $orders[] = $order;
}
$stmt->close();

// Pobieranie szczegółów zamówienia jeśli wybrany
$selected_order = null;
$order_details = [];
$order_products = [];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $order_id = (int)$_GET['id'];
    
    // Sprawdź czy zamówienie należy do użytkownika
    $check_sql = "SELECT * FROM orders WHERE OrderID = ? AND BuyerID = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ii", $order_id, $current_user_id);
    $stmt->execute();
    $check_result = $stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $selected_order = $check_result->fetch_assoc();
        
        // Pobierz szczegóły zamówienia
        $details_sql = "SELECT od.*, p.Title, p.`Condition`, c.Name as CategoryName,
                              (SELECT ImageURL FROM productimages WHERE ProductID = p.ProductID AND IsPrimary = TRUE LIMIT 1) as ImageURL
                        FROM orderdetails od
                        JOIN products p ON od.ProductID = p.ProductID
                        JOIN categories c ON p.CategoryID = c.CategoryID
                        WHERE od.OrderID = ?";
        $stmt = $conn->prepare($details_sql);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $details_result = $stmt->get_result();
        
        while ($detail = $details_result->fetch_assoc()) {
            $order_products[] = $detail;
        }
        $stmt->close();
    }
}

// Pobieranie liczby produktów w koszyku
$cart_count = 0;
$cart_sql = "SELECT SUM(Quantity) as total_items FROM cart WHERE UserID = ?";
$stmt = $conn->prepare($cart_sql);
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$cart_result = $stmt->get_result();
if ($row = $cart_result->fetch_assoc()) {
    $cart_count = $row['total_items'] ? $row['total_items'] : 0;
}
$stmt->close();

// Funkcja pomocnicza do formatowania statusu
function formatStatus($status) {
    switch ($status) {
        case 'pending': return 'Oczekujące';
        case 'paid': return 'Opłacone';
        case 'shipped': return 'Wysłane';
        case 'completed': return 'Zrealizowane';
        case 'cancelled': return 'Anulowane';
        default: return $status;
    }
}

// Funkcja pomocnicza do formatowania stanu produktu
function formatCondition($condition) {
    switch ($condition) {
        case 'new': return 'Nowy';
        case 'used': return 'Używany';
        case 'refurbished': return 'Odnowiony';
        default: return $condition;
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historia zakupów - Serwis Ogłoszeniowy</title>
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
        
        /* Header - style z dashboard.php */
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
        
        a {
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
        
        /* Style dla strony historii zakupów */
        .history-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin: 20px 0;
            padding: 20px;
        }
        
        .history-title {
            font-size: 24px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eaeaea;
        }
        
        .history-layout {
            display: flex;
            gap: 20px;
        }
        
        .orders-list {
            width: 300px;
            border-right: 1px solid #eaeaea;
            padding-right: 20px;
        }
        
        .order-details {
            flex: 1;
        }
        
        .order-card {
            padding: 15px;
            border: 1px solid #eaeaea;
            border-radius: 8px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .order-card:hover {
            border-color: #3498db;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .order-card.active {
            border-color: #3498db;
            background-color: #f0f7fc;
        }
        
        .order-id {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .order-date {
            color: #777;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .order-amount {
            font-weight: bold;
            color: #3498db;
        }
        
        .order-status {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .status-pending {
            background-color: #f39c12;
            color: white;
        }
        
        .status-paid {
            background-color: #2ecc71;
            color: white;
        }
        
        .status-shipped {
            background-color: #3498db;
            color: white;
        }
        
        .status-completed {
            background-color: #27ae60;
            color: white;
        }
        
        .status-cancelled {
            background-color: #e74c3c;
            color: white;
        }
        
        .order-details-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .order-details-title {
            font-size: 20px;
            font-weight: bold;
        }
        
        .order-summary {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .order-summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .order-summary-row:last-child {
            margin-bottom: 0;
        }
        
        .product-list {
            margin-top: 20px;
        }
        
        .product-item {
            display: flex;
            border: 1px solid #eaeaea;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .product-image {
            width: 100px;
            height: 100px;
            margin-right: 15px;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .product-info {
            flex: 1;
        }
        
        .product-title {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .product-meta {
            color: #777;
            font-size: 14px;
            margin-bottom: 3px;
        }
        
        .product-price {
            font-weight: bold;
            color: #3498db;
            font-size: 16px;
            margin-top: 5px;
        }
        
        .shipping-address {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            white-space: pre-line;
        }
        
        .no-orders {
            padding: 40px;
            text-align: center;
            color: #777;
        }
        
        .no-orders p {
            margin-bottom: 20px;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            border-radius: 4px;
            text-decoration: none;
        }
        
        .empty-state {
            padding: 40px;
            text-align: center;
            color: #777;
        }
        
        .footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 20px 0;
            margin-top: 40px;
        }
        
        @media (max-width: 768px) {
            .history-layout {
                flex-direction: column;
            }
            
            .orders-list {
                width: 100%;
                border-right: none;
                padding-right: 0;
                border-bottom: 1px solid #eaeaea;
                padding-bottom: 20px;
                margin-bottom: 20px;
            }
        }
    </style>
</head>

<body>
    <header>
        <div class="container">
            <div class="header-top">
                <div class="logo-area">
                    <a href="dashboard.php"><img src="logo-sklepu.png" alt="logo"></a>
                </div>
                
                <div class="search-area">
                    <form class="search-form" action="dashboard.php" method="GET">
                        <input type="text" class="search-input" name="query" placeholder="szukanie itp">
                        <button type="submit" class="search-button">Szukaj</button>
                    </form>
                </div>
                
                <div class="user-area">
                    <div class="cart-icon" onclick="location.href='cart.php'">
                        🛒 <span class="cart-count"><?php echo $cart_count; ?></span>
                    </div>
                    
                    <div class="user-icon" onclick="toggleUserMenu()">
                        <?php echo htmlspecialchars($username); ?>
                    </div>
                    
                    <div class="user-menu" id="userMenu">
                        <div class="menu-item">
                            <a href="account_management.php">Zarządzaj kontem</a>
                        </div>
                        <div class="menu-item">
                            <a href="add-product.php">Moje oferty</a>
                        </div>
                        <div class="menu-item">
                            <a href="purchase-history.php">Moje kupno</a>
                        </div>
                        <div class="menu-item">
                            <a href="messages.php">Wiadomości</a>
                        </div>
                        <div class="menu-item">
                            <a href="help.php">O stronie</a>
                        </div>
                        <div class="menu-item">
                            <a href="logout.php">wyloguj</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="container">
        <div class="history-container">
            <h1 class="history-title">Historia zakupów</h1>
            
            <?php if (empty($orders)): ?>
                <div class="no-orders">
                    <p>Nie masz jeszcze żadnych zakupów.</p>
                    <a href="dashboard.php" class="btn">Przeglądaj oferty</a>
                </div>
            <?php else: ?>
                <div class="history-layout">
                    <div class="orders-list">
                        <h2>Twoje zamówienia</h2>
                        <?php foreach ($orders as $order): ?>
                            <div class="order-card <?php echo (isset($_GET['id']) && $_GET['id'] == $order['OrderID']) ? 'active' : ''; ?>" 
                                 onclick="location.href='purchase-history.php?id=<?php echo $order['OrderID']; ?>'">
                                <div class="order-id">Zamówienie #<?php echo $order['OrderID']; ?></div>
                                <div class="order-date"><?php echo date('d.m.Y H:i', strtotime($order['OrderDate'])); ?></div>
                                <div class="order-amount"><?php echo number_format($order['TotalAmount'], 2, ',', ' '); ?> zł</div>
                                <div class="order-meta"><?php echo $order['TotalItems']; ?> <?php echo $order['TotalItems'] > 1 ? 'produktów' : 'produkt'; ?></div>
                                <span class="order-status status-<?php echo $order['Status']; ?>"><?php echo formatStatus($order['Status']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="order-details">
                        <?php if ($selected_order): ?>
                            <div class="order-details-header">
                                <div class="order-details-title">Szczegóły zamówienia #<?php echo $selected_order['OrderID']; ?></div>
                                <span class="order-status status-<?php echo $selected_order['Status']; ?>"><?php echo formatStatus($selected_order['Status']); ?></span>
                            </div>
                            
                            <div class="order-summary">
                                <h3>Podsumowanie</h3>
                                <div class="order-summary-row">
                                    <span>Data zamówienia:</span>
                                    <span><?php echo date('d.m.Y H:i', strtotime($selected_order['OrderDate'])); ?></span>
                                </div>
                                <div class="order-summary-row">
                                    <span>Metoda płatności:</span>
                                    <span><?php echo $selected_order['PaymentMethod']; ?></span>
                                </div>
                                <div class="order-summary-row">
                                    <span>Status płatności:</span>
                                    <span><?php echo formatStatus($selected_order['PaymentStatus']); ?></span>
                                </div>
                                <div class="order-summary-row">
                                    <span>Kwota:</span>
                                    <span><?php echo number_format($selected_order['TotalAmount'], 2, ',', ' '); ?> zł</span>
                                </div>
                            </div>
                            
                            <h3>Adres dostawy</h3>
                            <div class="shipping-address">
                                <?php echo nl2br(htmlspecialchars($selected_order['ShippingAddress'])); ?>
                            </div>
                            
                            <h3>Zakupione produkty</h3>
                            <div class="product-list">
                                <?php foreach ($order_products as $product): ?>
                                    <div class="product-item">
                                        <div class="product-image">
                                            <img src="<?php echo !empty($product['ImageURL']) ? htmlspecialchars($product['ImageURL']) : 'default-product-image.jpg'; ?>" 
                                                 alt="<?php echo htmlspecialchars($product['Title']); ?>">
                                        </div>
                                        <div class="product-info">
                                            <div class="product-title"><?php echo htmlspecialchars($product['Title']); ?></div>
                                            <div class="product-meta">Stan: <?php echo formatCondition($product['Condition']); ?></div>
                                            <div class="product-meta">Kategoria: <?php echo htmlspecialchars($product['CategoryName']); ?></div>
                                            <div class="product-meta">Ilość: <?php echo $product['Quantity']; ?></div>
                                            <div class="product-price"><?php echo number_format($product['UnitPrice'], 2, ',', ' '); ?> zł</div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <p>Wybierz zamówienie z listy, aby zobaczyć szczegóły.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Serwis Ogłoszeniowy. Wszelkie prawa zastrzeżone.</p>
        </div>
    </footer>

    <script>
        // Funkcja do przełączania menu użytkownika
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
$conn->close();
?>