<?php
// Vložení login logiky a kontrola přihlášení
include_once __DIR__ . '/../app/includes/login.php';
require_login();

// Vložení hlavičky a připojení k databázi
include_once __DIR__ . '/../app/includes/header.php';
include_once __DIR__ . '/../app/includes/db_connect.php';

// Definice proměnných pro zprávy (úspěch/chyba)
$message = '';
$message_type = '';

// --- PHP LOGIKA PRO ÚPRAVU A SMAZÁNÍ SMLUV ---

// Zpracování smazání smlouvy
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    
    // Použití prepared statement pro bezpečné smazání
    $stmt_delete = $conn->prepare("DELETE FROM smlouvy WHERE id = ?");
    $stmt_delete->bind_param("i", $delete_id);

    if ($stmt_delete->execute()) {
        $message = "Smlouva byla úspěšně smazána.";
        $message_type = "success";
    } else {
        $message = "Chyba při mazání smlouvy: " . $stmt_delete->error;
        $message_type = "error";
    }
    $stmt_delete->close();
}

// Zpracování úpravy smlouvy
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
    $update_id = $_POST['update_id'];
    
    // Získání a ošetření dat z formuláře
    $klient_id = $conn->real_escape_string($_POST['klient_id']);
    $cislo_smlouvy = $conn->real_escape_string($_POST['cislo_smlouvy']);
    $produkt_id = $conn->real_escape_string($_POST['produkt_id']);
    $pojistovna_id = $conn->real_escape_string($_POST['pojistovna_id']);
    $datum_sjednani = $conn->real_escape_string($_POST['datum_sjednani']);
    $datum_platnosti = $conn->real_escape_string($_POST['datum_platnosti']);
    $zaznam_zeteo = isset($_POST['zaznam_zeteo']) ? 1 : 0;
    $poznamka = $conn->real_escape_string($_POST['poznamka']);

    $cesta_k_souboru = $_POST['stara_cesta_k_souboru'];

    // Zpracování nahrání nového souboru (pokud je nahrán)
    if (isset($_FILES['soubor']) && $_FILES['soubor']['error'] == UPLOAD_ERR_OK) {
        // ... (logika pro nahrání souboru je stejná jako v původním kódu)
        $file_tmp_path = $_FILES['soubor']['tmp_name'];
        $file_name = $_FILES['soubor']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['pdf'];

        if (in_array($file_ext, $allowed_ext)) {
            $new_file_name = uniqid('smlouva_', true) . '.' . $file_ext;
            $upload_path = __DIR__ . '/uploads/' . $new_file_name;
            if (move_uploaded_file($file_tmp_path, $upload_path)) {
                $cesta_k_souboru = 'uploads/' . $new_file_name;
            } else {
                $message = "Chyba při nahrávání souboru.";
                $message_type = "error";
            }
        } else {
            $message = "Nahráný soubor není povolený typ (pouze PDF).";
            $message_type = "error";
        }
    }
    
    // Zpracování dynamických podmínek a uložení do JSON
    $podminky_produktu = [];
    switch ($produkt_id) {
        case 1: // Životní pojištění
            switch ($pojistovna_id) {
                case 6: $podminky_produktu['typ'] = 'ČPP'; $podminky_produktu['podtyp'] = $_POST['podtyp_cpp'] ?? ''; break;
                case 7: $podminky_produktu['typ'] = 'Kooperativa'; $podminky_produktu['podtyp'] = $_POST['podtyp_kooperativa'] ?? ''; break;
                case 8: $podminky_produktu['typ'] = 'Allianz'; $podminky_produktu['podtyp'] = $_POST['podtyp_allianz'] ?? ''; break;
                case 9: $podminky_produktu['typ'] = 'Maxima'; $podminky_produktu['podtyp'] = $_POST['podtyp_maxima'] ?? ''; break;
            }
            $podminky_produktu['dip'] = isset($_POST['dip']) ? 'Ano' : 'Ne';
            $podminky_produktu['detske'] = isset($_POST['detske']) ? 'Ano' : 'Ne';
            break;
        case 2: // Cestovní pojištění
            $podminky_produktu['zacatek'] = $_POST['cestovni_zacatek'] ?? '';
            $podminky_produktu['konec'] = $_POST['cestovni_konec'] ?? '';
            break;
        case 6: // Autopojištění
            $podminky_produktu['pov'] = isset($_POST['pov']) ? 'Ano' : 'Ne';
            $podminky_produktu['hav'] = isset($_POST['hav']) ? 'Ano' : 'Ne';
            $podminky_produktu['dalsi_pripojisteni'] = $_POST['dalsi_pripojisteni'] ?? '';
            break;
        case 7: // Pojištění nemovitosti
            $podminky_produktu['domacnost'] = isset($_POST['nemovitost_domacnost']) ? 'Ano' : 'Ne';
            $podminky_produktu['stavba'] = isset($_POST['nemovitost_stavba']) ? 'Ano' : 'Ne';
            $podminky_produktu['odpovednost'] = isset($_POST['nemovitost_odpovednost']) ? 'Ano' : 'Ne';
            $podminky_produktu['asistence'] = isset($_POST['nemovitost_asistence']) ? 'Ano' : 'Ne';
            $podminky_produktu['nop'] = isset($_POST['nemovitost_nop']) ? 'Ano' : 'Ne';
            $podminky_produktu['nop_poznamka'] = $_POST['nemovitost_nop_poznamka'] ?? '';
            break;
    }
    $json_podminky = json_encode($podminky_produktu);

    if ($message_type !== 'error') {
        // Použití prepared statement pro bezpečný update
        $stmt_update = $conn->prepare("UPDATE smlouvy SET klient_id=?, cislo_smlouvy=?, produkt_id=?, pojistovna_id=?, datum_sjednani=?, datum_platnosti=?, zaznam_zeteo=?, poznamka=?, podminky_produktu=?, cesta_k_souboru=? WHERE id=?");
        $stmt_update->bind_param("isssssisssi", $klient_id, $cislo_smlouvy, $produkt_id, $pojistovna_id, $datum_sjednani, $datum_platnosti, $zaznam_zeteo, $poznamka, $json_podminky, $cesta_k_souboru, $update_id);

        if ($stmt_update->execute()) {
            $message = "Smlouva byla úspěšně aktualizována.";
            $message_type = "success";
            header("Location: smlouvy.php");
            exit;
        } else {
            $message = "Chyba při aktualizaci smlouvy: " . $stmt_update->error;
            $message_type = "error";
        }
        $stmt_update->close();
    }
}

