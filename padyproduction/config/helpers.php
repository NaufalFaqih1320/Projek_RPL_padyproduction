<?php

/**
 * Helper Functions - PADY Production
 */

// Escape input untuk cegah SQL injection sederhana
function sanitize($conn, $data) {
    return mysqli_real_escape_string($conn, trim($data));
}

// Generate booking code: BK-YYYYMMDD-XX
function generateBookingCode($conn) {
    $date = date('Ymd');
    $prefix = "BK-{$date}-";

    $result = mysqli_query($conn,
        "SELECT COUNT(*) as total FROM bookings
         WHERE booking_code LIKE '{$prefix}%'"
    );
    $row = mysqli_fetch_assoc($result);
    $seq = str_pad($row['total'] + 1, 2, '0', STR_PAD_LEFT);

    return $prefix . $seq;
}

// Hitung stok tersedia (total - in_use)
function getAvailableStock($conn, $inventory_id) {
    $result = mysqli_query($conn,
        "SELECT quantity, quantity_in_use FROM inventory WHERE id='$inventory_id'"
    );
    if ($row = mysqli_fetch_assoc($result)) {
        return max(0, $row['quantity'] - $row['quantity_in_use']);
    }
    return 0;
}

// Buat reminder otomatis untuk booking
function createReminders($conn, $booking_id, $tanggal_acara, $client_user_id, $owner_user_id) {
    $tanggal = new DateTime($tanggal_acara);
    $intervals = [
        'H-7' => 7,
        'H-3' => 3,
        'H-1' => 1,
        'H'   => 0,
    ];

    foreach ($intervals as $tipe => $days) {
        $waktu = clone $tanggal;
        $waktu->modify("-{$days} days");
        $waktu->setTime(8, 0, 0);
        $waktu_str = $waktu->format('Y-m-d H:i:s');

        $pesan = "Pengingat {$tipe}: Persiapan acara pada " . date('d/m/Y', strtotime($tanggal_acara));
        $pesan_escaped = mysqli_real_escape_string($conn, $pesan);

        mysqli_query($conn,
            "INSERT INTO reminders
             (booking_id, client_user_id, owner_user_id, tipe, waktu_reminder, pesan)
             VALUES
             ('$booking_id', '$client_user_id', '$owner_user_id', '$tipe', '$waktu_str', '$pesan_escaped')"
        );
    }
}

// Cek apakah tanggal acara sudah di-booking (konflik jadwal)
function checkScheduleConflict($conn, $tanggal_acara, $exclude_id = null) {
    $tanggal = mysqli_real_escape_string($conn, $tanggal_acara);
    $exclude_clause = $exclude_id ? "AND id != '$exclude_id'" : "";

    $result = mysqli_query($conn,
        "SELECT id FROM bookings
         WHERE tanggal_acara = '$tanggal'
         AND status NOT IN ('Cancelled')
         $exclude_clause
         LIMIT 1"
    );

    return mysqli_num_rows($result) > 0;
}

// Redirect dengan pesan flash
function redirectWithMessage($url, $type, $message) {
    if (session_status() == PHP_SESSION_NONE) session_start();
    $_SESSION['flash_type']    = $type;
    $_SESSION['flash_message'] = $message;
    header("Location: $url");
    exit();
}

// Tampilkan flash message
function getFlashMessage() {
    if (session_status() == PHP_SESSION_NONE) session_start();
    if (isset($_SESSION['flash_message'])) {
        $type = $_SESSION['flash_type'];
        $msg  = $_SESSION['flash_message'];
        unset($_SESSION['flash_type'], $_SESSION['flash_message']);
        return "<div class='alert alert-{$type}'>{$msg}</div>";
    }
    return '';
}
