<?php
// Diagnostika PHP limitů
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Zkontrolujeme error log
$error_log = ini_get('error_log');
echo "<!-- PHP error_log: $error_log -->";

// Vytvoříme vlastní error log pokud neexistuje
$custom_log = __DIR__ . '/../logs/upload_errors.log';
if (!file_exists(dirname($custom_log))) {
    mkdir(dirname($custom_log), 0755, true);
}
ini_set('error_log', $custom_log);
echo "<!-- Custom error_log: $custom_log -->";

// Vložení login logiky a kontrola přihlášení
include_once __DIR__ . '/../app/includes/login.php';
require_login();

// Vložení hlavičky a připojení k databázi
include_once __DIR__ . '/../app/includes/header.php';
include_once __DIR__ . '/../app/includes/db_connect.php';

// Načtení controllerů a view
include_once __DIR__ . '/../app/controllers/smlouvy_controller.php';
include_once __DIR__ . '/../app/views/smlouvy_view.php';
include_once __DIR__ . '/../app/views/smlouvy_form.php';
include_once __DIR__ . '/../app/views/smlouvy_documents.php';
include_once __DIR__ . '/../app/models/dokumenty_model.php';

// Vytvoření instance controlleru
$controller = new SmlouvyController($conn);

// Zpracování požadavku
$controller->handleRequest();

// Získání dat pro zobrazení
$klienti = $controller->getKlienti();
$produkty = $controller->getProdukty();
$pojistovny = $controller->getPojistovny();
$smlouvy = $controller->getSmlouvy($_GET['search'] ?? '');
?>

<div class="container mx-auto mt-8 p-6 bg-white rounded-lg shadow-lg">
    <h1 class="text-3xl font-bold text-center text-gray-800 mb-6">Správa smluv</h1>

    <!-- Zobrazení zpráv -->
    <?php if ($controller->hasMessage()): ?>
        <div class="p-4 mb-4 rounded-md <?php echo $controller->getMessageType() === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
            <?php echo htmlspecialchars($controller->getMessage()); ?>
        </div>
    <?php endif; ?>

    <!-- Zbytek kódu zůstává podobný, jen voláme naše nové funkce -->
    <div class="flex justify-end mb-4">
        <button id="open-add-modal-btn" class="py-2 px-4 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200">
            Přidat novou smlouvu
        </button>
    </div>

    <!-- Vyhledávací pole -->
    <div class="bg-gray-50 p-4 rounded-md border border-gray-200 mb-6 flex justify-between items-center">
        <div id="search-info">
            <h2 class="text-lg font-semibold text-gray-800">Filtry a vyhledávání</h2>
            <?php if (!empty($_GET['search'])): ?>
                <p class="text-sm text-gray-500 mt-1">
                    Vyhledávání: <span class="font-bold text-blue-600">"<?php echo htmlspecialchars($_GET['search']); ?>"</span>
                    (nalezeno: <span class="font-bold text-blue-600"><?php echo count($smlouvy); ?></span>)
                </p>
            <?php endif; ?>
        </div>
        <form action="smlouvy.php" method="get" class="flex-grow max-w-sm ml-4">
            <label for="search" class="sr-only">Vyhledávání smlouvy</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <input type="text" name="search" id="search" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Vyhledat smlouvu..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            </div>
        </form>
    </div>

    <!-- Seznam smluv -->
    <div class="mt-8">
        <h2 class="text-xl font-semibold mb-4">Seznam smluv</h2>
        <div id="contracts-table-container">
            <?php
            // Zavoláme naši novou funkci pro zobrazení tabulky
            if (function_exists('displaySmlouvyTable')) {
                displaySmlouvyTable($smlouvy, $conn);
            } else {
                echo "Funkce pro zobrazení tabulky není dostupná.";
            }
            ?>
        </div>
    </div>
</div>


<?php
// Zobrazení modálních oken
if (function_exists('displayAddModal')) {
    displayAddModal($klienti, $produkty, $pojistovny, $conn);
}
if (function_exists('displayEditModal')) {
    displayEditModal($klienti, $produkty, $pojistovny, $conn);
}

?>

