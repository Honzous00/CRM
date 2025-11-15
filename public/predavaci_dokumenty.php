<?php
include_once __DIR__ . '/../app/includes/login.php';
require_login();
include_once __DIR__ . '/../app/includes/header.php';
include_once __DIR__ . '/../app/includes/db_connect.php';

$selected_id = $_GET['id'] ?? '';

// Zpracování odstranění souboru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $predavaci_dokument_id = $_POST['predavaci_dokument_id'];

    // Nejprve získáme cestu k souboru z databáze
    $sql_select = "SELECT cesta_k_souboru FROM predavaci_dokumenty WHERE id = ?";
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param("i", $predavaci_dokument_id);
    $stmt_select->execute();
    $result = $stmt_select->get_result();
    $dokument = $result->fetch_assoc();
    $stmt_select->close();

    if ($dokument && !empty($dokument['cesta_k_souboru'])) {
        $file_path = realpath(__DIR__ . '/../public/' . $dokument['cesta_k_souboru']);

        // Smazání souboru z disku
        if (file_exists($file_path)) {
            if (unlink($file_path)) {
                // Aktualizace databáze - nastavíme cestu na NULL
                $sql_update = "UPDATE predavaci_dokumenty SET cesta_k_souboru = NULL WHERE id = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("i", $predavaci_dokument_id);

                if ($stmt_update->execute()) {
                    $message = "Soubor byl úspěšně odstraněn!";
                    $message_type = "success";
                    $current_dokument['cesta_k_souboru'] = null;
                } else {
                    $message = "Chyba při aktualizaci databáze: " . $stmt_update->error;
                    $message_type = "error";
                }
                $stmt_update->close();
            } else {
                $message = "Chyba při mazání souboru z disku";
                $message_type = "error";
            }
        } else {
            // Soubor neexistuje na disku, ale smažeme záznam z databáze
            $sql_update = "UPDATE predavaci_dokumenty SET cesta_k_souboru = NULL WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("i", $predavaci_dokument_id);

            if ($stmt_update->execute()) {
                $message = "Záznam o souboru byl odstraněn z databáze (soubor již neexistoval na disku).";
                $message_type = "success";
                $current_dokument['cesta_k_souboru'] = null;
            } else {
                $message = "Chyba při aktualizaci databáze: " . $stmt_update->error;
                $message_type = "error";
            }
            $stmt_update->close();
        }
    } else {
        $message = "Pro tento dokument nebyl nalezen žádný soubor k odstranění.";
        $message_type = "error";
    }
}

