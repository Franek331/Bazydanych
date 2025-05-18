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
                
                // Opcjonalnie: automatyczne przekierowanie po kilku sekundach
                header("refresh:3;url=login.php");
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
    <title>Rejestracja - Sklep Internetowy</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 50px auto;
            padding: 0 15px;
        }
        
        .forms-container {
            display: flex;
            justify-content: center;
        }
        
        .form-box {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
            width: 100%;
            max-width: 500px;
        }
        
        .form {
            display: block;
        }
        
        .form h2 {
            margin-bottom: 20px;
            color: #333;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 20px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            width: 100%;
        }
        
        .btn:hover {
            background-color: #2980b9;
        }
        
        .error-message {
            padding: 10px;
            background-color: #ffebee;
            color: #c62828;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .success-message {
            padding: 10px;
            background-color: #e8f5e9;
            color: #2e7d32;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .login-link a {
            color: #3498db;
            text-decoration: none;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="forms-container">
            <div class="form-box">
                <!-- Formularz rejestracji -->
                <form id="register-form" class="form" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
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
                    
                    <div class="login-link">
                        Masz już konto? <a href="login.php">Zaloguj się</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>