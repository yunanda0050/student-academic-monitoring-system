<?php
require_once 'config/koneksi.php';
require_once 'partials/header.php';

// Ambil data guru wali kelas dari tabel guru
$stmt = $pdo->query("SELECT g.nama AS nama_wali, g.phone, k.nama_kelas, k.sub_kelas 
    FROM guru g
    JOIN kelas k ON g.id = k.guru_id
    ORDER BY k.nama_kelas, k.sub_kelas");
$wali_list = $stmt->fetchAll();
?>

<div class="container mx-auto px-4 py-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Kontak Wali Kelas</h2>
    <div class="mb-4">
    <a href="monitoring.php"
       class="inline-block px-4 py-2 bg-[#191970] text-white text-sm font-semibold rounded-lg shadow hover:bg-[#4169e1] transition duration-200">
        <i class="fas fa-arrow-left mr-2"></i> Kembali 
    </a>
</div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full table-auto">
            <thead class="bg-[#4169e1] text-white mb1">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold">Kelas</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold">Nama Wali Kelas</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold">No Telepon</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($wali_list as $wali): ?>
                    <tr class="hover:bg-[#add8e6]">
                        <td class="px-6 py-4"><?= 'Kelas ' . htmlspecialchars($wali['nama_kelas']) . '-' . htmlspecialchars($wali['sub_kelas']) ?></td>
                        <td class="px-6 py-4"><?= htmlspecialchars($wali['nama_wali']) ?></td>
                        <td class="px-6 py-4">
    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $wali['phone']) ?>"
       target="_blank"
       class="text-green-600 hover:text-green-800 flex items-center space-x-2">
        <i class="fab fa-whatsapp text-lg"></i>
        <span><?= htmlspecialchars($wali['phone']) ?></span>
    </a>
</td>

                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'partials/footer.php'; ?>
