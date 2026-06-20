<?php
session_start();
require_once 'config/koneksi.php';
require_once 'partials/header.php';

// Cek apakah user sudah login
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Include navbar
require_once 'partials/navbar.php';

// Function untuk mendapatkan kelas warna berdasarkan status
function getStatusClass($status) {
    switch($status) {
        case 'hadir': return 'status-hadir text-white';
        case 'sakit': return 'status-sakit text-white';
        case 'izin': return 'status-izin text-white';
        case 'alpha': return 'status-alpha text-white';
        default: return 'bg-gray-500 text-white';
    }
}

// Set tanggal default ke hari ini jika tidak ada filter
$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');

// Query untuk mendapatkan ringkasan absensi per kelas untuk tanggal tertentu
$query_summary = "
    SELECT 
        k.nama_kelas, k.sub_kelas,
        COUNT(DISTINCT s.id) as total_siswa,
        COUNT(CASE WHEN a.tanggal = ? AND a.status = 'hadir' THEN 1 END) as hadir,
        COUNT(CASE WHEN a.tanggal = ? AND a.status = 'sakit' THEN 1 END) as sakit,
        COUNT(CASE WHEN a.tanggal = ? AND a.status = 'izin' THEN 1 END) as izin,
        COUNT(CASE WHEN a.tanggal = ? AND a.status = 'alpha' THEN 1 END) as alpha
    FROM kelas k
    LEFT JOIN siswa s ON k.id = s.kelas_id
    LEFT JOIN absensi a ON s.id = a.siswa_id AND DATE(a.tanggal) = ?
    GROUP BY k.id, k.nama_kelas
    ORDER BY k.nama_kelas
";
$stmt = $pdo->prepare($query_summary);
$stmt->execute([$tanggal, $tanggal, $tanggal, $tanggal, $tanggal]);
$absensi_summary = $stmt->fetchAll();

// Query untuk mendapatkan total keseluruhan
$query_total = "
    SELECT 
        COUNT(DISTINCT s.id) as total_siswa,
        COUNT(CASE WHEN a.tanggal = ? AND a.status = 'hadir' THEN 1 END) as hadir,
        COUNT(CASE WHEN a.tanggal = ? AND a.status = 'sakit' THEN 1 END) as sakit,
        COUNT(CASE WHEN a.tanggal = ? AND a.status = 'izin' THEN 1 END) as izin,
        COUNT(CASE WHEN a.tanggal = ? AND a.status = 'alpha' THEN 1 END) as alpha
    FROM siswa s
    LEFT JOIN absensi a ON s.id = a.siswa_id AND DATE(a.tanggal) = ?
";
$stmt = $pdo->prepare($query_total);
$stmt->execute([$tanggal, $tanggal, $tanggal, $tanggal, $tanggal]);
$total_summary = $stmt->fetch();
?>

