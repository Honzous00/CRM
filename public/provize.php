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

// Zpracování dat z formuláře po odeslání
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Získání a ošetření dat z formuláře
    $smlouva_id = $conn->real_escape_string($_POST['smlouva_id']);
    $datum_vyplaty = $conn->real_escape_string($_POST['datum_vyplaty']);
    $castka = $conn->real_escape_string($_POST['castka']);
    $stornovana = isset($_POST['stornovana']) ? 1 : 0;
    $storno_rezerva = $conn->real_escape_string($_POST['storno_rezerva']);
    $cislo_vypisu = $conn->real_escape_string($_POST['cislo_vypisu']);
    $stupen_vyplaceni = $conn->real_escape_string($_POST['stupen_vyplaceni']);

    // Vytvoření SQL dotazu pro vložení provize
    $sql_insert = "INSERT INTO provize (smlouva_id, datum_vyplaty, castka, stornovana, storno_rezerva, cislo_vypisu, stupen_vyplaceni) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql_insert);
    $stmt->bind_param("isdidsi", $smlouva_id, $datum_vyplaty, $castka, $stornovana, $storno_rezerva, $cislo_vypisu, $stupen_vyplaceni);

    // Spuštění dotazu a kontrola výsledku
    if ($stmt->execute()) {
        $message = "Provize byla úspěšně přidána.";
        $message_type = "success";
        header("Location: provize.php");
        exit;
    } else {
        $message = "Chyba při přidávání provize: " . $stmt->error;
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
?>

<div class="container mx-auto mt-8 p-6 bg-white rounded-lg shadow-lg">
    <h1 class="text-3xl font-bold text-center text-gray-800 mb-6">Správa provizí</h1>

    <!-- Zobrazení zpráv (úspěch/chyba) -->
    <?php if ($message): ?>
        <div class="p-4 mb-4 rounded-md <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Formulář pro přidání provize -->
    <div class="bg-gray-50 p-6 rounded-md border border-gray-200">
        <h2 class="text-xl font-semibold mb-4">Přidat novou provizi</h2>
        <form action="" method="post">
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

            <button type="submit" class="w-full mt-6 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                Uložit provizi
            </button>
        </form>
    </div>

    <!-- Seznam provizí -->
    <div class="mt-8">
        <h2 class="text-xl font-semibold mb-4">Seznam provizí</h2>
        <?php if ($result_provize->num_rows > 0): ?>
            <div class="overflow-x-auto bg-gray-50 rounded-md border border-gray-200 shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Klient</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Číslo smlouvy</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Datum výplaty</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Částka</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stornována</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stornorezerva</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Číslo výpisu</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stupeň vyplácení</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vytvořeno</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while ($row = $result_provize->fetch_assoc()): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['id']); ?></td>
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const smlouvaSelect = document.getElementById('smlouva_id');
        const smlouvaDetailsContainer = document.getElementById('smlouva_details');

        smlouvaSelect.addEventListener('change', function() {
            const smlouvaId = this.value;

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
        });
    });
</script>

<?php
// Uzavření připojení k databázi
$conn->close();

// Vložení patičky
include_once __DIR__ . '/../app/includes/footer.php';
?>