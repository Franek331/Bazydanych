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
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logowanie - Sklep Internetowy</title>
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
        
        .register-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .register-link a {
            color: #3498db;
            text-decoration: none;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="forms-container">
            <div class="form-box">
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
                    
                    <div class="register-link">
                        Nie masz konta? <a href="register.php">Zarejestruj się</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>