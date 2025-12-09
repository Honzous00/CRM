<?php
include_once __DIR__ . '/../app/includes/login.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (check_login($username, $password)) {
        $_SESSION['user_id'] = $username;
        $_SESSION['LAST_ACTIVITY'] = time(); // Nastavení času přihlášení
        header('Location: index.php');
        exit;
    } else {
        $error_message = 'Neplatné uživatelské jméno nebo heslo.';
    }
}
?>

<!DOCTYPE html>
<html lang="cs">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Přihlášení - Můj CMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        /* Definice barev pro snadné úpravy */
        :root {
            --soft-bg: #f0f2f5;
            --accent-color: #2963e5;
            /* Indigo-600 */
            --shadow-light: #ffffff;
            --shadow-dark: #c8cacd;
        }

        /* 1. Neumorphism Pozadí (Soft Background) */
        .neumorphism-bg {
            background-color: var(--soft-bg);
        }

        /* 2. Neumorphic Karta (Popped out efekt) */
        .neumorphic-card {
            background-color: var(--soft-bg);
            /* Dva stíny pro hloubku a 3D efekt */
            box-shadow: 10px 10px 20px var(--shadow-dark),
                -10px -10px 20px var(--shadow-light);
        }

        /* 3. Neumorphic Vstupní pole (Pushed in efekt) */
        .neumorphic-input {
            border: none;
            background-color: var(--soft-bg);
            /* Vnitřní stín pro "zapuštěný" pocit */
            box-shadow: inset 4px 4px 8px var(--shadow-dark),
                inset -4px -4px 8px var(--shadow-light);
            transition: all 0.2s ease-in-out;
        }

        /* Zrušení stínu při focusu pro lepší čitelnost */
        .neumorphic-input:focus {
            box-shadow: none;
            border: 1px solid var(--accent-color);
        }

        /* 4. Neumorphic Tlačítko (Popped out) */
        .neumorphic-button {
            background-color: var(--accent-color);
            color: white;
            /* Stejné stíny jako karta */
            box-shadow: 6px 6px 12px var(--shadow-dark),
                -6px -6px 12px var(--shadow-light);
            transition: all 0.2s ease-in-out;
        }

        /* Reakce tlačítka na kliknutí/stisknutí (Pushed in) */
        .neumorphic-button:active {
            background-color: #2963e5;
            /* Lehce tmavší indigo */
            box-shadow: inset 4px 4px 8px var(--shadow-dark),
                inset -4px -4px 8px var(--shadow-light);
        }
    </style>
</head>

<body class="neumorphism-bg flex items-center justify-center min-h-screen p-4">

    <div class="w-full max-w-5xl flex neumorphic-card rounded-3xl overflow-hidden">

        <div class="hidden lg:flex lg:w-1/2 p-12 items-center justify-center bg-blue-600 relative">

            <div class="absolute inset-0 opacity-10 bg-gradient-to-br from-blue-400 to-blue-800"></div>

            <div class="text-center z-10">
                <img src="images/logo/White_square.svg" alt="RELATIO CRM SYSTEM Logo" class="w-64 h-64 mx-auto mb-6" />

                <p class="text-white text-lg font-normal">
                    Přístup k modernímu systému pro správu klientů a provizí.
                </p>
            </div>
        </div>

        <div class="w-full lg:w-1/2 p-10 sm:p-12">

            <h1 class="text-4xl font-extrabold text-center text-gray-800 mb-3">
                Přihlášení <span class="text-blue-600">uživatele</span>
            </h1>
            <p class="text-center text-gray-700 text-lm mb-8">Zadejte své přihlašovací údaje</p>

            <?php if ($error_message): ?>
                <div class="p-3 rounded-xl bg-red-100 text-red-700 text-sm flex items-center space-x-2 border border-red-300">
                    <i class="fas fa-exclamation-circle text-lg"></i>
                    <span><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            <?php endif; ?>

            <form action="login.php" method="post" class="space-y-6">
                <div>
                    <label for="username" class="block text-sm font-semibold text-gray-600 flex items-center mb-2">
                        <i class="fas fa-user-circle mr-2 text-blue-600"></i> Uživatelské jméno (admin)
                    </label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        required
                        class="neumorphic-input block w-full rounded-xl p-4 placeholder-gray-400 focus:ring-0"
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                        placeholder="Zadejte uživatelské jméno">
                </div>

                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-600 flex items-center mb-2">
                        <i class="fas fa-lock mr-2 text-blue-600"></i> Heslo (Heslo123!)
                    </label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        class="neumorphic-input block w-full rounded-xl p-4 placeholder-gray-400 focus:ring-0"
                        placeholder="Zadejte heslo">
                </div>

                <button
                    type="submit"
                    class="w-full py-4 px-4 rounded-xl text-base font-bold neumorphic-button hover:opacity-95 ">
                    <i class="fas fa-sign-in-alt mr-2"></i> Přihlásit se
                </button>
            </form>
        </div>

    </div>
</body>

</html>