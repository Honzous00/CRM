<?php
// Vložení login logiky a kontrola přihlášení
include_once __DIR__ . '/../app/includes/login.php';
require_login();

// Vložení hlavičky a připojení k databázi
include_once __DIR__ . '/../app/includes/header.php';
include_once __DIR__ . '/../app/includes/db_connect.php';

// Definice proměnných pro zprávy (úspěch/chyba) a filtry
$success = '';
$error = '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$count_results = 0;

// Zpracování dat z formuláře pro přidání klienta
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_klient') {
    $jmeno = trim($_POST['jmeno']);
    $email = trim($_POST['email']);
    $telefon = trim($_POST['telefon']);
    $ulice = trim($_POST['ulice']);
    $mesto = trim($_POST['mesto']);
    $psc = trim($_POST['psc']);
    $rc_ico = trim($_POST['rc_ico']);

    // Podmíněné získání korespondenční adresy
    $korespondencni_ulice = isset($_POST['jina_adresa']) ? trim($_POST['korespondencni_ulice']) : $ulice;
    $korespondencni_mesto = isset($_POST['jina_adresa']) ? trim($_POST['korespondencni_mesto']) : $mesto;
    $korespondencni_psc = isset($_POST['jina_adresa']) ? trim($_POST['korespondencni_psc']) : $psc;

    if (empty($jmeno)) {
        $error = "Jméno klienta je povinné!";
    } else {
        // Kontrola duplicity rodného čísla / IČO
        $check_sql = "SELECT id FROM klienti WHERE rc_ico = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $rc_ico);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = "Klient s tímto rodným číslem / IČO již existuje!";
        } else {
            $sql = "INSERT INTO klienti (jmeno, email, telefon, ulice, mesto, psc, rc_ico, korespondencni_ulice, korespondencni_mesto, korespondencni_psc) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssssss", $jmeno, $email, $telefon, $ulice, $mesto, $psc, $rc_ico, $korespondencni_ulice, $korespondencni_mesto, $korespondencni_psc);

            if ($stmt->execute()) {
                $success = "Nový klient byl úspěšně přidán.";
                header("Location: klienti.php");
                exit;
            } else {
                $error = "Chyba při přidávání klienta: " . $conn->error;
            }
        }
    }
}

// Zpracování formuláře pro úpravu klienta
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_klient') {
    $id = trim($_POST['id']);
    $jmeno = trim($_POST['jmeno']);
    $email = trim($_POST['email']);
    $telefon = trim($_POST['telefon']);
    $ulice = trim($_POST['ulice']);
    $mesto = trim($_POST['mesto']);
    $psc = trim($_POST['psc']);
    $rc_ico = trim($_POST['rc_ico']);
    $korespondencni_ulice = isset($_POST['jina_adresa']) ? trim($_POST['korespondencni_ulice']) : $ulice;
    $korespondencni_mesto = isset($_POST['jina_adresa']) ? trim($_POST['korespondencni_mesto']) : $mesto;
    $korespondencni_psc = isset($_POST['jina_adresa']) ? trim($_POST['korespondencni_psc']) : $psc;

    if (empty($jmeno)) {
        $error = "Jméno klienta je povinné!";
    } else {
        // Kontrola duplicity pro úpravu (ignorujeme aktuálního klienta)
        $check_sql = "SELECT id FROM klienti WHERE rc_ico = ? AND id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $rc_ico, $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = "Klient s tímto rodným číslem / IČO již existuje!";
        } else {
            $sql = "UPDATE klienti SET jmeno = ?, email = ?, telefon = ?, ulice = ?, mesto = ?, psc = ?, rc_ico = ?, korespondencni_ulice = ?, korespondencni_mesto = ?, korespondencni_psc = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssssssi", $jmeno, $email, $telefon, $ulice, $mesto, $psc, $rc_ico, $korespondencni_ulice, $korespondencni_mesto, $korespondencni_psc, $id);

            if ($stmt->execute()) {
                $success = "Údaje klienta byly úspěšně aktualizovány!";
            } else {
                $error = "Chyba při aktualizaci klienta: " . $conn->error;
            }
        }
    }
}

