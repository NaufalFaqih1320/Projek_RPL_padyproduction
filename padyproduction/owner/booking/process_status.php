<?php

/**
 * owner/booking/process_status.php
 *
 * Mengubah status booking dengan logika:
 * - Draft       → Confirmed  : reservasi inventaris, notifikasi
 * - Confirmed   → In Progress: notif crew
 * - In Progress → Completed  : lepas reservasi inventaris, notif client
 * - (apapun)   → Cancelled  : lepas reservasi inventaris, notif client
 */

session_start();
require_once("../../config/database.php");
require_once("../../config/auth.php");
require_once("../../config/helpers.php");

// Owner dan Admin bisa ubah status
if (!in_array($_SESSION['role'], ['owner', 'admin'])) {
    header("Location: ../../auth/login.php");
    exit();
}

$id         = (int) ($_POST['id']     ?? ($_GET['id']     ?? 0));
$new_status = trim($_POST['status']  ?? ($_GET['status']  ?? ''));

$allowed = ['Draft', 'Confirmed', 'In Progress', 'Completed', 'Cancelled'];
if ($id <= 0 || !in_array($new_status, $allowed)) {
    redirectWithMessage("index.php", "danger", "Request tidak valid.");
}

$booking = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT * FROM bookings WHERE id='$id' LIMIT 1")
);
if (!$booking) {
    redirectWithMessage("index.php", "danger", "Booking tidak ditemukan.");
}

$old_status = $booking['status'];

// Validasi transisi status yang logis
$transitions = [
    'Draft'       => ['Confirmed', 'Cancelled'],
    'Confirmed'   => ['In Progress', 'Cancelled'],
    'In Progress' => ['Completed', 'Cancelled'],
    'Completed'   => [],
    'Cancelled'   => [],
];
if (!in_array($new_status, $transitions[$old_status] ?? [])) {
    redirectWithMessage("detail.php?id=$id", "danger",
        "Tidak bisa mengubah status dari '$old_status' ke '$new_status'."
    );
}

mysqli_begin_transaction($conn);
try {
    $new_esc = mysqli_real_escape_string($conn, $new_status);
    mysqli_query($conn, "UPDATE bookings SET status='$new_esc' WHERE id='$id'");

    $booking_code = $booking['booking_code'];
    $nama_acara   = $booking['nama_acara'];
    $client_id    = (int)$booking['client_user_id'];
    $user_id      = (int)$_SESSION['id'];

    // Logika berdasarkan status baru
    if ($new_status === 'Confirmed') {
        // Tidak perlu reservasi ulang karena sudah dilakukan saat Draft (di process_add)
        // Notifikasi client
        insertNotification($conn, $client_id,
            "Booking Dikonfirmasi [$booking_code]",
            "Booking Anda untuk '{$nama_acara}' pada " . formatTanggal($booking['tanggal_acara']) . " telah dikonfirmasi!"
        );
        // Notifikasi crew
        $crews = mysqli_query($conn, "SELECT id FROM users WHERE role='crew'");
        while ($c = mysqli_fetch_assoc($crews)) {
            insertNotification($conn, (int)$c['id'],
                "Booking Dikonfirmasi: $nama_acara",
                "Booking [{$booking_code}] sudah dikonfirmasi. Bersiaplah untuk tanggal " . formatTanggal($booking['tanggal_acara']) . "."
            );
        }
    }

    if ($new_status === 'In Progress') {
        insertNotification($conn, $client_id,
            "Acara Sedang Berlangsung [$booking_code]",
            "Tim PADY Production sedang mempersiapkan acara '{$nama_acara}' Anda."
        );
    }

    if ($new_status === 'Completed') {
        // Lepas reservasi inventaris
        releaseInventory($conn, $id);
        insertNotification($conn, $client_id,
            "Booking Selesai [$booking_code]",
            "Acara '{$nama_acara}' telah selesai. Terima kasih telah mempercayakan dekorasi Anda kepada PADY Production!"
        );
    }

    if ($new_status === 'Cancelled') {
        // Lepas reservasi inventaris
        releaseInventory($conn, $id);
        insertNotification($conn, $client_id,
            "Booking Dibatalkan [$booking_code]",
            "Booking Anda untuk '{$nama_acara}' pada " . formatTanggal($booking['tanggal_acara']) . " telah dibatalkan."
        );
    }

    logBooking($conn, $id, $user_id, 'Status',
        "Status booking [{$booking_code}] diubah dari '{$old_status}' menjadi '{$new_status}'."
    );

    mysqli_commit($conn);
    redirectWithMessage("detail.php?id=$id", "success",
        "Status booking berhasil diubah menjadi <strong>$new_status</strong>."
    );

} catch (Exception $e) {
    mysqli_rollback($conn);
    redirectWithMessage("detail.php?id=$id", "danger", "Kesalahan sistem: " . $e->getMessage());
}