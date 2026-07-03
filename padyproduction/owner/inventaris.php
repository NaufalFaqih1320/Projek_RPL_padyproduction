<?php

require_once("../config/auth.php");
require_once("../config/database.php");
require_once("../config/helpers.php");

if ($_SESSION['role'] !== 'owner') {
    header("Location: ../auth/login.php");
    exit();
}

// ─── Filter ───────────────────────────────────────────────────────────────
$search        = isset($_GET['search']) ? sanitize($conn, $_GET['search']) : '';
$kondisi_filter = isset($_GET['kondisi']) ? sanitize($conn, $_GET['kondisi']) : '';
$kategori_filter = isset($_GET['kategori']) ? (int) $_GET['kategori'] : 0;

// ─── Ringkasan (kartu statistik atas) ───────────────────────────────────────
$total_kategori = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM inventory_categories"))['n'];
$total_item     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM inventory"))['n'];
$stok           = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(quantity),0) AS total, COALESCE(SUM(quantity_in_use),0) AS dipakai FROM inventory"
));
$total_stok    = (int) $stok['total'];
$stok_dipakai  = (int) $stok['dipakai'];
$stok_tersedia = max(0, $total_stok - $stok_dipakai);

// ─── Daftar kategori (untuk dropdown filter) ────────────────────────────────
$kategori_list = mysqli_query($conn, "SELECT id, category_name FROM inventory_categories ORDER BY category_name");

