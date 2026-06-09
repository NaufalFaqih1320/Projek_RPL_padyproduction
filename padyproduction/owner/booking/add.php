<?php

require_once("../../config/auth.php");
require_once("../../config/database.php");
require_once("../../config/helpers.php");

if ($_SESSION['role'] !== 'owner') {
    header("Location: ../../auth/login.php");
    exit();
}

// Ambil daftar client
$clients = mysqli_query($conn,
    "SELECT u.id, u.name, u.email FROM users u WHERE u.role='client' ORDER BY u.name"
);

// Ambil inventaris tersedia
$inventaris = mysqli_query($conn,
    "SELECT i.id, i.item_name, i.quantity, i.quantity_in_use, i.unit, ic.category_name
     FROM inventory i
     JOIN inventory_categories ic ON i.category_id = ic.id
     ORDER BY ic.category_name, i.item_name"
);

$errors  = $_SESSION['booking_errors'] ?? [];
$old     = $_SESSION['booking_old']    ?? [];
unset($_SESSION['booking_errors'], $_SESSION['booking_old']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Tambah Booking - PADY Production</title>
</head>
<body>

<h1>Tambah Booking</h1>
<a href="index.php">← Kembali</a>

<?php if (!empty($errors)): ?>
<div style="background:#fdd;padding:10px;margin:10px 0;border:1px solid red;">
    <strong>Terdapat kesalahan:</strong>
    <ul>
    <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e); ?></li>
    <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form action="process_add.php" method="POST">

<h3>Data Client</h3>
<label>Pilih Client *</label><br>
<select name="client_user_id" required>
    <option value="">-- Pilih Client --</option>
    <?php while ($c = mysqli_fetch_assoc($clients)): ?>
    <option value="<?= $c['id']; ?>"
        <?= (($old['client_user_id'] ?? '') == $c['id']) ? 'selected' : ''; ?>>
        <?= htmlspecialchars($c['name']); ?> (<?= htmlspecialchars($c['email']); ?>)
    </option>
    <?php endwhile; ?>
</select>
<br><br>

<h3>Detail Acara</h3>

<label>Nama Acara *</label><br>
<input type="text" name="nama_acara" value="<?= htmlspecialchars($old['nama_acara'] ?? ''); ?>" required style="width:400px"><br><br>

<label>Jenis Acara</label><br>
<input type="text" name="jenis_acara" value="<?= htmlspecialchars($old['jenis_acara'] ?? ''); ?>" placeholder="Pernikahan, Wisuda, Seminar..." style="width:400px"><br><br>

<label>Tanggal Acara *</label><br>
<input type="date" name="tanggal_acara" value="<?= htmlspecialchars($old['tanggal_acara'] ?? ''); ?>" min="<?= date('Y-m-d'); ?>" required><br><br>

<label>Lokasi *</label><br>
<input type="text" name="lokasi" value="<?= htmlspecialchars($old['lokasi'] ?? ''); ?>" required style="width:400px"><br><br>

<label>Kebutuhan Awal (ringkasan)</label><br>
<textarea name="kebutuhan_awal" rows="3" style="width:400px"><?= htmlspecialchars($old['kebutuhan_awal'] ?? ''); ?></textarea><br><br>

<label>Catatan Tambahan</label><br>
<textarea name="catatan" rows="2" style="width:400px"><?= htmlspecialchars($old['catatan'] ?? ''); ?></textarea><br><br>

<hr>
<h3>Checklist Kebutuhan Dekorasi</h3>
<div id="kebutuhan-list">
    <div class="kebutuhan-row" style="margin-bottom:8px;">
        <input type="text" name="kebutuhan[0][nama]" placeholder="Nama kebutuhan" style="width:250px">
        <input type="number" name="kebutuhan[0][jumlah]" value="1" min="1" style="width:60px"> buah
        <input type="text" name="kebutuhan[0][catatan]" placeholder="Catatan" style="width:200px">
    </div>
</div>
<button type="button" onclick="tambahKebutuhan()">+ Tambah Kebutuhan</button>

<hr>
<h3>Alat yang Digunakan</h3>

<table border="1" cellpadding="6" cellspacing="0">
<thead>
<tr>
    <th>Pilih</th>
    <th>Nama Alat</th>
    <th>Kategori</th>
    <th>Tersedia</th>
    <th>Jumlah Pakai</th>
</tr>
</thead>
<tbody>
<?php
$idx = 0;
while ($inv = mysqli_fetch_assoc($inventaris)):
    $avail = max(0, $inv['quantity'] - $inv['quantity_in_use']);
?>
<tr>
    <td>
        <input type="checkbox" name="alat_id[]" value="<?= $inv['id']; ?>"
               onchange="toggleJumlah(this, 'jml_<?= $inv['id']; ?>')">
        <input type="hidden" name="alat_jumlah[]" id="jml_<?= $inv['id']; ?>" value="0">
    </td>
    <td><?= htmlspecialchars($inv['item_name']); ?></td>
    <td><?= htmlspecialchars($inv['category_name']); ?></td>
    <td><?= $avail; ?> <?= htmlspecialchars($inv['unit']); ?></td>
    <td>
        <input type="number" min="1" max="<?= $avail; ?>" value="1"
               style="width:60px"
               onchange="document.getElementById('jml_<?= $inv['id']; ?>').value = this.value"
               id="input_<?= $inv['id']; ?>" disabled>
    </td>
</tr>
<?php $idx++; endwhile; ?>
</tbody>
</table>

<br>
<button type="submit" style="padding:10px 30px;background:#FF6A34;color:#fff;border:none;cursor:pointer;font-size:16px;">
    Simpan Booking
</button>

</form>

<script>
var kebutuhanCount = 1;

function tambahKebutuhan() {
    var div = document.createElement('div');
    div.className = 'kebutuhan-row';
    div.style.marginBottom = '8px';
    div.innerHTML =
        '<input type="text" name="kebutuhan['+kebutuhanCount+'][nama]" placeholder="Nama kebutuhan" style="width:250px">' +
        '<input type="number" name="kebutuhan['+kebutuhanCount+'][jumlah]" value="1" min="1" style="width:60px"> buah ' +
        '<input type="text" name="kebutuhan['+kebutuhanCount+'][catatan]" placeholder="Catatan" style="width:200px"> ' +
        '<button type="button" onclick="this.parentNode.remove()">✕</button>';
    document.getElementById('kebutuhan-list').appendChild(div);
    kebutuhanCount++;
}

function toggleJumlah(checkbox, inputId) {
    var input = document.getElementById(inputId);
    var numInput = document.getElementById('input_' + checkbox.value);
    if (checkbox.checked) {
        input.value = numInput ? numInput.value : 1;
        if (numInput) numInput.disabled = false;
    } else {
        input.value = 0;
        if (numInput) numInput.disabled = true;
    }
}
</script>

</body>
</html>
