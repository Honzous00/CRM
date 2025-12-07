<?php
// public/users.php
include_once __DIR__ . '/../app/includes/login.php';
require_admin(); // Pouze admin má přístup

include_once __DIR__ . '/../app/includes/header.php';
include_once __DIR__ . '/../app/includes/db_connect.php';

// Zpracování akcí
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? 0;
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $is_admin = isset($_POST['is_admin']) ? 1 : 0;
        $aktivni = isset($_POST['aktivni']) ? 1 : 0;

        // Validace
        if (empty($username)) {
            $message = 'Uživatelské jméno je povinné';
            $message_type = 'error';
        } elseif ($action === 'add' && empty($password)) {
            $message = 'Heslo je povinné pro nového uživatele';
            $message_type = 'error';
        } else {
            if ($action === 'add') {
                // Přidání nového uživatele
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, password_hash, is_admin, aktivni) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssii", $username, $password_hash, $is_admin, $aktivni);

                if ($stmt->execute()) {
                    $message = 'Uživatel byl úspěšně přidán';
                    $message_type = 'success';
                    $action = 'list'; // Vrátit se na seznam
                } else {
                    $message = 'Chyba: ' . ($stmt->error ?: $conn->error);
                    $message_type = 'error';
                }
            } elseif ($action === 'edit' && $id) {
                // Editace uživatele
                if (!empty($password)) {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET username = ?, password_hash = ?, is_admin = ?, aktivni = ? WHERE id = ?");
                    $stmt->bind_param("ssiii", $username, $password_hash, $is_admin, $aktivni, $id);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET username = ?, is_admin = ?, aktivni = ? WHERE id = ?");
                    $stmt->bind_param("siii", $username, $is_admin, $aktivni, $id);
                }

                if ($stmt->execute()) {
                    $message = 'Uživatel byl úspěšně upraven';
                    $message_type = 'success';
                    $action = 'list'; // Vrátit se na seznam
                } else {
                    $message = 'Chyba: ' . ($stmt->error ?: $conn->error);
                    $message_type = 'error';
                }
            }
        }
    }
}

// Akce smazání (deaktivace)
if ($action === 'delete' && $id) {
    // Zabránit smazání vlastního účtu
    if ($id == $_SESSION['user_id']) {
        $message = 'Nemůžete deaktivovat svůj vlastní účet';
        $message_type = 'error';
    } else {
        $stmt = $conn->prepare("UPDATE users SET aktivni = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $message = 'Uživatel byl deaktivován';
            $message_type = 'success';
        } else {
            $message = 'Chyba při deaktivaci';
            $message_type = 'error';
        }
        $action = 'list';
    }
}

// Akce aktivace
if ($action === 'activate' && $id) {
    $stmt = $conn->prepare("UPDATE users SET aktivni = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $message = 'Uživatel byl aktivován';
        $message_type = 'success';
    } else {
        $message = 'Chyba při aktivaci';
        $message_type = 'error';
    }
    $action = 'list';
}

// Načtení seznamu uživatelů
$users = [];
if ($action === 'list') {
    $result = $conn->query("SELECT id, username, is_admin, datum_vytvoreni, posledni_prihlaseni, aktivni FROM users ORDER BY id DESC");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
}

// Načtení dat pro editaci
$edit_user = null;
if ($action === 'edit' && $id) {
    $stmt = $conn->prepare("SELECT id, username, is_admin, aktivni FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $edit_user = $result->fetch_assoc();
    } else {
        $message = 'Uživatel nenalezen';
        $message_type = 'error';
        $action = 'list';
    }
}
?>

<div class="container mx-auto mt-8 px-4">
    <h1 class="text-4xl font-bold text-gray-800 mb-6">Správa uživatelů</h1>

    <?php if ($message): ?>
        <div class="mb-4 p-4 rounded-md <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($action === 'list'): ?>
        <!-- Tlačítko pro přidání uživatele -->
        <div class="mb-6 flex justify-between items-center">
            <h2 class="text-2xl font-semibold text-gray-700">Seznam uživatelů</h2>
            <a href="?action=add" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-200">
                <i class="fas fa-user-plus mr-2"></i>Přidat uživatele
            </a>
        </div>

        <!-- Tabulka uživatelů -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Uživatelské jméno</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vytvořen</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Poslední přihlášení</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stav</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Akce</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">Žádní uživatelé nenalezeni</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $user['id']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['username']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $user['is_admin'] ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                                        <?php echo $user['is_admin'] ? 'Administrátor' : 'Uživatel'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('d.m.Y H:i', strtotime($user['datum_vytvoreni'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $user['posledni_prihlaseni'] ? date('d.m.Y H:i', strtotime($user['posledni_prihlaseni'])) : '—'; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $user['aktivni'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $user['aktivni'] ? 'Aktivní' : 'Neaktivní'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="?action=edit&id=<?php echo $user['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3" title="Upravit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($user['aktivni']): ?>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <a href="?action=delete&id=<?php echo $user['id']; ?>" onclick="return confirm('Opravdu chcete deaktivovat tohoto uživatele?')" class="text-red-600 hover:text-red-900" title="Deaktivovat">
                                                <i class="fas fa-user-times"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-gray-400 cursor-not-allowed" title="Nemůžete deaktivovat vlastní účet">
                                                <i class="fas fa-user-times"></i>
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <a href="?action=activate&id=<?php echo $user['id']; ?>" class="text-green-600 hover:text-green-900" title="Aktivovat">
                                            <i class="fas fa-user-check"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <?php elseif ($action === 'add' || $action === 'edit'): ?>
        <!-- Formulář pro přidání/úpravu uživatele -->
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-semibold text-gray-800 mb-6">
                    <?php echo $action === 'add' ? 'Přidat nového uživatele' : 'Upravit uživatele'; ?>
                </h2>

                <form method="POST" action="?action=<?php echo $action; ?><?php echo $edit_user ? '&id=' . $edit_user['id'] : ''; ?>" class="space-y-6">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                            Uživatelské jméno *
                        </label>
                        <input type="text" id="username" name="username" required
                            value="<?php echo htmlspecialchars($edit_user['username'] ?? ''); ?>"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-3">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                            <?php if ($action === 'add'): ?>
                                Heslo *
                            <?php else: ?>
                                Nové heslo (nechte prázdné pro zachování stávajícího)
                            <?php endif; ?>
                        </label>
                        <input type="password" id="password" name="password"
                            <?php echo $action === 'add' ? 'required' : ''; ?>
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-3">
                        <?php if ($action === 'edit'): ?>
                            <p class="mt-1 text-sm text-gray-500">Pokud nechcete měnit heslo, ponechte pole prázdné.</p>
                        <?php endif; ?>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center">
                            <input type="checkbox" id="is_admin" name="is_admin" value="1"
                                <?php echo ($edit_user['is_admin'] ?? 0) ? 'checked' : ''; ?>
                                class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                            <label for="is_admin" class="ml-2 block text-sm text-gray-700">
                                Administrátorská práva
                            </label>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" id="aktivni" name="aktivni" value="1"
                                <?php echo !isset($edit_user) || ($edit_user['aktivni'] ?? 1) ? 'checked' : ''; ?>
                                class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                            <label for="aktivni" class="ml-2 block text-sm text-gray-700">
                                Aktivní účet
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-between pt-4">
                        <a href="?action=list" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-6 rounded-lg transition-colors duration-200">
                            Zpět na seznam
                        </a>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition-colors duration-200">
                            <?php echo $action === 'add' ? 'Přidat uživatele' : 'Uložit změny'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$conn->close();
include_once __DIR__ . '/../app/includes/footer.php';
?>