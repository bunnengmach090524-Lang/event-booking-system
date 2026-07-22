<?php
session_start();
require 'config/database.php';
require_once 'includes/lang.php';

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
    <script>tailwind.config = { darkMode: 'class' };</script>
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
    <script>
        // Apply saved theme instantly, before paint, to avoid a flash
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-gray-50 dark:bg-gray-900 dark:text-gray-100">

<!-- Navbar -->
<nav class="bg-white/80 dark:bg-gray-800/90 backdrop-blur-md shadow-sm px-6 py-4 flex justify-between items-center sticky top-0 z-50">
    <div class="flex items-center gap-2 font-bold text-xl text-gray-800 dark:text-white">
        <div class="w-9 h-9 bg-gradient-to-br from-blue-600 to-purple-600 rounded-lg flex items-center justify-center">
            <i data-lucide="ticket" class="w-5 h-5 text-white"></i>
        </div>
        EventPlace
    </div>

    <div class="hidden md:flex gap-8 text-sm font-medium text-gray-600 dark:text-gray-300">
        <a href="#home" class="hover:text-blue-600 dark:hover:text-blue-400 transition"><?= t('nav_home') ?></a>
        <a href="#events" class="hover:text-blue-600 dark:hover:text-blue-400 transition"><?= t('nav_events') ?></a>
        <a href="#features" class="hover:text-blue-600 dark:hover:text-blue-400 transition"><?= t('nav_features') ?></a>
        <a href="#about" class="hover:text-blue-600 dark:hover:text-blue-400 transition"><?= t('nav_about') ?></a>
        <a href="#contact" class="hover:text-blue-600 dark:hover:text-blue-400 transition"><?= t('nav_contact') ?></a>
    </div>

    <div class="flex gap-2 sm:gap-3 items-center">
        <!-- Language toggle -->
        <div id="langToggle" data-current="<?= htmlspecialchars($currentLang) ?>"
             class="hidden sm:flex items-center bg-gray-100 dark:bg-gray-700 rounded-full p-1 text-xs font-medium cursor-pointer select-none">
            <span data-lang="en" class="lang-option px-2.5 py-1 rounded-full text-gray-500 dark:text-gray-300">EN</span>
            <span data-lang="km" class="lang-option px-2.5 py-1 rounded-full text-gray-500 dark:text-gray-300">ខ្មែរ</span>
        </div>

        <!-- Theme toggle -->
        <button id="themeToggle" aria-label="Toggle theme"
                class="w-9 h-9 flex items-center justify-center rounded-lg text-gray-500 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
            <i data-lucide="sun" class="w-4 h-4 dark:hidden"></i>
            <i data-lucide="moon" class="w-4 h-4 hidden dark:block"></i>
        </button>

        <div class="hidden md:flex gap-3 items-center">
            <a href="auth/login.php" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-4 py-2 text-sm font-medium transition"><?= t('nav_login') ?></a>
            <a href="auth/register.php" class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-5 py-2.5 rounded-full text-sm font-semibold hover:shadow-lg hover:scale-105 transition-all">
                <?= t('nav_register') ?>
            </a>
        </div>

        <button id="menuBtn" class="md:hidden text-gray-700 dark:text-gray-300 p-2">
            <i data-lucide="menu" class="w-6 h-6"></i>
        </button>
    </div>
</nav>

