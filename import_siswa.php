<?php
session_start();
require_once 'config/koneksi.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Cek apakah user sudah login
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {

        if (!isset($_FILES['file']['tmp_name'])) {
            throw new Exception('Tidak ada file yang diunggah');
        }

        $inputFileName = $_FILES['file']['tmp_name'];
        $spreadsheet = IOFactory::load($inputFileName);
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow();

        // Debug: Cek jumlah baris
        error_log("Total baris: " . $highestRow);

        $pdo->beginTransaction();
        $success_count = 0;

        // Loop through rows, starting from row 8 (skip header dan petunjuk)
        for ($row = 9; $row <= $highestRow; $row++) {
            $nis            = trim($worksheet->getCellByColumnAndRow(1, $row)->getValue());
            $nisn           = trim($worksheet->getCellByColumnAndRow(2, $row)->getValue());
            $nama           = trim($worksheet->getCellByColumnAndRow(3, $row)->getValue());
            $jenis_kelamin  = strtoupper(trim($worksheet->getCellByColumnAndRow(4, $row)->getValue()));
            $kelas_id = trim($worksheet->getCellByColumnAndRow(5, $row)->getFormattedValue());
            $id_tahun = trim($worksheet->getCellByColumnAndRow(6, $row)->getFormattedValue());
            $kode_akses     = trim($worksheet->getCellByColumnAndRow(7, $row)->getValue());

            // Debug log
            error_log("Baris $row: NIS=$nis, NISN=$nisn, Nama=$nama, JK=$jenis_kelamin, Kelas=$kelas_id, Tahun=$id_tahun, Kode=$kode_akses");

            // Skip baris kosong
            if (empty($nis) || empty($nisn) || empty($nama)) {
                continue;
            }

            // Lewati contoh (opsional, kalau kamu tidak ingin contoh masuk)
            //if ($row <= 10 && in_array($nis, ['00012023', '00022023', '00032023'])) {
               // continue;
            //}

            // Validate jenis_kelamin
            if (!in_array(strtoupper($jenis_kelamin), ['L', 'P'])) {
                throw new Exception("Jenis kelamin tidak valid pada baris $row. Gunakan L atau P");
            }

            // Validate kelas_id
            if (empty($kelas_id)) {
                throw new Exception("ID Kelas tidak boleh kosong pada baris $row");
            }

            // Check if kelas exists
            $stmt = $pdo->prepare("SELECT id FROM kelas WHERE id = ?");
            $stmt->execute([$kelas_id]);
            if (!$stmt->fetch()) {
                throw new Exception("ID Kelas tidak valid pada baris $row");
            }

            // Validasi tahun ajaran
            $stmt = $pdo->prepare("SELECT id_tahun FROM tahun_ajaran WHERE id_tahun = ?");
            $stmt->execute([$id_tahun]);
            if (!$stmt->fetch()) {
                throw new Exception("ID Tahun Ajaran tidak valid pada baris $row");
            }

            // Check for duplicate NIS/NISN
            $stmt = $pdo->prepare("SELECT id FROM siswa WHERE nis = ? OR nisn = ?");
            $stmt->execute([$nis, $nisn]);
            if ($stmt->fetch()) {
                throw new Exception("NIS atau NISN sudah terdaftar pada baris $row");
            }

            // Cek duplikat kode akses
            $stmt = $pdo->prepare("SELECT id FROM siswa WHERE kode_akses = ?");
            $stmt->execute([$kode_akses]);
            if ($stmt->fetch()) {
                throw new Exception("Kode akses sudah digunakan pada baris $row");
            }

            // Simpan data siswa
            $stmt = $pdo->prepare("INSERT INTO siswa (nis, nisn, nama, jenis_kelamin, kelas_id, id_tahun, kode_akses) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([$nis, $nisn, $nama, $jenis_kelamin, $kelas_id, $id_tahun, $kode_akses]);

            if ($result) {
                $success_count++;
            } else {
                throw new Exception("Gagal menyimpan data pada baris $row");
            }
        }
        if ($success_count > 0) {
            $pdo->commit();
            $_SESSION['success'] = "Berhasil mengimport $success_count data siswa.";
        } else {
            throw new Exception("Tidak ada data yang diimpor.");
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
        $pdo->rollBack();
        }
        error_log("ERROR IMPORT: " . $e->getMessage());
        $_SESSION['error'] = "Gagal mengimpor data: " . $e->getMessage();
    }
}

header('Location: siswa.php');
exit;
?> 