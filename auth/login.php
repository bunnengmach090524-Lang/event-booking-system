<?php
require '../config/database.php';
require '../includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] === 'admin') {
            redirect('/event-booking/admin/dashboard.php');
        } else {
            redirect('/event-booking/customer/events.php');
        }
    } else {
        $error = "អ៊ីមែល ឬលេខសម្ងាត់មិនត្រឹមត្រូវ!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ចូលប្រើ - Event Booking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
        /* .bg-pattern {
            background-image: radial-gradient(circle at 2px 2px, rgba(255,255,255,0.15) 1px, transparent 0);
            background-size: 32px 32px;
        } */
        .input-focus:focus-within {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex">

    <!-- Left: Brand Panel (hidden on mobile) -->
    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-indigo-700 via-blue-700 to-purple-700 bg-pattern flex-col justify-center px-16 relative overflow-hidden">
        <div class="absolute top-20 right-20 w-32 h-32 rounded-full bg-cyan-300/20"></div>
        <div class="absolute bottom-32 left-10 w-20 h-20 rounded-full bg-pink-300/20"></div>
        
        <a href="/event-booking/" class="flex items-center gap-2 text-white font-bold text-2xl mb-10 relative z-10">
            <div class="w-10 h-10 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center">
                <i data-lucide="ticket" class="w-6 h-6"></i>
            </div>
            EventPlace
        </a>
        <h1 class="text-4xl font-bold text-white mb-4 relative z-10 leading-tight">
            រកឃើញ Event ដ៏អស្ចារ្យ<br>កក់សំបុត្រភ្លាមៗ
        </h1>
        <p class="text-blue-100 text-lg relative z-10 max-w-md">
            ចូលប្រើគណនីរបស់អ្នក ដើម្បីបន្តទស្សនា និងកក់សំបុត្រ Event ចាំបាច់
        </p>

        <div class="flex gap-8 mt-12 relative z-10">
            <div>
                <p class="text-3xl font-bold text-white">QR</p>
                <p class="text-blue-200 text-sm">Code Check-in</p>
            </div>
            <div>
                <p class="text-3xl font-bold text-white">24/7</p>
                <p class="text-blue-200 text-sm">Support Online</p>
            </div>
            <div>
                <p class="text-3xl font-bold text-white">100%</p>
                <p class="text-blue-200 text-sm">សុវត្ថិភាព</p>
            </div>
        </div>
    </div>

    <!-- Right: Login Form -->
    <div class="w-full lg:w-1/2 flex items-center justify-center px-6 py-12">
        <div class="w-full max-w-md">
            <!-- Mobile Logo -->
            <a href="/event-booking/" class="lg:hidden flex items-center gap-2 font-bold text-xl text-gray-800 mb-8">
                <div class="w-9 h-9 bg-gradient-to-br from-blue-600 to-purple-600 rounded-lg flex items-center justify-center">
                    <i data-lucide="ticket" class="w-5 h-5 text-white"></i>
                </div>
                EventPlace
            </a>

            <h2 class="text-2xl font-bold text-gray-800 mb-2">សូមស្វាគមន៍ត្រឡប់មកវិញ 👋</h2>
            <p class="text-gray-500 mb-8">សូមចូលប្រើគណនីរបស់អ្នក</p>

            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-600 p-3.5 rounded-lg mb-5 text-sm flex items-center gap-2">
                    <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">អ៊ីមែល</label>
                    <div class="flex items-center gap-2 border border-gray-300 rounded-lg px-4 py-3 input-focus transition-shadow">
                        <i data-lucide="mail" class="w-4 h-4 text-gray-400 flex-shrink-0"></i>
                        <input type="email" name="email" placeholder="you@example.com" required
                            class="w-full outline-none text-sm text-gray-700">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">លេខសម្ងាត់</label>
                    <div class="flex items-center gap-2 border border-gray-300 rounded-lg px-4 py-3 input-focus transition-shadow">
                        <i data-lucide="lock" class="w-4 h-4 text-gray-400 flex-shrink-0"></i>
                        <input type="password" name="password" placeholder="••••••••" required
                            class="w-full outline-none text-sm text-gray-700">
                    </div>
                </div>

                <button type="submit" 
                    class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white py-3 rounded-lg font-semibold hover:shadow-lg hover:scale-[1.01] active:scale-[0.99] transition-all flex items-center justify-center gap-2">
                    ចូលប្រើ
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>
            </form>

            <p class="text-center text-sm text-gray-500 mt-8">
                មិនទាន់មានគណនី? 
                <a href="register.php" class="text-blue-600 hover:underline font-semibold">ចុះឈ្មោះឥឡូវនេះ</a>
            </p>
        </div>
    </div>

<script>lucide.createIcons();</script>
</body>
</html>