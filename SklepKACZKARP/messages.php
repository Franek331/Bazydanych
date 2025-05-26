<?php
session_start();

// Check if user is logged in, redirect to login page if not
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "sklep_internetowy");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Send a new message
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_message'])) {
    $receiver_email = trim($_POST['receiver_email']);
    $content = trim($_POST['message_content']);
    $product_id = !empty($_POST['product_id']) ? (int)$_POST['product_id'] : null;
    
    // Check if all required fields are filled
    if (!empty($receiver_email) && !empty($content)) {
        // Get receiver ID from email
        $stmt = $conn->prepare("SELECT UserID FROM users WHERE Email = ?");
        $stmt->bind_param("s", $receiver_email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $receiver_id = $row['UserID'];
            
            // Don't allow sending messages to self
            if ($receiver_id == $user_id) {
                $message_error = "Nie możesz wysłać wiadomości do samego siebie.";
            } else {
                // Insert message
                $stmt = $conn->prepare("INSERT INTO messages (SenderID, ReceiverID, ProductID, MessageContent) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiis", $user_id, $receiver_id, $product_id, $content);
                
                if ($stmt->execute()) {
                    $message_success = "Wiadomość została wysłana pomyślnie.";
                    
                    // If in a conversation, redirect back to it
                    if (isset($_POST['conversation_partner_id'])) {
                        header("Location: messages.php?conversation=" . (int)$_POST['conversation_partner_id']);
                        exit();
                    }
                } else {
                    $message_error = "Błąd podczas wysyłania wiadomości: " . $conn->error;
                }
            }
        } else {
            $message_error = "Nie znaleziono użytkownika z podanym adresem email.";
        }
    } else {
        $message_error = "Proszę wypełnić wszystkie wymagane pola.";
    }
}

// Handle marking message as read
if (isset($_GET['mark_read']) && isset($_GET['message_id'])) {
    $message_id = (int)$_GET['message_id'];
    
    // Only mark as read if user is the receiver
    $stmt = $conn->prepare("UPDATE messages SET IsRead = TRUE WHERE MessageID = ? AND ReceiverID = ?");
    $stmt->bind_param("ii", $message_id, $user_id);
    $stmt->execute();
    
    // Redirect to remove the query parameters
    header("Location: messages.php" . (isset($_GET['conversation']) ? "?conversation=".$_GET['conversation'] : ""));
    exit();
}

// Get unread message count
$stmt = $conn->prepare("SELECT COUNT(*) as unread_count FROM messages WHERE ReceiverID = ? AND IsRead = FALSE");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$unread_result = $stmt->get_result();
$unread_count = $unread_result->fetch_assoc()['unread_count'];

// Default view is inbox
$view = isset($_GET['view']) ? $_GET['view'] : 'inbox';

$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? $_SESSION['username'] : '';
$cart_count = 0;
    if ($is_logged_in) {
        $cart_sql = "SELECT SUM(Quantity) as total_items FROM cart WHERE UserID = ?";
        $stmt = $conn->prepare($cart_sql);
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $cart_result = $stmt->get_result();
        if ($row = $cart_result->fetch_assoc()) {
            $cart_count = $row['total_items'] ? $row['total_items'] : 0;
        }
        $stmt->close();
    }