// Zpracování nahrání souboru pro předávací dokument
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['predavaci_dokument_id']) && (!isset($_POST['action']) || $_POST['action'] !== 'delete')) {
    $predavaci_dokument_id = $_POST['predavaci_dokument_id'];

    // Zpracování souboru
    if (isset($_FILES['soubor'])) {
        $file_error = $_FILES['soubor']['error'];

        if ($file_error == UPLOAD_ERR_OK) {
            $file_tmp_path = $_FILES['soubor']['tmp_name'];
            $file_name = $_FILES['soubor']['name'];
            $file_size = $_FILES['soubor']['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            // Povolené typy souborů
            $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'doc', 'docx'];
            if (!in_array($file_ext, $allowed_extensions)) {
                $message = "Nepodporovaný typ souboru. Povolené typy: " . implode(', ', $allowed_extensions);
                $message_type = "error";
            } else {
                // Nejprve zkontrolujeme, zda již existuje soubor pro tento dokument
                $sql_check = "SELECT cesta_k_souboru FROM predavaci_dokumenty WHERE id = ?";
                $stmt_check = $conn->prepare($sql_check);
                $stmt_check->bind_param("i", $predavaci_dokument_id);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();
                $existing_file = $result_check->fetch_assoc();
                $stmt_check->close();

                // Pokud již existuje soubor, vrátíme chybu
                if ($existing_file && !empty($existing_file['cesta_k_souboru'])) {
                    $message = "Chyba: Pro tento dokument již existuje nahraný soubor. Nejprve odstraňte stávající soubor, poté můžete nahrát nový.";
                    $message_type = "error";
                } else {
                    $new_file_name = uniqid('predavaci_dokument_', true) . '.' . $file_ext;

                    // Cesta pro upload
                    $project_root = realpath(__DIR__ . '/../');
                    $upload_dir = $project_root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'predavaci_dokumenty' . DIRECTORY_SEPARATOR;

                    // Vytvoříme složku pokud neexistuje
                    if (!is_dir($upload_dir)) {
                        if (!mkdir($upload_dir, 0755, true)) {
                            $message = "Chyba: Nelze vytvořit složku pro upload.";
                            $message_type = "error";
                        }
                    }

                    if (is_dir($upload_dir) && is_writable($upload_dir)) {
                        $upload_path = $upload_dir . $new_file_name;

                        if (move_uploaded_file($file_tmp_path, $upload_path)) {
                            // Kontrola existence souboru
                            if (file_exists($upload_path)) {
                                $relative_path = 'uploads/predavaci_dokumenty/' . $new_file_name;

                                // Aktualizace databáze
                                $sql_update = "UPDATE predavaci_dokumenty SET cesta_k_souboru = ? WHERE id = ?";
                                $stmt_update = $conn->prepare($sql_update);
                                $stmt_update->bind_param("si", $relative_path, $predavaci_dokument_id);

                                if ($stmt_update->execute()) {
                                    $message = "Soubor byl úspěšně nahrán!";
                                    $message_type = "success";
                                    $current_dokument['cesta_k_souboru'] = $relative_path;
                                } else {
                                    $message = "Chyba při ukládání do databáze: " . $stmt_update->error;
                                    $message_type = "error";
                                }
                                $stmt_update->close();
                            } else {
                                $message = "Chyba: Soubor byl nahrán, ale neexistuje v cílové cestě!";
                                $message_type = "error";
                            }
                        } else {
                            $message = "Chyba při nahrávání souboru";
                            $message_type = "error";
                        }
                    } else {
                        $message = "Složka pro upload neexistuje nebo není zapisovatelná.";
                        $message_type = "error";
                    }
                }
            }
        } else {
            $upload_errors = [
                UPLOAD_ERR_INI_SIZE => 'Soubor překračuje maximální povolenou velikost',
                UPLOAD_ERR_FORM_SIZE => 'Soubor překračuje maximální velikost definovanou ve formuláři',
                UPLOAD_ERR_PARTIAL => 'Soubor byl nahrán pouze částečně',
                UPLOAD_ERR_NO_FILE => 'Nebyl nahrán žádný soubor',
                UPLOAD_ERR_NO_TMP_DIR => 'Chybí dočasná složka',
                UPLOAD_ERR_CANT_WRITE => 'Chyba zápisu na disk',
                UPLOAD_ERR_EXTENSION => 'Nahrávání zastaveno rozšířením PHP'
            ];
            $message = "Chyba při nahrávání: " . ($upload_errors[$file_error] ?? 'Neznámá chyba');
            $message_type = "error";
        }
    } else {
        $message = "Nebyl vybrán žádný soubor.";
        $message_type = "error";
    }
}

// Získání seznamu předávacích dokumentů
$sql_dokumenty = "SELECT * FROM predavaci_dokumenty ORDER BY cislo";
$result_dokumenty = $conn->query($sql_dokumenty);
$dokumenty = [];
if ($result_dokumenty->num_rows > 0) {
    while ($row = $result_dokumenty->fetch_assoc()) {
        $dokumenty[] = $row;
    }
}

// Pokud je vybrán konkrétní dokument, načteme smlouvy
$smlouvy = [];
$current_dokument = null;
if (!empty($selected_id)) {
    $sql_dokument = "SELECT * FROM predavaci_dokumenty WHERE id = ?";
    $stmt = $conn->prepare($sql_dokument);
    $stmt->bind_param("i", $selected_id);
    $stmt->execute();
    $current_dokument = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($current_dokument) {
        $sql_smlouvy = "
            SELECT 
                smlouvy.*,
                klienti.jmeno AS jmeno_klienta,
                produkty.nazev AS nazev_produktu,
                pojistovny.nazev AS nazev_pojistovny
            FROM smlouvy
            LEFT JOIN klienti ON smlouvy.klient_id = klienti.id
            LEFT JOIN produkty ON smlouvy.produkt_id = produkty.id
            LEFT JOIN pojistovny ON smlouvy.pojistovna_id = pojistovny.id
            WHERE smlouvy.predavaci_dokument_id = ?
            ORDER BY smlouvy.datum_sjednani DESC
        ";
        $stmt = $conn->prepare($sql_smlouvy);
        $stmt->bind_param("i", $selected_id);
        $stmt->execute();
        $result_smlouvy = $stmt->get_result();
        if ($result_smlouvy->num_rows > 0) {
            while ($row = $result_smlouvy->fetch_assoc()) {
                $smlouvy[] = $row;
            }
        }
        $stmt->close();
    }
}
?>

