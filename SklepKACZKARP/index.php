<?php
// Połączenie z bazą danych
$host = "localhost"; // adres serwera bazy danych
$username = "root"; // nazwa użytkownika bazy danych
$password = ""; // hasło do bazy danych
$database = "sklep_internetowy"; // nazwa bazy danych

// Tworzenie połączenia
$conn = new mysqli($host, $username, $password, $database);

// Sprawdzanie połączenia
if ($conn->connect_error) {
    die("Błąd połączenia z bazą danych: " . $conn->connect_error);
}

// Ustawienie kodowania znaków
$conn->set_charset("utf8");

// Inicjalizacja sesji
session_start();

// Funkcja do oczyszczania danych wejściowych
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Obsługa logowania
if (isset($_POST['login'])) {
    $email = cleanInput($_POST['email']);
    $password = cleanInput($_POST['password']);
    
    // Sprawdzenie czy pola nie są puste
    if (empty($email) || empty($password)) {
        $login_error = "Wszystkie pola są wymagane";
    } else {
        // Zapytanie do bazy danych
        $sql = "SELECT UserID, Username, Email, Password FROM users WHERE Email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Weryfikacja hasła
            if (password_verify($password, $user['Password'])) {
                // Zalogowano pomyślnie
                $_SESSION['user_id'] = $user['UserID'];
                $_SESSION['username'] = $user['Username'];
                
                // Aktualizacja daty ostatniego logowania
                $update_sql = "UPDATE users SET LastLoginDate = NOW() WHERE UserID = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("i", $user['UserID']);
                $update_stmt->execute();
                
                // Przekierowanie do strony głównej
                header("Location: dashboard.php");
                exit();
            } else {
                $login_error = "Nieprawidłowy email lub hasło";
            }
        } else {
            $login_error = "Nieprawidłowy email lub hasło";
        }
    }
}

// Obsługa rejestracji
if (isset($_POST['register'])) {
    $username = cleanInput($_POST['username']);
    $email = cleanInput($_POST['email']);
    $password = cleanInput($_POST['password']);
    $confirm_password = cleanInput($_POST['confirm_password']);
    $first_name = cleanInput($_POST['first_name']);
    $last_name = cleanInput($_POST['last_name']);
    
    // Sprawdzenie czy pola nie są puste
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $register_error = "Wszystkie wymagane pola muszą być wypełnione";
    } 
    // Sprawdzenie czy hasła są takie same
    elseif ($password != $confirm_password) {
        $register_error = "Hasła nie są identyczne";
    } 
    // Sprawdzenie poprawności adresu email
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $register_error = "Nieprawidłowy format adresu email";
    } else {
        // Sprawdzenie czy nazwa użytkownika lub email już istnieją
        $check_sql = "SELECT UserID FROM users WHERE Username = ? OR Email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $register_error = "Użytkownik o takiej nazwie lub adresie email już istnieje";
        } else {
            // Haszowanie hasła
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Dodanie użytkownika do bazy danych
            $insert_sql = "INSERT INTO users (Username, Email, Password, FirstName, LastName, RegistrationDate) VALUES (?, ?, ?, ?, ?, NOW())";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("sssss", $username, $email, $hashed_password, $first_name, $last_name);
            
            if ($insert_stmt->execute()) {
                // Rejestracja pomyślna
                $register_success = "Rejestracja zakończona pomyślnie. Możesz się teraz zalogować.";
            } else {
                $register_error = "Wystąpił błąd podczas rejestracji. Spróbuj ponownie później.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logowanie i Rejestracja - Sklep Internetowy</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <div class="forms-container">
            <div class="form-box">
                <div class="nav-tabs">
                    <button class="tab-btn active" data-form="login-form">Logowanie</button>
                    <button class="tab-btn" data-form="register-form">Rejestracja</button>
                </div>
                
                <!-- Formularz logowania -->
                <form id="login-form" class="form" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <h2>Zaloguj się</h2>
                    
                    <?php if(isset($login_error)): ?>
                        <div class="error-message"><?php echo $login_error; ?></div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="login-email">Email</label>
                        <input type="email" id="login-email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="login-password">Hasło</label>
                        <input type="password" id="login-password" name="password" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="login" class="btn">Zaloguj się</button>
                    </div>
                </form>
                
                <!-- Formularz rejestracji -->
                <form id="register-form" class="form hide" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <h2>Zarejestruj się</h2>
                    
                    <?php if(isset($register_error)): ?>
                        <div class="error-message"><?php echo $register_error; ?></div>
                    <?php endif; ?>
                    
                    <?php if(isset($register_success)): ?>
                        <div class="success-message"><?php echo $register_success; ?></div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="username">Nazwa użytkownika*</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="register-email">Email*</label>
                        <input type="email" id="register-email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="register-password">Hasło*</label>
                        <input type="password" id="register-password" name="password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm-password">Potwierdź hasło*</label>
                        <input type="password" id="confirm-password" name="confirm_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="first-name">Imię</label>
                        <input type="text" id="first-name" name="first_name">
                    </div>
                    
                    <div class="form-group">
                        <label for="last-name">Nazwisko</label>
                        <input type="text" id="last-name" name="last_name">
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="register" class="btn">Zarejestruj się</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="script.js"></script>
</body>
</html>