// Display a specific conversation if requested
$conversation_user = null;
if (isset($_GET['conversation'])) {
    $conversation_partner_id = (int)$_GET['conversation'];
    
    // Get conversation partner info
    $stmt = $conn->prepare("SELECT UserID, Username, Email FROM users WHERE UserID = ?");
    $stmt->bind_param("i", $conversation_partner_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $conversation_user = $result->fetch_assoc();
    }
    
    // Get conversation messages
    $stmt = $conn->prepare("
        SELECT m.*, u_sender.Username as SenderName, u_receiver.Username as ReceiverName, p.Title as ProductTitle
        FROM messages m
        LEFT JOIN users u_sender ON m.SenderID = u_sender.UserID
        LEFT JOIN users u_receiver ON m.ReceiverID = u_receiver.UserID
        LEFT JOIN products p ON m.ProductID = p.ProductID
        WHERE (m.SenderID = ? AND m.ReceiverID = ?) OR (m.SenderID = ? AND m.ReceiverID = ?)
        ORDER BY m.SentDate ASC
    ");
    $stmt->bind_param("iiii", $user_id, $conversation_partner_id, $conversation_partner_id, $user_id);
    $stmt->execute();
    $messages_result = $stmt->get_result();
    
    // Mark all messages from this user as read
    $stmt = $conn->prepare("UPDATE messages SET IsRead = TRUE WHERE SenderID = ? AND ReceiverID = ?");
    $stmt->bind_param("ii", $conversation_partner_id, $user_id);
    $stmt->execute();
} else {
    // Get list of conversations (latest message from each conversation partner)
    if ($view == 'inbox') {
        $sql = "
            SELECT m.*, 
                u.Username, u.Email, 
                p.Title as ProductTitle,
                (SELECT COUNT(*) FROM messages WHERE SenderID = m.SenderID AND ReceiverID = ? AND IsRead = FALSE) as UnreadCount
            FROM messages m
            JOIN users u ON m.SenderID = u.UserID
            LEFT JOIN products p ON m.ProductID = p.ProductID
            JOIN (
                SELECT MAX(MessageID) as MaxID
                FROM messages 
                WHERE ReceiverID = ?
                GROUP BY SenderID
            ) as latest ON m.MessageID = latest.MaxID
            WHERE m.ReceiverID = ?
            ORDER BY m.SentDate DESC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $user_id, $user_id, $user_id);
    } else {
        // Sent messages view
        $sql = "
            SELECT m.*, 
                u.Username, u.Email,
                p.Title as ProductTitle
            FROM messages m
            JOIN users u ON m.ReceiverID = u.UserID
            LEFT JOIN products p ON m.ProductID = p.ProductID
            JOIN (
                SELECT MAX(MessageID) as MaxID
                FROM messages 
                WHERE SenderID = ?
                GROUP BY ReceiverID
            ) as latest ON m.MessageID = latest.MaxID
            WHERE m.SenderID = ?
            ORDER BY m.SentDate DESC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $user_id);
    }
    
    $stmt->execute();
    $conversations_result = $stmt->get_result();
}

// Search users by email for new message
$searched_users = [];
if (isset($_GET['search_email']) && !empty($_GET['search_email'])) {
    $search_email = '%' . $_GET['search_email'] . '%';
    $stmt = $conn->prepare("SELECT UserID, Username, Email FROM users WHERE Email LIKE ? AND UserID != ? LIMIT 10");
    $stmt->bind_param("si", $search_email, $user_id);
    $stmt->execute();
    $searched_users = $stmt->get_result();
}

