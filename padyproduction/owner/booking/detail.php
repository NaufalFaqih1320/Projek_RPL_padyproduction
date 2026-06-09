<?php

require_once("../../config/auth.php");
require_once("../../config/database.php");
require_once("../../config/helpers.php");

if ($_SESSION['role'] !== 'owner') {
    header("Location: ../../auth/login.php");
    exit();
}

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) { header("Location: index.php"); exit(); }

$booking = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT b.*, u.name AS client_name, u.email AS client_email, u.no_telepon AS client_telp
     FROM bookings b JOIN users u ON b.client_user_id=u.id
     WHERE b.id='$id' LIMIT 1"
));
if (!$booking) { header("Location: index.php"); exit(); }

$kebutuhan = mysqli_query($conn,
    "SELECT * FROM kebutuhan_dekorasi WHERE booking_id='$id' ORDER BY id"
);
$alat_list = mysqli_query($conn,
    "SELECT ba.jumlah_dipakai, i.item_name, i.unit, ic.category_name
     FROM booking_alat ba
     JOIN inventory i ON ba.inventory_id=i.id
     JOIN inventory_categories ic ON i.category_id=ic.id
     WHERE ba.booking_id='$id'"
);
$reminders = mysqli_query($conn,
    "SELECT * FROM reminders WHERE booking_id='$id' ORDER BY waktu_reminder"
);
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title>Detail Booking</title></head>
<body>
<h1>Detail Booking: <?= htmlspecialchars($booking['booking_code']); ?></h1>
<a href="index.php">← Kembali</a> |
<a href="edit.php?id=<?= $id; ?>">Edit</a>
<hr>

<h3>Informasi Acara</h3>
<table>
<tr><td><b>Kode Booking</b></td><td>: <?= htmlspecialchars($booking['booking_code']); ?></td></tr>
<tr><td><b>Nama Acara</b></td><td>: <?= htmlspecialchars($booking['nama_acara']); ?></td></tr>
<tr><td><b>Jenis Acara</b></td><td>: <?= htmlspecialchars($booking['jenis_acara']); ?></td></tr>
<tr><td><b>Tanggal Acara</b></td><td>: <?= date('d/m/Y', strtotime($booking['tanggal_acara'])); ?></td></tr>
<tr><td><b>Tanggal Booking</b></td><td>: <?= date('d/m/Y', strtotime($booking['tanggal_booking'])); ?></td></tr>
<tr><td><b>Lokasi</b></td><td>: <?= htmlspecialchars($booking['lokasi']); ?></td></tr>
<tr><td><b>Status</b></td><td>: <strong><?= $booking['status']; ?></strong></td></tr>
<tr><td><b>Kebutuhan Awal</b></td><td>: <?= nl2br(htmlspecialchars($booking['kebutuhan_awal'])); ?></td></tr>
<tr><td><b>Catatan</b></td><td>: <?= nl2br(htmlspecialchars($booking['catatan'])); ?></td></tr>
</table>

<h3>Data Client</h3>
<table>
<tr><td><b>Nama</b></td><td>: <?= htmlspecialchars($booking['client_name']); ?></td></tr>
<tr><td><b>Email</b></td><td>: <?= htmlspecialchars($booking['client_email']); ?></td></tr>
<tr><td><b>Telepon</b></td><td>: <?= htmlspecialchars($booking['client_telp']); ?></td></tr>
</table>

<h3>Checklist Kebutuhan Dekorasi</h3>
<?php if (mysqli_num_rows($kebutuhan) === 0): ?>
<p>Tidak ada kebutuhan dekorasi dicatat.</p>
<?php else: ?>
<table border="1" cellpadding="6" cellspacing="0">
<thead><tr><th>Nama Kebutuhan</th><th>Jumlah</th><th>Catatan</th><th>Status</th></tr></thead>
<tbody>
<?php while ($k = mysqli_fetch_assoc($kebutuhan)): ?>
<tr>
    <td><?= htmlspecialchars($k['nama_kebutuhan']); ?></td>
    <td><?= $k['jumlah']; ?></td>
    <td><?= htmlspecialchars($k['catatan']); ?></td>
    <td>
        <form method="POST" action="update_kebutuhan_status.php" style="display:inline">
            <input type="hidden" name="id" value="<?= $k['id']; ?>">
            <input type="hidden" name="booking_id" value="<?= $id; ?>">
            <select name="status_cek" onchange="this.form.submit()">
                <?php foreach (['Belum','Siap','Kurang'] as $s): ?>
                <option value="<?= $s; ?>" <?= ($k['status_cek']===$s)?'selected':''; ?>><?= $s; ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
<?php endif; ?>

<h3>Alat yang Digunakan</h3>
<?php if (mysqli_num_rows($alat_list) === 0): ?>
<p>Tidak ada alat dicatat.</p>
<?php else: ?>
<table border="1" cellpadding="6" cellspacing="0">
<thead><tr><th>Nama Alat</th><th>Kategori</th><th>Jumlah Dipakai</th></tr></thead>
<tbody>
<?php while ($a = mysqli_fetch_assoc($alat_list)): ?>
<tr>
    <td><?= htmlspecialchars($a['item_name']); ?></td>
    <td><?= htmlspecialchars($a['category_name']); ?></td>
    <td><?= $a['jumlah_dipakai']; ?> <?= htmlspecialchars($a['unit']); ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
<?php endif; ?>

<h3>Reminder Otomatis</h3>
<table border="1" cellpadding="6" cellspacing="0">
<thead><tr><th>Tipe</th><th>Waktu</th><th>Pesan</th><th>Terkirim</th></tr></thead>
<tbody>
<?php while ($r = mysqli_fetch_assoc($reminders)): ?>
<tr>
    <td><?= $r['tipe']; ?></td>
    <td><?= date('d/m/Y H:i', strtotime($r['waktu_reminder'])); ?></td>
    <td><?= htmlspecialchars($r['pesan']); ?></td>
    <td><?= $r['status_terkirim'] ? '✅ Ya' : '⏳ Belum'; ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

</body>
</html>
