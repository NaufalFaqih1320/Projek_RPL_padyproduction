<?php

/**
 * owner/booking/process_edit.php — VERSI LENGKAP
 */

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

$id             = (int) ($_POST['id']            ?? 0);
$nama_acara     = sanitize($conn, $_POST['nama_acara']     ?? '');
$jenis_acara    = sanitize($conn, $_POST['jenis_acara']    ?? '');
$tanggal_acara  = sanitize($conn, $_POST['tanggal_acara']  ?? '');
$lokasi         = sanitize($conn, $_POST['lokasi']          ?? '');
$kebutuhan_awal = sanitize($conn, $_POST['kebutuhan_awal'] ?? '');
$catatan        = sanitize($conn, $_POST['catatan']         ?? '');
$kebutuhan_list = $_POST['kebutuhan'] ?? [];

// Ambil data booking lama
$booking_lama = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT * FROM bookings WHERE id='$id' LIMIT 1")
);
if (!$booking_lama || $id <= 0) {
    header("Location: index.php");
    exit();
}

// Validasi hanya bisa edit jika masih Draft atau Confirmed
if (in_array($booking_lama['status'], ['Completed','Cancelled'])) {
    redirectWithMessage("detail.php?id=$id", "danger",
        "Booking dengan status '{$booking_lama['status']}' tidak dapat diubah."
    );
}

$errors = [];
if (empty($nama_acara))    $errors[] = "Nama acara wajib diisi.";
if (empty($tanggal_acara)) $errors[] = "Tanggal acara wajib diisi.";
if (empty($lokasi))        $errors[] = "Lokasi wajib diisi.";

if (!empty($tanggal_acara) && strtotime($tanggal_acara) < strtotime(date('Y-m-d'))) {
    $errors[] = "Tanggal acara tidak boleh sebelum hari ini.";
}

// Cek konflik jadwal (kecualikan booking ini sendiri)
if (empty($errors) && checkScheduleConflict($conn, $tanggal_acara, $id)) {
    $errors[] = "Tanggal <strong>$tanggal_acara</strong> sudah ada booking lain yang dikonfirmasi.";
}

if (!empty($errors)) {
    $_SESSION['booking_errors'] = $errors;
    header("Location: edit.php?id=$id");
    exit();
}

mysqli_begin_transaction($conn);
try {
    mysqli_query($conn, "
        UPDATE bookings SET
            nama_acara     = '$nama_acara',
            jenis_acara    = '$jenis_acara',
            tanggal_acara  = '$tanggal_acara',
            lokasi         = '$lokasi',
            kebutuhan_awal = '$kebutuhan_awal',
            catatan        = '$catatan'
        WHERE id = '$id'
    ");

    // Update kebutuhan dekorasi (hapus & insert ulang)
    mysqli_query($conn, "DELETE FROM kebutuhan_dekorasi WHERE booking_id='$id'");
    foreach ($kebutuhan_list as $k) {
        $nm  = sanitize($conn, $k['nama']      ?? '');
        $jml = max(1, (int)($k['jumlah']       ?? 1));
        $sat = sanitize($conn, $k['satuan']    ?? 'buah');
        $ket = sanitize($conn, $k['keterangan'] ?? '');
        if (empty($nm)) continue;
        mysqli_query($conn,
            "INSERT INTO kebutuhan_dekorasi (booking_id, nama_item, jumlah, satuan, keterangan)
             VALUES ('$id', '$nm', '$jml', '$sat', '$ket')"
        );
    }

    // Perbarui reminders jika tanggal berubah
    if ($tanggal_acara !== $booking_lama['tanggal_acara']) {
        createReminders($conn, $id, $tanggal_acara,
            (int)$booking_lama['client_user_id'],
            (int)$booking_lama['owner_user_id']
        );
    }

    // Notifikasi ke client
    insertNotification($conn, (int)$booking_lama['client_user_id'],
        "Booking Diperbarui [{$booking_lama['booking_code']}]",
        "Detail booking Anda untuk '{$nama_acara}' pada " . formatTanggal($tanggal_acara) . " telah diperbarui."
    );

    logBooking($conn, $id, (int)$_SESSION['id'], 'Edit',
        "Booking {$booking_lama['booking_code']} diperbarui: acara '{$nama_acara}' tgl {$tanggal_acara}."
    );

    mysqli_commit($conn);
    redirectWithMessage("detail.php?id=$id", "success", "Booking berhasil diperbarui!");

} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['booking_errors'] = ["Kesalahan sistem: " . $e->getMessage()];
    header("Location: edit.php?id=$id");
    exit();
}