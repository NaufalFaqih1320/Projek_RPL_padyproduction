<?php

/**
 * notification/index.php
 * Halaman notifikasi — dapat diakses semua role
 * URL: /padyproduction/notification/index.php
 */

require_once("../config/auth.php");
require_once("../config/database.php");
require_once("../config/helpers.php");

$user_id = (int) $_SESSION['id'];
$role    = $_SESSION['role'];

// Tandai semua notifikasi sebagai sudah dibaca
mysqli_query($conn, "UPDATE notifications SET is_read=1 WHERE user_id='$user_id' AND is_read=0");

// Filter
$filter = trim($_GET['filter'] ?? 'all'); // all, unread

// Ambil notifikasi
$notifs = mysqli_query($conn, "
    SELECT * FROM notifications
    WHERE user_id = '$user_id'
    ORDER BY created_at DESC
    LIMIT 100
");

// Hapus notifikasi jika diminta
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = (int) $_GET['delete'];
    mysqli_query($conn, "DELETE FROM notifications WHERE id='$del_id' AND user_id='$user_id'");
    header("Location: index.php");
    exit();
}
if (isset($_GET['delete_all'])) {
    mysqli_query($conn, "DELETE FROM notifications WHERE user_id='$user_id'");
    header("Location: index.php");
    exit();
}

// Tentukan link dashboard sesuai role
$dashboard_links = [
    'admin'  => '../admin/dashboard.php',
    'owner'  => '../owner/dashboard.php',
    'crew'   => '../crew/dashboard.php',
    'client' => '../user/dashboard.php',
    'user'   => '../user/dashboard.php',
];
$dashboard = $dashboard_links[$role] ?? '../auth/login.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Notifikasi - PADY Production</title>
<style>
* { box-sizing: border-box; }
body { font-family: Arial, sans-serif; background: #f0f2f5; margin: 0; padding: 20px; }
.container { max-width: 700px; margin: 0 auto; }
.header { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
.header h1 { font-size: 22px; color: #1D1E20; flex: 1; }
.btn { padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 13px; cursor: pointer; border: none; }
.btn-back { background: #6c757d; color: #fff; }
.btn-danger { background: #dc3545; color: #fff; }
.notif-card { background: #fff; border-radius: 10px; padding: 16px; margin-bottom: 12px; display: flex; gap: 14px; align-items: flex-start; box-shadow: 0 1px 4px rgba(0,0,0,.06); }
.notif-icon { font-size: 24px; flex-shrink: 0; }
.notif-body { flex: 1; }
.notif-title { font-weight: bold; font-size: 14px; color: #1D1E20; }
.notif-msg { font-size: 13px; color: #555; margin-top: 4px; line-height: 1.5; }
.notif-time { font-size: 11px; color: #aaa; margin-top: 6px; }
.notif-del { font-size: 13px; color: #dc3545; text-decoration: none; flex-shrink: 0; }
.empty { text-align: center; padding: 60px; color: #aaa; }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <a href="<?= $dashboard; ?>" class="btn btn-back">← Dashboard</a>
        <h1>🔔 Notifikasi</h1>
        <?php $total = mysqli_num_rows($notifs); if ($total > 0): ?>
        <a href="?delete_all=1" class="btn btn-danger"
           onclick="return confirm('Hapus semua notifikasi?')">Hapus Semua</a>
        <?php endif; ?>
    </div>

    <?php
    // Re-query karena pointer sudah dipakai num_rows
    $notifs = mysqli_query($conn, "
        SELECT * FROM notifications
        WHERE user_id = '$user_id'
        ORDER BY created_at DESC LIMIT 100
    ");
    $count = 0;
    while ($n = mysqli_fetch_assoc($notifs)):
        $count++;
        $icons = [
            'Booking'     => '📋',
            'Konfirmasi'  => '✅',
            'Selesai'     => '🎉',
            'Dibatalkan'  => '❌',
            'Reminder'    => '⏰',
            'Pengingat'   => '⏰',
            'Pesan'       => '💬',
            'Chat'        => '💬',
            'Inventaris'  => '📦',
            'Pengguna'    => '👤',
        ];
        $icon = '🔔';
        foreach ($icons as $kw => $ic) {
            if (stripos($n['title'], $kw) !== false) { $icon = $ic; break; }
        }
    ?>
    <div class="notif-card">
        <div class="notif-icon"><?= $icon; ?></div>
        <div class="notif-body">
            <div class="notif-title"><?= htmlspecialchars($n['title']); ?></div>
            <div class="notif-msg"><?= nl2br(htmlspecialchars($n['message'])); ?></div>
            <div class="notif-time"><?= date('d/m/Y H:i', strtotime($n['created_at'])); ?></div>
        </div>
        <a href="?delete=<?= $n['id']; ?>" class="notif-del" title="Hapus">✕</a>
    </div>
    <?php endwhile; ?>

    <?php if ($count === 0): ?>
    <div class="empty">
        <div style="font-size:48px;">🔕</div>
        <p style="margin-top:12px;">Tidak ada notifikasi.</p>
    </div>
    <?php endif; ?>
</div>
</body>
</html>