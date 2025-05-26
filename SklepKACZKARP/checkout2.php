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
$error_message = "";
$success_message = "";

// Pobranie danych użytkownika
$user_data = [];
$user_query = "SELECT * FROM users WHERE UserID = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $user_data = $row;
}
$stmt->close();

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

// Jeśli koszyk jest pusty, przekieruj do koszyka
if (empty($cart_items)) {
    header("Location: cart.php");
    exit;
}

// Obsługa formularza zamówienia
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_order'])) {
    // Walidacja danych
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $payment_method = $_POST['payment_method'];
    
    $is_valid = true;
    
    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($address)) {
        $error_message = "Wszystkie pola adresowe są wymagane";
        $is_valid = false;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Podaj prawidłowy adres email";
        $is_valid = false;
    }
    
    if ($is_valid) {
        // Rozpoczęcie transakcji
        $conn->begin_transaction();
        
        try {
            // Aktualizacja danych użytkownika, jeśli zostały zmienione
            $update_user = $conn->prepare("UPDATE users SET FirstName = ?, LastName = ?, Email = ?, PhoneNumber = ?, Address = ? WHERE UserID = ?");
            $update_user->bind_param("sssssi", $first_name, $last_name, $email, $phone, $address, $user_id);
            $update_user->execute();
            $update_user->close();
            
            // Utworzenie zamówienia
            $order_stmt = $conn->prepare("INSERT INTO orders (BuyerID, TotalAmount, Status, ShippingAddress, PaymentMethod, PaymentStatus) VALUES (?, ?, 'pending', ?, ?, 'pending')");
            $order_stmt->bind_param("idss", $user_id, $total_price, $address, $payment_method);
            $order_stmt->execute();
            $order_id = $conn->insert_id;
            $order_stmt->close();
            
            // Dodanie szczegółów zamówienia
            foreach ($cart_items as $item) {
                $detail_stmt = $conn->prepare("INSERT INTO orderdetails (OrderID, ProductID, Quantity, UnitPrice) VALUES (?, ?, ?, ?)");
                $detail_stmt->bind_param("iiid", $order_id, $item['ProductID'], $item['Quantity'], $item['Price']);
                $detail_stmt->execute();
                $detail_stmt->close();
                
                // Aktualizacja stanu magazynowego produktu
                $update_product = $conn->prepare("UPDATE products SET Quantity = Quantity - ? WHERE ProductID = ? AND Quantity >= ?");
                $update_product->bind_param("iii", $item['Quantity'], $item['ProductID'], $item['Quantity']);
                $update_product->execute();
                $update_product->close();
            }
            
            // Wyczyszczenie koszyka
            $clear_cart = $conn->prepare("DELETE FROM cart WHERE UserID = ?");
            $clear_cart->bind_param("i", $user_id);
            $clear_cart->execute();
            $clear_cart->close();
            
            // Zatwierdzenie transakcji
            $conn->commit();
            
            // Przekierowanie do strony potwierdzenia
            header("Location: order_confirmation.php?order_id=".$order_id);
            exit;
            
        } catch (Exception $e) {
            // Wycofanie transakcji w przypadku błędu
            $conn->rollback();
            $error_message = "Wystąpił błąd podczas przetwarzania zamówienia: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Złóż zamówienie - Serwis Ogłoszeniowy</title>
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
        
        .checkout-container {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }
        
        .checkout-form {
            flex: 1;
            min-width: 300px;
        }
        
        .checkout-summary {
            flex: 0 0 350px;
        }
        
        .form-section {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .radio-group {
            margin-top: 10px;
        }
        
        .radio-option {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .radio-option:hover {
            background-color: #f8f9fa;
            border-color: #3498db;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .radio-option input {
            margin-right: 12px;
        }
        
        .radio-option img {
            height: 30px;
            max-width: 80px;
            object-fit: contain;
        }
        
        .payment-label {
            display: flex;
            align-items: center;
            flex-grow: 1;
        }
        
        .section-heading {
            font-size: 20px;
            margin-bottom: 15px;
            color: #333;
            border-bottom: 1px solid #eaeaea;
            padding-bottom: 10px;
        }
        
        .radio-option input:checked + span {
            font-weight: bold;
            color: #3498db;
        }
        
        .radio-option:has(input:checked) {
            border-color: #3498db;
            background-color: rgba(52, 152, 219, 0.05);
            box-shadow: 0 2px 8px rgba(52, 152, 219, 0.2);
        }
        
        .summary-box {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .product-list {
            margin-bottom: 20px;
        }
        
        .product-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eaeaea;
        }
        
        .product-item:last-child {
            border-bottom: none;
        }
        
        .product-name {
            flex: 1;
        }
        
        .product-quantity {
            width: 60px;
            text-align: center;
            color: #666;
        }
        
        .product-price {
            width: 100px;
            text-align: right;
            font-weight: bold;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 16px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-top: 2px solid #eaeaea;
            font-size: 20px;
            font-weight: bold;
        }
        
        .submit-btn {
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
        
        .submit-btn:hover {
            background-color: #219653;
        }
        
        .error-message {
            background-color: #ffecec;
            color: #e74c3c;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #e74c3c;
        }
        
        .success-message {
            background-color: #e7f7ef;
            color: #27ae60;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #27ae60;
        }
        
        .footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 20px 0;
            margin-top: 40px;
        }
        
        @media (max-width: 768px) {
            .checkout-container {
                flex-direction: column;
            }
            
            .checkout-summary {
                order: -1;
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
                    <a href="dashboard.php"><img src="logo-sklepu.png" alt="logo"></a>
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
                            <a href="account_management.php">👤Zarządzaj kontem</a>
                        </div>
                        <div class="menu-item">
                            <a href="add-product.php">📦Moje oferty</a>
                        </div>
                        <div class="menu-item">
                            <a href="purchase-history.php">🛒Moje kupno</a>
                        </div>
                        <div class="menu-item">
                            <a href="messages.php">💬Wiadomości</a>
                        </div>
                        <div class="menu-item">
                            <a href="help.php">ℹ️O stronie</a>
                        </div>
                        <div class="menu-item">
                            <a href="logout.php">🚪Wyloguj</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="main-content container">
        <div class="section-title">Podsumowanie zamówienia</div>
        
        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <div class="checkout-container">
            <div class="checkout-form">
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="form-section">
                        <h2 class="section-heading">Dane dostawy</h2>
                        
                        <div class="form-group">
                            <label for="first_name">Imię</label>
                            <input type="text" id="first_name" name="first_name" class="form-control" 
                                value="<?php echo htmlspecialchars($user_data['FirstName'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Nazwisko</label>
                            <input type="text" id="last_name" name="last_name" class="form-control" 
                                value="<?php echo htmlspecialchars($user_data['LastName'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control" 
                                value="<?php echo htmlspecialchars($user_data['Email'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Telefon</label>
                            <input type="tel" id="phone" name="phone" class="form-control" 
                                value="<?php echo htmlspecialchars($user_data['PhoneNumber'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Adres dostawy</label>
                            <textarea id="address" name="address" class="form-control" rows="4" required><?php echo htmlspecialchars($user_data['Address'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h2 class="section-heading">Metoda płatności</h2>
                        
                        <div class="radio-group">
                            <label class="radio-option">
                                <div class="payment-label">
                                    <input type="radio" name="payment_method" value="bank_transfer" checked> 
                                    <span>Przelew bankowy</span>
                                </div>
                                <img src="Przelew.png" alt="Przelew">
                            </label>
                            
                            <label class="radio-option">
                                <div class="payment-label">
                                    <input type="radio" name="payment_method" value="blik"> 
                                    <span>BLIK</span>
                                </div>
                                <img src="blik.png" alt="BLIK">
                            </label>
                            
                            <label class="radio-option">
                                <div class="payment-label">
                                    <input type="radio" name="payment_method" value="credit_card"> 
                                    <span>Karta kredytowa</span>
                                </div>
                                <img src="KartaKredytowa.png" alt="Karta kredytowa">
                            </label>
                            
                            <label class="radio-option">
                                <div class="payment-label">
                                    <input type="radio" name="payment_method" value="paypal"> 
                                    <span>PayPal</span>
                                </div>
                                <img src="Peypal.png" alt="PayPal">
                            </label>
                            
                            <label class="radio-option">
                                <div class="payment-label">
                                    <input type="radio" name="payment_method" value="cash_on_delivery"> 
                                    <span>Płatność przy odbiorze</span>
                                </div>
                                <img src="PrzyOdbiorze.png" alt="Płatność przy odbiorze">
                            </label>
                        </div>
                    </div>
                    
                    <button type="submit" name="submit_order" class="submit-btn">Złóż zamówienie</button>
                </form>
            </div>
            
            <div class="checkout-summary">
                <div class="summary-box">
                    <h2 class="section-heading">Twoje produkty</h2>
                    
                    <div class="product-list">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="product-item">
                                <div class="product-name"><?php echo htmlspecialchars($item['Title']); ?></div>
                                <div class="product-quantity">x<?php echo $item['Quantity']; ?></div>
                                <div class="product-price"><?php echo number_format($item['Subtotal'], 2, ',', ' '); ?> zł</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="summary-row">
                        <div>Suma częściowa</div>
                        <div><?php echo number_format($total_price, 2, ',', ' '); ?> zł</div>
                    </div>
                    
                    <div class="summary-row">
                        <div>Koszt dostawy</div>
                        <div>0,00 zł</div>
                    </div>
                    
                    <div class="total-row">
                        <div>Do zapłaty</div>
                        <div><?php echo number_format($total_price, 2, ',', ' '); ?> zł</div>
                    </div>
                </div>
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
        
        // Obsługa wyboru metody płatności
        document.addEventListener('DOMContentLoaded', function() {
            const paymentOptions = document.querySelectorAll('input[name="payment_method"]');
            
            // Dodanie efektu zaznaczenia po kliknięciu w dowolne miejsce na elemencie
            document.querySelectorAll('.radio-option').forEach(function(option) {
                option.addEventListener('click', function() {
                    const radio = this.querySelector('input[type="radio"]');
                    radio.checked = true;
                    
                    // Wywołanie zdarzenia zmiany dla wywołania poniższej funkcji
                    radio.dispatchEvent(new Event('change'));
                });
            });
            
            // Funkcja podświetlająca wybraną opcję
            function highlightSelectedOption() {
                paymentOptions.forEach(function(option) {
                    const parent = option.closest('.radio-option');
                    if (option.checked) {
                        parent.style.borderColor = '#3498db';
                        parent.style.backgroundColor = 'rgba(52, 152, 219, 0.05)';
                        parent.style.boxShadow = '0 2px 8px rgba(52, 152, 219, 0.2)';
                    } else {
                        parent.style.borderColor = '#ddd';
                        parent.style.backgroundColor = '';
                        parent.style.boxShadow = '';
                    }
                });
            }
            
            // Podświetl początkowo wybraną opcję
            highlightSelectedOption();
            
            // Nasłuchuj zmian w wyborze metody płatności
            paymentOptions.forEach(function(option) {
                option.addEventListener('change', highlightSelectedOption);
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>