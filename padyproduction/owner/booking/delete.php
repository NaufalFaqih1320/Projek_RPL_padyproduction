<?php

/**
 * owner/booking/delete.php
 * Hanya bisa hapus booking berstatus Draft atau Cancelled
 */

session_start();
require_once("../../config/database.php");
require_once("../../config/auth.php");
require_once("../../config/helpers.php");

if ($_SESSION['role'] !== 'owner') {
    header("Location: ../../auth/login.php");
    exit();
}

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) redirectWithMessage("index.php", "danger", "ID tidak valid.");

$booking = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT * FROM bookings WHERE id='$id' LIMIT 1")
);
if (!$booking) redirectWithMessage("index.php", "danger", "Booking tidak ditemukan.");

if (!in_array($booking['status'], ['Draft', 'Cancelled'])) {
    redirectWithMessage("index.php", "danger",
        "Hanya booking berstatus Draft atau Cancelled yang dapat dihapus."
    );
}

mysqli_begin_transaction($conn);
try {
    // Lepas reservasi inventaris jika ada
    releaseInventory($conn, $id);

    // Hapus booking (CASCADE akan hapus kebutuhan_dekorasi, booking_alat, reminders, logs)
    mysqli_query($conn, "DELETE FROM bookings WHERE id='$id'");

    mysqli_commit($conn);
    redirectWithMessage("index.php", "success",
        "Booking <strong>{$booking['booking_code']}</strong> berhasil dihapus."
    );
} catch (Exception $e) {
    mysqli_rollback($conn);
    redirectWithMessage("index.php", "danger", "Gagal menghapus: " . $e->getMessage());
}