-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Maj 26, 2025 at 08:03 PM
-- Wersja serwera: 10.4.32-MariaDB
-- Wersja PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sklep_internetowy`
--

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `cart`
--

CREATE TABLE `cart` (
  `CartID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `ProductID` int(11) NOT NULL,
  `Quantity` int(11) NOT NULL DEFAULT 1,
  `DateAdded` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`CartID`, `UserID`, `ProductID`, `Quantity`, `DateAdded`) VALUES
(7, 4, 5, 1, '2025-05-18 22:18:47'),
(8, 4, 4, 1, '2025-05-18 22:19:06'),
(9, 2, 6, 1, '2025-05-19 20:16:11'),
(10, 2, 4, 1, '2025-05-25 21:46:19');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `categories`
--

CREATE TABLE `categories` (
  `CategoryID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `ParentCategoryID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`CategoryID`, `Name`, `Description`, `ParentCategoryID`) VALUES
(1, 'Elektronika', 'Urządzenia elektroniczne, komputery, telefony itp.', NULL),
(2, 'Odzież', 'Ubrania, buty i akcesoria', NULL),
(3, 'Dom i Ogród', 'Meble, dekoracje, narzędzia ogrodowe', NULL),
(4, 'Książki', 'Książki, e-booki, audiobooki', NULL);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `favorites`
--

