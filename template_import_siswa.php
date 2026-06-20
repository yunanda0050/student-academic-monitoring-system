<?php
session_start();

// Cek apakah user sudah login
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'config/koneksi.php';
require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

try {
    // Buat spreadsheet baru
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set judul worksheet
    $sheet->setTitle('Template Import Siswa');

    // Tambahkan petunjuk pengisian
    $sheet->setCellValue('A1', 'PETUNJUK PENGISIAN:');
    $sheet->mergeCells('A1:E1');
    $sheet->setCellValue('A2', '1. Lihat ID Kelas di sheet "Informasi Kelas"');
    $sheet->mergeCells('A2:E2');
    $sheet->setCellValue('A3', '2. Jenis Kelamin diisi dengan "L" atau "P"');
    $sheet->mergeCells('A3:E3');
    $sheet->setCellValue('A4', '3. Pastikan NIS dan NISN bersifat unik (tidak boleh sama dengan data yang sudah ada)');
    $sheet->mergeCells('A4:E4');
    $sheet->setCellValue('A5', '4. Format NIS dan NISN harus berupa text (klik kanan pada sel -> Format Cells -> Text)');
    $sheet->mergeCells('A5:E5');
    $sheet->setCellValue('A6', '5. ID Tahun Ajaran bisa dilihat di halaman manajemen tahun ajaran');
    $sheet->mergeCells('A6:F6');
    $sheet->setCellValue('A7', '6. Kode Akses adalah kode rahasia yang digunakan orang tua untuk mengakses nilai siswa. (bebas, max 20 karakter)');
    $sheet->mergeCells('A7:G7');

    // Style untuk petunjuk
    $petunjukStyle = [
        'font' => [
            'bold' => true,
            'size' => 11,
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'FFE699'],
        ],
    ];
    $sheet->getStyle('A6')->applyFromArray($petunjukStyle);
    
    // Set header kolom mulai 
    $headers = ['NIS', 'NISN', 'Nama Lengkap', 'Jenis Kelamin (L/P)', 'ID Kelas', 'ID Tahun Ajaran', 'Kode Akses'];
    $col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . '8', $header);
    $col++;
}
    
    // Styling untuk header
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '4A90E2'],
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
            ],
        ],
    ];

    // Tulis header dan terapkan style
    foreach (range('A', 'G') as $index => $column) {
    $sheet->setCellValue($column . '8', $headers[$index]);
    $sheet->getColumnDimension($column)->setAutoSize(true);
    }
    $sheet->getStyle('A7:G7')->applyFromArray($petunjukStyle);

    // Tambahkan contoh data
    $contohData = [
    ['00012023', '3169255540', 'Ahmad Setiawan', 'L', '4', '1', 'akses001'],
    ['00022023', '3169255541', 'Siti Nurhaliza', 'P', '5', '1', 'akses002'],
    ['00032023', '3169255542', 'Budi Santoso', 'L', '6', '1', 'akses003'],
    ['00042023', '3169255543', 'Rina Melati', 'P', '7', '1', 'akses004'],
    ['00052023', '3169255544', 'Muhammad Rizki', 'L', '8', '1', 'akses005'],
    ['00062023', '3169255545', 'Putri Ayu', 'P', '9', '1', 'akses006']
];

    $row = 9;
    foreach ($contohData as $data) {
        $col = 'A';
        foreach ($data as $value) {
            $sheet->setCellValue($col . $row, $value);
            // Set format text untuk kolom NIS dan NISN
            if ($col == 'A' || $col == 'B') {
                $sheet->getStyle($col . $row)->getNumberFormat()->setFormatCode('@');
            }
            $col++;
        }
        $row++;
    }
    
    for ($i = 9; $i <= 100; $i++) {
    // Pastikan kolom G (kode akses) diformat sebagai teks
    $sheet->getStyle('G' . $i)->getNumberFormat()->setFormatCode('@');
    }

    // Style untuk contoh data
    $contohStyle = [
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'E8F5E9'],
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
            ],
        ],
    ];
    $sheet->getStyle('A9:G14')->applyFromArray($contohStyle);

    // Tambahkan catatan contoh
    $sheet->setCellValue('A14', 'Catatan: Data di atas hanya contoh. Hapus atau timpa dengan data yang sebenarnya.');
    $sheet->mergeCells('A14:G14');
    $sheet->getStyle('A14')->getFont()->setItalic(true);
    $sheet->getStyle('A14')->getFont()->setSize(10);


    // Tambahkan informasi ID Kelas
 $sheet->setCellValue('I7', 'REFERENSI ID KELAS:');
$sheet->getStyle('I7')->getFont()->setBold(true);

