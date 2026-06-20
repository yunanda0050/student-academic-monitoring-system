<?php
session_start();
require_once 'config/koneksi.php';

// Cek login & role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'superadmin') {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_kelas = $_POST['id_kelas'];
    $id_mapel = $_POST['id_mapel'];

    // Ambil nama_kelas dari id_kelas yang dipilih
    $stmt = $pdo->prepare("SELECT nama_kelas FROM kelas WHERE id = ?");
    $stmt->execute([$id_kelas]);
    $kelas = $stmt->fetch();

    if ($kelas) {
        $nama_kelas = $kelas['nama_kelas'];

        // Ambil semua id kelas dengan nama_kelas yang sama
        $stmt_all = $pdo->prepare("SELECT id FROM kelas WHERE nama_kelas = ?");
        $stmt_all->execute([$nama_kelas]);
        $kelas_ids = $stmt_all->fetchAll(PDO::FETCH_COLUMN);

        // Masukkan ke tabel pelajaran untuk semua id kelas
        $insert = $pdo->prepare("INSERT INTO pelajaran (id, id_mapel) VALUES (?, ?)");

        foreach ($kelas_ids as $kelas_id) {
            // Cek apakah sudah ada agar tidak duplikat
            $cek = $pdo->prepare("SELECT COUNT(*) FROM pelajaran WHERE id = ? AND id_mapel = ?");
            $cek->execute([$kelas_id, $id_mapel]);
            if ($cek->fetchColumn() == 0) {
                $insert->execute([$kelas_id, $id_mapel]);
            }
        }

        $_SESSION['success'] = "Mapel berhasil ditambahkan ke semua sub-kelas Kelas $nama_kelas!";
    } else {
        $_SESSION['success'] = "Kelas tidak ditemukan.";
    }
}

header("Location: kelas.php");
exit;
