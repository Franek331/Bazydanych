<?php
session_start();

// Połączenie z bazą danych
$conn = new mysqli("localhost", "root", "", "sklep_internetowy");

// Sprawdzenie połączenia
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error_message = '';
$success_message = '';

// Jeśli formularz został wysłany
if (isset($_POST['forgot_password'])) {
    $email = $_POST['email'];
    
    // Sprawdzenie czy email istnieje w bazie
    $sql = "SELECT UserID, Username FROM users WHERE Email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Generowanie unikalnego tokenu
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Usunięcie poprzednich tokenów dla tego użytkownika
        $delete_sql = "DELETE FROM password_resets WHERE UserID = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $user['UserID']);
        $delete_stmt->execute();
        $delete_stmt->close();
        
        // Zapisanie tokenu do bazy
        $insert_sql = "INSERT INTO password_resets (UserID, ResetToken, ExpiresAt) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iss", $user['UserID'], $token, $expires);
        
        if ($insert_stmt->execute()) {
            // Link do resetowania hasła
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;
            
            // Email z linkiem do resetowania hasła (w realnym projekcie wysłano by prawdziwy email)
            $to = $email;
            $subject = "Reset hasła - Serwis Ogłoszeniowy";
            $message = "Witaj " . htmlspecialchars($user['Username']) . ",\n\n";
            $message .= "Otrzymujesz tę wiadomość, ponieważ poproszono o zresetowanie hasła dla Twojego konta.\n\n";
            $message .= "Kliknij poniższy link, aby zresetować hasło:\n\n";
            $message .= $reset_link . "\n\n";
            $message .= "Link wygaśnie za 1 godzinę.\n\n";
            $message .= "Jeśli to nie Ty prosiłeś o zresetowanie hasła, zignoruj tę wiadomość.\n\n";
            $message .= "Pozdrawiamy,\nZespół Serwisu Ogłoszeniowego";
            $headers = "From: noreply@example.com";
            
            // W środowisku produkcyjnym wysłalibyśmy prawdziwy email
            // mail($to, $subject, $message, $headers);
            
            // Dla celów demonstracyjnych wyświetlamy link na stronie
            $success_message = "Link do resetowania hasła został wysłany na adres email $email. Ze względów demonstracyjnych wyświetlamy go tutaj: <br><br> <a href='$reset_link'>$reset_link</a>";
        } else {
            $error_message = "Wystąpił błąd. Spróbuj ponownie później.";
        }
        $insert_stmt->close();
    } else {
        // Dla bezpieczeństwa nie ujawniamy czy email istnieje w bazie
        $success_message = "Jeśli podany adres email istnieje w naszej bazie, instrukcje resetowania hasła zostały na niego wysłane.";
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nie pamiętam hasła - Serwis Ogłoszeniowy</title>
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
        
        .forgot-password-form {
            max-width: 500px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        
        .section-title {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
            text-align: center;
        }
        
        .form-description {
            margin-bottom: 20px;
            color: #666;
            text-align: center;
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
            width: 100%;
            padding: 12px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
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
        
        .back-link {
            display: block;
            margin-top: 20px;
            text-align: center;
            color: #3498db;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
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
                    <a href="index.php" style="text-decoration: none; color: inherit;">Tutaj logo</a>
                </div>
            </div>
        </div>
    </header>

    <main class="main-content container">
        <div class="forgot-password-form">
            <div class="section-title">Nie pamiętam hasła</div>
            <p class="form-description">Podaj swój adres email, a wyślemy Ci link do zresetowania hasła.</p>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <form method="post" action="">
                <div class="form-group">
                    <label for="email">Adres email</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                
                <button type="submit" name="forgot_password" class="btn">Wyślij link do resetowania</button>
            </form>
            
            <a href="login.php" class="back-link">Powrót do logowania</a>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Serwis Ogłoszeniowy. Wszelkie prawa zastrzeżone.</p>
        </div>
    </footer>
</body>
</html>