<!-- Main Content -->
<div class="md:pl-64">
    <div class="container mx-auto px-4 py-8 pb-24">
        <!-- Header dengan Filter Tanggal -->
        <div class="bg-white rounded-lg shadow-sm mb-6">
            <div class="flex flex-col p-4">
                <div class="mb-4">
                    <h1 class="text-2xl font-bold text-gray-800">Dashboard Absensi</h1>
                    <p class="text-gray-600">Ringkasan absensi tanggal: <?= date('d F Y', strtotime($tanggal)) ?></p>
                </div>
                <div class="flex flex-col sm:flex-row items-center gap-4">
                    <div class="w-full sm:w-auto">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Tanggal</label>
                        <div class="relative">
                            <input type="date" 
                                   id="tanggal" 
                                   name="tanggal" 
                                   value="<?= htmlspecialchars($tanggal) ?>"
                                   class="block w-full px-4 py-2 text-base border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#191970] focus:border-transparent"
                                   onchange="window.location.href='?tanggal=' + this.value">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistik Cards -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
            <!-- Total Siswa -->
            <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 transform hover:scale-105 transition-all duration-200">
                <div class="flex items-center">
                    <div class="p-2 md:p-3 rounded-full bg-orange-100">
                        <i class="fas fa-users text-[#191970] text-lg md:text-xl"></i>
                    </div>
                    <div class="ml-3 md:ml-4">
                        <h3 class="text-gray-500 text-xs md:text-sm">Total Siswa</h3>
                        <p class="text-xl md:text-2xl font-semibold text-[#191970]"><?= $total_summary['total_siswa'] ?></p>
                    </div>
                </div>
            </div>

            <!-- Hadir -->
            <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 transform hover:scale-105 transition-all duration-200">
                <div class="flex items-center">
                    <div class="p-2 md:p-3 rounded-full bg-green-100">
                        <i class="fas fa-check text-green-600 text-lg md:text-xl"></i>
                    </div>
                    <div class="ml-3 md:ml-4">
                        <h3 class="text-gray-500 text-xs md:text-sm">Hadir</h3>
                        <p class="text-xl md:text-2xl font-semibold text-green-600"><?= $total_summary['hadir'] ?></p>
                    </div>
                </div>
            </div>

            <!-- Sakit -->
            <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 transform hover:scale-105 transition-all duration-200">
                <div class="flex items-center">
                    <div class="p-2 md:p-3 rounded-full bg-yellow-100">
                        <i class="fas fa-hospital text-yellow-600 text-lg md:text-xl"></i>
                    </div>
                    <div class="ml-3 md:ml-4">
                        <h3 class="text-gray-500 text-xs md:text-sm">Sakit</h3>
                        <p class="text-xl md:text-2xl font-semibold text-yellow-600"><?= $total_summary['sakit'] ?></p>
                    </div>
                </div>
            </div>

            <!-- Izin -->
            <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 transform hover:scale-105 transition-all duration-200">
                <div class="flex items-center">
                    <div class="p-2 md:p-3 rounded-full bg-blue-100">
                        <i class="fas fa-envelope text-blue-600 text-lg md:text-xl"></i>
                    </div>
                    <div class="ml-3 md:ml-4">
                        <h3 class="text-gray-500 text-xs md:text-sm">Izin</h3>
                        <p class="text-xl md:text-2xl font-semibold text-blue-600"><?= $total_summary['izin'] ?></p>
                    </div>
                </div>
            </div>

            <!-- Alpha -->
            <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 transform hover:scale-105 transition-all duration-200">
                <div class="flex items-center">
                    <div class="p-2 md:p-3 rounded-full bg-red-100">
                        <i class="fas fa-times text-red-600 text-lg md:text-xl"></i>
                    </div>
                    <div class="ml-3 md:ml-4">
                        <h3 class="text-gray-500 text-xs md:text-sm">Alpha</h3>
                        <p class="text-xl md:text-2xl font-semibold text-red-600"><?= $total_summary['alpha'] ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Rekap -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="px-4 md:px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-[#191970] to-[#191970]">
                <h2 class="text-lg font-semibold text-white">Rekap Absensi per Kelas</h2>
            </div>
            
            <!-- Panduan Scroll Mobile -->
            <div class="md:hidden bg-blue-50 p-4 flex items-center justify-center text-blue-600 border-b">
                <i class="fas fa-hand-point-right animate-bounce mr-2"></i>
                <span class="text-sm">Geser ke kanan untuk melihat data lengkap</span>
                <i class="fas fa-hand-point-left animate-bounce ml-2"></i>
            </div>

            <div class="overflow-x-auto relative">
                <!-- Indikator Scroll -->
                <div class="md:hidden absolute right-0 top-1/2 transform -translate-y-1/2 bg-[#191970] text-white p-2 rounded-l-lg shadow-lg opacity-50">
                    <i class="fas fa-arrows-left-right"></i>
                </div>

                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-orange-50">
                        <tr>
                            <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-[#191970] uppercase tracking-wider sticky left-0 bg-orange-50">Kelas</th>
                            <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-[#191970] uppercase tracking-wider">Total Siswa</th>
                            <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-[#191970] uppercase tracking-wider">Hadir</th>
                            <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-[#191970] uppercase tracking-wider">Sakit</th>
                            <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-[#191970] uppercase tracking-wider">Izin</th>
                            <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-[#191970] uppercase tracking-wider">Alpha</th>
                            <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-[#191970] uppercase tracking-wider">Persentase</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach($absensi_summary as $summary): ?>
                            <tr class="hover:bg-orange-50 transition-colors duration-200">
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 sticky left-0 bg-white">
                                    <?= 'Kelas ' . htmlspecialchars($summary['nama_kelas']) . '-' . htmlspecialchars($summary['sub_kelas']) ?>
                                </td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= $summary['total_siswa'] ?>
                                </td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-green-100 text-green-800">
                                        <?= $summary['hadir'] ?>
                                    </span>
                                </td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-yellow-100 text-yellow-800">
                                        <?= $summary['sakit'] ?>
                                    </span>
                                </td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 text-blue-800">
                                        <?= $summary['izin'] ?>
                                    </span>
                                </td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-red-100 text-red-800">
                                        <?= $summary['alpha'] ?>
                                    </span>
                                </td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php
                                    $persentase = $summary['total_siswa'] > 0 
                                        ? round(($summary['hadir'] / $summary['total_siswa']) * 100, 1) 
                                        : 0;
                                    ?>
                                    <div class="flex items-center">
                                        <div class="w-24 md:w-32 bg-gray-200 rounded-full h-2">
                                            <div class="bg-[#191970] h-2 rounded-full" style="width: <?= $persentase ?>%"></div>
                                        </div>
                                        <span class="ml-2 min-w-[40px]"><?= $persentase ?>%</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
/* Animasi hover untuk cards */
.transform:hover {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

/* Transisi smooth untuk semua elemen */
* {
    transition: all 0.2s ease-in-out;
}

/* Style untuk status badges */
.status-badge {
    transition: all 0.3s ease;
}

.status-badge:hover {
    transform: scale(1.1);
}

/* Responsif table styles */
@media (max-width: 768px) {
    .overflow-x-auto {
        -webkit-overflow-scrolling: touch;
        position: relative;
    }
    
    table {
        display: block;
        width: 100%;
        overflow-x: auto;
    }

    /* Tambah margin bottom untuk konten terakhir */
    .container {
        margin-bottom: 5rem;
    }

    /* Animasi untuk panduan scroll */
    @keyframes slideRight {
        0% { transform: translateX(0); }
        50% { transform: translateX(10px); }
        100% { transform: translateX(0); }
    }

    .animate-bounce {
        animation: bounce 1s infinite;
    }

    @keyframes bounce {
        0%, 100% { transform: translateY(-25%); }
        50% { transform: translateY(0); }
    }
}
</style>

<?php require_once 'partials/footer.php'; ?> 