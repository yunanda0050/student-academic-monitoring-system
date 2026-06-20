
<?php
session_start();
require_once 'config/koneksi.php';
require_once 'partials/header.php';
require_once 'partials/navbar.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tanggal = $_POST['tanggal'];
    $kelas_id = $_POST['kelas_id'];
    $id_mapel = $_POST['mapel_id'];
    $siswa_id = $_POST['simpan_siswa'];
    $id_tahun = $_POST['id_tahun'];
    $user_id = $_SESSION['user_id'];
    $now = date('Y-m-d H:i:s');

    $is_saved = false;
    $is_deleted = false;

    // Ambil daftar guru (id -> nama)
    $guru_map = [];
    $stmt_guru = $pdo->query("SELECT id, nama FROM guru");
    while ($row = $stmt_guru->fetch()) {
        $guru_map[$row['id']] = $row['nama'];
    }

    // === Proses nilai UH ===
    if (isset($_POST['uh'][$siswa_id])) {
        foreach ($_POST['uh'][$siswa_id] as $ke => $nilai) {
            $ke_uh = $ke + 1;
            if (strlen(trim($nilai)) > 0) {
                $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM tugas_detail 
                    WHERE siswa_id = ? AND id_mapel = ? AND jenis = 'uh' AND ke = ?");
                $stmt_check->execute([$siswa_id, $id_mapel, $ke_uh]);
                $exists = $stmt_check->fetchColumn();

                if ($exists) {
                    $stmt_update = $pdo->prepare("UPDATE tugas_detail 
                        SET nilai = ?, tanggal = ?, updated_by = ?
                        WHERE siswa_id = ? AND id_mapel = ? AND jenis = 'uh' AND ke = ?");
                    $stmt_update->execute([$nilai, $tanggal, $user_id, $siswa_id, $id_mapel, $ke_uh]);
                } else {
                    $stmt_insert = $pdo->prepare("INSERT INTO tugas_detail 
                        (id_tugas, id_mapel, siswa_id, jenis, nilai, ke, tanggal, created_by, updated_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt_insert->execute([$siswa_id, $id_mapel, $siswa_id, 'uh', $nilai, $ke_uh, $tanggal, $user_id, $user_id]);
                }
                $is_saved = true;
            } else {
                $stmt_delete = $pdo->prepare("DELETE FROM tugas_detail 
                    WHERE siswa_id = ? AND id_mapel = ? AND jenis = 'uh' AND ke = ?");
                $stmt_delete->execute([$siswa_id, $id_mapel, $ke_uh]);
                if ($stmt_delete->rowCount() > 0) $is_deleted = true;
            }
        }
    }

    // === Proses nilai TUGAS ===
    if (isset($_POST['tugas'][$siswa_id])) {
        foreach ($_POST['tugas'][$siswa_id] as $ke => $nilai) {
            $ke_tugas = $ke + 1;
            if (strlen(trim($nilai)) > 0) {
                $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM tugas_detail 
                    WHERE siswa_id = ? AND id_mapel = ? AND jenis = 'ntugas' AND ke = ? AND id_tahun = ?");
                $stmt_check->execute([$siswa_id, $id_mapel, $ke_tugas, $id_tahun]);
                $exists = $stmt_check->fetchColumn();

                if ($exists) {
                    $stmt_update = $pdo->prepare("UPDATE tugas_detail 
                        SET nilai = ?, tanggal = ?, updated_by = ?
                        WHERE siswa_id = ? AND id_mapel = ? AND jenis = 'ntugas' AND ke = ? AND id_tahun = ?");
                    $stmt_update->execute([$nilai, $tanggal, $user_id, $siswa_id, $id_mapel, $ke_tugas, $id_tahun]);
                } else {
                    $stmt_insert = $pdo->prepare("INSERT INTO tugas_detail 
                        (id_tugas, id_mapel, siswa_id, jenis, nilai, ke, tanggal, id_tahun, created_by, updated_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt_insert->execute([$siswa_id, $id_mapel, $siswa_id, 'ntugas', $nilai, $ke_tugas, $tanggal, $id_tahun, $user_id, $user_id]);
                }
                $is_saved = true;
            } else {
                $stmt_delete = $pdo->prepare("DELETE FROM tugas_detail 
                    WHERE siswa_id = ? AND id_mapel = ? AND jenis = 'ntugas' AND ke = ? AND id_tahun = ?");
                $stmt_delete->execute([$siswa_id, $id_mapel, $ke_tugas, $id_tahun]);
                if ($stmt_delete->rowCount() > 0) $is_deleted = true;
            }
        }
    }

    // Redirect setelah proses selesai
    $query_params = [
        "kelas_id"    => $kelas_id,
        "mapel_id"    => $id_mapel,
        "tanggal"     => $tanggal,
        "id_tahun"    => $id_tahun,
    ];
    if ($is_saved)   $query_params['success_simpan'] = 1;
    if ($is_deleted) $query_params['success_hapus'] = 1;

    $redirect_file = $_POST['redirect'] ?? 'tugas.php';
    $redirect_url  = $redirect_file . '?' . http_build_query($query_params);
    header("Location: $redirect_url");
    exit;
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
$siswa_id = $_GET['siswa_id'] ?? null;
$mapel_id = $_GET['mapel_id'] ?? null;
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

$siswa_list = [];

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

    $query = "SELECT siswa.*, tahun_ajaran.tahun 
              FROM siswa
              JOIN tahun_ajaran ON siswa.id_tahun = tahun_ajaran.id_tahun
              WHERE siswa.kelas_id = ? AND siswa.id_tahun = ?";
    $params = [$kelas_id, $id_tahun];

    if (!empty($id_siswa)) {
        $query .= " AND siswa.id = ?";
        $params[] = $id_siswa;
    }

    $query .= " ORDER BY siswa.nama ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $siswa_list = $stmt->fetchAll();

    if ($siswa_id && $mapel_id) {
    $stmt = $pdo->prepare("SELECT * FROM tugas WHERE siswa_id = ? AND mapel_id = ?");
    $stmt->execute([$siswa_id, $mapel_id]);
    $history = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $history = null;
}

}
?>

<div class="md:pl-64 pb-safe">
    <div class="container mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Input Nilai Tugas Siswa</h1>
            <p class="text-gray-600">Silakan pilih kelas, mata pelajaran, dan tahun ajaran untuk melakukan input tugas nilai.</p>
        </div>
        <?php if (isset($_GET['success_hapus'])): ?>
            <div id="notif-hapus" class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4">
                🗑️ <strong>Berhasil dihapus:</strong> Nilai kosong berhasil dihapus dari database.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success_simpan'])): ?>
            <div id="notif-simpan" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
                ✅ <strong>Berhasil disimpan:</strong> Nilai siswa berhasil ditambahkan atau diperbarui.
            </div>
        <?php endif; ?>

        <script>
        setTimeout(() => {
            const notifHapus = document.getElementById("notif-hapus");
            const notifSimpan = document.getElementById("notif-simpan");
            if (notifHapus) notifHapus.style.display = "none";
            if (notifSimpan) notifSimpan.style.display = "none";
        }, 3000); // 3 detik
        </script>

        <script>
        // Hapus query string notifikasi dari URL setelah ditampilkan
        if (window.history.replaceState) {
            const url = new URL(window.location.href);
            url.searchParams.delete("success_simpan");
            url.searchParams.delete("success_hapus");
            window.history.replaceState({}, document.title, url.toString());
        }
        </script>

        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="kelas_id">Pilih Kelas</label>
                    <select name="kelas_id" id="kelas_id" required
                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
                    onchange="location.href='tugas.php?kelas_id=' + this.value">
                    <option value="">-- Pilih Kelas --</option>
                    <?php foreach($kelas_list as $kelas): ?>
                        <option value="<?= $kelas['id'] ?>" <?= ($_GET['kelas_id'] ?? '') == $kelas['id'] ? 'selected' : '' ?>>
                            <?= 'Kelas ' . htmlspecialchars($kelas['nama_kelas']) . '-' . htmlspecialchars($kelas['sub_kelas']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                </div>
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
                <div>
    <label class="block text-sm font-medium text-gray-700 mb-2" for="id_tahun">Tahun Ajaran</label>

    <?php if (!empty($tahun_list)): ?>
        <!-- Jika tahun ajaran tersedia -->
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
                    <!-- Jika kelas belum dipilih -->
                    <select disabled
                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 bg-gray-100 text-gray-500 sm:text-sm rounded-md">
                        <option>-- Pilih Kelas Terlebih Dahulu --</option>
                    </select>

                <?php else: ?>
                    <!-- Jika kelas sudah dipilih tapi tidak ada tahun ajaran -->
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
            <input type="hidden" name="tanggal" value="<?= isset($_GET['tanggal']) ? htmlspecialchars($_GET['tanggal']) : date('Y-m-d') ?>">
            <input type="hidden" name="mapel_id" value="<?= htmlspecialchars($_GET['mapel_id']) ?>">
            <input type="hidden" name="id_tahun" value="<?= htmlspecialchars($_GET['id_tahun'] ?? '') ?>">
            <input type="hidden" name="redirect" value="tugas.php">

            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">Daftar Siswa</h2>
            </div>

            <div class="table-responsive">
                <table class="min-w-full divide-y divide-gray-200 table-responsive-stack">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                            <th class="px-10 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Siswa</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mapel</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tahun Ajaran</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">UH</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tugas</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Diinput Oleh</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Waktu Input</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Diupdate Oleh</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Update Terakhir</th>
                        </tr>
                    </thead>
<tbody class="bg-white divide-y divide-gray-200">
<?php foreach($siswa_list as $index => $siswa): ?>
<tr id="row-siswa-<?= $siswa['id'] ?>">
    <td class="px-2 py-4 text-sm text-gray-900"><?= $index + 1 ?></td>
    <td class="px-10 py-4 text-sm text-gray-900"><?= htmlspecialchars($siswa['nama']) ?></td>
    <td class="px-4 py-4 text-sm text-gray-900"><?= htmlspecialchars($nama_mapel_terpilih) ?></td>
    <td class="px-4 py-4 text-sm text-gray-900"><?= htmlspecialchars($siswa['tahun']) ?></td>

    <td class="px-4 py-4">
        <?php
        $stmt_uh = $pdo->prepare("SELECT nilai FROM tugas_detail WHERE siswa_id = ? AND id_mapel = ? AND jenis = 'uh' ORDER BY ke ASC");
        $stmt_uh->execute([$siswa['id'], $_GET['mapel_id']]);
        $nilai_uh = $stmt_uh->fetchAll(PDO::FETCH_COLUMN);
        ?>
        <div id="uh-container-<?= $siswa['id'] ?>">
            <?php if (!empty($nilai_uh)): ?>
                <?php foreach ($nilai_uh as $i => $nilai): ?>
                    <div class="mb-1">
                        <label class="text-xs">UH ke-<?= $i + 1 ?></label>
                        <input type="number" name="uh[<?= $siswa['id'] ?>][]" value="<?= htmlspecialchars($nilai) ?>" step="0.01" class="w-20 text-center border rounded bg-yellow-100">
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="mb-1">
                    <label class="text-xs">UH ke-1</label>
                    <input type="number" name="uh[<?= $siswa['id'] ?>][]" step="0.01" class="w-20 text-center border rounded bg-yellow-100">
                </div>
            <?php endif; ?>
        </div>  
        <input type="hidden" name="uh_count[<?= $siswa['id'] ?>]" value="<?= count($nilai_uh) ?>">

        <button type="button" onclick="tambahUH(<?= $siswa['id'] ?>)" class="text-blue-600 text-xs mt-1">+ Tambah UH</button>
    </td>
    <?php
// Ambil histori tugas dari salah satu entri tugas siswa (jika ada)
$history_stmt = $pdo->prepare("
    SELECT 
        s.id, s.nama, 
        td.created_at, td.updated_at,
        g1.nama AS guru_input,
        g2.nama AS guru_update
    FROM siswa s
    LEFT JOIN tugas_detail td ON s.id = td.siswa_id AND td.id_mapel = ?
    LEFT JOIN guru g1 ON td.created_by = g1.id
    LEFT JOIN guru g2 ON td.updated_by = g2.id
    WHERE s.id = ?
    ORDER BY td.updated_at DESC
    LIMIT 1
");
$history_stmt->execute([$_GET['mapel_id'], $siswa['id']]);
$histori = $history_stmt->fetch(PDO::FETCH_ASSOC);
?>

    <td class="px-4 py-4">
        <?php
        $stmt_tugas = $pdo->prepare("SELECT nilai FROM tugas_detail WHERE siswa_id = ? AND id_mapel = ? AND jenis = 'ntugas' ORDER BY ke ASC");
        $stmt_tugas->execute([$siswa['id'], $_GET['mapel_id']]);
        $nilai_tugas = $stmt_tugas->fetchAll(PDO::FETCH_COLUMN);
        ?>
        <div id="tugas-container-<?= $siswa['id'] ?>">
            <?php if (!empty($nilai_tugas)): ?>
                <?php foreach ($nilai_tugas as $i => $nilai): ?>
                    <div class="mb-1">
                        <label class="text-xs">Tugas ke-<?= $i + 1 ?></label>
                        <input type="number" name="tugas[<?= $siswa['id'] ?>][]" value="<?= htmlspecialchars($nilai) ?>" step="0.01" class="w-20 text-center border rounded bg-yellow-100">
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="mb-1">
                    <label class="text-xs">Tugas ke-1</label>
                    <input type="number" name="tugas[<?= $siswa['id'] ?>][]" step="0.01" class="w-20 text-center border rounded bg-yellow-100">
                </div>
            <?php endif; ?>
        </div>
        <input type="hidden" name="tugas_count[<?= $siswa['id'] ?>]" value="<?= count($nilai_tugas) ?>">

        <button type="button" onclick="tambahTugas(<?= $siswa['id'] ?>)" class="text-blue-600 text-xs mt-1">+ Tambah Tugas</button>
    </td>
    <?php if (!empty($tugas_detail)): ?>
    <div class="text-xs text-gray-500 mt-2 leading-tight">
        <div><strong>Diinput:</strong> <?= $guru_map[$tugas_detail[0]['created_by']] ?? '-' ?> <br><?= $tugas_detail[0]['created_at'] ?? '-' ?></div>
        <div class="mt-1"><strong>Update:</strong> <?= $guru_map[$tugas_detail[0]['updated_by']] ?? '-' ?> <br><?= $tugas_detail[0]['updated_at'] ?? '-' ?></div>
    </div>
    <?php endif; ?>

    <td class="px-4 py-4">
        <button type="submit" name="simpan_siswa" value="<?= $siswa['id'] ?>" class="bg-green-500 text-white px-2 py-1 rounded text-xs">Simpan</button>
    </td>

<!-- Diinput oleh -->
<td class="text-sm text-gray-700">
    <?= isset($histori['guru_input']) ? $histori['guru_input'] : '-' ?>
</td>

<!-- Waktu input -->
<td class="text-sm text-gray-700">
    <?= isset($histori['created_at']) ? date('d/m/Y H:i', strtotime($histori['created_at'])) : '-' ?>
</td>

<!-- Diupdate oleh -->
<td class="text-sm text-gray-700">
    <?= isset($histori['guru_update']) && $histori['guru_update'] !== ($histori['guru_input'] ?? '') ? $histori['guru_update'] : '-' ?>
</td>

<!-- Update terakhir -->
<td class="text-sm text-gray-700">
    <?= isset($histori['updated_at']) ? date('d/m/Y H:i', strtotime($histori['updated_at'])) : '-' ?>
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
function tambahUH(siswaId) {
    const container = document.getElementById(`uh-container-${siswaId}`);
    const count = container.querySelectorAll('input').length + 1;
    const div = document.createElement('div');
    div.classList.add('mb-1');
    div.innerHTML = `
        <label class="text-xs">UH ke-${count}</label>
        <input type="number" name="uh[${siswaId}][]" step="0.01" class="w-20 text-center border rounded bg-yellow-100">
    `;
    container.appendChild(div);
}

function tambahTugas(siswaId) {
    const container = document.getElementById(`tugas-container-${siswaId}`);
    const count = container.querySelectorAll('input').length + 1;
    const div = document.createElement('div');
    div.classList.add('mb-1');
    div.innerHTML = `
        <label class="text-xs">Tugas ke-${count}</label>
        <input type="number" name="tugas[${siswaId}][]" step="0.01" class="w-20 text-center border rounded bg-yellow-100">
    `;
    container.appendChild(div);
}
</script>

<?php require_once 'partials/footer.php'; ?>
