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
    header("Location: index.php");
    exit();
}

$id            = (int) $_POST['id'];
$nama_acara    = sanitize($conn, $_POST['nama_acara']);
$jenis_acara   = sanitize($conn, $_POST['jenis_acara'] ?? '');
$tanggal_acara = sanitize($conn, $_POST['tanggal_acara']);
$lokasi        = sanitize($conn, $_POST['lokasi']);
$kebutuhan_awal= sanitize($conn, $_POST['kebutuhan_awal'] ?? '');
$catatan       = sanitize($conn, $_POST['catatan'] ?? '');
$status        = sanitize($conn, $_POST['status']);
$kebutuhan_list= $_POST['kebutuhan'] ?? [];

$errors = [];
if (empty($nama_acara))    $errors[] = "Nama acara wajib diisi.";
if (empty($tanggal_acara)) $errors[] = "Tanggal acara wajib diisi.";
if (empty($lokasi))        $errors[] = "Lokasi wajib diisi.";

if (!empty($tanggal_acara) && strtotime($tanggal_acara) < strtotime(date('Y-m-d'))) {
    $errors[] = "Tanggal acara tidak boleh sebelum hari ini.";
}

if (empty($errors) && checkScheduleConflict($conn, $tanggal_acara, $id)) {
    $errors[] = "Tanggal $tanggal_acara sudah ada booking lain (konflik jadwal).";
}

if (!empty($errors)) {
    $_SESSION['booking_errors'] = $errors;
    header("Location: edit.php?id=$id");
    exit();
}

mysqli_begin_transaction($conn);
try {
    mysqli_query($conn, "UPDATE bookings SET
        nama_acara='$nama_acara', jenis_acara='$jenis_acara',
        tanggal_acara='$tanggal_acara', lokasi='$lokasi',
        kebutuhan_awal='$kebutuhan_awal', catatan='$catatan', status='$status'
        WHERE id='$id'");

    // Hapus kebutuhan lama, insert baru
    mysqli_query($conn, "DELETE FROM kebutuhan_dekorasi WHERE booking_id='$id'");
    foreach ($kebutuhan_list as $item) {
        $nm  = sanitize($conn, $item['nama'] ?? '');
        $jml = (int)($item['jumlah'] ?? 1);
        $ket = sanitize($conn, $item['catatan'] ?? '');
        if (empty($nm)) continue;
        mysqli_query($conn, "INSERT INTO kebutuhan_dekorasi (booking_id,nama_kebutuhan,jumlah,catatan)
            VALUES ('$id','$nm','$jml','$ket')");
    }

    mysqli_commit($conn);
    redirectWithMessage("index.php", "success", "Booking berhasil diperbarui.");
} catch (Exception $e) {
    mysqli_rollback($conn);
    redirectWithMessage("edit.php?id=$id", "danger", "Gagal update: " . $e->getMessage());
}