// Zpracování akce pro smazání klienta
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Použijeme transakci pro atomické smazání souvisejících záznamů
    $conn->begin_transaction();
    try {
        // Smazání souvisejících provizí
        $sql_provize = "DELETE FROM provize WHERE smlouva_id IN (SELECT id FROM smlouvy WHERE klient_id = ?)";
        $stmt_provize = $conn->prepare($sql_provize);
        $stmt_provize->bind_param("i", $id);
        $stmt_provize->execute();

        // Smazání souvisejících smluv
        $sql_smlouvy = "DELETE FROM smlouvy WHERE klient_id = ?";
        $stmt_smlouvy = $conn->prepare($sql_smlouvy);
        $stmt_smlouvy->bind_param("i", $id);
        $stmt_smlouvy->execute();

        // Smazání klienta
        $sql_klient = "DELETE FROM klienti WHERE id = ?";
        $stmt_klient = $conn->prepare($sql_klient);
        $stmt_klient->bind_param("i", $id);
        $stmt_klient->execute();

        $conn->commit();
        $success = "Klient a všechny související záznamy byly úspěšně smazány!";
        header("Location: klienti.php");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Chyba při mazání klienta: " . $e->getMessage();
    }
}

// Získání všech klientů s podporou vyhledávání
$klienti = [];
$sql_klienti = "SELECT * FROM klienti";
$params = [];
$types = '';

