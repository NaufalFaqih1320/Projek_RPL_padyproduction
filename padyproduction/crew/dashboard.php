<?php

require_once("../config/auth.php");
require_once("../config/database.php");

if ($_SESSION['role'] !== 'crew') {
    header("Location: ../auth/login.php");
    exit();
}

$total_alat    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM inventory"))['n'];
$total_stock   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(quantity),0) AS n FROM inventory"))['n'];
$in_use        = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(quantity_in_use),0) AS n FROM inventory"))['n'];

$booking_aktif = mysqli_query($conn,
    "SELECT b.booking_code, b.nama_acara, b.tanggal_acara, b.lokasi, b.status
     FROM bookings b
     WHERE b.tanggal_acara >= CURDATE() AND b.status NOT IN ('Completed','Cancelled')
     ORDER BY b.tanggal_acara ASC LIMIT 5"
);
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title>Dashboard Crew - PADY Production</title></head>
<body>

<h1>Dashboard Crew - PADY Production</h1>
<p>Selamat datang, <strong><?= htmlspecialchars($_SESSION['name']); ?></strong></p>

<nav>
    <a href="inventory/index.php">📦 Kelola Inventaris</a> |
    <a href="inventory/add.php">➕ Tambah Alat</a> |
    <a href="../auth/logout.php">🚪 Logout</a>
</nav>

<hr>

<h2>Ringkasan Inventaris</h2>
<table border="1" cellpadding="10" cellspacing="0">
<tr>
    <td><b>Jenis Alat</b><br><big><?= $total_alat; ?></big></td>
    <td><b>Total Stock</b><br><big><?= $total_stock; ?></big></td>
    <td><b>Sedang Dipakai</b><br><big><?= $in_use; ?></big></td>
    <td><b>Tersedia</b><br><big><?= ($total_stock - $in_use); ?></big></td>
</tr>
</table>

<h2>Booking Aktif (Jadwal Mendatang)</h2>
<?php if (mysqli_num_rows($booking_aktif) === 0): ?>
<p>Tidak ada jadwal mendatang.</p>
<?php else: ?>
<table border="1" cellpadding="8" cellspacing="0">
<thead><tr><th>Kode</th><th>Nama Acara</th><th>Tanggal</th><th>Lokasi</th><th>Status</th></tr></thead>
<tbody>
<?php while ($b = mysqli_fetch_assoc($booking_aktif)): ?>
<tr>
    <td><?= htmlspecialchars($b['booking_code']); ?></td>
    <td><?= htmlspecialchars($b['nama_acara']); ?></td>
    <td><?= date('d/m/Y', strtotime($b['tanggal_acara'])); ?></td>
    <td><?= htmlspecialchars($b['lokasi']); ?></td>
    <td><?= $b['status']; ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
<?php endif; ?>

</body>
</html>