// Zpracování dat z formuláře po odeslání (původní logika pro přidání smlouvy)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['update_id']) && !isset($_POST['delete_id'])) {
    $klient_id = $conn->real_escape_string($_POST['klient_id']);
    $cislo_smlouvy = $conn->real_escape_string($_POST['cislo_smlouvy']);
    $produkt_id = $conn->real_escape_string($_POST['produkt_id']);
    $pojistovna_id = $conn->real_escape_string($_POST['pojistovna_id']);
    $datum_sjednani = $conn->real_escape_string($_POST['datum_sjednani']);
    $datum_platnosti = $conn->real_escape_string($_POST['datum_platnosti']);
    $zaznam_zeteo = isset($_POST['zaznam_zeteo']) ? 1 : 0;
    $poznamka = $conn->real_escape_string($_POST['poznamka']);
    $cesta_k_souboru = '';

    $stmt_check = $conn->prepare("SELECT id FROM smlouvy WHERE cislo_smlouvy = ?");
    $stmt_check->bind_param("s", $cislo_smlouvy);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $message = "Chyba: Smlouva s číslem '" . htmlspecialchars($cislo_smlouvy) . "' již existuje v databázi.";
        $message_type = "error";
    } else {
        if (isset($_FILES['soubor']) && $_FILES['soubor']['error'] == UPLOAD_ERR_OK) {
            $file_tmp_path = $_FILES['soubor']['tmp_name'];
            $file_name = $_FILES['soubor']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_ext = ['pdf'];
            if (in_array($file_ext, $allowed_ext)) {
                $new_file_name = uniqid('smlouva_', true) . '.' . $file_ext;
                $upload_path = __DIR__ . '/uploads/' . $new_file_name;
                if (move_uploaded_file($file_tmp_path, $upload_path)) {
                    $cesta_k_souboru = 'uploads/' . $new_file_name;
                } else {
                    $message = "Chyba při přesouvání souboru na server.";
                    $message_type = "error";
                }
            } else {
                $message = "Nahráný soubor není povolený typ (povolené jsou pouze PDF).";
                $message_type = "error";
            }
        }
    }
    
    $podminky_produktu = [];
    switch ($produkt_id) {
        case 1: switch ($pojistovna_id) {
            case 6: $podminky_produktu['typ'] = 'ČPP'; $podminky_produktu['podtyp'] = $_POST['podtyp_cpp'] ?? ''; break;
            case 7: $podminky_produktu['typ'] = 'Kooperativa'; $podminky_produktu['podtyp'] = $_POST['podtyp_kooperativa'] ?? ''; break;
            case 8: $podminky_produktu['typ'] = 'Allianz'; $podminky_produktu['podtyp'] = $_POST['podtyp_allianz'] ?? ''; break;
            case 9: $podminky_produktu['typ'] = 'Maxima'; $podminky_produktu['podtyp'] = $_POST['podtyp_maxima'] ?? ''; break;
        }
        $podminky_produktu['dip'] = isset($_POST['dip']) ? 'Ano' : 'Ne';
        $podminky_produktu['detske'] = isset($_POST['detske']) ? 'Ano' : 'Ne';
        break;
        case 2: $podminky_produktu['zacatek'] = $_POST['cestovni_zacatek'] ?? ''; $podminky_produktu['konec'] = $_POST['cestovni_konec'] ?? ''; break;
        case 6: $podminky_produktu['pov'] = isset($_POST['pov']) ? 'Ano' : 'Ne'; $podminky_produktu['hav'] = isset($_POST['hav']) ? 'Ano' : 'Ne'; $podminky_produktu['dalsi_pripojisteni'] = $_POST['dalsi_pripojisteni'] ?? ''; break;
        case 7: $podminky_produktu['domacnost'] = isset($_POST['nemovitost_domacnost']) ? 'Ano' : 'Ne'; $podminky_produktu['stavba'] = isset($_POST['nemovitost_stavba']) ? 'Ano' : 'Ne'; $podminky_produktu['odpovednost'] = isset($_POST['nemovitost_odpovednost']) ? 'Ano' : 'Ne'; $podminky_produktu['asistence'] = isset($_POST['nemovitost_asistence']) ? 'Ano' : 'Ne'; $podminky_produktu['nop'] = isset($_POST['nemovitost_nop']) ? 'Ano' : 'Ne'; $podminky_produktu['nop_poznamka'] = $_POST['nemovitost_nop_poznamka'] ?? ''; break;
    }
    $json_podminky = json_encode($podminky_produktu);

    if ($message_type !== 'error') {
        $stmt_insert = $conn->prepare("INSERT INTO smlouvy (klient_id, cislo_smlouvy, produkt_id, pojistovna_id, datum_sjednani, datum_platnosti, zaznam_zeteo, poznamka, podminky_produktu, cesta_k_souboru) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_insert->bind_param("isssssisss", $klient_id, $cislo_smlouvy, $produkt_id, $pojistovna_id, $datum_sjednani, $datum_platnosti, $zaznam_zeteo, $poznamka, $json_podminky, $cesta_k_souboru);
        if ($stmt_insert->execute()) {
            $message = "Smlouva byla úspěšně přidána.";
            $message_type = "success";
            header("Location: smlouvy.php");
            exit;
        } else {
            $message = "Chyba při přidávání smlouvy: " . $stmt_insert->error;
            $message_type = "error";
        }
    }
}

