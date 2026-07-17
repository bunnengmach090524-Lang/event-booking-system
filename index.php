<?php
session_start();
require 'config/database.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: /event-booking/admin/dashboard.php');
    } else {
        header('Location: /event-booking/customer/events.php');
    }
    exit();
}

$search = $_GET['search'] ?? '';
$location = $_GET['location'] ?? '';
$category = $_GET['category'] ?? '';

$sql = "SELECT * FROM events WHERE event_date >= NOW()";
$params = [];
if ($search) {
    $sql .= " AND title LIKE ?";
    $params[] = "%$search%";
}
if ($location) {
    $sql .= " AND location LIKE ?";
    $params[] = "%$location%";
}
if ($category) {
    $sql .= " AND category = ?";
    $params[] = $category;
}
$sql .= " ORDER BY event_date ASC LIMIT 6";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

$locations = $pdo->query("SELECT DISTINCT location FROM events")->fetchAll(PDO::FETCH_COLUMN);

$categoryIcons = [
    'Concert' => '🎵', 'Conference' => '💼', 'Workshop' => '🛠️',
    'Sports' => '⚽', 'Exhibition' => '🎨', 'General' => '📌'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Booking System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Caveat:wght@600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .font-script { font-family: 'Caveat', cursive; }
        .hero-blob {
            background: radial-gradient(circle at 20% 30%, rgba(255,255,255,0.15), transparent 40%),
                        radial-gradient(circle at 80% 70%, rgba(255,255,255,0.1), transparent 40%);
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
        }
        .float-anim { animation: float 4s ease-in-out infinite; }
        .card-hover { transition: all 0.3s ease; }
        .card-hover:hover { transform: translateY(-8px); box-shadow: 0 20px 40px -10px rgba(0,0,0,0.15); }
    </style>
</head>
<body class="bg-gray-50">

<!-- Navbar -->
<nav class="bg-white/80 backdrop-blur-md shadow-sm px-6 py-4 flex justify-between items-center sticky top-0 z-50">
    <div class="flex items-center gap-2 font-bold text-xl text-gray-800">
        <div class="w-9 h-9 bg-gradient-to-br from-blue-600 to-purple-600 rounded-lg flex items-center justify-center">
            <i data-lucide="ticket" class="w-5 h-5 text-white"></i>
        </div>
        EventPlace
    </div>

    <div class="hidden md:flex gap-8 text-sm font-medium text-gray-600">
        <a href="#home" class="hover:text-blue-600 transition">ទំព័រដើម</a>
        <a href="#events" class="hover:text-blue-600 transition">Events</a>
        <a href="#features" class="hover:text-blue-600 transition">Feature</a>
        <a href="#about" class="hover:text-blue-600 transition">អំពីយើង</a>
        <a href="#contact" class="hover:text-blue-600 transition">ទំនាក់ទំនង</a>
    </div>

    <div class="hidden md:flex gap-3 items-center">
        <a href="auth/login.php" class="text-gray-600 hover:text-blue-600 px-4 py-2 text-sm font-medium transition">ចូលប្រើ</a>
        <a href="auth/register.php" class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-5 py-2.5 rounded-full text-sm font-semibold hover:shadow-lg hover:scale-105 transition-all">
            ចុះឈ្មោះ
        </a>
    </div>

    <button id="menuBtn" class="md:hidden text-gray-700 p-2">
        <i data-lucide="menu" class="w-6 h-6"></i>
    </button>
</nav>

<!-- Mobile Menu -->
<div id="mobileMenu" class="hidden md:hidden bg-white shadow-lg px-6 py-4 sticky top-[72px] z-40">
    <a href="#home" class="block py-2.5 text-gray-700 font-medium border-b border-gray-100">ទំព័រដើម</a>
    <a href="#events" class="block py-2.5 text-gray-700 font-medium border-b border-gray-100">Events</a>
    <a href="#features" class="block py-2.5 text-gray-700 font-medium border-b border-gray-100">Feature</a>
    <a href="#about" class="block py-2.5 text-gray-700 font-medium border-b border-gray-100">អំពីយើង</a>
    <a href="#contact" class="block py-2.5 text-gray-700 font-medium border-b border-gray-100">ទំនាក់ទំនង</a>
    <div class="flex flex-col gap-3 mt-4">
        <a href="auth/login.php" class="text-center border border-gray-300 text-gray-700 py-2.5 rounded-full text-sm font-medium">ចូលប្រើ</a>
        <a href="auth/register.php" class="text-center bg-gradient-to-r from-blue-600 to-purple-600 text-white py-2.5 rounded-full text-sm font-semibold">ចុះឈ្មោះ</a>
    </div>
</div>

<!-- Hero Section -->
<section id="home" class="relative overflow-hidden bg-gradient-to-br from-indigo-700 via-blue-700 to-purple-700 hero-blob">
    <div class="absolute top-16 right-16 w-24 h-24 rounded-full bg-cyan-300/30 float-anim hidden md:block"></div>
    <div class="absolute bottom-10 right-40 w-16 h-16 rounded-full bg-pink-300/30 float-anim hidden md:block" style="animation-delay: 1s;"></div>
    <div class="absolute top-1/3 left-10 w-3 h-3 rounded-full bg-white/40 hidden md:block"></div>
    <div class="absolute top-1/2 left-24 w-2 h-2 rounded-full bg-white/40 hidden md:block"></div>

    <div class="max-w-6xl mx-auto px-6 py-24 md:py-32 relative z-10">
        <p class="font-script text-2xl text-cyan-300 mb-2">Find Your Next Experience</p>
        <h1 class="text-4xl md:text-6xl font-extrabold text-white leading-tight mb-6 max-w-2xl">
            រកឃើញ & កក់សំបុត្រ<br>Event ដ៏ល្អបំផុត
        </h1>
        <p class="text-blue-100 text-lg mb-10 max-w-xl">
            ប្រព័ន្ធកក់សំបុត្រ Online សុវត្ថិភាព ងាយស្រួល ជាមួយ QR Code Check-in ភ្លាមៗ
        </p>

        <!-- Search Bar with Category -->
        <form method="GET" action="index.php" class="bg-white rounded-xl shadow-2xl p-2 flex flex-col md:flex-row gap-2 max-w-4xl">
            <div class="flex items-center gap-2 flex-1 px-4 py-2.5">
                <i data-lucide="search" class="w-5 h-5 text-gray-400 flex-shrink-0"></i>
                <input type="text" name="search" placeholder="ស្វែងរក Event..." value="<?= htmlspecialchars($search) ?>"
                    class="w-full outline-none text-gray-700 text-sm">
            </div>
            <div class="hidden md:block w-px bg-gray-200"></div>
            <div class="flex items-center gap-2 flex-1 px-4 py-2.5">
                <i data-lucide="tag" class="w-5 h-5 text-gray-400 flex-shrink-0"></i>
                <select name="category" class="w-full outline-none text-gray-700 text-sm bg-transparent">
                    <option value="">ប្រភេទទាំងអស់</option>
                    <?php foreach ($categoryIcons as $cat => $icon): ?>
                        <option value="<?= $cat ?>" <?= $category === $cat ? 'selected' : '' ?>><?= $icon ?> <?= $cat ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="hidden md:block w-px bg-gray-200"></div>
            <div class="flex items-center gap-2 flex-1 px-4 py-2.5">
                <i data-lucide="map-pin" class="w-5 h-5 text-gray-400 flex-shrink-0"></i>
                <select name="location" class="w-full outline-none text-gray-700 text-sm bg-transparent">
                    <option value="">ទីតាំងទាំងអស់</option>
                    <?php foreach ($locations as $loc): ?>
                        <option value="<?= htmlspecialchars($loc) ?>" <?= $location === $loc ? 'selected' : '' ?>>
                            <?= htmlspecialchars($loc) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="bg-gradient-to-r from-blue-600 to-purple-600 hover:opacity-90 text-white p-3.5 rounded-full flex-shrink-0 transition">
                <i data-lucide="search" class="w-5 h-5"></i>
            </button>
        </form>

        <!-- Quick Category Pills -->
        <div class="flex flex-wrap gap-2 mt-6">
            <?php foreach ($categoryIcons as $cat => $icon): ?>
                <a href="index.php?category=<?= urlencode($cat) ?>#events" 
                   class="flex items-center gap-1.5 bg-white/15 hover:bg-white/25 text-white text-sm px-4 py-2 rounded-full backdrop-blur transition">
                    <?= $icon ?> <?= $cat ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Features -->
<section id="features" class="max-w-6xl mx-auto py-20 px-6">
    <p class="text-center font-script text-2xl text-pink-500 mb-1">ហេតុអ្វីជ្រើសរើសយើង</p>
    <h2 class="text-3xl font-bold text-center text-gray-800 mb-14">Features ពិសេស</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="text-center group">
            <div class="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:bg-blue-600 group-hover:-translate-y-1 transition-all duration-300">
                <i data-lucide="search" class="w-7 h-7 text-blue-600 group-hover:text-white transition-colors"></i>
            </div>
            <h3 class="font-semibold text-gray-800 mb-2">ស្វែងរកងាយស្រួល</h3>
            <p class="text-gray-500 text-sm px-4">ស្វែងរក Event តាមទីតាំង ថ្ងៃខែ បានយ៉ាងឆាប់រហ័ស</p>
        </div>
        <div class="text-center group">
            <div class="w-16 h-16 bg-purple-100 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:bg-purple-600 group-hover:-translate-y-1 transition-all duration-300">
                <i data-lucide="qr-code" class="w-7 h-7 text-purple-600 group-hover:text-white transition-colors"></i>
            </div>
            <h3 class="font-semibold text-gray-800 mb-2">QR Code ភ្លាមៗ</h3>
            <p class="text-gray-500 text-sm px-4">ទទួល QR Code សំបុត្រភ្លាមៗ ក្រោយកក់ជោគជ័យ</p>
        </div>
        <div class="text-center group">
            <div class="w-16 h-16 bg-green-100 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:bg-green-600 group-hover:-translate-y-1 transition-all duration-300">
                <i data-lucide="mail-check" class="w-7 h-7 text-green-600 group-hover:text-white transition-colors"></i>
            </div>
            <h3 class="font-semibold text-gray-800 mb-2">ជូនដំណឹងតាម Email</h3>
            <p class="text-gray-500 text-sm px-4">ទទួល Email បញ្ជាក់ការកក់ភ្លាមៗ ជាមួយ QR Code</p>
        </div>
    </div>
</section>

<!-- Featured Events -->
<section id="events" class="bg-gray-100 py-20 px-6">
    <div class="max-w-6xl mx-auto">
        <p class="text-center font-script text-2xl text-pink-500 mb-1">Upcoming Event</p>
        <h2 class="text-3xl font-bold text-center text-gray-800 mb-14">Featured Events</h2>

        <?php if (empty($events)): ?>
            <div class="text-center py-16">
                <i data-lucide="calendar-x" class="w-16 h-16 text-gray-300 mx-auto mb-4"></i>
                <p class="text-gray-400">មិនទាន់មាន Event ត្រូវនឹងលក្ខខណ្ឌស្វែងរកទេ</p>
            </div>
        <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($events as $event): 
                $remaining = $event['total_tickets'] - $event['tickets_sold'];
                $catIcon = $categoryIcons[$event['category']] ?? '📌';
            ?>
            <div class="bg-white rounded-xl shadow-sm overflow-hidden card-hover">
                <div class="relative">
                    <?php if ($event['image']): ?>
                        <img src="uploads/events/<?= htmlspecialchars($event['image']) ?>" class="w-full h-48 object-cover">
                    <?php else: ?>
                        <div class="w-full h-48 bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                            <i data-lucide="ticket" class="w-12 h-12 text-white/80"></i>
                        </div>
                    <?php endif; ?>
                    <span class="absolute top-3 left-3 bg-white/90 backdrop-blur text-xs font-semibold px-3 py-1 rounded-full text-gray-700">
                        <?= $catIcon ?> <?= htmlspecialchars($event['category']) ?>
                    </span>
                </div>
                <div class="p-5">
                    <h3 class="font-bold text-gray-800 mb-2 line-clamp-1"><?= htmlspecialchars($event['title']) ?></h3>
                    <div class="flex items-center gap-1.5 text-xs text-gray-400 mb-1">
                        <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                        <?= date('D, d M Y', strtotime($event['event_date'])) ?>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-gray-400 mb-4">
                        <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                        <?= htmlspecialchars($event['location']) ?>
                    </div>
                    <div class="flex justify-between items-center pt-3 border-t border-gray-100">
                        <div>
                            <p class="text-xs text-gray-400">តម្លៃ</p>
                            <p class="font-bold text-blue-600">$<?= number_format($event['price'], 2) ?></p>
                        </div>
                        <a href="auth/login.php" 
                           class="border-2 border-blue-600 text-blue-600 text-xs font-bold px-4 py-2 rounded-full hover:bg-blue-600 hover:text-white transition-all">
                            <?= $remaining > 0 ? 'កក់ឥឡូវនេះ' : 'អស់ហើយ' ?>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="text-center mt-12">
            <a href="auth/register.php" 
               class="inline-flex items-center gap-2 border-2 border-gray-800 text-gray-800 px-6 py-3 rounded-full font-semibold hover:bg-gray-800 hover:text-white transition-all">
                មើល Event ទាំងអស់ <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </a>
        </div>
    </div>
</section>

<!-- About Us -->
<section id="about" class="max-w-6xl mx-auto py-20 px-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
        <div>
            <p class="font-script text-2xl text-pink-500 mb-1">អំពីយើង</p>
            <h2 class="text-3xl font-bold text-gray-800 mb-6">ជាមួយ EventPlace<br>រៀបចំ Event ក្លាយជារឿងងាយ</h2>
            <p class="text-gray-500 mb-6 leading-relaxed">
                EventPlace ជាប្រព័ន្ធកក់សំបុត្រ Online ដែលជួយអ្នករៀបចំ Event ភ្ជាប់ទំនាក់ទំនងជាមួយអ្នកចូលរួម
                ដោយងាយស្រួល សុវត្ថិភាព និងមានប្រសិទ្ធភាព។ ចាប់ពីការគ្រប់គ្រង Event រហូតដល់ Check-in
                តាមរយៈ QR Code យើងធានាថារាល់ Event របស់អ្នកដំណើរការរលូន។
            </p>
            <div class="grid grid-cols-3 gap-6">
                <div>
                    <p class="text-2xl font-bold text-blue-600">500+</p>
                    <p class="text-gray-400 text-sm">Events</p>
                </div>
                <div>
                    <p class="text-2xl font-bold text-purple-600">10K+</p>
                    <p class="text-gray-400 text-sm">អ្នកប្រើប្រាស់</p>
                </div>
                <div>
                    <p class="text-2xl font-bold text-green-600">99%</p>
                    <p class="text-gray-400 text-sm">ពេញចិត្ត</p>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl h-80 flex items-center justify-center">
            <i data-lucide="party-popper" class="w-24 h-24 text-white/80"></i>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="bg-gradient-to-r from-blue-700 to-purple-700 py-16 px-6 text-center">
    <h2 class="text-2xl md:text-3xl font-bold text-white mb-4">ត្រៀមខ្លួនចូលរួម Event ដ៏អស្ចារ្យបន្ទាប់?</h2>
    <p class="text-blue-100 mb-8">ចុះឈ្មោះឥឡូវនេះ ដើម្បីមិនខកខានឱកាសល្អៗ</p>
    <a href="auth/register.php" 
       class="inline-flex items-center gap-2 bg-white text-blue-700 px-8 py-3.5 rounded-full font-bold hover:shadow-2xl hover:scale-105 transition-all">
        ចុះឈ្មោះឥឡូវនេះ <i data-lucide="arrow-right" class="w-5 h-5"></i>
    </a>
</section>

<!-- Footer with Contact -->
<footer id="contact" class="bg-gray-900 text-gray-400 pt-16 pb-8 px-6">
    <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-10 mb-12">
        <div>
            <div class="flex items-center gap-2 text-white font-bold text-lg mb-4">
                <i data-lucide="ticket" class="w-5 h-5"></i> EventPlace
            </div>
            <p class="text-sm leading-relaxed">ប្រព័ន្ធកក់សំបុត្រ Event Online សុវត្ថិភាព និងងាយស្រួល</p>
        </div>
        <div>
            <h4 class="text-white font-semibold mb-4">ប្រភេទ Event</h4>
            <ul class="space-y-2 text-sm">
                <?php foreach ($categoryIcons as $cat => $icon): ?>
                <li><a href="index.php?category=<?= urlencode($cat) ?>#events" class="hover:text-white transition"><?= $icon ?> <?= $cat ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div>
            <h4 class="text-white font-semibold mb-4">តំណភ្ជាប់</h4>
            <ul class="space-y-2 text-sm">
                <li><a href="#home" class="hover:text-white transition">ទំព័រដើម</a></li>
                <li><a href="#events" class="hover:text-white transition">Events</a></li>
                <li><a href="#about" class="hover:text-white transition">អំពីយើង</a></li>
                <li><a href="auth/login.php" class="hover:text-white transition">ចូលប្រើ</a></li>
            </ul>
        </div>
        <div>
            <h4 class="text-white font-semibold mb-4">ទំនាក់ទំនង</h4>
            <ul class="space-y-3 text-sm">
                <li class="flex items-center gap-2"><i data-lucide="mail" class="w-4 h-4"></i> support@eventplace.com</li>
                <li class="flex items-center gap-2"><i data-lucide="phone" class="w-4 h-4"></i> +855 12 345 678</li>
                <li class="flex items-center gap-2"><i data-lucide="map-pin" class="w-4 h-4"></i> Phnom Penh, Cambodia</li>
            </ul>
        </div>
    </div>
    <div class="border-t border-gray-800 pt-6 text-center text-sm">
        <p>&copy; 2026 Event Booking System. All rights reserved.</p>
    </div>
</footer>

<script>
    lucide.createIcons();

    const menuBtn = document.getElementById('menuBtn');
    const mobileMenu = document.getElementById('mobileMenu');
    menuBtn.addEventListener('click', function() {
        mobileMenu.classList.toggle('hidden');
    });
</script>

</body>
</html>