<?php
// Inicjalizacja sesji
session_start();

// Zwracane dane jako JSON
header('Content-Type: application/json');

// Sprawdzenie czy użytkownik jest zalogowany
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Musisz być zalogowany, aby dodać produkt do koszyka.']);
    exit();
}

// Sprawdzenie czy otrzymano identyfikator produktu
if (!isset($_POST['product_id']) || !is_numeric($_POST['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'Nieprawidłowy identyfikator produktu.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$product_id = (int)$_POST['product_id'];
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

// Minimalna ilość to 1
if ($quantity < 1) {
    $quantity = 1;
}

// Połączenie z bazą danych
$conn = new mysqli("localhost", "root", "", "sklep_internetowy");

// Sprawdzenie połączenia
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Błąd połączenia z bazą danych.']);
    exit();
}

// Sprawdzenie czy produkt istnieje i jest aktywny
$check_sql = "SELECT ProductID, SellerID FROM products WHERE ProductID = ? AND Status = 'active'";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Produkt nie istnieje lub nie jest aktywny.']);
    $stmt->close();
    $conn->close();
    exit();
}

// Sprawdzenie czy użytkownik nie dodaje własnego produktu do koszyka
$product = $result->fetch_assoc();
if ($product['SellerID'] == $user_id) {
    echo json_encode(['success' => false, 'message' => 'Nie możesz dodać własnego produktu do koszyka.']);
    $stmt->close();
    $conn->close();
    exit();
}

// Sprawdzenie czy produkt już jest w koszyku
$check_cart_sql = "SELECT CartID, Quantity FROM cart WHERE UserID = ? AND ProductID = ?";
$stmt = $conn->prepare($check_cart_sql);
$stmt->bind_param("ii", $user_id, $product_id);
$stmt->execute();
$cart_result = $stmt->get_result();

if ($cart_result->num_rows > 0) {
    // Produkt już istnieje w koszyku - aktualizacja ilości
    $cart_item = $cart_result->fetch_assoc();
    $new_quantity = $cart_item['Quantity'] + $quantity;
    
    $update_sql = "UPDATE cart SET Quantity = ?, DateAdded = NOW() WHERE CartID = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ii", $new_quantity, $cart_item['CartID']);
    
    if ($stmt->execute()) {
        // Pobieranie liczby przedmiotów w koszyku
        $count_sql = "SELECT SUM(Quantity) as total_items FROM cart WHERE UserID = ?";
        $stmt = $conn->prepare($count_sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $count_result = $stmt->get_result();
        $row = $count_result->fetch_assoc();
        $cart_count = $row['total_items'];
        
        echo json_encode(['success' => true, 'message' => 'Zaktualizowano ilość produktu w koszyku.', 'cart_count' => $cart_count]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Błąd podczas aktualizacji koszyka.']);
    }
} else {
    // Dodawanie nowego produktu do koszyka
    $insert_sql = "INSERT INTO cart (UserID, ProductID, Quantity, DateAdded) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("iii", $user_id, $product_id, $quantity);
    
    if ($stmt->execute()) {
        // Pobieranie liczby przedmiotów w koszyku
        $count_sql = "SELECT SUM(Quantity) as total_items FROM cart WHERE UserID = ?";
        $stmt = $conn->prepare($count_sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $count_result = $stmt->get_result();
        $row = $count_result->fetch_assoc();
        $cart_count = $row['total_items'];
        
        echo json_encode(['success' => true, 'message' => 'Produkt został dodany do koszyka.', 'cart_count' => $cart_count]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Błąd podczas dodawania produktu do koszyka.']);
    }
}

$stmt->close();
$conn->close();
?>