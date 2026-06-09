<?php

require_once("../config/auth.php");
require_once("../config/database.php");

if ($_SESSION['role'] !== 'owner') {
    header("Location: ../auth/login.php");
    exit();
}

// Statistik
$total_booking    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM bookings"))['n'];
$booking_bulan    = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS n FROM bookings WHERE MONTH(tanggal_acara)=MONTH(CURDATE()) AND YEAR(tanggal_acara)=YEAR(CURDATE())"))['n'];
$total_inventaris = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM inventory"))['n'];
$booking_mendatang = mysqli_query($conn,
    "SELECT b.*, u.name AS client_name FROM bookings b
     JOIN users u ON b.client_user_id=u.id
     WHERE b.tanggal_acara >= CURDATE() AND b.status NOT IN ('Completed','Cancelled')
     ORDER BY b.tanggal_acara ASC LIMIT 5"
);
$reminders_pending = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS n FROM reminders WHERE status_terkirim=0 AND waktu_reminder <= NOW()"))['n'];
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title>Dashboard Owner - PADY Production</title></head>
<body>

<h1>Dashboard Owner - PADY Production</h1>
<p>Selamat datang, <strong><?= htmlspecialchars($_SESSION['name']); ?></strong></p>

<nav>
    <a href="booking/index.php">📋 Kelola Booking</a> |
    <a href="booking/add.php">➕ Tambah Booking</a> |
    <a href="../admin/inventory/index.php">📦 Inventaris</a> |
    <a href="../auth/logout.php">🚪 Logout</a>
</nav>

<hr>

<h2>Ringkasan</h2>
<table border="1" cellpadding="10" cellspacing="0">
<tr>
    <td><b>Total Booking</b><br><big><?= $total_booking; ?></big></td>
    <td><b>Booking Bulan Ini</b><br><big><?= $booking_bulan; ?></big></td>
    <td><b>Total Inventaris</b><br><big><?= $total_inventaris; ?></big></td>
    <td><b>Reminder Tertunda</b><br><big><?= $reminders_pending; ?></big></td>
</tr>
</table>

<h2>Booking Mendatang</h2>
<?php if (mysqli_num_rows($booking_mendatang) === 0): ?>
<p>Tidak ada booking mendatang.</p>
<?php else: ?>
<table border="1" cellpadding="8" cellspacing="0">
<thead><tr><th>Kode</th><th>Client</th><th>Nama Acara</th><th>Tanggal</th><th>Lokasi</th><th>Status</th><th>Aksi</th></tr></thead>
<tbody>
<?php while ($b = mysqli_fetch_assoc($booking_mendatang)): ?>
<tr>
    <td><?= htmlspecialchars($b['booking_code']); ?></td>
    <td><?= htmlspecialchars($b['client_name']); ?></td>
    <td><?= htmlspecialchars($b['nama_acara']); ?></td>
    <td><?= date('d/m/Y', strtotime($b['tanggal_acara'])); ?></td>
    <td><?= htmlspecialchars($b['lokasi']); ?></td>
    <td><?= $b['status']; ?></td>
    <td><a href="booking/detail.php?id=<?= $b['id']; ?>">Detail</a></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
<?php endif; ?>

</body>
</html>
