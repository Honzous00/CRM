<?php
include_once __DIR__ . '/../app/includes/login.php';
require_login();
include_once __DIR__ . '/../app/includes/db_connect.php';

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

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
";

if (!empty($search_query)) {
    $sql_provize .= " WHERE 
        klienti.jmeno LIKE ? OR 
        smlouvy.cislo_smlouvy LIKE ?";
}

$sql_provize .= " ORDER BY provize.datum_vytvoreni DESC";

$stmt = $conn->prepare($sql_provize);

if (!empty($search_query)) {
    $search_param = '%' . $search_query . '%';
    $stmt->bind_param("ss", $search_param, $search_param);
}

$stmt->execute();
$result_provize = $stmt->get_result();
?>

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
                    <tr>
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
                            <a href="provize.php?action=delete&id=<?php echo $row['id']; ?>"
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
    <p class="text-gray-500">Žádné provize nebyly nalezeny.</p>
<?php endif; ?>

<?php
$conn->close();
?>