// Get user's products for product selection dropdown
$stmt = $conn->prepare("SELECT ProductID, Title FROM products WHERE SellerID = ? AND Status = 'active'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_products = $stmt->get_result();

// Close database connection
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wiadomości - Serwis Ogłoszeniowy</title>
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
        
        /* Header */
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
        
        .search-area {
            flex-grow: 1;
            margin: 0 20px;
        }
        
        .search-form {
            display: flex;
            width: 100%;
        }
        
        .search-input {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px 0 0 4px;
            font-size: 16px;
        }
        
        .search-button {
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
        }
        
        .user-area {
            position: relative;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-icon {
            cursor: pointer;
            font-size: 16px;
            padding: 10px;
            border-radius: 50%;
            background-color: #f1f1f1;
        }
        
        .cart-icon {
            position: relative;
            cursor: pointer;
            font-size: 20px;
        }
        
        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 12px;
            font-weight: bold;
        }
        
        .user-menu {
            position: absolute;
            right: 0;
            top: 100%;
            width: 250px;
            background-color: #fff;
            border: 1px solid #eaeaea;
            border-radius: 4px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            z-index: 100;
            display: none;
        }
        
        .user-menu.active {
            display: block;
        }
        
        .menu-item {
            padding: 15px;
            border-bottom: 1px solid #eaeaea;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .menu-item:last-child {
            border-bottom: none;
        }
        
        .menu-item:hover {
            background-color: #f8f9fa;
        }
        
        .menu-item span {
            font-size: 14px;
            color: #666;
        }
        
        /* Main content */
        .main-content {
            padding: 30px 0;
        }
        
        .section-title {
            font-size: 28px;
            margin-bottom: 20px;
            color: #333;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        /* Message system styles - MESSENGER STYLE */
        .messenger-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            display: flex;
            height: 600px;
        }
        
        .contacts-sidebar {
            width: 30%;
            border-right: 1px solid #eaeaea;
            overflow-y: auto;
            background-color: #f9f9f9;
            display: flex;
            flex-direction: column;
        }
        
        .chat-container {
            width: 70%;
            display: flex;
            flex-direction: column;
        }
        
        .tabs {
            display: flex;
            border-bottom: 1px solid #eaeaea;
            flex-shrink: 0;
        }
        
        .tab {
            padding: 15px;
            cursor: pointer;
            background-color: #f1f1f1;
            border: none;
            outline: none;
            flex-grow: 1;
            text-align: center;
            font-weight: bold;
            position: relative;
        }
        
        .tab.active {
            background-color: #fff;
            border-bottom: 3px solid #3498db;
        }
        
        .tab-badge {
            position: absolute;
            top: 5px;
            right: 5px;
            background-color: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 12px;
        }
        
        .conversation-list {
            list-style: none;
            overflow-y: auto;
            flex-grow: 1;
        }
        
        .conversation-item {
            padding: 15px;
            border-bottom: 1px solid #eaeaea;
            cursor: pointer;
            transition: background-color 0.2s;
            position: relative;
        }
        
        .conversation-item:hover {
            background-color: #f0f0f0;
        }
        
        .conversation-item.active {
            background-color: #e1f5fe;
        }
        
        .conversation-item.unread {
            background-color: #ebf7fd;
        }
        
        .conversation-username {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .conversation-email {
            color: #666;
            font-size: 12px;
            margin-bottom: 8px;
        }
        
        .conversation-preview {
            color: #777;
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .conversation-date {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 12px;
            color: #999;
        }
        
        .unread-badge {
            position: absolute;
            top: 35px;
            right: 15px;
            background-color: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 12px;
        }
        
        .search-user {
            padding: 15px;
            border-bottom: 1px solid #eaeaea;
            flex-shrink: 0;
        }
        
        .search-user-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        
        /* Chat header */
        .chat-header {
            padding: 15px;
            border-bottom: 1px solid #eaeaea;
            display: flex;
            align-items: center;
            background-color: #fff;
            flex-shrink: 0;
        }
        
        .chat-header-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #ddd;
            margin-right: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: bold;
            color: #666;
        }
        
        .chat-header-info {
            flex-grow: 1;
        }
        
        .chat-header-name {
            font-weight: bold;
            font-size: 16px;
        }
        
        .chat-header-status {
            font-size: 12px;
            color: #777;
        }
        
        /* Messages area */
        .messages-area {
            flex-grow: 1;
            overflow-y: auto;
            padding: 15px;
            display: flex;
            flex-direction: column;
            background-color: #f5f5f5;
        }
        
        .message {
            max-width: 70%;
            padding: 10px 15px;
            margin-bottom: 10px;
            border-radius: 18px;
            position: relative;
            word-wrap: break-word;
        }
        
        .message-sent {
            background-color: #0084ff;
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 5px;
            margin-left: 50px;
        }
        
        .message-received {
            background-color: #e4e6eb;
            color: #000;
            align-self: flex-start;
            border-bottom-left-radius: 5px;
            margin-right: 50px;
        }
        
        .message-time {
            font-size: 11px;
            margin-top: 5px;
            opacity: 0.7;
        }
        
        .message-sent .message-time {
            text-align: right;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .message-received .message-time {
            text-align: left;
            color: #777;
        }
        
        .message-product {
            font-size: 12px;
            margin-top: 5px;
            cursor: pointer;
        }
        
        .message-sent .message-product {
            text-align: right;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .message-received .message-product {
            text-align: left;
            color: #3498db;
        }
        
        /* Message form */
        .message-form-container {
            padding: 15px;
            background-color: #f5f5f5;
            border-top: 1px solid #eaeaea;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }
        
        .message-form {
            display: flex;
            align-items: flex-end;
        }
        
        .message-textarea {
            flex-grow: 1;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 20px;
            resize: none;
            min-height: 44px;
            max-height: 120px;
            margin-right: 10px;
            font-size: 14px;
        }
        
        .message-send-button {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #0084ff;
            color: white;
            border: none;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 20px;
        }
        
        .message-options {
            display: flex;
            margin-bottom: 10px;
        }
        
        .product-select {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
            max-width: 300px;
        }
        
        .empty-state {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100%;
            color: #999;
            text-align: center;
            padding: 20px;
        }
        
        .empty-state-icon {
            font-size: 60px;
            margin-bottom: 20px;
            color: #ddd;
        }
        
        .new-message-button {
            padding: 8px 15px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
        }
        
        .compose-message-container {
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        
        .footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 20px 0;
            margin-top: 40px;
        }

        a {
            text-decoration: none;
            color: inherit;
        }
        
        /* Date divider in chat */
        .date-divider {
            text-align: center;
            margin: 15px 0;
            position: relative;
        }
        
        .date-divider::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background: #ddd;
            z-index: 1;
        }
        
        .date-divider span {
            background: #f5f5f5;
            padding: 0 15px;
            position: relative;
            z-index: 2;
            color: #888;
            font-size: 12px;
        }
        
        /* Typing indicator */
        .typing-indicator {
            display: flex;
            align-items: center;
            margin-top: 5px;
            margin-bottom: 15px;
            margin-left: 15px;
            color: #777;
            font-size: 12px;
        }
        
        .typing-indicator span {
            padding-left: 5px;
        }
        
        .dot {
            width: 8px;
            height: 8px;
            background-color: #999;
            border-radius: 50%;
            display: inline-block;
            margin: 0 1px;
            animation: pulse 1.4s infinite ease-in-out;
        }
        
        .dot:nth-child(1) { animation-delay: -0.32s; }
        .dot:nth-child(2) { animation-delay: -0.16s; }
        
        @keyframes pulse {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1); }
        }
    </style>
</head>

<body>
    <header>
        <div class="container">
            <div class="header-top">
                <div class="logo-area">
                    <a href="dashboard.php"><img src="logo-sklepu.png" alt="logo"></a>
                </div>
                
                <div class="search-area">
                    <form class="search-form" action="dashboard.php" method="GET">
                        <input type="text" class="search-input" name="query" placeholder="szukanie itp">
                        <button type="submit" class="search-button">Szukaj</button>
                    </form>
                </div>
                <?php if ($is_logged_in): ?>
                    <div class="cart-icon" onclick="location.href='cart.php'">
                        🛒 <span class="cart-count" id="cart-count"><?php echo $cart_count; ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="user-icon" onclick="toggleUserMenu()">
                        <?php echo htmlspecialchars($username); ?>
                    </div>
                    <div class="user-menu" id="userMenu">
                        <div class="menu-item">
                            <a href="account_management.php">👤Zarządzaj kontem</a>
                        </div>
                        <div class="menu-item">
                            <a href="add-product.php">📦Moje oferty</a>
                        </div>
                        <div class="menu-item">
                            <a href="purchase-history.php">🛒Moje kupno</a>
                        </div>
                        <div class="menu-item">
                            <a href="messages.php">
                                💬Wiadomości
                                <?php if ($unread_count > 0): ?>
                                <span>(<?php echo $unread_count; ?> nieprzeczytane)</span>
                                <?php endif; ?>
                            </a>
                        </div>
                         <div class="menu-item">
                                <a style="text-decoration: none; color: inherit;" href="info.php">ℹ️O stronie</a>
                            </div>
                        <div class="menu-item">
                            <a href="logout.php">🚪Wyloguj</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="main-content container">
        <div class="section-title">
            <span>Wiadomości</span>
            
            <?php if (!isset($_GET['conversation']) && !isset($_GET['new_message'])): ?>
            <a href="?new_message=1" class="new-message-button">+ Nowa wiadomość</a>
            <?php endif; ?>
        </div>
        
        <?php if (isset($message_success)): ?>
        <div class="alert alert-success">
            <?php echo $message_success; ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($message_error)): ?>
        <div class="alert alert-danger">
            <?php echo $message_error; ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['new_message'])): ?>
        <!-- Compose new message form -->
        <div class="compose-message-container">
            <h3>Nowa wiadomość</h3>
            <form action="messages.php" method="POST">
                <div class="form-group">
                    <label for="receiver_email">Email odbiorcy:</label>
                    <input type="email" id="receiver_email" name="receiver_email" required 
                           value="<?php echo isset($_GET['to']) ? htmlspecialchars($_GET['to']) : ''; ?>">
                </div>
                
                <div class="search-results" id="emailSearchResults" style="display: none;"></div>
                
                <div class="form-group">
                    <label for="product_select">Dotyczy ogłoszenia (opcjonalnie):</label>
                    <select id="product_select" name="product_id">
                        <option value="">-- Wybierz ogłoszenie --</option>
                        <?php
                        while ($product = $user_products->fetch_assoc()) {
                            echo '<option value="'.$product['ProductID'].'">' . htmlspecialchars($product['Title']) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="message_content">Treść wiadomości:</label>
                    <textarea id="message_content" name="message_content" rows="5" required></textarea>
                </div>
                
                <div class="form-group">
                    <button type="submit" name="send_message" class="send-button">Wyślij wiadomość</button>
                    <a href="messages.php" style="margin-left: 10px;">Anuluj</a>
                </div>
            </form>
            
            <?php if (isset($_GET['search_email']) && $searched_users->num_rows > 0): ?>
            <div class="search-results">
                <h4>Wyniki wyszukiwania:</h4>
                <?php while ($user = $searched_users->fetch_assoc()): ?>
                <div class="search-result-item" onclick="selectEmailFromSearch('<?php echo htmlspecialchars($user['Email']); ?>')">
                    <div class="search-result-username"><?php echo htmlspecialchars($user['Username']); ?></div>
                    <div class="search-result-email"><?php echo htmlspecialchars($user['Email']); ?></div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <?php else: ?>
        
        <!-- Messenger-style chat interface -->
        <div class="messenger-container">
            <!-- Contacts sidebar -->
             <!-- Contacts sidebar -->
            <div class="contacts-sidebar">
                <div class="tabs">
                    <a href="?view=inbox" class="tab <?php echo $view == 'inbox' ? 'active' : ''; ?>">
                        Odebrane
                        <?php if ($unread_count > 0): ?>
                        <span class="tab-badge"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </a>

                </div>
                
                <div class="search-user">
                    <input type="text" class="search-user-input" placeholder="Szukaj użytkownika..." id="userSearchInput">
                </div>
                
                <ul class="conversation-list">
                    <?php 
                    if (isset($conversations_result) && $conversations_result->num_rows > 0):
                        while ($conversation = $conversations_result->fetch_assoc()): 
                            $other_user_id = $view == 'inbox' ? $conversation['SenderID'] : $conversation['ReceiverID'];
                    ?>
                    <li class="conversation-item <?php echo (isset($_GET['conversation']) && $_GET['conversation'] == $other_user_id) ? 'active' : ''; ?> <?php echo ($view == 'inbox' && !$conversation['IsRead']) ? 'unread' : ''; ?>">
                        <a href="?conversation=<?php echo $other_user_id; ?>">
                            <div class="conversation-username"><?php echo htmlspecialchars($conversation['Username']); ?></div>
                            <div class="conversation-email"><?php echo htmlspecialchars($conversation['Email']); ?></div>
                            <div class="conversation-preview">
                                <?php 
                                if ($conversation['ProductID']) {
                                    echo '[' . htmlspecialchars($conversation['ProductTitle']) . '] ';
                                }
                                echo htmlspecialchars(mb_substr($conversation['MessageContent'], 0, 50)) . (mb_strlen($conversation['MessageContent']) > 50 ? '...' : '');
                                ?>
                            </div>
                            <div class="conversation-date">
                                <?php echo date('H:i', strtotime($conversation['SentDate'])); ?>
                                <?php echo date('Y-m-d', strtotime($conversation['SentDate'])) == date('Y-m-d') ? '' : date('d.m', strtotime($conversation['SentDate'])); ?>
                            </div>
                            <?php if ($view == 'inbox' && isset($conversation['UnreadCount']) && $conversation['UnreadCount'] > 0): ?>
                            <div class="unread-badge"><?php echo $conversation['UnreadCount']; ?></div>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <li class="conversation-item">
                        <div class="empty-state">
                            <div class="empty-state-icon">💬</div>
                            <p>Brak wiadomości</p>
                        </div>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <!-- Chat container -->
            <div class="chat-container">
                <?php if (isset($conversation_user)): ?>
                <!-- Active conversation view -->
                <div class="chat-header">
                    <div class="chat-header-avatar">
                        <?php echo strtoupper(substr($conversation_user['Username'], 0, 1)); ?>
                    </div>
                    <div class="chat-header-info">
                        <div class="chat-header-name"><?php echo htmlspecialchars($conversation_user['Username']); ?></div>
                        <div class="chat-header-status"><?php echo htmlspecialchars($conversation_user['Email']); ?></div>
                    </div>
                </div>
                
                <div class="messages-area" id="messagesArea">
                    <?php 
                    $current_date = '';
                    if ($messages_result->num_rows > 0):
                        while ($message = $messages_result->fetch_assoc()):
                            $message_date = date('Y-m-d', strtotime($message['SentDate']));
                            
                            // Add date divider if date changes
                            if ($message_date != $current_date):
                                $current_date = $message_date;
                                $date_display = ($message_date == date('Y-m-d')) ? 'Dzisiaj' : date('d.m.Y', strtotime($message['SentDate']));
                    ?>
                    <div class="date-divider"><span><?php echo $date_display; ?></span></div>
                    <?php endif; ?>
                    
                    <div class="message <?php echo ($message['SenderID'] == $user_id) ? 'message-sent' : 'message-received'; ?>">
                        <?php echo nl2br(htmlspecialchars($message['MessageContent'])); ?>
                        
                        <?php if ($message['ProductID']): ?>
                        <div class="message-product">
                            <a href="product.php?id=<?php echo $message['ProductID']; ?>" target="_blank">
                                Re: <?php echo htmlspecialchars($message['ProductTitle']); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <div class="message-time">
                            <?php echo date('H:i', strtotime($message['SentDate'])); ?>
                            <?php if (!$message['IsRead'] && $message['SenderID'] == $user_id): ?>
                            · Nieprzeczytane
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">✉️</div>
                        <p>Brak wiadomości. Rozpocznij konwersację poniżej.</p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="message-form-container">
                    <form class="message-form" method="POST" action="messages.php">
                        <input type="hidden" name="receiver_email" value="<?php echo htmlspecialchars($conversation_user['Email']); ?>">
                        <input type="hidden" name="conversation_partner_id" value="<?php echo $conversation_partner_id; ?>">
                        
                        <div class="message-options">
                            <select name="product_id" class="product-select">
                                <option value="">-- Bez ogłoszenia --</option>
                                <?php
                                $user_products->data_seek(0); // Reset the pointer to beginning
                                while ($product = $user_products->fetch_assoc()) {
                                    echo '<option value="'.$product['ProductID'].'">' . htmlspecialchars($product['Title']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <textarea class="message-textarea" name="message_content" placeholder="Napisz wiadomość..." required></textarea>
                        <button type="submit" name="send_message" class="message-send-button">➤</button>
                    </form>
                </div>
                
                <?php else: ?>
                <!-- No active conversation -->
                <div class="empty-state">
                    <div class="empty-state-icon">💬</div>
                    <p>Wybierz konwersację z listy lub</p>
                    <a href="?new_message=1" class="new-message-button" style="margin-top: 15px;">+ Rozpocznij nową konwersację</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2023 Serwis Ogłoszeniowy. Wszelkie prawa zastrzeżone.</p>
        </div>
    </footer>

    <script>
        // Toggle user menu
        function toggleUserMenu() {
            document.getElementById('userMenu').classList.toggle('active');
        }
        
        // Close menu when clicking outside
        window.addEventListener('click', function(e) {
            if (!e.target.matches('.user-icon') && !document.getElementById('userMenu').contains(e.target)) {
                document.getElementById('userMenu').classList.remove('active');
            }
        });
        
        // Auto-scroll to bottom of messages
        function scrollToBottom() {
            const messagesArea = document.getElementById('messagesArea');
            if (messagesArea) {
                messagesArea.scrollTop = messagesArea.scrollHeight;
            }
        }
        
        // Execute on page load
        document.addEventListener('DOMContentLoaded', function() {
            scrollToBottom();
            
            // Handle textarea auto resize
            const textarea = document.querySelector('.message-textarea');
            if (textarea) {
                textarea.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = (this.scrollHeight) + 'px';
                });
            }
            
            // Live search for email
            const userSearchInput = document.getElementById('userSearchInput');
            if (userSearchInput) {
                userSearchInput.addEventListener('input', function() {
                    if (this.value.length > 2) {
                        window.location.href = 'messages.php?search_email=' + encodeURIComponent(this.value);
                    }
                });
            }
        });
        
        // Select email from search results
        function selectEmailFromSearch(email) {
            document.getElementById('receiver_email').value = email;
        }

        function addToCart(productId, quantity = 1) {
        // Check if user is logged in (PHP variable converted to JS boolean)
        const isLoggedIn = window.isLoggedIn || false; // This should be set elsewhere based on PHP
        
        if (isLoggedIn) {
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&quantity=${quantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const cartCount = document.getElementById('cart-count');
                    if (cartCount) {
                        cartCount.textContent = parseInt(cartCount.textContent) + quantity;
                    }
                    alert('Produkt dodany do koszyka!');
                } else {
                    alert('Wystąpił błąd: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Wystąpił błąd podczas dodawania do koszyka.');
            });
        } else {
            alert('Musisz być zalogowany, aby dodać produkt do koszyka.');
            window.location.href = 'login.php';
        }
    }
    </script>
</body>
</html>