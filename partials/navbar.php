<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ob_start();

$current_page = basename($_SERVER['PHP_SELF']);

// Redirect ke login jika belum login
if (!isset($_SESSION['user_id']) && $current_page != 'login.php') {
    header("Location: login.php");
    exit;
}
?>

<!-- Desktop Sidebar -->
<nav class="hidden md:flex md:flex-col md:fixed md:inset-y-0 md:left-0 md:w-64 md:bg-white md:shadow-lg">
    <div class="flex flex-col h-full">
        <!-- Logo/Brand -->
        <div class="flex flex-col items-center justify-center h-auto px-4 py-6 bg-[#191970]">
            <img src="assets/images/logo-sekolah.png" alt="Logo Sekolah" class="w-20 h-20 mb-3">
            <div class="text-center">
                <div class="text-xl font-bold text-white mb-1">SMP Negeri 2 </div>
                <div class="text-sm font-medium text-white mb-1">Sepatan</div>
            </div>
        </div>

        <!-- Menu Items -->
        <div class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
            <a href="dashboard.php" 
               class="flex items-center px-4 py-3 rounded-lg <?= $current_page == 'dashboard.php' ? 'bg-orange-100 text-[#191970]' : 'text-gray-600 hover:bg-orange-50 hover:text-[#191970]' ?>">
                <i class="fas fa-home w-6"></i>
                <span class="ml-3">Dashboard</span>
            </a>

            <?php if ($_SESSION['role'] == 'guru'): ?>
                <a href="absensi.php" 
                   class="flex items-center px-4 py-3 rounded-lg <?= $current_page == 'absensi.php' ? 'bg-orange-100 text-[#191970]' : 'text-gray-600 hover:bg-orange-50 hover:text-[#191970]' ?>">
                    <i class="fas fa-clipboard-check w-6"></i>
                    <span class="ml-3">Absensi</span>
                </a>

                <!-- Menu Nilai -->
                <div class="relative" x-data="{ open: false }">
                    <button onclick="toggleDropdown()" 
                        class="flex items-center w-full px-4 py-3 rounded-lg <?= in_array($current_page, ['nilai.php', 'tugas.php']) ? 'bg-orange-100 text-[#191970]' : 'text-gray-600 hover:bg-orange-50 hover:text-[#191970]' ?>">
                        <i class="fas fa-graduation-cap w-6"></i>
                        <span class="ml-3 flex-1 text-left">Nilai</span>
                        <i class="fas fa-chevron-down ml-auto text-sm"></i>
                    </button>

                    <div id="dropdownMenu" class="absolute left-0 mt-1 w-40 bg-white border rounded-lg shadow-lg hidden z-10">
                        <a href="tugas.php" 
                           class="block px-4 py-2 text-sm <?= $current_page == 'tugas.php' ? 'bg-orange-100 text-[#191970]' : 'text-gray-700 hover:bg-orange-50 hover:text-[#191970]' ?>">
                            Data Tugas
                        </a>
                        <a href="nilai.php" 
                           class="block px-4 py-2 text-sm <?= $current_page == 'nilai.php' ? 'bg-orange-100 text-[#191970]' : 'text-gray-700 hover:bg-orange-50 hover:text-[#191970]' ?>">
                            Data Nilai
                        </a>
                    </div>
                </div>

                <!-- Menu Laporan -->
                <div class="relative" x-data="{ open: false }">
                    <button onclick="toggleDropdownLaporan()" 
                        class="flex items-center w-full px-4 py-3 rounded-lg <?= in_array($current_page, ['laporan_tugas.php', 'laporan_nilai.php']) ? 'bg-orange-100 text-[#191970]' : 'text-gray-600 hover:bg-orange-50 hover:text-[#191970]' ?>">
                        <i class="fas fa-file-alt w-6"></i>
                        <span class="ml-3 flex-1 text-left">Laporan</span>
                        <i class="fas fa-chevron-down ml-auto text-sm"></i>
                    </button>

                    <div id="dropdownLaporan" class="absolute left-0 mt-1 w-44 bg-white border rounded-lg shadow-lg hidden z-10">
                        <a href="laporan.php" 
                           class="block px-4 py-2 text-sm <?= $current_page == 'laporan.php' ? 'bg-orange-100 text-[#191970]' : 'text-gray-700 hover:bg-orange-50 hover:text-[#191970]' ?>">
                            Laporan Absensi
                        </a>
                        <a href="laporan_nilai.php" 
                           class="block px-4 py-2 text-sm <?= $current_page == 'laporan_nilai.php' ? 'bg-orange-100 text-[#191970]' : 'text-gray-700 hover:bg-orange-50 hover:text-[#191970]' ?>">
                            Laporan Nilai
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($_SESSION['role'] == 'superadmin'): ?>
                <div class="pt-4 mb-2">
                    <div class="text-xs uppercase tracking-wide font-semibold text-gray-500 px-4">Admin Menu</div>
                </div>

                <a href="guru.php" 
                   class="flex items-center px-4 py-3 rounded-lg <?= $current_page == 'guru.php' ? 'bg-orange-100 text-[#191970]' : 'text-gray-600 hover:bg-orange-50 hover:text-[#191970]' ?>">
                    <i class="fas fa-chalkboard-teacher w-6"></i>
                    <span class="ml-3">Guru</span>
                </a>

                <a href="kelas.php" 
                   class="flex items-center px-4 py-3 rounded-lg <?= $current_page == 'kelas.php' ? 'bg-orange-100 text-[#191970]' : 'text-gray-600 hover:bg-orange-50 hover:text-[#191970]' ?>">
                    <i class="fas fa-school w-6"></i>
                    <span class="ml-3">Kelas</span>
                </a>

                <a href="mapel.php" 
                   class="flex items-center px-4 py-3 rounded-lg <?= $current_page == 'mapel.php' ? 'bg-orange-100 text-[#191970]' : 'text-gray-600 hover:bg-orange-50 hover:text-[#191970]' ?>">
                    <i class="fas fa-list-alt w-6"></i>
                    <span class="ml-3">Mata Pelajaran</span>
                </a>

                <a href="siswa.php" 
                   class="flex items-center px-4 py-3 rounded-lg <?= $current_page == 'siswa.php' ? 'bg-orange-100 text-[#191970]' : 'text-gray-600 hover:bg-orange-50 hover:text-[#191970]' ?>">
                    <i class="fas fa-user-graduate w-6"></i>
                    <span class="ml-3">Siswa</span>
                </a>
            <?php endif; ?>
        </div>

        <!-- User Profile -->
        <div class="border-t border-gray-200 p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-user-circle text-2xl text-[#191970]"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-700"><?= htmlspecialchars($_SESSION['nama']) ?></p>
                    <a href="logout.php" class="text-xs text-[#191970] hover:text-[#4169e1]">
                        <i class="fas fa-sign-out-alt mr-1"></i>Keluar
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- JS Dropdown Scripts -->
<script>
function toggleDropdown() {
    document.getElementById("dropdownMenu").classList.toggle("hidden");
}

function toggleDropdownLaporan() {
    document.getElementById("dropdownLaporan").classList.toggle("hidden");
}

document.addEventListener("click", function(event) {
    const nilaiDropdown = document.getElementById("dropdownMenu");
    const laporanDropdown = document.getElementById("dropdownLaporan");
    const button = event.target.closest("button");

    if (!nilaiDropdown.contains(event.target) && !button) {
        nilaiDropdown.classList.add("hidden");
    }

    if (!laporanDropdown.contains(event.target) && !button) {
        laporanDropdown.classList.add("hidden");
    }
});
</script>