$kelasInfo = [
    ['4', 'Kelas 1'],
    ['5', 'Kelas 4'],
    ['6', 'Kelas 3'],
    ['7', 'Kelas 5'],
    ['8', 'Kelas 2'],
    ['9', 'Kelas 6']
];

$rowKelas = 8;
foreach ($kelasInfo as $info) {
    $sheet->setCellValue('I' . $rowKelas, $info[0]);
    $sheet->setCellValue('J' . $rowKelas, $info[1]);
    $rowKelas++;
}

$sheet->getStyle('I7:J' . ($rowKelas - 1))->applyFromArray([
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
]);

$sheet->getStyle('I8:J' . ($rowKelas - 1))->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getStartColor()->setRGB('F8F9FA');

$sheet->getColumnDimension('I')->setWidth(15);
$sheet->getColumnDimension('J')->setWidth(20);


    // Tambahkan informasi ID Tahun Ajaran
$sheet->setCellValue('L7', 'REFERENSI ID TAHUN AJARAN:');
$sheet->getStyle('L7')->getFont()->setBold(true);

$tahunInfo = [
    ['1', '2023/2024'],
    ['2', '2024/2025'],
    ['3', '2025/2026'],
];

$rowTahun = 8;
foreach ($tahunInfo as $info) {
    $sheet->setCellValue('L' . $rowTahun, $info[0]);
    $sheet->setCellValue('M' . $rowTahun, $info[1]);
    $rowTahun++;
}

$sheet->getStyle('L7:M' . ($rowTahun - 1))->applyFromArray([
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
]);

$sheet->getStyle('L8:M' . ($rowTahun - 1))->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getStartColor()->setRGB('F8F9FA');

$sheet->getColumnDimension('L')->setWidth(20);
$sheet->getColumnDimension('M')->setWidth(25);

    // Tambahkan validasi untuk kolom Jenis Kelamin
    $validation = $sheet->getCell('D9')->getDataValidation();
    $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
    $validation->setFormula1('"L,P"');
    $validation->setAllowBlank(false);
    $validation->setShowDropDown(true);
    $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
    $validation->setErrorTitle('Input Error');
    $validation->setError('Pilih "L" untuk Laki-laki atau "P" untuk Perempuan');

    // Tambahkan validasi untuk kolom ID Tahun Ajaran
$validationTahun = $sheet->getCell('F8')->getDataValidation();
$validationTahun->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
// Misal: tahun ajaran tersedia 1,2,3
$validationTahun->setFormula1('"1,2,3"'); 
$validationTahun->setAllowBlank(false);
$validationTahun->setShowDropDown(true);
$validationTahun->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
$validationTahun->setErrorTitle('Input Error');
$validationTahun->setError('Masukkan ID Tahun Ajaran sesuai referensi di sheet.');

for ($i = 9; $i <= 100; $i++) {
    $validationTahun = $sheet->getCell('F' . $i)->getDataValidation();
    $validationTahun->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
    $validationTahun->setFormula1('"1,2,3"'); // update sesuai tahun ajaran di database
    $validationTahun->setAllowBlank(false);
    $validationTahun->setShowDropDown(true);
}

    // Copy validasi ke 100 baris ke bawah
    for ($i = 9; $i <= 100; $i++) {
        $validation = $sheet->getCell('D' . $i)->getDataValidation();
        $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $validation->setFormula1('"L,P"');
        $validation->setAllowBlank(false);
        $validation->setShowDropDown(true);
    }

    // Tambahkan sheet informasi kelas
    $infoSheet = $spreadsheet->createSheet();
    $infoSheet->setTitle('Informasi Kelas');
    
    // Tambahkan judul di sheet informasi
    $infoSheet->setCellValue('A1', 'INFORMASI ID KELAS');
    $infoSheet->mergeCells('A1:C1');
    $infoSheet->getStyle('A1:C1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 14],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'FFE699'],
        ],
    ]);

    // Tambahkan catatan penting
    $infoSheet->setCellValue('A2', 'PENTING: Gunakan ID Kelas yang sesuai saat mengisi data siswa!');
    $infoSheet->mergeCells('A2:C2');
    $infoSheet->getStyle('A2')->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FF0000']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ]);
    
    // Header untuk informasi kelas
    $infoSheet->setCellValue('A4', 'ID Kelas');
    $infoSheet->setCellValue('B4', 'Nama Kelas');
    $infoSheet->setCellValue('C4', 'Wali Kelas');

    // Ambil data kelas beserta wali kelasnya
    $stmt = $pdo->query("
        SELECT k.id, k.nama_kelas, COALESCE(g.nama, '-') as wali_kelas 
        FROM kelas k 
        LEFT JOIN guru g ON k.guru_id = g.id 
        ORDER BY k.nama_kelas
    ");
    $kelas_list = $stmt->fetchAll();

    // Spasi antar bagian
$startRowTahun = count($kelas_list) + 7;

$infoSheet->setCellValue('A' . $startRowTahun, 'INFORMASI ID TAHUN AJARAN');
$infoSheet->mergeCells('A' . $startRowTahun . ':B' . $startRowTahun);
$infoSheet->getStyle('A' . $startRowTahun)->applyFromArray([
    'font' => ['bold' => true, 'size' => 14],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'FFE699'],
    ],
]);

