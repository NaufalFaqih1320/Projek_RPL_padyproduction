<?php

/**
 * owner/booking/process_add.php — VERSI LENGKAP
 *
 * Menangani:
 * - Validasi lengkap input
 * - Cek konflik jadwal
 * - Cek stok inventaris
 * - Insert booking + kebutuhan_dekorasi + booking_alat
 * - Update quantity_in_use inventaris
 * - Buat reminders otomatis (H-7, H-3, H-1, H)
 * - Notifikasi ke client & crew
 * - Log aktivitas
 */

require_once("../../config/auth.php");
require_once("../../config/database.php");
require_once("../../config/helpers.php");

if ($_SESSION['role'] !== 'owner') {
    header("Location: ../../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: add.php");
    exit();
}

// ── Ambil & sanitasi input ─────────────────────────────────────────────────
$client_user_id  = (int) ($_POST['client_user_id']  ?? 0);
$nama_acara      = sanitize($conn, $_POST['nama_acara']     ?? '');
$jenis_acara     = sanitize($conn, $_POST['jenis_acara']    ?? '');
$tanggal_acara   = sanitize($conn, $_POST['tanggal_acara']  ?? '');
$lokasi          = sanitize($conn, $_POST['lokasi']          ?? '');
$kebutuhan_awal  = sanitize($conn, $_POST['kebutuhan_awal'] ?? '');
$catatan         = sanitize($conn, $_POST['catatan']         ?? '');
$owner_user_id   = (int) $_SESSION['id'];
$kebutuhan_list  = $_POST['kebutuhan']   ?? [];  // array [{nama, jumlah, satuan, keterangan}]
$alat_ids        = $_POST['alat_id']     ?? [];
$alat_jumlah     = $_POST['alat_jumlah'] ?? [];

// ── Validasi ────────────────────────────────────────────────────────────────
$errors = [];

if ($client_user_id <= 0)  $errors[] = "Pilih client terlebih dahulu.";
if (empty($nama_acara))    $errors[] = "Nama acara wajib diisi.";
if (empty($tanggal_acara)) $errors[] = "Tanggal acara wajib diisi.";
if (empty($lokasi))        $errors[] = "Lokasi wajib diisi.";

if (!empty($tanggal_acara) && strtotime($tanggal_acara) < strtotime(date('Y-m-d'))) {
    $errors[] = "Tanggal acara tidak boleh sebelum hari ini.";
}

// Cek konflik jadwal
if (empty($errors) && checkScheduleConflict($conn, $tanggal_acara)) {
    $errors[] = "Tanggal <strong>$tanggal_acara</strong> sudah ada booking lain yang dikonfirmasi. Pilih tanggal lain.";
}

// Validasi client ada
if ($client_user_id > 0) {
    $cek_client = mysqli_fetch_assoc(
        mysqli_query($conn, "SELECT id, name FROM users WHERE id='$client_user_id' AND role='client' LIMIT 1")
    );
    if (!$cek_client) $errors[] = "Data client tidak ditemukan.";
}

// Validasi stok alat
$alat_valid = [];
foreach ($alat_ids as $idx => $inv_id) {
    $inv_id = (int)$inv_id;
    $jml    = max(1, (int)($alat_jumlah[$idx] ?? 1));
    if ($inv_id <= 0) continue;

    $stok = getAvailableStock($conn, $inv_id);
    if ($jml > $stok) {
        $nm = mysqli_fetch_assoc(mysqli_query($conn, "SELECT item_name FROM inventory WHERE id='$inv_id' LIMIT 1"));
        $nm = $nm ? $nm['item_name'] : "ID $inv_id";
        $errors[] = "Stok <strong>$nm</strong> tidak cukup. Tersedia: $stok, diminta: $jml.";
    } else {
        $alat_valid[] = ['inventory_id' => $inv_id, 'jumlah' => $jml];
    }
}

// ── Jika ada error, kembalikan ke form ──────────────────────────────────────
if (!empty($errors)) {
    $_SESSION['booking_errors'] = $errors;
    $_SESSION['booking_old']    = $_POST;
    header("Location: add.php");
    exit();
}

// ── Transaksi database ──────────────────────────────────────────────────────
mysqli_begin_transaction($conn);
try {
    $booking_code = generateBookingCode($conn);

    // Insert booking
    mysqli_query($conn, "
        INSERT INTO bookings
            (booking_code, client_user_id, owner_user_id, nama_acara, jenis_acara,
             tanggal_acara, lokasi, kebutuhan_awal, catatan, status, created_by)
        VALUES
            ('$booking_code', '$client_user_id', '$owner_user_id', '$nama_acara', '$jenis_acara',
             '$tanggal_acara', '$lokasi', '$kebutuhan_awal', '$catatan', 'Draft', '$owner_user_id')
    ");
    $booking_id = mysqli_insert_id($conn);

    // Insert kebutuhan dekorasi
    foreach ($kebutuhan_list as $k) {
        $nm  = sanitize($conn, $k['nama']        ?? '');
        $jml = max(1, (int)($k['jumlah']         ?? 1));
        $sat = sanitize($conn, $k['satuan']       ?? 'buah');
        $ket = sanitize($conn, $k['keterangan']   ?? '');
        if (empty($nm)) continue;
        mysqli_query($conn,
            "INSERT INTO kebutuhan_dekorasi (booking_id, nama_item, jumlah, satuan, keterangan)
             VALUES ('$booking_id', '$nm', '$jml', '$sat', '$ket')"
        );
    }

    // Insert alat & update quantity_in_use
    foreach ($alat_valid as $alat) {
        $inv_id = $alat['inventory_id'];
        $jml    = $alat['jumlah'];
        mysqli_query($conn,
            "INSERT INTO booking_alat (booking_id, inventory_id, jumlah_dipakai)
             VALUES ('$booking_id', '$inv_id', '$jml')"
        );
        // Reservasi stok (langsung saat Draft — sesuaikan jika reservasi baru saat Confirmed)
        mysqli_query($conn,
            "UPDATE inventory SET quantity_in_use = quantity_in_use + $jml WHERE id='$inv_id'"
        );
    }

    // Buat reminders otomatis
    createReminders($conn, $booking_id, $tanggal_acara, $client_user_id, $owner_user_id);

    // Notifikasi ke client
    insertNotification($conn, $client_user_id,
        "Booking Baru Dibuat",
        "Booking Anda [{$booking_code}] untuk acara '{$nama_acara}' pada " . formatTanggal($tanggal_acara) . " telah berhasil dibuat. Menunggu konfirmasi."
    );

    // Notifikasi ke semua crew
    $crews = mysqli_query($conn, "SELECT id FROM users WHERE role='crew'");
    while ($crew = mysqli_fetch_assoc($crews)) {
        insertNotification($conn, (int)$crew['id'],
            "Booking Baru: $nama_acara",
            "Ada booking baru [{$booking_code}] untuk acara '{$nama_acara}' pada " . formatTanggal($tanggal_acara) . " di $lokasi."
        );
    }

    // Log aktivitas
    logBooking($conn, $booking_id, $owner_user_id, 'Tambah',
        "Booking {$booking_code} ({$nama_acara}) berhasil dibuat."
    );

    mysqli_commit($conn);

    redirectWithMessage("detail.php?id=$booking_id", "success",
        "Booking <strong>$booking_code</strong> berhasil dibuat!"
    );

} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['booking_errors'] = ["Terjadi kesalahan sistem: " . $e->getMessage()];
    header("Location: add.php");
    exit();
}