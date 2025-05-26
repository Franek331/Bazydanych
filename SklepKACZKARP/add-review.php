<?php
// Inicjalizacja sesji
session_start();

// Sprawdzenie czy użytkownik jest zalogowany
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Sprawdzenie czy ID produktu zostało przekazane
if (!isset($_GET['product']) || !is_numeric($_GET['product'])) {
    header("Location: dashboard.php");
    exit();
}

$product_id = (int)$_GET['product'];

// Połączenie z bazą danych
$conn = new mysqli("localhost", "root", "", "sklep_internetowy");

// Sprawdzenie połączenia
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Najpierw sprawdźmy, czy produkt istnieje i czy nie jest własnością użytkownika
$check_sql = "SELECT p.*, u.Username FROM products p
              JOIN users u ON p.SellerID = u.UserID
              WHERE p.ProductID = ? AND p.Status = 'active'";
$stmt = $conn->prepare($check_sql);
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

// Sprawdzamy, czy użytkownik nie jest sprzedawcą produktu
if ($product['SellerID'] == $user_id) {
    header("Location: product-details.php?id=" . $product_id);
    exit();
}

// Sprawdzamy, czy użytkownik już dodał recenzję do tego produktu
$check_review_sql = "SELECT * FROM reviews WHERE ProductID = ? AND UserID = ?";
$stmt = $conn->prepare($check_review_sql);
$stmt->bind_param("ii", $product_id, $user_id);
$stmt->execute();
$existing_review = $stmt->get_result();
$has_review = $existing_review->num_rows > 0;
$review_data = $has_review ? $existing_review->fetch_assoc() : null;
$stmt->close();

$message = '';
$error = '';

// Obsługa formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $comment = trim($_POST['comment']);
    
    // Walidacja
    if ($rating < 1 || $rating > 5) {
        $error = "Proszę wybrać ocenę od 1 do 5 gwiazdek.";
    } elseif (empty($comment)) {
        $error = "Komentarz nie może być pusty.";
    } else {
        // Jeśli użytkownik już dodał recenzję, aktualizujemy ją
        if ($has_review) {
            $update_sql = "UPDATE reviews SET Rating = ?, Comment = ?, ReviewDate = NOW() 
                         WHERE ReviewID = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("isi", $rating, $comment, $review_data['ReviewID']);
            
            if ($stmt->execute()) {
                $message = "Twoja opinia została zaktualizowana!";
            } else {
                $error = "Wystąpił błąd podczas aktualizacji opinii.";
            }
        } else {
            // Dodajemy nową recenzję
            $insert_sql = "INSERT INTO reviews (ProductID, UserID, Rating, Comment) 
                         VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("iiis", $product_id, $user_id, $rating, $comment);
            
            if ($stmt->execute()) {
                $message = "Twoja opinia została dodana!";
            } else {
                $error = "Wystąpił błąd podczas dodawania opinii.";
            }
        }
        $stmt->close();
    }
}

// Pobieranie liczby produktów w koszyku, jeśli użytkownik jest zalogowany
$is_logged_in = isset($_SESSION['user_id']);
$cart_count = 0;