// ─── Kategori + ringkasan per kategori ──────────────────────────────────────
$kategori_summary = mysqli_query($conn, "
    SELECT ic.id, ic.category_name,
           COUNT(i.id) AS total_item,
           COALESCE(SUM(i.quantity),0) AS total_stock,
           COALESCE(SUM(i.quantity_in_use),0) AS total_in_use
    FROM inventory_categories ic
    LEFT JOIN inventory i ON ic.id = i.category_id
    GROUP BY ic.id
    ORDER BY ic.category_name
");

// ─── Item inventaris sesuai filter ───────────────────────────────────────────
$where = "WHERE 1=1";
if ($search)          $where .= " AND i.item_name LIKE '%$search%'";
if ($kondisi_filter)  $where .= " AND i.condition_status='$kondisi_filter'";
if ($kategori_filter) $where .= " AND i.category_id='$kategori_filter'";

$items_result = mysqli_query($conn, "
    SELECT i.*, ic.category_name
    FROM inventory i
    JOIN inventory_categories ic ON i.category_id = ic.id
    $where
    ORDER BY ic.category_name, i.item_name
");

// Kelompokkan item per kategori untuk ditampilkan
$items_per_kategori = [];
while ($it = mysqli_fetch_assoc($items_result)) {
    $items_per_kategori[$it['category_name']][] = $it;
}

function kondisiDotColor(string $kondisi): string {
    return match ($kondisi) {
        'Sangat Baik' => '#2fae4e',
        'Baik'        => '#2a9ed8',
        'Cukup'       => '#e0b21e',
        'Kurang Baik' => '#e08a1e',
        'Buruk'       => '#c93a3a',
        default       => '#888',
    };
}
function kondisiBadgeClass(string $kondisi): string {
    return match ($kondisi) {
        'Sangat Baik' => 'badge-sangat-baik',
        'Baik'        => 'badge-baik',
        'Cukup'       => 'badge-cukup',
        'Kurang Baik' => 'badge-kurang',
        'Buruk'       => 'badge-buruk',
        default       => 'badge-draft',
    };
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Informasi Inventaris - PADY Production</title>
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
        padding:18px 0 15px;
        text-decoration:none;
        color:#555;
        font-size:18px;
        font-weight:600;
        border-bottom:3px solid transparent;
    }
    nav.tabs a.active{
        color:var(--orange);
        border-bottom-color:var(--orange);
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

    /* Kartu statistik ringkasan */
    .stat-grid{
        display:grid;
        grid-template-columns:repeat(4,1fr);
        gap:18px;
        margin-bottom:26px;
    }
    .stat-card{
        background:#fff;
        border:1px solid var(--border);
        border-radius:14px;
        padding:20px 22px;
    }
    .stat-card h4{font-size:13px;color:var(--text-muted);font-weight:600;margin-bottom:8px;text-transform:uppercase;}
    .stat-card .num{font-size:30px;font-weight:700;}
    .stat-card.orange .num{color:var(--orange);}
    .stat-card.green .num{color:var(--green);}
    .stat-card.red .num{color:#c93a3a;}

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

    /* Ringkasan per kategori (chip horizontal scroll) */
    .kategori-scroll{
        display:flex;
        gap:14px;
        overflow-x:auto;
        padding-bottom:8px;
        margin-bottom:26px;
    }
    .kategori-chip{
        min-width:190px;
        background:#fff;
        border:1px solid var(--border);
        border-radius:14px;
        padding:16px 18px;
        flex-shrink:0;
    }
    .kategori-chip h4{font-size:15px;margin-bottom:10px;}
    .kategori-chip p{font-size:13px;color:var(--text-muted);margin-bottom:2px;}
    .kategori-chip .stock-bar{
        background:#eee;
        border-radius:8px;
        height:6px;
        margin-top:10px;
        overflow:hidden;
    }
    .kategori-chip .stock-bar span{
        display:block;
        height:100%;
        background:var(--orange);
    }

    .card{
        background:#fff;
        border:1px solid var(--border);
        border-radius:14px;
        padding:26px 30px;
        margin-bottom:24px;
    }
    .card h3{font-size:20px;margin-bottom:4px;}
    .card .cat-sub{font-size:13px;color:var(--text-muted);margin-bottom:18px;}

    .item-row{
        display:flex;
        align-items:center;
        gap:16px;
        padding:14px 0;
        border-bottom:1px solid var(--border);
    }
    .item-row:last-child{border-bottom:none;}
    .item-info{flex:1;}
    .item-info h4{font-size:15px;margin-bottom:4px;}
    .item-info p{font-size:13px;color:var(--text-muted);}
    .item-stock{
        text-align:center;
        min-width:110px;
    }
    .item-stock .num{font-size:18px;font-weight:700;}
    .item-stock .label{font-size:11px;color:var(--text-muted);text-transform:uppercase;}

    .badge{
        display:inline-block;
        padding:5px 14px;
        border-radius:14px;
        font-size:12px;
        font-weight:600;
        white-space:nowrap;
    }
    .badge-sangat-baik{background:#e7f8ec;color:var(--green);}
    .badge-baik{background:#e3f3fb;color:#2a9ed8;}
    .badge-cukup{background:#fff8e0;color:#b8900e;}
    .badge-kurang{background:#fff1e0;color:#e08a1e;}
    .badge-buruk{background:#fde9e9;color:#c93a3a;}

    .empty{text-align:center;color:#999;padding:40px 0;}

    @media (max-width:900px){
        .stat-grid{grid-template-columns:repeat(2,1fr);}
    }
</style>
</head>
<body>

<header>
    <div class="brand">
        <h1>PADYProduction</h1>
        <p>Dashboard Owner &mdash; <?= htmlspecialchars($_SESSION['name']); ?></p>
    </div>
    <a href="../auth/logout.php" class="logout">Keluar</a>
</header>

<nav class="tabs">
    <a href="dashboard.php">Dashboard</a>
    <a href="booking/index.php">Daftar Booking</a>
    <a href="jadwal.php">Jadwal</a>
    <a href="inventaris.php" class="active">Informasi Inventaris</a>
</nav>

<main>
    <div class="page-title">
        <h2>Informasi Inventaris</h2>
    </div>

    <div class="stat-grid">
        <div class="stat-card">
            <h4>Total Kategori</h4>
            <div class="num"><?= $total_kategori; ?></div>
        </div>
        <div class="stat-card orange">
            <h4>Total Jenis Barang</h4>
            <div class="num"><?= $total_item; ?></div>
        </div>
        <div class="stat-card green">
            <h4>Stok Tersedia</h4>
            <div class="num"><?= $stok_tersedia; ?></div>
        </div>
        <div class="stat-card red">
            <h4>Stok Sedang Dipakai</h4>
            <div class="num"><?= $stok_dipakai; ?></div>
        </div>
    </div>

    <div class="kategori-scroll">
        <?php mysqli_data_seek($kategori_summary, 0); ?>
        <?php while ($k = mysqli_fetch_assoc($kategori_summary)): ?>
            <?php
                $pakai_persen = $k['total_stock'] > 0
                    ? round(($k['total_in_use'] / $k['total_stock']) * 100)
                    : 0;
            ?>
            <div class="kategori-chip">
                <h4><?= htmlspecialchars($k['category_name']); ?></h4>
                <p><?= $k['total_item']; ?> jenis barang</p>
                <p><?= (int)$k['total_stock']; ?> total stok &middot; <?= (int)$k['total_in_use']; ?> dipakai</p>
                <div class="stock-bar"><span style="width:<?= $pakai_persen; ?>%"></span></div>
            </div>
        <?php endwhile; ?>
    </div>

    <form method="GET" class="filter-bar">
        <input type="text" name="search" value="<?= htmlspecialchars($search); ?>" placeholder="Cari nama barang...">
        <select name="kategori">
            <option value="">-- Semua Kategori --</option>
            <?php mysqli_data_seek($kategori_list, 0); ?>
            <?php while ($kat = mysqli_fetch_assoc($kategori_list)): ?>
            <option value="<?= $kat['id']; ?>" <?= ($kategori_filter === (int)$kat['id']) ? 'selected' : ''; ?>>
                <?= htmlspecialchars($kat['category_name']); ?>
            </option>
            <?php endwhile; ?>
        </select>
        <select name="kondisi">
            <option value="">-- Semua Kondisi --</option>
            <?php foreach (['Sangat Baik','Baik','Cukup','Kurang Baik','Buruk'] as $kd): ?>
            <option value="<?= $kd; ?>" <?= ($kondisi_filter === $kd) ? 'selected' : ''; ?>><?= $kd; ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Filter</button>
        <a href="inventaris.php">Reset</a>
    </form>

    <?php if (empty($items_per_kategori)): ?>
        <div class="card"><p class="empty">Tidak ada data inventaris yang cocok dengan pencarian.</p></div>
    <?php else: ?>
        <?php foreach ($items_per_kategori as $nama_kategori => $items): ?>
        <div class="card">
            <h3><?= htmlspecialchars($nama_kategori); ?></h3>
            <p class="cat-sub"><?= count($items); ?> jenis barang pada kategori ini</p>

            <?php foreach ($items as $it): ?>
                <?php $tersedia = max(0, (int)$it['quantity'] - (int)$it['quantity_in_use']); ?>
                <div class="item-row">
                    <div class="item-info">
                        <h4><?= htmlspecialchars($it['item_name']); ?></h4>
                        <p>Total <?= (int)$it['quantity']; ?> <?= htmlspecialchars($it['unit']); ?> &middot; <?= (int)$it['quantity_in_use']; ?> sedang dipakai</p>
                    </div>
                    <div class="item-stock">
                        <div class="num" style="color:<?= $tersedia > 0 ? 'var(--green)' : '#c93a3a'; ?>"><?= $tersedia; ?></div>
                        <div class="label">Tersedia</div>
                    </div>
                    <span class="badge <?= kondisiBadgeClass($it['condition_status']); ?>"><?= htmlspecialchars($it['condition_status']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

</body>
</html>