<?php
require '../../config/database.php';
require '../../includes/header.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $location = trim($_POST['location']);
    $event_date = $_POST['event_date'];
    $price = $_POST['price'];
    $total_tickets = $_POST['total_tickets'];

    $imageName = null;

    // Upload រូបភាព
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $imageName = time() . '_' . basename($_FILES['image']['name']);
        $targetPath = '../../uploads/events/' . $imageName;
        move_uploaded_file($_FILES['image']['tmp_name'], $targetPath);
    }

    $stmt = $pdo->prepare("INSERT INTO events (title, description, image, location, event_date, price, total_tickets, created_by) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$title, $description, $imageName, $location, $event_date, $price, $total_tickets, $_SESSION['user_id']]);

    redirect('/event-booking/admin/events/index.php');
}
?>

<h1 class="text-2xl font-bold text-gray-800 mb-6">➕ បង្កើត Event ថ្មី</h1>

<div class="bg-white p-6 rounded-lg shadow max-w-2xl">
    <form method="POST" enctype="multipart/form-data">
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">ចំណងជើង Event</label>
            <input type="text" name="title" required
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">ការពិពណ៌នា</label>
            <textarea name="description" rows="4"
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">ទីតាំង</label>
            <input type="text" name="location" required
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ថ្ងៃ + ម៉ោង</label>
                <input type="datetime-local" name="event_date" required
                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">តម្លៃ ($)</label>
                <input type="number" step="0.01" name="price" required
                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">ចំនួនសំបុត្រសរុប</label>
            <input type="number" name="total_tickets" required
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">រូបភាព</label>
            <input type="file" name="image" accept="image/*"
                class="w-full border border-gray-300 rounded-md px-3 py-2">
        </div>

        <div class="flex gap-3">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700">
                រក្សាទុក
            </button>
            <a href="index.php" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-300">
                បោះបង់
            </a>
        </div>
    </form>
</div>

<?php require '../../includes/footer.php'; ?>