<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Booking System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">

    <div class="max-w-4xl mx-auto mt-20 text-center">
        <h1 class="text-4xl font-bold text-gray-800">🎫 Event Booking System</h1>
        <p class="text-gray-500 mt-2">Setup ជោគជ័យ! Database ភ្ជាប់រួចរាល់</p>

        <?php
        require 'config/database.php';
        echo '<p class="text-green-600 mt-4">✅ Database Connected Successfully</p>';
        ?>
    </div>

</body>
</html>