$infoSheet->setCellValue('A' . ($startRowTahun + 1), 'ID Tahun Ajaran');
$infoSheet->setCellValue('B' . ($startRowTahun + 1), 'Tahun Ajaran');

// Ambil data tahun ajaran dari database
$stmtTahun = $pdo->query("SELECT id_tahun, tahun FROM tahun_ajaran ORDER BY tahun DESC");
$tahunList = $stmtTahun->fetchAll();

// Tulis tahun ajaran
$row = $startRowTahun + 2;
foreach ($tahunList as $tahun) {
    $infoSheet->setCellValue('A' . $row, $tahun['id_tahun']);
    $infoSheet->setCellValue('B' . $row, $tahun['tahun']);
    $row++;
}

// Styling header tahun ajaran
$infoSheet->getStyle('A' . ($startRowTahun + 1) . ':B' . ($startRowTahun + 1))->applyFromArray($headerStyle);
$infoSheet->getStyle('A' . ($startRowTahun + 2) . ':B' . ($row - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);


    // Style untuk header informasi
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '4A90E2'],
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
            ],
        ],
    ];
    $sheet->getStyle('A8:G8')->applyFromArray($headerStyle);

    // Set lebar kolom
    $infoSheet->getColumnDimension('A')->setWidth(20);
    $infoSheet->getColumnDimension('B')->setWidth(30);
    $infoSheet->getColumnDimension('C')->setWidth(35);

    // Isi data kelas
    $row = 5;
    foreach ($kelas_list as $kelas) {
        $infoSheet->setCellValue('A' . $row, $kelas['id']);
        $infoSheet->setCellValue('B' . $row, $kelas['nama_kelas']);
        $infoSheet->setCellValue('C' . $row, $kelas['wali_kelas']);
        
        // Style untuk baris data
        $infoSheet->getStyle('A'.$row.':C'.$row)->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);
        
        // Highlight baris alternate
        if ($row % 2 == 0) {
            $infoSheet->getStyle('A'.$row.':C'.$row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->setStartColor(new \PhpOffice\PhpSpreadsheet\Style\Color('F5F5F5'));
        }
        
        $row++;
    }

    // Tambahkan catatan penggunaan
    $noteRow = $row + 1;
    $infoSheet->setCellValue('A' . $noteRow, 'Catatan:');
    $infoSheet->mergeCells('A'.$noteRow.':C'.$noteRow);
    $infoSheet->getStyle('A'.$noteRow)->getFont()->setBold(true);

$notes = [
    '1. Gunakan ID Kelas yang tertera di kolom "ID Kelas" untuk mengisi data siswa.',
    '2. Gunakan ID Tahun Ajaran yang tersedia di bagian "INFORMASI ID TAHUN AJARAN".',
    '3. Pastikan ID Kelas dan Tahun Ajaran yang digunakan sesuai dengan data tahun berjalan.',
    '4. Jika ragu, tanyakan kepada admin sistem.',
];

    foreach ($notes as $index => $note) {
        $noteRow++;
        $infoSheet->mergeCells('A' . ($startRowTahun - 1) . ':C' . ($startRowTahun - 1));
        $infoSheet->setCellValue('A' . ($startRowTahun - 1), ''); // Kosongkan sebagai separator
        $infoSheet->getStyle('A' . ($startRowTahun - 1))->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('DDDDDD');
    }

    // Kembali ke sheet pertama
    $spreadsheet->setActiveSheetIndex(0);

    // Bersihkan output buffer
    if (ob_get_length()) ob_clean();
    
    // Set header untuk download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Template_Import_Siswa.xlsx"');
    header('Cache-Control: max-age=0');
    header('Cache-Control: max-age=1');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Cache-Control: cache, must-revalidate');
    header('Pragma: public');

    // Simpan file
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
} catch (Exception $e) {
    // Tampilkan error
    echo "Terjadi kesalahan: " . $e->getMessage();
    exit;
}
?> 