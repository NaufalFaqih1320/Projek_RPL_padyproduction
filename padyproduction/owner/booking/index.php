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

$bookings_result = mysqli_query($conn,
    "SELECT b.*, u.name AS client_name, u.email AS client_email
     FROM bookings b
     LEFT JOIN users u ON b.client_user_id = u.id
     $where
     ORDER BY b.tanggal_acara ASC"
);

// Ambil alat per booking sekalian, biar tidak query berkali-kali di dalam loop tampilan
$bookings = [];
while ($b = mysqli_fetch_assoc($bookings_result)) {
    $alat_res = mysqli_query($conn,
        "SELECT ba.jumlah_dipakai, i.item_name, i.unit
         FROM booking_alat ba
         JOIN inventory i ON ba.inventory_id = i.id
         WHERE ba.booking_id = '{$b['id']}'"
    );
    $b['alat'] = [];
    while ($a = mysqli_fetch_assoc($alat_res)) {
        $b['alat'][] = $a;
    }
    $bookings[] = $b;
}

$flash = getFlashMessage();

// Kelas badge status disesuaikan dengan nilai enum status yang dipakai di database
function statusBadgeClass(string $status): string {
    return match ($status) {
        'Confirmed'   => 'badge-confirmed',
        'In Progress' => 'badge-progress',
        'Completed'   => 'badge-completed',
        'Cancelled'   => 'badge-cancelled',
        default       => 'badge-draft', // Draft
    };
}

