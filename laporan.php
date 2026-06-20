<?php
session_start();
require_once 'config/koneksi.php';
require_once 'partials/header.php';
require_once 'partials/navbar.php';

// Cek apakah user sudah login
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Ambil parameter filter dari GET
$kelas_id = isset($_GET['kelas_id']) ? $_GET['kelas_id'] : '';
$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-d');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d');
$mapel_id = $_GET['mapel_id'] ?? '';
$id_siswa = $_GET['id_siswa'] ?? '';
$tahun_id = isset($_GET['tahun_id']) ? $_GET['tahun_id'] : '';

// Ambil daftar kelas untuk filter
$stmt = $pdo->query("SELECT id, nama_kelas, sub_kelas FROM kelas ORDER BY nama_kelas");
$kelas_list = $stmt->fetchAll();

$mapel_list = [];
if (!empty($kelas_id)) {
    $stmt_mapel = $pdo->prepare("
        SELECT DISTINCT m.id_mapel, m.mapel 
        FROM pelajaran p 
        JOIN mapel m ON p.id_mapel = m.id_mapel 
        WHERE p.id = ?
        ORDER BY m.mapel
    ");
    $stmt_mapel->execute([$kelas_id]);
    $mapel_list = $stmt_mapel->fetchAll();
}

$siswa_list = [];
if (!empty($kelas_id)) {
    $stmt_siswa = $pdo->prepare("
        SELECT id, nama, nis 
        FROM siswa 
        WHERE kelas_id = ?
        ORDER BY nama
    ");
    $stmt_siswa->execute([$kelas_id]);
    $siswa_list = $stmt_siswa->fetchAll();
}

// Query untuk mendapatkan data absensi berdasarkan filter
$where_conditions = [];
$params = [];

if(!empty($kelas_id)) {
    $where_conditions[] = "s.kelas_id = ?";
    $params[] = $kelas_id;
}

$mapel_id = $_GET['mapel_id'] ?? '';
$id_siswa = $_GET['id_siswa'] ?? '';
if (!empty($mapel_id)) {
    $where_conditions[] = "p.id_mapel = ?";
    $params[] = $mapel_id;
}

$where_conditions[] = "DATE(a.tanggal) BETWEEN ? AND ?";
$params[] = $tanggal_awal;
$params[] = $tanggal_akhir;


if (!empty($id_siswa)) {
    $where_conditions[] = "s.id = ?";
    $params[] = $id_siswa;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

$query = "
    SELECT 
        s.id AS id_siswa,
        k.nama_kelas,
        k.sub_kelas,
        s.nis,
        s.nama as nama_siswa,
        DATE(a.tanggal) as tanggal,
        a.status,
        a.keterangan,
        g_created.nama AS guru_input,
        g_updated.nama AS guru_update,
        a.created_at,
        a.updated_at,
        m.mapel,
        t.tahun AS tahun_ajaran
    FROM siswa s
    JOIN kelas k ON s.kelas_id = k.id
    JOIN tahun_ajaran t ON s.id_tahun = t.id_tahun
    LEFT JOIN absensi a ON s.id = a.siswa_id
    LEFT JOIN guru g_created ON a.created_by = g_created.id
    LEFT JOIN guru g_updated ON a.updated_by = g_updated.id
    LEFT JOIN pelajaran p ON p.id = k.id
    LEFT JOIN mapel m ON m.id_mapel = p.id_mapel
    $where_clause
    ORDER BY k.nama_kelas, s.nama, a.tanggal
";

$siswa_list = [];
if (!empty($kelas_id)) {
    $stmt_siswa = $pdo->prepare("
        SELECT id, nama 
        FROM siswa 
        WHERE kelas_id = ?
        ORDER BY nama ASC
    ");
    $stmt_siswa->execute([$kelas_id]);
    $siswa_list = $stmt_siswa->fetchAll();
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$data_absensi = $stmt->fetchAll();
?>

<!-- Main Content -->
<div class="md:pl-64 pb-safe">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-sm mb-6">
            <div class="p-6">
                <h1 class="text-2xl font-bold text-gray-800 mb-4">Laporan Absensi</h1>
                
                <!-- Filter Form -->
                <form method="GET" id="filterForm" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                     <!-- Filter Tanggal Awal -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Awal</label>
                        <input type="date" name="tanggal_awal" value="<?= htmlspecialchars($tanggal_awal) ?>"
                            onchange="this.form.submit()"
                            class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <!-- Filter Tanggal Akhir -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Akhir</label>
                        <input type="date" name="tanggal_akhir" value="<?= htmlspecialchars($tanggal_akhir) ?>"
                            onchange="this.form.submit()"
                            class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <!-- Filter Kelas -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Kelas</label>
                        <select name="kelas_id"
                                <?= (empty($tanggal_awal) || empty($tanggal_akhir)) ? 'disabled class="bg-gray-100 text-gray-500 w-full rounded-lg border-gray-300"' : 'class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500"' ?>
                                onchange="this.form.submit()">
                            <option value="">-- Pilih Kelas --</option>
                            <?php foreach ($kelas_list as $kelas): ?>
                                <option value="<?= $kelas['id'] ?>" <?= $kelas_id == $kelas['id'] ? 'selected' : '' ?>>
                                    <?= 'Kelas ' . htmlspecialchars($kelas['nama_kelas']) . '-' . htmlspecialchars($kelas['sub_kelas']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Mata Pelajaran</label>
                    <select name="mapel_id" <?= empty($kelas_id) ? 'disabled' : '' ?>
                        onchange="this.form.submit()"
                        class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                        <?php if (empty($kelas_id)): ?>
                            <option value="">Pilih kelas terlebih dahulu</option>
                        <?php else: ?>
                            <option value="">Semua Mapel</option>
                            <?php foreach ($mapel_list as $mapel): ?>
                                <option value="<?= $mapel['id_mapel'] ?>" <?= $mapel_id == $mapel['id_mapel'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($mapel['mapel']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="md:col-span-4 flex flex-col md:flex-row items-end gap-4">
                    <!-- Dropdown Siswa -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Siswa</label>
                        <select name="id_siswa" <?= empty($kelas_id) ? 'disabled' : '' ?>
                        onchange="this.form.submit()"
                        class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                        <?php if (empty($kelas_id)): ?>
                            <option value="">Pilih kelas terlebih dahulu</option>
                        <?php else: ?>
                                <option value="">Semua Siswa</option>
                                <?php foreach ($siswa_list as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= (isset($_GET['id_siswa']) && $_GET['id_siswa'] == $s['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['nama']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                        <!-- Tombol Export -->
                        <?php if(!empty($data_absensi)): ?>
                        <div class="ml-auto">
                            <a href="export_absensi.php?kelas_id=<?= htmlspecialchars($kelas_id) ?>&mapel_id=<?= htmlspecialchars($mapel_id) ?>&id_siswa=<?= urlencode($id_siswa) ?>&tanggal_awal=<?= htmlspecialchars($tanggal_awal) ?>&tanggal_akhir=<?= htmlspecialchars($tanggal_akhir) ?>" 
                            class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition duration-200 text-center block whitespace-nowrap">
                                <i class="fas fa-file-excel mr-2"></i>Export Excel
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                </form>
                <?php
                $filter_lengkap = !empty($tanggal_awal) && !empty($tanggal_akhir) && !empty($kelas_id);
                $ada_data = !empty($data_absensi);
                ?>
                <?php if(empty($data_absensi)): ?>
                    <?php if (empty($_GET['tanggal_awal']) || empty($_GET['tanggal_akhir'])): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-calendar-alt text-gray-400 text-4xl mb-3"></i>
                            <p class="text-gray-500">Silakan pilih tanggal awal dan akhir terlebih dahulu untuk menampilkan data absensi.</p>
                        </div>
                    <?php elseif (empty($kelas_id)): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-school text-gray-400 text-4xl mb-3"></i>
                            <p class="text-gray-500">Setelah memilih tanggal, silakan pilih kelas untuk menampilkan data.</p>
                        </div>
                    <?php endif; ?>
                    <div class="text-center py-8">
                        <i class="fas fa-info-circle text-gray-400 text-4xl mb-3"></i>
                        <p class="text-gray-500">Tidak ada data absensi untuk filter yang dipilih</p>
                    </div>
                <?php else: ?>
                    <!-- Tabel Data -->
                    <div class="overflow-auto max-w-full">
                        <?php if ($filter_lengkap && $ada_data): ?>
                        <table class="table-auto w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kelas</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mata Pelajaran</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">NIS</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tahun Ajaran</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Keterangan</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Diinput Oleh</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Waktu Input</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Diupdate Oleh</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Update Terakhir</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php 
                                $no = 1;
                                foreach($data_absensi as $data): 
                                    // Ambil riwayat pengisian absensi (history tracking)
                                    $history_stmt = $pdo->prepare("
                                        SELECT a.created_at, a.updated_at 
                                        FROM absensi a 
                                        LEFT JOIN guru g_created ON a.created_by = g_created.id
                                        LEFT JOIN guru g_updated ON a.updated_by = g_updated.id 
                                        WHERE a.siswa_id = ? AND DATE(a.tanggal) = ?
                                        LIMIT 1
                                    ");
                                    $history_stmt->execute([$data['id_siswa'], $data['tanggal']]);
                                    $history = $history_stmt->fetch(PDO::FETCH_ASSOC);
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td data-label="No" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $no++ ?></td>
                                    <td data-label="Tanggal" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= date('d/m/Y', strtotime($data['tanggal'])) ?>
                                    </td>
                                    <td data-label="Kelas" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= 'Kelas ' . htmlspecialchars($data['nama_kelas']) . '-' . htmlspecialchars($data['sub_kelas']) ?>
                                    </td>
                                    <td data-label="Mapel" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= htmlspecialchars($data['mapel']) ?>
                                    </td>

                                    <td data-label="NIS" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= htmlspecialchars($data['nis']) ?>
                                    </td>
                                    <td data-label="Nama" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= htmlspecialchars($data['nama_siswa']) ?>
                                    </td>
                                    <!-- Status -->
                                    <td data-label="Status" class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs rounded-full 
                                            <?php
                                            switch($data['status']) {
                                                case 'hadir':
                                                    echo 'bg-green-100 text-green-800';
                                                    break;
                                                case 'sakit':
                                                    echo 'bg-yellow-100 text-yellow-800';
                                                    break;
                                                case 'izin':
                                                    echo 'bg-blue-100 text-blue-800';
                                                    break;
                                                default:
                                                    echo 'bg-red-100 text-red-800';
                                            }
                                            ?>">
                                            <?= ucfirst($data['status']) ?>
                                        </span>
                                    </td>
                                    <!-- Tahun Ajaran -->
                                    <td data-label="Tahun Ajaran" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= $data['tahun_ajaran'] ? htmlspecialchars($data['tahun_ajaran']) : '-' ?>
                                    </td>
                                    <!-- Keterangan -->
                                    <td data-label="Keterangan" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= $data['keterangan'] ? nl2br(htmlspecialchars($data['keterangan'])) : '-' ?>
                                    </td>
                                    <?php if ($history): ?>
                                        <td data-label="Kelas" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= $data['guru_input'] ?? '-' ?>
                                        </td>
                                        <td data-label="Kelas" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= $data['created_at'] ? date('d/m/Y H:i', strtotime($data['created_at'])) : '-' ?>
                                        </td>
                                        <td data-label="Kelas" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= $data['guru_update'] && $data['guru_update'] !== $data['guru_input'] ? htmlspecialchars($data['guru_update']) : '-' ?>
                                        </td>
                                        <td data-label="Kelas" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= $data['updated_at'] ? date('d/m/Y H:i', strtotime($data['updated_at'])) : '-' ?>
                                        </td>
                                    <?php else: ?>
                                        <td colspan="3" class="px-6 py-4 whitespace-nowrap text-xs text-gray-400">
                                            <em>Belum ada data riwayat</em>
                                        </td>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tr>
                                <script>
                                document.querySelector('select[name="kelas_id"]').addEventListener('change', function () {
                                    // Reset dropdown mapel dan siswa
                                    const mapelSelect = document.querySelector('select[name="mapel_id"]');
                                    const siswaSelect = document.querySelector('select[name="id_siswa"]');

                                    if (mapelSelect) mapelSelect.selectedIndex = 0;
                                    if (siswaSelect) siswaSelect.selectedIndex = 0;

                                    // Submit form
                                    this.form.submit();
                                });
                                </script>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
                        <?php if (!$filter_lengkap): ?>
                    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4">
                        <p class="font-medium">Silakan pilih Tanggal dan Kelas terlebih dahulu.</p>
                    </div>
                <?php elseif ($filter_lengkap && !$ada_data): ?>
                    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4">
                        <p class="font-medium">Data siswa untuk kombinasi yang dipilih belum tersedia.</p>
                    </div>
                <?php endif; ?>
    </div>
</div>

<?php require_once 'partials/footer.php'; ?> 