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
    "SELECT * FROM bookings WHERE id='$id' LIMIT 1"
));
if (!$booking) { header("Location: index.php"); exit(); }

$kebutuhan = mysqli_query($conn,
    "SELECT * FROM kebutuhan_dekorasi WHERE booking_id='$id' ORDER BY id"
);
$kebutuhan_rows = [];
while ($k = mysqli_fetch_assoc($kebutuhan)) $kebutuhan_rows[] = $k;

$errors = $_SESSION['booking_errors'] ?? [];
unset($_SESSION['booking_errors']);
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title>Edit Booking</title></head>
<body>
<h1>Edit Booking: <?= htmlspecialchars($booking['booking_code']); ?></h1>
<a href="index.php">← Kembali</a>

<?php if (!empty($errors)): ?>
<div style="background:#fdd;padding:10px;margin:10px 0;border:1px solid red;">
    <strong>Error:</strong><ul>
    <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e); ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form action="process_edit.php" method="POST">
<input type="hidden" name="id" value="<?= $id; ?>">

<label>Nama Acara *</label><br>
<input type="text" name="nama_acara" value="<?= htmlspecialchars($booking['nama_acara']); ?>" required style="width:400px"><br><br>

<label>Jenis Acara</label><br>
<input type="text" name="jenis_acara" value="<?= htmlspecialchars($booking['jenis_acara']); ?>" style="width:400px"><br><br>

<label>Tanggal Acara *</label><br>
<input type="date" name="tanggal_acara" value="<?= $booking['tanggal_acara']; ?>" required><br><br>

<label>Lokasi *</label><br>
<input type="text" name="lokasi" value="<?= htmlspecialchars($booking['lokasi']); ?>" required style="width:400px"><br><br>

<label>Kebutuhan Awal</label><br>
<textarea name="kebutuhan_awal" rows="3" style="width:400px"><?= htmlspecialchars($booking['kebutuhan_awal']); ?></textarea><br><br>

<label>Catatan</label><br>
<textarea name="catatan" rows="2" style="width:400px"><?= htmlspecialchars($booking['catatan']); ?></textarea><br><br>

<label>Status</label><br>
<select name="status">
<?php foreach (['Draft','Confirmed','On Progress','Completed','Cancelled'] as $s): ?>
<option value="<?= $s; ?>" <?= ($booking['status']===$s)?'selected':''; ?>><?= $s; ?></option>
<?php endforeach; ?>
</select><br><br>

<hr>
<h3>Checklist Kebutuhan Dekorasi</h3>
<div id="kebutuhan-list">
<?php foreach ($kebutuhan_rows as $i => $k): ?>
<div class="kebutuhan-row" style="margin-bottom:8px;">
    <input type="text" name="kebutuhan[<?= $i; ?>][nama]" value="<?= htmlspecialchars($k['nama_kebutuhan']); ?>" placeholder="Nama kebutuhan" style="width:250px">
    <input type="number" name="kebutuhan[<?= $i; ?>][jumlah]" value="<?= $k['jumlah']; ?>" min="1" style="width:60px"> buah
    <input type="text" name="kebutuhan[<?= $i; ?>][catatan]" value="<?= htmlspecialchars($k['catatan']); ?>" placeholder="Catatan" style="width:200px">
    <button type="button" onclick="this.parentNode.remove()">✕</button>
</div>
<?php endforeach; ?>
</div>
<button type="button" onclick="tambahKebutuhan()">+ Tambah Kebutuhan</button>

<br><br>
<button type="submit" style="padding:10px 30px;background:#FF6A34;color:#fff;border:none;cursor:pointer;">
    Update Booking
</button>
</form>

<script>
var kebutuhanCount = <?= count($kebutuhan_rows); ?>;
function tambahKebutuhan() {
    var div = document.createElement('div');
    div.className = 'kebutuhan-row';
    div.style.marginBottom = '8px';
    div.innerHTML = '<input type="text" name="kebutuhan['+kebutuhanCount+'][nama]" placeholder="Nama kebutuhan" style="width:250px">' +
        '<input type="number" name="kebutuhan['+kebutuhanCount+'][jumlah]" value="1" min="1" style="width:60px"> buah ' +
        '<input type="text" name="kebutuhan['+kebutuhanCount+'][catatan]" placeholder="Catatan" style="width:200px"> ' +
        '<button type="button" onclick="this.parentNode.remove()">✕</button>';
    document.getElementById('kebutuhan-list').appendChild(div);
    kebutuhanCount++;
}
</script>
</body>
</html>
