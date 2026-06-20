<?php
session_start();
require_once 'config/koneksi.php';

// Jika sudah login, redirect ke dashboard
if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

require_once 'partials/header.php';

// Ambil data statistik
$stmt = $pdo->query("SELECT COUNT(*) FROM siswa");
$total_siswa = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM kelas");
$total_kelas = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM guru WHERE role = 'guru'");
$total_guru = $stmt->fetchColumn();
?>

<!-- Hero Section -->
<div class="relative bg-gradient-to-br from-[#191970] to-[#191970] overflow-hidden">
    <!-- Background Pattern -->
    <div class="absolute inset-0 bg-grid-white/[0.1] bg-[length:16px_16px]"></div>
    
    <!-- Hero Content -->
    <div class="relative container mx-auto px-4 py-12 md:py-24">
        <div class="grid md:grid-cols-2 gap-8 items-center">
            <!-- Text Content -->
            <div class="text-white space-y-6">
                <div class="flex items-center space-x-4 mb-6">
                    <img src="assets/images/logo-sekolah.png" alt="Logo Sekolah" class="w-40 h-40">
                    <div>
                        <h1 class="text-3xl md:text-4xl font-bold">SMP Negeri 2 Sepatan</h1>
                        <p class="text-orange-100">Jl. Raya Pakuhaji No.Km 02 Ds, Pd. Jaya</p>
                        <p class="text-orange-100">Kec. Sepatan, Kabupaten Tangerang, Banten 15520</p>
                    </div>
                </div>
                <p class="text-xl text-orange-50">Selamat datang di Sistem Informasi Monitoring Siswa</p>
                <p class="text-orange-100">Sistem informasi ini dirancang untuk memudahkan pengelolaan monitoring siswa secara efektif dan efisien.</p>
                
                <!-- Quick Stats -->
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mt-8">
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4">
                        <div class="text-3xl font-bold"><?= $total_siswa ?></div>
                        <div class="text-orange-100">Total Siswa</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4">
                        <div class="text-3xl font-bold"><?= $total_kelas ?></div>
                        <div class="text-orange-100">Total Kelas</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4">
                        <div class="text-3xl font-bold"><?= $total_guru ?></div>
                        <div class="text-orange-100">Total Guru</div>
                    </div>
                </div>

                <!-- Login Buttons -->
                <div class="mt-8 space-y-4">
                    <a href="login.php" class="inline-flex items-center px-6 py-3 bg-white text-[#191970] rounded-lg hover:bg-orange-50 transition duration-300">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Masuk ke Sistem
                    </a>
                    <a href="monitoring.php" class="inline-flex items-center px-6 py-3 bg-[#4169e1] text-white rounded-lg hover:bg-[#365ac0] transition duration-300">
                        <i class="fas fa-chart-line mr-2"></i>
                        Monitoring Orang Tua
                    </a>
                </div>
            </div>
            
            <!-- Image Gallery -->
            <div class="relative hidden md:block">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-4">
                        <img src="assets/images/smp2sepatannn.jpg" alt="Gedung Sekolah" class="w-full h-48 object-cover rounded-lg shadow-lg transform hover:scale-105 transition duration-300">
                        <img src="assets/images/logo-sekolah.png" alt="Logo" class="w-full h-48 object-contain bg-white/10 backdrop-blur-sm rounded-lg shadow-lg p-4 transform hover:scale-105 transition duration-300">
                    </div>
                    <div class="mt-8">
                        <img src="assets/images/smp2sepatan.jpg" alt="Foto Guru" class="w-full h-[400px] object-cover rounded-lg shadow-lg transform hover:scale-105 transition duration-300">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tentang Sekolah Section -->
<div class="bg-white py-16">
    <div class="container mx-auto px-4">
        <div class="max-w-3xl mx-auto text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-800 mb-4">Tentang Sekolah</h2>
            <p class="text-gray-600">SMPN 2 Sepatan adalah sekolah menengah pendidikan negeri yang berkomitmen untuk memberikan pendidikan berkualitas dan membentuk karakter siswa yang unggul. Dengan akreditasi A, sekolah kami terus berinovasi dalam mengembangkan metode pembelajaran yang efektif.</p>
        </div>

        <!-- Keunggulan -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-16">
            <div class="text-center p-6 bg-orange-50 rounded-lg">
                <i class="fas fa-award text-3xl text-[#191970] mb-3"></i>
                <h3 class="font-semibold">Akreditasi A</h3>
                <p class="text-sm text-gray-600">Terakreditasi Unggul</p>
            </div>
            <div class="text-center p-6 bg-orange-50 rounded-lg">
                <i class="fas fa-chalkboard-teacher text-3xl text-[#191970] mb-3"></i>
                <h3 class="font-semibold">Guru Berkualitas</h3>
                <p class="text-sm text-gray-600">Pendidik Profesional</p>
            </div>
            <div class="text-center p-6 bg-orange-50 rounded-lg">
                <i class="fas fa-book text-3xl text-[#191970] mb-3"></i>
                <h3 class="font-semibold">Kurikulum Merdeka</h3>
                <p class="text-sm text-gray-600">Pembelajaran Inovatif</p>
            </div>
            <div class="text-center p-6 bg-orange-50 rounded-lg">
                <i class="fas fa-trophy text-3xl text-[#191970] mb-3"></i>
                <h3 class="font-semibold">Prestasi</h3>
                <p class="text-sm text-gray-600">Akademik & Non-Akademik</p>
            </div>
        </div>

        <!-- Visi & Misi -->
        <div class="grid md:grid-cols-2 gap-8 mb-16">
            <div class="bg-white p-8 rounded-lg shadow-lg">
                <h3 class="text-2xl font-bold text-gray-800 mb-4">VISI</h3>
                <p class="text-gray-600">UNGGUL, RELIGIUS, CERDAS, KOMPETITIF, BERKARAKTER SERTA PEDULI LINGKUNGAN</p>
            </div>
            <div class="bg-white p-8 rounded-lg shadow-lg">
                <h3 class="text-2xl font-bold text-gray-800 mb-4">MISI</h3>
                <ul class="text-gray-600 space-y-3 list-disc pl-5">
