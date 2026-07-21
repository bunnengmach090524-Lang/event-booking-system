<?php
require_once __DIR__ . '/functions.php';
requireAdmin();

$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));

// ===== Sidebar: Badge Counts =====
$activeEventsCount = $pdo->query("SELECT COUNT(*) FROM events WHERE status = 'active' AND event_date >= NOW()")->fetchColumn();
$pendingCheckinCount = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'paid' AND is_checked_in = FALSE")->fetchColumn();
$categoriesCount = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();

// ===== Sidebar: Mini Stats (ថ្ងៃនេះ) =====
$todayBookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$todayRevenue = $pdo->query("SELECT SUM(total_price) FROM bookings WHERE status = 'paid' AND DATE(created_at) = CURDATE()")->fetchColumn() ?: 0;

// ===== Sidebar: Countdown Events ជិតបំផុត (5 events) =====
$sidebarUpcomingEvents = $pdo->query("
    SELECT title, event_date
    FROM events
    WHERE event_date >= NOW() AND status = 'active'
    ORDER BY event_date ASC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Event Booking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-track { background: transparent; }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .animate-in { opacity: 0; animation: fadeInUp .5s ease forwards; }
        .delay-1 { animation-delay: .05s; }
        .delay-2 { animation-delay: .1s; }
        .delay-3 { animation-delay: .15s; }
        .delay-4 { animation-delay: .2s; }
        .delay-5 { animation-delay: .25s; }

        .nav-link { position: relative; }
        .nav-link.active::before {
            content: '';
            position: absolute; left: -16px; top: 50%; transform: translateY(-50%);
            width: 4px; height: 22px; border-radius: 4px;
            background: linear-gradient(180deg, #60a5fa, #c084fc);
        }

        #sidebar { transition: width .3s ease, transform .3s ease; }
        @media (max-width: 1023px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.open { transform: translateX(0); }
        }

        @keyframes pulseBadge {
            0%, 100% { box-shadow: 0 0 0 0 rgba(248,113,113,.6); }
            50% { box-shadow: 0 0 0 5px rgba(248,113,113,0); }
        }
        .badge-pulse { animation: pulseBadge 2s infinite; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">

<div class="flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed lg:static z-40 top-0 left-0 h-full w-64 lg:w-64 bg-gradient-to-b from-slate-900 via-indigo-950 to-slate-900 text-white flex flex-col shadow-2xl">

        <!-- Logo + Collapse Toggle -->
        <div class="flex items-center justify-between px-6 py-6">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-500 rounded-xl flex items-center justify-center shadow-lg shadow-purple-900/40 flex-shrink-0">
                    <i data-lucide="ticket" class="w-5 h-5 text-white"></i>
                </div>
                <div class="sidebar-text min-w-0">
                    <p class="font-bold text-lg leading-none truncate">EventPlace</p>
                    <p class="text-[11px] text-indigo-300 mt-1 tracking-wide uppercase truncate">Admin Panel</p>
                </div>
            </div>
            <button id="collapseBtn" class="hidden lg:flex text-indigo-300 hover:text-white transition flex-shrink-0 p-1">
                <i id="collapseIcon" data-lucide="chevrons-left" class="w-4 h-4"></i>
            </button>
        </div>

        <!-- Today Stats Widget (Collapsible) -->
        <div class="sidebar-text mx-4 mb-3">
            <button type="button" onclick="toggleWidget('todayWidget')"
                class="w-full flex items-center justify-between bg-white/5 border border-white/10 rounded-xl px-3.5 py-2.5 hover:bg-white/10 transition">
                <span class="flex items-center gap-2 text-xs font-semibold text-indigo-300">
                    <i data-lucide="bar-chart-3" class="w-3.5 h-3.5"></i> ថ្ងៃនេះ
                </span>
                <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-indigo-400 transition-transform duration-200" id="todayWidgetChevron"></i>
            </button>
            <div id="todayWidget" class="hidden mt-1.5 bg-white/5 border border-white/10 rounded-xl p-3 space-y-2.5">
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-2 text-xs text-indigo-200">
                        <i data-lucide="ticket" class="w-3.5 h-3.5"></i> Bookings
                    </span>
                    <span class="text-sm font-bold text-white"><?= $todayBookings ?></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-2 text-xs text-indigo-200">
                        <i data-lucide="dollar-sign" class="w-3.5 h-3.5"></i> Revenue
                    </span>
                    <span class="text-sm font-bold text-green-400">$<?= number_format($todayRevenue, 2) ?></span>
                </div>
            </div>
        </div>

        <!-- Nav -->
        <nav class="flex-1 px-4 mt-1 space-y-1 overflow-y-auto">
            <p class="sidebar-text px-3 text-[11px] font-semibold text-indigo-400 uppercase tracking-wider mb-2">មេនុយ</p>

            <a href="/event-booking/admin/dashboard.php"
               class="nav-link <?= $currentPage === 'dashboard.php' ? 'active bg-white/10 text-white' : 'text-indigo-200 hover:bg-white/5 hover:text-white' ?> flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200">
                <i data-lucide="layout-dashboard" class="w-4.5 h-4.5 flex-shrink-0"></i>
                <span class="sidebar-text truncate">Dashboard</span>
            </a>

            <a href="/event-booking/admin/events/index.php"
               class="nav-link <?= $currentDir === 'events' ? 'active bg-white/10 text-white' : 'text-indigo-200 hover:bg-white/5 hover:text-white' ?> flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200">
                <i data-lucide="calendar-days" class="w-4.5 h-4.5 flex-shrink-0"></i>
                <span class="sidebar-text truncate flex-1">Events</span>
                <?php if ($activeEventsCount > 0): ?>
                <span class="sidebar-text bg-blue-500/20 text-blue-300 text-[11px] font-bold px-2 py-0.5 rounded-full flex-shrink-0"><?= $activeEventsCount ?></span>
                <?php endif; ?>
            </a>

            <a href="/event-booking/admin/categories/index.php"
               class="nav-link <?= $currentDir === 'categories' ? 'active bg-white/10 text-white' : 'text-indigo-200 hover:bg-white/5 hover:text-white' ?> flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200">
                <i data-lucide="tags" class="w-4.5 h-4.5 flex-shrink-0"></i>
                <span class="sidebar-text truncate flex-1">ប្រភេទ</span>
                <?php if ($categoriesCount > 0): ?>
                <span class="sidebar-text bg-purple-500/20 text-purple-300 text-[11px] font-bold px-2 py-0.5 rounded-full flex-shrink-0"><?= $categoriesCount ?></span>
                <?php endif; ?>
            </a>

            <a href="/event-booking/admin/bookings/index.php"
               class="nav-link <?= $currentDir === 'bookings' ? 'active bg-white/10 text-white' : 'text-indigo-200 hover:bg-white/5 hover:text-white' ?> flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200">
                <i data-lucide="receipt" class="w-4.5 h-4.5 flex-shrink-0"></i>
                <span class="sidebar-text truncate">Bookings</span>
            </a>

            <a href="/event-booking/admin/customers/index.php"
               class="nav-link <?= $currentDir === 'customers' ? 'active bg-white/10 text-white' : 'text-indigo-200 hover:bg-white/5 hover:text-white' ?> flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200">
                <i data-lucide="users" class="w-4.5 h-4.5 flex-shrink-0"></i>
                <span class="sidebar-text truncate">Customers</span>
            </a>

            <a href="/event-booking/admin/checkin.php"
               class="nav-link <?= $currentPage === 'checkin.php' ? 'active bg-white/10 text-white' : 'text-indigo-200 hover:bg-white/5 hover:text-white' ?> flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200">
                <i data-lucide="qr-code" class="w-4.5 h-4.5 flex-shrink-0"></i>
                <span class="sidebar-text truncate flex-1">Check-in</span>
                <?php if ($pendingCheckinCount > 0): ?>
                <span class="sidebar-text badge-pulse bg-red-500 text-white text-[11px] font-bold px-2 py-0.5 rounded-full flex-shrink-0"><?= $pendingCheckinCount ?></span>
                <?php endif; ?>
            </a>

            <a href="/event-booking/admin/reports/index.php"
               class="nav-link <?= $currentDir === 'reports' ? 'active bg-white/10 text-white' : 'text-indigo-200 hover:bg-white/5 hover:text-white' ?> flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200">
                <i data-lucide="bar-chart-3" class="w-4.5 h-4.5 flex-shrink-0"></i>
                <span class="sidebar-text truncate">Reports</span>
            </a>

            <p class="sidebar-text px-3 text-[11px] font-semibold text-indigo-400 uppercase tracking-wider mb-2 mt-6">ផ្សេងៗ</p>
            <a href="/event-booking/admin/team/index.php"
               class="nav-link <?= $currentDir === 'team' ? 'active bg-white/10 text-white' : 'text-indigo-200 hover:bg-white/5 hover:text-white' ?> flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200">
                <i data-lucide="settings" class="w-4.5 h-4.5 flex-shrink-0"></i>
                <span class="sidebar-text truncate">Team & Admin</span>
            </a>
            <a href="/event-booking/index.php" target="_blank"
               class="text-indigo-200 hover:bg-white/5 hover:text-white flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200">
                <i data-lucide="globe" class="w-4.5 h-4.5 flex-shrink-0"></i>
                <span class="sidebar-text truncate">មើលគេហទំព័រ</span>
            </a>
        </nav>

        <!-- Upcoming Events Widget (Collapsible) -->
        <?php if (!empty($sidebarUpcomingEvents)): ?>
        <div class="sidebar-text mx-4 mb-3">
            <button type="button" onclick="toggleWidget('eventWidget')"
                class="w-full flex items-center justify-between bg-gradient-to-br from-blue-600/20 to-purple-600/20 border border-white/10 rounded-xl px-3.5 py-2.5 hover:brightness-110 transition">
                <span class="flex items-center gap-2 text-xs font-semibold text-indigo-200 min-w-0">
                    <i data-lucide="hourglass" class="w-3.5 h-3.5 flex-shrink-0"></i>
                    <span class="truncate">Event ជិតបំផុត (<?= count($sidebarUpcomingEvents) ?>)</span>
                </span>
                <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-indigo-300 flex-shrink-0 transition-transform duration-200" id="eventWidgetChevron"></i>
            </button>
            <div id="eventWidget" class="hidden mt-1.5 bg-white/5 border border-white/10 rounded-xl p-3 space-y-3 max-h-56 overflow-y-auto">
                <?php foreach ($sidebarUpcomingEvents as $ev): ?>
                <div class="pb-2.5 border-b border-white/5 last:border-0 last:pb-0">
                    <p class="text-xs font-semibold text-white truncate"><?= htmlspecialchars($ev['title']) ?></p>
                    <p class="text-[11px] text-blue-300 mt-0.5 countdown-mini" data-target="<?= date('c', strtotime($ev['event_date'])) ?>">
                        កំពុងគណនា...
                    </p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- User Footer -->
        <div class="px-4 py-5 border-t border-white/10">
            <div class="flex items-center gap-3 px-2">
                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-sm font-bold flex-shrink-0">
                    <?= mb_strtoupper(mb_substr($_SESSION['name'] ?? 'A', 0, 1, 'UTF-8'), 'UTF-8') ?>
                </div>
                <div class="sidebar-text flex-1 min-w-0">
                    <p class="text-sm font-semibold truncate"><?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?></p>
                    <p class="text-xs text-indigo-300">Administrator</p>
                </div>
                <a href="/event-booking/auth/logout.php" title="Logout" class="text-indigo-300 hover:text-red-400 transition flex-shrink-0">
                    <i data-lucide="log-out" class="w-4.5 h-4.5"></i>
                </a>
            </div>
        </div>
    </aside>

    <!-- Mobile overlay -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black/40 z-30 hidden lg:hidden"></div>

    <!-- Main -->
    <div class="flex-1 flex flex-col min-w-0">
        <header class="bg-white/80 backdrop-blur border-b border-gray-100 px-4 lg:px-8 py-4 flex items-center justify-between sticky top-0 z-20">
            <button id="menuBtn" class="lg:hidden text-gray-600 p-2 -ml-2">
                <i data-lucide="menu" class="w-6 h-6"></i>
            </button>
            <div class="hidden lg:block text-sm text-gray-400">
                <?= date('l, d F Y') ?>
            </div>
            <div class="flex items-center gap-2 text-xs font-medium text-gray-500 bg-green-50 px-3 py-1.5 rounded-full">
                <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                System Online
            </div>
        </header>

        <main class="flex-1 overflow-y-auto px-4 lg:px-8 py-6">