<?php
require '../../config/database.php';
require '../../includes/header.php';

$id = $_GET['id'] ?? null;
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    redirect('/event-booking/admin/events/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $location = trim($_POST['location']);
    $event_date = $_POST['event_date'];
    $price = $_POST['price'];
    $total_tickets = $_POST['total_tickets'];

    $imageName = $event['image'];

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $imageName = time() . '_' . basename($_FILES['image']['name']);
        $targetPath = '../../uploads/events/' . $imageName;
        move_uploaded_file($_FILES['image']['tmp_name'], $targetPath);
    }

    $stmt = $pdo->prepare("UPDATE events SET title=?, description=?, image=?, location=?, event_date=?, price=?, total_tickets=? WHERE id=?");
    $stmt->execute([$title, $description, $imageName, $location, $event_date, $price, $total_tickets, $id]);

    redirect('/event-booking/admin/events/index.php');
}
?>

<h1 class="text-2xl font-bold text-gray-800 mb-6">✏️ កែប្រែ Event</h1>

<div class="bg-white p-6 rounded-lg shadow max-w-2xl">
    <form method="POST" enctype="multipart/form-data">
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">ចំណងជើង Event</label>
            <input type="text" name="title" required value="<?= htmlspecialchars($event['title']) ?>"
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">ការពិពណ៌នា</label>
            <textarea name="description" rows="4"
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($event['description']) ?></textarea>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">ទីតាំង</label>
            <input type="text" name="location" required value="<?= htmlspecialchars($event['location']) ?>"
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ថ្ងៃ + ម៉ោង</label>
                <input type="datetime-local" name="event_date" required 
                    value="<?= date('Y-m-d\TH:i', strtotime($event['event_date'])) ?>"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">តម្លៃ ($)</label>
                <input type="number" step="0.01" name="price" required value="<?= $event['price'] ?>"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">ចំនួនសំបុត្រសរុប</label>
            <input type="number" name="total_tickets" required value="<?= $event['total_tickets'] ?>"
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <?php if ($event['image']): ?>
        <div class="mb-4">
            <p class="text-sm text-gray-500 mb-2">រូបភាពបច្ចុប្បន្ន:</p>
            <img src="/event-booking/uploads/events/<?= htmlspecialchars($event['image']) ?>" class="w-32 rounded">
        </div>
        <?php endif; ?>

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">ប្តូររូបភាព (ទុកទទេ បើមិនប្តូរ)</label>
            <input type="file" name="image" accept="image/*"
                class="w-full border border-gray-300 rounded-md px-3 py-2">
        </div>

        <div class="flex gap-3">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700">
                រក្សាទុកការកែប្រែ
            </button>
            <a href="index.php" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-300">
                បោះបង់
            </a>
        </div>
    </form>
</div>

<?php require '../../includes/footer.php'; ?>