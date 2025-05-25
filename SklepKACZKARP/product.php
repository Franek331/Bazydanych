<?php 
// Inicjalizacja sesji
session_start();

// Sprawdzenie czy użytkownik jest zalogowany
$is_logged_in = isset($_SESSION['user_id']);
$current_user_id = $is_logged_in ? $_SESSION['user_id'] : null;

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
$sql = "SELECT p.*, c.Name as CategoryName, u.Username, u.FirstName, u.LastName, u.Email, u.PhoneNumber 
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

// Pobieranie zdjęć produktu
$images_sql = "SELECT * FROM productimages WHERE ProductID = ? ORDER BY IsPrimary DESC";
$stmt = $conn->prepare($images_sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$images_result = $stmt->get_result();
$images = [];
while ($image = $images_result->fetch_assoc()) {
    $images[] = $image;
}
$stmt->close();

// Domyślny obraz jeśli nie ma żadnych zdjęć
if (empty($images)) {
    $images[] = ['ImageURL' => 'default-product-image.jpg', 'IsPrimary' => true];
}

// Obsługa wysyłania wiadomości
$message_status = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message']) && $is_logged_in) {
    $message_content = trim($_POST['message_content']);
    
    if (!empty($message_content)) {
        // Sprawdzenie, czy nie wysyłamy wiadomości do samego siebie
        if ($current_user_id != $product['SellerID']) {
            $message_sql = "INSERT INTO messages (SenderID, ReceiverID, ProductID, MessageContent) 
                          VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($message_sql);
            $stmt->bind_param("iiis", $current_user_id, $product['SellerID'], $product_id, $message_content);
            
            if ($stmt->execute()) {
                $message_status = '<div class="alert-success">Wiadomość została wysłana!</div>';
            } else {
                $message_status = '<div class="alert-error">Błąd podczas wysyłania wiadomości.</div>';
            }
            $stmt->close();
        } else {
            $message_status = '<div class="alert-error">Nie możesz wysłać wiadomości do samego siebie.</div>';
        }
    } else {
        $message_status = '<div class="alert-error">Treść wiadomości nie może być pusta.</div>';
    }
}

// Pobieranie recenzji produktu
$reviews_sql = "SELECT r.*, u.Username 
                FROM reviews r
                JOIN users u ON r.UserID = u.UserID
                WHERE r.ProductID = ?
                ORDER BY r.ReviewDate DESC";
