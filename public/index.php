<?php
// Vložení login logiky a kontrola přihlášení
include_once __DIR__ . '/../app/includes/login.php';
require_login();

// Vložení hlavičky a připojení k databázi
include_once __DIR__ . '/../app/includes/header.php';
include_once __DIR__ . '/../app/includes/db_connect.php';

// SQL dotaz pro získání počtu záznamů
$sql_klienti = "SELECT COUNT(*) AS total FROM klienti";
$result_klienti = $conn->query($sql_klienti);
$total_klienti = $result_klienti->fetch_assoc()['total'];

$sql_smlouvy = "SELECT COUNT(*) AS total FROM smlouvy";
$result_smlouvy = $conn->query($sql_smlouvy);
$total_smlouvy = $result_smlouvy->fetch_assoc()['total'];

// SQL dotaz pro získání součtu vyplacené částky provizí
$sql_provize_castka = "SELECT SUM(castka) AS total FROM provize";
$result_provize_castka = $conn->query($sql_provize_castka);

// Opravená logika - zkontrolujeme, zda existuje výsledek, než se k němu pokusíme přistoupit
$total_provize_castka = 0;
if ($result_provize_castka && $result_provize_castka->num_rows > 0) {
    $row = $result_provize_castka->fetch_assoc();
    $total_provize_castka = $row['total'] ?? 0;
}

// Získání posledních 5 smluv
$sql_last_smlouvy = "SELECT * FROM smlouvy ORDER BY datum_sjednani DESC LIMIT 5";
$result_last_smlouvy = $conn->query($sql_last_smlouvy);
$last_smlouvy = [];
if ($result_last_smlouvy->num_rows > 0) {
    while ($row = $result_last_smlouvy->fetch_assoc()) {
        $last_smlouvy[] = $row;
    }
}

// Získání posledních 5 klientů
$sql_last_klienti = "SELECT * FROM klienti ORDER BY datum_vytvoreni DESC LIMIT 5";
$result_last_klienti = $conn->query($sql_last_klienti);
$last_klienti = [];
if ($result_last_klienti->num_rows > 0) {
    while ($row = $result_last_klienti->fetch_assoc()) {
        $last_klienti[] = $row;
    }
}
?>

<div class="container mx-auto mt-8 px-4">
    <h1 class="text-4xl font-bold text-gray-800 mb-6 text-center">Přehledný panel</h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Počet klientů -->
        <div class="bg-white rounded-lg shadow-md p-6 text-center">
            <h2 class="text-xl font-semibold text-gray-700 mb-2">Celkový počet klientů</h2>
            <p class="text-5xl font-bold text-blue-600"><?php echo $total_klienti; ?></p>
        </div>
        <!-- Počet smluv -->
        <div class="bg-white rounded-lg shadow-md p-6 text-center">
            <h2 class="text-xl font-semibold text-gray-700 mb-2">Celkový počet smluv</h2>
            <p class="text-5xl font-bold text-green-600"><?php echo $total_smlouvy; ?></p>
        </div>
        <!-- Celková částka provizí -->
        <div class="bg-white rounded-lg shadow-md p-6 text-center">
            <h2 class="text-xl font-semibold text-gray-700 mb-2">Celkem vyplaceno provizí</h2>
            <p class="text-5xl font-bold text-purple-600"><?php echo number_format($total_provize_castka, 2, ',', ' '); ?> Kč</p>
        </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Posledních 5 klientů -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Posledních 5 klientů</h2>
            <ul class="divide-y divide-gray-200">
                <?php if (!empty($last_klienti)): ?>
                    <?php foreach ($last_klienti as $klient): ?>
                        <li class="py-4 flex justify-between items-center">
                            <span class="text-gray-700 font-medium"><?php echo htmlspecialchars($klient['jmeno']); ?></span>
                            <span class="text-sm text-gray-500"><?php echo date('d.m.Y', strtotime($klient['datum_vytvoreni'])); ?></span>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="py-4 text-gray-500">Zatím nejsou přidáni žádní klienti.</li>
                <?php endif; ?>
            </ul>
        </div>
        
        <!-- Posledních 5 smluv -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Posledních 5 smluv</h2>
            <ul class="divide-y divide-gray-200">
                <?php if (!empty($last_smlouvy)): ?>
                    <?php foreach ($last_smlouvy as $smlouva): ?>
                        <li class="py-4 flex justify-between items-center">
                            <span class="text-gray-700 font-medium"><?php echo htmlspecialchars($smlouva['cislo_smlouvy']); ?></span>
                            <span class="text-sm text-gray-500"><?php echo date('d.m.Y', strtotime($smlouva['datum_sjednani'])); ?></span>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="py-4 text-gray-500">Zatím nejsou přidány žádné smlouvy.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

</div>

<?php
// Uzavření připojení k databázi
$conn->close();

// Vložení patičky
include_once __DIR__ . '/../app/includes/footer.php';
?>