CREATE TABLE `favorites` (
  `FavoriteID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `ProductID` int(11) NOT NULL,
  `AddedDate` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `messages`
--

CREATE TABLE `messages` (
  `MessageID` int(11) NOT NULL,
  `SenderID` int(11) NOT NULL,
  `ReceiverID` int(11) NOT NULL,
  `ProductID` int(11) DEFAULT NULL,
  `MessageContent` text NOT NULL,
  `SentDate` datetime DEFAULT current_timestamp(),
  `IsRead` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`MessageID`, `SenderID`, `ReceiverID`, `ProductID`, `MessageContent`, `SentDate`, `IsRead`) VALUES
(1, 3, 2, 3, 'Siema', '2025-05-19 18:21:36', 1),
(2, 2, 3, NULL, 'Siema', '2025-05-19 18:31:04', 0),
(3, 2, 3, 6, 'Elo', '2025-05-19 19:37:09', 0),
(4, 2, 3, 6, 'asafa', '2025-05-19 20:16:10', 0);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `orderdetails`
--

CREATE TABLE `orderdetails` (
  `OrderDetailID` int(11) NOT NULL,
  `OrderID` int(11) NOT NULL,
  `ProductID` int(11) NOT NULL,
  `Quantity` int(11) NOT NULL DEFAULT 1,
  `UnitPrice` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orderdetails`
--

INSERT INTO `orderdetails` (`OrderDetailID`, `OrderID`, `ProductID`, `Quantity`, `UnitPrice`) VALUES
(1, 1, 3, 1, 50.00),
(2, 1, 4, 1, 50.00),
(3, 2, 3, 1, 50.00),
(4, 2, 4, 1, 50.00),
(5, 3, 6, 1, 520.00),
(6, 4, 3, 1, 50.00);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `orders`
--

CREATE TABLE `orders` (
  `OrderID` int(11) NOT NULL,
  `BuyerID` int(11) NOT NULL,
  `OrderDate` datetime DEFAULT current_timestamp(),
  `TotalAmount` decimal(10,2) NOT NULL,
  `Status` enum('pending','paid','shipped','completed','cancelled') DEFAULT 'pending',
  `ShippingAddress` text DEFAULT NULL,
  `PaymentMethod` varchar(50) DEFAULT NULL,
  `PaymentStatus` enum('pending','completed','failed','refunded') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`OrderID`, `BuyerID`, `OrderDate`, `TotalAmount`, `Status`, `ShippingAddress`, `PaymentMethod`, `PaymentStatus`) VALUES
(1, 2, '2025-05-18 22:14:21', 100.00, 'pending', 'Świetlica 29', 'credit_card', 'pending'),
(2, 2, '2025-05-18 22:14:54', 100.00, 'pending', 'Świetlica 29', 'blik', 'pending'),
(3, 2, '2025-05-25 21:29:52', 520.00, 'paid', 'Franek Karpiuk\nŚwietlica 29\n29-100 Wygoda', 'blik', 'completed'),
(4, 2, '2025-05-25 21:40:36', 50.00, 'paid', 'Franek Karpiuk\nŚwietlica 29\n29-100 Wygoda', 'blik', 'completed');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `password_resets`
--

CREATE TABLE `password_resets` (
  `ResetID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `ResetToken` varchar(64) NOT NULL,
  `ExpiresAt` datetime NOT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp(),
  `UsedAt` datetime DEFAULT NULL,
  `IPAddress` varchar(45) DEFAULT NULL,
  `UserAgent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `productimages`
--

CREATE TABLE `productimages` (
  `ImageID` int(11) NOT NULL,
  `ProductID` int(11) NOT NULL,
  `ImageURL` varchar(255) NOT NULL,
  `IsPrimary` tinyint(1) DEFAULT 0,
  `UploadDate` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `productimages`
--

INSERT INTO `productimages` (`ImageID`, `ProductID`, `ImageURL`, `IsPrimary`, `UploadDate`) VALUES
(3, 3, 'uploads/products/6829fef82d4ca_skechers-kremowe-damskie-buty-sportowe-150041_OFWT-1.jpg', 1, '2025-05-18 17:38:32'),
(4, 4, 'uploads/products/6829ff302387a_kaskada-producent-odziezy-damskiej-bluzka-ania-ecru.jpg', 1, '2025-05-18 17:39:28'),
(5, 5, 'uploads/products/682a168ac1ae3_szkodniki-sanitarne-szczur-sniady-szkodniki-waw-pl.jpg', 1, '2025-05-18 19:19:06'),
(6, 6, 'uploads/products/682b578ddfd3e_pol_pl_Bawelniana-dresowa-BLUZA-oversize-z-kapturem-i-mankietami-z-prazkowanej-dzianiny-czarna-E340-13431_1.jpg', 1, '2025-05-19 18:08:45'),
(7, 7, 'uploads/products/682b58f50a7d8_zabawka-antystresowa-dla-dzieci-5-i-doroslych-silikonowa-plansza-28-babelkow.jpg', 1, '2025-05-19 18:14:45'),
(8, 8, 'uploads/products/682b59117cf38_infantino-zabawka-interaktywna-dj-panda-b-iext63886002.jpg', 1, '2025-05-19 18:15:13'),
(9, 9, 'uploads/products/682b678a0b855_telewizor-lg-55ur781c-55-4k-hdr-bluetooth-surround-ready-thinq-ai-3-lata-gwarancji.jpg', 1, '2025-05-19 19:16:58'),
(10, 10, 'uploads/products/682b67a24bff2_pngtree-mursiethiopiaafrica-people-black-man-photo-picture-image_7321766.jpg', 1, '2025-05-19 19:17:22');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `products`
--

CREATE TABLE `products` (
  `ProductID` int(11) NOT NULL,
  `SellerID` int(11) NOT NULL,
  `CategoryID` int(11) NOT NULL,
  `Title` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `Price` decimal(10,2) NOT NULL,
  `Quantity` int(11) DEFAULT 1,
  `Condition` enum('new','used','refurbished') NOT NULL,
  `PostedDate` datetime DEFAULT current_timestamp(),
  `Status` enum('active','sold','cancelled') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`ProductID`, `SellerID`, `CategoryID`, `Title`, `Description`, `Price`, `Quantity`, `Condition`, `PostedDate`, `Status`) VALUES
(3, 3, 2, 'Buty', 'Fajne takie o', 50.00, 1, 'new', '2025-05-18 17:38:32', 'sold'),
(4, 3, 2, 'Bluzka', 'Damska', 50.00, 1, 'new', '2025-05-18 17:39:28', 'active'),
(5, 2, 1, 'Myszak', 'aufsahgshds', 15.00, 1, 'used', '2025-05-18 19:19:06', 'active'),
(6, 3, 2, 'Bluza', 'Nowa', 520.00, 1, 'new', '2025-05-19 18:08:45', 'sold'),
(7, 3, 3, 'Popita', 'Kolor: tęczowy', 105.99, 3, 'new', '2025-05-19 18:14:45', 'active'),
(8, 3, 3, 'Zabawka dla dziecka', 'Coś dla dzieci', 55.99, 6, 'new', '2025-05-19 18:15:13', 'active'),
(9, 2, 1, 'Telewizor', '24cali', 1299.99, 1, 'new', '2025-05-19 19:16:58', 'active'),
(10, 2, 3, 'Czarny Czlowiek', 'Prosto z afryki', 200.00, 2, 'used', '2025-05-19 19:17:22', 'active');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `reviews`
--

CREATE TABLE `reviews` (
  `ReviewID` int(11) NOT NULL,
  `ProductID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `Rating` int(11) NOT NULL CHECK (`Rating` between 1 and 5),
  `Comment` text DEFAULT NULL,
  `ReviewDate` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`ReviewID`, `ProductID`, `UserID`, `Rating`, `Comment`, `ReviewDate`) VALUES
(1, 4, 2, 5, 'Bardzo fajna polecam', '2025-05-18 21:59:12'),
(2, 4, 4, 3, 'Długo szła ale fajna', '2025-05-18 22:22:24'),
(3, 6, 2, 2, 'Fajna bardzo', '2025-05-25 20:11:24');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `users`
--

CREATE TABLE `users` (
  `UserID` int(11) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `FirstName` varchar(50) DEFAULT NULL,
  `LastName` varchar(50) DEFAULT NULL,
  `PhoneNumber` varchar(15) DEFAULT NULL,
  `Address` text DEFAULT NULL,
  `RegistrationDate` datetime DEFAULT current_timestamp(),
  `LastLoginDate` datetime DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT 1,
  `UserRole` enum('regular','admin') DEFAULT 'regular'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserID`, `Username`, `Email`, `Password`, `FirstName`, `LastName`, `PhoneNumber`, `Address`, `RegistrationDate`, `LastLoginDate`, `IsActive`, `UserRole`) VALUES
(2, 'Franek', 'markarp77@gmail.com', '$2y$10$V8mDdmWDPiuCY1Mt16iJ.OuYy3GFfYXBCtYSh9LHPahVTTMysvvxG', 'Franek', 'Karpiuk', '', '', '2025-05-15 19:58:53', '2025-05-26 16:16:44', 1, 'regular'),
(3, 'Adam', 'adam@gmail.com', '$2y$10$NC.1v9noyyjDHDWcXsKSIuaF1T5811DCcJ4ifzuUxYpKTyOPQJl1a', 'Adam', 'Nowak', NULL, NULL, '2025-05-18 17:36:16', '2025-05-19 18:07:53', 1, 'regular'),
(4, 'Tatarinio', 'tat@gmail.com', '$2y$10$wvmx2OyRtv654XbtQxqPHOvMAE8z2e6VikKCJdHOOru8aPbLHrEjO', 'Adam', 'Iga', NULL, NULL, '2025-05-18 22:18:15', '2025-05-18 22:18:26', 1, 'regular'),
(5, 'jankowalski', 'jan.kowalski@example.com', '$2y$10$8ho0.0/FbTA9T9.9q56BUucvvEKhUTSfQMNQ5FMI94g7EkkLxYr9u', 'Jan', 'Kowalski', '987654321', NULL, '2025-05-25 20:30:54', NULL, 1, 'regular'),
(6, 'annanowak', 'anna.nowak@example.com', '$2y$10$AXGRiVklxshr1R/QZefLcOGDJGkrJavYGETwISQY1woNrJ8Rue9oG', 'Anna', 'Nowak', '555666777', NULL, '2025-05-25 20:30:54', NULL, 1, 'regular'),
(7, 'piotrwisniewski', 'piotr.wisniewski@example.com', '$2y$10$jC.b.DU3RWYf4CtXQFGaAeJOJlPWkHFcBhMWdzPf2yfHerS/JJVbi', 'Piotr', 'Wiśniewski', '111222333', NULL, '2025-05-25 20:30:54', NULL, 1, 'regular'),
(8, 'mariakaminska', 'maria.kaminska@example.com', '$2y$10$zrWABZbgnLJhETVxDOOWw.L.FIVTCooRUwNxjyYn7GzzYS7Fla8OK', 'Maria', 'Kamińska', '444555666', NULL, '2025-05-25 20:30:54', NULL, 1, 'regular');

--
-- Indeksy dla zrzutów tabel
--

--
-- Indeksy dla tabeli `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`CartID`),
  ADD UNIQUE KEY `UserID` (`UserID`,`ProductID`),
  ADD KEY `ProductID` (`ProductID`),
  ADD KEY `idx_cart_user` (`UserID`);

--
-- Indeksy dla tabeli `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`CategoryID`),
  ADD KEY `ParentCategoryID` (`ParentCategoryID`);

--
-- Indeksy dla tabeli `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`FavoriteID`),
  ADD UNIQUE KEY `UserID` (`UserID`,`ProductID`),
  ADD KEY `ProductID` (`ProductID`);

--
-- Indeksy dla tabeli `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`MessageID`),
  ADD KEY `SenderID` (`SenderID`),
  ADD KEY `ReceiverID` (`ReceiverID`),
  ADD KEY `ProductID` (`ProductID`);

--
-- Indeksy dla tabeli `orderdetails`
--
ALTER TABLE `orderdetails`
  ADD PRIMARY KEY (`OrderDetailID`),
  ADD KEY `OrderID` (`OrderID`),
  ADD KEY `ProductID` (`ProductID`);

--
-- Indeksy dla tabeli `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`OrderID`),
  ADD KEY `BuyerID` (`BuyerID`);

--
-- Indeksy dla tabeli `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`ResetID`),
  ADD UNIQUE KEY `ResetToken` (`ResetToken`),
  ADD KEY `idx_token` (`ResetToken`),
  ADD KEY `idx_user_id` (`UserID`),
  ADD KEY `idx_expires` (`ExpiresAt`),
  ADD KEY `idx_created` (`CreatedAt`);

