<?php

require_once("../config/auth.php");
require_once("../config/database.php");
require_once("../config/helpers.php");

if ($_SESSION['role'] !== 'owner') {
    header("Location: ../auth/login.php");
    exit();
}

// ─── Statistik ────────────────────────────────────────────────────────────
$total_booking     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM bookings"))['n'];
$booking_bulan     = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS n FROM bookings WHERE MONTH(tanggal_acara)=MONTH(CURDATE()) AND YEAR(tanggal_acara)=YEAR(CURDATE())"))['n'];
$total_inventaris  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM inventory"))['n'];
$reminders_pending = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS n FROM reminders WHERE status_terkirim=0 AND waktu_reminder <= NOW()"))['n'];

// Ringkasan status booking (untuk grafik mini/legend)
$status_counts_res = mysqli_query($conn, "SELECT status, COUNT(*) AS n FROM bookings GROUP BY status");
$status_counts = ['Draft'=>0,'Confirmed'=>0,'In Progress'=>0,'Completed'=>0,'Cancelled'=>0];
while ($row = mysqli_fetch_assoc($status_counts_res)) {
    $status_counts[$row['status']] = (int) $row['n'];
}

// Ringkasan stok inventaris
$stok = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(quantity),0) AS total, COALESCE(SUM(quantity_in_use),0) AS dipakai FROM inventory"
));
$stok_tersedia = max(0, (int)$stok['total'] - (int)$stok['dipakai']);

// ─── Booking mendatang ───────────────────────────────────────────────────────
$booking_mendatang = mysqli_query($conn,
    "SELECT b.*, u.name AS client_name FROM bookings b
     JOIN users u ON b.client_user_id=u.id
     WHERE b.tanggal_acara >= CURDATE() AND b.status NOT IN ('Completed','Cancelled')
     ORDER BY b.tanggal_acara ASC LIMIT 5"
);

// ─── Notifikasi/reminder terbaru untuk owner ─────────────────────────────────
$notif_terbaru = mysqli_query($conn,
    "SELECT * FROM notifications WHERE user_id='{$_SESSION['id']}' ORDER BY created_at DESC LIMIT 5"
);

