<?php
require '../config/database.php';
require '../includes/functions.php';
require '../includes/lang.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = t('register_error_mismatch');
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $error = t('register_error_email_exists');
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'customer')");
            $stmt->execute([$name, $email, $hashed_password]);
            
            redirect('/event-booking/auth/login.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('register_page_title') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
        /* .bg-pattern {
            background-image: radial-gradient(rgba(255,255,255,0.12) 1.5px, transparent 1.5px);
            background-size: 24px 24px;
        } */
        .input-focus:focus-within {
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.15);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex">

    <!-- Left: Brand Panel -->
    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-purple-700 via-indigo-700 to-blue-700 bg-pattern flex-col justify-center px-16 relative overflow-hidden">
        <div class="absolute top-24 right-16 w-28 h-28 rounded-full bg-pink-300/20"></div>
        <div class="absolute bottom-20 left-16 w-24 h-24 rounded-full bg-cyan-300/20"></div>

        <a href="/event-booking/" class="flex items-center gap-2 text-white font-bold text-2xl mb-10 relative z-10">
            <div class="w-10 h-10 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center">
                <i data-lucide="ticket" class="w-6 h-6"></i>
            </div>
            EventPlace
        </a>
        <h1 class="text-4xl font-bold text-white mb-4 relative z-10 leading-tight">
            <?= t('register_hero_title_l1') ?><br><?= t('register_hero_title_l2') ?>
        </h1>
        <p class="text-blue-100 text-lg relative z-10 max-w-md mb-10">
            <?= t('register_hero_subtitle') ?>
        </p>

        <div class="space-y-4 relative z-10">
            <div class="flex items-center gap-3 text-white">
                <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center flex-shrink-0">
                    <i data-lucide="check" class="w-4 h-4"></i>
                </div>
                <span class="text-sm"><?= t('benefit1') ?></span>
            </div>
            <div class="flex items-center gap-3 text-white">
                <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center flex-shrink-0">
                    <i data-lucide="check" class="w-4 h-4"></i>
                </div>
                <span class="text-sm"><?= t('benefit2') ?></span>
            </div>
            <div class="flex items-center gap-3 text-white">
                <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center flex-shrink-0">
                    <i data-lucide="check" class="w-4 h-4"></i>
                </div>
                <span class="text-sm"><?= t('benefit3') ?></span>
            </div>
        </div>
    </div>

    <!-- Right: Register Form -->
    <div class="w-full lg:w-1/2 flex items-center justify-center px-6 py-12">
        <div class="w-full max-w-md">
            <a href="/event-booking/" class="lg:hidden flex items-center gap-2 font-bold text-xl text-gray-800 mb-8">
                <div class="w-9 h-9 bg-gradient-to-br from-blue-600 to-purple-600 rounded-lg flex items-center justify-center">
                    <i data-lucide="ticket" class="w-5 h-5 text-white"></i>
                </div>
                EventPlace
            </a>

            <h2 class="text-2xl font-bold text-gray-800 mb-2"><?= t('create_account') ?></h2>
            <p class="text-gray-500 mb-8"><?= t('register_subtitle') ?></p>

            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-600 p-3.5 rounded-lg mb-5 text-sm flex items-center gap-2">
                    <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5"><?= t('full_name_label') ?></label>
                    <div class="flex items-center gap-2 border border-gray-300 rounded-lg px-4 py-3 input-focus transition-shadow">
                        <i data-lucide="user" class="w-4 h-4 text-gray-400 flex-shrink-0"></i>
                        <input type="text" name="name" placeholder="Sok Dara" required
                            class="w-full outline-none text-sm text-gray-700">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5"><?= t('email_label') ?></label>
                    <div class="flex items-center gap-2 border border-gray-300 rounded-lg px-4 py-3 input-focus transition-shadow">
                        <i data-lucide="mail" class="w-4 h-4 text-gray-400 flex-shrink-0"></i>
                        <input type="email" name="email" placeholder="you@example.com" required
                            class="w-full outline-none text-sm text-gray-700">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5"><?= t('password_label') ?></label>
                    <div class="flex items-center gap-2 border border-gray-300 rounded-lg px-4 py-3 input-focus transition-shadow">
                        <i data-lucide="lock" class="w-4 h-4 text-gray-400 flex-shrink-0"></i>
                        <input type="password" name="password" placeholder="••••••••" required
                            class="w-full outline-none text-sm text-gray-700">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5"><?= t('confirm_password_label') ?></label>
                    <div class="flex items-center gap-2 border border-gray-300 rounded-lg px-4 py-3 input-focus transition-shadow">
                        <i data-lucide="lock" class="w-4 h-4 text-gray-400 flex-shrink-0"></i>
                        <input type="password" name="confirm_password" placeholder="••••••••" required
                            class="w-full outline-none text-sm text-gray-700">
                    </div>
                </div>

                <button type="submit" 
                    class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white py-3 rounded-lg font-semibold hover:shadow-lg hover:scale-[1.01] active:scale-[0.99] transition-all flex items-center justify-center gap-2">
                    <?= t('register_button') ?>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>
            </form>

            <p class="text-center text-sm text-gray-500 mt-8">
                <?= t('have_account') ?>
                <a href="login.php" class="text-blue-600 hover:underline font-semibold"><?= t('login_now') ?></a>
            </p>
        </div>
    </div>

<script>lucide.createIcons();</script>
</body>
</html>