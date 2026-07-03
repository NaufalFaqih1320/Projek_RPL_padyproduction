<?php

require_once("../../config/auth.php");
require_once("../../config/database.php");
require_once("../../config/helpers.php");

if ($_SESSION['role'] !== 'crew') {
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

function cekBadge(string $status): string {
    return match ($status) {
        'Siap'   => 'bg-green-50 text-green-600',
        'Kurang' => 'bg-red-50 text-red-600',
        default  => 'bg-gray-100 text-gray-600', // Belum
    };
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Booking - PADY Production</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white">

<?php $active_tab = 'booking'; include "../partials/topbar.php"; ?>

<main class="max-w-4xl mx-auto px-6 py-10">

    <a href="index.php"
       class="inline-flex items-center gap-2 border border-orange-400 text-orange-500 rounded-full px-6 py-2.5 font-medium hover:bg-orange-50 transition mb-8">
        &larr; Kembali
    </a>

    <div class="border border-gray-200 rounded-3xl shadow-sm p-8 space-y-8">

        <div>
            <h2 class="text-2xl font-extrabold text-neutral-900 mb-1"><?= htmlspecialchars($booking['nama_acara']) ?></h2>
            <p class="text-gray-400 text-sm">Kode Booking: <?= htmlspecialchars($booking['booking_code']) ?></p>
        </div>

        <div>
            <h3 class="font-semibold text-neutral-800 mb-3">Informasi Acara</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <p><span class="text-gray-400">Jenis Acara</span><br><?= htmlspecialchars($booking['jenis_acara']) ?></p>
                <p><span class="text-gray-400">Status</span><br><?= htmlspecialchars($booking['status']) ?></p>
                <p><span class="text-gray-400">Tanggal Acara</span><br>🗓 <?= date('d/m/Y', strtotime($booking['tanggal_acara'])) ?></p>
                <p><span class="text-gray-400">Tanggal Booking</span><br>🗓 <?= date('d/m/Y', strtotime($booking['tanggal_booking'])) ?></p>
                <p class="sm:col-span-2"><span class="text-gray-400">Lokasi</span><br>📍 <?= htmlspecialchars($booking['lokasi']) ?></p>
                <p class="sm:col-span-2"><span class="text-gray-400">Kebutuhan Awal</span><br><?= nl2br(htmlspecialchars($booking['kebutuhan_awal'])) ?></p>
                <p class="sm:col-span-2"><span class="text-gray-400">Catatan</span><br><?= nl2br(htmlspecialchars($booking['catatan'] ?: '-')) ?></p>
            </div>
        </div>

        <div>
            <h3 class="font-semibold text-neutral-800 mb-3">Data Client</h3>
            <div class="text-sm space-y-1">
                <p><span class="text-gray-400">Nama:</span> <?= htmlspecialchars($booking['client_name']) ?></p>
                <p><span class="text-gray-400">Email:</span> <?= htmlspecialchars($booking['client_email']) ?></p>
                <p><span class="text-gray-400">Telepon:</span> <?= htmlspecialchars($booking['client_telp']) ?></p>
            </div>
        </div>

        <div>
            <h3 class="font-semibold text-neutral-800 mb-3">Checklist Kebutuhan Dekorasi</h3>
            <?php if (mysqli_num_rows($kebutuhan) === 0): ?>
                <p class="text-gray-400 text-sm">Tidak ada kebutuhan dekorasi dicatat.</p>
            <?php else: ?>
                <div class="space-y-2">
                    <?php while ($k = mysqli_fetch_assoc($kebutuhan)): ?>
                    <div class="flex items-center justify-between border border-gray-100 rounded-xl px-4 py-3 text-sm">
                        <div>
                            <p class="font-medium text-neutral-800"><?= htmlspecialchars($k['nama_kebutuhan']) ?> (<?= (int)$k['jumlah'] ?>)</p>
                            <?php if (!empty($k['catatan'])): ?>
                                <p class="text-gray-400"><?= htmlspecialchars($k['catatan']) ?></p>
                            <?php endif; ?>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?= cekBadge($k['status_cek']) ?>">
                            <?= htmlspecialchars($k['status_cek']) ?>
                        </span>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>

        <div>
            <h3 class="font-semibold text-neutral-800 mb-3">Alat yang Digunakan</h3>
            <?php if (mysqli_num_rows($alat_list) === 0): ?>
                <p class="text-gray-400 text-sm">Tidak ada alat dicatat.</p>
            <?php else: ?>
                <ul class="list-disc list-inside text-sm text-neutral-700 space-y-1">
                    <?php while ($a = mysqli_fetch_assoc($alat_list)): ?>
                        <li><?= htmlspecialchars($a['item_name']) ?> (<?= htmlspecialchars($a['category_name']) ?>) - <?= (int)$a['jumlah_dipakai'] ?> <?= htmlspecialchars($a['unit']) ?></li>
                    <?php endwhile; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div>
            <h3 class="font-semibold text-neutral-800 mb-3">Reminder Otomatis</h3>
            <?php if (mysqli_num_rows($reminders) === 0): ?>
                <p class="text-gray-400 text-sm">Belum ada reminder.</p>
            <?php else: ?>
                <div class="space-y-2 text-sm">
                    <?php while ($r = mysqli_fetch_assoc($reminders)): ?>
                    <div class="flex items-center justify-between border border-gray-100 rounded-xl px-4 py-3">
                        <div>
                            <p class="font-medium text-neutral-800"><?= htmlspecialchars($r['tipe']) ?> — <?= date('d/m/Y H:i', strtotime($r['waktu_reminder'])) ?></p>
                            <p class="text-gray-400"><?= htmlspecialchars($r['pesan']) ?></p>
                        </div>
                        <span class="text-xs font-semibold <?= $r['status_terkirim'] ? 'text-green-600' : 'text-gray-400' ?>">
                            <?= $r['status_terkirim'] ? '✅ Terkirim' : '⏳ Belum' ?>
                        </span>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

</main>

</body>
</html>