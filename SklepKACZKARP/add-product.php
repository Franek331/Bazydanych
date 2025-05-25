<?php
// Inicjalizacja sesji
session_start();

// Sprawdzenie czy użytkownik jest zalogowany, jeśli nie - przekierowanie do strony logowania
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Połączenie z bazą danych
$conn = new mysqli("localhost", "root", "", "sklep_internetowy");

// Sprawdzenie połączenia
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$success_message = "";
$error_message = "";

// Obsługa usuwania produktu
if (isset($_POST['delete_product']) && isset($_POST['product_id'])) {
    $product_id = $_POST['product_id'];
    
    // Sprawdzenie czy produkt należy do użytkownika
    $stmt = $conn->prepare("SELECT ProductID FROM products WHERE ProductID = ? AND SellerID = ?");
    $stmt->bind_param("ii", $product_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        // Rozpoczęcie transakcji
        $conn->begin_transaction();
        
        try {
            // Usunięcie zdjęć z serwera
            $stmt = $conn->prepare("SELECT ImageURL FROM productimages WHERE ProductID = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $images_result = $stmt->get_result();
            
            while ($image = $images_result->fetch_assoc()) {
                if (file_exists($image['ImageURL'])) {
                    unlink($image['ImageURL']);
                }
            }
            
            // Usunięcie produktu z bazy danych (kaskadowe usuwanie usunie też zdjęcia z bazy)
            $stmt = $conn->prepare("DELETE FROM products WHERE ProductID = ? AND SellerID = ?");
            $stmt->bind_param("ii", $product_id, $user_id);
            $stmt->execute();
            
            // Zatwierdzenie transakcji
            $conn->commit();
            
            $success_message = "Ogłoszenie zostało usunięte pomyślnie.";
        } catch (Exception $e) {
            // Wycofanie transakcji w przypadku błędu
            $conn->rollback();
            $error_message = "Wystąpił błąd podczas usuwania ogłoszenia.";
        }
    } else {
        $error_message = "Nie masz uprawnień do usunięcia tego ogłoszenia.";
    }
}

// Obsługa zmiany statusu produktu
if (isset($_POST['change_status']) && isset($_POST['product_id']) && isset($_POST['new_status'])) {
    $product_id = $_POST['product_id'];
    $new_status = $_POST['new_status'];
    
    // Sprawdzenie czy status jest prawidłowy
    if (in_array($new_status, ['active', 'sold', 'cancelled'])) {
        // Sprawdzenie czy produkt należy do użytkownika
        $stmt = $conn->prepare("SELECT ProductID FROM products WHERE ProductID = ? AND SellerID = ?");
        $stmt->bind_param("ii", $product_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $stmt = $conn->prepare("UPDATE products SET Status = ? WHERE ProductID = ?");
            $stmt->bind_param("si", $new_status, $product_id);
            
            if ($stmt->execute()) {
                $success_message = "Status ogłoszenia został zmieniony pomyślnie.";
            } else {
                $error_message = "Wystąpił błąd podczas zmiany statusu ogłoszenia.";
            }
        } else {
            $error_message = "Nie masz uprawnień do zmiany statusu tego ogłoszenia.";
        }
    } else {
        $error_message = "Nieprawidłowy status.";
    }
}

// Pobieranie ogłoszeń użytkownika
$stmt = $conn->prepare(
    "SELECT p.ProductID, p.Title, p.Price, p.PostedDate, p.Status, p.Quantity, 
     (SELECT COUNT(*) FROM orderdetails od JOIN orders o ON od.OrderID = o.OrderID 
      WHERE od.ProductID = p.ProductID AND o.Status != 'cancelled') AS OrderCount,
     (SELECT ImageURL FROM productimages WHERE ProductID = p.ProductID AND IsPrimary = 1 LIMIT 1) AS MainImage
     FROM products p
     WHERE p.SellerID = ?
     ORDER BY p.PostedDate DESC"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$products_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moje ogłoszenia - Serwis Ogłoszeniowy</title>
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
        
        .add-product-btn {
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 12px 24px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .add-product-btn:hover {
            background-color: #2980b9;
        }
        
        /* Alert styling */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d5f5e3;
            color: #2ecc71;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #e74c3c;
        }
        
        /* Product list styling */
        .products-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .product-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .product-image {
            width: 100%;
            height: 200px;
            background-color: #f1f1f1;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .no-image {
            color: #aaa;
            text-align: center;
            font-size: 14px;
        }
        
        .product-details {
            padding: 15px;
        }
        
        .product-title {
            font-size: 18px;
            margin-bottom: 10px;
            color: #333;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .product-price {
            font-size: 20px;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 10px;
        }
        
        .product-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .status-active {
            background-color: #d5f5e3;
            color: #2ecc71;
        }
        
        .status-sold {
            background-color: #f8d7da;
            color: #e74c3c;
        }
        
        .status-cancelled {
            background-color: #eaecef;
            color: #7f8c8d;
        }
        
        .product-date {
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 10px;
        }
        
        .product-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 14px;
            color: #7f8c8d;
        }
        
        .product-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
        }
        
        .action-btn {
            padding: 8px 15px;
            border-radius: 4px;
            font-size: 14px;
            text-align: center;
            cursor: pointer;
            text-decoration: none;
            border: none;
        }
        
        .btn-view {
            background-color: #3498db;
            color: white;
        }
        
        .btn-edit {
            background-color: #f39c12;
            color: white;
        }
        
        .btn-delete {
            background-color: #e74c3c;
            color: white;
        }
        
        .status-select {
            width: 100%;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
            margin-bottom: 10px;
        }
        
        .no-products {
            text-align: center;
            padding: 30px;
            font-size: 18px;
            color: #7f8c8d;
            grid-column: 1 / -1;
        }
        
        /* Footer styling */
        .footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 20px 0;
            margin-top: 40px;
        }
        
        /* Modal styling */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border-radius: 8px;
            width: 400px;
            max-width: 90%;
        }
        
        .modal-header {
            margin-bottom: 15px;
        }
        
        .modal-title {
            font-size: 20px;
            color: #333;
        }
        
        .modal-body {
            margin-bottom: 20px;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .close-modal {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close-modal:hover,
        .close-modal:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        
        .btn-cancel {
            background-color: #7f8c8d;
            color: white;
        }
        
        .btn-confirm {
            background-color: #e74c3c;
            color: white;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .products-list {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
            
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
    </style>
</head>

<body>
    <header>
        <div class="container">
            <div class="header-top">
                <div class="logo-area">
                    <a href="dashboard.php" style="text-decoration: none; color: inherit;"><img src="logo-sklepu.png" alt="logo"></a>
                </div>
                
                <div class="search-area">
                    <form class="search-form" action="search.php" method="GET">
                        <input type="text" class="search-input" name="query" placeholder="Czego szukasz?">
                        <button type="submit" class="search-button">Szukaj</button>
                    </form>
                </div>
                
                <div class="user-area">
                    <div class="user-icon" onclick="toggleUserMenu()">
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </div>
                    
                    <div class="user-menu" id="userMenu">
                        <div class="menu-item">
                            <a href="account_management.php" style="text-decoration: none; color: inherit;">Zarządzaj kontem</a>
                        </div>
                        <div class="menu-item">
                            <a href="add-product.php" style="text-decoration: none; color: inherit;">Moje oferty</a>
                        </div>
                        <div class="menu-item">
                            <a href="purchase-history.php" style="text-decoration: none; color: inherit;">Moje kupno</a>
                        </div>
                        <div class="menu-item">
                            <a href="messages.php" style="text-decoration: none; color: inherit;">Wiadomości</a>
                        </div>
                         <div class="menu-item">
                                <a style="text-decoration: none; color: inherit;" href="help.php">O stronie</a>
                            </div>
                        <div class="menu-item">
                            <a href="logout.php" style="text-decoration: none; color: inherit;">Wyloguj</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="main-content container">
        <div class="section-title">Moje ogłoszenia</div>

        <?php if(!empty($success_message)): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if(!empty($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <a href="add.php" class="add-product-btn">Dodaj nowe ogłoszenie</a>
        
        <div class="products-list">
            <?php if($products_result->num_rows > 0): ?>
                <?php while($product = $products_result->fetch_assoc()): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <?php if(!empty($product['MainImage'])): ?>
                                <img src="<?php echo htmlspecialchars($product['MainImage']); ?>" alt="<?php echo htmlspecialchars($product['Title']); ?>">
                            <?php else: ?>
                                <div class="no-image">Brak zdjęcia</div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-details">
                            <h3 class="product-title" title="<?php echo htmlspecialchars($product['Title']); ?>">
                                <?php echo htmlspecialchars($product['Title']); ?>
                            </h3>
                            
                            <div class="product-price">
                                <?php echo number_format($product['Price'], 2, ',', ' '); ?> PLN
                            </div>
                            
                            <div class="product-status status-<?php echo $product['Status']; ?>">
                                <?php 
                                switch($product['Status']) {
                                    case 'active':
                                        echo 'Aktywne';
                                        break;
                                    case 'sold':
                                        echo 'Sprzedane';
                                        break;
                                    case 'cancelled':
                                        echo 'Anulowane';
                                        break;
                                }
                                ?>
                            </div>
                            
                            <div class="product-date">
                                Dodano: <?php echo date('d.m.Y, H:i', strtotime($product['PostedDate'])); ?>
                            </div>
                            
                            <div class="product-stats">
                                <div>Ilość: <?php echo $product['Quantity']; ?></div>
                                <div>Zamówienia: <?php echo $product['OrderCount']; ?></div>
                            </div>
                            
                            <?php if($product['Status'] === 'active'): ?>
                                <form method="post" action="">
                                    <input type="hidden" name="product_id" value="<?php echo $product['ProductID']; ?>">
                                    <select name="new_status" class="status-select" onchange="this.form.submit()">
                                        <option value="">Zmień status</option>
                                        <option value="active">Aktywne</option>
                                        <option value="sold">Oznacz jako sprzedane</option>
                                        <option value="cancelled">Anuluj ogłoszenie</option>
                                    </select>
                                    <input type="hidden" name="change_status" value="1">
                                </form>
                            <?php endif; ?>
                            
                            <div class="product-actions">
                                <a href="product.php?id=<?php echo $product['ProductID']; ?>" class="action-btn btn-view">Podgląd</a>
                                
                                <?php if($product['Status'] === 'active'): ?>
                                    <a href="edit_product.php?id=<?php echo $product['ProductID']; ?>" class="action-btn btn-edit">Edytuj</a>
                                <?php endif; ?>
                                
                                <button class="action-btn btn-delete" onclick="openDeleteModal(<?php echo $product['ProductID']; ?>, '<?php echo addslashes($product['Title']); ?>')">Usuń</button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-products">
                    Nie masz jeszcze żadnych ogłoszeń. Kliknij "Dodaj nowe ogłoszenie", aby utworzyć pierwsze.
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal potwiedzenia usunięcia -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close-modal" onclick="closeDeleteModal()">&times;</span>
                <h2 class="modal-title">Potwierdzenie usunięcia</h2>
            </div>
            <div class="modal-body">
                <p>Czy na pewno chcesz usunąć ogłoszenie: <strong id="productTitle"></strong>?</p>
                <p>Tej operacji nie można cofnąć.</p>
            </div>
            <div class="modal-footer">
                <button class="action-btn btn-cancel" onclick="closeDeleteModal()">Anuluj</button>
                <form id="deleteForm" method="post" style="display: inline;">
                    <input type="hidden" name="product_id" id="deleteProductId">
                    <input type="hidden" name="delete_product" value="1">
                    <button type="submit" class="action-btn btn-confirm">Usuń</button>
                </form>
            </div>
        </div>
    </div>

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
        
        // Funkcja otwierająca modal usuwania
        function openDeleteModal(productId, productTitle) {
            document.getElementById('deleteProductId').value = productId;
            document.getElementById('productTitle').textContent = productTitle;
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        // Funkcja zamykająca modal usuwania
        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        // Zamykanie modala po kliknięciu poza nim
        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target === modal) {
                closeDeleteModal();
            }
        }
    </script>
</body>
</html>