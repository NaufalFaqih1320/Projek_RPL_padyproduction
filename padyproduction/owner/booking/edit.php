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

$status_options = ['Draft', 'Confirmed', 'On Progress', 'Completed', 'Cancelled'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Edit Booking - PADY Production</title>
<style>
    :root{
        --orange:#FF6A2B;
        --orange-dark:#e85a1f;
        --dark:#1c1c1c;
        --border:#FF6A2B;
        --text-muted:#8a8a8a;
    }
    *{box-sizing:border-box;}
    body{
        margin:0;
        font-family:'Segoe UI', Arial, sans-serif;
        background:#f5f5f5;
        color:#1c1c1c;
    }

    .topbar{
        background:var(--dark);
        color:#fff;
        padding:18px 40px;
        display:flex;
        justify-content:space-between;
        align-items:center;
    }
    .topbar .brand h1{ margin:0; color:var(--orange); font-size:26px; }
    .topbar .brand span{ display:block; font-size:14px; color:#ddd; margin-top:2px; }
    .btn-keluar{
        border:1px solid var(--orange);
        color:var(--orange);
        background:transparent;
        padding:8px 20px;
        border-radius:20px;
        cursor:pointer;
        font-size:14px;
        text-decoration:none;
    }

    .tabs{
        background:#fff;
        display:flex;
        gap:50px;
        padding:0 40px;
        border-bottom:1px solid #eee;
    }
    .tabs a{
        padding:18px 4px;
        text-decoration:none;
        color:#666;
        font-size:16px;
        border-bottom:3px solid transparent;
    }
    .tabs a.active{
        color:var(--orange);
        border-bottom-color:var(--orange);
        font-weight:600;
    }

    .content{ max-width:900px; margin:30px auto; padding:0 20px; }

    .btn-kembali{
        display:inline-flex;
        align-items:center;
        gap:8px;
        border:1px solid var(--orange);
        color:var(--orange);
        background:#fff;
        padding:10px 20px;
        border-radius:8px;
        text-decoration:none;
        font-size:14px;
        margin-bottom:20px;
    }

    .card{
        background:#fff;
        border-radius:14px;
        padding:35px 40px 30px;
        box-shadow:0 2px 10px rgba(0,0,0,.05);
    }
    .card h2{ text-align:center; margin:0 0 4px; font-size:28px; }
    .card .kode{ text-align:center; color:var(--text-muted); font-size:14px; margin-bottom:25px; }

    .errors{
        background:#fdecea;
        border:1px solid #f5c2c0;
        color:#a12a22;
        padding:12px 16px;
        border-radius:8px;
        margin-bottom:20px;
        font-size:14px;
    }
    .errors ul{ margin:6px 0 0; padding-left:20px; }

    .form-row{
        display:grid;
        grid-template-columns:1fr 1fr;
        gap:20px;
        margin-bottom:18px;
    }
    @media(max-width:640px){ .form-row{grid-template-columns:1fr;} }

    label{ display:block; margin-bottom:6px; font-size:14px; font-weight:600; }
    input[type=text], input[type=date], select, textarea{
        width:100%;
        padding:12px 14px;
        border:1px solid var(--border);
        border-radius:10px;
        font-size:14px;
        outline:none;
        font-family:inherit;
        background:#fff;
    }
    textarea{ resize:vertical; }

    .section-title{ font-size:15px; font-weight:600; margin:26px 0 12px; }

    .kebutuhan-row{
        display:flex;
        gap:10px;
        margin-bottom:10px;
        flex-wrap:wrap;
        align-items:center;
        border:1px solid var(--border);
        border-radius:10px;
        padding:10px 14px;
    }
    .kebutuhan-row input{ padding:9px 12px; }
    .kebutuhan-row .nama-keb{ flex:2; min-width:180px; }
    .kebutuhan-row .jml-wrap{ display:flex; align-items:center; gap:6px; width:110px; }
    .kebutuhan-row .jml-keb{ width:60px; }
    .kebutuhan-row .catatan-keb{ flex:2; min-width:160px; }
    .btn-hapus-keb{
        border:none;
        background:#fde9e9;
        color:#c93a3a;
        width:30px;
        height:30px;
        border-radius:50%;
        cursor:pointer;
        font-size:14px;
        line-height:1;
        flex-shrink:0;
    }
    .btn-tambah-keb{
        border:1px dashed var(--orange);
        background:#fff;
        color:var(--orange);
        padding:10px 18px;
        border-radius:8px;
        cursor:pointer;
        font-size:14px;
        font-weight:600;
    }
    .kebutuhan-empty{
        color:var(--text-muted);
        font-size:13px;
        margin-bottom:10px;
    }

    .actions{ display:flex; gap:16px; margin-top:30px; }
    .btn-batal, .btn-simpan{
        flex:1;
        padding:15px 0;
        border-radius:10px;
        font-size:16px;
        font-weight:600;
        cursor:pointer;
        text-align:center;
        border:1px solid var(--orange);
    }
    .btn-batal{ background:#fff; color:var(--orange); text-decoration:none; }
    .btn-simpan{ background:var(--orange); color:#fff; border:none; }
    .btn-simpan:hover{ background:var(--orange-dark); }
</style>
</head>
<body>

<div class="topbar">
    <div class="brand">
        <h1>PADYProduction</h1>
        <span>Dashboard Owner</span>
    </div>
    <a href="../../auth/logout.php" class="btn-keluar">Keluar</a>
</div>

<div class="tabs">
    <a href="index.php" class="active">Daftar Booking</a>
    <a href="../jadwal/index.php">Jadwal</a>
    <a href="../inventaris/index.php">Informasi Inventaris</a>
</div>

<div class="content">
    <a href="index.php" class="btn-kembali">&larr; Kembali</a>

    <div class="card">
        <h2>Edit Booking</h2>
        <p class="kode">ID Booking : <?= htmlspecialchars($booking['booking_code']); ?></p>

        <?php if (!empty($errors)): ?>
        <div class="errors">
            <strong>Terdapat kesalahan:</strong>
            <ul>
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e); ?></li>
            <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form action="process_edit.php" method="POST" id="formBooking">
            <input type="hidden" name="id" value="<?= $id; ?>">

            <div class="form-row">
                <div>
                    <label>Nama Acara *</label>
                    <input type="text" name="nama_acara" placeholder="Seminar Nasional 2026"
                           value="<?= htmlspecialchars($booking['nama_acara']); ?>" required>
                </div>
                <div>
                    <label>Jenis Acara</label>
                    <input type="text" name="jenis_acara" placeholder="Pernikahan, Wisuda, Seminar..."
                           value="<?= htmlspecialchars($booking['jenis_acara']); ?>">
                </div>
            </div>

            <div class="form-row">
                <div>
                    <label>Tanggal Acara *</label>
                    <input type="date" name="tanggal_acara" value="<?= htmlspecialchars($booking['tanggal_acara']); ?>" required>
                </div>
                <div>
                    <label>Status</label>
                    <select name="status">
                        <?php foreach ($status_options as $s): ?>
                        <option value="<?= $s; ?>" <?= ($booking['status'] === $s) ? 'selected' : ''; ?>><?= $s; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row" style="grid-template-columns:1fr;">
                <div>
                    <label>Lokasi Acara *</label>
                    <input type="text" name="lokasi" placeholder="Ballroom Hotel Grand"
                           value="<?= htmlspecialchars($booking['lokasi']); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div>
                    <label>Kebutuhan Awal</label>
                    <textarea name="kebutuhan_awal" rows="3"><?= htmlspecialchars($booking['kebutuhan_awal']); ?></textarea>
                </div>
                <div>
                    <label>Catatan</label>
                    <textarea name="catatan" rows="3"><?= htmlspecialchars($booking['catatan']); ?></textarea>
                </div>
            </div>

            <div class="section-title">Checklist Kebutuhan Dekorasi</div>
            <div id="kebutuhan-list">
                <?php if (empty($kebutuhan_rows)): ?>
                    <p class="kebutuhan-empty" id="kebutuhan-empty-msg">Belum ada kebutuhan dekorasi ditambahkan.</p>
                <?php else: ?>
                    <?php foreach ($kebutuhan_rows as $i => $k): ?>
                    <div class="kebutuhan-row">
                        <input type="text" class="nama-keb" name="kebutuhan[<?= $i; ?>][nama]"
                               value="<?= htmlspecialchars($k['nama_kebutuhan']); ?>" placeholder="Nama kebutuhan">
                        <div class="jml-wrap">
                            <input type="number" class="jml-keb" name="kebutuhan[<?= $i; ?>][jumlah]"
                                   value="<?= (int) $k['jumlah']; ?>" min="1"> buah
                        </div>
                        <input type="text" class="catatan-keb" name="kebutuhan[<?= $i; ?>][catatan]"
                               value="<?= htmlspecialchars($k['catatan']); ?>" placeholder="Catatan">
                        <button type="button" class="btn-hapus-keb" onclick="this.closest('.kebutuhan-row').remove()">&times;</button>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button type="button" class="btn-tambah-keb" onclick="tambahKebutuhan()">+ Tambah Kebutuhan</button>

            <div class="actions">
                <a href="index.php" class="btn-batal">Batal</a>
                <button type="submit" class="btn-simpan">Update Booking</button>
            </div>
        </form>
    </div>
</div>

<script>
var kebutuhanCount = <?= count($kebutuhan_rows); ?>;

function tambahKebutuhan() {
    var emptyMsg = document.getElementById('kebutuhan-empty-msg');
    if (emptyMsg) emptyMsg.remove();

    var div = document.createElement('div');
    div.className = 'kebutuhan-row';
    div.innerHTML =
        '<input type="text" class="nama-keb" name="kebutuhan['+kebutuhanCount+'][nama]" placeholder="Nama kebutuhan">' +
        '<div class="jml-wrap"><input type="number" class="jml-keb" name="kebutuhan['+kebutuhanCount+'][jumlah]" value="1" min="1"> buah</div>' +
        '<input type="text" class="catatan-keb" name="kebutuhan['+kebutuhanCount+'][catatan]" placeholder="Catatan">' +
        '<button type="button" class="btn-hapus-keb" onclick="this.closest(\'.kebutuhan-row\').remove()">&times;</button>';
    document.getElementById('kebutuhan-list').appendChild(div);
    kebutuhanCount++;
}
</script>

</body>
</html>