$stmt = $conn->prepare($reviews_sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$reviews_result = $stmt->get_result();
$reviews = [];
while ($review = $reviews_result->fetch_assoc()) {
    $reviews[] = $review;
}
$stmt->close();

// Obliczanie średniej oceny
$avg_rating = 0;
$review_count = count($reviews);
if ($review_count > 0) {
    $total_rating = 0;
    foreach ($reviews as $review) {
        $total_rating += $review['Rating'];
    }
    $avg_rating = round($total_rating / $review_count, 1);
}

// Sprawdzenie, czy produkt jest w ulubionych użytkownika
$is_favorite = false;
if ($is_logged_in) {
    $fav_sql = "SELECT * FROM favorites WHERE UserID = ? AND ProductID = ?";
    $stmt = $conn->prepare($fav_sql);
    $stmt->bind_param("ii", $current_user_id, $product_id);
    $stmt->execute();
    $is_favorite = ($stmt->get_result()->num_rows > 0);
    $stmt->close();
}

// Formatowanie daty
$posted_date = new DateTime($product['PostedDate']);
$posted_date_formatted = $posted_date->format('d.m.Y H:i');

// Formatowanie stanu produktu
$condition_map = [
    'new' => 'Nowy',
    'used' => 'Używany',
    'refurbished' => 'Odnowiony'
];
$condition = isset($condition_map[$product['Condition']]) ? $condition_map[$product['Condition']] : $product['Condition'];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$product_id = (int)$_GET['id'];

// Pobieranie liczby produktów w koszyku, jeśli użytkownik jest zalogowany
$cart_count = 0;
if ($is_logged_in) {
    $cart_sql = "SELECT SUM(Quantity) as total_items FROM cart WHERE UserID = ?";
    $stmt = $conn->prepare($cart_sql);
    $stmt->bind_param("i", $current_user_id);
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
    <title><?php echo htmlspecialchars($product['Title']); ?> - Serwis Ogłoszeniowy</title>
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
         .favorite-btn, .buy-btn, .add-to-cart-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .favorite-btn {
            background-color: #f1f1f1;
            color: #333;
        }
        
        .favorite-btn.active {
            background-color: #ff9800;
            color: white;
        }
        
        .add-to-cart-btn {
            background-color: #3498db;
            color: white;
        }
        
        .buy-btn {
            background-color: #4CAF50;
            color: white;
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
        
        /* Product details styling */
        .product-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin: 20px 0;
            padding: 20px;
            display: flex;
            flex-wrap: wrap;
        }
        
        .product-images {
            width: 50%;
            padding-right: 20px;
        }
        
        .main-image {
            width: 100%;
            height: 400px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 10px;
            border: 1px solid #eaeaea;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .main-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .thumbnail-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .thumbnail {
            width: 80px;
            height: 80px;
            border: 1px solid #eaeaea;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }
        
        .thumbnail img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }
        
        .thumbnail.active {
            border: 2px solid #3498db;
        }
        
        .product-info {
            width: 50%;
            padding-left: 20px;
        }
        
        .product-title {
            font-size: 24px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .product-price {
            font-size: 28px;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 15px;
        }
        
        .product-category {
            color: #666;
            margin-bottom: 5px;
        }
        
        .product-condition {
            color: #666;
            margin-bottom: 15px;
        }
        
        .product-date {
            color: #666;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .product-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .favorite-btn, .buy-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .favorite-btn {
            background-color: #f1f1f1;
            color: #333;
        }
        
        .favorite-btn.active {
            background-color: #ff9800;
            color: white;
        }
        
        .buy-btn {
            background-color: #4CAF50;
            color: white;
        }
        
        .seller-info {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #eaeaea;
            border-radius: 4px;
        }
        
        .seller-title {
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .seller-detail {
            margin-bottom: 5px;
        }
        
        .description-section, .reviews-section, .message-section {
            width: 100%;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eaeaea;
        }
        
        .section-title {
            font-size: 20px;
            margin-bottom: 15px;
        }
        
        .review-item {
            margin-bottom: 15px;
            padding: 15px;
            border: 1px solid #eaeaea;
            border-radius: 4px;
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .stars {
            color: #ffc107;
        }
        
        .star {
            font-size: 18px;
        }
        
        .message-form {
            width: 100%;
        }
        
        .message-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            min-height: 100px;
            resize: vertical;
            margin-bottom: 10px;
        }
        
        .message-form button {
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
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
        
        .footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 20px 0;
            margin-top: 40px;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .product-images, .product-info {
                width: 100%;
                padding: 0;
            }
            
            .product-info {
                margin-top: 20px;
            }
            
            .main-image {
                height: 300px;
            }
        }
    </style>
</head>

<body>
    <header>
        <div class="container">
            <div class="header-top">
                <div class="logo-area">
                    <a href="dashboard.php">Tutaj logo</a>
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
                            <a href="account_management.php" class="menu-item">Zarządzaj kontem</a>
                            <a href="add-product.php" class="menu-item">Moje oferty</a>
                            <div class="menu-item">Moje kupno</div>
                            <div class="menu-item">  <a style="text-decoration: none; color: inherit;" href="messages.php">Wiadomości</a></div>
                             <div class="menu-item">
                                <a style="text-decoration: none; color: inherit;" href="help.php">O stronie</a>
                            </div>
                            <a href="logout.php" class="menu-item">Wyloguj</a>
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
        <div class="product-container">
            <div class="product-images">
                <div class="main-image">
                    <img id="mainImage" src="<?php echo htmlspecialchars($images[0]['ImageURL']); ?>" alt="<?php echo htmlspecialchars($product['Title']); ?>">
                </div>
                
                <?php if (count($images) > 1): ?>
                <div class="thumbnail-container">
                    <?php foreach ($images as $index => $image): ?>
                    <div class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>" onclick="changeImage('<?php echo htmlspecialchars($image['ImageURL']); ?>', this)">
                        <img src="<?php echo htmlspecialchars($image['ImageURL']); ?>" alt="Miniatura">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="product-info">
                <h1 class="product-title"><?php echo htmlspecialchars($product['Title']); ?></h1>
                <div class="product-price"><?php echo number_format($product['Price'], 2, ',', ' '); ?> zł</div>
                <div class="product-category">Kategoria: <?php echo htmlspecialchars($product['CategoryName']); ?></div>
                <div class="product-condition">Stan: <?php echo $condition; ?></div>
                <div class="product-date">Dodano: <?php echo $posted_date_formatted; ?></div>
                
                <div class="product-actions">
                    <?php if ($is_logged_in): ?>
                    <button class="favorite-btn <?php echo $is_favorite ? 'active' : ''; ?>" onclick="toggleFavorite(<?php echo $product_id; ?>)">
                        <span id="favText"><?php echo $is_favorite ? 'Usuń z ulubionych' : 'Dodaj do ulubionych'; ?></span>
                    </button>
                    <?php endif; ?>
                    
                    <?php if ($is_logged_in && $current_user_id != $product['SellerID']): ?>
                    <button class="buy-btn" onclick="location.href='buy.php?id=<?php echo $product_id; ?>'">Kup teraz</button>
                     <button class="add-to-cart-btn" onclick="addToCart(<?php echo $product_id; ?>)">Dodaj do koszyka</button>
                    <?php endif; ?>
                </div>
                
                <div class="seller-info">
                    <h3 class="seller-title">Informacje o sprzedającym</h3>
                    <div class="seller-detail"><strong>Nazwa użytkownika:</strong> <?php echo htmlspecialchars($product['Username']); ?></div>
                    
                    <?php if (!empty($product['FirstName']) && !empty($product['LastName'])): ?>
                    <div class="seller-detail"><strong>Imię i nazwisko:</strong> <?php echo htmlspecialchars($product['FirstName'] . ' ' . $product['LastName']); ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($product['Email'])): ?>
                    <div class="seller-detail"><strong>Email:</strong> <?php echo htmlspecialchars($product['Email']); ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($product['PhoneNumber'])): ?>
                    <div class="seller-detail"><strong>Telefon:</strong> <?php echo htmlspecialchars($product['PhoneNumber']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="description-section">
                <h2 class="section-title">Opis</h2>
                <div class="product-description">
                    <?php echo nl2br(htmlspecialchars($product['Description'])); ?>
                </div>
            </div>
            
            <?php if ($is_logged_in && $current_user_id != $product['SellerID']): ?>
            <div class="message-section">
                <h2 class="section-title">Napisz do sprzedającego</h2>
                <?php echo $message_status; ?>
                <form class="message-form" method="POST">
                    <textarea name="message_content" placeholder="Twoja wiadomość..." required></textarea>
                    <button type="submit" name="send_message">Wyślij wiadomość</button>
                </form>
            </div>
            <?php endif; ?>
            
            <div class="reviews-section">
                <h2 class="section-title">Opinie (<?php echo $review_count; ?>)</h2>
                
                <?php if ($review_count > 0): ?>
                <div class="avg-rating">
                    <strong>Średnia ocena:</strong> <?php echo $avg_rating; ?>/5
                    <div class="stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="star"><?php echo ($i <= $avg_rating) ? '★' : '☆'; ?></span>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <?php foreach ($reviews as $review): ?>
                <div class="review-item">
                    <div class="review-header">
                        <div>
                            <strong><?php echo htmlspecialchars($review['Username']); ?></strong>
                            <span class="review-date"><?php echo date('d.m.Y', strtotime($review['ReviewDate'])); ?></span>
                        </div>
                        <div class="stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="star"><?php echo ($i <= $review['Rating']) ? '★' : '☆'; ?></span>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="review-comment">
                        <?php echo nl2br(htmlspecialchars($review['Comment'])); ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php else: ?>
                <p>Ten produkt nie ma jeszcze opinii.</p>
                <?php endif; ?>
                
                <?php if ($is_logged_in && $current_user_id != $product['SellerID']): ?>
                <div class="add-review" style="margin-top: 20px;">
                    <a href="add-review.php?product=<?php echo $product_id; ?>" style="background-color: #3498db; color: white; padding: 10px 15px; border-radius: 4px; display: inline-block;">Dodaj opinię</a>
                </div>
                <?php endif; ?>
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
        
        function changeImage(src, thumbnail) {
            // Zmień główne zdjęcie
            document.getElementById('mainImage').src = src;
            
            // Aktualizuj aktywną miniaturę
            document.querySelectorAll('.thumbnail').forEach(thumb => {
                thumb.classList.remove('active');
            });
            thumbnail.classList.add('active');
        }
        
        function toggleFavorite(productId) {
            if (!<?php echo $is_logged_in ? 'true' : 'false'; ?>) {
                alert('Musisz być zalogowany, aby dodać produkt do ulubionych.');
                return;
            }
            
            // W rzeczywistym scenariuszu tutaj byłoby żądanie AJAX do serwera
            // aby dodać/usunąć produkt z ulubionych
            const favBtn = document.querySelector('.favorite-btn');
            const favText = document.getElementById('favText');
            
            if (favBtn.classList.contains('active')) {
                favBtn.classList.remove('active');
                favText.innerText = 'Dodaj do ulubionych';
                // Tutaj żądanie AJAX do usunięcia z ulubionych
            } else {
                favBtn.classList.add('active');
                favText.innerText = 'Usuń z ulubionych';
                // Tutaj żądanie AJAX do dodania do ulubionych
            }
        }
        
        function addToCart(productId) {
            // Wysyłamy żądanie AJAX do serwera aby dodać produkt do koszyka
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'product_id=' + productId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Produkt został dodany do koszyka!');
                    // Aktualizacja licznika koszyka, jeśli istnieje
                    if (document.getElementById('cart-count')) {
                        document.getElementById('cart-count').textContent = data.cart_count;
                    }
                } else {
                    alert(data.message || 'Wystąpił błąd podczas dodawania produktu do koszyka.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Wystąpił błąd podczas dodawania produktu do koszyka.');
            });
        }
    </script>
</body>
</html>
<?php
// Zamknięcie połączenia z bazą danych
$conn->close();
?>