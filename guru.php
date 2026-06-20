<?php
session_start();

// Cek apakah user sudah login
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Cek apakah user adalah superadmin
if($_SESSION['role'] != 'superadmin') {
    header("Location: dashboard.php");
    exit;
}

require_once 'config/koneksi.php';

// Proses tambah/edit guru
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['action'])) {
        if($_POST['action'] == 'tambah') {
            // Cek apakah username sudah ada
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM guru WHERE username = ?");
            $stmt->execute([$_POST['username']]);
            $count = $stmt->fetchColumn();
            
            if($count > 0) {
                $_SESSION['error'] = "Username '{$_POST['username']}' sudah digunakan. Silakan pilih username lain.";
            } else {
                // Hash password
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                
                try {
                    $stmt = $pdo->prepare("INSERT INTO guru (nip, nama, jenis_kelamin, username, phone, password, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['nip'],
                        $_POST['nama'],
                        $_POST['jenis_kelamin'],
                        $_POST['username'],
                        $_POST['phone'],
                        $password,
                        'guru'
                    ]);
                    $_SESSION['success'] = "Data guru berhasil ditambahkan!";
                } catch(PDOException $e) {
                    $_SESSION['error'] = "Gagal menambahkan data guru. Silakan coba lagi.";
                }
            }
        } elseif($_POST['action'] == 'edit') {
            // Cek apakah username sudah ada (kecuali untuk guru yang sedang diedit)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM guru WHERE username = ? AND id != ?");
            $stmt->execute([$_POST['username'], $_POST['id']]);
            $count = $stmt->fetchColumn();
            
            if($count > 0) {
                $_SESSION['error'] = "Username '{$_POST['username']}' sudah digunakan. Silakan pilih username lain.";
            } else {
                try {
                    if(!empty($_POST['password'])) {
                        // Update dengan password baru
                        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE guru SET nip = ?, nama = ?, jenis_kelamin = ?, username = ?, phone = ?, password = ? WHERE id = ?");
                        $stmt->execute([
                            $_POST['nip'],
                            $_POST['nama'],
                            $_POST['jenis_kelamin'],
                            $_POST['username'],
                            $_POST['phone'],
                            $password,
                            $_POST['id']
                        ]);
                    } else {
                        // Update tanpa password
                        $stmt = $pdo->prepare("UPDATE guru SET nip = ?, nama = ?, jenis_kelamin = ?, username = ?, phone = ? WHERE id = ?");
                        $stmt->execute([
                            $_POST['nip'],
                            $_POST['nama'],
                            $_POST['jenis_kelamin'],
                            $_POST['username'],
                            $_POST['phone'],
                            $_POST['id']
                        ]);
                    }
                    $_SESSION['success'] = "Data guru berhasil diperbarui!";
                } catch(PDOException $e) {
                    $_SESSION['error'] = "Gagal memperbarui data guru. Silakan coba lagi.";
                }
            }
        } elseif($_POST['action'] == 'hapus') {
            try {
                $stmt = $pdo->prepare("DELETE FROM guru WHERE id = ? AND role != 'superadmin'");
                $stmt->execute([$_POST['id']]);
                $_SESSION['success'] = "Data guru berhasil dihapus!";
            } catch(PDOException $e) {
                $_SESSION['error'] = "Gagal menghapus data guru. Silakan coba lagi.";
            }
        }
        header("Location: guru.php");
        exit;
    }
}

// Setelah semua logika, baru include header dan navbar
require_once 'partials/header.php';
require_once 'partials/navbar.php';

// Ambil data guru untuk dropdown
$stmt = $pdo->query("SELECT id, nama, phone FROM guru WHERE role = 'guru' ORDER BY nama");
$guru_list = $stmt->fetchAll();

// Filter pencarian
$search = isset($_GET['search']) ? $_GET['search'] : '';
$jk_filter = isset($_GET['jk_filter']) ? $_GET['jk_filter'] : '';

// Query untuk mendapatkan data guru dengan filter
$query = "
    SELECT * FROM guru 
    WHERE role = 'guru'
";

$params = [];

if (!empty($search)) {
    $query .= " AND (nama LIKE ? OR nip LIKE ? OR username LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($jk_filter)) {
    $query .= " AND jenis_kelamin = ?";
    $params[] = $jk_filter;
}

$query .= " ORDER BY nama";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$guru_list = $stmt->fetchAll();
?>

