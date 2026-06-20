
<?php
session_start();
require_once 'config/koneksi.php';
require_once 'partials/header.php';
require_once 'partials/navbar.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Ambil parameter filter
$kelas_id  = $_GET['kelas_id'] ?? '';
$mapel_id  = $_GET['mapel_id'] ?? '';
$tahun_id  = $_GET['id_tahun'] ?? '';
$id_siswa = $_GET['id_siswa'] ?? '';

// Ambil daftar kelas
$kelas_stmt = $pdo->prepare("
    SELECT DISTINCT k.id, k.nama_kelas, k.sub_kelas
    FROM kelas k
    JOIN siswa s ON s.kelas_id = k.id
    WHERE s.id_tahun = ?
    ORDER BY k.nama_kelas, k.sub_kelas
");
$kelas_stmt->execute([$tahun_id]);
$kelas_list = $kelas_stmt->fetchAll();

// Ambil daftar mapel berdasarkan kelas
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

// Ambil daftar siswa untuk dropdown jika kelas dan tahun dipilih
// Ambil daftar siswa untuk dropdown jika kelas dan tahun dipilih
$dropdown_siswa_list = [];
if (!empty($kelas_id) && !empty($tahun_id)) {
    $stmt_siswa = $pdo->prepare("
        SELECT id, nama 
        FROM siswa 
        WHERE kelas_id = ? AND id_tahun = ?
        ORDER BY nama
    ");
    $stmt_siswa->execute([$kelas_id, $tahun_id]);
    $dropdown_siswa_list = $stmt_siswa->fetchAll();
}

// Ambil semua tahun ajaran (tidak tergantung kelas)
$stmt = $pdo->query("SELECT id_tahun, tahun FROM tahun_ajaran ORDER BY tahun DESC");
$tahun_list = $stmt->fetchAll();

// Ambil data siswa & nilai
$data_nilai = [];

if ($kelas_id && $mapel_id && $tahun_id) {
    if (!empty($id_siswa)) {
        // Jika siswa dipilih, filter berdasarkan id_siswa
        $stmt = $pdo->prepare("
            SELECT 
                s.nis, s.nama AS nama_siswa, s.jenis_kelamin,
                k.nama_kelas, k.sub_kelas,
                t.tahun,
                n.absen, n.tugas, n.uts, n.uas, n.total, n.grade,
                m.mapel
            FROM siswa s
            JOIN kelas k ON s.kelas_id = k.id
            JOIN tahun_ajaran t ON s.id_tahun = t.id_tahun
            LEFT JOIN nilai n ON n.id_nilai = s.id AND n.id_mapel = ?
            LEFT JOIN mapel m ON m.id_mapel = n.id_mapel
            WHERE s.kelas_id = ? AND s.id_tahun = ? AND s.id = ?
            ORDER BY s.nama
        ");
        $stmt->execute([$mapel_id, $kelas_id, $tahun_id, $id_siswa]);
    } else {
        // Jika tidak dipilih siswa, tampilkan semua
        $stmt = $pdo->prepare("
            SELECT 
                s.nis, s.nama AS nama_siswa, s.jenis_kelamin,
                k.nama_kelas, k.sub_kelas,
                t.tahun,
                n.absen, n.tugas, n.uts, n.uas, n.total, n.grade,
                m.mapel
            FROM siswa s
            JOIN kelas k ON s.kelas_id = k.id
            JOIN tahun_ajaran t ON s.id_tahun = t.id_tahun
            LEFT JOIN nilai n ON n.id_nilai = s.id AND n.id_mapel = ?
            LEFT JOIN mapel m ON m.id_mapel = n.id_mapel
            WHERE s.kelas_id = ? AND s.id_tahun = ?
            ORDER BY s.nama
        ");
        $stmt->execute([$mapel_id, $kelas_id, $tahun_id]);
    }

    $data_nilai = $stmt->fetchAll();
}
?>

<div class="md:pl-64 pb-safe">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-sm mb-6">
            <div class="p-6">
                <h1 class="text-2xl font-bold text-gray-800 mb-4">Laporan Nilai</h1>

<form method="GET" id="filterForm" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end mb-6">
    <!-- Tahun Ajaran -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Tahun Ajaran</label>
        <select name="id_tahun" onchange="document.getElementById('filterForm').submit()" 
            class="w-full rounded border-gray-300">
            <option value="">Pilih Tahun</option>
            <?php foreach ($tahun_list as $t): ?>
                <option value="<?= $t['id_tahun'] ?>" <?= $tahun_id == $t['id_tahun'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($t['tahun']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Kelas -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Kelas</label>
        <select name="kelas_id" onchange="document.getElementById('filterForm').submit()" 
            class="w-full rounded border-gray-300" <?= empty($tahun_id) ? 'disabled' : '' ?>>
            <?php if (empty($tahun_id)): ?>
                <option value="">Pilih Tahun Ajaran Dahulu</option>
            <?php else: ?>
                <option value="">Pilih Kelas</option>
                <?php foreach ($kelas_list as $k): ?>
                    <option value="<?= $k['id'] ?>" <?= $kelas_id == $k['id'] ? 'selected' : '' ?>>
                        <?= "Kelas {$k['nama_kelas']}-{$k['sub_kelas']}" ?>
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
    </div>

    <!-- Mapel -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Mata Pelajaran</label>
        <select name="mapel_id" onchange="document.getElementById('filterForm').submit()" 
            class="w-full rounded border-gray-300" <?= (empty($tahun_id) || empty($kelas_id)) ? 'disabled' : '' ?>>
            <?php if (empty($tahun_id) || empty($kelas_id)): ?>
                <option value="">Pilih Kelas Dahulu</option>
            <?php else: ?>
                <option value="">Semua Mapel</option>
                <?php foreach ($mapel_list as $m): ?>
                    <option value="<?= $m['id_mapel'] ?>" <?= $mapel_id == $m['id_mapel'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m['mapel']) ?>
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
    </div>

    <!-- Siswa -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Siswa</label>
        <select name="id_siswa" onchange="document.getElementById('filterForm').submit()" 
            class="w-full rounded border-gray-300" <?= (empty($tahun_id) || empty($kelas_id)) ? 'disabled' : '' ?>>
            <?php if (empty($tahun_id) || empty($kelas_id)): ?>
                <option value="">Pilih Kelas Dahulu</option>
            <?php else: ?>
                <option value="">Semua Siswa</option>
                <?php foreach ($dropdown_siswa_list as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= ($id_siswa == $s['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['nama']) ?>
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
    </div>

    <!-- Export -->
    <div class="flex justify-end md:mt-6">
        <button 
            id="btnExport"
            class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed w-full md:w-auto"
            onclick="return handleExportClick();"
            <?= empty($tahun_id) ? 'disabled' : '' ?>
        >
            <i class="fas fa-file-excel mr-1"></i>Export Excel
        </button>
    </div>
</form>
                <?php if (empty($data_nilai)): ?>
                <?php else: ?>
                    <!-- Tabel Nilai -->
                    <div class="overflow-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">No</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Nama</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">NIS</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">JK</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Kelas</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Mapel</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Absen</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Tugas</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">UTS</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">UAS</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Total</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Grade</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 text-sm">
                                <?php $no = 1; foreach ($data_nilai as $row): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-2"><?= $no++ ?></td>
                                        <td class="px-4 py-2"><?= htmlspecialchars($row['nama_siswa']) ?></td>
                                        <td class="px-4 py-2"><?= htmlspecialchars($row['nis']) ?></td>
                                        <td class="px-4 py-2"><?= htmlspecialchars($row['jenis_kelamin']) ?></td>
                                        <td class="px-4 py-2"><?= 'Kelas ' . htmlspecialchars($row['nama_kelas']) . '-' . htmlspecialchars($row['sub_kelas']) ?></td>
                                        <td class="px-4 py-2"><?= htmlspecialchars($row['mapel']) ?></td>
                                        <td class="px-4 py-2"><?= $row['absen'] ?></td>
                                        <td class="px-4 py-2"><?= $row['tugas'] ?></td>
                                        <td class="px-4 py-2"><?= $row['uts'] ?></td>
                                        <td class="px-4 py-2"><?= $row['uas'] ?></td>
                                        <td class="px-4 py-2"><?= $row['total'] ?></td>
                                        <td class="px-4 py-2"><?= $row['grade'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
            <!-- Setelah form filter -->
<?php if (!empty($tahun_id) && !empty($kelas_id) && !empty($mapel_id) && isset($siswa_list) && count($siswa_list) === 0): ?>
    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded mb-6">
        <p class="font-medium">Data siswa untuk kombinasi Kelas, Mapel, dan Tahun Ajaran yang dipilih belum tersedia.</p>
    </div>
<?php elseif (empty($tahun_id) || empty($kelas_id) || empty($mapel_id)) : ?>
    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded mb-6">
        <p class="font-medium">Silakan pilih <strong>Tahun Ajaran</strong>, <strong>Kelas</strong> dan <strong>Mata Pelajaran</strong> terlebih dahulu untuk menampilkan data.</p>
    </div>
<?php endif; ?>
    </div>
</div>

<script>
function handleExportClick() {
    const urlParams = new URLSearchParams(window.location.search);
    const tahun = urlParams.get("id_tahun");
    const kelas = urlParams.get("kelas_id") ?? '';
    const mapel = urlParams.get("mapel_id") ?? '';
    const siswa = urlParams.get("id_siswa") ?? '';

    if (!tahun) {
        alert("Silakan pilih Tahun Ajaran terlebih dahulu sebelum export.");
        return false;
    }

    // Redirect manual ke halaman export
    const url = `export_nilai.php?id_tahun=${tahun}&kelas_id=${kelas}&mapel_id=${mapel}&id_siswa=${siswa}`;
    window.location.href = url;
    return false;
}
</script>

<?php require_once 'partials/footer.php'; ?>
