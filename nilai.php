<?php
session_start();
require_once 'config/koneksi.php';
require_once 'partials/header.php';
require_once 'partials/navbar.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_siswa'])) {
    $siswa_id = $_POST['simpan_siswa'];
    $tanggal = $_POST['tanggal'];
    $kelas_id = $_POST['kelas_id'];
    $id_mapel = $_POST['mapel_id'];

    $status = $_POST['status'][$siswa_id] ?? '';
    $keterangan_val = $_POST['keterangan'][$siswa_id] ?? '';
    $absen_text = $_POST['absen'][$siswa_id] ?? 0;
    $tugas_val = $_POST['tugas'][$siswa_id] ?? 0;
    $uts_val = $_POST['uts'][$siswa_id] ?? 0;
    $uas_val = $_POST['uas'][$siswa_id] ?? 0;
    $total_val = $_POST['total'][$siswa_id] ?? 0;
    $grade_val = $_POST['grade'][$siswa_id] ?? '';

    // Proses absensi
    $stmt_check_absen = $pdo->prepare("SELECT id FROM absensi WHERE siswa_id = ? AND DATE(tanggal) = ?");
    $stmt_check_absen->execute([$siswa_id, $tanggal]);

    if ($stmt_check_absen->rowCount() > 0) {
        $stmt_update_absen = $pdo->prepare("UPDATE absensi SET status = ?, keterangan = ?, created_by = ? WHERE siswa_id = ? AND DATE(tanggal) = ?");
        $stmt_update_absen->execute([$status, $keterangan_val, $_SESSION['user_id'], $siswa_id, $tanggal]);
    } else {
        $stmt_insert_absen = $pdo->prepare("INSERT INTO absensi (siswa_id, tanggal, status, keterangan, created_by) VALUES (?, ?, ?, ?, ?)");
        $stmt_insert_absen->execute([$siswa_id, $tanggal, $status, $keterangan_val, $_SESSION['user_id']]);
    }

    // Proses nilai dengan id_mapel
    $id_tahun = $_POST['id_tahun'] ?? ''; // PENTING: pastikan ini ada dan ikut dikirim di form (lihat langkah 2)

    $stmt_check_nilai = $pdo->prepare("SELECT id_nilai FROM nilai WHERE id_nilai = ? AND id_mapel = ?");
    $stmt_check_nilai->execute([$siswa_id, $id_mapel]);

if ($stmt_check_nilai->rowCount() > 0) {
    // UPDATE NILAI DENGAN update_by & update_at
    $stmt_update_nilai = $pdo->prepare("
        UPDATE nilai 
        SET absen = ?, tugas = ?, uts = ?, uas = ?, total = ?, grade = ?, 
            update_by = ?, updated_at = NOW()
        WHERE id_nilai = ? AND id_mapel = ?
    ");
    $stmt_update_nilai->execute([
        $absen_text,
        $tugas_val,
        $uts_val,
        $uas_val,
        $total_val,
        $grade_val,
        $_SESSION['user_id'], // update_by
        $siswa_id,
        $id_mapel
    ]);
} else {
    // INSERT NILAI DENGAN created_by & created_at
    $stmt_insert_nilai = $pdo->prepare("
        INSERT INTO nilai 
        (id_nilai, id_mapel, absen, tugas, uts, uas, total, grade, created_by, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt_insert_nilai->execute([
        $siswa_id,
        $id_mapel,
        $absen_text,
        $tugas_val,
        $uts_val,
        $uas_val,
        $total_val,
        $grade_val,
        $_SESSION['user_id'] // created_by
    ]);
}
}

$stmt = $pdo->query("
    SELECT DISTINCT nama_kelas, sub_kelas, MIN(id) as id
    FROM kelas
    GROUP BY nama_kelas, sub_kelas
    ORDER BY nama_kelas, sub_kelas
");
$kelas_list = $stmt->fetchAll();

$mapel_list = [];
if (isset($_GET['kelas_id']) && !empty($_GET['kelas_id'])) {
    $stmt = $pdo->prepare("
        SELECT DISTINCT m.id_mapel, m.mapel
        FROM pelajaran p
        JOIN mapel m ON p.id_mapel = m.id_mapel
        WHERE p.id = ?
        ORDER BY m.mapel
    ");
    $stmt->execute([$_GET['kelas_id']]);
    $mapel_list = $stmt->fetchAll();
}


$siswa_list = [];
if (isset($_GET['kelas_id'], $_GET['mapel_id']) && !empty($_GET['kelas_id']) && !empty($_GET['mapel_id'])) {
    $nama_mapel_terpilih = '';
if (isset($_GET['mapel_id'])) {
    foreach ($mapel_list as $m) {
        if ($m['id_mapel'] == $_GET['mapel_id']) {
            $nama_mapel_terpilih = $m['mapel'];
            break;
        }
    }
}
    $tanggal = $_GET['tanggal'] ?? date('Y-m-d');
$query = "SELECT 
    s.*, 
    t.tahun, 
    n.absen, 
    n.tugas, 
    n.uts, 
    n.uas, 
    n.total, 
    n.grade, 
    n.created_by,
    n.created_at,
    n.update_by,
    n.updated_at, 
    g.nama AS created_by_nama,
    g2.nama AS updated_by_nama
FROM siswa s
JOIN tahun_ajaran t ON s.id_tahun = t.id_tahun
LEFT JOIN nilai n ON n.id_nilai = s.id AND n.id_mapel = ?
LEFT JOIN guru g ON n.created_by = g.id
LEFT JOIN guru g2 ON n.update_by = g2.id
WHERE s.kelas_id = ? AND s.id_tahun = ?
ORDER BY s.nama ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$_GET['mapel_id'], $_GET['kelas_id'], $_GET['id_tahun']]);
    $siswa_list = $stmt->fetchAll();
}

// Ambil data kelas
$stmt = $pdo->query("
    SELECT DISTINCT nama_kelas, sub_kelas, MIN(id) as id
    FROM kelas
    GROUP BY nama_kelas, sub_kelas
    ORDER BY nama_kelas, sub_kelas
");
$kelas_list = $stmt->fetchAll();

// Ambil daftar mapel jika kelas sudah dipilih
$mapel_list = [];
if (isset($_GET['kelas_id']) && !empty($_GET['kelas_id'])) {
    $stmt_mapel = $pdo->prepare("
    SELECT DISTINCT m.id_mapel, m.mapel
    FROM pelajaran p
    JOIN mapel m ON p.id_mapel = m.id_mapel
    WHERE p.id = ? 
    ORDER BY m.mapel
");
    $stmt_mapel->execute([$_GET['kelas_id']]);
    $mapel_list = $stmt_mapel->fetchAll();
}

$nama_mapel_terpilih = '';
if (isset($_GET['mapel_id']) && !empty($_GET['mapel_id'])) {
    $stmt = $pdo->prepare("SELECT mapel FROM mapel WHERE id_mapel = ?");
    $stmt->execute([$_GET['mapel_id']]);
    $mapel_data = $stmt->fetch();
    $nama_mapel_terpilih = $mapel_data ? $mapel_data['mapel'] : '';
}

$tahun_list = [];
if (isset($_GET['kelas_id']) && !empty($_GET['kelas_id'])) {
    $stmt_tahun = $pdo->prepare("
        SELECT DISTINCT t.id_tahun, t.tahun
        FROM siswa s
        JOIN tahun_ajaran t ON s.id_tahun = t.id_tahun
        WHERE s.kelas_id = ?
        ORDER BY t.tahun DESC
    ");
    $stmt_tahun->execute([$_GET['kelas_id']]);
    $tahun_list = $stmt_tahun->fetchAll();
}

$dropdown_siswa_list = [];

if (
    isset($_GET['kelas_id']) && !empty($_GET['kelas_id']) &&
    isset($_GET['mapel_id']) && !empty($_GET['mapel_id']) &&
    isset($_GET['id_tahun']) && !empty($_GET['id_tahun'])
) {
    $stmt_siswa = $pdo->prepare("
        SELECT DISTINCT s.id, s.nama 
        FROM siswa s
        JOIN pelajaran p ON s.kelas_id = p.id
        WHERE s.kelas_id = ? 
          AND p.id_mapel = ? 
          AND s.id_tahun = ?
        ORDER BY s.nama ASC
    ");
    $stmt_siswa->execute([
        $_GET['kelas_id'],
        $_GET['mapel_id'],
        $_GET['id_tahun']
    ]);
    $dropdown_siswa_list = $stmt_siswa->fetchAll();
}

if (isset($_GET['kelas_id'], $_GET['mapel_id'], $_GET['id_tahun'])) {
    $kelas_id = $_GET['kelas_id'];
    $mapel_id = $_GET['mapel_id'];
    $id_tahun = $_GET['id_tahun'];
    $id_siswa = $_GET['id_siswa'] ?? '';

    $query = "SELECT 
        s.*, 
        t.tahun, 
        n.absen, n.tugas, n.uts, n.uas, n.total, n.grade, 
        n.created_by,
        n.created_at,
        n.update_by,
        n.updated_at,
        g.nama AS created_by_nama,
        g2.nama AS updated_by_nama
    FROM siswa s
    JOIN tahun_ajaran t ON s.id_tahun = t.id_tahun
    LEFT JOIN nilai n ON n.id_nilai = s.id AND n.id_mapel = ?
    LEFT JOIN guru g ON n.created_by = g.id
    LEFT JOIN guru g2 ON n.update_by = g2.id
    WHERE s.kelas_id = ? AND s.id_tahun = ?";

    $params = [$mapel_id, $kelas_id, $id_tahun];

    if (!empty($id_siswa)) {
        $query .= " AND s.id = ?";
        $params[] = $id_siswa;
    }

    $query .= " ORDER BY s.nama ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $siswa_list = $stmt->fetchAll();
}
?>

<div class="md:pl-64 pb-safe">
    <div class="container mx-auto px-4 py-8">
        
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Input Nilai Siswa</h1>
            <p class="text-gray-600">Silakan pilih kelas dan tanggal untuk melakukan input nilai</p>
        </div>
        
<div class="bg-white rounded-lg shadow-md p-6 mb-8">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-2 gap-6"">
        <!-- Dropdown Kelas -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2" for="kelas_id">Pilih Kelas</label>
            <select name="kelas_id" id="kelas_id" required
                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
                onchange="location.href='nilai.php?kelas_id=' + this.value">
                <option value="">-- Pilih Kelas --</option>
                <?php foreach($kelas_list as $kelas): ?>
                    <option value="<?= $kelas['id'] ?>" <?= ($_GET['kelas_id'] ?? '') == $kelas['id'] ? 'selected' : '' ?>>
                        <?= 'Kelas ' . htmlspecialchars($kelas['nama_kelas']) . '-' . htmlspecialchars($kelas['sub_kelas']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Dropdown Mapel -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2" for="mapel_id">Pilih Mapel</label>
            <?php if (!empty($mapel_list)): ?>
                <select name="mapel_id" id="mapel_id" required onchange="this.form.submit()"
                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                    <option value="">-- Pilih Mapel --</option>
                    <?php foreach($mapel_list as $mapel): ?>
                        <option value="<?= $mapel['id_mapel'] ?>" <?= ($_GET['mapel_id'] ?? '') == $mapel['id_mapel'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($mapel['mapel']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <select disabled
                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 bg-gray-100 text-gray-500 sm:text-sm rounded-md">
                    <option>-- Pilih Kelas Terlebih Dahulu --</option>
                </select>
            <?php endif; ?>
        </div>
        <!-- Dropdown Tahun Ajaran -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2" for="id_tahun">Tahun Ajaran</label>
            <?php if (!empty($tahun_list)): ?>
                <select name="id_tahun" id="id_tahun" required onchange="this.form.submit()"
                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                    <option value="">-- Pilih Tahun Ajaran --</option>
                    <?php foreach($tahun_list as $tahun): ?>
                        <option value="<?= $tahun['id_tahun'] ?>" <?= ($_GET['id_tahun'] ?? '') == $tahun['id_tahun'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($tahun['tahun']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php elseif (!isset($_GET['kelas_id']) || empty($_GET['kelas_id'])): ?>
                <select disabled
                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 bg-gray-100 text-gray-500 sm:text-sm rounded-md">
                    <option>-- Pilih Kelas Terlebih Dahulu --</option>
                </select>
            <?php else: ?>
                <select disabled
                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 bg-gray-100 text-gray-500 sm:text-sm rounded-md">
                    <option>-- Tidak Ada Tahun Ajaran --</option>
                </select>
            <?php endif; ?>
        </div>

        <!-- Dropdown Nama Siswa -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2" for="id_siswa">Nama Siswa</label>
            <?php if (!empty($dropdown_siswa_list)): ?>
                <select name="id_siswa" id="id_siswa" onchange="this.form.submit()"
                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                    <option value="">-- Semua Siswa --</option>
                    <?php foreach($dropdown_siswa_list as $siswa): ?>
                        <option value="<?= $siswa['id'] ?>" <?= (isset($_GET['id_siswa']) && $_GET['id_siswa'] == $siswa['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($siswa['nama']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <select disabled
                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 bg-gray-100 text-gray-500 sm:text-sm rounded-md">
                    <option>-- Pilih Kelas Terlebih Dahulu --</option>
                </select>
            <?php endif; ?>
        </div>
    </form>
</div>

        <?php if(!empty($siswa_list)): ?>
        <form method="POST" class="bg-white rounded-lg shadow-md overflow-hidden pb-20 md:pb-0">
            <input type="hidden" name="kelas_id" value="<?= htmlspecialchars($_GET['kelas_id']) ?>">
            <input type="hidden" name="id_tahun" value="<?= htmlspecialchars($_GET['id_tahun'] ?? '') ?>">
            <input type="hidden" name="tanggal" value="<?= isset($_GET['tanggal']) ? htmlspecialchars($_GET['tanggal']) : date('Y-m-d') ?>">
            <input type="hidden" name="mapel_id" value="<?= htmlspecialchars($_GET['mapel_id']) ?>">
            <input type="hidden" name="redirect" value="nilai.php">


            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">Daftar Siswa</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="table-auto min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                            <th class="px-10 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Siswa</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mapel</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Absen</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tugas</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">UTS</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">UAS</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Grade</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Diinput Oleh</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu Input</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Diperbarui Oleh</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu Update</th>
                        </tr>
                    </thead>
                    <?php
$tugas_akumulasi = []; // [siswa_id => nilai]

foreach ($siswa_list as $s) {
    $id_siswa = $s['id'];

    // Ambil semua nilai UH
    $stmt_uh = $pdo->prepare("SELECT nilai FROM tugas_detail WHERE siswa_id = ? AND id_mapel = ? AND jenis = 'uh'");
    $stmt_uh->execute([$id_siswa, $_GET['mapel_id']]);
    $nilai_uh = $stmt_uh->fetchAll(PDO::FETCH_COLUMN);

    // Ambil semua nilai Tugas (ntugas)
    $stmt_tugas = $pdo->prepare("SELECT nilai FROM tugas_detail WHERE siswa_id = ? AND id_mapel = ? AND jenis = 'ntugas'");
    $stmt_tugas->execute([$id_siswa, $_GET['mapel_id']]);
    $nilai_ntugas = $stmt_tugas->fetchAll(PDO::FETCH_COLUMN);

    // Hitung rata-rata UH dan Tugas jika ada
    $avg_uh = count($nilai_uh) > 0 ? array_sum($nilai_uh) / count($nilai_uh) : 0;
    $avg_tugas = count($nilai_ntugas) > 0 ? array_sum($nilai_ntugas) / count($nilai_ntugas) : 0;

    // Gabungkan (misalnya, rata-rata dari keduanya)
    $akumulasi = ($avg_uh + $avg_tugas) / 2;

    $tugas_akumulasi[$id_siswa] = round($akumulasi, 2); // dua desimal
}
?>

                    <tbody class="bg-white divide-y divide-gray-200">
<?php foreach($siswa_list as $index => $siswa): ?>
<tr>
    <td class="px-2 py-4 text-sm text-gray-900"><?= $index + 1 ?></td>
    <td class="px-10 py-4 text-sm text-gray-900"><?= htmlspecialchars($siswa['nama']) ?></td>
    <td class="px-4 py-4 text-sm text-gray-900"><?= htmlspecialchars($nama_mapel_terpilih) ?></td>
    
    <!-- Absen -->
    <td class="px-4 py-4">
        <input type="number" step="0.01" name="absen[<?= $siswa['id'] ?>]" 
            value="<?= htmlspecialchars($siswa['absen'] ?? '') ?>" 
            class="w-20 text-sm text-center border-l border-r border-black rounded-md shadow-sm bg-yellow-100 text-black">
    </td>

    <!-- Tugas -->
    <td class="px-4 py-4">
        <input type="number" step="0.01" name="tugas[<?= $siswa['id'] ?>]"
            value="<?= $tugas_akumulasi[$siswa['id']] ?? 0 ?>"
            readonly
            class="w-20 text-sm text-center border-l border-r border-black rounded-md shadow-sm bg-gray-200 text-black">
    </td>

    <!-- UTS -->
    <td class="px-4 py-4">
        <input type="number" step="0.01" name="uts[<?= $siswa['id'] ?>]" 
            value="<?= htmlspecialchars($siswa['uts'] ?? '') ?>" 
            class="w-20 text-sm text-center border-l border-r border-black rounded-md shadow-sm bg-yellow-100 text-black">
    </td>

    <!-- UAS -->
    <td class="px-4 py-4">
        <input type="number" step="0.01" name="uas[<?= $siswa['id'] ?>]" 
            value="<?= htmlspecialchars($siswa['uas'] ?? '') ?>" 
            class="w-20 text-sm text-center border-l border-r border-black rounded-md shadow-sm bg-yellow-100 text-black">
    </td>

    <!-- Total -->
    <td class="px-4 py-4 text-sm text-gray-900">
        <span id="total_display_<?= $siswa['id'] ?>"><?= htmlspecialchars($siswa['total'] ?? '') ?></span>
        <input type="hidden" name="total[<?= $siswa['id'] ?>]" id="total_input_<?= $siswa['id'] ?>" value="<?= htmlspecialchars($siswa['total'] ?? '') ?>">
    </td>

    <!-- Grade -->
    <td class="px-4 py-4 text-sm text-gray-900">
        <span id="grade_display_<?= $siswa['id'] ?>"><?= htmlspecialchars($siswa['grade'] ?? '') ?></span>
        <input type="hidden" name="grade[<?= $siswa['id'] ?>]" id="grade_input_<?= $siswa['id'] ?>" value="<?= htmlspecialchars($siswa['grade'] ?? '') ?>">
    </td>

    <!-- Aksi -->
    <td class="px-4 py-4 text-sm text-gray-900 whitespace-nowrap">
        <button type="submit" name="simpan_siswa" value="<?= $siswa['id'] ?>" 
            class="bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium px-3 py-1 rounded">
            Simpan
        </button>
    </td>

    <!-- Diinput Oleh -->
    <td class="px-2 py-4 text-xs text-gray-900 whitespace-nowrap">
        <?= htmlspecialchars($siswa['created_by_nama'] ?? '-') ?>
    </td>

    <!-- Waktu Input -->
    <td class="px-2 py-4 text-xs text-gray-900 whitespace-nowrap">
        <?= !empty($siswa['created_at']) ? date('d-m-Y H:i', strtotime($siswa['created_at'])) : '-' ?>
    </td>
    <!-- Diupdate Oleh -->
<!-- Diupdate Oleh -->
<td class="px-2 py-4 text-xs text-gray-900 whitespace-nowrap">
    <?= htmlspecialchars($siswa['updated_by_nama'] ?? '-') ?>
</td>

<!-- Waktu Update -->
<td class="px-2 py-4 text-xs text-gray-900 whitespace-nowrap">
    <?= !empty($siswa['updated_at']) ? date('d-m-Y H:i', strtotime($siswa['updated_at'])) : '-' ?>
</td>

</tr>
<?php endforeach; ?>
</tbody>
                </table>
            </div>
        </form>
        <?php else: ?>
    <?php
    $kelas_terpilih = isset($_GET['kelas_id']) && $_GET['kelas_id'] !== '';
    $mapel_terpilih = isset($_GET['mapel_id']) && $_GET['mapel_id'] !== '';
    $tahun_terpilih = isset($_GET['id_tahun']) && $_GET['id_tahun'] !== '';
    ?>

    <?php if ($kelas_terpilih && $mapel_terpilih && $tahun_terpilih): ?>
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4">
            <p class="font-medium">Data siswa untuk kombinasi kelas, mapel, dan tahun ajaran yang dipilih belum tersedia.</p>
        </div>
    <?php else: ?>
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4">
            <p class="font-medium">Silahkan Pilih Kelas, Mata Pelajaran dan Tahun Ajaran Terlebih Dahulu.</p>
        </div>
    <?php endif; ?>
<?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const siswaRows = document.querySelectorAll('tbody tr');
    siswaRows.forEach(row => {
        const id = row.querySelector('input[name^="absen"]').name.match(/\d+/)[0];
        const absen = row.querySelector(`input[name="absen[${id}]"]`);
        const tugas = row.querySelector(`input[name="tugas[${id}]"]`);
        const uts = row.querySelector(`input[name="uts[${id}]"]`);
        const uas = row.querySelector(`input[name="uas[${id}]"]`);
        const totalText = document.getElementById(`total_display_${id}`);
        const gradeText = document.getElementById(`grade_display_${id}`);
        const totalInput = document.getElementById(`total_input_${id}`);
        const gradeInput = document.getElementById(`grade_input_${id}`);

function hitung() {
    const a = parseFloat(absen.value) || 0;
    const t = parseFloat(tugas.value) || 0;
    const u1 = parseFloat(uts.value) || 0;
    const u2 = parseFloat(uas.value) || 0;

    // Hitung total berbobot sesuai bobot baru
    const total = (a * 0.10) + (t * 0.30) + (u1 * 0.30) + (u2 * 0.30);

    // Tentukan grade
    let grade = 'D';
    if (total >= 80) grade = 'A';
    else if (total >= 70) grade = 'B';
    else if (total >= 60) grade = 'C';

    // Tampilkan total dan grade
    totalText.textContent = total.toFixed(2);
    gradeText.textContent = grade;
    totalInput.value = total.toFixed(2);
    gradeInput.value = grade;
}


        [absen, tugas, uts, uas].forEach(el => el.addEventListener('input', hitung));
        hitung();
    });
});
</script>

<?php require_once 'partials/footer.php'; ?>
