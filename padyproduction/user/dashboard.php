<?php

require_once("../config/auth.php");
require_once("../config/database.php");
require_once("../config/helpers.php");

if (!in_array($_SESSION['role'], ['client', 'user'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = (int) $_SESSION['id'];

// Proses reminder
processReminders($conn);

// Booking milik client ini
$bookings = mysqli_query($conn,
    "SELECT * FROM bookings
     WHERE client_user_id = '$user_id'
     ORDER BY created_at DESC
     LIMIT 10"
);

// Booking mendatang (yang aktif)
$booking_mendatang = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM bookings
     WHERE client_user_id = '$user_id'
       AND tanggal_acara >= CURDATE()
       AND status NOT IN ('Completed','Cancelled')
     ORDER BY tanggal_acara ASC
     LIMIT 1"
));

$unread_notif = getUnreadNotificationCount($conn, $user_id);
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard - PADY Production</title>
</head>
<body>

<h1>Selamat datang, <?= htmlspecialchars($_SESSION['name']); ?></h1>

<nav>
    <a href="chat.php">💬 Chat</a> |
    <a href="../notification/index.php">
        🔔 Notifikasi
        <?php if ($unread_notif > 0): ?>
            (<?= $unread_notif; ?>)
        <?php endif; ?>
    </a> |
    <a href="../auth/logout.php">🚪 Logout</a>
</nav>

<hr>

<?= $flash; ?>

<?php if ($booking_mendatang): ?>
<div style="border:2px solid #007bff;padding:14px;margin-bottom:20px;border-radius:6px;">
    <h3>Booking Mendatang</h3>
    <p>
        <strong><?= htmlspecialchars($booking_mendatang['nama_acara']); ?></strong><br>
        Tanggal: <?= formatTanggal($booking_mendatang['tanggal_acara']); ?><br>
        Lokasi: <?= htmlspecialchars($booking_mendatang['lokasi']); ?><br>
        Status: <?= statusBadge($booking_mendatang['status']); ?>
    </p>
</div>
<?php endif; ?>

<h2>Riwayat Booking Saya</h2>

<?php if (mysqli_num_rows($bookings) === 0): ?>
<p>Belum ada booking. Hubungi kami via <a href="chat.php">Chat</a> untuk membuat booking.</p>
<?php else: ?>
<table border="1" cellpadding="8" cellspacing="0" style="width:100%;border-collapse:collapse;">
<thead>
<tr>
    <th>Kode Booking</th>
    <th>Nama Acara</th>
    <th>Tanggal Acara</th>
    <th>Lokasi</th>
    <th>Status</th>
</tr>
</thead>
<tbody>
<?php while ($b = mysqli_fetch_assoc($bookings)): ?>
<tr>
    <td><?= htmlspecialchars($b['booking_code']); ?></td>
    <td><?= htmlspecialchars($b['nama_acara']); ?></td>
    <td><?= formatTanggal($b['tanggal_acara']); ?></td>
    <td><?= htmlspecialchars($b['lokasi']); ?></td>
    <td><?= statusBadge($b['status']); ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
<?php endif; ?>

</body>
</html>
