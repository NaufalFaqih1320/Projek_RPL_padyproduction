<?php

require_once("../../config/auth.php");
require_once("../../config/database.php");
require_once("../../config/helpers.php");

if ($_SESSION['role'] !== 'crew') {
    header("Location: ../../auth/login.php");
    exit();
}

$categories = mysqli_query($conn, "SELECT * FROM inventory_categories ORDER BY category_name ASC");

$errors = getFlashErrors('inv_errors');
$old = $_SESSION['inv_old'] ?? [];
unset($_SESSION['inv_old']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Inventaris - PADY Production</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white">

<?php $active_tab = 'inventaris'; include "../partials/topbar.php"; ?>

<main class="max-w-4xl mx-auto px-6 py-10">

    <a href="index.php"
       class="inline-flex items-center gap-2 border border-orange-400 text-orange-500 rounded-full px-6 py-2.5 font-medium hover:bg-orange-50 transition mb-8">
        &larr; Kembali
    </a>

    <div class="border border-gray-200 rounded-3xl shadow-sm p-10">
        <h2 class="text-3xl font-extrabold text-center text-neutral-900 mb-8">Tambah Inventaris</h2>

        <?= $errors ?>

        <form action="process_add.php" method="POST" class="space-y-6">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label class="block text-neutral-800 mb-2">Jenis Inventaris</label>
                    <select name="category_id" required
                            class="w-full border border-orange-300 rounded-full px-5 py-3 text-gray-600 focus:outline-none focus:ring-2 focus:ring-orange-300">
                        <option value="" disabled <?= empty($old['category_id']) ? 'selected' : '' ?>>Pilih jenis inventaris</option>
                        <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                            <option value="<?= $cat['id'] ?>" <?= (($old['category_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['category_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-neutral-800 mb-2">Nama Inventaris</label>
                    <input type="text" name="item_name" required placeholder="Sound System Besar"
                           value="<?= htmlspecialchars($old['item_name'] ?? '') ?>"
                           class="w-full border border-orange-300 rounded-full px-5 py-3 text-gray-600 focus:outline-none focus:ring-2 focus:ring-orange-300">
                </div>
            </div>

            <div>
                <label class="block text-neutral-800 mb-2">Jumlah</label>
                <input type="number" name="quantity" min="1" required placeholder="1"
                       value="<?= htmlspecialchars($old['quantity'] ?? '') ?>"
                       class="w-full border border-orange-300 rounded-full px-5 py-3 text-gray-600 focus:outline-none focus:ring-2 focus:ring-orange-300">
            </div>

            <input type="hidden" name="unit" value="Unit">

            <div>
                <label class="block text-neutral-800 mb-2">Kondisi</label>
                <select name="condition_status" required
                        class="w-full border border-orange-300 rounded-full px-5 py-3 text-gray-600 focus:outline-none focus:ring-2 focus:ring-orange-300">
                    <?php foreach (['Sangat Baik', 'Baik', 'Cukup', 'Kurang Baik', 'Buruk'] as $kondisi): ?>
                        <option value="<?= $kondisi ?>" <?= (($old['condition_status'] ?? 'Sangat Baik') === $kondisi) ? 'selected' : '' ?>>
                            <?= $kondisi ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex gap-4 pt-4">
                <a href="index.php"
                   class="flex-1 text-center border border-orange-400 text-orange-500 rounded-full py-3 font-semibold hover:bg-orange-50 transition">
                    Batal
                </a>
                <button type="submit"
                        class="flex-1 bg-orange-500 hover:bg-orange-600 text-white rounded-full py-3 font-semibold transition">
                    Tambah
                </button>
            </div>

        </form>
    </div>

</main>

</body>
</html>