<?php
session_start();

// Hapus session variables monitoring
unset($_SESSION['monitoring_siswa_id']);
unset($_SESSION['monitoring_siswa_nama']);
unset($_SESSION['monitoring_siswa_kelas_id']);

// Redirect ke halaman monitoring
header("Location: monitoring.php");
exit; 