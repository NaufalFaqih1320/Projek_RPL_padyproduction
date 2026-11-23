# PADY Production

Sistem manajemen penyewaan dekorasi berbasis web untuk **PADY Production**. Aplikasi ini mengelola booking acara, inventaris alat dekorasi, jadwal, notifikasi, dan komunikasi antara owner, crew, dan klien dalam satu tempat.

Proyek mata kuliah Rekayasa Perangkat Lunak (RPL).

## Fitur

### Autentikasi
- Login berbasis session dengan empat role: `admin`, `owner`, `crew`, `client`.
- Password disimpan dengan bcrypt. Password plain-text lama otomatis di-upgrade ke bcrypt saat login pertama.
- Proteksi brute-force sederhana (lockout sementara setelah beberapa kali gagal login).
- Setelah login, user diarahkan ke dashboard sesuai role-nya.

### Owner
- Dashboard ringkasan.
- CRUD booking, lengkap dengan kode booking otomatis (format `BK-YYYYMMDD-NN`).
- Alur status booking: `Draft → Confirmed → In Progress → Completed`, dan `Cancelled` dari status mana pun yang belum selesai.
- Deteksi konflik jadwal untuk tanggal yang sudah berstatus `Confirmed` atau `In Progress`.
- Kalender jadwal bulanan dengan filter status dan tanggal.
- Checklist kebutuhan dekorasi per booking.
- Halaman inventaris dan chat dengan klien.

### Admin
- Dashboard admin.
- Manajemen user (tambah, ubah, hapus).
- Manajemen inventaris (CRUD, detail, kategori).

### Crew
- Dashboard crew.
- Melihat daftar dan detail booking.
- Mengelola inventaris (CRUD).

### Client
- Dashboard klien.
- Chat dengan owner.

### Fitur Umum
- **Inventaris**: stok total vs stok yang sedang dipakai. Stok otomatis direservasi saat booking dikonfirmasi dan dilepas saat booking selesai atau dibatalkan.
- **Notifikasi**: notifikasi dalam aplikasi untuk perubahan status booking dan pengingat acara.
- **Reminder otomatis**: pengingat H-7, H-3, H-1, dan hari-H (pukul 08.00) untuk klien, owner, dan seluruh crew.
- **Chat**: pesan antara owner dan klien, dengan dukungan balasan FAQ (tabel `chatbot_faq`).
- **Log aktivitas**: riwayat perubahan booking (`booking_logs`) dan inventaris (`inventory_logs`).
- **Flash message** dan pengisian ulang form (old input) saat validasi gagal.

## Teknologi

| Komponen | Teknologi |
|----------|-----------|
| Backend | PHP (native) |
| Database | MySQL / MariaDB (`mysqli`) |
| Frontend | HTML, Tailwind CSS (via CDN) |
| Server lokal | XAMPP / Laragon / WAMP |

## Struktur Proyek

```
padyproduction/
├── index.php            # Router awal: arahkan ke dashboard sesuai role
├── auth/                # Login, proses login, logout
├── config/
│   ├── database.php     # Koneksi database
│   ├── session.php      # Inisialisasi session
│   ├── auth.php         # Guard: wajib login
│   └── helpers.php      # Fungsi bantu (booking, stok, reminder, notifikasi)
├── admin/               # Dashboard, users, inventory
├── owner/               # Dashboard, booking, jadwal, inventaris, chat
├── crew/                # Dashboard, booking (read-only), inventory
├── user/                # Dashboard dan chat klien
└── notification/        # Halaman notifikasi
```

## Tabel Database

Repositori ini belum menyertakan file SQL. Berdasarkan query di kode, tabel yang dibutuhkan adalah:

`users`, `bookings`, `booking_alat`, `kebutuhan_dekorasi`, `booking_logs`, `inventory`, `inventory_categories`, `inventory_logs`, `reminders`, `notifications`, `chats`, `chatbot_faq`

## Instalasi

1. **Clone repositori**
   ```bash
   git clone https://github.com/NaufalFaqih1320/Projek_RPL_padyproduction.git
   ```
2. **Pindahkan folder `padyproduction`** ke direktori web server, misalnya `htdocs` (XAMPP) atau `www` (Laragon).
3. **Buat database** bernama `padyproduction` di MySQL, lalu buat tabel-tabel di atas (import file SQL proyek).
4. **Sesuaikan koneksi** di `padyproduction/config/database.php` jika perlu:
   ```php
   $host     = "localhost";
   $username = "root";
   $password = "";
   $database = "padyproduction";
   ```
5. **Jalankan Apache dan MySQL**, lalu buka:
   ```
   http://localhost/padyproduction/
   ```
6. Login dengan akun yang sudah ada di tabel `users`.

> Karena Tailwind dimuat lewat CDN, perangkat perlu terhubung ke internet agar tampilan muncul dengan benar.

## Alur Status Booking

```
Draft ──► Confirmed ──► In Progress ──► Completed
  │           │              │
  └───────────┴──────────────┴──► Cancelled
```

| Perubahan | Efek |
|-----------|------|
| Draft → Confirmed | Stok inventaris direservasi, notifikasi dikirim |
| Confirmed → In Progress | Crew mendapat notifikasi |
| In Progress → Completed | Reservasi stok dilepas, klien mendapat notifikasi |
| Apa pun → Cancelled | Reservasi stok dilepas, klien mendapat notifikasi |

## Kontributor

- [Naufal Faqih](https://github.com/NaufalFaqih1320)
- [Irsyad Nabil](https://github.com/IrsyadNabil)
