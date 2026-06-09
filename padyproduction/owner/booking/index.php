<?php

require_once("../../config/auth.php");
require_once("../../config/database.php");
require_once("../../config/helpers.php");

if ($_SESSION['role'] !== 'owner') {
    header("Location: ../../auth/login.php");
    exit();
}

// Filter opsional
$status_filter = isset($_GET['status']) ? sanitize($conn, $_GET['status']) : '';
$search        = isset($_GET['search']) ? sanitize($conn, $_GET['search']) : '';

$where = "WHERE 1=1";
if ($status_filter) $where .= " AND b.status='$status_filter'";
if ($search)        $where .= " AND (b.nama_acara LIKE '%$search%' OR b.booking_code LIKE '%$search%' OR u.name LIKE '%$search%')";

$bookings = mysqli_query($conn,
    "SELECT b.*, u.name AS client_name, u.email AS client_email
     FROM bookings b
     LEFT JOIN users u ON b.client_user_id = u.id
     $where
     ORDER BY b.tanggal_acara ASC"
);

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Daftar Booking - PADY Production</title>
</head>
<body>

<h1>Daftar Booking</h1>
<p>Login sebagai: <strong><?= htmlspecialchars($_SESSION['name']); ?></strong></p>

<?= $flash; ?>

<a href="add.php">+ Tambah Booking</a>
<a href="../../auth/logout.php">Logout</a>

<hr>

<!-- Filter -->
<form method="GET">
    <input type="text" name="search" value="<?= htmlspecialchars($search); ?>" placeholder="Cari nama acara / client...">
    <select name="status">
        <option value="">-- Semua Status --</option>
        <?php foreach (['Draft','Confirmed','On Progress','Completed','Cancelled'] as $s): ?>
        <option value="<?= $s; ?>" <?= ($status_filter === $s) ? 'selected' : ''; ?>><?= $s; ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Filter</button>
    <a href="index.php">Reset</a>
</form>

<hr>

<?php if (mysqli_num_rows($bookings) === 0): ?>
<p>Belum ada data booking.</p>
<?php else: ?>

<table border="1" cellpadding="8" cellspacing="0" style="width:100%;border-collapse:collapse;">
<thead>
<tr>
    <th>Kode Booking</th>
    <th>Client</th>
    <th>Nama Acara</th>
    <th>Tanggal Acara</th>
    <th>Lokasi</th>
    <th>Status</th>
    <th>Aksi</th>
</tr>
</thead>
<tbody>
<?php while ($b = mysqli_fetch_assoc($bookings)): ?>
<tr>
    <td><?= htmlspecialchars($b['booking_code']); ?></td>
    <td><?= htmlspecialchars($b['client_name']); ?></td>
    <td><?= htmlspecialchars($b['nama_acara']); ?></td>
    <td><?= date('d/m/Y', strtotime($b['tanggal_acara'])); ?></td>
    <td><?= htmlspecialchars($b['lokasi']); ?></td>
    <td><strong><?= $b['status']; ?></strong></td>
    <td>
        <a href="detail.php?id=<?= $b['id']; ?>">Detail</a> |
        <a href="edit.php?id=<?= $b['id']; ?>">Edit</a> |
        <a href="delete.php?id=<?= $b['id']; ?>"
           onclick="return confirm('Yakin hapus booking ini? Stok alat akan dikembalikan.')">Hapus</a>
    </td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
<?php endif; ?>

</body>
</html>
