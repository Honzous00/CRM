<?php
// app/includes/db_connect.php

// Kontrola, zda systém není nainstalován (neexistuje lock soubor)
$lock_file = __DIR__ . '/../../installed.lock';
if (!file_exists($lock_file)) {
    // Pokud nejsme na instalační stránce, přesměrujeme na instalaci
    $current_page = basename($_SERVER['PHP_SELF']);
    if ($current_page != 'install.php' && $current_page != 'login.php') {
        header('Location: ../../install.php');
        exit;
    }
}


// Bezpečná definice konstant pro připojení k databázi.
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', 'Heslo123');
define('DB_NAME', 'muj_cms');

// Vytvoření instance mysqli pro připojení
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Kontrola připojení
if ($conn->connect_error) {
    die("Chyba při připojování k databázi: " . $conn->connect_error);
}

// Nastavení kódování znaků na UTF-8 pro správnou podporu češtiny
$conn->set_charset("utf8mb4");
