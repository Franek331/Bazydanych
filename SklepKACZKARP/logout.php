<?php
// Dołącz plik konfiguracyjny
require_once('config.php');

// Sprawdź czy użytkownik jest zalogowany
if (isLoggedIn()) {
    // Przekieruj zalogowanego użytkownika do panelu
    header("Location: dashboard.php");
    exit();
}

// Obsługa wylogowania
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    logoutUser();
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wylogowanie - Sklep Internetowy</title>
</head>
<body>
    <p>Wylogowywanie...</p>
    <script>
        // Przekierowanie po krótkiej chwili
        setTimeout(function() {
            window.location.href = "index.php";
        }, 500);
    </script>
</body>
</html>