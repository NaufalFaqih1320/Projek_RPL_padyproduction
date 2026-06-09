<?php

session_start();
require_once("../../config/database.php");
require_once("../../config/auth.php");
require_once("../../config/helpers.php");

if ($_SESSION['role'] !== 'owner') {
    header("Location: ../../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: add.php");
    exit();
}

// Ambil & sanitasi input
$client_user_id  = (int) $_POST['client_user_id'];
$nama_acara      = sanitize($conn, $_POST['nama_acara']);
$jenis_acara     = sanitize($conn, $_POST['jenis_acara'] ?? '');
$tanggal_acara   = sanitize($conn, $_POST['tanggal_acara']);
$tanggal_booking = date('Y-m-d');
$lokasi          = sanitize($conn, $_POST['lokasi']);
$kebutuhan_awal  = sanitize($conn, $_POST['kebutuhan_awal'] ?? '');
$catatan         = sanitize($conn, $_POST['catatan'] ?? '');
$owner_user_id   = (int) $_SESSION['id'];
$kebutuhan_list  = $_POST['kebutuhan']   ?? [];
$alat_ids        = $_POST['alat_id']     ?? [];
$alat_jumlah     = $_POST['alat_jumlah'] ?? [];

// Validasi
$errors = [];
if ($client_user_id <= 0)  $errors[] = "Pilih client terlebih dahulu.";
if (empty($nama_acara))    $errors[] = "Nama acara wajib diisi.";
if (empty($tanggal_acara)) $errors[] = "Tanggal acara wajib diisi.";
if (empty($lokasi))        $errors[] = "Lokasi wajib diisi.";

if (!empty($tanggal_acara) && strtotime($tanggal_acara) < strtotime(date('Y-m-d'))) {
    $errors[] = "Tanggal acara tidak boleh sebelum hari ini.";
}

if (empty($errors) && checkScheduleConflict($conn, $tanggal_acara)) {
    $errors[] = "Tanggal {$tanggal_acara} sudah ada booking lain (konflik jadwal).";
}

$cek_client = null;
if ($client_user_id > 0) {
    $cek_client = mysqli_fetch_assoc(
        mysqli_query($conn, "SELECT * FROM users WHERE id='$client_user_id' AND role='client' LIMIT 1")
    );
    if (!$cek_client) $errors[] = "Data client tidak ditemukan.";
}

foreach ($alat_ids as $idx => $inv_id) {
    $inv_id = (int)$inv_id;
    $jml    = (int)($alat_jumlah[$idx] ?? 1);
    if ($jml < 1 || $inv_id <= 0) continue;
    $avail  = getAvailableStock($conn, $inv_id);
    if ($jml > $avail) {
        $nm = mysqli_fetch_assoc(mysqli_query($conn, "SELECT item_name FROM inventory WHERE id='$inv_id'"))['item_name'] ?? "#$inv_id";
        $errors[] = "Stok '$nm' tidak cukup. Tersedia: $avail unit.";
    }
}

if (!empty($errors)) {
    $_SESSION['booking_errors'] = $errors;
    $_SESSION['booking_old']    = $_POST;
    header("Location: add.php");
    exit();
}

// Simpan dengan transaksi
mysqli_begin_transaction($conn);
try {
    $booking_code = generateBookingCode($conn);

    mysqli_query($conn, "INSERT INTO bookings
        (booking_code,client_user_id,owner_user_id,nama_acara,jenis_acara,
         tanggal_booking,tanggal_acara,lokasi,kebutuhan_awal,catatan,status)
        VALUES
        ('$booking_code','$client_user_id','$owner_user_id','$nama_acara','$jenis_acara',
         '$tanggal_booking','$tanggal_acara','$lokasi','$kebutuhan_awal','$catatan','Confirmed')");
    $booking_id = mysqli_insert_id($conn);

    foreach ($kebutuhan_list as $item) {
        $nm  = sanitize($conn, $item['nama'] ?? '');
        $jml = (int)($item['jumlah'] ?? 1);
        $ket = sanitize($conn, $item['catatan'] ?? '');
        if (empty($nm)) continue;
        mysqli_query($conn, "INSERT INTO kebutuhan_dekorasi (booking_id,nama_kebutuhan,jumlah,catatan)
            VALUES ('$booking_id','$nm','$jml','$ket')");
    }

    foreach ($alat_ids as $idx => $inv_id) {
        $inv_id = (int)$inv_id;
        $jml    = (int)($alat_jumlah[$idx] ?? 1);
        if ($inv_id <= 0 || $jml < 1) continue;
        mysqli_query($conn, "INSERT INTO booking_alat (booking_id,inventory_id,jumlah_dipakai)
            VALUES ('$booking_id','$inv_id','$jml')");
        mysqli_query($conn, "UPDATE inventory SET quantity_in_use=quantity_in_use+'$jml' WHERE id='$inv_id'");
    }

    createReminders($conn, $booking_id, $tanggal_acara, $client_user_id, $owner_user_id);

    mysqli_commit($conn);
    redirectWithMessage("index.php", "success", "Booking <strong>$booking_code</strong> berhasil ditambahkan!");
} catch (Exception $e) {
    mysqli_rollback($conn);
    redirectWithMessage("add.php", "danger", "Gagal: " . $e->getMessage());
}
