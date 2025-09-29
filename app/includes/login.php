<?php

// Definice uživatelských údajů pro jednoduchý login (v reálném projektu by byly v databázi)
$users = [
    'admin' => 'heslo123',
];

session_start();

function check_login($username, $password)
{
    global $users;
    if (isset($users[$username]) && $users[$username] === $password) {
        return true;
    }
    return false;
}

function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

function require_login()
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}
