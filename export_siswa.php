<?php
session_start();
require_once 'config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'superadmin') {
    die('Unauthorized access');
}

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=data_siswa_" . date('Ymd_His') . ".xls");

// Ambil filter dari GET
$search = isset($_GET['search']) ? $_GET['search'] : '';
$kelas_filter = isset($_GET['kelas_filter']) ? $_GET['kelas_filter'] : '';
$jk_filter = isset($_GET['jk_filter']) ? $_GET['jk_filter'] : '';
$tahun_filter = isset($_GET['tahun_filter']) ? $_GET['tahun_filter'] : '';

// Query sama seperti di siswa.php
$query = "
    SELECT s.nis, s.nisn, s.nama, s.jenis_kelamin, 
           CONCAT(k.nama_kelas, '-', k.sub_kelas) AS kelas, 
           t.tahun, s.kode_akses
    FROM siswa s 
    JOIN kelas k ON s.kelas_id = k.id 
    LEFT JOIN tahun_ajaran t ON s.id_tahun = t.id_tahun
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $query .= " AND (s.nama LIKE ? OR s.nis LIKE ? OR s.nisn LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if (!empty($kelas_filter)) {
    $query .= " AND s.kelas_id = ?";
    $params[] = $kelas_filter;
}
if (!empty($tahun_filter)) {
    $query .= " AND s.id_tahun = ?";
    $params[] = $tahun_filter;
}
if (!empty($jk_filter)) {
    $query .= " AND s.jenis_kelamin = ?";
    $params[] = $jk_filter;
}

$query .= " ORDER BY k.nama_kelas, s.nama";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$siswa_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Output data sebagai tabel HTML (dibaca Excel)
echo "<table border='1'>";
echo "<tr>
        <th>NIS</th>
        <th>NISN</th>
        <th>Nama</th>
        <th>Jenis Kelamin</th>
        <th>Kelas</th>
        <th>Tahun Ajaran</th>
        <th>Kode Akses</th>
      </tr>";

foreach ($siswa_list as $siswa) {
    echo "<tr>
            <td>{$siswa['nis']}</td>
            <td>{$siswa['nisn']}</td>
            <td>{$siswa['nama']}</td>
            <td>{$siswa['jenis_kelamin']}</td>
            <td>{$siswa['kelas']}</td>
            <td>{$siswa['tahun']}</td>
            <td>{$siswa['kode_akses']}</td>
          </tr>";
}

echo "</table>";
exit;