<!-- Mobile Menu -->
<div id="mobileMenu" class="hidden md:hidden bg-white dark:bg-gray-800 shadow-lg px-6 py-4 sticky top-[72px] z-40">
    <a href="#home" class="block py-2.5 text-gray-700 dark:text-gray-300 font-medium border-b border-gray-100 dark:border-gray-700"><?= t('nav_home') ?></a>
    <a href="#events" class="block py-2.5 text-gray-700 dark:text-gray-300 font-medium border-b border-gray-100 dark:border-gray-700"><?= t('nav_events') ?></a>
    <a href="#features" class="block py-2.5 text-gray-700 dark:text-gray-300 font-medium border-b border-gray-100 dark:border-gray-700"><?= t('nav_features') ?></a>
    <a href="#about" class="block py-2.5 text-gray-700 dark:text-gray-300 font-medium border-b border-gray-100 dark:border-gray-700"><?= t('nav_about') ?></a>
    <a href="#contact" class="block py-2.5 text-gray-700 dark:text-gray-300 font-medium border-b border-gray-100 dark:border-gray-700"><?= t('nav_contact') ?></a>

    <!-- Language toggle (mobile, shown here since hidden in top bar on small screens) -->
    <div class="sm:hidden flex items-center justify-between py-2.5 border-b border-gray-100 dark:border-gray-700">
        <span class="text-sm text-gray-500 dark:text-gray-400"><?= t('language_label') ?></span>
        <div id="langToggleMobile" data-current="<?= htmlspecialchars($currentLang) ?>"
             class="flex items-center bg-gray-100 dark:bg-gray-700 rounded-full p-1 text-xs font-medium cursor-pointer select-none">
            <span data-lang="en" class="lang-option px-2.5 py-1 rounded-full text-gray-500 dark:text-gray-300">EN</span>
            <span data-lang="km" class="lang-option px-2.5 py-1 rounded-full text-gray-500 dark:text-gray-300">ខ្មែរ</span>
        </div>
    </div>

    <div class="flex flex-col gap-3 mt-4">
        <a href="auth/login.php" class="text-center border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 py-2.5 rounded-full text-sm font-medium"><?= t('nav_login') ?></a>
        <a href="auth/register.php" class="text-center bg-gradient-to-r from-blue-600 to-purple-600 text-white py-2.5 rounded-full text-sm font-semibold"><?= t('nav_register') ?></a>
    </div>
</div>

<!-- Hero Section -->
<section id="home" class="relative overflow-hidden bg-gradient-to-br from-indigo-700 via-blue-700 to-purple-700 dark:from-indigo-900 dark:via-blue-900 dark:to-purple-900 hero-blob">
    <div class="absolute top-16 right-16 w-24 h-24 rounded-full bg-cyan-300/30 float-anim hidden md:block"></div>
    <div class="absolute bottom-10 right-40 w-16 h-16 rounded-full bg-pink-300/30 float-anim hidden md:block" style="animation-delay: 1s;"></div>
    <div class="absolute top-1/3 left-10 w-3 h-3 rounded-full bg-white/40 hidden md:block"></div>
    <div class="absolute top-1/2 left-24 w-2 h-2 rounded-full bg-white/40 hidden md:block"></div>

    <div class="max-w-6xl mx-auto px-6 py-24 md:py-32 relative z-10">
        <p class="font-script text-2xl text-cyan-300 mb-2"><?= t('hero_tagline') ?></p>
        <h1 class="text-4xl md:text-6xl font-extrabold text-white leading-tight mb-6 max-w-2xl">
            <?= t('hero_title_line1') ?><br><?= t('hero_title_line2') ?>
        </h1>
        <p class="text-blue-100 text-lg mb-10 max-w-xl">
            <?= t('hero_subtitle') ?>
        </p>

        <!-- Search Bar with Category -->
        <div class="relative max-w-4xl">
            <form method="GET" action="index.php" class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-2 flex flex-col md:flex-row gap-2">
                <div class="flex items-center gap-2 flex-1 px-4 py-2.5 relative">
                    <i data-lucide="search" class="w-5 h-5 text-gray-400 flex-shrink-0"></i>
                    <input type="text" id="searchInput" name="search" placeholder="<?= t('search_events_placeholder') ?>" 
                        value="<?= htmlspecialchars($search) ?>" autocomplete="off"
                        class="w-full outline-none text-gray-700 dark:text-gray-200 dark:bg-transparent text-sm">
                </div>
                <div class="hidden md:block w-px bg-gray-200 dark:bg-gray-600"></div>
                <div class="flex items-center gap-2 flex-1 px-4 py-2.5">
                    <i data-lucide="tag" class="w-5 h-5 text-gray-400 flex-shrink-0"></i>
                    <select name="category" class="w-full outline-none text-gray-700 dark:text-gray-200 text-sm bg-transparent">
                        <option value=""><?= t('category_all') ?></option>
                        <?php foreach ($categoryIcons as $cat => $icon): ?>
                            <option value="<?= $cat ?>" <?= $category === $cat ? 'selected' : '' ?>><?= $icon ?> <?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="hidden md:block w-px bg-gray-200 dark:bg-gray-600"></div>
                <div class="flex items-center gap-2 flex-1 px-4 py-2.5">
                    <i data-lucide="map-pin" class="w-5 h-5 text-gray-400 flex-shrink-0"></i>
                    <select name="location" class="w-full outline-none text-gray-700 dark:text-gray-200 text-sm bg-transparent">
                        <option value=""><?= t('location_all') ?></option>
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

            <div id="suggestionBox" class="hidden absolute top-full left-0 right-0 mt-2 bg-white dark:bg-gray-800 rounded-xl shadow-2xl overflow-hidden z-50 max-h-96 overflow-y-auto"></div>
        </div>

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
    <p class="text-center font-script text-2xl text-pink-500 mb-1"><?= t('why_choose_us') ?></p>
    <h2 class="text-3xl font-bold text-center text-gray-800 dark:text-white mb-14"><?= t('features_heading') ?></h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="text-center group">
            <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900/40 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:bg-blue-600 group-hover:-translate-y-1 transition-all duration-300">
                <i data-lucide="search" class="w-7 h-7 text-blue-600 dark:text-blue-400 group-hover:text-white transition-colors"></i>
            </div>
            <h3 class="font-semibold text-gray-800 dark:text-white mb-2"><?= t('feature1_title') ?></h3>
            <p class="text-gray-500 dark:text-gray-400 text-sm px-4"><?= t('feature1_desc') ?></p>
        </div>
        <div class="text-center group">
            <div class="w-16 h-16 bg-purple-100 dark:bg-purple-900/40 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:bg-purple-600 group-hover:-translate-y-1 transition-all duration-300">
                <i data-lucide="qr-code" class="w-7 h-7 text-purple-600 dark:text-purple-400 group-hover:text-white transition-colors"></i>
            </div>
            <h3 class="font-semibold text-gray-800 dark:text-white mb-2"><?= t('feature2_title') ?></h3>
            <p class="text-gray-500 dark:text-gray-400 text-sm px-4"><?= t('feature2_desc') ?></p>
        </div>
        <div class="text-center group">
            <div class="w-16 h-16 bg-green-100 dark:bg-green-900/40 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:bg-green-600 group-hover:-translate-y-1 transition-all duration-300">
                <i data-lucide="mail-check" class="w-7 h-7 text-green-600 dark:text-green-400 group-hover:text-white transition-colors"></i>
            </div>
            <h3 class="font-semibold text-gray-800 dark:text-white mb-2"><?= t('feature3_title') ?></h3>
            <p class="text-gray-500 dark:text-gray-400 text-sm px-4"><?= t('feature3_desc') ?></p>
        </div>
    </div>
