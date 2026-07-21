# 🎫 Event Booking System

ប្រព័ន្ធកក់សំបុត្រ Event អនឡាញ បង្កើតឡើងដោយ PHP + MySQL + Tailwind CSS

## ✨ Features

- 🔐 Authentication (Register/Login) ជាមួយ Role-based Access (Admin/Customer)
- 🎫 Event Management (CRUD) ជាមួយ Image Upload
- 🔍 Browse & Search Events
- 🎟️ Ticket Booking System
- 📱 QR Code Generation សម្រាប់សំបុត្រនីមួយៗ
- ✅ Check-in System (Scan/Input QR Code)
- 📊 Admin Dashboard ជាមួយ Sales Chart (Chart.js)

## 🛠️ Tech Stack

- **Backend**: PHP (Native, PDO)
- **Database**: MySQL
- **Frontend**: Tailwind CSS, Chart.js
- **Libraries**: endroid/qr-code

## 📦 Installation

1. Clone repository:
   \`\`\`bash
   git clone https://github.com/YOUR_USERNAME/event-booking.git
   \`\`\`

2. Import Database:
   - បង្កើត Database ឈ្មោះ `event_booking_db`
   - Import file `database.sql`

3. Configure Database connection:
   - កែ File `config/database.php` (username, password)

4. ដំឡើង Dependencies:
   \`\`\`bash
   composer install
   \`\`\`

5. បង្កើត Folder សម្រាប់ Upload:
   \`\`\`bash
   mkdir uploads/events assets/qrcodes
   \`\`\`

6. បើក Browser: `http://localhost/event-booking`

## 📸 Screenshots

_(បន្ថែម Screenshot នៅទីនេះ)_

## 👨‍💻 Author

_(ឈ្មោះអ្នក)_


# EventPlace navbar — final setup (matched to your project)

## Where each file goes

```
event-booking/
├── includes/
│   ├── lang.php          <- new
│   ├── theme.php         <- new
│   ├── notify.php        <- new
│   ├── functions.php     <- already exists, untouched
│   ├── auth.php          <- already exists, untouched
│   └── ...
├── api/                  <- new top-level folder (separate from admin/api)
│   ├── favorites.php
│   └── notifications.php
├── lang/                 <- new
│   ├── en.php
│   └── km.php
├── assets/
│   └── js/
│       └── navbar.js     <- new
└── customer/
    └── header.php        <- REPLACES your existing customer/header.php
```

## Step 1 — Database
If you haven't already, run `database/schema.sql` from the earlier message. You've already confirmed this succeeded (`favorites` and `notifications` tables exist, `users` has `preferred_lang`/`preferred_theme`).

## Step 2 — Copy files
Copy every file above into place. `customer/header.php` fully replaces your current one — it keeps your existing Events / My tickets links, user name, and logout button exactly as they were, and adds the language toggle, theme toggle, favorites, notifications, and a search bar around them.

## Step 3 — Create one missing page
The navbar links to `/event-booking/customer/favorites.php`, which doesn't exist yet. For now you can either:
- Create a simple page that lists a user's favorited events (I can build this next if you want), or
- Temporarily point that link elsewhere until it's built

## Step 4 — Hook notifications into admin actions
In `admin/events/cancel.php` and `admin/events/reactivate.php`, after a successful action:
```php
require_once __DIR__ . '/../../includes/notify.php';
notify($pdo, $userId, 'booking_cancelled', 'Booking cancelled',
    'Your booking for "' . $eventTitle . '" was cancelled.',
    '/event-booking/customer/my-tickets.php');
```
`$userId` here is whichever user owns that booking — pull it from your `bookings` table row.

## Step 5 — Add favorite hearts to event cards
Wherever you render event cards (likely in `customer/events.php`):
```html
<button onclick="toggleFavorite(<?= $event['id'] ?>, this)" class="text-gray-400 hover:text-red-500">
    <i data-lucide="heart" class="w-4 h-4"></i>
</button>
```

## Step 6 — Test
1. Load any customer page — theme should apply instantly with no flash, matching your last choice.
2. Click the language pill — page reloads in Khmer, stays Khmer on other pages.
3. Click the bell — dropdown opens, badge clears (marks all read).
4. Click a heart on an event card — favorites badge count updates.

## Notes
- Dark mode uses Tailwind's `class` strategy — I added `tailwind.config = { darkMode: 'class' }` in the `<head>`, since the CDN build defaults to OS-preference-only otherwise.
- All API/include URLs are hardcoded to `/event-booking/...` to match the `htdocs`/`www` folder name I saw in your existing links (e.g. `/event-booking/customer/events.php`). If your Laragon virtual host uses a different base path, tell me and I'll adjust every URL in one pass.