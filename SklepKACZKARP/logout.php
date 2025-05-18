<?php
// Dołącz plik konfiguracyjny
require_once('config.php');

// Obsługa wylogowania
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    // Wywołaj funkcję wylogowania
    logoutUser();
    
    // Dodaj komunikat o wylogowaniu (opcjonalnie)
    $_SESSION['logout_message'] = "Zostałeś pomyślnie wylogowany.";
    
    // Przekieruj do strony głównej
    header("Location: login.php");
    exit();
}

// Jeśli ktoś wszedł bezpośrednio na tą stronę (bez parametru action=logout)
// a jest zalogowany, to również go wyloguj
if (isLoggedIn()) {
    logoutUser();
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
            window.location.href = "login.php";
        }, 500);
    </script>
</body>
</html>