--
-- Indeksy dla tabeli `productimages`
--
ALTER TABLE `productimages`
  ADD PRIMARY KEY (`ImageID`),
  ADD KEY `ProductID` (`ProductID`);

--
-- Indeksy dla tabeli `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`ProductID`),
  ADD KEY `SellerID` (`SellerID`),
  ADD KEY `CategoryID` (`CategoryID`);

--
-- Indeksy dla tabeli `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`ReviewID`),
  ADD KEY `ProductID` (`ProductID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indeksy dla tabeli `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `Username` (`Username`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `CartID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `CategoryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `favorites`
--
ALTER TABLE `favorites`
  MODIFY `FavoriteID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `MessageID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `orderdetails`
--
ALTER TABLE `orderdetails`
  MODIFY `OrderDetailID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `OrderID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `ResetID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `productimages`
--
ALTER TABLE `productimages`
  MODIFY `ImageID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `ProductID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `ReviewID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ProductID`) ON DELETE CASCADE;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`ParentCategoryID`) REFERENCES `categories` (`CategoryID`);

--
-- Constraints for table `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`),
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ProductID`);

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`SenderID`) REFERENCES `users` (`UserID`),
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`ReceiverID`) REFERENCES `users` (`UserID`),
  ADD CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ProductID`);

--
-- Constraints for table `orderdetails`
--
ALTER TABLE `orderdetails`
  ADD CONSTRAINT `orderdetails_ibfk_1` FOREIGN KEY (`OrderID`) REFERENCES `orders` (`OrderID`),
  ADD CONSTRAINT `orderdetails_ibfk_2` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ProductID`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`BuyerID`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `productimages`
--
ALTER TABLE `productimages`
  ADD CONSTRAINT `productimages_ibfk_1` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ProductID`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`SellerID`) REFERENCES `users` (`UserID`),
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`CategoryID`) REFERENCES `categories` (`CategoryID`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ProductID`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
