<?php

function displaySmlouvyTable($smlouvy, $conn)
{
    if (empty($smlouvy)) {
        echo '<p class="text-gray-500">Zatím nejsou přidány žádné smlouvy.</p>';
        return;
    }
?>
    <div class="overflow-x-auto bg-gray-50 rounded-md border border-gray-200 shadow-sm contracts-table-wrapper">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-100">
                <tr>
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
                <?php foreach ($smlouvy as $row): ?>
                    <tr class="hover:bg-gray-100 transition-colors smlouva-row"
                        data-smlouva-id="<?php echo $row['id']; ?>"
                        data-id="<?php echo htmlspecialchars($row['id']); ?>"
                        data-klient-id="<?php echo htmlspecialchars($row['klient_id']); ?>"
                        data-cislo-smlouvy="<?php echo htmlspecialchars($row['cislo_smlouvy']); ?>"
                        data-produkt-id="<?php echo htmlspecialchars($row['produkt_id']); ?>"
                        data-pojistovna-id="<?php echo htmlspecialchars($row['pojistovna_id']); ?>"
                        data-datum-sjednani="<?php echo htmlspecialchars($row['datum_sjednani']); ?>"
                        data-datum-platnosti="<?php echo htmlspecialchars($row['datum_platnosti']); ?>"
                        data-zaznam-zeteo="<?php echo $row['zaznam_zeteo']; ?>"
                        data-poznamka="<?php echo htmlspecialchars($row['poznamka']); ?>"
                        data-cesta-k-souboru="<?php echo htmlspecialchars($row['cesta_k_souboru']); ?>"
                        data-podminky-produktu="<?php echo htmlspecialchars($row['podminky_produktu']); ?>">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['jmeno_klienta']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <a href="smlouvy.php?id=<?php echo $row['id']; ?>" class="text-blue-600 hover:text-blue-800 hover:underline">
                                <?php echo htmlspecialchars($row['cislo_smlouvy']); ?>
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['nazev_produktu']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['nazev_pojistovny']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['datum_sjednani']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['datum_platnosti']); ?></td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <?php
                            if ($row['podminky_produktu']) {
                                $podminky = json_decode($row['podminky_produktu'], true);
                                if (is_array($podminky) && !empty(array_filter($podminky))) {
                            ?>
                                    <div class="relative inline-block group">
                                        <button type="button"
                                            class="inline-flex items-center text-blue-600 hover:text-blue-800 transition-colors duration-200"
                                            title="Zobrazit podrobnosti">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <span class="ml-1">Specifika</span>
                                        </button>

                                        <!-- CSS Tooltip -->
                                        <div class="absolute invisible opacity-0 group-hover:visible group-hover:opacity-100 transition-all duration-200 bottom-full left-1/2 transform -translate-x-1/2 mb-2 z-50">
                                            <div class="bg-white rounded-lg shadow-xl border border-gray-200 p-4 max-w-sm relative">
                                                <div class="text-sm text-gray-700 space-y-2">
                                                    <?php
                                                    foreach ($podminky as $klic => $hodnota) {
                                                        if (!empty($hodnota) && $hodnota !== 'Ne') {
                                                            $displayKey = ucfirst(str_replace('_', ' ', $klic));
                                                            echo '<div class="flex justify-between">';
                                                            echo '<span class="font-medium">' . htmlspecialchars($displayKey) . ':</span>';
                                                            echo '<span class="ml-2">' . htmlspecialchars($hodnota) . '</span>';
                                                            echo '</div>';
                                                        }
                                                    }
                                                    ?>
                                                </div>
                                                <!-- Šipka -->
                                                <div class="absolute top-full left-1/2 transform -translate-x-1/2 -mt-1">
                                                    <div class="w-4 h-4 bg-white border-r border-b border-gray-200 transform rotate-45"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                            <?php
                                } else {
                                    echo '<span class="text-gray-400">-</span>';
                                }
                            } else {
                                echo '<span class="text-gray-400">-</span>';
                            }
                            ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                            <?php echo $row['zaznam_zeteo'] ? '&#x2714;' : '&#x2718;'; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php
                            $dokumentyModel = new DokumentyModel($conn);
                            $dokumenty = $dokumentyModel->getDokumentyBySmlouva($row['id']);
                            $vsechnyDokumenty = [];

                            // Přidání hlavní smlouvy
                            if (!empty($row['cesta_k_souboru'])) {
                                $vsechnyDokumenty[] = [
                                    'nazev' => 'Hlavní smlouva',
                                    'cesta' => $row['cesta_k_souboru'],
                                    'typ' => 'Smlouva'
                                ];
                            }

                            // Přidání dalších dokumentů
                            foreach ($dokumenty as $dokument) {
                                if ($dokument['typ_dokumentu'] !== 'Smlouva' && !empty($dokument['cesta_k_souboru'])) {
                                    $vsechnyDokumenty[] = [
                                        'nazev' => $dokument['typ_dokumentu'],
                                        'cesta' => $dokument['cesta_k_souboru'],
                                        'typ' => $dokument['typ_dokumentu']
                                    ];
                                }
                            }

                            $pocetDokumentu = count($vsechnyDokumenty);

                            if ($pocetDokumentu === 0) {
                                echo 'N/A';
                            } elseif ($pocetDokumentu === 1) {
                                // Pokud je jen jeden dokument - přímý odkaz
                                $dokument = $vsechnyDokumenty[0];
                                echo '<a href="' . htmlspecialchars($dokument['cesta']) . '" target="_blank" 
                class="text-blue-600 hover:text-blue-800 transition-colors duration-200 flex items-center"
                title="' . htmlspecialchars($dokument['nazev']) . '">
                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Smlouva
              </a>';
                            } else {
                                // Pokud je více dokumentů - dropdown menu
                            ?>
                                <div class="relative inline-block text-left documents-container">
                                    <button type="button"
                                        class="inline-flex items-center text-blue-600 hover:text-blue-800 transition-colors duration-200 documents-dropdown-btn"
                                        data-smlouva-id="<?php echo $row['id']; ?>">
                                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        Dokumenty (<?php echo $pocetDokumentu; ?>)
                                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </button>
                                    <!-- dokud se tohle nesmaže, tak vracej -->
                                    <div id="documents-dropdown-<?php echo $row['id']; ?>"
                                        class="hidden absolute left-0 mt-2 w-64 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 documents-dropdown">
                                        <div class="py-1 max-h-64 overflow-y-auto" role="menu" aria-orientation="vertical">
                                            <?php foreach ($vsechnyDokumenty as $index => $dokument): ?>
                                                <a href="<?php echo htmlspecialchars($dokument['cesta']); ?>"
                                                    target="_blank"
                                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 flex items-center"
                                                    role="menuitem"
                                                    style="z-index: 10002; position: relative;">
                                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                    </svg>
                                                    <?php echo htmlspecialchars($dokument['nazev']); ?>
                                                </a>
                                                <?php if ($index < count($vsechnyDokumenty) - 1): ?>
                                                    <div class="border-t border-gray-100 my-1"></div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php
                            }
                            ?>
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
                            <form method="post" action="smlouvy.php" class="inline-block delete-form ml-2"
                                data-confirm="Opravdu chcete smazat smlouvu č. <?php echo htmlspecialchars($row['cislo_smlouvy']); ?> klienta <?php echo htmlspecialchars($row['jmeno_klienta']); ?>?">
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
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php
}
?>

