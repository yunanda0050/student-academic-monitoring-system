<?php
session_start();
require_once 'config/koneksi.php';

// Cek jika sudah login
if(isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Proses Login
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $query = "SELECT * FROM guru WHERE username = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$username]);
    $guru = $stmt->fetch();
    
    // Cek login dengan password biasa
    if($guru && $password === $guru['password']) {
        $_SESSION['user_id'] = $guru['id'];
        $_SESSION['username'] = $guru['username'];
        $_SESSION['role'] = $guru['role'];
        $_SESSION['nama'] = $guru['nama'];
        
        header("Location: index.php");
        exit;
    } else {
        $error = "Username atau password salah!";
    }
}

require_once 'partials/header.php';
?>

<style>
.hero-pattern {
    background-color: #191970;
}

.image-slider {
    animation: slideShow 20s infinite;
}

@keyframes slideShow {
    0%, 33% {
        opacity: 1;
        transform: scale(1);
    }
    34%, 35% {
        opacity: 0;
        transform: scale(1.1);
    }
    36%, 69% {
        opacity: 1;
        transform: scale(1);
    }
    70%, 71% {
        opacity: 0;
        transform: scale(1.1);
    }
    72%, 100% {
        opacity: 1;
        transform: scale(1);
    }
}
</style>

<div class="min-h-screen hero-pattern flex">
    <!-- Bagian Kiri - Gambar dan Informasi -->
    <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden">
        <div class="absolute inset-0 bg-black bg-opacity-40 z-10"></div>
        <div class="absolute inset-0">
            <img src="assets/images/smp2sepatan.jpg" class="absolute inset-0 w-full h-full object-cover image-slider" alt="Gedung Sekolah" style="animation-delay: 0s">
            <img src="assets/images/smp2sepatannn.jpg" class="absolute inset-0 w-full h-full object-cover image-slider" alt="Foto Guru" style="animation-delay: -13s">
        </div>
        <div class="relative z-20 p-12 flex flex-col justify-center text-white">
            <img src="assets/images/logo-sekolah.png" alt="Logo Sekolah" class="w-40 h-40 mb-8">
            <h1 class="text-4xl font-bold mb-4">SMP Negeri 2 Sepatan</h1>
            <p class="text-lg mb-6">Sistem Informasi Monitoring Siswa</p>
            <div class="bg-white/20 backdrop-blur-sm rounded-lg p-6">
                <p class="text-sm">Jl. Raya Pakuhaji No.Km 02 Ds, Pd. Jaya</p>
                <p class="text-sm">Kec. Sepatan, Kabupaten Tangerang, Banten 15520</p>
            </div>
        </div>
    </div>

    <!-- Bagian Kanan - Form Login -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-8">
        <div class="max-w-md w-full">
            <div class="text-center mb-6">
                <a href="index.php" class="inline-flex items-center px-4 py-2 bg-white text-[#191970] rounded-lg hover:bg-orange-50 transition duration-200 shadow-sm">
                    <i class="fas fa-home mr-2"></i>
                    Kembali ke Beranda
                </a>
            </div>

            <div class="bg-white rounded-xl shadow-2xl p-8">
                <div class="text-center mb-8">
                    <div class="lg:hidden mb-6">
                        <img src="assets/images/logo-sekolah.png" alt="Logo Sekolah" class="w-24 h-24 mx-auto">
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">
                        Selamat Datang
                    </h2>
                    <p class="text-sm text-gray-600">
                        Silakan masuk untuk mengakses Sistem Monitoring Siswa
                    </p>
                </div>
                
                <?php if(isset($error)): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-md">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-red-500"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-red-700"><?= $error ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <form class="space-y-6" method="POST">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700">
                            Username
                        </label>
                        <div class="mt-1 relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <input id="username" name="username" type="text" required 
                                   class="appearance-none block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#191970] focus:border-transparent sm:text-sm" 
                                   placeholder="Masukkan username">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            Password
                        </label>
                        <div class="mt-1 relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input id="password" name="password" type="password" required 
                                   class="appearance-none block w-full pl-10 pr-10 py-2 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#191970] focus:border-transparent sm:text-sm" 
                                   placeholder="Masukkan password">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <button type="button" onclick="togglePassword()" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                                    <i id="toggleIcon" class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div>
                        <button type="submit" 
                                class="group relative w-full flex justify-center py-2.5 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-[#191970] hover:bg-[#4169e1] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#191970] transition duration-200">
                            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                <i class="fas fa-sign-in-alt text-[#4169e1] group-hover:text-[#365ac0]"></i>
                            </span>
                            Masuk
                        </button>
                    </div>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-xs text-gray-600">
                        &copy; <?= date('Y') ?> SMP Negeri 2 Sepatan. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}
</script>

<?php require_once 'partials/footer.php'; ?>
