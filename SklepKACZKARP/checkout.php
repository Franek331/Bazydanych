<?php
// Inicjalizacja sesji
session_start();

// Sprawdzenie czy użytkownik jest zalogowany
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=checkout.php" . (isset($_GET['id']) ? "?id=" . $_GET['id'] : ""));
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

// Sprawdzenie, czy ID produktu jest dostępne
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$product_id = (int)$_GET['id'];

// Pobieranie informacji o produkcie
$sql = "SELECT p.*, c.Name as CategoryName, u.Username as SellerName, 
        u.FirstName as SellerFirstName, u.LastName as SellerLastName, u.UserID as SellerID
        FROM products p
        JOIN categories c ON p.CategoryID = c.CategoryID
        JOIN users u ON p.SellerID = u.UserID
        WHERE p.ProductID = ? AND p.Status = 'active'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Produkt nie istnieje lub nie jest aktywny
    header("Location: dashboard.php");
    exit();
}

$product = $result->fetch_assoc();
$stmt->close();

// Sprawdzenie czy użytkownik nie kupuje własnego produktu
if ($product['SellerID'] == $current_user_id) {
    header("Location: product.php?id=$product_id&error=own_product");
    exit();
}

// Pobieranie danych użytkownika (dla wypełnienia formularza)
$user_sql = "SELECT * FROM users WHERE UserID = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();
$stmt->close();