if ($is_logged_in) {
    $cart_sql = "SELECT SUM(Quantity) as total_items FROM cart WHERE UserID = ?";
    $stmt = $conn->prepare($cart_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cart_result = $stmt->get_result();
    if ($row = $cart_result->fetch_assoc()) {
        $cart_count = $row['total_items'] ? $row['total_items'] : 0;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $has_review ? "Edytuj opinię" : "Dodaj opinię"; ?> - <?php echo htmlspecialchars($product['Title']); ?></title>
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
        
        .user-menu.active {
            display: block;
        }
        
        .menu-item {
            padding: 15px;
            border-bottom: 1px solid #eaeaea;
            display: block;
        }
        
        .menu-item:last-child {
            border-bottom: none;
        }
        
        .menu-item:hover {
            background-color: #f8f9fa;
        }
        
        a {
            text-decoration: none;
            color: #333;
        }
        
        /* Review form styling */
        .review-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin: 20px 0;
            padding: 20px;
        }
        
        .review-header {
            margin-bottom: 20px;
        }
        
        .review-header h1 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .product-info {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .product-image {
            width: 80px;
            height: 80px;
            margin-right: 15px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #eaeaea;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .product-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .product-details h2 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .product-seller {
            color: #666;
            font-size: 14px;
        }
        
        .alert-success {
            padding: 10px;
            background-color: #dff0d8;
            color: #3c763d;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .alert-error {
            padding: 10px;
            background-color: #f2dede;
            color: #a94442;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
        }
        
        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            margin-bottom: 15px;
        }
        
        .star-rating input {
            display: none;
        }
        
        .star-rating label {
            cursor: pointer;
            width: 40px;
            height: 40px;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24"><path fill="%23ddd" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>');
            background-repeat: no-repeat;
        }
        
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24"><path fill="%23ffca28" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>');
        }
        
        .comment-area {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            min-height: 150px;
            resize: vertical;
            font-size: 16px;
        }
        
        .submit-btn {
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .cancel-btn {
            padding: 10px 20px;
            background-color: #f1f1f1;
            color: #333;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-left: 10px;
        }
        
        .form-actions {
            display: flex;
        }
        
        .footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 20px 0;
            margin-top: 40px;
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
                            <a href="account_management.php" class="menu-item">👤Zarządzaj kontem</a>
                            <a href="add-product.php" class="menu-item">📦Moje oferty</a>
                            <div class="menu-item">🛒Moje kupno</div>
                            <div class="menu-item">  <a style="text-decoration: none; color: inherit;" href="messages.php">💬Wiadomości</a></div>
                             <div class="menu-item">
                                <a style="text-decoration: none; color: inherit;" href="help.php">ℹ️O stronie</a>
                            </div>
                            <a href="logout.php" class="menu-item">🚪Wyloguj</a>
                        <?php else: ?>
                            <a href="login.php" class="menu-item">Zaloguj się</a>
                            <a href="register.php" class="menu-item">Zarejestruj się</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="container">
        <div class="review-container">
            <div class="review-header">
                <h1><?php echo $has_review ? "Edytuj opinię" : "Dodaj opinię"; ?></h1>
                
                <div class="product-info">
                    <div class="product-image">
                        <?php
                        // Pobieramy główne zdjęcie produktu
                        $img_sql = "SELECT ImageURL FROM productimages WHERE ProductID = ? AND IsPrimary = 1 LIMIT 1";
                        $stmt = $conn->prepare($img_sql);
                        $stmt->bind_param("i", $product_id);
                        $stmt->execute();
                        $img_result = $stmt->get_result();
                        $image_url = "default-product-image.jpg"; // Domyślny obraz
                        
                        if ($img_result->num_rows > 0) {
                            $image = $img_result->fetch_assoc();
                            $image_url = $image['ImageURL'];
                        }
                        $stmt->close();
                        ?>
                        <img src="<?php echo htmlspecialchars($image_url); ?>" alt="<?php echo htmlspecialchars($product['Title']); ?>">
                    </div>
                    
                    <div class="product-details">
                        <h2><?php echo htmlspecialchars($product['Title']); ?></h2>
                        <div class="product-seller">Sprzedawca: <?php echo htmlspecialchars($product['Username']); ?></div>
                    </div>
                </div>
                
                <?php if (!empty($message)): ?>
                <div class="alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                <div class="alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Twoja ocena:</label>
                    <div class="star-rating">
                        <input type="radio" id="star5" name="rating" value="5" <?php echo ($has_review && $review_data['Rating'] == 5) ? 'checked' : ''; ?>>
                        <label for="star5" title="5 gwiazdek"></label>
                        
                        <input type="radio" id="star4" name="rating" value="4" <?php echo ($has_review && $review_data['Rating'] == 4) ? 'checked' : ''; ?>>
                        <label for="star4" title="4 gwiazdki"></label>
                        
                        <input type="radio" id="star3" name="rating" value="3" <?php echo ($has_review && $review_data['Rating'] == 3) ? 'checked' : ''; ?>>
                        <label for="star3" title="3 gwiazdki"></label>
                        
                        <input type="radio" id="star2" name="rating" value="2" <?php echo ($has_review && $review_data['Rating'] == 2) ? 'checked' : ''; ?>>
                        <label for="star2" title="2 gwiazdki"></label>
                        
                        <input type="radio" id="star1" name="rating" value="1" <?php echo ($has_review && $review_data['Rating'] == 1) ? 'checked' : ''; ?>>
                        <label for="star1" title="1 gwiazdka"></label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="comment">Twój komentarz:</label>
                    <textarea id="comment" name="comment" class="comment-area" placeholder="Napisz swoją opinię o produkcie..."><?php echo $has_review ? htmlspecialchars($review_data['Comment']) : ''; ?></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="submit-btn"><?php echo $has_review ? "Zaktualizuj opinię" : "Dodaj opinię"; ?></button>
                    <button type="button" class="cancel-btn" onclick="location.href='product-details.php?id=<?php echo $product_id; ?>'">Anuluj</button>
                </div>
            </form>
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