function statusBadgeClass(string $status): string {
    return match ($status) {
        'Confirmed'   => 'badge-confirmed',
        'In Progress' => 'badge-progress',
        'Completed'   => 'badge-completed',
        'Cancelled'   => 'badge-cancelled',
        default       => 'badge-draft',
    };
}
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
<title>Dashboard Owner - PADY Production</title>
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
        margin-bottom:6px;
        flex-wrap:wrap;
        gap:12px;
    }
    .page-title h2{font-size:28px;}
    .welcome{color:var(--text-muted);font-size:15px;margin-bottom:24px;}

    /* Kartu statistik */
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
        display:flex;
        flex-direction:column;
        gap:6px;
    }
    .stat-card h4{font-size:13px;color:var(--text-muted);font-weight:600;text-transform:uppercase;}
    .stat-card .num{font-size:30px;font-weight:700;}
    .stat-card.orange .num{color:var(--orange);}
    .stat-card.green .num{color:var(--green);}
    .stat-card.red .num{color:#c93a3a;}
    .stat-card .sub{font-size:12px;color:var(--text-muted);}

    .grid-2{
        display:grid;
        grid-template-columns:2fr 1fr;
        gap:24px;
        align-items:start;
    }

    .card{
        background:#fff;
        border:1px solid var(--border);
        border-radius:14px;
        padding:26px 30px;
        margin-bottom:24px;
    }
    .card-header{
        display:flex;
        justify-content:space-between;
        align-items:center;
        margin-bottom:18px;
    }
    .card-header h3{font-size:20px;}
    .card-header a{
        font-size:13px;
        color:var(--orange);
        text-decoration:none;
        font-weight:600;
    }

    .booking-item{
        display:flex;
        gap:16px;
        align-items:center;
        padding:14px 0;
        border-bottom:1px solid var(--border);
    }
    .booking-item:last-child{border-bottom:none;}
    .booking-date{
        min-width:56px;
        text-align:center;
    }
    .booking-date .num{font-size:20px;font-weight:700;color:var(--orange);display:block;}
    .booking-date .mon{font-size:11px;color:var(--text-muted);text-transform:uppercase;}
    .booking-body{flex:1;}
    .booking-body h4{font-size:15px;margin-bottom:3px;}
    .booking-body p{font-size:13px;color:var(--text-muted);}
    .booking-actions a{
        border:1px solid var(--orange);
        color:var(--orange);
        background:transparent;
        padding:7px 16px;
        border-radius:20px;
        font-size:13px;
        text-decoration:none;
        white-space:nowrap;
    }

    .badge{
        display:inline-block;
        padding:4px 12px;
        border-radius:14px;
        font-size:12px;
        font-weight:600;
        margin-top:4px;
    }
    .badge-confirmed{background:#e7f8ec;color:var(--green);}
    .badge-draft{background:#eee;color:#666;}
    .badge-progress{background:#fff1e0;color:#e08a1e;}
    .badge-completed{background:#e6f0ff;color:#2a6fdb;}
    .badge-cancelled{background:#fde9e9;color:#c93a3a;}

    /* Status ringkas */
    .status-list{display:flex;flex-direction:column;gap:12px;}
    .status-row{display:flex;align-items:center;gap:10px;}
    .status-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0;}
    .status-row .label{flex:1;font-size:14px;color:#444;}
    .status-row .count{font-size:14px;font-weight:700;}

    .notif-item{padding:12px 0;border-bottom:1px solid var(--border);}
    .notif-item:last-child{border-bottom:none;}
    .notif-item h5{font-size:14px;margin-bottom:2px;}
    .notif-item p{font-size:12px;color:var(--text-muted);}

    .empty{text-align:center;color:#999;padding:30px 0;font-size:14px;}

    @media (max-width:1000px){
        .stat-grid{grid-template-columns:repeat(2,1fr);}
        .grid-2{grid-template-columns:1fr;}
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
    <a href="dashboard.php" class="active">Dashboard</a>
    <a href="booking/index.php">Daftar Booking</a>
    <a href="jadwal.php">Jadwal</a>
    <a href="inventaris.php">Informasi Inventaris</a>
</nav>

<main>
    <div class="page-title">
        <h2>Selamat Datang, <?= htmlspecialchars($_SESSION['name']); ?> 👋</h2>
    </div>
    <p class="welcome">Berikut ringkasan aktivitas PADYProduction hari ini.</p>

    <div class="stat-grid">
        <div class="stat-card orange">
            <h4>Total Booking</h4>
            <div class="num"><?= $total_booking; ?></div>
        </div>
        <div class="stat-card">
            <h4>Booking Bulan Ini</h4>
            <div class="num"><?= $booking_bulan; ?></div>
        </div>
        <div class="stat-card green">
            <h4>Stok Inventaris Tersedia</h4>
            <div class="num"><?= $stok_tersedia; ?></div>
            <div class="sub">dari <?= $total_inventaris; ?> jenis barang</div>
        </div>
        <div class="stat-card red">
            <h4>Reminder Tertunda</h4>
            <div class="num"><?= $reminders_pending; ?></div>
        </div>
    </div>

    <div class="grid-2">
        <div>
            <div class="card">
                <div class="card-header">
                    <h3>Booking Mendatang</h3>
                    <a href="booking/index.php">Lihat semua &rarr;</a>
                </div>

                <?php if (mysqli_num_rows($booking_mendatang) === 0): ?>
                    <p class="empty">Tidak ada booking mendatang.</p>
                <?php else: ?>
                    <?php while ($b = mysqli_fetch_assoc($booking_mendatang)): ?>
                    <div class="booking-item">
                        <div class="booking-date">
                            <span class="num"><?= date('d', strtotime($b['tanggal_acara'])); ?></span>
                            <span class="mon"><?= strtoupper(date('M', strtotime($b['tanggal_acara']))); ?></span>
                        </div>
                        <div class="booking-body">
                            <h4><?= htmlspecialchars($b['nama_acara']); ?></h4>
                            <p>👤 <?= htmlspecialchars($b['client_name']); ?> &nbsp;•&nbsp; 📍 <?= htmlspecialchars($b['lokasi']); ?></p>
                            <span class="badge <?= statusBadgeClass($b['status']); ?>"><?= statusLabel($b['status']); ?></span>
                        </div>
                        <div class="booking-actions">
                            <a href="booking/detail.php?id=<?= $b['id']; ?>">Detail</a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <div class="card">
                <div class="card-header">
                    <h3>Status Booking</h3>
                </div>
                <div class="status-list">
                    <div class="status-row">
                        <span class="status-dot" style="background:#888"></span>
                        <span class="label">Draft</span>
                        <span class="count"><?= $status_counts['Draft']; ?></span>
                    </div>
                    <div class="status-row">
                        <span class="status-dot" style="background:#2fae4e"></span>
                        <span class="label">Konfirmasi</span>
                        <span class="count"><?= $status_counts['Confirmed']; ?></span>
                    </div>
                    <div class="status-row">
                        <span class="status-dot" style="background:#e08a1e"></span>
                        <span class="label">Berlangsung</span>
                        <span class="count"><?= $status_counts['In Progress']; ?></span>
                    </div>
                    <div class="status-row">
                        <span class="status-dot" style="background:#2a6fdb"></span>
                        <span class="label">Selesai</span>
                        <span class="count"><?= $status_counts['Completed']; ?></span>
                    </div>
                    <div class="status-row">
                        <span class="status-dot" style="background:#c93a3a"></span>
                        <span class="label">Dibatalkan</span>
                        <span class="count"><?= $status_counts['Cancelled']; ?></span>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Notifikasi Terbaru</h3>
                </div>
                <?php if (!$notif_terbaru || mysqli_num_rows($notif_terbaru) === 0): ?>
                    <p class="empty">Belum ada notifikasi.</p>
                <?php else: ?>
                    <?php while ($n = mysqli_fetch_assoc($notif_terbaru)): ?>
                    <div class="notif-item">
                        <h5><?= htmlspecialchars($n['title']); ?></h5>
                        <p><?= htmlspecialchars($n['message']); ?></p>
                    </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

</body>
</html>