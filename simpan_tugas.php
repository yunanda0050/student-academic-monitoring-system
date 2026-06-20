<?php
session_start();
require_once 'config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tanggal = $_POST['tanggal'];
    $kelas_id = $_POST['kelas_id'];
    $id_mapel = $_POST['mapel_id'];
    $siswa_id = $_POST['siswa_id'];

    // ✅ SIMPAN UH jika ada
    if (isset($_POST['uh'])) {
        foreach ($_POST['uh'] as $ke => $nilai) {
            $stmt = $pdo->prepare("INSERT INTO tugas_detail (id_tugas, id_mapel, siswa_id, jenis, nilai, ke, tanggal)
                                   VALUES (?, ?, ?, 'uh', ?, ?, ?)
                                   ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)");
            $stmt->execute([$siswa_id, $id_mapel, $siswa_id, $nilai, $ke + 1, $tanggal]);
        }
    }

    // ✅ SIMPAN TUGAS jika ada
    if (isset($_POST['tugas'])) {
        foreach ($_POST['tugas'] as $ke => $nilai) {
            $stmt = $pdo->prepare("INSERT INTO tugas_detail (id_tugas, id_mapel, siswa_id, jenis, nilai, ke, tanggal)
                                   VALUES (?, ?, ?, 'ntugas', ?, ?, ?)
                                   ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)");
            $stmt->execute([$siswa_id, $id_mapel, $siswa_id, $nilai, $ke + 1, $tanggal]);
        }
    }

    echo json_encode(['success' => true]);
}