// Obsługa formularza płatności
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    // Walidacja formularza
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $zip_code = trim($_POST['zip_code']);
    $payment_method = $_POST['payment_method'];
    
    if (empty($first_name) || empty($last_name) || empty($address) || empty($city) || empty($zip_code)) {
        $error_message = 'Wszystkie pola adresu są wymagane.';
    } else {
        // Formatowanie adresu dostawy
        $shipping_address = "$first_name $last_name\n$address\n$zip_code $city";
        
        // Rozpocznij transakcję
        $conn->begin_transaction();
        
        try {
            // 1. Sprawdzenie, czy produkt jest nadal dostępny
            $check_sql = "SELECT Status FROM products WHERE ProductID = ? AND Status = 'active' FOR UPDATE";
            $stmt = $conn->prepare($check_sql);
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $check_result = $stmt->get_result();
            
            if ($check_result->num_rows === 0) {
                throw new Exception("Produkt nie jest już dostępny.");
            }
            
            // 2. Utworzenie zamówienia
            $order_sql = "INSERT INTO orders (BuyerID, TotalAmount, Status, ShippingAddress, PaymentMethod, PaymentStatus) 
                          VALUES (?, ?, 'paid', ?, ?, 'completed')";
            $stmt = $conn->prepare($order_sql);
            $stmt->bind_param("idss", $current_user_id, $product['Price'], $shipping_address, $payment_method);
            $stmt->execute();
            $order_id = $conn->insert_id;
            
            // 3. Dodanie szczegółów zamówienia
            $detail_sql = "INSERT INTO orderdetails (OrderID, ProductID, Quantity, UnitPrice) 
                           VALUES (?, ?, 1, ?)";
            $stmt = $conn->prepare($detail_sql);
            $stmt->bind_param("iid", $order_id, $product_id, $product['Price']);
            $stmt->execute();
            
            // 4. Aktualizacja statusu produktu na "sprzedany"
            $update_sql = "UPDATE products SET Status = 'sold' WHERE ProductID = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            
            // Zatwierdź transakcję
            $conn->commit();
            
            $success_message = "Zamówienie zostało złożone pomyślnie!";
        } catch (Exception $e) {
            // Wycofaj transakcję w przypadku błędu
            $conn->rollback();
            $error_message = "Wystąpił błąd: " . $e->getMessage();
        }
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

// Pobieranie głównego zdjęcia produktu
$image_sql = "SELECT ImageURL FROM productimages WHERE ProductID = ? AND IsPrimary = TRUE LIMIT 1";
$stmt = $conn->prepare($image_sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$image_result = $stmt->get_result();
$image = "default-product-image.jpg"; // domyślny obraz
if ($image_row = $image_result->fetch_assoc()) {
    $image = $image_row['ImageURL'];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Realizacja zamówienia - Serwis Ogłoszeniowy</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
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
        
        /* Style dla strony checkoutu */
        .checkout-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin: 20px 0;
            padding: 20px;
        }
        
        .checkout-title {
            font-size: 24px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eaeaea;
        }
        
        .checkout-content {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .checkout-form {
            flex: 1;
            min-width: 300px;
        }
        
        .order-summary {
            width: 350px;
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            border-color: #3498db;
            outline: none;
        }
        
        .product-summary {
            display: flex;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eaeaea;
        }
        
        .product-image {
            width: 80px;
            height: 80px;
            margin-right: 10px;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .product-details {
            flex: 1;
        }
        
        .product-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .product-price {
            font-size: 18px;
            color: #3498db;
            font-weight: bold;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 18px;
            font-weight: bold;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #eaeaea;
        }
        
        .checkout-button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 12px 20px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
        }
        
        .checkout-button:hover {
            background-color: #45a049;
        }
        
        .alert-success {
            background-color: #dff0d8;
            color: #3c763d;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-error {
            background-color: #f2dede;
            color: #a94442;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 20px 0;
            margin-top: 40px;
        }
        
        .success-message {
            text-align: center;
            padding: 40px 20px;
        }
        
        .success-message h2 {
            color: #4CAF50;
            margin-bottom: 20px;
        }
        
        .success-message p {
            margin-bottom: 20px;
        }
        
        .success-message .buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
        }
        
        .success-message .button {
            background-color: #3498db;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
        }
        
        .success-message .button.green {
            background-color: #4CAF50;
        }
        
        @media (max-width: 768px) {
            .checkout-content {
                flex-direction: column;
            }
            
            .order-summary {
                width: 100%;
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
        <?php if ($success_message): ?>
            <div class="checkout-container">
                <div class="success-message">
                    <h2>Dziękujemy za złożenie zamówienia!</h2>
                    <p>Twoje zamówienie zostało pomyślnie złożone i opłacone.</p>
                    <p>Wkrótce otrzymasz potwierdzenie na adres e-mail.</p>
                    <div class="buttons">
                        <a href="dashboard.php" class="button">Powrót do strony głównej</a>
                        <a href="purchase-history.php" class="button green">Zobacz swoje zamówienia</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="checkout-container">
                <h1 class="checkout-title">Realizacja zamówienia</h1>
                
                <?php if ($error_message): ?>
                    <div class="alert-error"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <div class="checkout-content">
                    <div class="checkout-form">
                        <h2>Dane do wysyłki</h2>
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="first_name">Imię</label>
                                <input type="text" id="first_name" name="first_name" value="<?php echo isset($user['FirstName']) ? htmlspecialchars($user['FirstName']) : ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="last_name">Nazwisko</label>
                                <input type="text" id="last_name" name="last_name" value="<?php echo isset($user['LastName']) ? htmlspecialchars($user['LastName']) : ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="address">Adres</label>
                                <input type="text" id="address" name="address" value="<?php echo isset($user['Address']) ? htmlspecialchars($user['Address']) : ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="city">Miasto</label>
                                <input type="text" id="city" name="city" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="zip_code">Kod pocztowy</label>
                                <input type="text" id="zip_code" name="zip_code" required>
                            </div>
                            
                            <h2 style="margin-top: 30px;">Metoda płatności</h2>
                            
                            
                            
                            <div class="form-section">
                        
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
                            <button type="submit" name="place_order" class="checkout-button">Zapłać i zamów</button>
                        </div>
                    </div>
                        </form>
                    </div>
                    
                    <div class="order-summary">
                        <h2>Podsumowanie zamówienia</h2>
                        
                        <div class="product-summary">
                            <div class="product-image">
                                <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($product['Title']); ?>">
                            </div>
                            <div class="product-details">
                                <div class="product-title"><?php echo htmlspecialchars($product['Title']); ?></div>
                                <div class="product-meta">Kategoria: <?php echo htmlspecialchars($product['CategoryName']); ?></div>
                                <div class="product-meta">Sprzedawca: <?php echo htmlspecialchars($product['SellerName']); ?></div>
                                <div class="product-price"><?php echo number_format($product['Price'], 2, ',', ' '); ?> zł</div>
                            </div>
                        </div>
                        
                        <div class="summary-row">
                            <span>Cena produktu:</span>
                            <span><?php echo number_format($product['Price'], 2, ',', ' '); ?> zł</span>
                        </div>
                        
                        <div class="summary-row">
                            <span>Koszt dostawy:</span>
                            <span>0,00 zł</span>
                        </div>
                        
                        <div class="total-row">
                            <span>Razem do zapłaty:</span>
                            <span><?php echo number_format($product['Price'], 2, ',', ' '); ?> zł</span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
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
        
        // Dynamiczne pokazywanie/ukrywanie pól dla karty kredytowej
        document.addEventListener('DOMContentLoaded', function() {
            const paymentMethodSelect = document.getElementById('payment_method');
            const creditCardDetails = document.getElementById('credit_card_details');
            
            if (paymentMethodSelect && creditCardDetails) {
                paymentMethodSelect.addEventListener('change', function() {
                    if (this.value === 'credit_card') {
                        creditCardDetails.style.display = 'block';
                    } else {
                        creditCardDetails.style.display = 'none';
                    }
                });
            }
            
            // Formatowanie numeru karty
            const cardNumberInput = document.getElementById('card_number');
            if (cardNumberInput) {
                cardNumberInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
                    let formattedValue = '';
                    
                    for (let i = 0; i < value.length; i++) {
                        if (i > 0 && i % 4 === 0) {
                            formattedValue += ' ';
                        }
                        formattedValue += value[i];
                    }
                    
                    e.target.value = formattedValue;
                });
            }
            
            // Formatowanie daty ważności
            const expiryDateInput = document.getElementById('expiry_date');
            if (expiryDateInput) {
                expiryDateInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/[^0-9]/gi, '');
                    
                    if (value.length > 2) {
                        value = value.substring(0, 2) + '/' + value.substring(2, 4);
                    }
                    
                    e.target.value = value;
                });
            }
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>