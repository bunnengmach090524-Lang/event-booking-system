<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Booking</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">

<nav class="bg-white shadow px-6 py-4 flex justify-between items-center">
    <div class="font-bold text-lg text-blue-600">🎫 Event Booking</div>
    <div class="flex gap-6 items-center">
        <a href="/event-booking/customer/events.php" class="text-gray-700 hover:text-blue-600">Events</a>
        <a href="/event-booking/customer/my-tickets.php" class="text-gray-700 hover:text-blue-600">សំបុត្ររបស់ខ្ញុំ</a>
        <span class="text-gray-300">|</span>
        <span class="text-sm text-gray-500">👤 <?= htmlspecialchars($_SESSION['name']) ?></span>
        <a href="/event-booking/auth/logout.php" class="bg-red-500 text-white px-3 py-1 rounded text-sm hover:bg-red-600">Logout</a>
    </div>
</nav>

<div class="max-w-6xl mx-auto mt-8 px-4 pb-16"></div>