if (!empty($search_query)) {
    // Přidání WHERE klauzule pro vyhledávání ve jménu, emailu, a IČO
    $sql_klienti .= " WHERE jmeno LIKE ? OR email LIKE ? OR rc_ico LIKE ?";
    $search_param = '%' . $search_query . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

$sql_klienti .= " ORDER BY jmeno ASC";

$stmt_klienti = $conn->prepare($sql_klienti);

if (!empty($params)) {
    $stmt_klienti->bind_param($types, ...$params);
}

$stmt_klienti->execute();
$result_klienti = $stmt_klienti->get_result();

if ($result_klienti->num_rows > 0) {
    while ($row = $result_klienti->fetch_assoc()) {
        $klienti[] = $row;
    }
    $count_results = count($klienti);
}
?>

<div class="container mx-auto mt-8 p-6 bg-white rounded-lg shadow-lg">
    <h1 class="text-3xl font-bold text-center text-gray-800 mb-6">Správa klientů</h1>

    <?php if (isset($success) && !empty($success)): ?>
        <div class="p-4 mb-4 rounded-md bg-green-100 text-green-700">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($error) && !empty($error)): ?>
        <div class="p-4 mb-4 rounded-md bg-red-100 text-red-700">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="flex justify-end mb-4">
        <button onclick="openAddModal()" class="py-2 px-4 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 transition-colors duration-200">
            Přidat nového klienta
        </button>
    </div>

    <div class="bg-gray-50 p-4 rounded-md border border-gray-200 mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Filtry a vyhledávání</h2>
            <div id="search-info">
                <?php if (!empty($search_query)): ?>
                    <p class="text-sm text-gray-500 mt-1">
                        Vyhledávání: <span class="font-bold text-blue-600">"<?php echo htmlspecialchars($search_query); ?>"</span>
                        (nalezeno: <span class="font-bold text-blue-600"><?php echo $count_results; ?></span>)
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <div class="flex-grow max-w-sm ml-4">
            <label for="search" class="sr-only">Vyhledávání klienta</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                    </svg>
                </div>
                <input type="text" id="search" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Vyhledat klienta..." value="<?php echo htmlspecialchars($search_query); ?>">
            </div>
        </div>
    </div>

    <div class="mt-8">
        <h2 class="text-xl font-semibold mb-4">Seznam klientů</h2>
        <div id="clients-table-container">
            <div class="overflow-x-auto bg-gray-50 rounded-md border border-gray-200 shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jméno / Firma</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rodné č. / IČO</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Telefon</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trvalá adresa</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Korespond. adresa</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Akce</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (!empty($klienti)): ?>
                            <?php foreach ($klienti as $klient): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($klient['jmeno']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($klient['rc_ico']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($klient['email']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($klient['telefon']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($klient['ulice'] . ', ' . $klient['mesto'] . ', ' . $klient['psc']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php
                                        // Zobrazí korespondenční adresu pouze pokud je jiná než trvalá
                                        if ($klient['ulice'] !== $klient['korespondencni_ulice'] || $klient['mesto'] !== $klient['korespondencni_mesto'] || $klient['psc'] !== $klient['korespondencni_psc']) {
                                            echo htmlspecialchars($klient['korespondencni_ulice'] . ', ' . $klient['korespondencni_mesto'] . ', ' . $klient['korespondencni_psc']);
                                        } else {
                                            echo 'shodná s trvalou';
                                        }
                                        ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($klient)); ?>)" class="text-blue-600 hover:text-blue-900 mr-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                            </svg>
                                            Upravit
                                        </button>
                                        <a href="klienti.php?action=delete&id=<?php echo $klient['id']; ?>" onclick="return confirm('Opravdu chcete smazat tohoto klienta a všechny související smlouvy a provize?');" class="text-red-600 hover:text-red-900">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                            </svg>
                                            Smazat
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                    Zatím nejsou přidáni žádní klienti.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div id="addModal" class="fixed z-10 inset-0 overflow-y-auto hidden">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-7xl sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Přidat nového klienta
                        </h3>
                        <div class="mt-2">
                            <form id="addForm" action="klienti.php" method="POST">
                                <input type="hidden" name="action" value="add_klient">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="mb-4">
                                        <label for="jmeno" class="block text-sm font-medium text-gray-700">Jméno / Firma</label>
                                        <input type="text" id="jmeno" name="jmeno" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                                    </div>
                                    <div class="mb-4">
                                        <label for="rc_ico" class="block text-sm font-medium text-gray-700">Rodné číslo / IČO</label>
                                        <input type="text" id="rc_ico" name="rc_ico" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                                    </div>
                                    <div class="mb-4">
                                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                        <input type="email" id="email" name="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                                    </div>
                                    <div class="mb-4">
                                        <label for="telefon" class="block text-sm font-medium text-gray-700">Telefon</label>
                                        <input type="tel" id="telefon" name="telefon" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                                    </div>
                                </div>

                                <h3 class="text-lg font-medium text-gray-800 mt-6 mb-2">Trvalá adresa</h3>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="mb-4">
                                        <label for="ulice" class="block text-sm font-medium text-gray-700">Ulice</label>
                                        <input type="text" id="ulice" name="ulice" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                                    </div>
                                    <div class="mb-4">
                                        <label for="mesto" class="block text-sm font-medium text-gray-700">Město</label>
                                        <input type="text" id="mesto" name="mesto" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                                    </div>
                                    <div class="mb-4">
                                        <label for="psc" class="block text-sm font-medium text-gray-700">PSČ</label>
                                        <input type="text" id="psc" name="psc" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                                    </div>
                                </div>

                                <div class="mt-6 flex items-center">
                                    <input type="checkbox" id="jina_adresa" name="jina_adresa" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="jina_adresa" class="ml-2 block text-sm text-gray-900">
                                        Jiná korespondenční adresa
                                    </label>
                                </div>

                                <div id="korespondencni-adresa-fields" class="mt-4 hidden border-t pt-4 border-gray-200">
                                    <h3 class="text-lg font-medium text-gray-800 mb-2">Korespondenční adresa</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div class="mb-4">
                                            <label for="korespondencni_ulice" class="block text-sm font-medium text-gray-700">Ulice</label>
                                            <input type="text" id="korespondencni_ulice" name="korespondencni_ulice" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                                        </div>
                                        <div class="mb-4">
                                            <label for="korespondencni_mesto" class="block text-sm font-medium text-gray-700">Město</label>
                                            <input type="text" id="korespondencni_mesto" name="korespondencni_mesto" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                                        </div>
                                        <div class="mb-4">
                                            <label for="korespondencni_psc" class="block text-sm font-medium text-gray-700">PSČ</label>
                                            <input type="text" id="korespondencni_psc" name="korespondencni_psc" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="submit" form="addForm" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Přidat klienta
                </button>
                <button type="button" onclick="closeAddModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Zavřít
                </button>
            </div>
        </div>
    </div>
</div>

<div id="editModal" class="fixed z-10 inset-0 overflow-y-auto hidden">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-7xl sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Upravit klienta
                        </h3>
                        <div class="mt-2">
                            <form id="editForm" action="klienti.php" method="POST">
                                <input type="hidden" name="action" value="update_klient">
                                <input type="hidden" name="id" id="edit-id">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="mb-4">
                                        <label for="edit-jmeno" class="block text-sm font-medium text-gray-700">Jméno</label>
                                        <input type="text" name="jmeno" id="edit-jmeno" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                                    </div>
                                    <div class="mb-4">
                                        <label for="edit-rc_ico" class="block text-sm font-medium text-gray-700">Rodné číslo / IČO</label>
                                        <input type="text" name="rc_ico" id="edit-rc_ico" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                                    </div>
                                    <div class="mb-4">
                                        <label for="edit-email" class="block text-sm font-medium text-gray-700">E-mail</label>
                                        <input type="email" name="email" id="edit-email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                                    </div>
                                    <div class="mb-4">
                                        <label for="edit-telefon" class="block text-sm font-medium text-gray-700">Telefon</label>
                                        <input type="tel" name="telefon" id="edit-telefon" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                                    </div>
                                </div>

                                <h3 class="text-lg font-medium text-gray-800 mt-6 mb-2">Trvalá adresa</h3>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="mb-4">
                                        <label for="edit-ulice" class="block text-sm font-medium text-gray-700">Ulice</label>
                                        <input type="text" id="edit-ulice" name="ulice" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                                    </div>
                                    <div class="mb-4">
                                        <label for="edit-mesto" class="block text-sm font-medium text-gray-700">Město</label>
                                        <input type="text" id="edit-mesto" name="mesto" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                                    </div>
                                    <div class="mb-4">
                                        <label for="edit-psc" class="block text-sm font-medium text-gray-700">PSČ</label>
                                        <input type="text" id="edit-psc" name="psc" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                                    </div>
                                </div>
                                <div class="mt-6 flex items-center">
                                    <input type="checkbox" id="edit-jina_adresa" name="jina_adresa" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="edit-jina_adresa" class="ml-2 block text-sm text-gray-900">
                                        Jiná korespondenční adresa
                                    </label>
                                </div>
                                <div id="edit-korespondencni-adresa-fields" class="mt-4 hidden border-t pt-4 border-gray-200">
                                    <h3 class="text-lg font-medium text-gray-800 mb-2">Korespondenční adresa</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div class="mb-4">
                                            <label for="edit-korespondencni_ulice" class="block text-sm font-medium text-gray-700">Ulice</label>
                                            <input type="text" id="edit-korespondencni_ulice" name="korespondencni_ulice" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                                        </div>
                                        <div class="mb-4">
                                            <label for="edit-korespondencni_mesto" class="block text-sm font-medium text-gray-700">Město</label>
                                            <input type="text" id="edit-korespondencni_mesto" name="korespondencni_mesto" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                                        </div>
                                        <div class="mb-4">
                                            <label for="edit-korespondencni_psc" class="block text-sm font-medium text-gray-700">PSČ</label>
                                            <input type="text" id="edit-korespondencni_psc" name="korespondencni_psc" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="submit" form="editForm" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Uložit změny
                </button>
                <button type="button" onclick="closeEditModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Zavřít
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Real-time vyhledávání
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('search');
        let searchTimeout;

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();

            // Zobrazit indikátor načítání
            const tableContainer = document.getElementById('clients-table-container');
            tableContainer.innerHTML = '<div class="text-center py-4">Načítání...</div>';

            searchTimeout = setTimeout(() => {
                searchClients(query);
            }, 300); // Zpoždění 300ms pro optimalizaci
        });

        function searchClients(query) {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'search_klienti.php?search=' + encodeURIComponent(query), true);

            xhr.onload = function() {
                if (xhr.status === 200) {
                    document.getElementById('clients-table-container').innerHTML = xhr.responseText;

                    // Aktualizovat informace o vyhledávání
                    const searchInfo = document.getElementById('search-info');
                    const resultCount = document.querySelectorAll('#clients-table-container tbody tr').length - 1; // Odečteme řádek s "žádní klienti"

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
                } else {
                    document.getElementById('clients-table-container').innerHTML = '<div class="text-center py-4 text-red-500">Chyba při načítání dat.</div>';
                }
            };

            xhr.onerror = function() {
                document.getElementById('clients-table-container').innerHTML = '<div class="text-center py-4 text-red-500">Chyba připojení k serveru.</div>';
            };

            xhr.send();
        }

        // Původní kód pro správu modálních oken
        const jinaAdresaCheckbox = document.getElementById('jina_adresa');
        const korespondencniAdresaFields = document.getElementById('korespondencni-adresa-fields');

        if (jinaAdresaCheckbox) {
            jinaAdresaCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    korespondencniAdresaFields.classList.remove('hidden');
                } else {
                    korespondencniAdresaFields.classList.add('hidden');
                }
            });
        }

        const editJinaAdresaCheckbox = document.getElementById('edit-jina_adresa');
        const editKorespondencniAdresaFields = document.getElementById('edit-korespondencni-adresa-fields');

        if (editJinaAdresaCheckbox) {
            editJinaAdresaCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    editKorespondencniAdresaFields.classList.remove('hidden');
                } else {
                    editKorespondencniAdresaFields.classList.add('hidden');
                }
            });
        }
    });

    function openAddModal() {
        document.getElementById('addModal').classList.remove('hidden');
    }

    function closeAddModal() {
        document.getElementById('addModal').classList.add('hidden');
    }

    function openEditModal(klient) {
        document.getElementById('edit-id').value = klient.id;
        document.getElementById('edit-jmeno').value = klient.jmeno;
        document.getElementById('edit-rc_ico').value = klient.rc_ico;
        document.getElementById('edit-email').value = klient.email;
        document.getElementById('edit-telefon').value = klient.telefon;
        document.getElementById('edit-ulice').value = klient.ulice;
        document.getElementById('edit-mesto').value = klient.mesto;
        document.getElementById('edit-psc').value = klient.psc;

        const editKorespondencniAdresaFields = document.getElementById('edit-korespondencni-adresa-fields');
        const editJinaAdresaCheckbox = document.getElementById('edit-jina_adresa');

        if (klient.ulice !== klient.korespondencni_ulice || klient.mesto !== klient.korespondencni_mesto || klient.psc !== klient.korespondencni_psc) {
            editJinaAdresaCheckbox.checked = true;
            editKorespondencniAdresaFields.classList.remove('hidden');
            document.getElementById('edit-korespondencni_ulice').value = klient.korespondencni_ulice;
            document.getElementById('edit-korespondencni_mesto').value = klient.korespondencni_mesto;
            document.getElementById('edit-korespondencni_psc').value = klient.korespondencni_psc;
        } else {
            editJinaAdresaCheckbox.checked = false;
            editKorespondencniAdresaFields.classList.add('hidden');
        }

        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }
</script>

<?php
// Uzavření připojení k databázi
$conn->close();

// Vložení patičky
include_once __DIR__ . '/../app/includes/footer.php';
?>