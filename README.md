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