// Získání seznamu klientů, produktů a pojišťoven
$klienti = [];
$produkty = [];
$pojistovny = [];
$sql_klienti = "SELECT id, jmeno FROM klienti ORDER BY jmeno ASC";
$result_klienti = $conn->query($sql_klienti);
if ($result_klienti->num_rows > 0) {
    while ($row = $result_klienti->fetch_assoc()) {
        $klienti[] = $row;
    }
}
$sql_produkty = "SELECT id, nazev FROM produkty ORDER BY nazev ASC";
$result_produkty = $conn->query($sql_produkty);
if ($result_produkty->num_rows > 0) {
    while ($row = $result_produkty->fetch_assoc()) {
        $produkty[] = $row;
    }
}
$sql_pojistovny = "SELECT id, nazev FROM pojistovny ORDER BY nazev ASC";
$result_pojistovny = $conn->query($sql_pojistovny);
if ($result_pojistovny->num_rows > 0) {
    while ($row = $result_pojistovny->fetch_assoc()) {
        $pojistovny[] = $row;
    }
}

// Získání seznamu všech smluv s informacemi o klientovi, produktu a pojišťovně
$sql_smlouvy = "
    SELECT
        smlouvy.id,
        smlouvy.cislo_smlouvy,
        smlouvy.cesta_k_souboru,
        smlouvy.datum_vytvoreni,
        smlouvy.datum_sjednani,
        smlouvy.datum_platnosti,
        smlouvy.zaznam_zeteo,
        smlouvy.poznamka,
        smlouvy.podminky_produktu,
        smlouvy.klient_id,
        smlouvy.produkt_id,
        smlouvy.pojistovna_id,
        klienti.jmeno AS jmeno_klienta,
        produkty.nazev AS nazev_produktu,
        pojistovny.nazev AS nazev_pojistovny
    FROM smlouvy
    LEFT JOIN klienti ON smlouvy.klient_id = klienti.id
    LEFT JOIN produkty ON smlouvy.produkt_id = produkty.id
    LEFT JOIN pojistovny ON smlouvy.pojistovna_id = pojistovny.id
    ORDER BY smlouvy.datum_vytvoreni DESC
";
$result_smlouvy = $conn->query($sql_smlouvy);
?>

