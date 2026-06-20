# Sistem Informasi Absensi Siswa - SDN 203 BONTOMACINNA

Sistem Informasi Absensi Siswa adalah aplikasi berbasis web yang dirancang khusus untuk SDN 203 BONTOMACINNA untuk mengelola dan memantau kehadiran siswa secara digital.

## Fitur Utama

- 📊 **Dashboard Informatif**
  - Statistik kehadiran real-time
  - Grafik absensi per kelas
  - Ringkasan status kehadiran

- 📝 **Manajemen Absensi**
  - Input absensi digital
  - Status: Hadir, Sakit, Izin, Alpha
  - Keterangan tambahan untuk setiap status

- 👥 **Monitoring Orang Tua**
  - Akses khusus untuk orang tua
  - Pantau kehadiran anak secara real-time
  - Riwayat kehadiran lengkap

- 📋 **Laporan**
  - Laporan harian dan bulanan
  - Export data ke Excel
  - Filter berdasarkan kelas dan periode

- 👨‍🏫 **Manajemen Data**
  - Data Guru
  - Data Siswa
  - Data Kelas

## Teknologi yang Digunakan

- PHP 7.4+
- MySQL/MariaDB
- TailwindCSS
- Font Awesome
- JavaScript

## Persyaratan Sistem

- PHP >= 7.4
- MySQL/MariaDB
- Web Server (Apache/Nginx)
- Browser modern (Chrome, Firefox, Safari, Edge)

## Instalasi

1. Clone repository ini:
   ```bash
   git clone https://github.com/Galang0304/absen-siswa-v2.git
   ```

2. Import database:
   - Buat database baru
   - Import file SQL dari folder `database`

3. Konfigurasi database:
   - Buka file `config/koneksi.php`
   - Sesuaikan pengaturan database:
     ```php
     $host = 'localhost';
     $dbname = 'nama_database';
     $username = 'username_database';
     $password = 'password_database';
     ```

4. Akses aplikasi melalui browser

## Struktur Role

1. **Superadmin**
   - Akses penuh ke semua fitur
   - Manajemen data master
   - Laporan lengkap

2. **Guru**
   - Input absensi
   - Lihat laporan kelas
   - Update profil

3. **Orang Tua**
   - Monitoring kehadiran anak
   - Lihat riwayat absensi
   - Notifikasi ketidakhadiran

## Penggunaan

1. **Login Sistem**
   - Gunakan username dan password yang diberikan
   - Pilih role sesuai akses

2. **Input Absensi**
   - Pilih kelas
   - Pilih tanggal
   - Input status kehadiran
   - Tambah keterangan jika perlu

3. **Monitoring**
   - Masukkan NIS siswa
   - Verifikasi dengan tanggal lahir
   - Akses informasi kehadiran

## Kontribusi

Jika Anda ingin berkontribusi pada proyek ini, silakan:
1. Fork repository
2. Buat branch baru
3. Commit perubahan
4. Push ke branch
5. Buat Pull Request

## Lisensi

Hak Cipta © 2024 SDN 203 BONTOMACINNA. All rights reserved.

## Kontak

Untuk informasi lebih lanjut, hubungi:
- Email: sdn203bontomacinna@gmail.com
- Telepon: +62 852-9923-5494 / +62 852-5570-7086
- Alamat: Desa Bontomacinna, Kec. Gantarang, Kab. Bulukumba, Sulawesi Selatan 92561 