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

// Zpracování přidání/úpravy provize
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'insert';
    $smlouva_id = $conn->real_escape_string($_POST['smlouva_id']);
    $datum_vyplaty = $conn->real_escape_string($_POST['datum_vyplaty']);
    $castka = $conn->real_escape_string($_POST['castka']);
    $stornovana = isset($_POST['stornovana']) ? 1 : 0;
    $storno_rezerva = $conn->real_escape_string($_POST['storno_rezerva']);
    $cislo_vypisu = $conn->real_escape_string($_POST['cislo_vypisu']);
    $stupen_vyplaceni = $conn->real_escape_string($_POST['stupen_vyplaceni']);

    if ($action === 'update' && isset($_POST['provize_id'])) {
        // UPDATE existující provize
        $provize_id = $conn->real_escape_string($_POST['provize_id']);
        $sql = "UPDATE provize SET smlouva_id=?, datum_vyplaty=?, castka=?, stornovana=?, storno_rezerva=?, cislo_vypisu=?, stupen_vyplaceni=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isdidsii", $smlouva_id, $datum_vyplaty, $castka, $stornovana, $storno_rezerva, $cislo_vypisu, $stupen_vyplaceni, $provize_id);

        if ($stmt->execute()) {
            $message = "Provize byla úspěšně upravena.";
            $message_type = "success";
            header("Location: provize.php");
            exit;
        } else {
            $message = "Chyba při úpravě provize: " . $stmt->error;
            $message_type = "error";
        }
    } else {
        // INSERT nové provize
        $sql = "INSERT INTO provize (smlouva_id, datum_vyplaty, castka, stornovana, storno_rezerva, cislo_vypisu, stupen_vyplaceni) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isdidsi", $smlouva_id, $datum_vyplaty, $castka, $stornovana, $storno_rezerva, $cislo_vypisu, $stupen_vyplaceni);

        if ($stmt->execute()) {
            $message = "Provize byla úspěšně přidána.";
            $message_type = "success";
            header("Location: provize.php");
            exit;
        } else {
            $message = "Chyba při přidávání provize: " . $stmt->error;
            $message_type = "error";
        }
    }
    $stmt->close();
}

// Zpracování mazání provize
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = $conn->real_escape_string($_GET['id']);
    $sql_delete = "DELETE FROM provize WHERE id = ?";
    $stmt = $conn->prepare($sql_delete);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $message = "Provize byla úspěšně smazána.";
        $message_type = "success";
        header("Location: provize.php");
        exit;
    } else {
        $message = "Chyba při mazání provize: " . $stmt->error;
        $message_type = "error";
    }
    $stmt->close();
}

// Získání seznamu všech smluv pro rozevírací seznam
$smlouvy = [];
$sql_smlouvy = "SELECT id, cislo_smlouvy FROM smlouvy ORDER BY cislo_smlouvy ASC";
$result_smlouvy = $conn->query($sql_smlouvy);
if ($result_smlouvy->num_rows > 0) {
    while ($row = $result_smlouvy->fetch_assoc()) {
        $smlouvy[] = $row;
    }
}

// Získání seznamu všech provizí s informacemi o smlouvě
$sql_provize = "
    SELECT
        provize.id,
        provize.smlouva_id,
        provize.datum_vyplaty,
        provize.castka,
        provize.stornovana,
        provize.storno_rezerva,
        provize.cislo_vypisu,
        provize.stupen_vyplaceni,
        provize.datum_vytvoreni,
        smlouvy.cislo_smlouvy,
        klienti.jmeno AS jmeno_klienta
    FROM provize
    LEFT JOIN smlouvy ON provize.smlouva_id = smlouvy.id
    LEFT JOIN klienti ON smlouvy.klient_id = klienti.id
    ORDER BY provize.datum_vytvoreni DESC
";
$result_provize = $conn->query($sql_provize);
$count_results = $result_provize->num_rows;
?>

