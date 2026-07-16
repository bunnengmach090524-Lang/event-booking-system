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
</head>
<body class="bg-gray-100">

<nav class="bg-gray-800 text-white px-6 py-4 flex justify-between items-center">
    <div class="font-bold text-lg">🎫 Admin Panel</div>
    <div class="flex gap-6 items-center">
        <a href="/event-booking/admin/dashboard.php" class="hover:text-blue-300">Dashboard</a>
        <a href="/event-booking/admin/events/index.php" class="hover:text-blue-300">Events</a>
        <a href="/event-booking/admin/checkin.php" class="hover:text-blue-300">Check-in</a>
        <span class="text-gray-400">|</span>
        <span class="text-sm">👤 <?= htmlspecialchars($_SESSION['name']) ?></span>
        <a href="/event-booking/auth/logout.php" class="bg-red-500 px-3 py-1 rounded text-sm hover:bg-red-600">Logout</a>
    </div>
</nav>

<div class="max-w-6xl mx-auto mt-8 px-4"></div>