<?php
include_once __DIR__ . '/../app/includes/login.php';
require_login();
include_once __DIR__ . '/../app/includes/db_connect.php';

$klient_id = isset($_GET['klient_id']) ? intval($_GET['klient_id']) : 0;

if ($klient_id <= 0) {
    echo '<div class="text-center py-4 text-red-500">Neplatné ID klienta.</div>';
    exit;
}

// Získáme jméno klienta pro nadpis
$sql_klient = "SELECT jmeno FROM klienti WHERE id = ?";
$stmt_klient = $conn->prepare($sql_klient);
$stmt_klient->bind_param("i", $klient_id);
$stmt_klient->execute();
$result_klient = $stmt_klient->get_result();
$klient = $result_klient->fetch_assoc();

if (!$klient) {
    echo '<div class="text-center py-4 text-red-500">Klient nebyl nalezen.</div>';
    exit;
}

// Získáme smlouvy klienta - odstraníme sloupec stav z dotazu
$sql_smlouvy = "SELECT s.id, s.cislo_smlouvy, s.datum_sjednani, s.datum_platnosti, 
                        p.nazev as produkt_nazev, po.nazev as pojistovna_nazev 
                FROM smlouvy s 
                LEFT JOIN produkty p ON s.produkt_id = p.id 
                LEFT JOIN pojistovny po ON s.pojistovna_id = po.id 
                WHERE s.klient_id = ? 
                ORDER BY s.datum_sjednani DESC";
$stmt_smlouvy = $conn->prepare($sql_smlouvy);
$stmt_smlouvy->bind_param("i", $klient_id);
$stmt_smlouvy->execute();
$result_smlouvy = $stmt_smlouvy->get_result();

echo '<h4 class="text-md font-semibold mb-4">Smlouvy klienta: ' . htmlspecialchars($klient['jmeno'] ?? '') . '</h4>';

if ($result_smlouvy->num_rows > 0) {
    echo '<div class="overflow-x-auto">';
    echo '<table class="min-w-full divide-y divide-gray-200">';
    echo '<thead class="bg-gray-100">';
    echo '<tr>';
    echo '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Číslo smlouvy</th>';
    echo '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Produkt</th>';
    echo '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Pojišťovna</th>';
    echo '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Datum sjednání</th>';
    echo '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Datum platnosti</th>';
    echo '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Akce</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody class="bg-white divide-y divide-gray-200">';

    while ($smlouva = $result_smlouvy->fetch_assoc()) {
        echo '<tr>';
        echo '<td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($smlouva['cislo_smlouvy'] ?? '') . '</td>';
        echo '<td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($smlouva['produkt_nazev'] ?? '') . '</td>';
        echo '<td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($smlouva['pojistovna_nazev'] ?? '') . '</td>';
        echo '<td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($smlouva['datum_sjednani'] ?? '') . '</td>';
        echo '<td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($smlouva['datum_platnosti'] ?? '') . '</td>';
        echo '<td class="px-4 py-2 whitespace-nowrap text-sm font-medium">';
        // Použijeme vyhledávání podle čísla smlouvy jako dočasné řešení
        echo '<a href="smlouvy.php?search=' . urlencode($smlouva['cislo_smlouvy'] ?? '') . '" class="text-blue-600 hover:text-blue-900 mr-2" target="_blank">Otevřít</a>';
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
} else {
    echo '<div class="text-center py-4 text-gray-500">Tento klient nemá žádné smlouvy.</div>';
}

$conn->close();
