<?php

require_once("../../config/auth.php");
require_once("../../config/database.php");
require_once("../../config/helpers.php");

if ($_SESSION['role'] !== 'crew') {
    header("Location: ../../auth/login.php");
    exit();
}

// Filter kategori (klik chip -> ?kategori=Audio, default Semua)
$kategori = isset($_GET['kategori']) ? sanitize($conn, $_GET['kategori']) : 'Semua';

$where = $kategori !== 'Semua' ? "WHERE ic.category_name = '$kategori'" : "";

$items = mysqli_query($conn, "
    SELECT i.*, ic.category_name
    FROM inventory i
    JOIN inventory_categories ic ON i.category_id = ic.id
    $where
    ORDER BY i.item_name ASC
");

$categories = mysqli_query($conn, "SELECT category_name FROM inventory_categories ORDER BY category_name ASC");

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Inventaris - PADY Production</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white">

<?php $active_tab = 'inventaris'; include "../partials/topbar.php"; ?>

<main class="max-w-6xl mx-auto px-6 py-8">

    <?php if ($flash): ?>
        <div class="mb-6"><?= $flash ?></div>
    <?php endif; ?>

    <div class="flex items-center justify-between mb-6">
        <h2 class="text-3xl font-extrabold text-neutral-900">Daftar Inventaris</h2>
        <a href="add.php"
           class="bg-orange-500 hover:bg-orange-600 text-white font-semibold rounded-full px-6 py-3 flex items-center gap-2 transition">
            <span class="text-lg leading-none">+</span> Tambah Inventaris
        </a>
    </div>

    <div class="flex flex-wrap gap-3 mb-8">
        <a href="?kategori=Semua"
           class="px-5 py-2 rounded-full border text-sm font-medium transition
           <?= $kategori === 'Semua' ? 'bg-orange-500 text-white border-orange-500' : 'border-orange-400 text-orange-500 hover:bg-orange-50' ?>">
            Semua
        </a>
        <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
            <a href="?kategori=<?= urlencode($cat['category_name']) ?>"
               class="px-5 py-2 rounded-full border text-sm font-medium transition
               <?= $kategori === $cat['category_name'] ? 'bg-orange-500 text-white border-orange-500' : 'border-orange-400 text-orange-500 hover:bg-orange-50' ?>">
                <?= htmlspecialchars($cat['category_name']) ?>
            </a>
        <?php endwhile; ?>
    </div>

    <div class="space-y-5">
        <?php if (mysqli_num_rows($items) === 0): ?>
            <p class="text-gray-500 text-center py-10">Belum ada inventaris pada kategori ini.</p>
        <?php else: ?>
            <?php while ($item = mysqli_fetch_assoc($items)):
                $tersedia = max(0, (int)$item['quantity'] - (int)$item['quantity_in_use']);
            ?>
            <div class="border border-green-100 rounded-2xl shadow-sm p-6 flex items-center justify-between">
                <div class="flex-1">
                    <h3 class="text-2xl font-bold text-neutral-900 mb-4"><?= htmlspecialchars($item['item_name']) ?></h3>
                    <div class="flex gap-16">
                        <div>
                            <p class="text-gray-400 text-sm mb-1">Total Stok</p>
                            <p class="text-2xl font-bold text-neutral-900"><?= (int)$item['quantity'] ?> <span class="text-sm font-normal text-gray-400">unit</span></p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-sm mb-1">Sedang Disewa</p>
                            <p class="text-2xl font-bold text-orange-500"><?= (int)$item['quantity_in_use'] ?> <span class="text-sm font-normal text-gray-400">unit</span></p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-sm mb-1">Tersedia</p>
                            <p class="text-2xl font-bold text-green-600"><?= $tersedia ?> <span class="text-sm font-normal text-gray-400">unit</span></p>
                        </div>
                    </div>
                </div>
                <a href="detail.php?id=<?= (int)$item['category_id'] ?>"
                   class="border border-orange-400 text-orange-500 rounded-full px-6 py-2.5 font-medium hover:bg-orange-50 transition whitespace-nowrap">
                    ✎ Detail
                </a>
            </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

</main>

</body>
</html>