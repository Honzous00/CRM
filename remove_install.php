<?php
// CRM/remove_install.php
if (!isset($_GET['confirm']) || $_GET['confirm'] != 'yes') {
    echo "<h2>Smazání instalačního souboru</h2>";
    echo "<p>Tento skript smaže soubor install.php a SQL soubory.</p>";
    echo "<a href='?confirm=yes'>Ano, smazat instalační soubory</a>";
    echo " | <a href='public/index.php'>Ne, vrátit se do systému</a>";
    exit;
}

$files_to_delete = [
    __DIR__ . '/install.php',
    __DIR__ . '/muj_cms.sql',
    __DIR__ . '/database.sql',
    __DIR__ . '/remove_install.php',
];

echo "<h2>Odstraňování instalačních souborů</h2>";
foreach ($files_to_delete as $file) {
    if (file_exists($file)) {
        if (unlink($file)) {
            echo "<p style='color: green'>✓ Smazáno: " . basename($file) . "</p>";
        } else {
            echo "<p style='color: red'>✗ Nelze smazat: " . basename($file) . "</p>";
        }
    }
}

echo "<hr>";
echo "<p><strong>Instalační soubory byly odstraněny.</strong></p>";
echo "<a href='public/index.php'>Pokračovat do systému</a>";
