<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
require_once __DIR__ . '/../includes/lang.php';

$userInitial = mb_strtoupper(mb_substr($_SESSION['name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Booking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' };</script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
    </style>
    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-gray-50 dark:bg-gray-900 dark:text-gray-100">

<nav class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 sticky top-0 z-50 shadow-sm">
    <!-- Row 1: Logo + Nav (desktop) + Icons + Hamburger -->
    <div class="px-4 sm:px-6 py-3 flex items-center gap-3">
        <!-- Logo -->
        <div class="flex items-center gap-2 font-bold text-lg text-gray-800 dark:text-white flex-shrink-0">
            <div class="w-9 h-9 bg-gradient-to-br from-blue-600 to-purple-600 rounded-lg flex items-center justify-center">
                <i data-lucide="ticket" class="w-5 h-5 text-white"></i>
            </div>
            <span class="hidden sm:inline">EventPlace</span>
        </div>

        <!-- Desktop Nav Links (md+) -->
        <div class="hidden md:flex gap-1 items-center flex-shrink-0">
            <a href="/event-booking/customer/events.php"
               class="flex items-center gap-2 px-3 lg:px-4 py-2 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-blue-600 transition whitespace-nowrap">
                <i data-lucide="compass" class="w-4 h-4"></i> <?= t('nav_events') ?>
            </a>
            <a href="/event-booking/customer/my-tickets.php"
               class="flex items-center gap-2 px-3 lg:px-4 py-2 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-blue-600 transition whitespace-nowrap">
                <i data-lucide="ticket" class="w-4 h-4"></i> <?= t('nav_tickets') ?>
            </a>
        </div>

        <!-- Search Box (visible md+, inline on lg+, own row on md/tablet via order) -->
        <div class="hidden md:flex flex-1 justify-center px-2">
            <div class="flex items-center gap-2 bg-gray-100 dark:bg-gray-700 rounded-lg px-3 h-9 w-full max-w-xs relative">
                <i data-lucide="search" class="w-4 h-4 text-gray-400 flex-shrink-0"></i>
                <input type="text" id="navSearchInput" autocomplete="off" placeholder="<?= t('nav_search') ?>"
                    class="bg-transparent outline-none text-sm text-gray-600 dark:text-gray-200 w-full min-w-0 placeholder-gray-400">
                <div id="searchSuggestions" hidden
                    class="absolute left-0 right-0 top-full mt-2 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-lg shadow-lg py-2 text-sm max-h-96 overflow-y-auto z-50">
                </div>
            </div>
        </div>

        <!-- Icon Cluster -->
        <div class="flex items-center gap-1.5 sm:gap-2 ml-auto flex-shrink-0">
            <!-- Language toggle (hidden on very small screens) -->
            <div id="langToggle" data-current="<?= htmlspecialchars($currentLang) ?>"
                class="flex items-center bg-gray-100 dark:bg-gray-700 rounded-full p-1 text-xs font-medium cursor-pointer select-none">
                <span data-lang="en" class="lang-option px-2 sm:px-2.5 py-1 rounded-full text-gray-500 dark:text-gray-300">EN</span>
                <span data-lang="km" class="lang-option px-2 sm:px-2.5 py-1 rounded-full text-gray-500 dark:text-gray-300">ខ្មែរ</span>
            </div>

            <!-- Theme toggle -->
            <button id="themeToggle" aria-label="Toggle theme"
                    class="w-9 h-9 flex items-center justify-center rounded-lg text-gray-500 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition flex-shrink-0">
                <i data-lucide="sun" class="w-4 h-4 dark:hidden"></i>
                <i data-lucide="moon" class="w-4 h-4 hidden dark:block"></i>
            </button>

            <!-- Favorites -->
            <a href="/event-booking/customer/favorites.php" aria-label="<?= t('nav_favorites') ?>"
               class="relative w-9 h-9 flex items-center justify-center rounded-lg text-gray-500 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition flex-shrink-0">
                <i data-lucide="heart" class="w-4 h-4"></i>
                <span id="favoritesCount" hidden
                      class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] leading-none rounded-full px-1.5 py-0.5">0</span>
            </a>

            <!-- Notifications -->
            <div class="relative flex-shrink-0">
                <button id="notificationsBtn" aria-label="<?= t('nav_notifications') ?>"
                        class="relative w-9 h-9 flex items-center justify-center rounded-lg text-gray-500 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    <i data-lucide="bell" class="w-4 h-4"></i>
                    <span id="notificationsCount" hidden
                          class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] leading-none rounded-full px-1.5 py-0.5">0</span>
                </button>
                <div id="notificationsDropdown" hidden
                     class="absolute right-0 mt-2 w-72 max-w-[90vw] bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-lg shadow-lg py-2 text-sm max-h-80 overflow-y-auto">
                    <p class="px-4 py-2 text-gray-400 dark:text-gray-500 text-xs">No notifications yet</p>
                </div>
            </div>

            <!-- Avatar + Name (sm+) -->
            <div class="hidden sm:flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 border-r border-gray-200 dark:border-gray-700 pr-2 sm:pr-3 flex-shrink-0">
                <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center text-purple-600 dark:text-purple-300 font-semibold text-xs flex-shrink-0">
                    <?= htmlspecialchars($userInitial) ?>
                </div>
                <span class="hidden lg:inline whitespace-nowrap"><?= htmlspecialchars($_SESSION['name']) ?></span>
            </div>

            <!-- Logout -->
            <a href="/event-booking/auth/logout.php"
               class="flex items-center gap-1.5 bg-red-50 dark:bg-red-900/40 text-red-600 dark:text-red-300 px-2.5 sm:px-3.5 py-2 rounded-lg text-sm font-medium hover:bg-red-100 dark:hover:bg-red-900 transition flex-shrink-0">
                <i data-lucide="log-out" class="w-4 h-4"></i>
                <span class="hidden lg:inline"><?= t('nav_logout') ?></span>
            </a>

            <!-- Hamburger (mobile only, <md) -->
            <button id="mobileMenuBtn" aria-label="Menu"
                    class="md:hidden w-9 h-9 flex items-center justify-center rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition flex-shrink-0">
                <i data-lucide="menu" class="w-5 h-5"></i>
            </button>
        </div>
    </div>

    <!-- Row 2: Mobile Search (only <md, shown always as its own row) -->
    <div class="md:hidden px-4 sm:px-6 pb-3">
        <div class="flex items-center gap-2 bg-gray-100 dark:bg-gray-700 rounded-lg px-3 py-2 relative">
            <i data-lucide="search" class="w-4 h-4 text-gray-400 flex-shrink-0"></i>
            <input type="text" id="navSearchInputMobile" autocomplete="off" placeholder="<?= t('nav_search') ?>"
                   class="bg-transparent outline-none text-sm text-gray-600 dark:text-gray-200 w-full placeholder-gray-400">
            <div id="searchSuggestionsMobile" hidden
                 class="absolute left-0 right-0 top-full mt-2 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-lg shadow-lg py-2 text-sm max-h-96 overflow-y-auto z-50">
            </div>
        </div>
    </div>

    <!-- Mobile Menu (Nav Links, <md, toggled by hamburger) -->
    <div id="mobileMenu" hidden class="md:hidden border-t border-gray-100 dark:border-gray-700 px-4 sm:px-6 py-3 space-y-1">
        <a href="/event-booking/customer/events.php"
           class="flex items-center gap-2 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
            <i data-lucide="compass" class="w-4 h-4"></i> <?= t('nav_events') ?>
        </a>
        <a href="/event-booking/customer/my-tickets.php"
           class="flex items-center gap-2 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
            <i data-lucide="ticket" class="w-4 h-4"></i> <?= t('nav_tickets') ?>
        </a>
        <!-- Language toggle for very small screens where it's hidden in the icon row -->
        <div class="xs:hidden flex items-center justify-between px-3 py-2.5">
            <span class="text-sm text-gray-500 dark:text-gray-400"><?= t('nav_events') ?? 'Language' ?></span>
            <div id="langToggleMobile" data-current="<?= htmlspecialchars($currentLang) ?>"
                 class="flex items-center bg-gray-100 dark:bg-gray-700 rounded-full p-1 text-xs font-medium cursor-pointer select-none">
                <span data-lang="en" class="lang-option px-2.5 py-1 rounded-full text-gray-500 dark:text-gray-300">EN</span>
                <span data-lang="km" class="lang-option px-2.5 py-1 rounded-full text-gray-500 dark:text-gray-300">ខ្មែរ</span>
            </div>
        </div>
        <div class="sm:hidden flex items-center gap-2 px-3 py-2.5 text-sm text-gray-600 dark:text-gray-300">
            <div class="w-7 h-7 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center text-purple-600 dark:text-purple-300 font-semibold text-xs">
                <?= htmlspecialchars($userInitial) ?>
            </div>
            <?= htmlspecialchars($_SESSION['name']) ?>
        </div>
    </div>
