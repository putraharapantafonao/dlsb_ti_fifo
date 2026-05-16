<?php
require 'auth.php';
require 'koneksi.php';

$role        = $_SESSION['role'] ?? 'mahasiswa';
$nim_session = $_SESSION['nim'] ?? null;

// Ambil ID transaksi dari URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: peminjaman.php");
    exit;
}

// Kalau mahasiswa, hanya boleh lihat punyanya sendiri
$where_extra = "";
if ($role !== 'admin' && $nim_session) {
    $nim_safe    = mysqli_real_escape_string($conn, $nim_session);
    $where_extra = " AND p.nim = '$nim_safe' ";
}

$sql = "SELECT
            p.*,
            m.nama        AS nama_mhs,
            m.prodi,
            m.no_hp,
            b.nama_barang,
            b.kode_barang
        FROM peminjaman p
        JOIN mahasiswa m ON p.nim = m.nim
        JOIN barang b    ON p.id_barang = b.id_barang
        WHERE p.id_pinjam = $id $where_extra
        LIMIT 1";

$res  = mysqli_query($conn, $sql);
$data = mysqli_fetch_assoc($res);

// Kalau tidak ada data / tidak berhak
if (!$data) {
    header("Location: peminjaman.php");
    exit;
}

// Hitung lama peminjaman
$lama_hari = '-';
if (!empty($data['tgl_pinjam'])) {
    $start = new DateTime($data['tgl_pinjam']);
    if (!empty($data['tgl_kembali_real'])) {
        $end = new DateTime($data['tgl_kembali_real']);
    } else {
        $end = new DateTime(); // sekarang
    }
    $diff      = $start->diff($end);
    $lama_hari = $diff->days . ' hari';
}

$status_text = strtoupper($data['status']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Transaksi #<?php echo $data['id_pinjam']; ?> - DLSB-TI FIFO</title>
    <link rel="stylesheet" href="style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<div class="container">

    <div class="header">
        <div class="header-brand">
            <div class="logo-group">
                <img src="img/logo-ti.png" alt="Logo TI" class="logo-square">
            </div>
            <div class="header-main">
                <div class="app-name">DLSB-TI FIFO</div>
                <div class="header-title">Detail Transaksi Peminjaman Barang</div>
                <div class="header-subtitle">
                    Informasi lengkap satu transaksi peminjaman & pengembalian.
                </div>
            </div>
        </div>

        <div>
            <span class="badge">
                <?php echo 'Login: '.htmlspecialchars($_SESSION['nama'] ?? '').' • '.strtoupper($role); ?>
            </span>
            <a href="javascript:history.back()" class="btn-ghost" style="margin-left:8px;">← Kembali</a>
        </div>
    </div>

    <h3>Ringkasan Transaksi</h3>
    <div class="table-wrapper">
        <table>
            <tr><th>ID Transaksi</th><td><?php echo $data['id_pinjam']; ?></td></tr>
            <tr><th>Status</th><td><?php echo $status_text; ?></td></tr>
            <tr><th>Tanggal & Waktu Pinjam</th><td><?php echo $data['tgl_pinjam']; ?></td></tr>
            <tr><th>Tanggal & Waktu Kembali</th>
                <td><?php echo $data['tgl_kembali_real'] ?: 'Belum dikembalikan'; ?></td></tr>
            <tr><th>Lama Peminjaman</th><td><?php echo $lama_hari; ?></td></tr>
            <tr><th>Keterangan</th><td><?php echo $data['keterangan'] ?: '-'; ?></td></tr>
        </table>
    </div>

    <h3>Data Peminjam</h3>
    <div class="table-wrapper">
        <table>
            <tr><th>NIM</th><td><?php echo $data['nim']; ?></td></tr>
            <tr><th>Nama Mahasiswa</th><td><?php echo $data['nama_mhs']; ?></td></tr>
            <tr><th>Program Studi</th><td><?php echo $data['prodi']; ?></td></tr>
            <tr><th>Kelas / Mata Kuliah</th><td><?php echo $data['kelas_mk']; ?></td></tr>
            <tr><th>Dosen Pengampu</th><td><?php echo $data['nama_dosen']; ?></td></tr>
            <tr><th>Nomor HP</th><td><?php echo $data['no_hp']; ?></td></tr>
        </table>
    </div>

    <h3>Data Barang</h3>
    <div class="table-wrapper">
        <table>
            <tr><th>Kode Barang</th><td><?php echo $data['kode_barang']; ?></td></tr>
            <tr><th>Nama Barang</th><td><?php echo $data['nama_barang']; ?></td></tr>
            <tr><th>Jumlah Dipinjam</th><td><?php echo $data['jumlah']; ?></td></tr>
        </table>
    </div>

    <div class="footer">
        Detail Transaksi • DLSB-TI FIFO • Jurusan Teknik Informatika
    </div>

</div>
</body>
</html>
