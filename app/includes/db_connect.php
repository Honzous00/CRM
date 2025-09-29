<?php

// Bezpečná definice konstant pro připojení k databázi.
// Tyto hodnoty si upravte podle vašeho nastavení v XAMPP/MariaDB.
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // Výchozí uživatel v XAMPP
define('DB_PASSWORD', '');     // Výchozí heslo v XAMPP
define('DB_NAME', 'muj_cms');  // Název databáze, kterou vytvoříte

// Vytvoření instance mysqli pro připojení
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Kontrola připojení
if ($conn->connect_error) {
    die("Chyba při připojování k databázi: " . $conn->connect_error);
}

// Nastavení kódování znaků na UTF-8 pro správnou podporu češtiny
$conn->set_charset("utf8mb4");

?>