</section>

<!-- Featured Events -->
<section id="events" class="bg-gray-100 dark:bg-gray-800/50 py-20 px-6">
    <div class="max-w-6xl mx-auto">
        <p class="text-center font-script text-2xl text-pink-500 mb-1"><?= t('upcoming_event_tag') ?></p>
        <h2 class="text-3xl font-bold text-center text-gray-800 dark:text-white mb-14"><?= t('featured_events_heading') ?></h2>

        <?php if (empty($events)): ?>
            <div class="text-center py-16">
                <i data-lucide="calendar-x" class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4"></i>
                <p class="text-gray-400 dark:text-gray-500"><?= t('no_events_match') ?></p>
            </div>
        <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($events as $event): 
                $remaining = $event['total_tickets'] - $event['tickets_sold'];
                $catIcon = $categoryIcons[$event['category']] ?? '📌';
            ?>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden card-hover">
                <div class="relative">
                    <?php if ($event['image']): ?>
                        <img src="uploads/events/<?= htmlspecialchars($event['image']) ?>" class="w-full h-48 object-cover">
                    <?php else: ?>
                        <div class="w-full h-48 bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                            <i data-lucide="ticket" class="w-12 h-12 text-white/80"></i>
                        </div>
                    <?php endif; ?>
                    <span class="absolute top-3 left-3 bg-white/90 dark:bg-gray-900/80 backdrop-blur text-xs font-semibold px-3 py-1 rounded-full text-gray-700 dark:text-gray-200">
                        <?= $catIcon ?> <?= htmlspecialchars($event['category']) ?>
                    </span>
                </div>
                <div class="p-5">
                    <h3 class="font-bold text-gray-800 dark:text-white mb-2 line-clamp-1"><?= htmlspecialchars($event['title']) ?></h3>
                    <div class="flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-500 mb-1">
                        <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                        <?= date('D, d M Y', strtotime($event['event_date'])) ?>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-500 mb-4">
                        <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                        <?= htmlspecialchars($event['location']) ?>
                    </div>
                    <div class="flex justify-between items-center pt-3 border-t border-gray-100 dark:border-gray-700">
                        <div>
                            <p class="text-xs text-gray-400 dark:text-gray-500"><?= t('price_label') ?></p>
                            <p class="font-bold text-blue-600 dark:text-blue-400">$<?= number_format($event['price'], 2) ?></p>
                        </div>
                        <a href="auth/login.php" 
                           class="border-2 border-blue-600 text-blue-600 dark:text-blue-400 dark:border-blue-400 text-xs font-bold px-4 py-2 rounded-full hover:bg-blue-600 hover:text-white transition-all">
                            <?= $remaining > 0 ? t('book_now_short') : t('sold_out_short') ?>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="text-center mt-12">
            <a href="auth/register.php" 
               class="inline-flex items-center gap-2 border-2 border-gray-800 dark:border-gray-200 text-gray-800 dark:text-gray-200 px-6 py-3 rounded-full font-semibold hover:bg-gray-800 hover:text-white dark:hover:bg-gray-200 dark:hover:text-gray-900 transition-all">
                <?= t('view_all_events') ?> <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </a>
        </div>
    </div>
