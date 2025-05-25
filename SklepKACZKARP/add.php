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

// Zdefiniowanie zmiennych i inicjalizacja pustymi wartościami
$title = $description = $price = $quantity = $condition = $category = "";
$title_err = $description_err = $price_err = $quantity_err = $condition_err = $category_err = $image_err = "";
$success_message = "";

// Przetwarzanie danych formularza po przesłaniu
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Sprawdzenie i walidacja tytułu
    if (empty(trim($_POST["title"]))) {
        $title_err = "Proszę podać tytuł ogłoszenia.";
    } elseif (strlen(trim($_POST["title"])) > 100) {
        $title_err = "Tytuł nie może przekraczać 100 znaków.";
    } else {
        $title = trim($_POST["title"]);
    }
    
    // Sprawdzenie i walidacja opisu
    if (empty(trim($_POST["description"]))) {
        $description_err = "Proszę podać opis produktu.";
    } else {
        $description = trim($_POST["description"]);
    }
    
    // Sprawdzenie i walidacja ceny
    if (empty(trim($_POST["price"]))) {
        $price_err = "Proszę podać cenę.";
    } elseif (!is_numeric(str_replace(',', '.', $_POST["price"])) || (float)str_replace(',', '.', $_POST["price"]) <= 0) {
        $price_err = "Proszę podać prawidłową cenę.";
    } else {
        $price = (float)str_replace(',', '.', $_POST["price"]);
    }
    
    // Sprawdzenie i walidacja ilości
    if (empty(trim($_POST["quantity"]))) {
        $quantity_err = "Proszę podać ilość.";
    } elseif (!is_numeric($_POST["quantity"]) || (int)$_POST["quantity"] <= 0) {
        $quantity_err = "Proszę podać prawidłową ilość.";
    } else {
        $quantity = (int)$_POST["quantity"];
    }
    
    // Sprawdzenie i walidacja stanu produktu
    if (empty($_POST["condition"])) {
        $condition_err = "Proszę wybrać stan produktu.";
    } elseif (!in_array($_POST["condition"], ['new', 'used', 'refurbished'])) {
        $condition_err = "Nieprawidłowy stan produktu.";
    } else {
        $condition = $_POST["condition"];
    }
    
    // Sprawdzenie i walidacja kategorii
    if (empty($_POST["category"])) {
        $category_err = "Proszę wybrać kategorię.";
    } else {
        // Sprawdzanie czy kategoria istnieje w bazie danych
        $stmt = $conn->prepare("SELECT CategoryID FROM categories WHERE CategoryID = ?");
        $stmt->bind_param("i", $_POST["category"]);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $category = $_POST["category"];
        } else {
            $category_err = "Nieprawidłowa kategoria.";
        }
        $stmt->close();
    }
    
    // Sprawdzenie czy zostało przesłane zdjęcie główne
    if (!isset($_FILES['primary_image']) || $_FILES['primary_image']['error'] == UPLOAD_ERR_NO_FILE) {
        $image_err = "Proszę dodać przynajmniej jedno zdjęcie.";
    } elseif ($_FILES['primary_image']['error'] != UPLOAD_ERR_OK) {
        $image_err = "Wystąpił błąd podczas przesyłania zdjęcia: " . $_FILES['primary_image']['error'];
    } else {
        // Sprawdzenie typu pliku i jego rozmiaru
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($_FILES['primary_image']['type'], $allowed_types)) {
            $image_err = "Dozwolone są tylko pliki JPG, PNG i GIF.";
        } elseif ($_FILES['primary_image']['size'] > 5000000) { // 5MB limit
            $image_err = "Zdjęcie nie może przekraczać 5MB.";
        }
    }
    
    // Sprawdź czy nie ma błędów walidacji przed dodaniem do bazy danych
    if (empty($title_err) && empty($description_err) && empty($price_err) && empty($quantity_err) && 
        empty($condition_err) && empty($category_err) && empty($image_err)) {
        
        // Rozpocznij transakcję
        $conn->begin_transaction();
        
        try {
            // Przygotuj zapytanie SQL do dodania produktu
            $stmt = $conn->prepare("INSERT INTO products (SellerID, CategoryID, Title, Description, Price, Quantity, `Condition`, PostedDate, Status) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'active')");
            $stmt->bind_param("iissdis", $_SESSION['user_id'], $category, $title, $description, $price, $quantity, $condition);
            
            // Wykonaj zapytanie
            if ($stmt->execute()) {
                $product_id = $conn->insert_id;
                
                // Zapisz zdjęcie główne
                $upload_dir = "uploads/products/";
                
                // Upewnij się, że katalog istnieje
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Generowanie unikalnej nazwy pliku
                $filename = uniqid() . '_' . basename($_FILES['primary_image']['name']);
                $target_file = $upload_dir . $filename;
                
                // Przenieś plik do docelowego katalogu
                if (move_uploaded_file($_FILES['primary_image']['tmp_name'], $target_file)) {
                    // Zapisz informację o zdjęciu w bazie danych
                    $stmt = $conn->prepare("INSERT INTO productimages (ProductID, ImageURL, IsPrimary, UploadDate) VALUES (?, ?, 1, NOW())");
                    $stmt->bind_param("is", $product_id, $target_file);
                    $stmt->execute();
                    
                    // Sprawdź czy są dodatkowe zdjęcia
                    if (isset($_FILES['additional_images'])) {
                        foreach ($_FILES['additional_images']['tmp_name'] as $key => $tmp_name) {
                            if ($_FILES['additional_images']['error'][$key] == UPLOAD_ERR_OK) {
                                // Sprawdź typ i rozmiar dodatkowego zdjęcia
                                if (in_array($_FILES['additional_images']['type'][$key], $allowed_types) && 
                                    $_FILES['additional_images']['size'][$key] <= 5000000) {
                                    
                                    $add_filename = uniqid() . '_' . basename($_FILES['additional_images']['name'][$key]);
                                    $add_target_file = $upload_dir . $add_filename;
                                    
                                    if (move_uploaded_file($tmp_name, $add_target_file)) {
                                        $stmt = $conn->prepare("INSERT INTO productimages (ProductID, ImageURL, IsPrimary, UploadDate) VALUES (?, ?, 0, NOW())");
                                        $stmt->bind_param("is", $product_id, $add_target_file);
                                        $stmt->execute();
                                    }
                                }
                            }
                        }
                    }
                    
                    // Zatwierdź transakcję
                    $conn->commit();
                    
                    // Komunikat o sukcesie
                    $success_message = "Ogłoszenie zostało dodane pomyślnie!";
                    
                    // Reset formularza
                    $title = $description = $price = $quantity = $condition = $category = "";
                } else {
                    throw new Exception("Wystąpił problem z przesłaniem pliku.");
                }
            } else {
                throw new Exception("Wystąpił błąd. Proszę spróbować ponownie później.");
            }
        } catch (Exception $e) {
            // Wycofaj transakcję w przypadku błędu
            $conn->rollback();
            $image_err = $e->getMessage();
        }
    }
}

