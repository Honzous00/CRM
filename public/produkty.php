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

// Zpracování dat z formuláře pro přidání produktu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pridat_produkt'])) {
    $nazev = $conn->real_escape_string($_POST['nazev']);

    // Vytvoření SQL dotazu pro vložení produktu
    $sql_insert = "INSERT INTO produkty (nazev) VALUES ('$nazev')";

    // Spuštění dotazu a kontrola výsledku
    if ($conn->query($sql_insert) === TRUE) {
        $message = "Produkt byl úspěšně přidán.";
        $message_type = "success";
    } else {
        $message = "Chyba při přidávání produktu: " . $conn->error;
        $message_type = "error";
    }
}

// Zpracování dat pro smazání produktu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['smazat_produkt'])) {
    $produkt_id = $conn->real_escape_string($_POST['produkt_id']);
    
    // Vytvoření SQL dotazu pro smazání produktu
    $sql_delete = "DELETE FROM produkty WHERE id = '$produkt_id'";
    
    // Spuštění dotazu a kontrola výsledku
    if ($conn->query($sql_delete) === TRUE) {
        $message = "Produkt byl úspěšně smazán.";
        $message_type = "success";
    } else {
        $message = "Chyba při mazání produktu: " . $conn->error;
        $message_type = "error";
    }
}

// Získání seznamu všech produktů
$produkty = [];
$sql_produkty = "SELECT * FROM produkty ORDER BY nazev ASC";
$result_produkty = $conn->query($sql_produkty);
if ($result_produkty->num_rows > 0) {
    while ($row = $result_produkty->fetch_assoc()) {
        $produkty[] = $row;
    }
}
?>

<div class="container mx-auto mt-8 p-6 bg-white rounded-lg shadow-lg">
    <h1 class="text-3xl font-bold text-center text-gray-800 mb-6">Správa produktů</h1>

    <!-- Zobrazení zpráv (úspěch/chyba) -->
    <?php if ($message): ?>
        <div class="p-4 mb-4 rounded-md <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Formulář pro přidání nového produktu -->
    <div class="bg-gray-50 p-6 rounded-md border border-gray-200 mb-8">
        <h2 class="text-xl font-semibold mb-4">Přidat nový typ produktu</h2>
        <form action="" method="post" class="flex items-end">
            <div class="flex-grow mr-4">
                <label for="nazev" class="block text-sm font-medium text-gray-700">Název produktu</label>
                <input type="text" id="nazev" name="nazev" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            </div>
            <button type="submit" name="pridat_produkt" class="py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                Přidat
            </button>
        </form>
    </div>

    <!-- Seznam existujících produktů -->
    <div class="bg-gray-50 p-6 rounded-md border border-gray-200">
        <h2 class="text-xl font-semibold mb-4">Seznam produktů</h2>
        <?php if (empty($produkty)): ?>
            <p class="text-gray-500">Nejsou k dispozici žádné produkty.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Název</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Akce</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($produkty as $produkt): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($produkt['id']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($produkt['nazev']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <form action="" method="post" onsubmit="return confirm('Opravdu chcete smazat tento produkt?');">
                                        <input type="hidden" name="produkt_id" value="<?php echo htmlspecialchars($produkt['id']); ?>">
                                        <button type="submit" name="smazat_produkt" class="text-red-600 hover:text-red-800 transition-colors duration-200">
                                            Smazat
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Uzavření připojení k databázi
$conn->close();

// Vložení patičky
include_once __DIR__ . '/../app/includes/footer.php';
?>