// Label Bahasa Indonesia untuk status
function statusLabel(string $status): string {
    return match ($status) {
        'Confirmed'   => 'Konfirmasi',
        'In Progress' => 'Berlangsung',
        'Completed'   => 'Selesai',
        'Cancelled'   => 'Dibatalkan',
        default       => 'Draft',
    };
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Daftar Booking - PADY Production</title>
<style>
    :root{
        --orange:#ff6a2b;
        --dark:#1c1c1c;
        --green:#2fae4e;
        --border:#e6e6e6;
        --text-muted:#7a7a7a;
    }
    *{box-sizing:border-box;margin:0;padding:0;font-family:'Segoe UI',Arial,sans-serif;}
    body{background:#fafafa;color:#1c1c1c;}

    header{
        background:var(--dark);
        color:#fff;
        padding:18px 40px;
        display:flex;
        justify-content:space-between;
        align-items:center;
    }
    header .brand h1{color:var(--orange);font-size:26px;}
    header .brand p{font-size:14px;color:#ccc;}
    header .logout{
        border:1px solid var(--orange);
        color:var(--orange);
        background:transparent;
        padding:8px 20px;
        border-radius:20px;
        cursor:pointer;
        text-decoration:none;
    }

    nav.tabs{
        display:flex;
        gap:60px;
        padding:0 40px;
        background:#fff;
        border-bottom:1px solid var(--border);
    }
    nav.tabs a{
        padding:18px 0;
        text-decoration:none;
        color:#555;
        font-size:18px;
    }
    nav.tabs a.active{
        color:var(--orange);
        border-bottom:3px solid var(--orange);
        font-weight:600;
    }

    main{padding:30px 40px;}
    .page-title{
        display:flex;
        justify-content:space-between;
        align-items:center;
        margin-bottom:20px;
        flex-wrap:wrap;
        gap:12px;
    }
    .page-title h2{font-size:28px;}
    .btn-add{
        background:var(--orange);
        color:#fff;
        border:none;
        padding:12px 22px;
        border-radius:24px;
        font-size:15px;
        cursor:pointer;
        text-decoration:none;
        display:inline-flex;
        align-items:center;
        gap:6px;
    }

    .filter-bar{
        display:flex;
        gap:10px;
        margin-bottom:24px;
        flex-wrap:wrap;
    }
    .filter-bar input, .filter-bar select{
        padding:9px 14px;
        border:1px solid var(--border);
        border-radius:20px;
        font-size:14px;
    }
    .filter-bar button{
        padding:9px 20px;
        border:none;
        border-radius:20px;
        background:var(--dark);
        color:#fff;
        cursor:pointer;
    }
    .filter-bar a{
        padding:9px 16px;
        border-radius:20px;
        text-decoration:none;
        color:#555;
        border:1px solid var(--border);
    }

    .card{
        background:#fff;
        border:1px solid var(--border);
        border-radius:14px;
        padding:26px 30px;
        margin-bottom:24px;
        position:relative;
    }
    .card-header{
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:12px;
    }
    .card h3{font-size:24px;margin-bottom:12px;}
    .card-actions{display:flex;gap:8px;flex-wrap:wrap;}
    .btn-edit, .btn-danger{
        border:1px solid var(--orange);
        color:var(--orange);
        background:transparent;
        padding:8px 18px;
        border-radius:20px;
        font-size:14px;
        cursor:pointer;
        text-decoration:none;
        white-space:nowrap;
    }
    .btn-danger{border-color:#c93a3a;color:#c93a3a;}

    .badge{
        display:inline-block;
        padding:5px 16px;
        border-radius:14px;
        font-size:13px;
        margin-bottom:14px;
        font-weight:600;
    }
    .badge-confirmed{background:#e7f8ec;color:var(--green);}
    .badge-draft{background:#eee;color:#666;}
    .badge-progress{background:#fff1e0;color:#e08a1e;}
    .badge-completed{background:#e6f0ff;color:#2a6fdb;}
    .badge-cancelled{background:#fde9e9;color:#c93a3a;}

    .client-name{font-size:18px;font-weight:600;margin-bottom:4px;}
    .booking-id{color:var(--text-muted);font-size:14px;margin-bottom:18px;}

    .info-row{display:flex;gap:60px;margin-bottom:14px;flex-wrap:wrap;}
    .info-block h4{font-size:15px;margin-bottom:8px;}
    .info-block p{color:#444;font-size:14px;display:flex;align-items:center;gap:6px;}

    .items h4{font-size:15px;margin-bottom:8px;}
    .items ul{list-style:disc;margin-left:20px;color:#444;font-size:14px;line-height:1.7;}
    .items p.empty-items{color:var(--text-muted);font-size:14px;}

    hr{border:none;border-top:1px solid var(--border);margin:18px 0;}

    .notes-label{color:var(--text-muted);font-size:13px;margin-bottom:8px;}
    .notes-box{
        border:1px solid var(--border);
        border-radius:24px;
        padding:12px 20px;
        color:#888;
        font-size:14px;
    }

    .empty{text-align:center;color:#999;padding:60px 0;}
</style>
</head>
<body>

<header>
    <div class="brand">
        <h1>PADYProduction</h1>
        <p>Dashboard Owner &mdash; <?= htmlspecialchars($_SESSION['name']); ?></p>
    </div>
    <a href="../../auth/logout.php" class="logout">Keluar</a>
</header>

<nav class="tabs">
    <a href="index.php" class="active">Daftar Booking</a>
    <a href="../dashboard.php">Jadwal</a>
    <a href="../dashboard.php">Informasi Inventaris</a>
</nav>

<main>
    <div class="page-title">
        <h2>Daftar Booking</h2>
        <a href="add.php" class="btn-add">+ Tambah Booking</a>
    </div>

    <?= $flash; ?>

    <form method="GET" class="filter-bar">
        <input type="text" name="search" value="<?= htmlspecialchars($search); ?>" placeholder="Cari nama acara / client...">
        <select name="status">
            <option value="">-- Semua Status --</option>
            <?php foreach (['Draft','Confirmed','In Progress','Completed','Cancelled'] as $s): ?>
            <option value="<?= $s; ?>" <?= ($status_filter === $s) ? 'selected' : ''; ?>><?= $s; ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Filter</button>
        <a href="index.php">Reset</a>
    </form>

    <?php if (empty($bookings)): ?>
        <p class="empty">Belum ada data booking.</p>
    <?php else: ?>
        <?php foreach ($bookings as $b): ?>
        <div class="card">
            <div class="card-header">
                <h3><?= htmlspecialchars($b['nama_acara']); ?></h3>
                <div class="card-actions">
                    <a href="detail.php?id=<?= $b['id']; ?>" class="btn-edit">Detail</a>
                    <a href="edit.php?id=<?= $b['id']; ?>" class="btn-edit">+ Edit</a>
                    <a href="delete.php?id=<?= $b['id']; ?>" class="btn-danger"
                       onclick="return confirm('Yakin hapus booking ini? Stok alat akan dikembalikan.')">Hapus</a>
                </div>
            </div>

            <span class="badge <?= statusBadgeClass($b['status']); ?>">
                <?= statusLabel($b['status']); ?>
            </span>

            <p class="client-name"><?= htmlspecialchars($b['client_name'] ?? '-'); ?></p>
            <p class="booking-id">ID Booking : <?= htmlspecialchars($b['booking_code']); ?></p>

            <div class="info-block" style="margin-bottom:14px;">
                <h4>Lokasi Acara</h4>
                <p>📍 <?= htmlspecialchars($b['lokasi']); ?></p>
            </div>

            <div class="info-row">
                <div class="info-block">
                    <h4>Tanggal Acara</h4>
                    <p>🗓 <?= formatTanggal($b['tanggal_acara']); ?></p>
                </div>
                <div class="info-block">
                    <h4>Tanggal Booking</h4>
                    <p>🗓 <?= formatTanggal($b['tanggal_booking']); ?></p>
                </div>
            </div>

            <div class="items">
                <h4>Alat yang Disewa :</h4>
                <?php if (empty($b['alat'])): ?>
                    <p class="empty-items">Belum ada alat dipilih.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($b['alat'] as $item): ?>
                            <li><?= htmlspecialchars($item['item_name']); ?> - <?= (int)$item['jumlah_dipakai']; ?> <?= htmlspecialchars($item['unit']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <hr>

            <p class="notes-label">Catatan</p>
            <div class="notes-box">
                <?= !empty($b['catatan']) ? htmlspecialchars($b['catatan']) : '-'; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

</body>
</html>