<?php session_start();?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>O Serwisie Ogłoszeniowym</title>
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
        
        a{
            text-decoration: none;
            color: black;
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
            min-height: calc(100vh - 200px);
        }
        
        .section-title {
            font-size: 28px;
            margin-bottom: 20px;
            color: #333;
            text-align: center;
        }

        .info-card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .info-card h2 {
            font-size: 22px;
            color: #3498db;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eaeaea;
        }
        
        .info-card p {
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .team-members {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 20px;
        }
        
        .team-member {
            flex: 1;
            min-width: 250px;
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .team-member img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
            background-color: #e0e0e0;
        }
        
        .team-member h3 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .team-member p {
            font-size: 14px;
            color: #666;
        }
        
        .contact-form {
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
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .form-group textarea {
            height: 150px;
            resize: vertical;
        }
        
        .submit-button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }
        
        .submit-button:hover {
            background-color: #2980b9;
        }
        
        .faq-item {
            margin-bottom: 15px;
        }
        
        .faq-question {
            font-weight: bold;
            margin-bottom: 5px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .faq-question:after {
            content: '+';
            font-size: 20px;
        }
        
        .faq-question.active:after {
            content: '-';
        }
        
        .faq-answer {
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
            display: none;
        }
        
        .faq-answer.active {
            display: block;
        }
        
        .app-stats {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            margin-top: 20px;
            text-align: center;
        }
        
        .stat-item {
            flex: 1;
            min-width: 100px;
            padding: 15px;
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #666;
        }
        
        .footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 20px 0;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .header-top {
                flex-direction: column;
            }
            
            .search-area {
                margin: 15px 0;
                width: 100%;
            }
            
            .user-area {
                align-self: flex-end;
            }
            
            .team-members {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <?php
    // Inicjalizacja sesji
    
    // Sprawdzenie czy użytkownik jest zalogowany
    $is_logged_in = isset($_SESSION['user_id']);
    $username = $is_logged_in ? $_SESSION['username'] : '';
    
    // Połączenie z bazą danych
    $conn = new mysqli("localhost", "root", "", "sklep_internetowy");
    
    // Sprawdzenie połączenia
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
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
    
    // Statystyki aplikacji (w realnym projekcie te dane byłyby pobierane z bazy)
    $total_products = 0;
    $total_users = 0;
    $total_transactions = 0;
    
    $stats_sql = "SELECT 
        (SELECT COUNT(*) FROM products WHERE Status = 'active') as product_count,
        (SELECT COUNT(*) FROM users) as user_count,
        (SELECT COUNT(*) FROM orders) as order_count";
    
    $stats_result = $conn->query($stats_sql);
    if ($stats = $stats_result->fetch_assoc()) {
        $total_products = $stats['product_count'];
        $total_users = $stats['user_count'];
        $total_transactions = $stats['order_count'];
    }
    ?>

    <header>
        <div class="container">
            <div class="header-top">
                <div class="logo-area">
                    <a href="dashboard.php">Tutaj logo</a>
                </div>
                
                <div class="search-area">
                    <form class="search-form" action="dashboard.php" method="GET">
                        <input type="text" class="search-input" name="query" placeholder="szukanie itp">
                        <button type="submit" class="search-button">Szukaj</button>
                    </form>
                </div>
                <div class="user-area">
                    <?php if ($is_logged_in): ?>
                    <div class="cart-icon" onclick="location.href='cart.php'">
                        🛒 <span class="cart-count" id="cart-count"><?php echo $cart_count; ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="user-icon" onclick="toggleUserMenu()">
                        <?php echo $is_logged_in ? htmlspecialchars($_SESSION['username']) : 'Konto'; ?>
                    </div>
                    <div class="user-menu" id="userMenu">
                        <?php if ($is_logged_in): ?>
                            <div class="menu-item">
                                <a href="account_management.php">Zarządzaj kontem</a>
                            </div>
                            <div class="menu-item">
                                <a href="add-product.php">Moje oferty</a>
                            </div>
                            <div class="menu-item">
                                Moje kupno
                            </div>
                            <div class="menu-item">
                                <a style="text-decoration: none; color: inherit;" href="messages.php">Wiadomości</a>
                            </div>
                            <div class="menu-item">
                                <a style="text-decoration: none; color: inherit;" href="info.php">O stronie</a>
                            </div>
                            <div class="menu-item">
                                <a href="logout.php" style="text-decoration: none; color: inherit;">wyloguj</a>
                            </div>
                        <?php else: ?>
                            <div class="menu-item">
                                <a href="login.php" style="text-decoration: none; color: inherit;">Zaloguj się</a>
                            </div>
                            <div class="menu-item">
                                <a href="register.php" style="text-decoration: none; color: inherit;">Zarejestruj się</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="main-content container">
        <h1 class="section-title">O Serwisie Ogłoszeniowym</h1>
        
        <div class="info-card">
            <h2>O Nas</h2>
            <p>Serwis Ogłoszeniowy to platforma stworzona z myślą o łatwym i bezpiecznym publikowaniu oraz przeglądaniu ogłoszeń. Naszym celem jest zapewnienie intuicyjnego interfejsu, który umożliwia użytkownikom szybkie znalezienie tego, czego szukają, oraz skuteczne dotarcie do potencjalnych klientów.</p>
            <p>Projekt został zrealizowany jako część praktycznego szkolenia z zakresu tworzenia aplikacji webowych, kładąc szczególny nacisk na bezpieczeństwo danych, wydajność oraz przyjazność dla użytkownika.</p>
            
            <div class="app-stats">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $total_products; ?></div>
                    <div class="stat-label">Aktywnych ogłoszeń</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $total_users; ?></div>
                    <div class="stat-label">Zarejestrowanych użytkowników</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $total_transactions; ?></div>
                    <div class="stat-label">Przeprowadzonych transakcji</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">24/7</div>
                    <div class="stat-label">Wsparcie techniczne</div>
                </div>
            </div>
        </div>
        
        <div class="info-card">
            <h2>Nasz Zespół</h2>
            <p>Poznaj osoby odpowiedzialne za stworzenie i rozwój Serwisu Ogłoszeniowego:</p>
            
            <div class="team-members">
                <div class="team-member">
                    <img src="/api/placeholder/100/100" alt="Franciszek Karpiuk">
                    <h3>Franciszek Karpiuk</h3>
                    <p>Główny programista</p>
                    <p>franciszek.karpiuk@example.com</p>
                </div>
                <div class="team-member">
                    <img src="/api/placeholder/100/100" alt="Jakub Kaczmarek">
                    <h3>Jakub Kaczmarek</h3>
                    <p>UI/UX Designer</p>
                    <p>jakub.kaczmarek@example.com</p>
                </div>
            </div>
        </div>
        
        <div class="info-card">
            <h2>Informacje Techniczne</h2>
            <p><strong>Wersja aplikacji:</strong> 1.04.25 (Testowa)</p>
            <p><strong>Data ostatniej aktualizacji:</strong> 15.05.2025</p>
            <p><strong>Technologie:</strong> PHP, MySQL, HTML5, CSS3, JavaScript</p>
            <p><strong>Przeglądarki:</strong> Aplikacja działa poprawnie na najnowszych wersjach Chrome, Firefox, Safari i Edge.</p>
            <p><strong>Wymagania systemowe:</strong> Dowolny nowoczesny system operacyjny z dostępem do internetu.</p>
        </div>
        
        <div class="info-card">
            <h2>FAQ - Najczęściej zadawane pytania</h2>
            
            <div class="faq-item">
                <div class="faq-question">Jak dodać ogłoszenie?</div>
                <div class="faq-answer">
                    Aby dodać ogłoszenie, należy najpierw założyć konto i się zalogować. Następnie należy przejść do zakładki "Moje ogłoszenia" i kliknąć przycisk "Dodaj ogłoszenie". Wypełnij formularz, dodaj zdjęcia i kliknij "Opublikuj".
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">Jak mogę edytować swoje ogłoszenie?</div>
                <div class="faq-answer">
                    Edycja ogłoszenia jest możliwa po zalogowaniu się na konto i przejściu do zakładki "Moje ogłoszenia". Przy każdym ogłoszeniu znajduje się przycisk "Edytuj", który przeniesie Cię do formularza edycji.
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">Czy korzystanie z serwisu jest bezpłatne?</div>
                <div class="faq-answer">
                    Tak, podstawowe funkcje serwisu, takie jak przeglądanie ogłoszeń i dodawanie własnych, są całkowicie bezpłatne. W przyszłości planujemy wprowadzenie opcji promowania ogłoszeń za dodatkową opłatą.
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">Co zrobić, gdy ogłoszenie nie pojawia się w wynikach wyszukiwania?</div>
                <div class="faq-answer">
                    Każde nowe ogłoszenie przechodzi przez krótki proces weryfikacji, który może potrwać do 24 godzin. Jeśli po tym czasie Twoje ogłoszenie nadal się nie pojawia, sprawdź, czy poprawnie wypełniłeś wszystkie wymagane pola lub skontaktuj się z naszym zespołem wsparcia.
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">Jak mogę skontaktować się ze sprzedającym?</div>
                <div class="faq-answer">
                    Na stronie każdego ogłoszenia znajduje się przycisk "Kontakt", który umożliwia wysłanie wiadomości do sprzedającego. Musisz być zalogowany, aby korzystać z tej funkcji.
                </div>
            </div>
        </div>
        
        <div class="info-card">
            <h2>Kontakt</h2>
            <p><strong>Adres e-mail wsparcia:</strong> pomoc@serwisogloszeniowyprzyklad.pl</p>
            <p><strong>Telefon:</strong> +48 123 456 789 (w godz. 9:00-17:00, pon-pt)</p>
            <p><strong>Adres:</strong> ul. Programistów 123, 00-000 Warszawa</p>
            
            <h3 style="margin-top: 20px;">Formularz kontaktowy</h3>
            <form class="contact-form" action="send_message.php" method="POST">
                <div class="form-group">
                    <label for="name">Imię i nazwisko:</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Adres e-mail:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="subject">Temat:</label>
                    <input type="text" id="subject" name="subject" required>
                </div>
                
                <div class="form-group">
                    <label for="message">Wiadomość:</label>
                    <textarea id="message" name="message" required></textarea>
                </div>
                
                <button type="submit" class="submit-button">Wyślij wiadomość</button>
            </form>
        </div>
        
        <div class="info-card">
            <h2>Polityka prywatności</h2>
            <p>Serwis Ogłoszeniowy szanuje prywatność użytkowników i dba o ochronę ich danych osobowych. Korzystając z naszego serwisu, zgadzasz się na politykę prywatności, która jest zgodna z przepisami RODO.</p>
            <p>Pełna treść Polityki Prywatności oraz Regulaminu dostępna jest <a href="privacy_policy.php">tutaj</a>.</p>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Serwis Ogłoszeniowy. Wszelkie prawa zastrzeżone.</p>
            <p>Autorzy: Franciszek Karpiuk, Jakub Kaczmarek</p>
            <p>Kontakt: pomoc@serwisogloszeniowyprzyklad.pl</p>
            <p>Wersja aplikacji: 1.04.25 Testowa</p>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle user menu
            window.toggleUserMenu = function() {
                document.getElementById('userMenu').classList.toggle('active');
            }
            
            // Close user menu when clicking outside
            document.addEventListener('click', function(event) {
                const userMenu = document.getElementById('userMenu');
                const userIcon = document.querySelector('.user-icon');
                
                if (userMenu && userIcon && !userIcon.contains(event.target) && !userMenu.contains(event.target)) {
                    userMenu.classList.remove('active');
                }
            });
            
            // FAQ toggle functionality
            const faqQuestions = document.querySelectorAll('.faq-question');
            
            faqQuestions.forEach(question => {
                question.addEventListener('click', function() {
                    this.classList.toggle('active');
                    const answer = this.nextElementSibling;
                    answer.classList.toggle('active');
                });
            });
            
            // Contact form validation
            const contactForm = document.querySelector('.contact-form');
            if (contactForm) {
                contactForm.addEventListener('submit', function(e) {
                    const nameInput = document.getElementById('name');
                    const emailInput = document.getElementById('email');
                    const messageInput = document.getElementById('message');
                    
                    let isValid = true;
                    
                    if (nameInput.value.trim() === '') {
                        isValid = false;
                        alert('Proszę podać imię i nazwisko');
                    } else if (emailInput.value.trim() === '' || !validateEmail(emailInput.value)) {
                        isValid = false;
                        alert('Proszę podać poprawny adres e-mail');
                    } else if (messageInput.value.trim() === '') {
                        isValid = false;
                        alert('Proszę wprowadzić treść wiadomości');
                    }
                    
                    if (!isValid) {
                        e.preventDefault();
                    }
                });
                
                function validateEmail(email) {
                    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    return re.test(email);
                }
            }
        });
    </script>
</body>
</html>