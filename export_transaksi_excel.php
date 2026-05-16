<?php
require 'auth.php';
require 'koneksi.php';

$role = $_SESSION['role'] ?? '';
if ($role !== 'admin') {
    die("Akses ditolak.");
}

// Ambil parameter filter
$mode  = $_GET['mode']  ?? 'range';
$start = $_GET['start'] ?? '';
$end   = $_GET['end']   ?? '';
$bulan = $_GET['bulan'] ?? '';

$where = "1=1";
$label = "Semua Periode";

// ==============================
//  FILTER PER TANGGAL (RANGE)
// ==============================
if ($mode === 'range') {

    if ($start && $end) {

        $start_safe = mysqli_real_escape_string($conn, $start);
        $end_safe   = mysqli_real_escape_string($conn, $end);

        // Rentang jam lengkap supaya data masuk
        $where = "
            p.tgl_pinjam >= '{$start_safe} 00:00:00' 
            AND p.tgl_pinjam <= '{$end_safe} 23:59:59'
        ";

        $label = "Periode: $start s.d. $end";

    } else {
        die("Parameter tanggal tidak lengkap.");
    }

// ==============================
//  FILTER PER BULAN
// ==============================
} elseif ($mode === 'month') {

    if ($bulan) {

        $bulan_safe = mysqli_real_escape_string($conn, $bulan);
        $where = "DATE_FORMAT(p.tgl_pinjam, '%Y-%m') = '$bulan_safe'";
        $label = "Bulan: " . date('F Y', strtotime($bulan.'-01'));

    } else {
        die("Parameter bulan tidak lengkap.");
    }
}

// ==============================
//   HEADER FILE EXCEL
// ==============================

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Laporan_Transaksi_".date('Ymd_His').".xls");
header("Pragma: no-cache");
header("Expires: 0");

// ==============================
//   QUERY DATA
// ==============================

$sql = "
SELECT 
    p.id_pinjam,
    p.nim,
    m.nama AS nama_mhs,
    p.kelas_mk,
    p.nama_dosen,
    b.nama_barang,
    p.jumlah,
    p.tgl_pinjam,
    p.tgl_kembali_real,
    p.status,
    p.keterangan
FROM peminjaman p
JOIN mahasiswa m ON p.nim = m.nim
JOIN barang b    ON p.id_barang = b.id_barang
WHERE $where
ORDER BY p.tgl_pinjam ASC, p.id_pinjam ASC
";

$result = mysqli_query($conn, $sql);

// Kalau query error → tampilkan pesan supaya tidak kosong
if (!$result) {
    echo "Terjadi kesalahan query: " . mysqli_error($conn);
    exit;
}
?>

<html>
<body>

<h2 style="text-align:center;">Laporan Transaksi Peminjaman Barang</h2>
<h4 style="text-align:center;"><?php echo $label; ?></h4>
<p style="text-align:center;">DLSB-TI FIFO • Jurusan Teknik Informatika • Universitas Malikussaleh</p>
<br>

<table border="1" cellspacing="0" cellpadding="5">
    <tr style="background:#e0e0e0; font-weight:bold; text-align:center;">
        <td>No</td>
        <td>ID</td>
        <td>NIM</td>
        <td>Nama Mahasiswa</td>
        <td>Kelas / MK</td>
        <td>Dosen Pengampu</td>
        <td>Barang</td>
        <td>Jumlah</td>
        <td>Waktu Pinjam</td>
        <td>Waktu Kembali</td>
        <td>Status</td>
        <td>Keterangan</td>
    </tr>

<?php
$no = 1;
$ada_data = false;

while ($row = mysqli_fetch_assoc($result)) { 
    $ada_data = true;
?>
<tr>
    <td><?php echo $no++; ?></td>
    <td><?php echo $row['id_pinjam']; ?></td>
    <td><?php echo $row['nim']; ?></td>
    <td><?php echo $row['nama_mhs']; ?></td>
    <td><?php echo $row['kelas_mk']; ?></td>
    <td><?php echo $row['nama_dosen']; ?></td>
    <td><?php echo $row['nama_barang']; ?></td>
    <td><?php echo $row['jumlah']; ?></td>
    <td><?php echo $row['tgl_pinjam']; ?></td>
    <td><?php echo $row['tgl_kembali_real']; ?></td>
    <td><?php echo strtoupper($row['status']); ?></td>
    <td><?php echo $row['keterangan']; ?></td>
</tr>
<?php } ?>

<?php if (!$ada_data): ?>
<tr>
    <td colspan="12" style="text-align:center;">
        Tidak ada data transaksi pada periode ini.
    </td>
</tr>
<?php endif; ?>

</table>

</body>
</html>