<div class="container mx-auto mt-8 p-6 bg-white rounded-lg shadow-lg">
    <h1 class="text-3xl font-bold text-center text-gray-800 mb-6">Správa smluv</h1>

    <!-- Zobrazení zpráv (úspěch/chyba) -->
    <?php if ($message): ?>
        <div class="p-4 mb-4 rounded-md <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Formulář pro přidání smlouvy -->
    <div class="bg-gray-50 p-6 rounded-md border border-gray-200">
        <h2 class="text-xl font-semibold mb-4">Přidat novou smlouvu</h2>
        <form action="" method="post" enctype="multipart/form-data">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="mb-4">
                    <label for="klient_id" class="block text-sm font-medium text-gray-700">Klient</label>
                    <select id="klient_id" name="klient_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                        <?php if (empty($klienti)): ?>
                            <option value="" disabled selected>Nejsou k dispozici žádní klienti</option>
                        <?php else: ?>
                            <?php foreach ($klienti as $klient): ?>
                                <option value="<?php echo htmlspecialchars($klient['id']); ?>">
                                    <?php echo htmlspecialchars($klient['jmeno']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="cislo_smlouvy" class="block text-sm font-medium text-gray-700">Číslo smlouvy</label>
                    <input type="text" id="cislo_smlouvy" name="cislo_smlouvy" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                </div>
                <div class="mb-4">
                    <label for="produkt_id" class="block text-sm font-medium text-gray-700">Typ produktu</label>
                    <select id="produkt_id" name="produkt_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                        <?php if (empty($produkty)): ?>
                            <option value="" disabled selected>Nejsou k dispozici žádné produkty</option>
                        <?php else: ?>
                            <?php foreach ($produkty as $produkt): ?>
                                <option value="<?php echo htmlspecialchars($produkt['id']); ?>">
                                    <?php echo htmlspecialchars($produkt['nazev']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="pojistovna_id" class="block text-sm font-medium text-gray-700">Pojišťovna/Instituce</label>
                    <select id="pojistovna_id" name="pojistovna_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                        <?php if (empty($pojistovny)): ?>
                            <option value="" disabled selected>Nejsou k dispozici žádné pojišťovny</option>
                        <?php else: ?>
                            <?php foreach ($pojistovny as $pojistovna): ?>
                                <option value="<?php echo htmlspecialchars($pojistovna['id']); ?>">
                                    <?php echo htmlspecialchars($pojistovna['nazev']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="datum_sjednani" class="block text-sm font-medium text-gray-700">Datum sjednání</label>
                    <input type="date" id="datum_sjednani" name="datum_sjednani" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                </div>
                <div class="mb-4">
                    <label for="datum_platnosti" class="block text-sm font-medium text-gray-700">Datum počátku smlouvy</label>
                    <input type="date" id="datum_platnosti" name="datum_platnosti" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                </div>
            </div>

            <!-- Dynamicky vkládané podmínky -->
            <div id="dynamic-fields" class="mt-4 border-t pt-4 border-gray-200">
                <!-- Životní pojištění -->
                <div id="zivotni_pojisteni_fields" data-product-id="1" class="hidden">
                    <div id="zivotni_cpp_fields" data-pojistovna-id="6" class="hidden">
                        <div class="mb-4">
                            <label for="podtyp_cpp" class="block text-sm font-medium text-gray-700">Podtyp ČPP</label>
                            <select id="podtyp_cpp" name="podtyp_cpp" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                                <option value="">Vyberte podtyp</option>
                                <option value="RISK">RISK</option>
                                <option value="Life">Life</option>
                                <option value="Invest">Invest</option>
                            </select>
                        </div>
                    </div>
                    <div id="zivotni_kooperativa_fields" data-pojistovna-id="7" class="hidden">
                        <div class="mb-4">
                            <label for="podtyp_kooperativa" class="block text-sm font-medium text-gray-700">Podtyp Kooperativa</label>
                            <select id="podtyp_kooperativa" name="podtyp_kooperativa" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                                <option value="">Vyberte podtyp</option>
                                <option value="Koop_Moznost1">Koop_Možnost1</option>
                                <option value="Koop_Moznost2">Koop_Možnost2</option>
                            </select>
                        </div>
                    </div>
                    <div id="zivotni_allianz_fields" data-pojistovna-id="8" class="hidden">
                        <div class="mb-4">
                            <label for="podtyp_allianz" class="block text-sm font-medium text-gray-700">Podtyp Allianz</label>
                            <select id="podtyp_allianz" name="podtyp_allianz" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                                <option value="">Vyberte podtyp</option>
                                <option value="Allianz_Moznost">Allianz_Možnost</option>
                            </select>
                        </div>
                    </div>
                    <div id="zivotni_maxima_fields" data-pojistovna-id="9" class="hidden">
                        <div class="mb-4">
                            <label for="podtyp_maxima" class="block text-sm font-medium text-gray-700">Podtyp Maxima</label>
                            <select id="podtyp_maxima" name="podtyp_maxima" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                                <option value="">Vyberte podtyp</option>
                                <option value="Maxima_Moznost">Maxima_Možnost</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center mb-4">
                        <input type="checkbox" id="dip" name="dip" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="dip" class="ml-2 block text-sm text-gray-900">DIP</label>
                    </div>
                    <div class="flex items-center mb-4">
                        <input type="checkbox" id="detske" name="detske" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="detske" class="ml-2 block text-sm text-gray-900">Dětské pojištění</label>
                    </div>
                </div>

                <!-- Cestovní pojištění -->
                <div id="cestovni_pojisteni_fields" data-product-id="2" class="hidden">
                    <div class="mb-4">
                        <label for="cestovni_zacatek" class="block text-sm font-medium text-gray-700">Začátek pojištění</label>
                        <input type="date" id="cestovni_zacatek" name="cestovni_zacatek" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    </div>
                    <div class="mb-4">
                        <label for="cestovni_konec" class="block text-sm font-medium text-gray-700">Konec pojištění</label>
                        <input type="date" id="cestovni_konec" name="cestovni_konec" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    </div>
                </div>

                <!-- Autopojištění -->
                <div id="autopojisteni_fields" data-product-id="6" class="hidden">
                    <div class="flex items-center mb-4">
                        <input type="checkbox" id="pov" name="pov" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="pov" class="ml-2 block text-sm text-gray-900">POV</label>
                    </div>
                    <div class="flex items-center mb-4">
                        <input type="checkbox" id="hav" name="hav" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="hav" class="ml-2 block text-sm text-gray-900">HAV</label>
                    </div>
                    <div class="mb-4">
                        <label for="dalsi_pripojisteni" class="block text-sm font-medium text-gray-700">Další připojištění (text)</label>
                        <input type="text" id="dalsi_pripojisteni" name="dalsi_pripojisteni" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    </div>
                </div>

                <!-- Pojištění nemovitosti -->
                <div id="nemovitost_fields" data-product-id="7" class="hidden">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex items-center">
                            <input type="checkbox" id="nemovitost_domacnost" name="nemovitost_domacnost" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="nemovitost_domacnost" class="ml-2 block text-sm text-gray-900">Domácnost</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="nemovitost_stavba" name="nemovitost_stavba" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="nemovitost_stavba" class="ml-2 block text-sm text-gray-900">Stavba</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="nemovitost_odpovednost" name="nemovitost_odpovednost" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="nemovitost_odpovednost" class="ml-2 block text-sm text-gray-900">Odpovědnost</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="nemovitost_asistence" name="nemovitost_asistence" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="nemovitost_asistence" class="ml-2 block text-sm text-gray-900">Asistence</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="nemovitost_nop" name="nemovitost_nop" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="nemovitost_nop" class="ml-2 block text-sm text-gray-900">NOP</label>
                        </div>
                    </div>
                    <div class="mt-4 mb-4">
                        <label for="nemovitost_nop_poznamka" class="block text-sm font-medium text-gray-700">Poznámka k NOP</label>
                        <textarea id="nemovitost_nop_poznamka" name="nemovitost_nop_poznamka" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2"></textarea>
                    </div>
                </div>
            </div>
            <!-- Konec dynamických podmínek -->

            <div class="mt-4 flex items-center">
                <input type="checkbox" id="zaznam_zeteo" name="zaznam_zeteo" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                <label for="zaznam_zeteo" class="ml-2 block text-sm text-gray-900">
                    Záznam z jednání v Zeteo
                </label>
            </div>
            <div class="mb-4">
                <label for="soubor" class="block text-sm font-medium text-gray-700">Přiložit soubor (pouze PDF)</label>
                <input type="file" id="soubor" name="soubor" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            </div>
            <div class="md:col-span-2 mb-4">
                <label for="poznamka" class="block text-sm font-medium text-gray-700">Poznámka</label>
                <textarea id="poznamka" name="poznamka" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2"></textarea>
            </div>
            <button type="submit" class="w-full mt-6 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                Přidat smlouvu
            </button>
        </form>
    </div>

    <!-- Seznam smluv -->
    <div class="mt-8">
        <h2 class="text-xl font-semibold mb-4">Seznam smluv</h2>
        <?php if ($result_smlouvy->num_rows > 0): ?>
            <div class="overflow-x-auto bg-gray-50 rounded-md border border-gray-200 shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Klient</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Číslo smlouvy</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produkt</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pojišťovna</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sjednáno</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Počátek platnosti</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Specifika</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Zeteo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Soubor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Poznámka</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vytvořeno</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Akce</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while ($row = $result_smlouvy->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-100 transition-colors" data-id="<?php echo htmlspecialchars($row['id']); ?>" data-klient-id="<?php echo htmlspecialchars($row['klient_id']); ?>" data-cislo-smlouvy="<?php echo htmlspecialchars($row['cislo_smlouvy']); ?>" data-produkt-id="<?php echo htmlspecialchars($row['produkt_id']); ?>" data-pojistovna-id="<?php echo htmlspecialchars($row['pojistovna_id']); ?>" data-datum-sjednani="<?php echo htmlspecialchars($row['datum_sjednani']); ?>" data-datum-platnosti="<?php echo htmlspecialchars($row['datum_platnosti']); ?>" data-zaznam-zeteo="<?php echo htmlspecialchars($row['zaznam_zeteo']); ?>" data-poznamka="<?php echo htmlspecialchars($row['poznamka']); ?>" data-podminky-produktu='<?php echo htmlspecialchars($row['podminky_produktu']); ?>' data-cesta-k-souboru="<?php echo htmlspecialchars($row['cesta_k_souboru']); ?>">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['id']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <a href="klient_detail.php?id=<?php echo htmlspecialchars($row['klient_id']); ?>" class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                        <?php echo htmlspecialchars($row['jmeno_klienta']); ?>
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['cislo_smlouvy']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['nazev_produktu']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['nazev_pojistovny']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['datum_sjednani']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['datum_platnosti']); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?php
                                    if ($row['podminky_produktu']) {
                                        $podminky = json_decode($row['podminky_produktu'], true);
                                        foreach ($podminky as $klic => $hodnota) {
                                            echo '<strong>' . htmlspecialchars($klic) . ':</strong> ' . htmlspecialchars($hodnota) . '<br>';
                                        }
                                    }
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                    <?php echo $row['zaznam_zeteo'] ? '&#x2714;' : '&#x2718;'; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php if (!empty($row['cesta_k_souboru'])): ?>
                                        <a href="<?php echo htmlspecialchars($row['cesta_k_souboru']); ?>" target="_blank" class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                            Stáhnout PDF
                                        </a>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['poznamka']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('d.m.Y', strtotime($row['datum_vytvoreni'])); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button class="edit-btn text-indigo-600 hover:text-indigo-900 transition-colors duration-200" title="Upravit">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                        </svg>
                                        Upravit
                                    </button>
                                    <form method="post" action="smlouvy.php" class="inline-block delete-form ml-2" data-confirm="Opravdu chcete smazat tuto smlouvu s ID: <?php echo htmlspecialchars($row['id']); ?>?">
                                        <input type="hidden" name="delete_id" value="<?php echo htmlspecialchars($row['id']); ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-900 transition-colors duration-200" title="Smazat">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                            Smazat
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-gray-500">Zatím nejsou přidány žádné smlouvy.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Modální okno pro úpravu smlouvy -->
<div id="edit-modal" class="hidden fixed inset-0 z-50 overflow-auto bg-gray-800 bg-opacity-75 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 w-full max-w-2xl mx-auto my-12 shadow-lg">
        <div class="flex justify-between items-center pb-3">
            <h2 class="text-2xl font-semibold text-gray-800">Upravit smlouvu</h2>
            <button id="close-modal-btn" class="text-gray-500 hover:text-gray-700">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form action="" method="post" enctype="multipart/form-data">
            <input type="hidden" name="update_id" id="edit_id">
            <input type="hidden" name="stara_cesta_k_souboru" id="stara_cesta_k_souboru">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="mb-4">
                    <label for="edit_klient_id" class="block text-sm font-medium text-gray-700">Klient</label>
                    <select id="edit_klient_id" name="klient_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                        <?php foreach ($klienti as $klient): ?>
                            <option value="<?php echo htmlspecialchars($klient['id']); ?>"><?php echo htmlspecialchars($klient['jmeno']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="edit_cislo_smlouvy" class="block text-sm font-medium text-gray-700">Číslo smlouvy</label>
                    <input type="text" id="edit_cislo_smlouvy" name="cislo_smlouvy" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                </div>
                <div class="mb-4">
                    <label for="edit_produkt_id" class="block text-sm font-medium text-gray-700">Typ produktu</label>
                    <select id="edit_produkt_id" name="produkt_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                        <?php foreach ($produkty as $produkt): ?>
                            <option value="<?php echo htmlspecialchars($produkt['id']); ?>"><?php echo htmlspecialchars($produkt['nazev']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="edit_pojistovna_id" class="block text-sm font-medium text-gray-700">Pojišťovna/Instituce</label>
                    <select id="edit_pojistovna_id" name="pojistovna_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                        <?php foreach ($pojistovny as $pojistovna): ?>
                            <option value="<?php echo htmlspecialchars($pojistovna['id']); ?>"><?php echo htmlspecialchars($pojistovna['nazev']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="edit_datum_sjednani" class="block text-sm font-medium text-gray-700">Datum sjednání</label>
                    <input type="date" id="edit_datum_sjednani" name="datum_sjednani" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                </div>
                <div class="mb-4">
                    <label for="edit_datum_platnosti" class="block text-sm font-medium text-gray-700">Datum počátku smlouvy</label>
                    <input type="date" id="edit_datum_platnosti" name="datum_platnosti" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                </div>
            </div>

            <!-- Dynamické podmínky pro editaci -->
            <div id="edit-dynamic-fields" class="mt-4 border-t pt-4 border-gray-200">
                 <!-- Životní pojištění -->
                 <div id="edit_zivotni_pojisteni_fields" data-product-id="1" class="hidden">
                    <div id="edit_zivotni_cpp_fields" data-pojistovna-id="6" class="hidden">
                        <div class="mb-4">
                            <label for="edit_podtyp_cpp" class="block text-sm font-medium text-gray-700">Podtyp ČPP</label>
                            <select id="edit_podtyp_cpp" name="podtyp_cpp" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                                <option value="">Vyberte podtyp</option>
                                <option value="RISK">RISK</option>
                                <option value="Life">Life</option>
                                <option value="Invest">Invest</option>
                            </select>
                        </div>
                    </div>
                    <div id="edit_zivotni_kooperativa_fields" data-pojistovna-id="7" class="hidden">
                        <div class="mb-4">
                            <label for="edit_podtyp_kooperativa" class="block text-sm font-medium text-gray-700">Podtyp Kooperativa</label>
                            <select id="edit_podtyp_kooperativa" name="podtyp_kooperativa" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                                <option value="">Vyberte podtyp</option>
                                <option value="Koop_Moznost1">Koop_Možnost1</option>
                                <option value="Koop_Moznost2">Koop_Možnost2</option>
                            </select>
                        </div>
                    </div>
                    <div id="edit_zivotni_allianz_fields" data-pojistovna-id="8" class="hidden">
                        <div class="mb-4">
                            <label for="edit_podtyp_allianz" class="block text-sm font-medium text-gray-700">Podtyp Allianz</label>
                            <select id="edit_podtyp_allianz" name="podtyp_allianz" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                                <option value="">Vyberte podtyp</option>
                                <option value="Allianz_Moznost">Allianz_Možnost</option>
                            </select>
                        </div>
                    </div>
                    <div id="edit_zivotni_maxima_fields" data-pojistovna-id="9" class="hidden">
                        <div class="mb-4">
                            <label for="edit_podtyp_maxima" class="block text-sm font-medium text-gray-700">Podtyp Maxima</label>
                            <select id="edit_podtyp_maxima" name="podtyp_maxima" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                                <option value="">Vyberte podtyp</option>
                                <option value="Maxima_Moznost">Maxima_Možnost</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center mb-4">
                        <input type="checkbox" id="edit_dip" name="dip" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="edit_dip" class="ml-2 block text-sm text-gray-900">DIP</label>
                    </div>
                    <div class="flex items-center mb-4">
                        <input type="checkbox" id="edit_detske" name="detske" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="edit_detske" class="ml-2 block text-sm text-gray-900">Dětské pojištění</label>
                    </div>
                </div>

                <!-- Cestovní pojištění -->
                <div id="edit_cestovni_pojisteni_fields" data-product-id="2" class="hidden">
                    <div class="mb-4">
                        <label for="edit_cestovni_zacatek" class="block text-sm font-medium text-gray-700">Začátek pojištění</label>
                        <input type="date" id="edit_cestovni_zacatek" name="cestovni_zacatek" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    </div>
                    <div class="mb-4">
                        <label for="edit_cestovni_konec" class="block text-sm font-medium text-gray-700">Konec pojištění</label>
                        <input type="date" id="edit_cestovni_konec" name="cestovni_konec" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    </div>
                </div>

                <!-- Autopojištění -->
                <div id="edit_autopojisteni_fields" data-product-id="6" class="hidden">
                    <div class="flex items-center mb-4">
                        <input type="checkbox" id="edit_pov" name="pov" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="edit_pov" class="ml-2 block text-sm text-gray-900">POV</label>
                    </div>
                    <div class="flex items-center mb-4">
                        <input type="checkbox" id="edit_hav" name="hav" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="edit_hav" class="ml-2 block text-sm text-gray-900">HAV</label>
                    </div>
                    <div class="mb-4">
                        <label for="edit_dalsi_pripojisteni" class="block text-sm font-medium text-gray-700">Další připojištění (text)</label>
                        <input type="text" id="edit_dalsi_pripojisteni" name="dalsi_pripojisteni" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    </div>
                </div>

                <!-- Pojištění nemovitosti -->
                <div id="edit_nemovitost_fields" data-product-id="7" class="hidden">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex items-center">
                            <input type="checkbox" id="edit_nemovitost_domacnost" name="nemovitost_domacnost" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="edit_nemovitost_domacnost" class="ml-2 block text-sm text-gray-900">Domácnost</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="edit_nemovitost_stavba" name="nemovitost_stavba" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="edit_nemovitost_stavba" class="ml-2 block text-sm text-gray-900">Stavba</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="edit_nemovitost_odpovednost" name="nemovitost_odpovednost" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="edit_nemovitost_odpovednost" class="ml-2 block text-sm text-gray-900">Odpovědnost</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="edit_nemovitost_asistence" name="nemovitost_asistence" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="edit_nemovitost_asistence" class="ml-2 block text-sm text-gray-900">Asistence</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="edit_nemovitost_nop" name="nemovitost_nop" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="edit_nemovitost_nop" class="ml-2 block text-sm text-gray-900">NOP</label>
                        </div>
                    </div>
                    <div class="mt-4 mb-4">
                        <label for="edit_nemovitost_nop_poznamka" class="block text-sm font-medium text-gray-700">Poznámka k NOP</label>
                        <textarea id="edit_nemovitost_nop_poznamka" name="nemovitost_nop_poznamka" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2"></textarea>
                    </div>
                </div>
            </div>

            <div class="mt-4 flex items-center">
                <input type="checkbox" id="edit_zaznam_zeteo" name="zaznam_zeteo" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                <label for="edit_zaznam_zeteo" class="ml-2 block text-sm text-gray-900">Záznam z jednání v Zeteo</label>
            </div>
            <div class="mb-4">
                <label for="edit_soubor" class="block text-sm font-medium text-gray-700">Přiložit nový soubor (pouze PDF)</label>
                <input type="file" id="edit_soubor" name="soubor" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            </div>
            <div class="md:col-span-2 mb-4">
                <label for="edit_poznamka" class="block text-sm font-medium text-gray-700">Poznámka</label>
                <textarea id="edit_poznamka" name="poznamka" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2"></textarea>
            </div>
            <button type="submit" class="w-full mt-6 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                Uložit změny
            </button>
        </form>
    </div>
</div>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        const produktSelect = document.getElementById('produkt_id');
        const pojistovnaSelect = document.getElementById('pojistovna_id');
        const dynamicFieldsContainers = document.querySelectorAll('#dynamic-fields > div');

        const editModal = document.getElementById('edit-modal');
        const closeEditModalBtn = document.getElementById('close-modal-btn');
        const editButtons = document.querySelectorAll('.edit-btn');
        
        const editProduktSelect = document.getElementById('edit_produkt_id');
        const editPojistovnaSelect = document.getElementById('edit_pojistovna_id');
        const editDynamicFieldsContainers = document.querySelectorAll('#edit-dynamic-fields > div');

        function updateDynamicFields(produktId, pojistovnaId, containers) {
            containers.forEach(container => {
                container.classList.add('hidden');
                const nestedDivs = container.querySelectorAll('div[data-pojistovna-id]');
                nestedDivs.forEach(nestedDiv => {
                    nestedDiv.classList.add('hidden');
                });
            });

            const productContainer = document.querySelector(`#${containers[0].parentNode.id} > div[data-product-id="${produktId}"]`);
            if (productContainer) {
                productContainer.classList.remove('hidden');
                if (produktId === '1') {
                    const pojistovnaNestedDiv = productContainer.querySelector(`div[data-pojistovna-id="${pojistovnaId}"]`);
                    if (pojistovnaNestedDiv) {
                        pojistovnaNestedDiv.classList.remove('hidden');
                    }
                }
            }
        }

        // Původní posluchače pro přidávací formulář
        if (produktSelect && pojistovnaSelect) {
            produktSelect.addEventListener('change', () => updateDynamicFields(produktSelect.value, pojistovnaSelect.value, dynamicFieldsContainers));
            pojistovnaSelect.addEventListener('change', () => updateDynamicFields(produktSelect.value, pojistovnaSelect.value, dynamicFieldsContainers));
            updateDynamicFields(produktSelect.value, pojistovnaSelect.value, dynamicFieldsContainers);
        }

        // Posluchače pro editaci
        editProduktSelect.addEventListener('change', () => updateDynamicFields(editProduktSelect.value, editPojistovnaSelect.value, editDynamicFieldsContainers));
        editPojistovnaSelect.addEventListener('change', () => updateDynamicFields(editProduktSelect.value, editPojistovnaSelect.value, editDynamicFieldsContainers));
        
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const row = this.closest('tr');
                const data = row.dataset;
                
                // Vyplnění základních polí
                document.getElementById('edit_id').value = data.id;
                document.getElementById('edit_klient_id').value = data.klientId;
                document.getElementById('edit_cislo_smlouvy').value = data.cisloSmlouvy;
                document.getElementById('edit_produkt_id').value = data.produktId;
                document.getElementById('edit_pojistovna_id').value = data.pojistovnaId;
                document.getElementById('edit_datum_sjednani').value = data.datumSjednani;
                document.getElementById('edit_datum_platnosti').value = data.datumPlatnosti;
                document.getElementById('edit_zaznam_zeteo').checked = data.zaznamZeteo == 1;
                document.getElementById('edit_poznamka').value = data.poznamka;
                document.getElementById('stara_cesta_k_souboru').value = data.cestaKSouboru;

                // Vyplnění dynamických polí
                const podminky = JSON.parse(data.podminkyProduktu || '{}');
                
                // Uklidit všechny checkboxy a inputy
                document.querySelectorAll('#edit-dynamic-fields input[type="text"]').forEach(input => input.value = '');
                document.querySelectorAll('#edit-dynamic-fields input[type="date"]').forEach(input => input.value = '');
                document.querySelectorAll('#edit-dynamic-fields input[type="checkbox"]').forEach(checkbox => checkbox.checked = false);
                document.querySelectorAll('#edit-dynamic-fields select').forEach(select => select.value = '');

                // Nastavení hodnot z JSONu
                if (podminky) {
                    for (const key in podminky) {
                        const input = document.getElementById(`edit_${key}`);
                        if (input) {
                            if (input.type === 'checkbox') {
                                input.checked = podminky[key] === 'Ano';
                            } else {
                                input.value = podminky[key];
                            }
                        }
                        const select = document.getElementById(`edit_${key}`);
                        if (select) {
                            select.value = podminky[key];
                        }
                    }
                }
                
                // Aktualizace dynamických polí v modalu
                updateDynamicFields(data.produktId, data.pojistovnaId, editDynamicFieldsContainers);
                
                // Zobrazení modalu
                editModal.classList.remove('hidden');
            });
        });

        closeEditModalBtn.addEventListener('click', () => {
            editModal.classList.add('hidden');
        });

        // Posluchač pro smazání s custom modal
        document.querySelectorAll('.delete-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const confirmMessage = this.dataset.confirm;
                if (!window.confirm(confirmMessage)) {
                    e.preventDefault();
                }
            });
        });
    });
</script>
<?php
// Uzavření připojení k databázi
$conn->close();

// Vložení patičky
include_once __DIR__ . '/../app/includes/footer.php';
?>
