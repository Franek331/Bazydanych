<?php
// Inicjalizacja sesji
session_start();

// Sprawdzenie czy użytkownik jest zalogowany
$is_logged_in = isset($_SESSION['user_id']);
$current_user_id = $is_logged_in ? $_SESSION['user_id'] : null;

// Połączenie z bazą danych
$conn = new mysqli("localhost", "root", "", "sklep_internetowy");

// Sprawdzenie połączenia
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Obsługa dodawania produktu do koszyka
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id']) && $is_logged_in) {
    $product_id = (int)$_POST['product_id'];
    
    // Sprawdzamy, czy produkt istnieje i jest aktywny
    $check_sql = "SELECT * FROM products WHERE ProductID = ? AND Status = 'active'";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product_result = $stmt->get_result();
    
    if ($product_result->num_rows > 0) {
        $product = $product_result->fetch_assoc();
        
        // Sprawdzamy, czy produkt już jest w koszyku
        $check_cart_sql = "SELECT * FROM cart WHERE UserID = ? AND ProductID = ?";
        $stmt = $conn->prepare($check_cart_sql);
        $stmt->bind_param("ii", $current_user_id, $product_id);
        $stmt->execute();
        $cart_result = $stmt->get_result();
        
        if ($cart_result->num_rows > 0) {
            // Produkt już istnieje w koszyku - aktualizujemy ilość
            $cart_item = $cart_result->fetch_assoc();
            $new_quantity = $cart_item['Quantity'] + 1;
            
            // Sprawdzamy, czy mamy wystarczającą ilość produktu na stanie
            if ($new_quantity <= $product['Quantity']) {
                $update_sql = "UPDATE cart SET Quantity = ? WHERE CartID = ?";
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param("ii", $new_quantity, $cart_item['CartID']);
                $stmt->execute();
                $success = true;
                $message = "Zwiększono ilość produktu w koszyku!";
            } else {
                $success = false;
                $message = "Nie można dodać więcej tego produktu. Osiągnięto maksymalną dostępną ilość.";
            }
        } else {
            // Dodajemy nowy produkt do koszyka
            $insert_sql = "INSERT INTO cart (UserID, ProductID, Quantity) VALUES (?, ?, 1)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("ii", $current_user_id, $product_id);
            $stmt->execute();
            $success = true;
            $message = "Produkt został dodany do koszyka!";
        }
        
        // Pobieramy aktualną liczbę przedmiotów w koszyku
        $cart_count_sql = "SELECT SUM(Quantity) as total_items FROM cart WHERE UserID = ?";
        $stmt = $conn->prepare($cart_count_sql);
        $stmt->bind_param("i", $current_user_id);
        $stmt->execute();
        $cart_count_result = $stmt->get_result();
        $cart_count = 0;
        if ($row = $cart_count_result->fetch_assoc()) {
            $cart_count = $row['total_items'] ? $row['total_items'] : 0;
        }
        
        // Zwracamy odpowiedź JSON
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'cart_count' => $cart_count
        ]);
    } else {
        // Produkt nie istnieje lub nie jest aktywny
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Produkt nie istnieje lub nie jest już dostępny.'
        ]);
    }
    
    $stmt->close();
    $conn->close();
    exit();
} else {
    // Błędne żądanie
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Nieprawidłowe żądanie lub użytkownik nie jest zalogowany.'
    ]);
    $conn->close();
    exit();
}
?>