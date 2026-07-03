<?php
/**
 * crew/partials/topbar.php
 * Include ini di setiap halaman crew. Set $active_tab sebelum include:
 *   'inventaris' | 'booking' | 'jadwal'
 */
$active_tab = $active_tab ?? 'inventaris';

function navClass(string $tab, string $active): string {
    return $tab === $active
        ? 'text-orange-500 border-b-2 border-orange-500 font-semibold'
        : 'text-gray-500 hover:text-gray-700 border-b-2 border-transparent';
}
?>
<header class="bg-neutral-900">
    <div class="max-w-6xl mx-auto flex items-center justify-between px-6 py-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-neutral-800 flex items-center justify-center text-orange-500 font-black text-xs border border-neutral-700">
                PADY
            </div>
            <div>
                <h1 class="text-2xl font-extrabold text-orange-500 leading-tight">PADYProduction</h1>
                <p class="text-gray-300 text-sm">Dashboard Crew - Gudang</p>
            </div>
        </div>
        <a href="/padyproduction/auth/logout.php"
           class="border border-orange-500 text-orange-500 rounded-full px-5 py-2 text-sm font-medium hover:bg-orange-500 hover:text-white transition">
            Keluar
        </a>
    </div>
</header>

<nav class="border-b border-gray-200 bg-white">
    <div class="max-w-6xl mx-auto flex gap-10 px-6">
        <a href="/padyproduction/crew/inventory/index.php"
           class="py-4 text-lg <?= navClass('inventaris', $active_tab) ?>">Kelola Inventaris</a>
        <a href="/padyproduction/crew/booking/index.php"
           class="py-4 text-lg <?= navClass('booking', $active_tab) ?>">Daftar Booking</a>
        <a href="/padyproduction/crew/jadwal.php"
           class="py-4 text-lg <?= navClass('jadwal', $active_tab) ?>">Jadwal</a>
    </div>
</nav>