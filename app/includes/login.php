<?php
// Definice uživatelských údajů pro jednoduchý login
$users = [
    'admin' => 'heslo123',
];

session_start();

// Nastavení session timeout na 35 minut (o něco více než JavaScript)
$timeout = 35 * 60; // 35 minut v sekundách

// Kontrola timeoutu pouze pokud je uživatel přihlášen
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout) {
        // Session vypršela
        session_unset();
        session_destroy();
        header('Location: ../public/login.php');
        exit;
    }
    // Aktualizace času poslední aktivity při každém načtení stránky
    $_SESSION['LAST_ACTIVITY'] = time();
}

function check_login($username, $password)
{
    global $users;
    return isset($users[$username]) && $users[$username] === $password;
}

function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

function require_login()
{
    if (!is_logged_in()) {
        header('Location: ../public/login.php');
        exit;
    }
}
