<?php
$current_page = basename($_SERVER['PHP_SELF']);
if($current_page == 'index.php'): 
?>
    <footer class="bg-white shadow-lg mt-8">
        <div class="container mx-auto px-4 py-4">
            <p class="text-center text-gray-600 text-sm">
                &copy; <?= date('Y') ?> Sistem Absensi Siswa. All rights reserved.
            </p>
        </div>
    </footer>
<?php endif; ?>

<!-- Script untuk animasi navbar -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const navItems = document.querySelectorAll('.nav-item');
        navItems.forEach(item => {
            item.addEventListener('click', function() {
                this.classList.add('scale-95');
                setTimeout(() => {
                    this.classList.remove('scale-95');
                }, 100);
            });
        });
    });
</script>
</body>
</html>
