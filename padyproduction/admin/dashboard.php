<?php

require_once("../config/auth.php");
require_once("../config/database.php");

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$total_users    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM users"))['n'];
$total_booking  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM bookings"))['n'];
$total_inv      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM inventory"))['n'];
$recent_booking = mysqli_query($conn,
    "SELECT b.booking_code, b.nama_acara, b.status, b.created_at, u.name AS client_name
     FROM bookings b JOIN users u ON b.client_user_id=u.id
     ORDER BY b.created_at DESC LIMIT 10"
);
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title>Dashboard Admin - PADY Production</title></head>
<body>

<h1>Dashboard Admin - PADY Production</h1>
<p>Login sebagai: <strong><?= htmlspecialchars($_SESSION['name']); ?></strong></p>

<nav>
    <a href="users/index.php">👥 Kelola Pengguna</a> |
    <a href="inventory/index.php">📦 Inventaris</a> |
    <a href="../auth/logout.php">🚪 Logout</a>
</nav>

<hr>

<h2>Statistik Sistem</h2>
<table border="1" cellpadding="10" cellspacing="0">
<tr>
    <td><b>Total Pengguna</b><br><big><?= $total_users; ?></big></td>
    <td><b>Total Booking</b><br><big><?= $total_booking; ?></big></td>
    <td><b>Total Inventaris</b><br><big><?= $total_inv; ?></big></td>
</tr>
</table>

<h2>Aktivitas Booking Terbaru</h2>
<table border="1" cellpadding="8" cellspacing="0">
<thead><tr><th>Kode</th><th>Client</th><th>Nama Acara</th><th>Status</th><th>Dibuat</th></tr></thead>
<tbody>
<?php while ($b = mysqli_fetch_assoc($recent_booking)): ?>
<tr>
    <td><?= htmlspecialchars($b['booking_code']); ?></td>
    <td><?= htmlspecialchars($b['client_name']); ?></td>
    <td><?= htmlspecialchars($b['nama_acara']); ?></td>
    <td><?= $b['status']; ?></td>
    <td><?= date('d/m/Y H:i', strtotime($b['created_at'])); ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

</body>
</html>
