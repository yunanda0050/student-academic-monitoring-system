<?php
session_start();

// Cek login
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'superadmin') {
    header("Location: login.php");
    exit;
}

require_once 'config/koneksi.php';

// Proses tambah/edit/hapus
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if($_POST['action'] == 'tambah') {
        $stmt = $pdo->prepare("INSERT INTO mapel (mapel) VALUES (?)");
        $stmt->execute([$_POST['mapel']]);
        $_SESSION['success'] = "Mata pelajaran berhasil ditambahkan!";
    } elseif($_POST['action'] == 'edit') {
        $stmt = $pdo->prepare("UPDATE mapel SET mapel = ? WHERE id_mapel = ?");
        $stmt->execute([$_POST['mapel'], $_POST['id_mapel']]);
        $_SESSION['success'] = "Mata pelajaran berhasil diperbarui!";
    } elseif($_POST['action'] == 'hapus') {
        $stmt = $pdo->prepare("DELETE FROM mapel WHERE id_mapel = ?");
        $stmt->execute([$_POST['id_mapel']]);
        $_SESSION['success'] = "Mata pelajaran berhasil dihapus!";
    }
    header("Location: mapel.php");
    exit;
}

require_once 'partials/header.php';
require_once 'partials/navbar.php';

// Ambil data mapel
$stmt = $pdo->query("SELECT * FROM mapel ORDER BY id_mapel ASC");
$mapel_list = $stmt->fetchAll();
?>

<div class="md:pl-64 pb-safe">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Daftar Mata Pelajaran</h1>
            <button onclick="document.getElementById('modalTambah').classList.remove('hidden')" 
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-plus mr-2"></i>Tambah Mapel
            </button>
        </div>

        <?php if(isset($_SESSION['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6">
            <span><?= $_SESSION['success'] ?></span>
            <?php unset($_SESSION['success']); ?>
        </div>
        <?php endif; ?>

        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mata Pelajaran</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if(empty($mapel_list)): ?>
                    <tr>
                        <td colspan="3" class="px-6 py-4 text-center text-gray-500">Belum ada data mata pelajaran</td>
                    </tr>
                    <?php else: ?>
                    <?php $no = 1; foreach($mapel_list as $mapel): ?>
<tr>
    <td class="px-6 py-4"><?= $no++ ?></td>

                        <td class="px-6 py-4"><?= htmlspecialchars($mapel['mapel']) ?></td>
                        <td class="px-6 py-4">
                            <button onclick="editMapel(<?= htmlspecialchars(json_encode($mapel)) ?>)"
                                class="text-blue-600 hover:text-blue-900 mr-3">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button onclick="hapusMapel(<?= $mapel['id_mapel'] ?>, '<?= htmlspecialchars($mapel['mapel']) ?>')"
                                class="text-red-600 hover:text-red-900">
                                <i class="fas fa-trash"></i> Hapus
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Mapel -->
<div id="modalTambah" class="fixed z-50 inset-0 hidden">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75"></div>
        <div class="bg-white rounded-lg overflow-hidden shadow-xl transform sm:max-w-lg sm:w-full p-6">
            <form method="POST">
                <input type="hidden" name="action" value="tambah">
                <h3 class="text-lg font-bold mb-4">Tambah Mata Pelajaran</h3>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Nama Mapel</label>
                    <input type="text" name="mapel" required
                        class="w-full border rounded px-3 py-2">
                </div>
                <div class="flex justify-end">
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded mr-2">Simpan</button>
                    <button type="button"
                        onclick="document.getElementById('modalTambah').classList.add('hidden')"
                        class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Mapel -->
<div id="modalEdit" class="fixed z-50 inset-0 hidden">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75"></div>
        <div class="bg-white rounded-lg overflow-hidden shadow-xl transform sm:max-w-lg sm:w-full p-6">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id_mapel" id="edit_id_mapel">
                <h3 class="text-lg font-bold mb-4">Edit Mata Pelajaran</h3>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Nama Mapel</label>
                    <input type="text" name="mapel" id="edit_mapel" required
                        class="w-full border rounded px-3 py-2">
                </div>
                <div class="flex justify-end">
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded mr-2">Update</button>
                    <button type="button"
                        onclick="document.getElementById('modalEdit').classList.add('hidden')"
                        class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Form Hapus -->
<form id="formHapus" method="POST" class="hidden">
    <input type="hidden" name="action" value="hapus">
    <input type="hidden" name="id_mapel" id="hapus_id_mapel">
</form>

<script>
function editMapel(mapel) {
    document.getElementById('edit_id_mapel').value = mapel.id_mapel;
    document.getElementById('edit_mapel').value = mapel.mapel;
    document.getElementById('modalEdit').classList.remove('hidden');
}

function hapusMapel(id, nama) {
    if (confirm('Yakin ingin menghapus mapel "' + nama + '"?')) {
        document.getElementById('hapus_id_mapel').value = id;
        document.getElementById('formHapus').submit();
    }
}
</script>

<?php require_once 'partials/footer.php'; ?>