<script>
    let dokumentCounter = 1;

    function addDokument() {
        const container = document.getElementById('dokumenty-container');
        const newRow = document.createElement('div');
        newRow.className = 'dokument-row mb-4 p-4 border border-gray-200 rounded-lg bg-white';
        newRow.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Typ dokumentu *</label>
                <input type="text" name="dokument_typ[${dokumentCounter}]" list="typy-dokumentu" 
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 dokument-typ"
                       placeholder="Začněte psát pro návrhy..." required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Soubor *</label>
                <input type="file" name="dokument_soubor[${dokumentCounter}]" 
                       class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                       accept=".pdf,.jpg,.jpeg,.png,.gif" required>
                <p class="text-xs text-gray-500 mt-1">PDF, JPG, PNG, GIF (max. 10MB)</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Popis</label>
                <input type="text" name="dokument_popis[${dokumentCounter}]" 
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2"
                       placeholder="Volitelný popis...">
                <button type="button" onclick="removeDokument(this)" class="mt-2 text-red-600 hover:text-red-800 text-sm">Odstranit</button>
            </div>
        </div>
    `;
        container.appendChild(newRow);
        dokumentCounter++;
    }

    function removeDokument(button) {
        const row = button.closest('.dokument-row');
        if (document.querySelectorAll('.dokument-row').length > 1) {
            row.remove();
        } else {
            row.querySelectorAll('input').forEach(input => {
                if (input.type !== 'file') {
                    input.value = '';
                }
            });
            row.querySelector('input[type="file"]').value = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded - initializing modal functionality');

        // ✅ VYLEPŠENÁ VALIDACE - STEJNÁ JAKO V PŘEDCHOZÍM FUNKČNÍM KÓDU
        function showError(fieldId, message) {
            const errorElement = document.getElementById(fieldId + '_error');
            const fieldElement = document.getElementById(fieldId);

            if (errorElement && fieldElement) {
                errorElement.textContent = message;
                errorElement.classList.remove('hidden');
                fieldElement.classList.add('border-red-500', 'border-2');
                fieldElement.setAttribute('aria-invalid', 'true');
                fieldElement.setAttribute('aria-describedby', fieldId + '_error');
            }
        }

        function hideError(fieldId) {
            const errorElement = document.getElementById(fieldId + '_error');
            const fieldElement = document.getElementById(fieldId);

            if (errorElement && fieldElement) {
                errorElement.classList.add('hidden');
                fieldElement.classList.remove('border-red-500', 'border-2');
                fieldElement.removeAttribute('aria-invalid');
                fieldElement.removeAttribute('aria-describedby');
            }
        }

        function validateRequiredField(fieldId, fieldName) {
            const value = document.getElementById(fieldId).value.trim();
            if (!value) {
                showError(fieldId, `${fieldName} je povinné pole`);
                return false;
            } else {
                hideError(fieldId);
                return true;
            }
        }

        function validateAddForm() {
            let isValid = true;
            if (!validateRequiredField('cislo_smlouvy', 'Číslo smlouvy')) isValid = false;
            if (!validateRequiredField('datum_sjednani', 'Datum sjednání')) isValid = false;
            if (!validateRequiredField('datum_platnosti', 'Datum platnosti')) isValid = false;
            return isValid;
        }

        function validateEditForm() {
            let isValid = true;
            if (!validateRequiredField('edit_cislo_smlouvy', 'Číslo smlouvy')) isValid = false;
            if (!validateRequiredField('edit_datum_sjednani', 'Datum sjednání')) isValid = false;
            if (!validateRequiredField('edit_datum_platnosti', 'Datum platnosti')) isValid = false;
            return isValid;
        }

        // Funkce pro kontrolu duplicity čísla smlouvy
        function checkDuplicateCisloSmlouvy(cisloSmlouvy, currentId = null) {
            return new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', '../app/controllers/check_duplicate_smlouva.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                xhr.onload = function() {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            resolve(response);
                        } catch (e) {
                            reject('Chyba při parsování odpovědi');
                        }
                    } else {
                        reject('Chyba serveru');
                    }
                };

                xhr.onerror = function() {
                    reject('Chyba připojení');
                };

                const data = `cislo_smlouvy=${encodeURIComponent(cisloSmlouvy)}${currentId ? `&current_id=${currentId}` : ''}`;
                xhr.send(data);
            });
        }

        // Real-time validace pro přidání smlouvy
        const cisloSmlouvyInput = document.getElementById('cislo_smlouvy');
        const datumSjednaniInput = document.getElementById('datum_sjednani');
        const datumPlatnostiInput = document.getElementById('datum_platnosti');

        if (cisloSmlouvyInput) {
            cisloSmlouvyInput.addEventListener('blur', function() {
                const cislo = this.value.trim();
                if (cislo) {
                    hideError('cislo_smlouvy');
                    showError('cislo_smlouvy', 'Kontroluji duplicitu...');

                    checkDuplicateCisloSmlouvy(cislo).then(response => {
                        hideError('cislo_smlouvy');
                        if (response.duplicate) {
                            showError('cislo_smlouvy', 'Smlouva s tímto číslem již existuje v databázi');
                        }
                    }).catch(error => {
                        hideError('cislo_smlouvy');
                        console.error('Chyba při kontrole duplicity:', error);
                    });
                }
            });

            cisloSmlouvyInput.addEventListener('input', function() {
                hideError('cislo_smlouvy');
                validateRequiredField('cislo_smlouvy', 'Číslo smlouvy');
            });
        }

        // Real-time validace pro editaci smlouvy
        const editCisloSmlouvyInput = document.getElementById('edit_cislo_smlouvy');
        if (editCisloSmlouvyInput) {
            editCisloSmlouvyInput.addEventListener('blur', function() {
                const cislo = this.value.trim();
                const currentId = document.getElementById('edit_id').value;
                if (cislo && currentId) {
                    hideError('edit_cislo_smlouvy');
                    showError('edit_cislo_smlouvy', 'Kontroluji duplicitu...');

                    checkDuplicateCisloSmlouvy(cislo, currentId).then(response => {
                        hideError('edit_cislo_smlouvy');
                        if (response.duplicate) {
                            showError('edit_cislo_smlouvy', 'Smlouva s tímto číslem již existuje v databázi');
                        }
                    }).catch(error => {
                        hideError('edit_cislo_smlouvy');
                        console.error('Chyba při kontrole duplicity:', error);
                    });
                }
            });

            editCisloSmlouvyInput.addEventListener('input', function() {
                hideError('edit_cislo_smlouvy');
                validateRequiredField('edit_cislo_smlouvy', 'Číslo smlouvy');
            });
        }

        // Validace před odesláním - přidání
        const addForm = document.getElementById('add-smlouva-form');
        const addSubmitBtn = document.getElementById('add-submit-btn');

        if (addForm && addSubmitBtn) {
            addForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                // Zakázat tlačítko
                addSubmitBtn.disabled = true;
                addSubmitBtn.textContent = 'Kontroluji...';

                let canSubmit = true;

                // Validace povinných polí
                if (!validateAddForm()) {
                    canSubmit = false;
                }

                // Kontrola duplicity před odesláním
                const cisloSmlouvy = document.getElementById('cislo_smlouvy').value.trim();
                if (cisloSmlouvy && canSubmit) {
                    try {
                        const duplicateCheck = await checkDuplicateCisloSmlouvy(cisloSmlouvy);
                        if (duplicateCheck.duplicate) {
                            showError('cislo_smlouvy', 'Smlouva s tímto číslem již existuje v databázi');
                            canSubmit = false;
                        }
                    } catch (error) {
                        console.error('Chyba při kontrole duplicity:', error);
                        showError('cislo_smlouvy', 'Chyba při kontrole duplicity');
                        canSubmit = false;
                    }
                }

                // Obnovit tlačítko
                addSubmitBtn.disabled = false;
                addSubmitBtn.textContent = 'Přidat smlouvu';

                if (canSubmit) {
                    addForm.submit();
                } else {
                    const firstError = document.querySelector('.border-red-500');
                    if (firstError) {
                        firstError.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                        firstError.focus();
                    }
                }
            });
        }

        // Validace před odesláním - editace
        const editForm = document.getElementById('edit-smlouva-form');
        const editSubmitBtn = document.getElementById('edit-submit-btn');

        if (editForm && editSubmitBtn) {
            editForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                // Zakázat tlačítko
                editSubmitBtn.disabled = true;
                editSubmitBtn.textContent = 'Kontroluji...';

                let canSubmit = true;

                // Validace povinných polí
                if (!validateEditForm()) {
                    canSubmit = false;
                }

                // Kontrola duplicity před odesláním
                const cisloSmlouvy = document.getElementById('edit_cislo_smlouvy').value.trim();
                const currentId = document.getElementById('edit_id').value;

                if (cisloSmlouvy && currentId && canSubmit) {
                    try {
                        const duplicateCheck = await checkDuplicateCisloSmlouvy(cisloSmlouvy, currentId);
                        if (duplicateCheck.duplicate) {
                            showError('edit_cislo_smlouvy', 'Smlouva s tímto číslem již existuje v databázi');
                            canSubmit = false;
                        }
                    } catch (error) {
                        console.error('Chyba při kontrole duplicity:', error);
                        showError('edit_cislo_smlouvy', 'Chyba při kontrole duplicity');
                        canSubmit = false;
                    }
                }

                // Obnovit tlačítko
                editSubmitBtn.disabled = false;
                editSubmitBtn.textContent = 'Uložit změny';

                if (canSubmit) {
                    editForm.submit();
                } else {
                    const firstError = document.querySelector('.border-red-500');
                    if (firstError) {
                        firstError.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                        firstError.focus();
                    }
                }
            });
        }

        // Real-time validace povinných polí při psaní
        document.querySelectorAll('.validation-field').forEach(field => {
            field.addEventListener('input', function() {
                const fieldId = this.id;
                const fieldName = this.previousElementSibling.textContent.replace('*', '').trim();
                validateRequiredField(fieldId, fieldName);
            });
        });

        // Zbytek kódu pro modální okna a dynamická pole zůstává
        const openAddModalBtn = document.getElementById('open-add-modal-btn');
        const addModal = document.getElementById('add-modal');
        const closeAddModalBtn = document.getElementById('close-add-modal-btn');

        const editModal = document.getElementById('edit-modal');
        const closeEditModalBtn = document.getElementById('close-modal-btn');

        // Otevření modálního okna pro přidání
        if (openAddModalBtn && addModal) {
            openAddModalBtn.addEventListener('click', () => {
                addModal.classList.remove('hidden');
            });
        }

        // Zavření modálního okna pro přidání
        if (closeAddModalBtn && addModal) {
            closeAddModalBtn.addEventListener('click', () => {
                addModal.classList.add('hidden');
            });
        }

        // Zavření modálního okna pro editaci
        if (closeEditModalBtn && editModal) {
            closeEditModalBtn.addEventListener('click', () => {
                editModal.classList.add('hidden');
            });
        }
        /*
                // Zavření modálního okna při kliknutí mimo obsah
                if (addModal) {
                    addModal.addEventListener('click', function(e) {
                        if (e.target === addModal) {
                            addModal.classList.add('hidden');
                        }
                    });
                }

                if (editModal) {
                    editModal.addEventListener('click', function(e) {
                        if (e.target === editModal) {
                            editModal.classList.add('hidden');
                        }
                    });
                }
        */
        // Zpracování tlačítek pro editaci v tabulce
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', async function() {
                const row = this.closest('tr');
                const data = row.dataset;

                // Resetujte formulář před naplněním novými daty
                document.getElementById('edit-smlouva-form').reset();

                // Vyplnění základních polí
                document.getElementById('edit_id').value = data.id;
                document.getElementById('edit_klient_id').value = data.klientId;
                document.getElementById('edit_cislo_smlouvy').value = data.cisloSmlouvy;
                document.getElementById('edit_produkt_id').value = data.produktId;
                document.getElementById('edit_pojistovna_id').value = data.pojistovnaId;
                document.getElementById('edit_datum_sjednani').value = data.datumSjednani;
                document.getElementById('edit_datum_platnosti').value = data.datumPlatnosti;
                document.getElementById('edit_zaznam_zeteo').checked = data.zaznamZeteo === '1';
                document.getElementById('edit_poznamka').value = data.poznamka;
                document.getElementById('stara_cesta_k_souboru').value = data.cestaKSouboru;

                // Zobrazení modalu
                editModal.classList.remove('hidden');

                // Reset chybových zpráv
                document.querySelectorAll('[id$="_error"]').forEach(errorElement => {
                    errorElement.classList.add('hidden');
                });
                document.querySelectorAll('.validation-field').forEach(field => {
                    field.classList.remove('border-red-500', 'border-2');
                });

                // Po vyplnění základních dat aktualizujeme dynamická pole
                setTimeout(() => {
                    const produktId = document.getElementById('edit_produkt_id').value;
                    const pojistovnaId = document.getElementById('edit_pojistovna_id').value;
                    updateDynamicFields(produktId, pojistovnaId, true);

                    // Naplníme dynamická pole daty z podmínek
                    if (data.podminkyProduktu) {
                        try {
                            const podminky = JSON.parse(data.podminkyProduktu);
                            fillDynamicFields(produktId, pojistovnaId, podminky, true);
                        } catch (e) {
                            console.error('Chyba při načítání podmínek:', e);
                        }
                    }
                }, 100);
            });
        });

        // Funkce pro resetování všech dynamických polí
        function resetDynamicFields(isEdit = false) {
            const prefix = isEdit ? 'edit_' : '';

            // Reset všech typů polí v dynamických sekcích
            const dynamicContainer = document.getElementById(`${prefix}dynamic-fields`);
            if (!dynamicContainer) return;

            // Reset checkboxů
            const checkboxes = dynamicContainer.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });

            // Reset textových polí
            const textFields = dynamicContainer.querySelectorAll('input[type="text"]');
            textFields.forEach(field => {
                field.value = '';
            });

            // Reset textareas
            const textareas = dynamicContainer.querySelectorAll('textarea');
            textareas.forEach(textarea => {
                textarea.value = '';
            });

            // Reset selectů - nastavit na první option
            const selects = dynamicContainer.querySelectorAll('select');
            selects.forEach(select => {
                if (select.options.length > 0) {
                    select.selectedIndex = 0;
                }
            });

            // Reset date polí
            const dateFields = dynamicContainer.querySelectorAll('input[type="date"]');
            dateFields.forEach(field => {
                field.value = '';
            });

            console.log(`Resetováno ${checkboxes.length} checkboxů, ${textFields.length} textových polí, ${textareas.length} textareas, ${selects.length} selectů, ${dateFields.length} date polí`);
        }

        // Dynamické zobrazování podmínek podle produktu a pojišťovny
        function updateDynamicFields(produktId, pojistovnaId, isEdit = false) {
            const prefix = isEdit ? 'edit_' : '';
            console.log(`Aktualizace dynamických polí: produkt=${produktId}, pojišťovna=${pojistovnaId}, edit=${isEdit}`);

            // Nejprve resetujte všechna dynamická pole
            resetDynamicFields(isEdit);

            // Skryj všechny dynamické bloky
            const allDynamicBlocks = document.querySelectorAll(`#${prefix}dynamic-fields > div`);
            allDynamicBlocks.forEach(block => {
                block.classList.add('hidden');
            });

            // Zobraz příslušný blok podle produktu
            const productSlug = getProductSlug(produktId);
            const productBlock = document.getElementById(`${prefix}${productSlug}_fields`);

            if (productBlock) {
                console.log(`Zobrazuji blok pro produkt: ${productSlug}`);
                productBlock.classList.remove('hidden');

                // Pro životní pojištění zobraz příslušnou pojišťovnu
                if (produktId == 11) {
                    const insurerBlocks = productBlock.querySelectorAll('div[data-pojistovna-id]');
                    insurerBlocks.forEach(block => {
                        block.classList.add('hidden');
                    });

                    const specificInsurerBlock = productBlock.querySelector(`div[data-pojistovna-id="${pojistovnaId}"]`);
                    if (specificInsurerBlock) {
                        specificInsurerBlock.classList.remove('hidden');
                        console.log(`Zobrazuji blok pro pojišťovnu: ${pojistovnaId}`);
                    }
                }
            } else {
                console.log(`Blok pro produkt ${productSlug} nebyl nalezen`);
            }

            // Debug výpis
            setTimeout(() => {
                const visibleBlocks = document.querySelectorAll(`#${prefix}dynamic-fields > div:not(.hidden)`);
                console.log(`Po aktualizaci je viditelných ${visibleBlocks.length} bloků:`);
                visibleBlocks.forEach(block => {
                    console.log(`- ${block.id}`);
                });
            }, 50);
        }

        // Pomocná funkce pro získání slug produktu
        function getProductSlug(produktId) {
            switch (produktId) {
                case '11':
                    return 'zivotni_pojisteni';
                case '2':
                    return 'cestovni_pojisteni';
                case '1':
                    return 'autopojisteni';
                case '8':
                    return 'nemovitost';
                case '12':
                    return 'bytovy';
                default:
                    return '';
            }
        }

        // Funkce pro naplnění dynamických polí z dat
        function fillDynamicFields(produktId, pojistovnaId, podminky, isEdit = false) {
            const prefix = isEdit ? 'edit_' : '';

            switch (produktId) {
                case '11': // Životní pojištění
                    if (document.getElementById(`${prefix}dip`)) {
                        document.getElementById(`${prefix}dip`).checked = podminky.dip === 'Ano';
                    }
                    if (document.getElementById(`${prefix}detske`)) {
                        document.getElementById(`${prefix}detske`).checked = podminky.detske === 'Ano';
                    }
                    // Naplnění podtypu podle pojišťovny
                    switch (pojistovnaId) {
                        case '1':
                            if (document.getElementById(`${prefix}podtyp_allianz`)) {
                                document.getElementById(`${prefix}podtyp_allianz`).value = podminky.podtyp || '';
                            }
                            break;
                        case '2':
                            if (document.getElementById(`${prefix}podtyp_cpp`)) {
                                document.getElementById(`${prefix}podtyp_cpp`).value = podminky.podtyp || '';
                            }
                            break;
                        case '3':
                            if (document.getElementById(`${prefix}podtyp_kooperativa`)) {
                                document.getElementById(`${prefix}podtyp_kooperativa`).value = podminky.podtyp || '';
                            }
                            break;
                        case '4':
                            if (document.getElementById(`${prefix}podtyp_maxima`)) {
                                document.getElementById(`${prefix}podtyp_maxima`).value = podminky.podtyp || '';
                            }
                            break;
                    }
                    break;

                case '2': // Cestovní pojištění
                    if (document.getElementById(`${prefix}cestovni_zacatek`)) {
                        document.getElementById(`${prefix}cestovni_zacatek`).value = podminky.zacatek || '';
                    }
                    if (document.getElementById(`${prefix}cestovni_konec`)) {
                        document.getElementById(`${prefix}cestovni_konec`).value = podminky.konec || '';
                    }
                    break;

                case '1': // Autopojištění
                    if (document.getElementById(`${prefix}pov`)) {
                        document.getElementById(`${prefix}pov`).checked = podminky.pov === 'Ano';
                    }
                    if (document.getElementById(`${prefix}hav`)) {
                        document.getElementById(`${prefix}hav`).checked = podminky.hav === 'Ano';
                    }
                    if (document.getElementById(`${prefix}dalsi_pripojisteni`)) {
                        document.getElementById(`${prefix}dalsi_pripojisteni`).value = podminky.dalsi_pripojisteni || '';
                    }
                    break;

                case '8': // Pojištění nemovitosti
                    if (document.getElementById(`${prefix}nemovitost_domacnost`)) {
                        document.getElementById(`${prefix}nemovitost_domacnost`).checked = podminky.domacnost === 'Ano';
                    }
                    if (document.getElementById(`${prefix}nemovitost_stavba`)) {
                        document.getElementById(`${prefix}nemovitost_stavba`).checked = podminky.stavba === 'Ano';
                    }
                    if (document.getElementById(`${prefix}nemovitost_odpovednost`)) {
                        document.getElementById(`${prefix}nemovitost_odpovednost`).checked = podminky.odpovednost === 'Ano';
                    }
                    if (document.getElementById(`${prefix}nemovitost_asistence`)) {
                        document.getElementById(`${prefix}nemovitost_asistence`).checked = podminky.asistence === 'Ano';
                    }
                    if (document.getElementById(`${prefix}nemovitost_nop`)) {
                        document.getElementById(`${prefix}nemovitost_nop`).checked = podminky.nop === 'Ano';
                    }
                    if (document.getElementById(`${prefix}nemovitost_nop_poznamka`)) {
                        document.getElementById(`${prefix}nemovitost_nop_poznamka`).value = podminky.nop_poznamka || '';
                    }
                    break;

                case '12': // Bytový dům
                    if (document.getElementById(`${prefix}bytovy_domacnost`)) {
                        document.getElementById(`${prefix}bytovy_domacnost`).checked = podminky.domacnost === 'Ano';
                    }
                    if (document.getElementById(`${prefix}bytovy_stavba`)) {
                        document.getElementById(`${prefix}bytovy_stavba`).checked = podminky.stavba === 'Ano';
                    }
                    if (document.getElementById(`${prefix}bytovy_odpovednost`)) {
                        document.getElementById(`${prefix}bytovy_odpovednost`).checked = podminky.odpovednost === 'Ano';
                    }
                    if (document.getElementById(`${prefix}bytovy_asistence`)) {
                        document.getElementById(`${prefix}bytovy_asistence`).checked = podminky.asistence === 'Ano';
                    }
                    if (document.getElementById(`${prefix}bytovy_nop`)) {
                        document.getElementById(`${prefix}bytovy_nop`).checked = podminky.nop === 'Ano';
                    }
                    if (document.getElementById(`${prefix}bytovy_nop_poznamka`)) {
                        document.getElementById(`${prefix}bytovy_nop_poznamka`).value = podminky.nop_poznamka || '';
                    }
                    break;
            }
        }

        // Inicializace pro přidávací formulář
        const produktSelect = document.getElementById('produkt_id');
        const pojistovnaSelect = document.getElementById('pojistovna_id');

        if (produktSelect && pojistovnaSelect) {
            produktSelect.addEventListener('change', function() {
                updateDynamicFields(this.value, pojistovnaSelect.value, false);
            });

            pojistovnaSelect.addEventListener('change', function() {
                updateDynamicFields(produktSelect.value, this.value, false);
            });

            // Inicializace při načtení
            updateDynamicFields(produktSelect.value, pojistovnaSelect.value, false);
        }

        // Inicializace pro editační formulář
        const editProduktSelect = document.getElementById('edit_produkt_id');
        const editPojistovnaSelect = document.getElementById('edit_pojistovna_id');

        if (editProduktSelect && editPojistovnaSelect) {
            editProduktSelect.addEventListener('change', function() {
                updateDynamicFields(this.value, editPojistovnaSelect.value, true);
            });

            editPojistovnaSelect.addEventListener('change', function() {
                updateDynamicFields(editProduktSelect.value, this.value, true);
            });
        }


        // Otevření modálního okna pro přidání
        if (openAddModalBtn && addModal) {
            openAddModalBtn.addEventListener('click', () => {
                // Resetujte formulář
                document.getElementById('add-smlouva-form').reset();

                // Resetujte dynamická pole
                resetDynamicFields(false);

                // Reset chybových zpráv
                document.querySelectorAll('[id$="_error"]').forEach(errorElement => {
                    errorElement.classList.add('hidden');
                });
                document.querySelectorAll('.validation-field').forEach(field => {
                    field.classList.remove('border-red-500', 'border-2');
                });

                // Aktualizujte dynamická pole podle výchozích hodnot
                const produktId = document.getElementById('produkt_id').value;
                const pojistovnaId = document.getElementById('pojistovna_id').value;
                updateDynamicFields(produktId, pojistovnaId, false);

                addModal.classList.remove('hidden');
            });
        }

        console.log('Modal functionality initialized');


        // Inicializace dokumentů pro přidání
        const addModalBtn = document.getElementById('open-add-modal-btn');
        if (addModalBtn) {
            addModalBtn.addEventListener('click', function() {
                dokumentCounter = 1;
            });
        }

        // Inicializace dokumentů pro editaci
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                dokumentCounter = 1;
                // Zde můžete načíst existující dokumenty pro editaci pomocí AJAXu
            });
        });

        // Inicializace při načtení stránky
        document.addEventListener('DOMContentLoaded', function() {
            // Resetovat počítadlo při otevření modálního okna
            document.getElementById('open-add-modal-btn').addEventListener('click', function() {
                okumentCounter = 1;
            });
        });
    });
</script>

<?php

// Vložení patičky
include_once __DIR__ . '/../app/includes/footer.php';
?>