// Pobierz wszystkie kategorie dla listy rozwijanej
$sql = "SELECT CategoryID, Name FROM categories ORDER BY Name";
$categories_result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dodaj ogłoszenie - Serwis Ogłoszeniowy</title>
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
        
        /* Form Styling */
        .form-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        a{
            text-decoration: none;
            color: white;
        }
        .form-label {
            display: block;
            margin-bottom: 8px;
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
        
        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.5);
        }
        
        .text-danger {
            color: #e74c3c;
            font-size: 14px;
            margin-top: 5px;
            display: block;
        }
        
        .text-success {
            color: #2ecc71;
            font-size: 16px;
            margin-bottom: 20px;
            display: block;
            text-align: center;
            padding: 10px;
            background-color: #d5f5e3;
            border-radius: 4px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-align: center;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #2980b9;
        }
        
        .btn-block {
            display: block;
            width: 100%;
        }
        
        .image-preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        
        .image-preview {
            width: 150px;
            height: 150px;
            border: 1px solid #ddd;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }
        
        .image-preview img {
            max-width: 100%;
            max-height: 100%;
        }
        
        .preview-placeholder {
            color: #aaa;
            text-align: center;
            font-size: 14px;
        }
        
        .image-upload-btn {
            background-color: #f8f9fa;
            border: 1px dashed #ccc;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .image-upload-btn span {
            font-size: 30px;
            color: #aaa;
        }
        
        .file-input {
            display: none;
        }
        
        .file-label {
            cursor: pointer;
            display: block;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .radio-group {
            display: flex;
            gap: 20px;
        }
        
        .radio-container {
            display: flex;
            align-items: center;
        }
        
        .radio-container input[type="radio"] {
            margin-right: 8px;
        }
        
        /* Footer styling */
        .footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 20px 0;
            margin-top: 40px;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .form-container {
                padding: 20px;
            }
            
            .radio-group {
                flex-direction: column;
                gap: 10px;
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
    </style>
</head>

<body>
    <header>
        <div class="container">
            <div class="header-top">
                <div class="logo-area">
                    <a href="dashboard.php" style="text-decoration: none; color: inherit;">Serwis Ogłoszeniowy</a>
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
                            <a href="profile.php" style="text-decoration: none; color: inherit;">Zarządzaj kontem</a>
                        </div>
                        <div class="menu-item">
                            <a href="myproducts.php" style="text-decoration: none; color: inherit;">Moje oferty</a>
                        </div>
                        <div class="menu-item">
                            <a href="myorders.php" style="text-decoration: none; color: inherit;">Moje kupno</a>
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
        <div class="section-title">Dodaj nowe ogłoszenie</div>
        
        <div class="form-container">
            <?php if(!empty($success_message)): ?>
                <div class="text-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label" for="title">Tytuł ogłoszenia*</label>
                    <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($title); ?>">
                    <?php if(!empty($title_err)): ?>
                        <span class="text-danger"><?php echo $title_err; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="category">Kategoria*</label>
                    <select id="category" name="category" class="form-control">
                        <option value="">Wybierz kategorię</option>
                        <?php
                        if ($categories_result->num_rows > 0) {
                            while($row = $categories_result->fetch_assoc()) {
                                $selected = ($category == $row["CategoryID"]) ? "selected" : "";
                                echo '<option value="' . $row["CategoryID"] . '" ' . $selected . '>' . htmlspecialchars($row["Name"]) . '</option>';
                            }
                        }
                        ?>
                    </select>
                    <?php if(!empty($category_err)): ?>
                        <span class="text-danger"><?php echo $category_err; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="description">Opis produktu*</label>
                    <textarea id="description" name="description" class="form-control"><?php echo htmlspecialchars($description); ?></textarea>
                    <?php if(!empty($description_err)): ?>
                        <span class="text-danger"><?php echo $description_err; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="price">Cena (PLN)*</label>
                    <input type="text" id="price" name="price" class="form-control" value="<?php echo htmlspecialchars($price); ?>">
                    <?php if(!empty($price_err)): ?>
                        <span class="text-danger"><?php echo $price_err; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="quantity">Ilość*</label>
                    <input type="number" id="quantity" name="quantity" class="form-control" value="<?php echo htmlspecialchars($quantity); ?>" min="1">
                    <?php if(!empty($quantity_err)): ?>
                        <span class="text-danger"><?php echo $quantity_err; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Stan produktu*</label>
                    <div class="radio-group">
                        <div class="radio-container">
                            <input type="radio" id="condition-new" name="condition" value="new" <?php echo ($condition == "new") ? "checked" : ""; ?>>
                            <label for="condition-new">Nowy</label>
                        </div>
                        <div class="radio-container">
                            <input type="radio" id="condition-used" name="condition" value="used" <?php echo ($condition == "used") ? "checked" : ""; ?>>
                            <label for="condition-used">Używany</label>
                        </div>
                        <div class="radio-container">
                            <input type="radio" id="condition-refurbished" name="condition" value="refurbished" <?php echo ($condition == "refurbished") ? "checked" : ""; ?>>
                            <label for="condition-refurbished">Odnowiony</label>
                        </div>
                    </div>
                    <?php if(!empty($condition_err)): ?>
                        <span class="text-danger"><?php echo $condition_err; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Zdjęcie główne*</label>
                    <div class="image-preview-container">
                        <div class="image-preview" id="primary-image-preview">
                            <div class="preview-placeholder">
                                Wybierz zdjęcie
                            </div>
                        </div>
                        <div class="image-preview image-upload-btn">
                            <label for="primary-image" class="file-label">
                                <span>+</span>
                                <div>Dodaj zdjęcie</div>
                            </label>
                            <input type="file" id="primary-image" name="primary_image" class="file-input" accept="image/*">
                        </div>
                    </div>
                    <?php if(!empty($image_err)): ?>
                        <span class="text-danger"><?php echo $image_err; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Dodatkowe zdjęcia (opcjonalnie)</label>
                    <div class="image-preview-container" id="additional-images-container">
                        <div class="image-preview image-upload-btn">
                            <label for="additional-images" class="file-label">
                                <span>+</span>
                                <div>Dodaj zdjęcia</div>
                            </label>
                            <input type="file" id="additional-images" name="additional_images[]" class="file-input" accept="image/*" multiple>
                        </div>
                    </div>
                    <small>Możesz wybrać do 5 dodatkowych zdjęć</small>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-block">Dodaj ogłoszenie</button><br>
                    <button type="button" class="btn btn-block"><a href="add-product.php">Anuluj</a></button>
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
        
        // Podgląd zdjęć
        document.getElementById('primary-image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('primary-image-preview');
                    preview.innerHTML = `<img src="${e.target.result}" alt="Podgląd zdjęcia">`;
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Podgląd dodatkowych zdjęć
        document.getElementById('additional-images').addEventListener('change', function(e) {
            const files = e.target.files;
            const container = document.getElementById('additional-images-container');
            
            // Usuń wszystkie istniejące podglądy (oprócz przycisku dodawania)
            const previews = container.querySelectorAll('.image-preview:not(.image-upload-btn)');
            previews.forEach(preview => container.removeChild(preview));
            
            // Dodaj nowe podglądy (maksymalnie 5)
            const maxFiles = 5;
            for (let i = 0; i < Math.min(files.length, maxFiles); i++) {
                const file = files[i];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const preview = document.createElement('div');
                    preview.className = 'image-preview';
                    preview.innerHTML = `<img src="${e.target.result}" alt="Podgląd dodatkowego zdjęcia">`;
                    
                    // Wstaw przed przyciskiem dodawania
                    container.insertBefore(preview, container.querySelector('.image-upload-btn'));
                };
                
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
