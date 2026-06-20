<?php
session_start();

// Cek login dan role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'superadmin') {
    header("Location: login.php");
    exit;
}

require_once 'config/koneksi.php';

// ==== Ambil filter ====
$tahun_filter = $_GET['tahun_filter'] ?? '';
$kelas_filter = $_GET['kelas_filter'] ?? '';
$guru_filter  = $_GET['guru_filter'] ?? '';

// Ambil semua tahun ajaran
$tahun_stmt = $pdo->query("SELECT id_tahun, tahun FROM tahun_ajaran ORDER BY tahun DESC");
$tahun_list = $tahun_stmt->fetchAll();

// Ambil daftar kelas sesuai tahun ajaran
$kelas_list_dropdown = [];
if (!empty($tahun_filter)) {
    $stmt = $pdo->prepare("
        SELECT DISTINCT k.id, k.nama_kelas, k.sub_kelas
        FROM kelas k
        JOIN siswa s ON s.kelas_id = k.id
        WHERE s.id_tahun = ?
        ORDER BY CAST(k.nama_kelas AS UNSIGNED), CAST(k.sub_kelas AS UNSIGNED)
    ");
    $stmt->execute([$tahun_filter]);
    $kelas_list_dropdown = $stmt->fetchAll();
}

// Ambil daftar wali kelas sesuai kelas
$guru_list_dropdown = [];
if (!empty($kelas_filter)) {
    $stmt = $pdo->prepare("
        SELECT DISTINCT g.id, g.nama
        FROM guru g
        JOIN kelas k ON k.guru_id = g.id
        WHERE k.id = ?
    ");
    $stmt->execute([$kelas_filter]);
    $guru_list_dropdown = $stmt->fetchAll();
}


// === Ambil data kelas untuk ditampilkan di tabel utama ===
$params = [];
$query = "
    SELECT 
        k.*, 
        g.nama AS nama_guru,
        (SELECT tahun FROM tahun_ajaran ta 
            WHERE ta.id_tahun = (
                SELECT s.id_tahun FROM siswa s WHERE s.kelas_id = k.id LIMIT 1
            )
        ) AS tahun_ajaran,
        (SELECT COUNT(*) FROM siswa s2 WHERE s2.kelas_id = k.id) AS total_siswa 
    FROM kelas k 
    LEFT JOIN guru g ON k.guru_id = g.id 
    WHERE 1=1
";

if (!empty($tahun_filter)) {
    $query .= " AND EXISTS (SELECT 1 FROM siswa s3 WHERE s3.kelas_id = k.id AND s3.id_tahun = ?)";
    $params[] = $tahun_filter;
}
if (!empty($kelas_filter)) {
    $query .= " AND k.id = ?";
    $params[] = $kelas_filter;
}
if (!empty($guru_filter)) {
    $query .= " AND k.guru_id = ?";
    $params[] = $guru_filter;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$kelas_list = $stmt->fetchAll();

// === Proses Tambah/Edit/Hapus Kelas ===
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'tambah') {
        $stmt = $pdo->prepare("INSERT INTO kelas (nama_kelas, sub_kelas, guru_id) VALUES (?, ?, ?)");
        $stmt->execute([$_POST['nama_kelas'], $_POST['sub_kelas'], $_POST['guru_id']]);
        $_SESSION['success'] = "Data kelas berhasil ditambahkan!";
    } elseif ($_POST['action'] == 'edit') {
        $stmt = $pdo->prepare("UPDATE kelas SET nama_kelas = ?, sub_kelas = ?, guru_id = ? WHERE id = ?");
        $stmt->execute([$_POST['nama_kelas'], $_POST['sub_kelas'], $_POST['guru_id'], $_POST['id']]);
        $_SESSION['success'] = "Data kelas berhasil diperbarui!";
    } elseif ($_POST['action'] == 'hapus') {
        $stmt = $pdo->prepare("DELETE FROM kelas WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        $_SESSION['success'] = "Data kelas berhasil dihapus!";
    }
    header("Location: kelas.php");
    exit;
}

// Ambil data guru untuk dropdown
$stmt = $pdo->query("SELECT id, nama FROM guru WHERE role = 'guru' ORDER BY nama");
$guru_list = $stmt->fetchAll();

// === Include tampilan ===
require_once 'partials/header.php';
require_once 'partials/navbar.php';
?>

<!-- Main Content -->
<div class="md:pl-64 pb-safe">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Daftar Kelas</h1>
            <button onclick="document.getElementById('modalTambah').classList.remove('hidden')" 
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-plus mr-2"></i>Tambah Kelas
            </button>
        </div>

        <?php if(isset($_SESSION['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
            <span class="block sm:inline"><?= $_SESSION['success'] ?></span>
            <button class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="this.parentElement.style.display='none'">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <?php 
            unset($_SESSION['success']);
        endif; 
        ?>

<!-- Filter Dropdown Bertahap -->
<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">

        <!-- Tahun Ajaran -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tahun Ajaran</label>
            <select name="tahun_filter" onchange="this.form.submit()" required
                class="w-full rounded border-gray-300 px-3 py-2">
                <option value="">-- Pilih Tahun Ajaran --</option>
                <?php foreach($tahun_list as $t): ?>
                    <option value="<?= $t['id_tahun'] ?>" <?= $tahun_filter == $t['id_tahun'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['tahun']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Kelas -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Kelas</label>
            <select name="kelas_filter" onchange="this.form.submit()" <?= empty($tahun_filter) ? 'disabled' : '' ?>
                class="w-full rounded border-gray-300 px-3 py-2">
                <option value="">-- Pilih Kelas --</option>
                <?php foreach($kelas_list_dropdown as $k): ?>
                    <option value="<?= $k['id'] ?>" <?= $kelas_filter == $k['id'] ? 'selected' : '' ?>>
                        <?= 'Kelas ' . $k['nama_kelas'] . '-' . $k['sub_kelas'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Wali Kelas -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Wali Kelas</label>
            <select name="guru_filter" onchange="this.form.submit()" <?= empty($kelas_filter) ? 'disabled' : '' ?>
                class="w-full rounded border-gray-300 px-3 py-2">
                <option value="">-- Semua Wali Kelas --</option>
                <?php foreach($guru_list_dropdown as $g): ?>
                    <option value="<?= $g['id'] ?>" <?= $guru_filter == $g['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($g['nama']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>
        
<!-- Tabel Kelas -->
<?php
$kelas_group = [];
foreach ($kelas_list as $k) {
    $kelas_group[$k['nama_kelas']][] = $k;
}
?>

<div class="space-y-4">
    <?php foreach ($kelas_group as $nama_kelas => $sub_kelas_list): ?>
        <div class="border border-gray-300 rounded">
            <button type="button" 
                    class="w-full text-left px-4 py-2 bg-blue-100 hover:bg-blue-200 font-semibold text-gray-700 focus:outline-none"
                    onclick="toggleSubkelas('subkelas-<?= $nama_kelas ?>')">
                Kelas <?= htmlspecialchars($nama_kelas) ?>
            </button>
            <div id="subkelas-<?= $nama_kelas ?>" class="hidden px-4 pb-4">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Sub Kelas</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Wali Kelas</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tahun Ajaran</th> <!-- Tambahan -->
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Total Siswa</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($sub_kelas_list as $kelas): ?>
                        <tr>
                            <td class="px-4 py-2"><?= 'Kelas ' . htmlspecialchars($kelas['nama_kelas']) . '-' . htmlspecialchars($kelas['sub_kelas']) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($kelas['nama_guru'] ?? '-') ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($kelas['tahun_ajaran'] ?? '-') ?></td> <!-- Tambahan -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <span class="inline-flex items-center justify-center px-3 py-1 rounded-full bg-blue-100 text-blue-800">
                                    <?= $kelas['total_siswa'] ?> Siswa
                                </span>
                            </td>
                            <td class="px-4 py-2">
                                <button onclick="editKelas(<?= htmlspecialchars(json_encode($kelas)) ?>)"
                                        class="text-blue-600 hover:text-blue-900 mr-2">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button onclick="hapusKelas(<?= $kelas['id'] ?>, '<?= htmlspecialchars($kelas['nama_kelas']) ?>')"
                                        class="text-red-600 hover:text-red-900">
                                    <i class="fas fa-trash"></i> Hapus
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                </table>
                <!-- Tirai Mapel untuk Kelas <?= $nama_kelas ?> -->
<div class="mt-4">
    <button type="button"
    onclick="toggleMapel('mapel-<?= $kelas['id'] ?>')"

        class="bg-green-100 hover:bg-green-200 px-4 py-2 rounded text-sm font-semibold text-gray-800">
        Lihat Mata Pelajaran Kelas <?= htmlspecialchars($nama_kelas) ?>
    </button>

<button type="button"
    onclick="bukaFormMapel(<?= $kelas['id'] ?>)"
    class="bg-blue-100 hover:bg-blue-200 px-4 py-2 rounded text-sm font-semibold text-blue-800">
    + Tambah Mapel
</button>

<button type="button"
    onclick="bukaEditMapel(<?= $kelas['id'] ?>)"
    class="bg-yellow-100 hover:bg-yellow-200 px-4 py-2 rounded text-sm font-semibold text-yellow-800">
    ✎ Edit Mapel
</button>

<div id="mapel-<?= $kelas['id'] ?>" class="hidden mt-2">
<?php
    $stmt_mapel = $pdo->prepare("
        SELECT 
            p.id_pel,
            m.mapel
        FROM pelajaran p
        JOIN mapel m ON p.id_mapel = m.id_mapel
        WHERE p.id = ?
        ORDER BY m.mapel
    ");
    $stmt_mapel->execute([$kelas['id']]);
    $mapel_list = $stmt_mapel->fetchAll();
?>


    <?php if (count($mapel_list) > 0): ?>
        <ul class="list-disc ml-6 text-gray-700">
            <?php foreach ($mapel_list as $mapel): ?>
                <li><?= htmlspecialchars($mapel['mapel']) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="text-sm text-gray-500">Belum ada mata pelajaran untuk kelas ini.</p>
    <?php endif; ?>
</div>

</div>

            </div>
        </div>
    <?php endforeach; ?>
</div>

    </div>
</div>

<!-- Modal Tambah Mapel -->
<div id="modalMapel" class="fixed inset-0 bg-black bg-opacity-30 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
        <form method="POST" action="tambah_mapel_kelas.php">
            <input type="hidden" name="id_kelas" id="modal_id_kelas">

            <h2 class="text-lg font-semibold mb-4">Tambah Mapel ke Kelas</h2>

            <label class="block text-sm font-medium mb-1">Pilih Mapel</label>
            <select name="id_mapel" required class="w-full border rounded px-3 py-2 mb-4">
                <?php
                $mapel_all = $pdo->query("SELECT id_mapel, mapel FROM mapel ORDER BY mapel")->fetchAll();
                foreach ($mapel_all as $m) {
                    echo '<option value="'.htmlspecialchars($m['id_mapel']).'">'.htmlspecialchars($m['mapel']).'</option>';
                }
                ?>
            </select>

            <div class="flex justify-end">
                <button type="button" onclick="tutupModalMapel()" class="mr-2 px-4 py-2 rounded bg-gray-300 hover:bg-gray-400">Batal</button>
                <button type="submit" class="px-4 py-2 rounded bg-blue-500 text-white hover:bg-blue-600">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Mapel (Multi Pilih) -->
<div id="modalEditMapel" class="fixed inset-0 bg-black bg-opacity-30 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-xl p-6 overflow-auto max-h-screen">
        <form method="POST" action="edit_mapel_kelas.php">
            <input type="hidden" name="id_kelas" id="edit_multi_id_kelas">

            <h2 class="text-lg font-semibold mb-4">Edit Mapel untuk Kelas Ini</h2>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Pilih Mapel Baru (Checklist mapel yang ingin diubah)</label>
                <div class="grid grid-cols-2 gap-2 max-h-64 overflow-y-auto border rounded p-2">
                    <?php
                    $mapel_all = $pdo->query("SELECT id_mapel, mapel FROM mapel ORDER BY mapel")->fetchAll();
                    foreach ($mapel_all as $m) {
                        echo '<label class="flex items-center space-x-2">';
                        echo '<input type="checkbox" name="selected_mapel_ids[]" value="'.htmlspecialchars($m['id_mapel']).'" class="form-checkbox h-4 w-4">';
                        echo '<span>'.htmlspecialchars($m['mapel']).'</span>';
                        echo '</label>';
                    }
                    ?>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="button" onclick="tutupModalEditMapel()" class="mr-2 px-4 py-2 rounded bg-gray-300 hover:bg-gray-400">Batal</button>
                <button type="submit" class="px-4 py-2 rounded bg-yellow-500 text-white hover:bg-yellow-600">Update Mapel</button>
            </div>
        </form>
    </div>
</div>



<!-- Modal Tambah Kelas -->
<div id="modalTambah" class="fixed z-50 inset-0 hidden">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="relative bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full">
            <form method="POST">
                <input type="hidden" name="action" value="tambah">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Tambah Kelas Baru</h3>
<div class="mb-4">
    <label class="block text-gray-700 text-sm font-bold mb-2" for="nama_kelas">
        Nama Kelas
    </label>
    <select name="nama_kelas" id="nama_kelas" required
            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-white">
        <option value="">-- Pilih Kelas --</option>
        <option value="9">Kelas 9</option>
        <option value="8">Kelas 8</option>
        <option value="7">Kelas 7</option>
    </select>
</div>
                                        <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="sub_kelas">
                            Sub Kelas
                        </label>
                        <input type="text" name="sub_kelas" id="sub_kelas" required
       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">

                            </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="guru_id">
                            Wali Kelas
                        </label>
                        <select name="guru_id" id="guru_id" required
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <option value="">Pilih Wali Kelas</option>
                                <?php foreach($guru_list as $guru): ?>
                                    <option value="<?= $guru['id'] ?>"><?= htmlspecialchars($guru['nama']) ?></option>
                                <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Simpan
                    </button>
                    <button type="button" onclick="document.getElementById('modalTambah').classList.add('hidden')" 
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Kelas -->
<div id="modalEdit" class="fixed z-50 inset-0 hidden">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="relative bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Edit Kelas</h3>
<div class="mb-4">
    <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_nama_kelas">
        Nama Kelas
    </label>
    <select name="nama_kelas" id="edit_nama_kelas" required
            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-white">
        <option value="">-- Pilih Kelas --</option>
<option value="9">Kelas 9</option>
<option value="8">Kelas 8</option>
<option value="7">Kelas 7</option>

    </select>
</div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_sub_kelas">
                            Sub Kelas
                        </label>
                        <input type="text" name="sub_kelas" id="edit_sub_kelas" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_guru_id">
                            Wali Kelas
                        </label>
                        <select name="guru_id" id="edit_guru_id" required
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <option value="">Pilih Wali Kelas</option>
                                <?php foreach($guru_list as $guru): ?>
                                    <option value="<?= $guru['id'] ?>"><?= htmlspecialchars($guru['nama']) ?></option>
                                <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Update
                    </button>
                    <button type="button" onclick="document.getElementById('modalEdit').classList.add('hidden')" 
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Form Hapus (Hidden) -->
<form id="formHapus" method="POST" class="hidden">
    <input type="hidden" name="action" value="hapus">
    <input type="hidden" name="id" id="hapus_id">
</form>

<script>
document.querySelector('[name="tahun_filter"]').addEventListener('change', function () {
    if (!this.value) {
        document.querySelector('[name="kelas_filter"]').value = '';
        document.querySelector('[name="kelas_filter"]').disabled = true;

        document.querySelector('[name="guru_filter"]').value = '';
        document.querySelector('[name="guru_filter"]').disabled = true;
    } else {
        document.querySelector('[name="kelas_filter"]').disabled = false;
    }
});
</script>

<script>
function editKelas(data) {
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_nama_kelas').value = data.nama_kelas;
    document.getElementById('edit_sub_kelas').value = data.sub_kelas;

    // Set dropdown guru_id
    const guruSelect = document.getElementById('edit_guru_id');
    for (let i = 0; i < guruSelect.options.length; i++) {
        if (guruSelect.options[i].value == data.guru_id) {
            guruSelect.options[i].selected = true;
            break;
        }
    }

    // Tampilkan modal
    document.getElementById('modalEdit').classList.remove('hidden');
}
</script>

<script>
function editKelas(kelas) {
    document.getElementById('edit_id').value = kelas.id;
    document.getElementById('edit_nama_kelas').value = kelas.nama_kelas;
    document.getElementById('edit_sub_kelas').value = kelas.sub_kelas;
    document.getElementById('edit_guru_id').value = kelas.guru_id || '';
    document.getElementById('modalEdit').classList.remove('hidden');
}


function hapusKelas(id, nama) {
    if(confirm('Apakah Anda yakin ingin menghapus kelas "' + nama + '"?')) {
        document.getElementById('hapus_id').value = id;
        document.getElementById('formHapus').submit();
    }
}

function toggleSubkelas(id) {
    const el = document.getElementById(id);
    el.classList.toggle('hidden');
}
function toggleMapel(id) {
    const el = document.getElementById(id);
    el.classList.toggle('hidden');
}

function bukaFormMapel(idKelas) {
    document.getElementById('modal_id_kelas').value = idKelas;
    document.getElementById('modalMapel').classList.remove('hidden');
    document.getElementById('modalMapel').classList.add('flex');
}

function tutupModalMapel() {
    document.getElementById('modalMapel').classList.add('hidden');
    document.getElementById('modalMapel').classList.remove('flex');
}

function bukaEditMapel(idKelas) {
    document.getElementById('edit_multi_id_kelas').value = idKelas;
    document.getElementById('modalEditMapel').classList.remove('hidden');
    document.getElementById('modalEditMapel').classList.add('flex');
}

function tutupModalEditMapel() {
    document.getElementById('modalEditMapel').classList.add('hidden');
    document.getElementById('modalEditMapel').classList.remove('flex');
}
</script>

<?php require_once 'partials/footer.php'; ?> 