<?php
/**
 * Zobrazení detailní karty smlouvy a seznamu provizí.
 *
 * @param array $smlouva
 * @param array $provize
 * @param float $totalProvize
 * @param mysqli $conn Připojení k databázi (nutné pro DokumentyModel)
 */
function displaySmlouvaDetail($smlouva, $provize, $totalProvize, $conn)
{
?>
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <!-- HLAVIČKA S TLAČÍTKY AKCÍ -->
        <div class="flex flex-wrap justify-between items-center mb-6 pb-4 border-b border-gray-200 gap-2">
            <h2 class="text-2xl font-bold text-gray-800">Detail smlouvy</h2>
            <div class="flex items-center gap-2">
                <!-- TLAČÍTKO UPRAVIT -->
                <button type="button"
                    onclick='openEditModalFromDetail(<?php echo json_encode($smlouva, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'
                    class="inline-flex items-center px-3 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 transition-colors">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Upravit
                </button>

                <!-- TLAČÍTKO SMAZAT (formulář) -->
                <form method="post" action="smlouvy.php"
                    onsubmit="return confirm('Opravdu chcete smazat smlouvu č. <?php echo htmlspecialchars($smlouva['cislo_smlouvy']); ?>?');"
                    class="inline-block m-0 p-0">
                    <input type="hidden" name="delete_id" value="<?php echo $smlouva['id']; ?>">
                    <button type="submit"
                        class="inline-flex items-center px-3 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 transition-colors">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Smazat
                    </button>
                </form>

                <!-- ZPĚT NA SEZNAM (původní) -->
                <a href="smlouvy.php"
                    class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Zpět na seznam
                </a>
            </div>
        </div>

        <!-- Dvousloupcový grid s informacemi o smlouvě -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Levý sloupec -->
            <div class="space-y-4">
                <div class="flex items-start">
                    <span class="text-sm font-medium text-gray-500 w-32">Číslo smlouvy:</span>
                    <span class="text-sm text-gray-900 font-semibold"><?php echo htmlspecialchars($smlouva['cislo_smlouvy']); ?></span>
                </div>
                <div class="flex items-start">
                    <span class="text-sm font-medium text-gray-500 w-32">Klient:</span>
                    <span class="text-sm text-gray-900"><?php echo htmlspecialchars($smlouva['jmeno_klienta']); ?></span>
                </div>
                <div class="flex items-start">
                    <span class="text-sm font-medium text-gray-500 w-32">Produkt:</span>
                    <span class="text-sm text-gray-900"><?php echo htmlspecialchars($smlouva['nazev_produktu']); ?></span>
                </div>
                <div class="flex items-start">
                    <span class="text-sm font-medium text-gray-500 w-32">Pojišťovna:</span>
                    <span class="text-sm text-gray-900"><?php echo htmlspecialchars($smlouva['nazev_pojistovny']); ?></span>
                </div>
            </div>

            <!-- Pravý sloupec -->
            <div class="space-y-4">
                <div class="flex items-start">
                    <span class="text-sm font-medium text-gray-500 w-32">Datum sjednání:</span>
                    <span class="text-sm text-gray-900"><?php echo date('d.m.Y', strtotime($smlouva['datum_sjednani'])); ?></span>
                </div>
                <div class="flex items-start">
                    <span class="text-sm font-medium text-gray-500 w-32">Datum platnosti:</span>
                    <span class="text-sm text-gray-900"><?php echo date('d.m.Y', strtotime($smlouva['datum_platnosti'])); ?></span>
                </div>
                <div class="flex items-start">
                    <span class="text-sm font-medium text-gray-500 w-32">Záznam Zeteo:</span>
                    <span class="text-sm text-gray-900">
                        <?php echo $smlouva['zaznam_zeteo'] ? 'Ano' : 'Ne'; ?>
                    </span>
                </div>
                <div class="flex items-start">
                    <span class="text-sm font-medium text-gray-500 w-32">Poznámka:</span>
                    <span class="text-sm text-gray-900"><?php echo htmlspecialchars($smlouva['poznamka'] ?: '-'); ?></span>
                </div>
            </div>
        </div>

        <!-- Specifické podmínky produktu (pokud existují) -->
        <?php if (!empty($smlouva['podminky_produktu'])): ?>
            <div class="mt-6 pt-4 border-t border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Specifika produktu</h3>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-2">
                        <?php foreach ($smlouva['podminky_produktu'] as $klic => $hodnota): ?>
                            <?php if (!empty($hodnota) && $hodnota !== 'Ne'): ?>
                                <div class="flex justify-between py-1">
                                    <dt class="text-sm font-medium text-gray-600"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $klic))); ?>:</dt>
                                    <dd class="text-sm text-gray-900"><?php echo htmlspecialchars($hodnota); ?></dd>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </dl>
                </div>
            </div>
        <?php endif; ?>

        <!-- Dokumenty (opraveno – použito $conn) -->
        <?php
        $dokumentyModel = new DokumentyModel($conn);
        $dokumenty = $dokumentyModel->getDokumentyBySmlouva($smlouva['id']);
        $hlavniDokument = !empty($smlouva['cesta_k_souboru']);
        ?>
        <?php if ($hlavniDokument || !empty($dokumenty)): ?>
            <div class="mt-6 pt-4 border-t border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Dokumenty</h3>
                <div class="flex flex-wrap gap-2">
                    <?php if ($hlavniDokument): ?>
                        <a href="<?php echo htmlspecialchars($smlouva['cesta_k_souboru']); ?>" target="_blank"
                            class="inline-flex items-center px-3 py-2 bg-blue-50 text-blue-700 rounded-md hover:bg-blue-100 transition-colors">
                            <!-- SVG ikona -->
                            Hlavní smlouva
                        </a>
                    <?php endif; ?>
                    <?php foreach ($dokumenty as $dok): ?>
                        <?php if ($dok['typ_dokumentu'] !== 'Smlouva' && !empty($dok['cesta_k_souboru'])): ?>
                            <a href="<?php echo htmlspecialchars($dok['cesta_k_souboru']); ?>" target="_blank"
                                class="inline-flex items-center px-3 py-2 bg-gray-50 text-gray-700 rounded-md hover:bg-gray-100 transition-colors">
                                <!-- SVG ikona -->
                                <?php echo htmlspecialchars($dok['typ_dokumentu']); ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sekce provizí -->
    <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-800">Provize ke smlouvě</h3>
            <div class="text-lg">
                <span class="font-medium text-gray-600">Celkem:</span>
                <span class="font-bold text-green-600 ml-2"><?php echo number_format($totalProvize, 2, ',', ' '); ?> Kč</span>
            </div>
        </div>

        <?php if (empty($provize)): ?>
            <p class="text-gray-500 py-4 text-center">K této smlouvě nejsou evidovány žádné provize.</p>
        <?php else: ?>
            <div class="overflow-x-auto border border-gray-200 rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Datum</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Částka</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stav</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Poznámka</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vytvořeno</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($provize as $provizeItem): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo date('d.m.Y', strtotime($provizeItem['datum_provize'] ?? $provizeItem['datum_vytvoreni'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo number_format($provizeItem['castka'], 2, ',', ' '); ?> Kč
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <?php
                                    $stav = $provizeItem['stav'] ?? 'čeká';
                                    $badgeColor = match ($stav) {
                                        'zaplaceno' => 'bg-green-100 text-green-800',
                                        'stornováno' => 'bg-red-100 text-red-800',
                                        default => 'bg-yellow-100 text-yellow-800',
                                    };
                                    ?>
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $badgeColor; ?>">
                                        <?php echo htmlspecialchars(ucfirst($stav)); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($provizeItem['poznamka'] ?: '-'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('d.m.Y', strtotime($provizeItem['datum_vytvoreni'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php
}
?>