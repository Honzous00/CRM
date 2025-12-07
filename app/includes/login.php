<?php
// app/includes/login.php
require_once __DIR__ . '/db_connect.php';

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
    global $conn;

    // Prepared statement pro bezpečnost - chrání před SQL injection
    $stmt = $conn->prepare("SELECT id, username, password_hash, is_admin FROM users WHERE username = ? AND aktivni = 1");

    if (!$stmt) {
        error_log("Chyba přípravy SQL: " . $conn->error);
        return false;
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Ověření hesla pomocí password_verify() - BEZPEČNÉ!
        if (password_verify($password, $user['password_hash'])) {

            // Aktualizace času posledního přihlášení v DB
            $update_stmt = $conn->prepare("UPDATE users SET posledni_prihlaseni = NOW() WHERE id = ?");
            $update_stmt->bind_param("i", $user['id']);
            $update_stmt->execute();

            // Uložení více informací do session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['is_admin'];

            return true;
        }
    }

    // Bezpečnostní zpoždění proti brute-force útokům
    sleep(1);

    return false;
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

function require_admin()
{
    if (!is_logged_in() || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
        header('Location: ../public/login.php');
        exit;
    }
}

// Funkce pro získání informací o přihlášeném uživateli
function get_logged_in_user()
{
    global $conn;

    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT id, username, is_admin FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            return $result->fetch_assoc();
        }
    }
    return null;
}

// Funkce pro změnu hesla
function change_password($user_id, $current_password, $new_password)
{
    global $conn;

    // Získání aktuálního hashe
    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Ověření aktuálního hesla
        if (password_verify($current_password, $user['password_hash'])) {
            // Hash nového hesla
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);

            // Aktualizace v databázi
            $update_stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $update_stmt->bind_param("si", $new_hash, $user_id);

            return $update_stmt->execute();
        }
    }

    return false;
}

// Funkce pro vytvoření nového uživatele (jen pro admina)
function create_user($username, $password, $is_admin = 0)
{
    global $conn;

    // Kontrola, zda uživatel již existuje
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        return false; // Uživatel již existuje
    }

    // Hash hesla
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Vložení nového uživatele
    $stmt = $conn->prepare("INSERT INTO users (username, password_hash, is_admin) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $username, $password_hash, $is_admin);

    return $stmt->execute();
}
