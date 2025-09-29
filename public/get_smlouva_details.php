<?php
// Vložení souboru pro připojení k databázi
include_once __DIR__ . '/../app/includes/db_connect.php';

// Zkontrolujeme, zda bylo v URL předáno ID smlouvy
if (isset($_GET['smlouva_id'])) {
    // Zabezpečení vstupu
    $smlouva_id = $conn->real_escape_string($_GET['smlouva_id']);

    // SQL dotaz pro získání detailů smlouvy a souvisejících dat
    $sql_details = "
        SELECT
            smlouvy.id,
            smlouvy.cislo_smlouvy,
            smlouvy.datum_sjednani,
            smlouvy.datum_platnosti,
            smlouvy.poznamka,
            klienti.jmeno AS jmeno_klienta,
            produkty.nazev AS nazev_produktu,
            pojistovny.nazev AS nazev_pojistovny
        FROM smlouvy
        LEFT JOIN klienti ON smlouvy.klient_id = klienti.id
        LEFT JOIN produkty ON smlouvy.produkt_id = produkty.id
        LEFT JOIN pojistovny ON smlouvy.pojistovna_id = pojistovny.id
        WHERE smlouvy.id = '$smlouva_id'
    ";

    $result_details = $conn->query($sql_details);

    if ($result_details->num_rows > 0) {
        // Našli jsme data, vrátíme je jako JSON
        $smlouva_details = $result_details->fetch_assoc();
        header('Content-Type: application/json');
        echo json_encode($smlouva_details);
    } else {
        // Smlouva nebyla nalezena
        http_response_code(404);
        echo json_encode(["error" => "Smlouva nenalezena."]);
    }
} else {
    // ID nebylo předáno
    http_response_code(400);
    echo json_encode(["error" => "Chybí ID smlouvy."]);
}

// Uzavření připojení k databázi
$conn->close();