<div class="container mx-auto mt-8 p-6 bg-white rounded-lg shadow-lg">
    <h1 class="text-3xl font-bold text-center text-gray-800 mb-6">Správa provizí</h1>

    <!-- Zobrazení zpráv (úspěch/chyba) -->
    <?php if ($message): ?>
        <div class="p-4 mb-4 rounded-md <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Hlavní ovládací tlačítko -->
    <div class="flex justify-end mb-4">
        <button id="openModal" class="py-2 px-4 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200">
            Přidat novou provizi
        </button>
    </div>

    <!-- Filtry a vyhledávání -->
    <div class="bg-gray-50 p-4 rounded-md border border-gray-200 mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Filtry a vyhledávání</h2>
            <div id="search-info">
                <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                    <p class="text-sm text-gray-500 mt-1">
                        Vyhledávání: <span class="font-bold text-blue-600">"<?php echo htmlspecialchars($_GET['search']); ?>"</span>
                        (nalezeno: <span class="font-bold text-blue-600"><?php echo $count_results; ?></span>)
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <div class="flex-grow max-w-sm ml-4">
            <label for="search" class="sr-only">Vyhledávání provize</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                    </svg>
                </div>
                <input type="text" id="search" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Vyhledat klienta, číslo smlouvy..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            </div>
        </div>
    </div>

    <!-- Seznam provizí -->
    <div class="mt-8">
        <h2 class="text-xl font-semibold mb-4">Seznam provizí</h2>
        <div id="commissions-table-container">
            <?php if ($result_provize->num_rows > 0): ?>
                <div class="overflow-x-auto bg-gray-50 rounded-md border border-gray-200 shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <!-- <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th> -->
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Klient</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Číslo smlouvy</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Datum výplaty</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Částka</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stornována</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stornorezerva</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Číslo výpisu</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stupeň vyplácení</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vytvořeno</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Akce</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($row = $result_provize->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-100 transition-colors">
                                    <!-- <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['id']); ?></td> -->
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['jmeno_klienta']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['cislo_smlouvy']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['datum_vyplaty']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['castka']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                        <?php echo $row['stornovana'] ? '&#x2714;' : '&#x2718;'; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['storno_rezerva']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['cislo_vypisu']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['stupen_vyplaceni']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('d.m.Y', strtotime($row['datum_vytvoreni'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button class="text-indigo-600 hover:text-indigo-900 edit-btn mr-3"
                                            data-id="<?php echo $row['id']; ?>"
                                            data-smlouva-id="<?php echo $row['smlouva_id']; ?>"
                                            data-datum-vyplaty="<?php echo $row['datum_vyplaty']; ?>"
                                            data-castka="<?php echo $row['castka']; ?>"
                                            data-stornovana="<?php echo $row['stornovana']; ?>"
                                            data-storno-rezerva="<?php echo $row['storno_rezerva']; ?>"
                                            data-cislo-vypisu="<?php echo $row['cislo_vypisu']; ?>"
                                            data-stupen-vyplaceni="<?php echo $row['stupen_vyplaceni']; ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                            </svg>
                                            Upravit
                                        </button>
                                        <a href="?action=delete&id=<?php echo $row['id']; ?>"
                                            class="text-red-600 hover:text-red-900 delete-btn"
                                            data-confirm="Opravdu chcete smazat tuto provizi?">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                            Smazat
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-500">Zatím nejsou přidány žádné provize.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modální okno -->
<div id="modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center pb-3">
            <h2 class="text-2xl font-semibold text-gray-800" id="modalTitle">Přidat novou provizi</h2>
            <button id="closeModal" class="text-gray-500 hover:text-gray-700">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form action="" method="post" id="provizeForm">
            <input type="hidden" name="action" id="formAction" value="insert">
            <input type="hidden" name="provize_id" id="provize_id" value="">

            <div class="mb-4">
                <label for="smlouva_id" class="block text-sm font-medium text-gray-700">Vybrat smlouvu</label>
                <select id="smlouva_id" name="smlouva_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    <option value="">-- Vyberte smlouvu --</option>
                    <?php if (!empty($smlouvy)): ?>
                        <?php foreach ($smlouvy as $smlouva): ?>
                            <option value="<?php echo htmlspecialchars($smlouva['id']); ?>">
                                <?php echo htmlspecialchars($smlouva['cislo_smlouvy']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div id="smlouva_details" class="mt-4 border-t pt-4 border-gray-200 hidden">
                <h3 class="text-lg font-medium text-gray-800 mb-2">Detaily smlouvy</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Klient</label>
                        <input type="text" id="klient_details" class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm p-2" readonly>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Číslo smlouvy</label>
                        <input type="text" id="cislo_smlouvy_details" class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm p-2" readonly>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Typ produktu</label>
                        <input type="text" id="produkt_details" class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm p-2" readonly>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Pojišťovna</label>
                        <input type="text" id="pojistovna_details" class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm p-2" readonly>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Datum sjednání</label>
                        <input type="text" id="datum_sjednani_details" class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm p-2" readonly>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Počátek platnosti</label>
                        <input type="text" id="datum_platnosti_details" class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm p-2" readonly>
                    </div>
                    <div class="md:col-span-2 mb-4">
                        <label class="block text-sm font-medium text-gray-700">Poznámka ke smlouvě</label>
                        <textarea id="poznamka_details" rows="3" class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm p-2" readonly></textarea>
                    </div>
                </div>
            </div>

            <div class="mt-4 border-t pt-4 border-gray-200">
                <h3 class="text-lg font-medium text-gray-800 mb-2">Údaje o provizi</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="mb-4">
                        <label for="datum_vyplaty" class="block text-sm font-medium text-gray-700">Datum výplaty</label>
                        <input type="date" id="datum_vyplaty" name="datum_vyplaty" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    </div>
                    <div class="mb-4">
                        <label for="castka" class="block text-sm font-medium text-gray-700">Částka</label>
                        <input type="number" step="0.01" id="castka" name="castka" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    </div>
                    <div class="flex items-center mb-4 md:col-span-2">
                        <input type="checkbox" id="stornovana" name="stornovana" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="stornovana" class="ml-2 block text-sm text-gray-900">
                            Smlouva stornována
                        </label>
                    </div>
                    <div class="mb-4">
                        <label for="storno_rezerva" class="block text-sm font-medium text-gray-700">Stornorezerva</label>
                        <input type="number" step="0.01" id="storno_rezerva" name="storno_rezerva" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    </div>
                    <div class="mb-4">
                        <label for="cislo_vypisu" class="block text-sm font-medium text-gray-700">Číslo výpisu</label>
                        <input type="number" id="cislo_vypisu" name="cislo_vypisu" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    </div>
                    <div class="mb-4">
                        <label for="stupen_vyplaceni" class="block text-sm font-medium text-gray-700">Stupeň vyplácení</label>
                        <select id="stupen_vyplaceni" name="stupen_vyplaceni" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex justify-end space-x-3 mt-6">
                <button type="submit" class="w-full mt-6 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                    Uložit provizi
                </button>
            </div>
        </form>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('modal');
        const openModalBtn = document.getElementById('openModal');
        const closeModalBtn = document.getElementById('closeModal');
        const modalTitle = document.getElementById('modalTitle');
        const form = document.getElementById('provizeForm');
        const formAction = document.getElementById('formAction');
        const provizeIdInput = document.getElementById('provize_id');
        const smlouvaSelect = document.getElementById('smlouva_id');
        const smlouvaDetailsContainer = document.getElementById('smlouva_details');

        // Real-time vyhledávání
        const searchInput = document.getElementById('search');
        let searchTimeout;

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();

            // Zobrazit indikátor načítání
            const tableContainer = document.getElementById('commissions-table-container');
            tableContainer.innerHTML = '<div class="text-center py-4">Načítání...</div>';

            searchTimeout = setTimeout(() => {
                searchCommissions(query);
            }, 300); // Zpoždění 300ms pro optimalizaci
        });

        function searchCommissions(query) {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'search_provize.php?search=' + encodeURIComponent(query), true);

            xhr.onload = function() {
                if (xhr.status === 200) {
                    document.getElementById('commissions-table-container').innerHTML = xhr.responseText;

                    // Aktualizovat informace o vyhledávání
                    const searchInfo = document.getElementById('search-info');
                    const resultCount = document.querySelectorAll('#commissions-table-container tbody tr').length;

                    if (query) {
                        searchInfo.innerHTML = `
                            <p class="text-sm text-gray-500 mt-1">
                                Vyhledávání: <span class="font-bold text-blue-600">"${query}"</span>
                                (nalezeno: <span class="font-bold text-blue-600">${resultCount}</span>)
                            </p>
                        `;
                    } else {
                        searchInfo.innerHTML = '';
                    }

                    // Znovu připojit event listenery pro editaci a mazání
                    attachEventListeners();
                } else {
                    document.getElementById('commissions-table-container').innerHTML = '<div class="text-center py-4 text-red-500">Chyba při načítání dat.</div>';
                }
            };

            xhr.onerror = function() {
                document.getElementById('commissions-table-container').innerHTML = '<div class="text-center py-4 text-red-500">Chyba připojení k serveru.</div>';
            };

            xhr.send();
        }

        function attachEventListeners() {
            // Připojit event listenery pro tlačítka editace
            document.querySelectorAll('.edit-btn').forEach(button => {
                button.addEventListener('click', function() {
                    modalTitle.textContent = 'Upravit provizi';
                    formAction.value = 'update';

                    // Naplnění formuláře daty
                    provizeIdInput.value = this.getAttribute('data-id');
                    smlouvaSelect.value = this.getAttribute('data-smlouva-id');
                    document.getElementById('datum_vyplaty').value = this.getAttribute('data-datum-vyplaty');
                    document.getElementById('castka').value = this.getAttribute('data-castka');
                    document.getElementById('stornovana').checked = this.getAttribute('data-stornovana') === '1';
                    document.getElementById('storno_rezerva').value = this.getAttribute('data-storno-rezerva');
                    document.getElementById('cislo_vypisu').value = this.getAttribute('data-cislo-vypisu');
                    document.getElementById('stupen_vyplaceni').value = this.getAttribute('data-stupen-vyplaceni');

                    // Načtení detailů smlouvy
                    loadSmlouvaDetails(this.getAttribute('data-smlouva-id'));

                    modal.classList.remove('hidden');
                });
            });

            // Připojit event listenery pro mazání
            document.querySelectorAll('.delete-btn').forEach(link => {
                link.addEventListener('click', function(e) {
                    const confirmMessage = this.getAttribute('data-confirm');
                    if (!confirm(confirmMessage)) {
                        e.preventDefault();
                    }
                });
            });
        }

        // Otevření modálního okna pro přidání
        openModalBtn.addEventListener('click', function() {
            modalTitle.textContent = 'Přidat novou provizi';
            formAction.value = 'insert';
            provizeIdInput.value = '';
            form.reset();
            smlouvaDetailsContainer.classList.add('hidden');
            modal.classList.remove('hidden');
        });

        // Zavření modálního okna
        closeModalBtn.addEventListener('click', function() {
            modal.classList.add('hidden');
        });

        // Načítání detailů smlouvy
        function loadSmlouvaDetails(smlouvaId) {
            if (smlouvaId) {
                // Skryjeme detaily, než se načtou nové
                smlouvaDetailsContainer.classList.add('hidden');

                // Načteme data ze samostatného endpointu
                fetch(`get_smlouva_details.php?smlouva_id=${smlouvaId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Chyba při načítání dat.');
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Získáme data a vyplníme políčka
                        document.getElementById('klient_details').value = data.jmeno_klienta || '';
                        document.getElementById('cislo_smlouvy_details').value = data.cislo_smlouvy || '';
                        document.getElementById('produkt_details').value = data.nazev_produktu || '';
                        document.getElementById('pojistovna_details').value = data.nazev_pojistovny || '';
                        document.getElementById('datum_sjednani_details').value = data.datum_sjednani || '';
                        document.getElementById('datum_platnosti_details').value = data.datum_platnosti || '';
                        document.getElementById('poznamka_details').value = data.poznamka || '';

                        // Zobrazíme detaily smlouvy
                        smlouvaDetailsContainer.classList.remove('hidden');
                    })
                    .catch(error => {
                        console.error('Došlo k chybě:', error);
                        // V případě chyby skryjeme detaily
                        smlouvaDetailsContainer.classList.add('hidden');
                    });
            } else {
                // Pokud není nic vybráno, skryjeme detaily
                smlouvaDetailsContainer.classList.add('hidden');
            }
        }

        // Načítání detailů při změně smlouvy
        smlouvaSelect.addEventListener('change', function() {
            loadSmlouvaDetails(this.value);
        });

        // Připojit event listenery pro stávající řádky
        attachEventListeners();
    });
</script>

<?php
// Uzavření připojení k databázi
$conn->close();

// Vložení patičky
include_once __DIR__ . '/../app/includes/footer.php';
?>