<!-- Main Content -->
<div class="md:pl-64 pb-safe">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Daftar Guru</h1>
            <button onclick="document.getElementById('modalTambah').classList.remove('hidden')" 
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-plus mr-2"></i>Tambah Guru
            </button>
        </div>
        
        <?php if(isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
            <span class="block sm:inline"><?= $_SESSION['error'] ?></span>
            <button class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="this.parentElement.style.display='none'">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <?php 
            unset($_SESSION['error']);
        endif; 
        ?>
        
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
        
        <!-- Tabel Guru -->
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <div class="table-responsive">
                <table class="min-w-full divide-y divide-gray-200 table-responsive-stack">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NIP</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">JK</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No Telepon</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach($guru_list as $guru): ?>
                            <tr>
                                <td data-label="NIP" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($guru['nip']) ?>
                                </td>
                                <td data-label="Nama" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($guru['nama']) ?>
                                </td>
                                <td data-label="JK" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= $guru['jenis_kelamin'] ?>
                                </td>
                                <td data-label="Username" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($guru['username']) ?>
                                </td>
                                <td data-label="No Telepon" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($guru['phone']) ?>
                                </td>
                                <td data-label="Aksi" class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="editGuru(<?= htmlspecialchars(json_encode($guru)) ?>)" 
                                            class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button onclick="hapusGuru(<?= $guru['id'] ?>, '<?= htmlspecialchars($guru['nama']) ?>')" 
                                            class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i> Hapus
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Guru -->
<div id="modalTambah" class="fixed z-50 inset-0 hidden">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="relative bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full">
            <form method="POST">
                <input type="hidden" name="action" value="tambah">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Tambah Guru Baru</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="nip">NIP</label>
                            <input type="text" name="nip" id="nip" required
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="username">Username</label>
                            <input type="text" name="username" id="username" required
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="nama">Nama Lengkap</label>
                        <input type="text" name="nama" id="nama" required
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                                        <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="phone">No Telepon</label>
                        <input type="text" name="phone" id="phone" required
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Jenis Kelamin</label>
                            <div class="mt-2">
                                <label class="inline-flex items-center">
                                    <input type="radio" name="jenis_kelamin" value="L" required class="form-radio">
                                    <span class="ml-2">Laki-laki</span>
                                </label>
                                <label class="inline-flex items-center ml-6">
                                    <input type="radio" name="jenis_kelamin" value="P" required class="form-radio">
                                    <span class="ml-2">Perempuan</span>
                                </label>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="password">Password</label>
                            <input type="password" name="password" id="password" required
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
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

<!-- Modal Edit Guru -->
<div id="modalEdit" class="fixed z-50 inset-0 hidden">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="relative bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Edit Guru</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_nip">NIP</label>
                            <input type="text" name="nip" id="edit_nip" required
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_username">Username</label>
                            <input type="text" name="username" id="edit_username" required
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_nama">Nama Lengkap</label>
                        <input type="text" name="nama" id="edit_nama" required
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_nama">No Telepon</label>
                        <input type="text" name="phone" id="edit_phone" required
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Jenis Kelamin</label>
                            <div class="mt-2">
                                <label class="inline-flex items-center">
                                    <input type="radio" name="jenis_kelamin" value="L" id="edit_jk_l" required class="form-radio">
                                    <span class="ml-2">Laki-laki</span>
                                </label>
                                <label class="inline-flex items-center ml-6">
                                    <input type="radio" name="jenis_kelamin" value="P" id="edit_jk_p" required class="form-radio">
                                    <span class="ml-2">Perempuan</span>
                                </label>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_password">
                                Password Baru (Kosongkan jika tidak diubah)
                            </label>
                            <input type="password" name="password" id="edit_password"
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
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
function editGuru(guru) {
    document.getElementById('edit_id').value = guru.id;
    document.getElementById('edit_nip').value = guru.nip;
    document.getElementById('edit_nama').value = guru.nama;
    document.getElementById('edit_username').value = guru.username;
    document.getElementById('edit_phone').value = guru.phone;
    
    if(guru.jenis_kelamin == 'L') {
        document.getElementById('edit_jk_l').checked = true;
    } else {
        document.getElementById('edit_jk_p').checked = true;
    }
    
    document.getElementById('modalEdit').classList.remove('hidden');
}

function hapusGuru(id, nama) {
    if(confirm('Apakah Anda yakin ingin menghapus data guru "' + nama + '"?')) {
        document.getElementById('hapus_id').value = id;
        document.getElementById('formHapus').submit();
    }
}
</script>

<?php require_once 'partials/footer.php'; ?> 