</nav>

<script src="/event-booking/assets/js/navbar.js" defer></script>
<script>
    // Mobile hamburger menu toggle (kept separate from navbar.js to avoid touching working code)
    document.addEventListener('DOMContentLoaded', function () {
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileMenu = document.getElementById('mobileMenu');
        if (mobileMenuBtn && mobileMenu) {
            mobileMenuBtn.addEventListener('click', function () {
                mobileMenu.hidden = !mobileMenu.hidden;
            });
        }

        // Mirror the mobile search input to the same suggestion logic as desktop.
        // We reuse the desktop #navSearchInput handlers by simply forwarding
        // typed value + firing the same fetch, since navbar.js targets IDs directly
        // we duplicate a light fetch call here for the mobile input.
        const mobileInput = document.getElementById('navSearchInputMobile');
        const mobileDropdown = document.getElementById('searchSuggestionsMobile');
        let mobileDebounce = null;

        if (mobileInput) {
            mobileInput.addEventListener('input', function () {
                const q = mobileInput.value.trim();
                clearTimeout(mobileDebounce);

                if (q.length < 2) {
                    mobileDropdown.hidden = true;
                    return;
                }

                mobileDebounce = setTimeout(() => {
                    fetch(`/event-booking/api/search-suggestions.php?q=${encodeURIComponent(q)}`)
                        .then(r => r.json())
                        .then(data => {
                            if (!data.success) return;
                            if (data.results.length === 0) {
                                mobileDropdown.innerHTML = '<p class="px-4 py-3 text-gray-400 dark:text-gray-500 text-xs">No events found</p>';
                                mobileDropdown.hidden = false;
                                return;
                            }
                            mobileDropdown.innerHTML = data.results.map(ev => `
                                <a href="/event-booking/customer/event-detail.php?id=${ev.id}"
                                   class="flex items-center gap-3 px-3 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-700 border-b border-gray-50 dark:border-gray-700 last:border-0">
                                    <div class="w-10 h-10 rounded-md bg-gradient-to-br from-blue-500 to-purple-600 flex-shrink-0 overflow-hidden flex items-center justify-center text-white text-xs">
                                        ${ev.image ? `<img src="/event-booking/uploads/events/${ev.image}" class="w-full h-full object-cover">` : '🎫'}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-medium text-gray-700 dark:text-gray-200 truncate">${ev.title}</p>
                                        <p class="text-gray-400 dark:text-gray-500 text-xs truncate">${ev.location} · $${Number(ev.price).toFixed(2)}</p>
                                    </div>
                                </a>
                            `).join('');
                            mobileDropdown.hidden = false;
                        })
                        .catch(() => { mobileDropdown.hidden = true; });
                }, 250);
            });

            document.addEventListener('click', function (e) {
                if (!mobileInput.contains(e.target) && !mobileDropdown.contains(e.target)) {
                    mobileDropdown.hidden = true;
                }
            });
        }

        // Language toggle mirrored for mobile menu (uses same lang.php endpoint)
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
    });
</script>

<div class="max-w-6xl mx-auto mt-8 px-4 pb-16">