<?php

/**
 * Helper Functions - PADY Production
 * File ini MENGGANTIKAN config/helpers.php yang ada di repo
 */

// ─── Sanitasi & Keamanan ────────────────────────────────────────────────────

function sanitize($conn, $data): string {
    return mysqli_real_escape_string($conn, trim((string)$data));
}

// ─── Flash Message ──────────────────────────────────────────────────────────

function setFlashMessage(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlashMessage(): string {
    if (empty($_SESSION['flash'])) return '';
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $colors = [
        'success' => '#d4edda;color:#155724;border-color:#c3e6cb',
        'danger'  => '#f8d7da;color:#721c24;border-color:#f5c6cb',
        'info'    => '#d1ecf1;color:#0c5460;border-color:#bee5eb',
        'warning' => '#fff3cd;color:#856404;border-color:#ffeeba',
    ];
    $style = $colors[$f['type']] ?? $colors['info'];
    return "<div style='background:{$style};padding:10px 16px;border:1px solid;border-radius:6px;margin:10px 0;'>{$f['message']}</div>";
}

function redirectWithMessage(string $url, string $type, string $message): void {
    setFlashMessage($type, $message);
    header("Location: $url");
    exit();
}

// ─── Booking ────────────────────────────────────────────────────────────────

function generateBookingCode($conn): string {
    $date   = date('Ymd');
    $prefix = "BK-{$date}-";
    $result = mysqli_query($conn,
        "SELECT COUNT(*) AS total FROM bookings WHERE booking_code LIKE '{$prefix}%'"
    );
    $seq = str_pad(mysqli_fetch_assoc($result)['total'] + 1, 2, '0', STR_PAD_LEFT);
    return $prefix . $seq;
}

/**
 * Cek apakah tanggal acara sudah ada booking yang CONFIRMED/IN_PROGRESS
 * @param $exclude_id ID booking yang dikecualikan (untuk edit)
 */
function checkScheduleConflict($conn, string $tanggal_acara, int $exclude_id = 0): bool {
    $escaped = mysqli_real_escape_string($conn, $tanggal_acara);
    $query   = "SELECT id FROM bookings
                WHERE tanggal_acara = '$escaped'
                AND status IN ('Confirmed','In Progress')
                AND id != '$exclude_id'
                LIMIT 1";
    $result  = mysqli_query($conn, $query);
    return mysqli_num_rows($result) > 0;
}

// ─── Inventaris ─────────────────────────────────────────────────────────────

function getAvailableStock($conn, int $inventory_id): int {
    $result = mysqli_query($conn,
        "SELECT quantity, quantity_in_use FROM inventory WHERE id='$inventory_id' LIMIT 1"
    );
    if ($row = mysqli_fetch_assoc($result)) {
        return max(0, (int)$row['quantity'] - (int)$row['quantity_in_use']);
    }
    return 0;
}

/**
 * Tambah quantity_in_use saat booking dikonfirmasi
 */
function reserveInventory($conn, int $booking_id): void {
    $alats = mysqli_query($conn,
        "SELECT inventory_id, jumlah_dipakai FROM booking_alat WHERE booking_id='$booking_id'"
    );
    while ($row = mysqli_fetch_assoc($alats)) {
        mysqli_query($conn,
            "UPDATE inventory
             SET quantity_in_use = quantity_in_use + {$row['jumlah_dipakai']}
             WHERE id = '{$row['inventory_id']}'"
        );
    }
}

/**
 * Kurangi quantity_in_use saat booking selesai/dibatalkan
 */
function releaseInventory($conn, int $booking_id): void {
    $alats = mysqli_query($conn,
        "SELECT inventory_id, jumlah_dipakai FROM booking_alat WHERE booking_id='$booking_id'"
    );
    while ($row = mysqli_fetch_assoc($alats)) {
        mysqli_query($conn,
            "UPDATE inventory
             SET quantity_in_use = GREATEST(0, quantity_in_use - {$row['jumlah_dipakai']})
             WHERE id = '{$row['inventory_id']}'"
        );
    }
}

// ─── Reminders ──────────────────────────────────────────────────────────────

function createReminders($conn, int $booking_id, string $tanggal_acara, int $client_user_id, int $owner_user_id): void {
    // Hapus reminder lama (jika ada, misal saat edit booking)
    mysqli_query($conn, "DELETE FROM reminders WHERE booking_id='$booking_id'");

    $intervals = ['H-7' => 7, 'H-3' => 3, 'H-1' => 1, 'H' => 0];
    $tanggal   = new DateTime($tanggal_acara);

    foreach ($intervals as $tipe => $days) {
        $waktu = clone $tanggal;
        if ($days > 0) $waktu->modify("-{$days} days");
        $waktu->setTime(8, 0, 0);
        $waktu_str   = $waktu->format('Y-m-d H:i:s');
        $pesan       = "Pengingat {$tipe}: Persiapan acara pada " . date('d/m/Y', strtotime($tanggal_acara));
        $pesan_esc   = mysqli_real_escape_string($conn, $pesan);
        mysqli_query($conn,
            "INSERT INTO reminders (booking_id, client_user_id, owner_user_id, tipe, waktu_reminder, pesan)
             VALUES ('$booking_id','$client_user_id','$owner_user_id','$tipe','$waktu_str','$pesan_esc')"
        );
    }
}

/**
 * Jalankan reminder yang sudah waktunya (dipanggil dari cron atau tiap halaman)
 */
function processReminders($conn): void {
    $reminders = mysqli_query($conn,
        "SELECT r.*, b.booking_code, b.nama_acara
         FROM reminders r
         JOIN bookings b ON r.booking_id = b.id
         WHERE r.status_terkirim = 0
           AND r.waktu_reminder <= NOW()
           AND b.status NOT IN ('Cancelled','Completed')"
    );

    while ($r = mysqli_fetch_assoc($reminders)) {
        $title   = "Pengingat Acara [{$r['booking_code']}]";
        $message = $r['pesan'] . " — {$r['nama_acara']}";

        // Kirim notifikasi ke client
        if ($r['client_user_id']) {
            insertNotification($conn, (int)$r['client_user_id'], $title, $message);
        }
        // Kirim notifikasi ke owner
        if ($r['owner_user_id']) {
            insertNotification($conn, (int)$r['owner_user_id'], $title, $message);
        }
        // Kirim ke semua crew
        $crews = mysqli_query($conn, "SELECT id FROM users WHERE role='crew'");
        while ($crew = mysqli_fetch_assoc($crews)) {
            insertNotification($conn, (int)$crew['id'], $title, $message);
        }

        // Tandai sudah terkirim
        $rid = (int)$r['id'];
        mysqli_query($conn, "UPDATE reminders SET status_terkirim=1, terkirim_at=NOW() WHERE id='$rid'");
    }
}

// ─── Notifikasi ─────────────────────────────────────────────────────────────

function insertNotification($conn, int $user_id, string $title, string $message): void {
    $title_esc   = mysqli_real_escape_string($conn, $title);
    $message_esc = mysqli_real_escape_string($conn, $message);
    mysqli_query($conn,
        "INSERT INTO notifications (user_id, title, message)
         VALUES ('$user_id', '$title_esc', '$message_esc')"
    );
}

function getUnreadNotificationCount($conn, int $user_id): int {
    $result = mysqli_query($conn,
        "SELECT COUNT(*) AS n FROM notifications WHERE user_id='$user_id' AND is_read=0"
    );
    return (int)mysqli_fetch_assoc($result)['n'];
}

// ─── Chatbot ────────────────────────────────────────────────────────────────

/**
 * Cari jawaban otomatis dari FAQ berdasarkan pesan user
 * Return null jika tidak ditemukan (akan diteruskan ke owner)
 */
function chatbotReply($conn, string $message): ?string {
    $message_lower = strtolower($message);
    $faqs          = mysqli_query($conn, "SELECT * FROM chatbot_faq WHERE is_active=1");

    while ($faq = mysqli_fetch_assoc($faqs)) {
        $keywords = explode(',', $faq['keyword']);
        foreach ($keywords as $kw) {
            if (str_contains($message_lower, trim($kw))) {
                return $faq['answer'];
            }
        }
    }
    return null; // Tidak ada jawaban → teruskan ke owner
}

// ─── Log Aktivitas ──────────────────────────────────────────────────────────

function logBooking($conn, int $booking_id, int $user_id, string $action_type, string $description): void {
    $desc_esc = mysqli_real_escape_string($conn, $description);
    mysqli_query($conn,
        "INSERT INTO booking_logs (booking_id, user_id, action_type, description)
         VALUES ('$booking_id', '$user_id', '$action_type', '$desc_esc')"
    );
}

function logInventory($conn, int $inventory_id, int $user_id, string $action_type, string $description): void {
    $desc_esc = mysqli_real_escape_string($conn, $description);
    mysqli_query($conn,
        "INSERT INTO inventory_logs (inventory_id, user_id, action_type, description)
         VALUES ('$inventory_id', '$user_id', '$action_type', '$desc_esc')"
    );
}

// ─── Format ─────────────────────────────────────────────────────────────────

function formatTanggal(string $date): string {
    $bulan = ['','Januari','Februari','Maret','April','Mei','Juni',
              'Juli','Agustus','September','Oktober','November','Desember'];
    $d = date_create($date);
    if (!$d) return $date;
    return date_format($d,'j') . ' ' . $bulan[(int)date_format($d,'n')] . ' ' . date_format($d,'Y');
}

function statusBadge(string $status): string {
    $colors = [
        'Draft'       => '#6c757d',
        'Confirmed'   => '#007bff',
        'In Progress' => '#fd7e14',
        'Completed'   => '#28a745',
        'Cancelled'   => '#dc3545',
    ];
    $color = $colors[$status] ?? '#6c757d';
    return "<span style='background:{$color};color:#fff;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:bold;'>$status</span>";
}

function kondisiBadge(string $kondisi): string {
    $colors = [
        'Sangat Baik' => '#28a745',
        'Baik'        => '#17a2b8',
        'Cukup'       => '#ffc107',
        'Kurang Baik' => '#fd7e14',
        'Buruk'       => '#dc3545',
    ];
    $color = $colors[$kondisi] ?? '#6c757d';
    return "<span style='background:{$color};color:#fff;padding:3px 10px;border-radius:20px;font-size:12px;'>$kondisi</span>";
}