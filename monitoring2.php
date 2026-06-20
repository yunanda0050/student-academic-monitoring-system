<?php
session_start();
ob_start();
require_once 'config/koneksi.php';
require_once 'partials/header.php';

// Hapus session saat tombol "selesai" ditekan
if (isset($_POST['selesai'])) {
    unset($_SESSION['akses_siswa']);
    header("Location: monitoring.php?tab=nilai");
    exit;
}

// Verifikasi kode akses
if (isset($_POST['verifikasi'])) {
    $id = $_POST['id_siswa'];
    $kode = $_POST['kode_akses'];

    $stmt = $pdo->prepare("SELECT * FROM siswa WHERE id = ? AND kode_akses = ?");
    $stmt->execute([$id, $kode]);

    if ($stmt->rowCount()) {
        $_SESSION['akses_siswa'] = $id;
        header("Location: monitoring.php?tab=nilai&id=$id");
        exit;
    } else {
        $pesan_error = "Kode akses salah.";
    }
}

// Set filter default
$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');
$kelas_id = isset($_GET['kelas_id']) ? $_GET['kelas_id'] : '';
$siswa_id = isset($_GET['siswa_id']) ? $_GET['siswa_id'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'absensi';
$mode = isset($_GET['mode']) && in_array($_GET['mode'], ['nilai', 'tugas']) ? $_GET['mode'] : 'nilai';
$current_mode = isset($_GET['mode']) ? $_GET['mode'] : 'nilai';
$mapel_id = isset($_GET['mapel_id']) ? $_GET['mapel_id'] : '';

// Ambil daftar kelas untuk filter
$stmt = $pdo->query("SELECT id, nama_kelas, sub_kelas FROM kelas ORDER BY nama_kelas");
$kelas_list = $stmt->fetchAll();

// Ambil daftar siswa sesuai kelas
$siswa_dari_kelas = [];
if (!empty($kelas_id)) {
    $stmt = $pdo->prepare("SELECT id, nama FROM siswa WHERE kelas_id = ?");
    $stmt->execute([$kelas_id]);
    $siswa_dari_kelas = $stmt->fetchAll();
}

// Ambil daftar siswa sesuai kelas
$mapel_dari_kelas = [];
if (!empty($kelas_id)) {
    $stmt = $pdo->prepare("SELECT DISTINCT m.id_mapel, m.mapel 
        FROM pelajaran p
        JOIN mapel m ON m.id_mapel = p.id_mapel
        WHERE p.id = ?");
    $stmt->execute([$kelas_id]);
    $mapel_dari_kelas = $stmt->fetchAll();
}

// Query untuk mendapatkan data absensi
if ($tab === 'absensi' && !empty($kelas_id)) {
    $query = "
        SELECT 
            s.id,
            s.nis,
            s.nama AS nama_siswa,
            s.jenis_kelamin,
            k.nama_kelas,
            k.sub_kelas,
            COALESCE(a.status, '') AS status,
            COALESCE(a.keterangan, '') AS keterangan,
            m.mapel,
            NULL AS absen,
            NULL AS tugas,
            NULL AS uts,
            NULL AS uas,
            NULL AS total,
            NULL AS grade
        FROM siswa s
        JOIN kelas k ON s.kelas_id = k.id
        LEFT JOIN absensi a ON s.id = a.siswa_id AND DATE(a.tanggal) = ?
        LEFT JOIN pelajaran p ON p.id = s.kelas_id
        LEFT JOIN mapel m ON m.id_mapel = p.id_mapel
        WHERE 1=1
    ";

    $params = [$tanggal];

    if (!empty($kelas_id)) {
        $query .= " AND s.kelas_id = ?";
        $params[] = $kelas_id;
    }

    if (!empty($siswa_id)) {
        $query .= " AND s.id = ?";
        $params[] = $siswa_id;
    }

    if (!empty($mapel_id)) {
    $query .= " AND m.id_mapel = ?";
    $params[] = $mapel_id;
    }

    if (!empty($search)) {
        $query .= " AND (s.nama LIKE ? OR s.nis LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $query .= " ORDER BY k.nama_kelas, s.nama";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $siswa_list = $stmt->fetchAll();
}

if ($tab === 'nilai' && !empty($kelas_id)) {
    $query = "
        SELECT 
            s.id,
            s.nis,
            s.nama AS nama_siswa,
            s.jenis_kelamin,
            k.nama_kelas,
            k.sub_kelas,
            '' AS status,
            '' AS keterangan,
            n.id_nilai,
            n.absen,
            n.tugas,
            n.uts,
            n.uas,
            n.total,
            n.grade,
            m.mapel
        FROM nilai n
        JOIN siswa s ON n.id_nilai = s.id
        JOIN kelas k ON s.kelas_id = k.id
        JOIN mapel m ON m.id_mapel = n.id_mapel
        WHERE 1=1
        GROUP BY n.id_nilai
    ";

    $params = [];

    if (!empty($kelas_id)) {
        $query .= " AND s.kelas_id = ?";
        $params[] = $kelas_id;
        // TIDAK PERLU ambil ulang siswa di sini
    }

    if (!empty($siswa_id)) {
        $query .= " AND s.id = ?";
        $params[] = $siswa_id;
    }

    if (!empty($mapel_id)) {
    $query .= " AND m.id_mapel = ?";
    $params[] = $mapel_id;
    }

    if (!empty($search)) {
        $query .= " AND (s.nama LIKE ? OR s.nis LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $query .= " ORDER BY k.nama_kelas, s.nama";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $siswa_list = $stmt->fetchAll();
}

// Hitung statistik
$total_hadir = 0;
$total_sakit = 0;
$total_izin = 0;
$total_alpha = 0;

$siswa_list = $siswa_list ?? [];

foreach ($siswa_list as $siswa) {
    switch ($siswa['status']) {
        case 'hadir': $total_hadir++; break;
        case 'sakit': $total_sakit++; break;
        case 'izin': $total_izin++; break;
        case 'alpha': $total_alpha++; break;
    }
}
?>

<div class="min-h-screen bg-gradient-to-br from-orange-50 to-white py-12">
    <div class="container mx-auto px-4">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="bg-gradient-to-r from-[#191970] to-[#191970] rounded-lg shadow-sm mb-6 p-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold text-white">Monitoring Orang Tua Siswa</h1>
                        <p class="text-orange-100">SMP Negeri 2 Sepatan</p>
                    </div>
                    <a href="index.php" class="inline-flex items-center px-4 py-2 bg-white text-[#191970] rounded-lg hover:bg-orange-50 transition duration-200">
                        <i class="fas fa-home mr-2"></i>
                        Kembali ke Beranda
                    </a>
                </div>
            </div>

<!-- Filter -->
            <div class="bg-white rounded-lg shadow-sm mb-6 p-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Kelas</label>
                        <select name="kelas_id" class="w-full rounded-lg border-gray-300 focus:ring-[#191970] focus:border-[#191970]" onchange="this.form.submit()">
                            <option value="">Semua Kelas</option>
                            <?php foreach($kelas_list as $kelas): ?>
                                <option value="<?= $kelas['id'] ?>" <?= $kelas_id == $kelas['id'] ? 'selected' : '' ?>>
                                    <?= 'Kelas ' . htmlspecialchars($kelas['nama_kelas']) . '-' . htmlspecialchars($kelas['sub_kelas']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
            
                    <!-- FILTER SISWA (BARU) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama Siswa</label>
                        <select name="siswa_id"
                                class="w-full rounded-lg border-gray-300 focus:ring-[#191970] focus:border-[#191970]"
                                onchange="this.form.submit()">
                            <option value="">
                                <?= empty($kelas_id) ? 'Pilih kelas terlebih dahulu' : '-- Semua Siswa --' ?>
                            </option>
                            <?php if (!empty($kelas_id)): ?>
                                <?php foreach ($siswa_dari_kelas as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= ($siswa_id == $s['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['nama']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- FILTER MAPEL -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mata Pelajaran</label>
                        <select name="mapel_id"
                                class="w-full rounded-lg border-gray-300 focus:ring-[#191970] focus:border-[#191970]"
                                onchange="this.form.submit()">
                            <option value="">
                                <?= empty($kelas_id) ? 'Pilih kelas terlebih dahulu' : '-- Semua Mapel --' ?>
                            </option>
                            <?php if (!empty($kelas_id)): ?>
                                <?php foreach ($mapel_dari_kelas as $m): ?>
                                    <option value="<?= $m['id_mapel'] ?>" <?= ($mapel_id == $m['id_mapel']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($m['mapel']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <!-- Tanggal hanya jika tab absensi -->
                    <?php if ($tab === 'absensi'): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Tanggal</label>
                            <input type="date" name="tanggal" value="<?= htmlspecialchars($tanggal) ?>" 
                                class="w-full rounded-lg border-gray-300 focus:ring-[#191970] focus:border-[#191970]"
                                onchange="this.form.submit()">
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        <div class="bg-white rounded-lg shadow-sm mb-6 p-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">

                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cari Siswa</label>
                        <div class="relative">
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                                   placeholder="Cari nama atau NIS..."
                                   class="w-full rounded-lg border-gray-300 focus:ring-[#191970] focus:border-[#191970] pl-10"
                                   onchange="this.form.submit()">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Statistik -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 transform hover:scale-105 transition-transform duration-200">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-orange-100">
                            <i class="fas fa-check text-[#191970] text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-gray-500 text-sm">Hadir</h3>
                            <p class="text-xl md:text-2xl font-semibold text-[#191970]"><?= $total_hadir ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 transform hover:scale-105 transition-transform duration-200">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-orange-100">
                            <i class="fas fa-hospital text-[#191970] text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-gray-500 text-sm">Sakit</h3>
                            <p class="text-xl md:text-2xl font-semibold text-[#191970]"><?= $total_sakit ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 transform hover:scale-105 transition-transform duration-200">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-orange-100">
                            <i class="fas fa-envelope text-[#191970] text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-gray-500 text-sm">Izin</h3>
                            <p class="text-xl md:text-2xl font-semibold text-[#191970]"><?= $total_izin ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 transform hover:scale-105 transition-transform duration-200">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-orange-100">
                            <i class="fas fa-times text-[#191970] text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-gray-500 text-sm">Alpha</h3>
                            <p class="text-xl md:text-2xl font-semibold text-[#191970]"><?= $total_alpha ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel Absensi -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-[#191970] to-[#191970]">
                  <?php $tab = isset($_GET['tab']) ? $_GET['tab'] : 'absensi'; ?>

<div class="mb-4 flex justify-between items-center">
    <!-- Navigasi Tab -->
    <div class="inline-flex rounded-lg shadow-sm overflow-hidden border border-orange-300 bg-white">
        <a href="?tab=absensi"
           class="px-4 py-2 text-sm font-medium <?= $tab === 'absensi' ? 'bg-[#191970] text-white' : 'text-[#191970] hover:bg-orange-100' ?>">
            Absensi
        </a>
        <a href="?tab=nilai"
           class="px-4 py-2 text-sm font-medium <?= $tab === 'nilai' ? 'bg-[#191970] text-white' : 'text-[#191970] hover:bg-orange-100' ?>">
            Nilai
        </a>
    </div>

    <!-- Tombol Kontak Wali Kelas -->
    <a href="kontak_wali_kelas.php"
       class="inline-block px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg shadow hover:bg-blue-700 transition duration-200">
        <i class="fas fa-phone-alt mr-1"></i> Kontak Wali Kelas
    </a>
</div>


<?php if ($tab === 'absensi'): ?>
    <p class="text-sm text-orange-100">Tanggal: <?= date('d F Y', strtotime($tanggal)) ?></p>
<?php endif; ?>
</div>
                
<!-- Panduan Scroll Mobile -->
<div class="md:hidden bg-blue-50 p-4 flex items-center justify-center text-blue-600 border-b">
    <i class="fas fa-hand-point-right animate-bounce mr-2"></i>
    <span class="text-sm">Geser ke kanan untuk melihat data lengkap</span>
    <i class="fas fa-hand-point-left animate-bounce ml-2"></i>
</div>

<?php if (empty($kelas_id)): ?>
    <!-- Jika kelas belum dipilih -->
    <div class="text-center py-8">
        <i class="fas fa-filter text-orange-400 text-4xl mb-3"></i>
        <p class="text-gray-600 text-lg">
            Silakan pilih <strong>kelas</strong> terlebih dahulu untuk menampilkan data siswa.
        </p>
    </div>

<?php elseif (empty($siswa_list)): ?>
    <!-- Kelas sudah dipilih tapi tidak ada data -->
    <div class="text-center py-8">
        <i class="fas fa-info-circle text-gray-400 text-4xl mb-3"></i>
        <p class="text-gray-500">Tidak ada data untuk ditampilkan</p>
    </div>
    
<?php else: ?>
    <div class="overflow-x-auto">
        <!-- Indikator Scroll -->
        <div class="md:hidden absolute right-0 top-1/2 transform -translate-y-1/2 bg-[#191970] text-white p-2 rounded-l-lg shadow-lg opacity-50">
            <i class="fas fa-arrows-left-right"></i>
        </div>

                        <?php if ($tab === 'absensi'): ?>
    <div class="p-6">
        <h2 class="text-lg font-semibold text-[#191970] mb-4">Data Absensi Siswa</h2>
        
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white shadow-md rounded-lg overflow-hidden">
                <thead class="bg-orange-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-[#191970] uppercase tracking-wider">No</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-[#191970] uppercase tracking-wider">Kelas</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-[#191970] uppercase tracking-wider">Mata Pelajaran</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-[#191970] uppercase tracking-wider">Nama Siswa</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-[#191970] uppercase tracking-wider">JK</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-[#191970] uppercase tracking-wider">Status</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-[#191970] uppercase tracking-wider">Keterangan</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php $no = 1; foreach($siswa_list as $siswa): ?>
                                            <tr class="hover:bg-orange-50 transition-colors duration-200">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    <div class="flex items-center">
                                                        <div class="ml-4">
                                                            <div class="text-sm font-medium text-gray-900"><?= $no++ ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    <?= 'Kelas ' . htmlspecialchars($siswa['nama_kelas']) . '-' . htmlspecialchars($siswa['sub_kelas']) ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    <?= htmlspecialchars($siswa['mapel']) ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    <?= htmlspecialchars($siswa['nama_siswa']) ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    <?= $siswa['jenis_kelamin'] ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-3 py-1 text-xs rounded-full font-medium
                                                        <?php
                                                        switch($siswa['status']) {
                                                            case 'hadir':
                                                                echo 'bg-green-100 text-green-800';
                                                                break;
                                                            case 'sakit':
                                                                echo 'bg-yellow-100 text-yellow-800';
                                                                break;
                                                            case 'izin':
                                                                echo 'bg-blue-100 text-blue-800';
                                                                break;
                                                            case 'alpha':
                                                                echo 'bg-red-100 text-red-800';
                                                                break;
                                                            default:
                                                                echo 'bg-gray-100 text-gray-800';
                                                        }
                                                        ?>">
                                                        <?= $siswa['status'] ? ucfirst($siswa['status']) : 'Belum Absen' ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?= $siswa['keterangan'] ? htmlspecialchars($siswa['keterangan']) : '-' ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
            </table>
        </div>
    </div>

<?php elseif ($tab === 'nilai'): ?>
    <div class="p-6">
        <h2 class="text-lg font-semibold text-[#191970] mb-4">Data Nilai Siswa</h2>

        <?php if (isset($pesan_error)): ?>
            <p class="text-red-500"><?= $pesan_error ?></p>
        <?php endif; ?>

        <?php if (isset($_GET['id'])): ?>
            <?php $id_siswa = $_GET['id']; ?>

            <?php if (!isset($_SESSION['akses_siswa']) || $_SESSION['akses_siswa'] != $id_siswa): ?>
                <!-- FORM KODE AKSES -->
                <form method="POST" class="mb-4">
                    <input type="hidden" name="id_siswa" value="<?= $id_siswa ?>">
                    <input type="password" name="kode_akses" placeholder="Masukkan Kode Akses" required>
                    <button type="submit" name="verifikasi" class="bg-[#191970] text-white px-4 py-2 rounded">
                        Lihat Nilai
                    </button>
                </form>

            <?php else: ?>
                <!-- QUERY DATA NILAI -->
<?php
$id_siswa = $_GET['id'];
$current_mode = isset($_GET['mode']) ? $_GET['mode'] : 'nilai';

if ($current_mode === 'nilai') {
    // Mode NILAI: Ambil nilai final (group by mapel)
    $stmt = $pdo->prepare("
        SELECT 
            s.id,
            s.nis,
            s.nama AS nama_siswa,
            s.jenis_kelamin,
            k.nama_kelas,
            k.sub_kelas,
            n.id_nilai,
            n.absen,
            n.tugas,
            n.uts,
            n.uas,
            n.total,
            n.grade,
            m.mapel
        FROM nilai n
        JOIN siswa s ON n.id_nilai = s.id
        JOIN kelas k ON s.kelas_id = k.id
        JOIN mapel m ON m.id_mapel = n.id_mapel
        WHERE s.id = ?
        GROUP BY m.id_mapel
        ORDER BY m.mapel ASC
    ");
} else {
    // Mode TUGAS: Tampilkan semua entri tugas_detail per siswa (tanpa group)
    $stmt = $pdo->prepare("
SELECT 
    s.id,
    s.nis,
    s.nama AS nama_siswa,
    s.jenis_kelamin,
    k.nama_kelas,
    k.sub_kelas,
    m.mapel,
    t.jenis,
    t.ke,
    t.nilai
FROM tugas_detail t
JOIN siswa s ON t.siswa_id = s.id
JOIN kelas k ON s.kelas_id = k.id
JOIN mapel m ON m.id_mapel = t.id_mapel
WHERE s.id = ?
ORDER BY m.mapel ASC, t.jenis ASC, t.ke ASC

    ");
}

$stmt->execute([$id_siswa]);
$nilai_list = $stmt->fetchAll();
?>

                <?php if ($nilai_list): ?>
                    <!-- TABEL NILAI -->
                    <table class="min-w-full bg-white shadow-md rounded-lg overflow-hidden mb-4">
                        <!-- TOMBOL PILIH MODE: NILAI / TUGAS -->
                        <div class="mt-4 flex gap-2">
                            <?php $current_mode = isset($_GET['mode']) ? $_GET['mode'] : 'nilai'; ?>
                            
                            <a href="monitoring.php?tab=nilai&id=<?= $id_siswa ?>&mode=nilai"
                            class="px-4 py-2 rounded text-sm font-semibold 
                            <?= $current_mode === 'nilai' ? 'bg-[#191970] text-white' : 'bg-gray-200 text-gray-800 hover:bg-gray-300' ?>">
                                Tampilkan Nilai
                            </a>

                            <a href="monitoring.php?tab=nilai&id=<?= $id_siswa ?>&mode=tugas"
                            class="px-4 py-2 rounded text-sm font-semibold 
                            <?= $current_mode === 'tugas' ? 'bg-[#191970] text-white' : 'bg-gray-200 text-gray-800 hover:bg-gray-300' ?>">
                                Tampilkan Tugas
                            </a>
                        </div><br>

<thead class="bg-orange-50">
    <tr>
        <th class="px-6 py-3 text-left text-xs font-medium text-[#191970] uppercase">No</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-[#191970] uppercase">Kelas</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-[#191970] uppercase">Mata Pelajaran</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-[#191970] uppercase">NIS</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-[#191970] uppercase">Nama Siswa</th>
<?php if ($current_mode === 'tugas'): ?>
<th class="px-6 py-3 text-left text-xs font-medium text-[#191970] uppercase">Tugas</th>
<th class="px-6 py-3 text-left text-xs font-medium text-[#191970] uppercase">Nilai UH</th>
<?php endif; ?>

        <?php if ($current_mode === 'nilai'): ?>
        <th class="px-6 py-3 text-left text-xs font-medium text-[#191970] uppercase">Absen</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-[#191970] uppercase">Tugas</th>    
            <th class="px-6 py-3 text-left text-xs font-medium text-[#191970] uppercase">UTS</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-[#191970] uppercase">UAS</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-[#191970] uppercase">Total</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-[#191970] uppercase">Grade</th>
        <?php endif; ?>
    </tr>
</thead>

                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php $no = 1; foreach ($nilai_list as $siswa): ?>
                                <tr class="hover:bg-orange-50 transition-colors duration-200">
                                    <td class="px-6 py-4 text-sm text-gray-900"><?= $no++ ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?= 'Kelas ' . htmlspecialchars($siswa['nama_kelas']) . '-' . htmlspecialchars($siswa['sub_kelas']) ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($siswa['mapel']) ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($siswa['nis']) ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($siswa['nama_siswa']) ?></td>
<?php if ($current_mode === 'tugas'): ?>
<!-- Kolom Tugas -->
<td class="px-6 py-4 text-sm text-gray-900">
    <div class="flex items-center gap-2">
        <?php if ($siswa['jenis'] == 'ntugas'): ?>
            <input type="number" value="<?= htmlspecialchars($siswa['nilai']) ?>" class="w-20 text-center border rounded bg-yellow-100" readonly>
            <span class="text-sm font-semibold">TUGAS ke-<?= htmlspecialchars($siswa['ke']) ?></span>
        <?php else: ?>
            <span class="text-sm text-gray-400 italic">-</span>
        <?php endif; ?>
    </div>
</td>

<!-- Kolom Nilai UH -->
<td class="px-6 py-4 text-sm text-gray-900">
    <div class="flex items-center gap-2">
        <?php if ($siswa['jenis'] == 'uh'): ?>
            <input type="number" value="<?= htmlspecialchars($siswa['nilai']) ?>" class="w-20 text-center border rounded bg-yellow-100" readonly>
            <span class="text-sm font-semibold">NILAI UH ke-<?= htmlspecialchars($siswa['ke']) ?></span>
        <?php else: ?>
            <span class="text-sm text-gray-400 italic">-</span>
        <?php endif; ?>
    </div>
</td>
<?php endif; ?>

                                    <?php if ($current_mode === 'nilai'): ?>
                                    <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($siswa['absen']) ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($siswa['tugas']) ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($siswa['uts']) ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($siswa['uas']) ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($siswa['total']) ?></td>
                                        <td class="px-6 py-4 text-sm font-bold text-[#191970]"><?= htmlspecialchars($siswa['grade']) ?></td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-gray-500">Belum ada data nilai untuk siswa ini.</p>
                <?php endif; ?>

                <!-- TOMBOL SELESAI -->
                <form method="POST">
                    <button type="submit" name="selesai" class="bg-red-500 text-white px-4 py-2 rounded">
                        Selesai Melihat
                    </button>
                </form>
            <?php endif; ?>

        <?php else: ?>
            <!-- DAFTAR SISWA -->
            <p class="text-gray-600 mb-4">Silakan pilih siswa untuk melihat detail nilai:</p>
            <table class="min-w-full bg-white shadow-md rounded-lg overflow-hidden">
                <thead class="bg-orange-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-[#191970] uppercase">Nama</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-[#191970] uppercase">Kelas</th>
                        
                        <th class="px-6 py-3 text-left text-xs font-medium text-[#191970] uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($siswa_list as $siswa): ?>
                        <tr class="hover:bg-orange-50">
                            <td class="px-6 py-4"><?= htmlspecialchars($siswa['nama_siswa']) ?></td>
                            <td class="px-6 py-4">
                                <?= 'Kelas ' . htmlspecialchars($siswa['nama_kelas']) . '-' . htmlspecialchars($siswa['sub_kelas']) ?>
                            </td>
                            
                            <td class="px-6 py-4 space-x-2">
                                <a href="monitoring.php?tab=nilai&id=<?= $siswa['id'] ?>&mode=<?= isset($_GET['mode']) ? $_GET['mode'] : 'nilai' ?>"
                                   class="inline-block px-4 py-2 bg-[#191970] text-white rounded hover:bg-[#191970] transition duration-200">
                                    Lihat Nilai
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php endif; ?>

    </div>
<?php endif; ?>
<style>
/* Responsif table styles */
@media (max-width: 768px) {
    .overflow-x-auto {
        -webkit-overflow-scrolling: touch;
        position: relative; /* Untuk positioning indikator scroll */
    }
    
    table {
        display: block;
        width: 100%;
        overflow-x: auto;
    }
    
    th, td {
        min-width: 120px;
    }
    
    /* Membuat kolom nomor dan status lebih kecil */
    th:first-child, td:first-child,
    th:nth-child(5), td:nth-child(5) {
        min-width: 60px;
        position: sticky;
        left: 0;
        background: white;
        z-index: 1;
    }
    
    /* Membuat kolom status sedikit lebih lebar */
    th:nth-child(6), td:nth-child(6) {
        min-width: 100px;
    }
    
    /* Membuat kolom keterangan lebih lebar */
    th:last-child, td:last-child {
        min-width: 150px;
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

/* Animasi hover untuk baris tabel */
tr {
    transition: all 0.2s ease-in-out;
}

tr:hover {
    transform: translateX(5px);
}

/* Style untuk status badges */
.status-badge {
    transition: all 0.3s ease;
}

.status-badge:hover {
    transform: scale(1.1);
}
</style>
<script>
window.addEventListener("popstate", function () {
    // Hapus session kode akses
    fetch("hapus_session.php", { method: "POST" }).then(() => {
        // Ganti state history biar user nggak bisa klik forward lagi
        window.history.pushState(null, "", "monitoring.php?tab=nilai");

        // Paksa redirect ulang agar data fresh tanpa session
        window.location.href = "monitoring.php?tab=nilai";
    });
});
</script>




<?php require_once 'partials/footer.php'; ?> 