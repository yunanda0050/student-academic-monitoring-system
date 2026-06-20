<?php
session_start();
require_once 'config/koneksi.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$kelas_id = isset($_GET['kelas_id']) ? $_GET['kelas_id'] : '';
$mapel_id = isset($_GET['mapel_id']) ? $_GET['mapel_id'] : '';
$id_siswa = isset($_GET['id_siswa']) ? $_GET['id_siswa'] : '';

$where_conditions = [];
$params = [];

if (!empty($kelas_id)) {
    $where_conditions[] = "s.kelas_id = ?";
    $params[] = $kelas_id;
}

if (!empty($mapel_id)) {
    $where_conditions[] = "n.id_mapel = ?";
    $params[] = $mapel_id;
}

if (!empty($id_siswa)) { // <-- Tambahkan ini
    $where_conditions[] = "s.id = ?";
    $params[] = $id_siswa;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Query data nilai
$query = "
    SELECT 
        k.nama_kelas,
        k.sub_kelas,
        s.nis,
        s.nama AS nama_siswa,
        s.jenis_kelamin,
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
    JOIN mapel m ON n.id_mapel = m.id_mapel
    $where_clause
    ORDER BY n.id_nilai DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$data_nilai = $stmt->fetchAll();

// Header untuk Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Laporan_Nilai_' . date('d-m-Y') . '.xls"');
header('Cache-Control: max-age=0');

?>

<table border="1">
    <thead>
        <tr>
            <th colspan="12" style="text-align: center; font-size: 14pt; font-weight: bold;">
                Laporan Nilai Siswa
            </th>
        </tr>
        <tr>
            <th style="background-color: #f0f0f0;">No</th>
            <th style="background-color: #f0f0f0;">Kelas</th>
            <th style="background-color: #f0f0f0;">Mapel</th>
            <th style="background-color: #f0f0f0;">NIS</th>
            <th style="background-color: #f0f0f0;">Nama</th>
            <th style="background-color: #f0f0f0;">JK</th>
            <th style="background-color: #f0f0f0;">Absen</th>
            <th style="background-color: #f0f0f0;">Tugas</th>
            <th style="background-color: #f0f0f0;">UTS</th>
            <th style="background-color: #f0f0f0;">UAS</th>
            <th style="background-color: #f0f0f0;">Total</th>
            <th style="background-color: #f0f0f0;">Grade</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($data_nilai)): ?>
            <tr><td colspan="12" style="text-align: center;">Tidak ada data ditemukan.</td></tr>
        <?php else: ?>
            <?php $no = 1; foreach ($data_nilai as $row): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= 'Kelas ' . htmlspecialchars($row['nama_kelas']) . '-' . htmlspecialchars($row['sub_kelas']) ?></td>
                    <td><?= htmlspecialchars($row['mapel']) ?></td>
                    <td><?= htmlspecialchars($row['nis']) ?></td>
                    <td><?= htmlspecialchars($row['nama_siswa']) ?></td>
                    <td><?= htmlspecialchars($row['jenis_kelamin']) ?></td>
                    <td><?= $row['absen'] ?></td>
                    <td><?= $row['tugas'] ?></td>
                    <td><?= $row['uts'] ?></td>
                    <td><?= $row['uas'] ?></td>
                    <td><?= $row['total'] ?></td>
                    <td><?= $row['grade'] ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="12">&nbsp;</td>
        </tr>
        <tr>
            <td colspan="12" style="text-align: right;">
                Dicetak pada: <?= date('d/m/Y H:i:s') ?>
            </td>
        </tr>
    </tfoot>
</table>


<?php
function getStatusStyle($status) {
    switch($status) {
        case 'hadir':
            return 'background-color: #dcfce7; color: #166534;'; // Green
        case 'sakit':
            return 'background-color: #fef9c3; color: #854d0e;'; // Yellow
        case 'izin':
            return 'background-color: #dbeafe; color: #1e40af;'; // Blue
        case 'alpha':
            return 'background-color: #fee2e2; color: #991b1b;'; // Red
        default:
            return '';
    }
} 