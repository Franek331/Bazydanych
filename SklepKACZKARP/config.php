<?php
// Ten plik zawiera wspólne funkcje i połączenie z bazą danych dla obu plików
// Można go dołączyć na początku innych plików za pomocą require_once('config.php')

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
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Funkcja do oczyszczania danych wejściowych
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Funkcja sprawdzająca czy użytkownik jest zalogowany
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Funkcja przekierowująca niezalogowanych użytkowników
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: index.php");
        exit();
    }
}

// Funkcja do wylogowania użytkownika
function logoutUser() {
    // Usuń wszystkie zmienne sesji
    $_SESSION = array();
    
    // Jeśli używasz cookie do sesji, usuń cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Zniszcz sesję
    session_destroy();
}