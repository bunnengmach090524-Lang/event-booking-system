<?php
require_once __DIR__ . '/functions.php';
requireAdmin();
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
    </style>
</head>
<body class="bg-gray-50">

<nav class="bg-white border-b border-gray-100 px-6 py-3.5 flex justify-between items-center sticky top-0 z-50 shadow-sm">
    <div class="flex items-center gap-2 font-bold text-lg text-gray-800">
        <div class="w-9 h-9 bg-gradient-to-br from-blue-600 to-purple-600 rounded-lg flex items-center justify-center">
            <i data-lucide="ticket" class="w-5 h-5 text-white"></i>
        </div>
        <span class="hidden sm:inline">Admin Panel</span>
    </div>

    <div class="hidden md:flex gap-1 items-center">
        <a href="/event-booking/admin/dashboard.php" 
           class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-blue-600 transition">
            <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Dashboard
        </a>
        <a href="/event-booking/admin/events/index.php" 
           class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-blue-600 transition">
            <i data-lucide="calendar" class="w-4 h-4"></i> Events
        </a>
        <a href="/event-booking/admin/checkin.php" 
           class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-blue-600 transition">
            <i data-lucide="qr-code" class="w-4 h-4"></i> Check-in
        </a>
    </div>

    <div class="flex items-center gap-3">
        <div class="hidden sm:flex items-center gap-2 text-sm text-gray-600 border-r border-gray-200 pr-3">
            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                <i data-lucide="user" class="w-4 h-4 text-blue-600"></i>
            </div>
            <?= htmlspecialchars($_SESSION['name']) ?>
        </div>
        <a href="/event-booking/auth/logout.php" 
           class="flex items-center gap-1.5 bg-red-50 text-red-600 px-3.5 py-2 rounded-lg text-sm font-medium hover:bg-red-100 transition">
            <i data-lucide="log-out" class="w-4 h-4"></i>
            <span class="hidden sm:inline">Logout</span>
        </a>
    </div>
</nav>

<div class="max-w-6xl mx-auto mt-8 px-4 pb-16">