<?php

require_once("../../config/auth.php");
require_once("../../config/database.php");
require_once("../../config/helpers.php");

if ($_SESSION['role'] !== 'crew') {
    header("Location: ../../auth/login.php");
    exit();
}

$status_filter = isset($_GET['status']) ? sanitize($conn, $_GET['status']) : '';
$search        = isset($_GET['search']) ? sanitize($conn, $_GET['search']) : '';

$where = "WHERE 1=1";
if ($status_filter) $where .= " AND b.status='$status_filter'";
if ($search)        $where .= " AND (b.nama_acara LIKE '%$search%' OR b.booking_code LIKE '%$search%' OR u.name LIKE '%$search%')";

$bookings_result = mysqli_query($conn,
    "SELECT b.*, u.name AS client_name
     FROM bookings b
     LEFT JOIN users u ON b.client_user_id = u.id
     $where
     ORDER BY b.tanggal_acara ASC"
);

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

function statusBadgeStyle(string $status): string {
    return match ($status) {
        'Confirmed'   => 'bg-green-50 text-green-600',
        'In Progress' => 'bg-orange-50 text-orange-600',
        'Completed'   => 'bg-blue-50 text-blue-600',
        'Cancelled'   => 'bg-red-50 text-red-600',
        default       => 'bg-gray-100 text-gray-600', // Draft
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Booking - PADY Production</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white">

<?php $active_tab = 'booking'; include "../partials/topbar.php"; ?>

<main class="max-w-6xl mx-auto px-6 py-8">

    <div class="flex items-center justify-between mb-6">
        <h2 class="text-3xl font-extrabold text-neutral-900">Daftar Booking</h2>
    </div>

    <form method="GET" class="flex flex-wrap gap-3 mb-8">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
               placeholder="Cari nama acara / client..."
               class="border border-gray-300 rounded-full px-5 py-2.5 text-sm flex-1 min-w-[220px] focus:outline-none focus:ring-2 focus:ring-orange-300">
        <select name="status" class="border border-gray-300 rounded-full px-5 py-2.5 text-sm">
            <option value="">-- Semua Status --</option>
            <?php foreach (['Draft','Confirmed','In Progress','Completed','Cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= $status_filter === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="bg-neutral-900 text-white rounded-full px-6 py-2.5 text-sm font-medium">Filter</button>
        <a href="index.php" class="border border-gray-300 rounded-full px-6 py-2.5 text-sm text-gray-600">Reset</a>
    </form>

    <div class="space-y-5">
        <?php if (empty($bookings)): ?>
            <p class="text-gray-500 text-center py-10">Belum ada data booking.</p>
        <?php else: ?>
            <?php foreach ($bookings as $b): ?>
            <div class="border border-gray-200 rounded-2xl shadow-sm p-6">
                <div class="flex items-start justify-between mb-3">
                    <h3 class="text-2xl font-bold text-neutral-900"><?= htmlspecialchars($b['nama_acara']) ?></h3>
                    <a href="detail.php?id=<?= $b['id'] ?>"
                       class="border border-orange-400 text-orange-500 rounded-full px-6 py-2 text-sm font-medium hover:bg-orange-50 transition whitespace-nowrap">
                        Detail
                    </a>
                </div>

                <span class="inline-block px-4 py-1 rounded-full text-sm font-semibold mb-3 <?= statusBadgeStyle($b['status']) ?>">
                    <?= statusLabel($b['status']) ?>
                </span>

                <p class="text-lg font-semibold text-neutral-800"><?= htmlspecialchars($b['client_name'] ?? '-') ?></p>
                <p class="text-gray-400 text-sm mb-4">ID Booking : <?= htmlspecialchars($b['booking_code']) ?></p>

                <div class="flex flex-wrap gap-10 mb-2">
                    <div>
                        <p class="text-gray-400 text-sm">Lokasi Acara</p>
                        <p class="text-neutral-800">📍 <?= htmlspecialchars($b['lokasi']) ?></p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-sm">Tanggal Acara</p>
                        <p class="text-neutral-800">🗓 <?= formatTanggal($b['tanggal_acara']) ?></p>
                    </div>
                </div>

                <div class="mt-3">
                    <p class="text-gray-400 text-sm mb-1">Alat yang Disewa</p>
                    <?php if (empty($b['alat'])): ?>
                        <p class="text-gray-400 text-sm">Belum ada alat dipilih.</p>
                    <?php else: ?>
                        <ul class="list-disc list-inside text-neutral-700 text-sm space-y-0.5">
                            <?php foreach ($b['alat'] as $item): ?>
                                <li><?= htmlspecialchars($item['item_name']) ?> - <?= (int)$item['jumlah_dipakai'] ?> <?= htmlspecialchars($item['unit']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</main>

</body>
</html>