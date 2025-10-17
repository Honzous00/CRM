<?php
function displayDocumentManager($smlouva_id = null)
{
    global $conn;

    if (!$smlouva_id) {
        return;
    }

    $dokumentyModel = new DokumentyModel($conn);
    $dokumenty = $dokumentyModel->getDokumentyBySmlouva($smlouva_id);
    $typyDokumentu = $dokumentyModel->getTypyDokumentu();
?>

    <div class="mt-6 border-t pt-6">
        <h3 class="text-lg font-semibold mb-4">Dokumenty smlouvy</h3>

        <!-- Hlavní smlouva -->
        <div class="mb-6 p-4 bg-blue-50 rounded-lg">
            <h4 class="font-medium text-blue-800 mb-2">Hlavní smlouva</h4>
            <?php displayDocumentRow(0, [
                'typ_dokumentu' => 'Smlouva',
                'povinny' => true
            ], true); ?>
        </div>

        <!-- Přílohy -->
        <div class="mb-4">
            <h4 class="font-medium text-gray-700 mb-2">Přílohy</h4>
            <div id="prilohy-container">
                <?php
                $index = 1;
                foreach ($dokumenty as $dokument) {
                    if ($dokument['typ_dokumentu'] !== 'Smlouva') {
                        displayDocumentRow($index, $dokument, false);
                        $index++;
                    }
                }
                ?>

                <!-- Prázdný řádek pro novou přílohu -->
                <?php displayDocumentRow($index, null, false); ?>
            </div>

            <button type="button" onclick="addAnotherAttachment()" class="mt-2 px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors">
                + Přidat další přílohu
            </button>
        </div>
    </div>

    <script>
        function addAnotherAttachment() {
            const container = document.getElementById('prilohy-container');
            const rows = container.querySelectorAll('.document-row');
            const newIndex = rows.length;

            // Vytvoření nového řádku
            const newRow = document.createElement('div');
            newRow.className = 'document-row mb-4 p-4 border border-gray-200 rounded-lg';
            newRow.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Typ dokumentu</label>
                    <input type="text" name="dokument_typ[${newIndex}]" list="typy-dokumentu" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Soubor</label>
                    <input type="file" name="dokument_soubor[${newIndex}]" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Popis</label>
                    <input type="text" name="dokument_popis[${newIndex}]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    <button type="button" onclick="removeAttachment(this)" class="mt-2 text-red-600 hover:text-red-800 text-sm">Odstranit</button>
                </div>
            </div>
        `;

            container.appendChild(newRow);
        }

        function removeAttachment(button) {
            const row = button.closest('.document-row');
            row.remove();
        }
    </script>

    <datalist id="typy-dokumentu">
        <?php foreach ($typyDokumentu as $typ): ?>
            <option value="<?php echo htmlspecialchars($typ); ?>">
            <?php endforeach; ?>
    </datalist>
<?php
}

function displayDocumentRow($index, $dokument = null, $isMain = false)
{
    $typ = $dokument ? $dokument['typ_dokumentu'] : '';
    $popis = $dokument ? $dokument['poznamka'] : '';
    $soubor = $dokument ? $dokument['nazev_souboru'] : '';
?>

    <div class="document-row mb-4 p-4 border border-gray-200 rounded-lg <?php echo $isMain ? 'bg-blue-50' : ''; ?>">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Typ dokumentu</label>
                <?php if ($isMain): ?>
                    <input type="text" value="Smlouva" readonly class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm p-2">
                    <input type="hidden" name="dokument_typ[<?php echo $index; ?>]" value="Smlouva">
                <?php else: ?>
                    <input type="text" name="dokument_typ[<?php echo $index; ?>]" value="<?php echo htmlspecialchars($typ); ?>" list="typy-dokumentu" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                <?php endif; ?>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Soubor</label>
                <?php if ($dokument && !empty($soubor)): ?>
                    <div class="mt-1">
                        <a href="<?php echo htmlspecialchars($dokument['cesta_k_souboru']); ?>" target="_blank" class="text-blue-600 hover:text-blue-800">
                            <?php echo htmlspecialchars($soubor); ?>
                        </a>
                        <?php if (!$isMain): ?>
                            <br>
                            <label class="inline-flex items-center mt-1">
                                <input type="checkbox" name="dokument_smazat[<?php echo $dokument['id']; ?>]" class="text-red-600">
                                <span class="ml-2 text-sm text-red-600">Smazat</span>
                            </label>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <input type="file" name="dokument_soubor[<?php echo $index; ?>]" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                <?php endif; ?>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Popis</label>
                <input type="text" name="dokument_popis[<?php echo $index; ?>]" value="<?php echo htmlspecialchars($popis); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                <?php if (!$isMain && !$dokument): ?>
                    <button type="button" onclick="removeAttachment(this)" class="mt-2 text-red-600 hover:text-red-800 text-sm">Odstranit</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php
}
?>