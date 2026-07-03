<?php

require_once("../config/auth.php");
require_once("../config/database.php");
require_once("../config/helpers.php");

if ($_SESSION['role'] !== 'owner') {
    header("Location: ../auth/login.php");
    exit();
}

// ─── Parameter bulan/tahun & filter ──────────────────────────────────────────
$bulan_ini = (int) ($_GET['bulan'] ?? date('n'));
$tahun_ini = (int) ($_GET['tahun'] ?? date('Y'));
if ($bulan_ini < 1 || $bulan_ini > 12) $bulan_ini = (int) date('n');

$status_filter = isset($_GET['status']) ? sanitize($conn, $_GET['status']) : '';
$tanggal_pilih = isset($_GET['tanggal']) ? sanitize($conn, $_GET['tanggal']) : '';

// Bulan sebelumnya & berikutnya (untuk tombol navigasi)
$prev_bulan = $bulan_ini - 1; $prev_tahun = $tahun_ini;
if ($prev_bulan < 1) { $prev_bulan = 12; $prev_tahun--; }
$next_bulan = $bulan_ini + 1; $next_tahun = $tahun_ini;
if ($next_bulan > 12) { $next_bulan = 1; $next_tahun++; }

// ─── Ambil booking dalam bulan berjalan ──────────────────────────────────────
$where = "WHERE MONTH(b.tanggal_acara)='$bulan_ini' AND YEAR(b.tanggal_acara)='$tahun_ini'";
if ($status_filter) $where .= " AND b.status='$status_filter'";
if ($tanggal_pilih) $where .= " AND b.tanggal_acara='$tanggal_pilih'";

$result = mysqli_query($conn,
    "SELECT b.*, u.name AS client_name
     FROM bookings b
     LEFT JOIN users u ON b.client_user_id = u.id
     $where
     ORDER BY b.tanggal_acara ASC"
);

// Kelompokkan booking per tanggal (dipakai kalender & agenda)
$jadwal_per_tanggal = [];
while ($b = mysqli_fetch_assoc($result)) {
    $tgl = date('j', strtotime($b['tanggal_acara']));
    $jadwal_per_tanggal[$tgl][] = $b;
}

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
function statusDotColor(string $status): string {
    return match ($status) {
        'Confirmed'   => '#2fae4e',
        'In Progress' => '#e08a1e',
        'Completed'   => '#2a6fdb',
        'Cancelled'   => '#c93a3a',
        default       => '#888',
    };
}

$nama_bulan = ['','Januari','Februari','Maret','April','Mei','Juni',
               'Juli','Agustus','September','Oktober','November','Desember'];

$jumlah_hari   = (int) date('t', mktime(0,0,0,$bulan_ini,1,$tahun_ini));
$hari_pertama  = (int) date('N', mktime(0,0,0,$bulan_ini,1,$tahun_ini)); // 1=Senin ... 7=Minggu
$offset_kosong = $hari_pertama - 1;

