<?php
session_start();

// Sprawdzenie czy użytkownik jest zalogowany
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
$success_message = '';
$error_message = '';

// Pobieranie danych użytkownika
$sql = "SELECT Username, Email, FirstName, LastName, PhoneNumber, Address FROM users WHERE UserID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Obsługa zmiany hasła
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Sprawdzenie, czy nowe hasła są identyczne
    if ($new_password !== $confirm_password) {
        $error_message = "Nowe hasła nie są identyczne!";
    } else {
        // Pobieranie aktualnego hasła z bazy
        $sql = "SELECT Password FROM users WHERE UserID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();
        $stmt->close();
        
        // Weryfikacja aktualnego hasła
        if (password_verify($current_password, $user_data['Password'])) {
            // Aktualizacja hasła
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE users SET Password = ? WHERE UserID = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($update_stmt->execute()) {
                $success_message = "Hasło zostało zmienione pomyślnie.";
            } else {
                $error_message = "Wystąpił błąd podczas zmiany hasła.";
            }
            $update_stmt->close();
        } else {
            $error_message = "Aktualne hasło jest nieprawidłowe.";
        }
    }
}

// Obsługa aktualizacji danych profilowych
if (isset($_POST['update_profile'])) {
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    
    $update_sql = "UPDATE users SET FirstName = ?, LastName = ?, PhoneNumber = ?, Address = ? WHERE UserID = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssssi", $firstname, $lastname, $phone, $address, $user_id);
    
    if ($update_stmt->execute()) {
        $success_message = "Dane profilowe zostały zaktualizowane.";
        // Aktualizacja zmiennych do wyświetlenia
        $user['FirstName'] = $firstname;
        $user['LastName'] = $lastname;
        $user['PhoneNumber'] = $phone;
        $user['Address'] = $address;
    } else {
        $error_message = "Wystąpił błąd podczas aktualizacji danych.";
    }
    $update_stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zarządzanie kontem - Serwis Ogłoszeniowy</title>
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
        
        .main-content {
            padding: 30px 0;
        }
        
        .section-title {
            font-size: 28px;
            margin-bottom: 20px;
            color: #333;
        }
        
        .account-management {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
        }
        
        .account-sidebar {
            flex: 1;
            min-width: 250px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        
        .sidebar-menu {
            list-style: none;
        }
        
        .sidebar-menu li {
            margin-bottom: 10px;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 10px;
            text-decoration: none;
            color: #333;
            border-radius: 4px;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: #f1f1f1;
        }
        
        .account-content {
            flex: 3;
            min-width: 300px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        
        .content-section {
            display: none;
        }
        
        .content-section.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .btn {
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .btn:hover {
            background-color: #2980b9;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 20px 0;
            margin-top: 40px;
        }
        
        .profile-info {
            margin-bottom: 30px;
        }
        
        .profile-info p {
            padding: 5px 0;
            display: flex;
        }
        
        .profile-info p span:first-child {
            font-weight: bold;
            width: 150px;
        }
        
        .password-requirements {
            margin-top: 5px;
            font-size: 12px;
            color: #666;
        }

        .forgot-password {
            margin-top: 10px;
            text-align: right;
        }

        .forgot-password a {
            color: #3498db;
            text-decoration: none;
            font-size: 14px;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .account-management {
                flex-direction: column;
            }
            
            .account-sidebar, .account-content {
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
                    <a href="dashboard.php" style="text-decoration: none; color: inherit;"><img src="logo-sklepu.png" alt="logo"></a>
                </div>
            </div>
        </div>
    </header>

    <main class="main-content container">
        <div class="section-title">Zarządzanie kontem</div>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="account-management">
            <div class="account-sidebar">
                <ul class="sidebar-menu">
                    <li><a href="#" class="menu-link active" data-target="profile">Profil</a></li>
                    <li><a href="#" class="menu-link" data-target="change-password">Zmiana hasła</a></li>
                    <li><a href="dashboard.php">Powrót do strony głównej</a></li>
                </ul>
            </div>
            
            <div class="account-content">
                <!-- Profil użytkownika -->
                <div class="content-section active" id="profile">
                    <h3>Dane profilowe</h3>
                    <div class="profile-info">
                        <p><span>Nazwa użytkownika:</span> <span><?php echo htmlspecialchars($user['Username']); ?></span></p>
                        <p><span>Email:</span> <span><?php echo htmlspecialchars($user['Email']); ?></span></p>
                        <p><span>Imię:</span> <span><?php echo htmlspecialchars($user['FirstName'] ?? ''); ?></span></p>
                        <p><span>Nazwisko:</span> <span><?php echo htmlspecialchars($user['LastName'] ?? ''); ?></span></p>
                        <p><span>Telefon:</span> <span><?php echo htmlspecialchars($user['PhoneNumber'] ?? ''); ?></span></p>
                        <p><span>Adres:</span> <span><?php echo htmlspecialchars($user['Address'] ?? ''); ?></span></p>
                    </div>
                    
                    <h3>Edytuj profil</h3>
                    <form method="post" action="">
                        <div class="form-group">
                            <label for="firstname">Imię</label>
                            <input type="text" id="firstname" name="firstname" class="form-control" value="<?php echo htmlspecialchars($user['FirstName'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="lastname">Nazwisko</label>
                            <input type="text" id="lastname" name="lastname" class="form-control" value="<?php echo htmlspecialchars($user['LastName'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Telefon</label>
                            <input type="text" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['PhoneNumber'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Adres</label>
                            <textarea id="address" name="address" class="form-control" rows="3"><?php echo htmlspecialchars($user['Address'] ?? ''); ?></textarea>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn">Aktualizuj dane</button>
                    </form>
                </div>
                
                <!-- Zmiana hasła -->
                <div class="content-section" id="change-password">
                    <h3>Zmiana hasła</h3>
                    <form method="post" action="">
                        <div class="form-group">
                            <label for="current_password">Aktualne hasło</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">Nowe hasło</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" required>
                            <div class="password-requirements">
                                Hasło powinno zawierać co najmniej 8 znaków, w tym wielkie i małe litery, cyfry oraz znaki specjalne.
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Potwierdź nowe hasło</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn">Zmień hasło</button>
                        
                        <div class="forgot-password">
                            <a href="forgot_password.php">Nie pamiętam hasła</a>
                        </div>
                    </form>
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
        document.addEventListener('DOMContentLoaded', function() {
            const menuLinks = document.querySelectorAll('.menu-link');
            
            menuLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Usuń klasę active ze wszystkich linków
                    menuLinks.forEach(item => item.classList.remove('active'));
                    
                    // Dodaj klasę active do klikniętego linku
                    this.classList.add('active');
                    
                    // Pobierz docelową sekcję
                    const targetId = this.getAttribute('data-target');
                    
                    // Ukryj wszystkie sekcje
                    document.querySelectorAll('.content-section').forEach(section => {
                        section.classList.remove('active');
                    });
                    
                    // Pokaż docelową sekcję
                    document.getElementById(targetId).classList.add('active');
                });
            });
        });
    </script>
</body>
</html>