<li>MENUMBUHKAN SEMANGAT KEUNGGULAN KEPADA SELURUH WARGA SEKOLAH</li>
<li>MENUMBUHKAN SIFAT RELIGIUS KEPADA SELURUH WARGA SEKOLAH TERUTAMA PESERTA DIDIK</li>
<li>MENCIPTAKAN PROSES PEMBELAJARAN YANG EFEKTIF</li>
<li>MENINGKATKAN MUTU LULUSAN YANG BERDAYA SAING TINGGI</li>
<li>MEWUJUDKAN LINGKUNGAN SEKOLAH YANG CLEAN & GREEN SERTA INDAH DAN SEHAT</li>
<li>MEWUJUDKAN PELESTARIAN LINGKUNGAN SEKITAR SEKOLAH</li>
<li>MENERAPKAN MANAJEMEN PARTISIPASI WARGA SEKOLAH DAN MASYARAKAT MENUJU LINGKUNGAN SEKOLAH YANG BERSINAR TERANG ( BERSIH, INDAH, ASRI, RINDANG, TERTIB, AMAN, NYAMAN DAN TERANG)</li>
<li>MELAKSANAKAN PEMBELAJARAN KONTEKSTUAL YANG MEMANFAATKAN LINGKUNGAN SEBAGAI MEDIA DAN SUMBER BELAJAR SERTA BIMBINGAN SECARA EFEKTIF SEHINGGA SETIAP PESERTA DIDIK BERKEMBANG SECARA OPTIMAL SESUAI DENGAN POTENSI YANG DIMILIKI</li>
<li>PEMBERDAYAAN KOMITE SEKOLAH DALAM MENINGKATKAN PERAN SERTA ORANG TUA MURID/MASYARAKAT DAN INSTANSI TERKAIT DALAM PENYELENGGARAAN PENDIDIKAN BERDASARKAN PRINSIP OTONOMI DAERAH</li>
<li>MENGEMBANGKAN RASA INGIN TAHU, GEMAR MEMBACA DAN BERINTEGRAS BAGI PESERTA DIDIK DENGAN MELAKSANAKAN LITERASI SEBAGAI PEMBIASAAN</li>
                </ul>
            </div>
        </div>

        <!-- Kontak -->
        <div class="bg-orange-50 rounded-lg p-8">
            <h3 class="text-2xl font-bold text-gray-800 mb-6 text-center">Kontak Kami</h3>
            <div class="grid md:grid-cols-2 gap-6">
                <div class="text-center">
                    <i class="fas fa-map-marker-alt text-3xl text-[#191970] mb-3"></i>
                    <h4 class="font-semibold mb-2">Alamat</h4>
                    <p class="text-gray-600">Alamat: Jl. Raya Pakuhaji No.Km 02 Ds, Pd. Jaya, Kec. Sepatan, </br>Kabupaten Tangerang, Banten 15520</p>
                </div>
                <div class="text-center">
                    <i class="fas fa-phone text-3xl text-[#191970] mb-3"></i>
                    <h4 class="font-semibold mb-2">Telepon</h4>
                    <p class="text-gray-600">(021) 59372209</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="container mx-auto px-4 py-8">
    <!-- Features Section -->
    <div class="grid md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="text-[#191970] text-3xl mb-4">
                <i class="fas fa-clipboard-check"></i>
            </div>
            <h3 class="text-xl font-semibold mb-2">Absensi Digital</h3>
            <p class="text-gray-600">Sistem absensi digital yang memudahkan pencatatan kehadiran siswa secara efisien.</p>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="text-[#191970] text-3xl mb-4">
                <i class="fas fa-chart-bar"></i>
            </div>
            <h3 class="text-xl font-semibold mb-2">Laporan Real-time</h3>
            <p class="text-gray-600">Pantau kehadiran siswa secara real-time dan dapatkan laporan detail dengan mudah.</p>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="text-[#191970] text-3xl mb-4">
                <i class="fas fa-users"></i>
            </div>
            <h3 class="text-xl font-semibold mb-2">Monitoring Orang Tua</h3>
            <p class="text-gray-600">Orang tua dapat memantau kehadiran dan nilai akademik anak secara langsung melalui sistem.</p>
        </div>
    </div>

    <!-- Monitoring Section -->
    <div class="mt-12">
        <div class="bg-white rounded-lg shadow-md p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Cara Monitoring untuk Orang Tua</h2>
            <div class="grid md:grid-cols-2 gap-8">
                <div>
                    <h3 class="text-lg font-semibold mb-4">Langkah-langkah Monitoring:</h3>
                    <ol class="list-decimal list-inside space-y-3 text-gray-600">
                        <li>Klik tombol "Monitoring Orang Tua" di atas</li>
                        <li>Masukkan NIS (Nomor Induk Siswa) anak Anda</li>
                        <li>Masukkan kode array_key_exists sebagai verifikasi</li>
                        <li>Lihat informasi kehadiran dan nilai akademik ank Anda</li>
                    </ol>
                </div>
            
            </div>
        </div>
    </div>
</div>

<?php require_once 'partials/footer.php'; ?> 