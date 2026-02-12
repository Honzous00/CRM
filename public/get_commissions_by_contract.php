<?php
include_once __DIR__ . '/../app/includes/login.php';
require_login();
include_once __DIR__ . '/../app/includes/db_connect.php';

if (!isset($_GET['smlouva_id']) || !is_numeric($_GET['smlouva_id'])) {
    echo '<p class="text-red-500 text-center">Neplatné ID smlouvy.</p>';
    exit;
}

$smlouva_id = (int)$_GET['smlouva_id'];

// Načti všechny provize pro danou smlouvu
$sql = "
    SELECT
        p.id,
        p.smlouva_id,
        p.datum_vyplaty,
        p.castka,
        p.stornovana,
        p.storno_rezerva,
        p.predavaci_dokument_cislo,
        p.cislo_vypisu,
        p.stupen_vyplaceni,
        p.datum_vytvoreni,
        s.cislo_smlouvy,
        k.jmeno AS jmeno_klienta,
        pd.cislo AS predavaci_dokument,
        pd.id AS predavaci_dokument_id
    FROM provize p
    LEFT JOIN smlouvy s ON p.smlouva_id = s.id
    LEFT JOIN klienti k ON s.klient_id = k.id
    LEFT JOIN predavaci_dokumenty pd ON s.predavaci_dokument_id = pd.id
    WHERE p.smlouva_id = ?
    ORDER BY p.datum_vytvoreni DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $smlouva_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="overflow-x-auto border border-gray-200 rounded-lg">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-100">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Datum výplaty</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Částka</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stornována</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stornorezerva</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Předávací dokument</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Číslo výpisu</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stupeň vyplácení</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vytvořeno</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Akce</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if ($result->num_rows === 0): ?>
                <tr>
                    <td colspan="9" class="px-6 py-4 text-center text-gray-500">
                        K této smlouvě nejsou evidovány žádné provize.
                    </td>
                </tr>
            <?php else: ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo htmlspecialchars($row['datum_vyplaty']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo number_format($row['castka'], 2, ',', ' '); ?> Kč</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center"><?php echo $row['stornovana'] ? '✔️' : '❌'; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo htmlspecialchars($row['storno_rezerva']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <?php if (!empty($row['predavaci_dokument_cislo'])): ?>
                                <a href="predavaci_dokumenty.php?id=<?php echo $row['predavaci_dokument_id']; ?>"
                                    class="text-blue-600 hover:text-blue-800">
                                    <?php echo htmlspecialchars($row['predavaci_dokument_cislo']); ?>
                                </a>
                            <?php else: ?>
                                <?php echo htmlspecialchars($row['predavaci_dokument_cislo']); ?>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <?php if (!empty($row['cislo_vypisu'])): ?>
                                <a href="cislo_vypisu.php?cislo=<?php echo urlencode($row['cislo_vypisu']); ?>"
                                    class="text-green-600 hover:text-green-800">
                                    <?php echo htmlspecialchars($row['cislo_vypisu']); ?>
                                </a>
                            <?php else: ?>
                                <?php echo htmlspecialchars($row['cislo_vypisu']); ?>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo htmlspecialchars($row['stupen_vyplaceni']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo date('d.m.Y', strtotime($row['datum_vytvoreni'])); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button class="text-indigo-600 hover:text-indigo-900 edit-btn mr-2"
                                data-id="<?php echo $row['id']; ?>"
                                data-smlouva-id="<?php echo $row['smlouva_id']; ?>"
                                data-datum-vyplaty="<?php echo $row['datum_vyplaty']; ?>"
                                data-castka="<?php echo $row['castka']; ?>"
                                data-stornovana="<?php echo $row['stornovana']; ?>"
                                data-storno-rezerva="<?php echo $row['storno_rezerva']; ?>"
                                data-predavaci-dokument-cislo="<?php echo htmlspecialchars($row['predavaci_dokument_cislo']); ?>"
                                data-cislo-vypisu="<?php echo htmlspecialchars($row['cislo_vypisu']); ?>"
                                data-stupen-vyplaceni="<?php echo $row['stupen_vyplaceni']; ?>">
                                Upravit
                            </button>
                            <a href="?action=delete&id=<?php echo $row['id']; ?>"
                                class="text-red-600 hover:text-red-900 delete-btn"
                                onclick="return confirm('Opravdu chcete smazat tuto provizi?');">
                                Smazat
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$stmt->close();
$conn->close();
