<?php
session_start();
require_once 'config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_kelas = $_POST['id_kelas'];
    $selected_mapel_ids = $_POST['selected_mapel_ids'] ?? [];

    // Bersihkan mapel yang lama untuk kelas ini
    $stmt = $pdo->prepare("DELETE FROM pelajaran WHERE id = ?");
    $stmt->execute([$id_kelas]);

    // Tambahkan mapel yang dipilih ulang
    $stmt = $pdo->prepare("INSERT INTO pelajaran (id, id_mapel) VALUES (?, ?)");
    foreach ($selected_mapel_ids as $id_mapel) {
        $stmt->execute([$id_kelas, $id_mapel]);
    }

    $_SESSION['success'] = "Mapel kelas berhasil diperbarui.";
    header("Location: kelas.php");
    exit;
}