<div class="container mx-auto mt-8 p-6 bg-white rounded-lg shadow-lg">
    <h1 class="text-3xl font-bold text-center text-gray-800 mb-6">Předávací dokumenty</h1>

    <?php if (empty($selected_id)): ?>
        <!-- Seznam předávacích dokumentů -->
        <div class="mb-6">
            <p class="text-gray-600 mb-4">Klikněte na číslo předávacího dokumentu pro zobrazení příslušných smluv.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($dokumenty as $dokument): ?>
                <a href="?id=<?php echo $dokument['id']; ?>"
                    class="block p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors border border-blue-200">
                    <h2 class="text-lg font-semibold text-blue-800"><?php echo htmlspecialchars($dokument['cislo']); ?></h2>
                    <p class="text-sm text-blue-600 mt-1">Klikněte pro detail</p>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($dokumenty)): ?>
            <div class="text-center py-8">
                <p class="text-gray-500 text-lg">Žádné předávací dokumenty nebyly nalezeny.</p>
                <p class="text-gray-400 mt-2">Předávací dokumenty se zobrazí po jejich zadání u provizí.</p>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <!-- Detail předávacího dokumentu -->
        <div class="mb-6">
            <a href="predavaci_dokumenty.php" class="inline-flex items-center text-blue-600 hover:text-blue-800 mb-4 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Zpět na seznam předávacích dokumentů
            </a>
            <h2 class="text-2xl font-semibold text-gray-800">Předávací dokument: <span class="text-blue-600"><?php echo htmlspecialchars($current_dokument['cislo']); ?></span></h2>
            <p class="text-gray-600 mt-2">Počet smluv: <span class="font-semibold"><?php echo count($smlouvy); ?></span></p>
        </div>

        <!-- Zobrazení zpráv (úspěch/chyba) -->
        <?php if (isset($message)): ?>
            <div class="p-4 mb-4 rounded-md <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Sekce pro soubor předávacího dokumentu -->
        <div class="mt-6 border-t pt-6">
            <h3 class="text-lg font-semibold mb-4">Dokument předávacího protokolu</h3>

            <?php if (!empty($current_dokument['cesta_k_souboru'])): ?>
                <div class="mb-4">
                    <p class="text-gray-700 mb-2">Aktuálně nahraný dokument:</p>
                    <div class="flex items-center space-x-4">
                        <a href="<?php echo htmlspecialchars($current_dokument['cesta_k_souboru']); ?>"
                            target="_blank"
                            class="inline-flex items-center text-blue-600 hover:text-blue-800 bg-blue-50 px-4 py-2 rounded-lg border border-blue-200">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Otevřít dokument
                        </a>
                        <form action="" method="post" class="inline">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="predavaci_dokument_id" value="<?php echo $current_dokument['id']; ?>">
                            <button type="submit"
                                onclick="return confirm('Opravdu chcete odstranit tento dokument? Tato akce je nevratná.')"
                                class="inline-flex items-center text-red-600 hover:text-red-800 bg-red-50 px-4 py-2 rounded-lg border border-red-200 hover:bg-red-100 transition-colors">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                Odstranit dokument
                            </button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-gray-500 mb-4">Dosud nebyl nahrán žádný dokument.</p>

                <form action="" method="post" enctype="multipart/form-data" class="mt-4">
                    <input type="hidden" name="predavaci_dokument_id" value="<?php echo $current_dokument['id']; ?>">
                    <div class="mb-4">
                        <label for="soubor" class="block text-sm font-medium text-gray-700">Nahrát dokument</label>
                        <input type="file" id="soubor" name="soubor" required
                            class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <p class="text-xs text-gray-500 mt-1">Podporované formáty: PDF, JPG, JPEG, PNG, GIF, DOC, DOCX</p>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                        Nahrát dokument
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <!-- Tabulka smluv -->
        <?php if (!empty($smlouvy)): ?>
            <div class="overflow-x-auto bg-white rounded-lg border border-gray-200 shadow-sm mt-6">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Klient</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Číslo smlouvy</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produkt</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pojišťovna</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sjednáno</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Platnost od</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Soubor</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($smlouvy as $smlouva): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($smlouva['jmeno_klienta']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <a href="smlouvy.php?search=<?php echo urlencode($smlouva['cislo_smlouvy']); ?>" class="text-blue-600 hover:text-blue-800">
                                        <?php echo htmlspecialchars($smlouva['cislo_smlouvy']); ?>
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($smlouva['nazev_produktu']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($smlouva['nazev_pojistovny']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($smlouva['datum_sjednani']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($smlouva['datum_platnosti']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php if (!empty($smlouva['cesta_k_souboru'])): ?>
                                        <a href="<?php echo htmlspecialchars($smlouva['cesta_k_souboru']); ?>" target="_blank" class="text-blue-600 hover:text-blue-800 inline-flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            Otevřít
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-8">
                <p class="text-gray-500 text-lg">Pro tento předávací dokument nebyly nalezeny žádné smlouvy.</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
$conn->close();
include_once __DIR__ . '/../app/includes/footer.php';
?>