<?php
// Vložení nezbytných souborů
include_once __DIR__ . '/../app/includes/login.php';
require_login();

include_once __DIR__ . '/../app/includes/db_connect.php';
include_once __DIR__ . '/../app/models/smlouvy_model.php';
include_once __DIR__ . '/../app/models/provize_model.php';
include_once __DIR__ . '/../app/models/dokumenty_model.php';
include_once __DIR__ . '/../app/views/smlouvy_view.php';

// Zkontrolujeme, zda bylo v URL předáno ID smlouvy
if (!isset($_GET['smlouva_id']) || !is_numeric($_GET['smlouva_id'])) {
    http_response_code(400);
    if (isset($_GET['format']) && $_GET['format'] === 'html') {
        echo '<p class="text-red-500 text-center">Chybí nebo je neplatné ID smlouvy.</p>';
    } else {
        echo json_encode(["error" => "Chybí nebo je neplatné ID smlouvy."]);
    }
    exit;
}

$smlouva_id = (int) $_GET['smlouva_id'];
$format = $_GET['format'] ?? 'json';   // výchozí je JSON

// Načtení dat pomocí modelů
$smlouvyModel = new SmlouvyModel($conn);
$provizeModel = new ProvizeModel($conn);

$smlouva = $smlouvyModel->getSmlouvaById($smlouva_id);

if (!$smlouva) {
    http_response_code(404);
    if ($format === 'html') {
        echo '<p class="text-red-500 text-center">Smlouva nebyla nalezena.</p>';
    } else {
        echo json_encode(["error" => "Smlouva nenalezena."]);
    }
    exit;
}

// Pro HTML výstup – detail včetně provizí a dokumentů
if ($format === 'html') {
    $provize = $provizeModel->getProvizeBySmlouva($smlouva_id);
    $totalProvize = $provizeModel->getTotalProvizeBySmlouva($smlouva_id);

    if (function_exists('displaySmlouvaDetail')) {
        displaySmlouvaDetail($smlouva, $provize, $totalProvize, $conn);
    } else {
        echo '<p class="text-red-500">Chyba: view funkce není dostupná.</p>';
    }
    exit;
}

// --- Jinak JSON (původní chování pro provize) ---
// Sestavíme pouze pole, které potřebuje formulář provize
$output = [
    'id' => $smlouva['id'],
    'cislo_smlouvy' => $smlouva['cislo_smlouvy'],
    'datum_sjednani' => $smlouva['datum_sjednani'],
    'datum_platnosti' => $smlouva['datum_platnosti'],
    'poznamka' => $smlouva['poznamka'],
    'jmeno_klienta' => $smlouva['jmeno_klienta'],
    'nazev_produktu' => $smlouva['nazev_produktu'],
    'nazev_pojistovny' => $smlouva['nazev_pojistovny']
];

header('Content-Type: application/json');
echo json_encode($output);