</section>

<!-- About Us -->
<section id="about" class="max-w-6xl mx-auto py-20 px-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
        <div>
            <p class="font-script text-2xl text-pink-500 mb-1"><?= t('about_tag') ?></p>
            <h2 class="text-3xl font-bold text-gray-800 dark:text-white mb-6"><?= t('about_heading') ?></h2>
            <p class="text-gray-500 dark:text-gray-400 mb-6 leading-relaxed">
                <?= t('about_desc') ?>
            </p>
            <div class="grid grid-cols-3 gap-6">
                <div>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">500+</p>
                    <p class="text-gray-400 dark:text-gray-500 text-sm"><?= t('stat_events_label') ?></p>
                </div>
                <div>
                    <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">10K+</p>
                    <p class="text-gray-400 dark:text-gray-500 text-sm"><?= t('stat_users_label') ?></p>
                </div>
                <div>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">99%</p>
                    <p class="text-gray-400 dark:text-gray-500 text-sm"><?= t('stat_satisfaction_label') ?></p>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl h-80 flex items-center justify-center">
            <i data-lucide="party-popper" class="w-24 h-24 text-white/80"></i>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="bg-gradient-to-r from-blue-700 to-purple-700 dark:from-blue-900 dark:to-purple-900 py-16 px-6 text-center">
    <h2 class="text-2xl md:text-3xl font-bold text-white mb-4"><?= t('cta_heading') ?></h2>
    <p class="text-blue-100 mb-8"><?= t('cta_subtitle') ?></p>
    <a href="auth/register.php" 
       class="inline-flex items-center gap-2 bg-white text-blue-700 px-8 py-3.5 rounded-full font-bold hover:shadow-2xl hover:scale-105 transition-all">
        <?= t('cta_button') ?> <i data-lucide="arrow-right" class="w-5 h-5"></i>
    </a>
</section>

<!-- Footer with Contact -->
<footer id="contact" class="bg-gray-900 text-gray-400 pt-16 pb-8 px-6">
    <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-10 mb-12">
        <div>
            <div class="flex items-center gap-2 text-white font-bold text-lg mb-4">
                <i data-lucide="ticket" class="w-5 h-5"></i> EventPlace
            </div>
            <p class="text-sm leading-relaxed"><?= t('footer_tagline') ?></p>
        </div>
        <div>
            <h4 class="text-white font-semibold mb-4"><?= t('footer_categories_heading') ?></h4>
            <ul class="space-y-2 text-sm">
                <?php foreach ($categoryIcons as $cat => $icon): ?>
                <li><a href="index.php?category=<?= urlencode($cat) ?>#events" class="hover:text-white transition"><?= $icon ?> <?= $cat ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div>
            <h4 class="text-white font-semibold mb-4"><?= t('footer_links_heading') ?></h4>
            <ul class="space-y-2 text-sm">
                <li><a href="#home" class="hover:text-white transition"><?= t('nav_home') ?></a></li>
                <li><a href="#events" class="hover:text-white transition"><?= t('nav_events') ?></a></li>
                <li><a href="#about" class="hover:text-white transition"><?= t('nav_about') ?></a></li>
                <li><a href="auth/login.php" class="hover:text-white transition"><?= t('nav_login') ?></a></li>
            </ul>
        </div>
        <div>
            <h4 class="text-white font-semibold mb-4"><?= t('footer_contact_heading') ?></h4>
            <ul class="space-y-3 text-sm">
                <li class="flex items-center gap-2"><i data-lucide="mail" class="w-4 h-4"></i> support@eventplace.com</li>
                <li class="flex items-center gap-2"><i data-lucide="phone" class="w-4 h-4"></i> +855 12 345 678</li>
                <li class="flex items-center gap-2"><i data-lucide="map-pin" class="w-4 h-4"></i> Phnom Penh, Cambodia</li>
            </ul>
        </div>
    </div>
    <div class="border-t border-gray-800 pt-6 text-center text-sm">
        <p><?= t('footer_copyright') ?></p>
    </div>
