<?php

session_start();
require_once("../../config/database.php");
require_once("../../config/auth.php");
require_once("../../config/helpers.php");

if ($_SESSION['role'] !== 'owner') {
    header("Location: ../../auth/login.php");
    exit();
}

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: index.php");
    exit();
}

// Kembalikan stok alat sebelum hapus
$alat_list = mysqli_query($conn,
    "SELECT inventory_id, jumlah_dipakai FROM booking_alat WHERE booking_id='$id'"
);
while ($alat = mysqli_fetch_assoc($alat_list)) {
    mysqli_query($conn,
        "UPDATE inventory SET quantity_in_use = GREATEST(0, quantity_in_use - '{$alat['jumlah_dipakai']}')
         WHERE id='{$alat['inventory_id']}'"
    );
}

// Hapus booking (cascade: kebutuhan_dekorasi, booking_alat, reminders)
mysqli_query($conn, "DELETE FROM bookings WHERE id='$id'");

redirectWithMessage("index.php", "success", "Booking berhasil dihapus.");
