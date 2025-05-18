

-- Tworzenie bazy danych
CREATE DATABASE IF NOT EXISTS sklep_internetowy;
USE sklep_internetowy;

-- Tworzenie tabeli użytkowników
CREATE TABLE IF NOT EXISTS Users (
    UserID INT PRIMARY KEY AUTO_INCREMENT,
    Username VARCHAR(50) NOT NULL UNIQUE,
    Email VARCHAR(100) NOT NULL UNIQUE,
    Password VARCHAR(255) NOT NULL,
    FirstName VARCHAR(50),
    LastName VARCHAR(50),
    PhoneNumber VARCHAR(15),
    Address TEXT,
    RegistrationDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    LastLoginDate DATETIME,
    IsActive BOOLEAN DEFAULT TRUE,
    UserRole ENUM('regular', 'admin') DEFAULT 'regular'
);

-- Wstawianie testowego administratora
INSERT INTO Users (Username, Email, Password, FirstName, LastName, UserRole, RegistrationDate)
VALUES ('admin', 'admin@example.com', '$2y$10$6SbCAFfZTPGQJGsCY.5cauEVZQRrSNkRy2hcP8QcmL5qn6wENSzAS', 'Admin', 'System', 'admin', NOW());

-- Hasło dla admina: admin123