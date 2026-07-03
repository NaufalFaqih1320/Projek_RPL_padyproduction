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
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tambah Booking - PADY Production</title>
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

    /* Header */
    .topbar{
        background:var(--dark);
        color:#fff;
        padding:18px 40px;
        display:flex;
        justify-content:space-between;
        align-items:center;
    }
    .topbar .brand h1{
        margin:0;
        color:var(--orange);
        font-size:26px;
    }
    .topbar .brand span{
        display:block;
        font-size:14px;
        color:#ddd;
        margin-top:2px;
    }
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

    /* Tabs */
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

    .content{
        max-width:900px;
        margin:30px auto;
        padding:0 20px;
    }

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
    .card h2{
        text-align:center;
        margin:0 0 25px;
        font-size:28px;
    }

    .errors{
        background:#fdecea;
        border:1px solid #f5c2c0;
        color:#a12a22;
        padding:12px 16px;
        border-radius:8px;
        margin-bottom:20px;
        font-size:14px;
    }
    .errors ul{margin:6px 0 0; padding-left:20px;}

    .form-row{
        display:grid;
        grid-template-columns:1fr 1fr;
        gap:20px;
        margin-bottom:18px;
    }
    @media(max-width:640px){ .form-row{grid-template-columns:1fr;} }

    label{
        display:block;
        margin-bottom:6px;
        font-size:14px;
        font-weight:600;
    }
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
    textarea{resize:vertical;}

    .section-title{
        font-size:15px;
        font-weight:600;
        margin:22px 0 10px;
    }

    .alat-card{
        border:1px solid var(--border);
        border-radius:10px;
        padding:14px 18px;
        display:flex;
        justify-content:space-between;
        align-items:center;
        margin-bottom:14px;
    }
    .alat-card .nama{
        font-size:18px;
        font-weight:700;
        margin:0;
    }
    .alat-card .tersedia{
        font-size:13px;
        color:var(--text-muted);
        margin:2px 0 0;
    }
    .counter{
        display:flex;
        align-items:center;
        gap:14px;
    }
    .counter button{
        width:34px;
        height:34px;
        border-radius:50%;
        border:none;
        color:#fff;
        font-size:18px;
        line-height:1;
        cursor:pointer;
    }
    .counter .minus{background:#e14b3d;}
    .counter .plus{background:#3fae52;}
    .counter .qty{
        min-width:20px;
        text-align:center;
        font-size:18px;
        font-weight:600;
    }

    .kebutuhan-row{
        display:flex;
        gap:10px;
        margin-bottom:8px;
        flex-wrap:wrap;
    }
    .kebutuhan-row input{width:auto;}
    .kebutuhan-row .nama-keb{flex:2; min-width:180px;}
    .kebutuhan-row .jml-keb{width:70px;}
    .kebutuhan-row .catatan-keb{flex:2; min-width:160px;}
    .btn-tambah-keb{
        border:1px dashed var(--orange);
        background:#fff;
        color:var(--orange);
        padding:8px 16px;
        border-radius:8px;
        cursor:pointer;
        font-size:13px;
    }

    .actions{
        display:flex;
        gap:16px;
        margin-top:30px;
    }
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
    .btn-batal{
        background:#fff;
        color:var(--orange);
        text-decoration:none;
    }
    .btn-simpan{
        background:var(--orange);
        color:#fff;
        border:none;
    }
    .btn-simpan:hover{background:var(--orange-dark);}
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
        <h2>Tambah Booking</h2>

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

        <form action="process_add.php" method="POST" id="formBooking">

            <div class="form-row">
                <div>
                    <label>Nama Client *</label>
                    <select name="client_user_id" required>
                        <option value="">-- Pilih Client --</option>
                        <?php while ($c = mysqli_fetch_assoc($clients)): ?>
                        <option value="<?= $c['id']; ?>"
                            <?= (($old['client_user_id'] ?? '') == $c['id']) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($c['name']); ?> (<?= htmlspecialchars($c['email']); ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label>Nama Acara *</label>
                    <input type="text" name="nama_acara" placeholder="Seminar Nasional 2026"
                           value="<?= htmlspecialchars($old['nama_acara'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div>
                    <label>Tanggal Acara *</label>
                    <input type="date" name="tanggal_acara" min="<?= date('Y-m-d'); ?>"
                           value="<?= htmlspecialchars($old['tanggal_acara'] ?? ''); ?>" required>
                </div>
                <div>
                    <label>Tanggal Pemasangan</label>
                    <input type="date" name="tanggal_pemasangan"
                           value="<?= htmlspecialchars($old['tanggal_pemasangan'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-row" style="grid-template-columns:1fr;">
                <div>
                    <label>Lokasi Acara *</label>
                    <input type="text" name="lokasi" placeholder="Ballroom Hotel Grand"
                           value="<?= htmlspecialchars($old['lokasi'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div>
                    <label>Jenis Acara</label>
                    <input type="text" name="jenis_acara" placeholder="Pernikahan, Wisuda, Seminar..."
                           value="<?= htmlspecialchars($old['jenis_acara'] ?? ''); ?>">
                </div>
                <div>
                    <label>Catatan Tambahan</label>
                    <input type="text" name="catatan" placeholder="Catatan tambahan (opsional)"
                           value="<?= htmlspecialchars($old['catatan'] ?? ''); ?>">
                </div>
            </div>

            <div class="section-title">Alat yang Disewa :</div>
            <div id="alat-list">
                <?php while ($inv = mysqli_fetch_assoc($inventaris)):
                    $avail = max(0, $inv['quantity'] - $inv['quantity_in_use']);
                ?>
                <div class="alat-card">
                    <div>
                        <p class="nama"><?= htmlspecialchars($inv['item_name']); ?></p>
                        <p class="tersedia">Tersedia : <?= $avail; ?> <?= htmlspecialchars($inv['unit']); ?></p>
                    </div>
                    <div class="counter" data-id="<?= $inv['id']; ?>" data-max="<?= $avail; ?>">
                        <button type="button" class="minus">&minus;</button>
                        <span class="qty">0</span>
                        <button type="button" class="plus">&plus;</button>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>

            <div class="actions">
                <a href="index.php" class="btn-batal">Batal</a>
                <button type="submit" class="btn-simpan">Tambah Booking</button>
            </div>
        </form>
    </div>
</div>

<script>
// Counter +/- untuk setiap alat, dibatasi jumlah tersedia
document.querySelectorAll('.counter').forEach(function (counter) {
    var max   = parseInt(counter.dataset.max, 10) || 0;
    var qtyEl = counter.querySelector('.qty');
    var minus = counter.querySelector('.minus');
    var plus  = counter.querySelector('.plus');

    minus.addEventListener('click', function () {
        var val = parseInt(qtyEl.textContent, 10);
        if (val > 0) qtyEl.textContent = val - 1;
    });

    plus.addEventListener('click', function () {
        var val = parseInt(qtyEl.textContent, 10);
        if (val < max) qtyEl.textContent = val + 1;
    });
});

// Sebelum submit, bangun alat_id[] & alat_jumlah[] hanya untuk alat dengan jumlah > 0
document.getElementById('formBooking').addEventListener('submit', function (e) {
    var counters = document.querySelectorAll('.counter');
    var dipilih  = 0;

    counters.forEach(function (counter) {
        var qty = parseInt(counter.querySelector('.qty').textContent, 10);
        if (qty > 0) {
            dipilih++;
            var inputId = document.createElement('input');
            inputId.type  = 'hidden';
            inputId.name  = 'alat_id[]';
            inputId.value = counter.dataset.id;
            this.appendChild(inputId);

            var inputJml = document.createElement('input');
            inputJml.type  = 'hidden';
            inputJml.name  = 'alat_jumlah[]';
            inputJml.value = qty;
            this.appendChild(inputJml);
        }
    }, e.target);

    if (dipilih === 0) {
        e.preventDefault();
        alert('Pilih minimal 1 alat yang disewa.');
    }
});
</script>

</body>
</html>