<?php
session_start();

// Sprawdzenie czy użytkownik jest zalogowany
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Połączenie z bazą danych
$conn = new mysqli("localhost", "root", "", "sklep_internetowy");

// Sprawdzenie połączenia
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$action = isset($_POST['action']) ? $_POST['action'] : '';
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

// Obsługa akcji
if ($action == 'update' && $product_id > 0 && $quantity >= 0) {
    if ($quantity == 0) {
        // Usuń przedmiot z koszyka
        $stmt = $conn->prepare("DELETE FROM cart WHERE UserID = ? AND ProductID = ?");
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        $stmt->close();
    } else {
        // Aktualizacja ilości
        $stmt = $conn->prepare("UPDATE cart SET Quantity = ? WHERE UserID = ? AND ProductID = ?");
        $stmt->bind_param("iii", $quantity, $user_id, $product_id);
        $stmt->execute();
        $stmt->close();
    }
    
    // Przekierowanie aby uniknąć ponownego przesłania formularza
    header("Location: cart.php");
    exit;
}

// Pobranie zawartości koszyka
$cart_items = [];
$total_price = 0;

$sql = "SELECT c.CartID, c.ProductID, c.Quantity, p.Title, p.Price, p.SellerID,
        (SELECT ImageURL FROM productimages WHERE ProductID = p.ProductID AND IsPrimary = TRUE LIMIT 1) as PrimaryImage
        FROM cart c
        JOIN products p ON c.ProductID = p.ProductID
        WHERE c.UserID = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $row['Subtotal'] = $row['Price'] * $row['Quantity'];
    $total_price += $row['Subtotal'];
    $cart_items[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Koszyk - Serwis Ogłoszeniowy</title>
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
        
        .main-content {
            padding: 30px 0;
        }
        
        .section-title {
            font-size: 28px;
            margin-bottom: 20px;
            color: #333;
        }
        
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .cart-table th, .cart-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eaeaea;
        }
        
        .cart-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .quantity-input {
            width: 60px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }
        
        .update-btn {
            padding: 8px 12px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
        }
        
        .checkout-section {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .checkout-total {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #eaeaea;
            margin-bottom: 20px;
        }
        
        .checkout-btn {
            width: 100%;
            padding: 15px;
            background-color: #27ae60;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .checkout-btn:hover {
            background-color: #219653;
        }
        
        .empty-cart {
            text-align: center;
            padding: 40px 0;
            color: #666;
        }
        
        .continue-shopping {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        
        .footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 20px 0;
            margin-top: 40px;
        }
        
        @media (max-width: 768px) {
            .cart-table thead {
                display: none;
            }
            
            .cart-table, .cart-table tbody, .cart-table tr, .cart-table td {
                display: block;
                width: 100%;
            }
            
            .cart-table tr {
                margin-bottom: 15px;
                border-bottom: 2px solid #eaeaea;
            }
            
            .cart-table td {
                text-align: right;
                padding-left: 50%;
                position: relative;
            }
            
            .cart-table td::before {
                content: attr(data-label);
                position: absolute;
                left: 0;
                width: 50%;
                padding-left: 15px;
                font-weight: bold;
                text-align: left;
            }
        }
        
        a {
            text-decoration: none;
            color: inherit;
        }
    </style>
</head>
<body>
    <?php
    // Obliczenie łącznej liczby przedmiotów w koszyku
    $cart_count = 0;
    foreach ($cart_items as $item) {
        $cart_count += $item['Quantity'];
    }
    ?>

    <header>
        <div class="container">
            <div class="header-top">
                <div class="logo-area">
                    <a href="dashboard.php">Tutaj logo</a>
                </div>
                
                <div class="user-area">
                    <div class="cart-icon" onclick="location.href='cart.php'">
                        🛒 <span class="cart-count"><?php echo $cart_count; ?></span>
                    </div>
                    
                    <div class="user-icon" onclick="toggleUserMenu()">
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </div>
                    <div class="user-menu" id="userMenu">
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
                            <a href="logout.php">wyloguj</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="main-content container">
        <div class="section-title">Twój koszyk</div>
        
        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <h2>Twój koszyk jest pusty</h2>
                <p>Dodaj produkty do koszyka, aby rozpocząć zakupy.</p>
                <a href="dashboard.php" class="continue-shopping">Kontynuuj zakupy</a>
            </div>
        <?php else: ?>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Produkt</th>
                        <th>Cena</th>
                        <th>Ilość</th>
                        <th>Suma</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item): ?>
                        <tr>
                            <td data-label="Produkt">
                                <div style="display: flex; align-items: center;">
                                    <img src="<?php echo $item['PrimaryImage'] ?: 'default-product-image.jpg'; ?>" alt="<?php echo htmlspecialchars($item['Title']); ?>" class="product-image">
                                    <div style="margin-left: 15px;">
                                        <a href="product.php?id=<?php echo $item['ProductID']; ?>"><?php echo htmlspecialchars($item['Title']); ?></a>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Cena"><?php echo number_format($item['Price'], 2, ',', ' '); ?> zł</td>
                            <td data-label="Ilość">
                                <form method="post" style="display: flex; align-items: center;">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="product_id" value="<?php echo $item['ProductID']; ?>">
                                    <input type="number" name="quantity" value="<?php echo $item['Quantity']; ?>" min="0" class="quantity-input">
                                    <button type="submit" class="update-btn">Aktualizuj</button>
                                </form>
                            </td>
                            <td data-label="Suma"><?php echo number_format($item['Subtotal'], 2, ',', ' '); ?> zł</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="checkout-section">
                <div class="checkout-total">
                    <h3>Suma</h3>
                    <h3><?php echo number_format($total_price, 2, ',', ' '); ?> zł</h3>
                </div>
                
                <a href="checkout.php" class="checkout-btn" style="display: block; text-align: center; text-decoration: none;">Przejdź do kasy</a>
            </div>
        <?php endif; ?>
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
<?php $conn->close(); ?>