</footer>

<!-- Reuse the same theme/language toggle logic that powers the logged-in navbar.
     Its favorites/notifications calls are guarded by `if (element)` checks and
     fail silently as unauthenticated (401) since there's no logged-in user on
     this public page, so nothing here breaks. -->
<script src="/event-booking/assets/js/navbar.js" defer></script>

<script>
    // Translation string(s) needed inside JS, passed from PHP since JS can't call t() directly.
    const i18n = {
        noSearchResults: <?= json_encode(t('no_search_results')) ?>
    };

    lucide.createIcons();

    const menuBtn = document.getElementById('menuBtn');
    const mobileMenu = document.getElementById('mobileMenu');
    menuBtn.addEventListener('click', function() {
        mobileMenu.classList.toggle('hidden');
    });

    // Mirror language toggle for the mobile menu's own switch (small screens
    // hide the top-bar one, so this duplicate keeps it reachable there too)
    const langToggleMobile = document.getElementById('langToggleMobile');
    if (langToggleMobile) {
        langToggleMobile.addEventListener('click', function (e) {
            const target = e.target.closest('[data-lang]');
            if (!target) return;
            fetch(`/event-booking/includes/lang.php?set=${target.dataset.lang}&ajax=1`)
                .then(r => r.json())
                .then(data => { if (data.success) window.location.reload(); });
        });
    }

    // Search Suggestion Logic (unchanged)
    const searchInput = document.getElementById('searchInput');
    const suggestionBox = document.getElementById('suggestionBox');
    let debounceTimer;

    const categoryIcons = {
        'Concert': '🎵', 'Conference': '💼', 'Workshop': '🛠️',
        'Sports': '⚽', 'Exhibition': '🎨', 'General': '📌'
    };

    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const query = this.value.trim();

        if (query.length < 1) {
            suggestionBox.classList.add('hidden');
            suggestionBox.innerHTML = '';
            return;
        }

        debounceTimer = setTimeout(() => {
            fetch(`api/search-suggestions.php?q=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    const results = data.results ?? data; // supports both response shapes
                    if (!results || results.length === 0) {
                        suggestionBox.innerHTML = `
                            <div class="p-4 text-center text-gray-400 text-sm">
                                ${i18n.noSearchResults.replace('%s', query)}
                            </div>`;
                    } else {
                        suggestionBox.innerHTML = results.map(event => `
                            <a href="auth/login.php" class="flex items-center gap-3 p-3 hover:bg-gray-50 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700 last:border-0 transition">
                                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center flex-shrink-0 text-lg">
                                    ${categoryIcons[event.category] || '📌'}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-gray-800 dark:text-gray-100 text-sm truncate">${event.title}</p>
                                    <p class="text-xs text-gray-400 truncate">📍 ${event.location}</p>
                                </div>
                                <span class="text-blue-600 dark:text-blue-400 font-bold text-sm flex-shrink-0">$${parseFloat(event.price).toFixed(2)}</span>
                            </a>
                        `).join('');
                    }
                    suggestionBox.classList.remove('hidden');
                })
                .catch(() => {
                    suggestionBox.classList.add('hidden');
                });
        }, 300);
    });

    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !suggestionBox.contains(e.target)) {
            suggestionBox.classList.add('hidden');
        }
    });

    searchInput.addEventListener('focus', function() {
        if (this.value.trim().length > 0 && suggestionBox.innerHTML.trim() !== '') {
            suggestionBox.classList.remove('hidden');
        }
    });
</script>

</body>
</html>