$hari_ini_cocok = ($bulan_ini == (int)date('n') && $tahun_ini == (int)date('Y')) ? (int)date('j') : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Jadwal - PADY Production</title>
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
        align-items:center;
    }
    .filter-bar select{
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

    .month-nav{
        display:flex;
        align-items:center;
        gap:14px;
        margin-left:auto;
    }
    .month-nav a{
        width:36px;height:36px;
        display:flex;align-items:center;justify-content:center;
        border:1px solid var(--border);
        border-radius:50%;
        text-decoration:none;
        color:var(--dark);
        font-weight:700;
    }
    .month-nav span{font-size:17px;font-weight:600;min-width:150px;text-align:center;}

    .card{
        background:#fff;
        border:1px solid var(--border);
        border-radius:14px;
        padding:26px 30px;
        margin-bottom:24px;
    }

    /* Kalender */
    .calendar{
        width:100%;
        border-collapse:collapse;
    }
    .calendar th{
        font-size:13px;
        color:var(--text-muted);
        font-weight:600;
        padding:8px 0;
        text-align:center;
        text-transform:uppercase;
    }
    .calendar td{
        border:1px solid var(--border);
        vertical-align:top;
        height:100px;
        width:14.28%;
        padding:8px;
    }
    .calendar td.empty{background:#fbfbfb;}
    .calendar td.today{background:#fff6f0;border:1px solid var(--orange);}
    .calendar td:hover{background:#fafafa;cursor:pointer;}
    .day-num{
        font-size:14px;
        font-weight:600;
        margin-bottom:6px;
        display:inline-block;
    }
    .today .day-num{
        background:var(--orange);
        color:#fff;
        border-radius:50%;
        width:22px;height:22px;
        line-height:22px;
        text-align:center;
        display:inline-block;
    }
    .event-chip{
        display:block;
        font-size:11px;
        padding:2px 6px;
        border-radius:8px;
        margin-bottom:3px;
        color:#fff;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
        text-decoration:none;
    }
    .event-more{
        font-size:11px;
        color:var(--text-muted);
    }

    /* Legenda */
    .legend{display:flex;gap:18px;flex-wrap:wrap;margin-top:16px;}
    .legend-item{display:flex;align-items:center;gap:6px;font-size:13px;color:#555;}
    .legend-dot{width:10px;height:10px;border-radius:50%;display:inline-block;}

    /* Agenda list */
    .agenda-title{font-size:20px;margin-bottom:16px;}
    .agenda-item{
        display:flex;
        gap:18px;
        padding:16px 0;
        border-bottom:1px solid var(--border);
    }
    .agenda-item:last-child{border-bottom:none;}
    .agenda-date{
        min-width:64px;
        text-align:center;
    }
    .agenda-date .num{font-size:22px;font-weight:700;color:var(--orange);display:block;}
    .agenda-date .mon{font-size:12px;color:var(--text-muted);text-transform:uppercase;}
    .agenda-body{flex:1;}
    .agenda-body h4{font-size:16px;margin-bottom:4px;}
    .agenda-body p{font-size:13px;color:#555;margin-bottom:2px;}
    .agenda-actions{display:flex;align-items:center;}
    .btn-edit{
        border:1px solid var(--orange);
        color:var(--orange);
        background:transparent;
        padding:7px 16px;
        border-radius:20px;
        font-size:13px;
        cursor:pointer;
        text-decoration:none;
        white-space:nowrap;
    }

    .badge{
        display:inline-block;
        padding:4px 12px;
        border-radius:14px;
        font-size:12px;
        font-weight:600;
    }
    .badge-confirmed{background:#e7f8ec;color:var(--green);}
    .badge-draft{background:#eee;color:#666;}
    .badge-progress{background:#fff1e0;color:#e08a1e;}
    .badge-completed{background:#e6f0ff;color:#2a6fdb;}
    .badge-cancelled{background:#fde9e9;color:#c93a3a;}

    .empty{text-align:center;color:#999;padding:40px 0;}
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
    <a href="jadwal.php" class="active">Jadwal</a>
    <a href="inventaris.php">Informasi Inventaris</a>
</nav>

<main>
    <div class="page-title">
        <h2>Jadwal Acara</h2>
        <a href="booking/add.php" class="btn-add">+ Tambah Booking</a>
    </div>

    <form method="GET" class="filter-bar">
        <input type="hidden" name="bulan" value="<?= $bulan_ini; ?>">
        <input type="hidden" name="tahun" value="<?= $tahun_ini; ?>">
        <select name="status">
            <option value="">-- Semua Status --</option>
            <?php foreach (['Draft','Confirmed','In Progress','Completed','Cancelled'] as $s): ?>
            <option value="<?= $s; ?>" <?= ($status_filter === $s) ? 'selected' : ''; ?>><?= $s; ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Filter</button>
        <a href="jadwal.php?bulan=<?= $bulan_ini; ?>&tahun=<?= $tahun_ini; ?>">Reset</a>

        <div class="month-nav">
            <a href="?bulan=<?= $prev_bulan; ?>&tahun=<?= $prev_tahun; ?>&status=<?= urlencode($status_filter); ?>">&lsaquo;</a>
            <span><?= $nama_bulan[$bulan_ini] . ' ' . $tahun_ini; ?></span>
            <a href="?bulan=<?= $next_bulan; ?>&tahun=<?= $next_tahun; ?>&status=<?= urlencode($status_filter); ?>">&rsaquo;</a>
        </div>
    </form>

    <div class="card">
        <table class="calendar">
            <thead>
                <tr>
                    <th>Sen</th><th>Sel</th><th>Rab</th><th>Kam</th><th>Jum</th><th>Sab</th><th>Min</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                <?php
                // Sel kosong sebelum tanggal 1
                for ($i = 0; $i < $offset_kosong; $i++) echo '<td class="empty"></td>';

                $kolom = $offset_kosong;
                for ($hari = 1; $hari <= $jumlah_hari; $hari++) {
                    if ($kolom === 7) { echo '</tr><tr>'; $kolom = 0; }
                    $isToday   = ($hari === $hari_ini_cocok);
                    $eventsHr  = $jadwal_per_tanggal[$hari] ?? [];
                    $tgl_full  = sprintf('%04d-%02d-%02d', $tahun_ini, $bulan_ini, $hari);

                    echo '<td class="' . ($isToday ? 'today' : '') . '" onclick="location.href=\'?bulan=' . $bulan_ini . '&tahun=' . $tahun_ini . '&status=' . urlencode($status_filter) . '&tanggal=' . $tgl_full . '#agenda\'">';
                    echo '<span class="day-num">' . $hari . '</span>';

                    $tampil = array_slice($eventsHr, 0, 2);
                    foreach ($tampil as $ev) {
                        echo '<a class="event-chip" style="background:' . statusDotColor($ev['status']) . '" href="booking/detail.php?id=' . $ev['id'] . '" onclick="event.stopPropagation()">'
                             . htmlspecialchars($ev['nama_acara']) . '</a>';
                    }
                    if (count($eventsHr) > 2) {
                        echo '<span class="event-more">+' . (count($eventsHr) - 2) . ' lainnya</span>';
                    }
                    echo '</td>';
                    $kolom++;
                }
                // Sel kosong penutup baris terakhir
                while ($kolom < 7) { echo '<td class="empty"></td>'; $kolom++; }
                ?>
                </tr>
            </tbody>
        </table>

        <div class="legend">
            <div class="legend-item"><span class="legend-dot" style="background:#888"></span> Draft</div>
            <div class="legend-item"><span class="legend-dot" style="background:#2fae4e"></span> Konfirmasi</div>
            <div class="legend-item"><span class="legend-dot" style="background:#e08a1e"></span> Berlangsung</div>
            <div class="legend-item"><span class="legend-dot" style="background:#2a6fdb"></span> Selesai</div>
            <div class="legend-item"><span class="legend-dot" style="background:#c93a3a"></span> Dibatalkan</div>
        </div>
    </div>

    <div class="card" id="agenda">
        <h3 class="agenda-title">
            <?= $tanggal_pilih ? 'Agenda ' . formatTanggal($tanggal_pilih) : 'Agenda Bulan ' . $nama_bulan[$bulan_ini] . ' ' . $tahun_ini; ?>
            <?php if ($tanggal_pilih): ?>
                <a href="?bulan=<?= $bulan_ini; ?>&tahun=<?= $tahun_ini; ?>&status=<?= urlencode($status_filter); ?>" style="font-size:13px;color:var(--orange);text-decoration:none;">(lihat semua)</a>
            <?php endif; ?>
        </h3>

        <?php
        // Susun ulang jadwal menjadi list terurut tanggal untuk agenda
        $agenda = [];
        foreach ($jadwal_per_tanggal as $hari => $list) {
            foreach ($list as $ev) $agenda[] = $ev;
        }
        usort($agenda, fn($a, $b) => strtotime($a['tanggal_acara']) <=> strtotime($b['tanggal_acara']));
        ?>

        <?php if (empty($agenda)): ?>
            <p class="empty">Tidak ada jadwal acara pada periode ini.</p>
        <?php else: ?>
            <?php foreach ($agenda as $ev): ?>
            <div class="agenda-item">
                <div class="agenda-date">
                    <span class="num"><?= date('d', strtotime($ev['tanggal_acara'])); ?></span>
                    <span class="mon"><?= strtoupper(substr($nama_bulan[(int)date('n', strtotime($ev['tanggal_acara']))], 0, 3)); ?></span>
                </div>
                <div class="agenda-body">
                    <h4><?= htmlspecialchars($ev['nama_acara']); ?></h4>
                    <p>👤 <?= htmlspecialchars($ev['client_name'] ?? '-'); ?> &nbsp;•&nbsp; 📍 <?= htmlspecialchars($ev['lokasi']); ?></p>
                    <p>Kode: <?= htmlspecialchars($ev['booking_code']); ?></p>
                    <span class="badge <?= statusBadgeClass($ev['status']); ?>"><?= statusLabel($ev['status']); ?></span>
                </div>
                <div class="agenda-actions">
                    <a href="booking/detail.php?id=<?= $ev['id']; ?>" class="btn-edit">Detail</a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

</body>
</html>