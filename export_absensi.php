<?php
session_start();
require_once 'config/koneksi.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-d');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d');
$kelas_id = isset($_GET['kelas_id']) ? $_GET['kelas_id'] : '';
$tahun_ajaran = isset($_GET['tahun_ajaran']) ? $_GET['tahun_ajaran'] : '-';

$where_conditions = [];
$params = [];

if (!empty($kelas_id)) {
    $where_conditions[] = "s.kelas_id = ?";
    $params[] = $kelas_id;
}

if (!empty($_GET['mapel_id'])) {
    $where_conditions[] = "p.id_mapel = ?";
    $params[] = $_GET['mapel_id'];
}

if (!empty($_GET['id_siswa'])) {
    $where_conditions[] = "s.id = ?";
    $params[] = $_GET['id_siswa'];
}

$where_conditions[] = "DATE(a.tanggal) BETWEEN ? AND ?";
$params[] = $tanggal_awal;
$params[] = $tanggal_akhir;


// Ambil mapel_id dan id_siswa juga
$mapel_id = isset($_GET['mapel_id']) ? $_GET['mapel_id'] : '';
$id_siswa = isset($_GET['id_siswa']) ? $_GET['id_siswa'] : '';

if (!empty($mapel_id)) {
    $where_conditions[] = "p.id_mapel = ?";
    $params[] = $mapel_id;
}

if (!empty($id_siswa)) {
    $where_conditions[] = "s.id = ?";
    $params[] = $id_siswa;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

$query = "
    SELECT 
        k.nama_kelas,
        k.sub_kelas,
        s.nis,
        s.nama AS nama_siswa,
        DATE(a.tanggal) AS tanggal,
        a.status,
        a.keterangan,
        a.created_at,
        a.updated_at,
        g.nama AS guru_pengajar,
        m.mapel,
        t.tahun AS tahun_ajaran,
        g2.nama AS guru_update
    FROM siswa s
    JOIN kelas k ON s.kelas_id = k.id
    LEFT JOIN absensi a ON s.id = a.siswa_id
    LEFT JOIN guru g ON a.created_by = g.id
    LEFT JOIN guru g2 ON a.updated_by = g2.id
    LEFT JOIN pelajaran p ON p.id = s.kelas_id
    LEFT JOIN mapel m ON m.id_mapel = p.id_mapel
    LEFT JOIN tahun_ajaran t ON s.id_tahun = t.id_tahun
    $where_clause
    ORDER BY k.nama_kelas, s.nama, a.tanggal
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$data_absensi = $stmt->fetchAll();


header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Laporan_Absensi_' . date('d-m-Y', strtotime($tanggal_awal)) . '_sd_' . date('d-m-Y', strtotime($tanggal_akhir)) . '.xls"');
header('Cache-Control: max-age=0');
?>

<table border="1">
    <thead>
        <tr>
            <th colspan="13" style="text-align: center; font-size: 14pt; font-weight: bold;">Laporan Absensi Siswa</th>
        </tr>
        <tr>
            <th colspan="13" style="text-align: center;">
                Periode: <?= date('d F Y', strtotime($tanggal_awal)) ?> s/d <?= date('d F Y', strtotime($tanggal_akhir)) ?>
            </th>
        </tr>
        <tr>
        <tr>
            <th>No</th>
            <th>Tanggal</th>
            <th>Kelas</th>
            <th>Mata Pelajaran</th>
            <th>NIS</th>
            <th>Nama Siswa</th>
            <th>Status</th>
            <th>Tahun Ajaran</th>
            <th>Keterangan</th>
            <th>Diinput Oleh</th>
            <th>Waktu Input</th>
            <th>Diupdate Oleh</th>
            <th>Update Terakhir</th>
        </tr>
        </tr>
    </thead>
    <tbody>
        <?php 
        $no = 1;
        $current_kelas = '';
        foreach($data_absensi as $row): 
            if($current_kelas != '' && $current_kelas != $row['nama_kelas']):
        ?>
            <tr><td colspan="13">&nbsp;</td></tr>
        <?php 
            endif;
            $current_kelas = $row['nama_kelas'];
        ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                <td><?= 'Kelas ' . htmlspecialchars($row['nama_kelas']) . '-' . htmlspecialchars($row['sub_kelas']) ?></td>
                <td><?= htmlspecialchars($row['mapel']) ?></td>
                <td><?= htmlspecialchars($row['nis']) ?></td>
                <td><?= htmlspecialchars($row['nama_siswa']) ?></td>
                <td style="<?= getStatusStyle($row['status']) ?>"><?= ucfirst($row['status']) ?></td>
                <td><?= htmlspecialchars($row['tahun_ajaran'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['keterangan'] ?? '-') ?></td>
                <td><?= $row['created_at'] ? date('d/m/Y H:i', strtotime($row['created_at'])) : '-' ?></td>
                <td><?= htmlspecialchars($row['guru_pengajar'] ?? '-') ?></td>
                <td><?= $row['updated_at'] ? date('d/m/Y H:i', strtotime($row['updated_at'])) : '-' ?></td>
                <td><?= htmlspecialchars($row['guru_update'] ?? '-') ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr><td colspan="13">&nbsp;</td></tr>
        <tr>
            <td colspan="13" style="text-align: right;">
                Dicetak pada: <?= date('d/m/Y H:i:s') ?>
            </td>
        </tr>
    </tfoot>
</table>

<?php
function getStatusStyle($status) {
    switch($status) {
        case 'hadir':
            return 'background-color: #dcfce7; color: #166534;';
        case 'sakit':
            return 'background-color: #fef9c3; color: #854d0e;';
        case 'izin':
            return 'background-color: #dbeafe; color: #1e40af;';
        case 'alpha':
            return 'background-color: #fee2e2; color: #991b1b;';
        default:
            return '';
    }
}
?>
