<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'auth.php';
require 'koneksi.php';
$nama = $_SESSION['nama'] ?? 'User';
$role = $_SESSION['role'] ?? 'mahasiswa';

$total_mahasiswa = 0;
$total_barang    = 0;
$total_dipinjam  = 0;
$total_transaksi = 0;
$recent          = null;

if ($role === 'admin') {
    $r1 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS jml FROM mahasiswa"));
    $total_mahasiswa = $r1['jml'] ?? 0;

    $r2 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS jml FROM barang"));
    $total_barang = $r2['jml'] ?? 0;

    $r3 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS jml FROM peminjaman WHERE status='dipinjam'"));
    $total_dipinjam = $r3['jml'] ?? 0;

    $r4 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS jml FROM peminjaman"));
    $total_transaksi = $r4['jml'] ?? 0;

    $recent = mysqli_query($conn,
        "SELECT
            p.id_pinjam,
            p.nim,
            p.kelas_mk,
            p.nama_dosen,
            m.nama       AS nama_mhs,
            b.nama_barang,
            p.jumlah,
            p.tgl_pinjam,
            p.tgl_kembali_real,
            p.status
         FROM peminjaman p
         JOIN mahasiswa m ON p.nim = m.nim
         JOIN barang b    ON p.id_barang = b.id_barang
         ORDER BY p.id_pinjam DESC
         LIMIT 10"
    );
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>DLSB-TI FIFO - Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>

<body>
<div class="container">

    <!-- HEADER -->
    <div class="header">
        <div class="header-brand">
            <div class="logo-group">
                <img src="img/logo-ti.png" class="logo-square" alt="Logo TI">
            </div>
            <div class="header-main">
                <div class="app-name">DLSB-TI FIFO</div>
                <div class="header-title">Digitalisasi Layanan Sirkulasi Barang</div>
                <div class="header-subtitle">
                    Jurusan Teknik Informatika • Universitas Malikussaleh
                </div>
            </div>
        </div>

        <div>
            <span class="badge">
                <?php echo 'Login: '.htmlspecialchars($nama).' • '.strtoupper($role); ?>
            </span>
            <a href="logout.php" class="btn-ghost" style="margin-left:8px;">Logout</a>
        </div>
    </div>

    <!-- MENU UTAMA -->
    <div class="menu-grid">
        <?php if ($role === 'admin'): ?>
            <a href="mahasiswa.php" class="menu-item">
                <div class="menu-title">Data Mahasiswa</div>
                <div class="menu-desc">Kelola akun & biodata mahasiswa peminjam.</div>
            </a>

            <a href="barang.php" class="menu-item">
                <div class="menu-title">Data Barang</div>
                <div class="menu-desc">Daftar inventaris & stok barang TI.</div>
            </a>
        <?php endif; ?>

        <a href="peminjaman.php" class="menu-item">
            <div class="menu-title">Peminjaman & Pengembalian</div>
            <div class="menu-desc">
                <?php echo ($role === 'admin')
                    ? 'Kelola semua transaksi barang.'
                    : 'Ajukan peminjaman & pengembalian barang.'; ?>
            </div>
        </a>
    </div>

    <?php if ($role === 'admin'): ?>

        <!-- STATISTIK -->
        <h3>Ringkasan Data Peminjaman</h3>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Mahasiswa</div>
                <div class="stat-value"><?php echo $total_mahasiswa; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Total Barang</div>
                <div class="stat-value"><?php echo $total_barang; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Sedang Dipinjam</div>
                <div class="stat-value"><?php echo $total_dipinjam; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Total Transaksi</div>
                <div class="stat-value"><?php echo $total_transaksi; ?></div>
            </div>
        </div>

        <!-- 10 TRANSAKSI TERBARU -->
        <h3>10 Transaksi Peminjaman Terbaru</h3>
        <div class="table-wrapper">
            <table>
                <tr>
                    <th>ID</th>
                    <th>NIM</th>
                    <th>Nama Mahasiswa</th>
                    <th>Kelas / MK</th>
                    <th>Dosen Pengampu</th>
                    <th>Barang</th>
                    <th>Jumlah</th>
                    <th>Waktu Pinjam</th>
                    <th>Kembali Real</th>
                    <th>Status</th>
                    <th>Detail</th>
                </tr>
                <?php if ($recent && mysqli_num_rows($recent) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($recent)) { ?>
                        <tr>
                            <td><?php echo $row['id_pinjam']; ?></td>
                            <td><?php echo $row['nim']; ?></td>
                            <td><?php echo $row['nama_mhs']; ?></td>
                            <td><?php echo $row['kelas_mk']; ?></td>
                            <td><?php echo $row['nama_dosen']; ?></td>
                            <td><?php echo $row['nama_barang']; ?></td>
                            <td><?php echo $row['jumlah']; ?></td>
                            <td><?php echo $row['tgl_pinjam']; ?></td>
                            <td><?php echo $row['tgl_kembali_real'] ?: '—'; ?></td>
                            <td><?php echo strtoupper($row['status']); ?></td>
                            <td>
                                <a href="detail_transaksi.php?id=<?php echo $row['id_pinjam']; ?>">
                                    Lihat
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                <?php else: ?>
                    <tr><td colspan="11">Belum ada transaksi.</td></tr>
                <?php endif; ?>
            </table>
        </div>

        <!-- EXPORT LAPORAN – EXCEL SAJA -->
        <h3>Export Laporan Transaksi (Excel)</h3>

        <div class="export-grid">

            <!-- KIRI: EXPORT PER TANGGAL -->
            <div class="export-card">
                <h4>Export Per Tanggal</h4>

                <form method="get" action="export_transaksi_excel.php">
                    <input type="hidden" name="mode" value="range">
                    <label>Dari Tanggal</label>
                    <input type="date" name="start" required>
                    <label>Sampai Tanggal</label>
                    <input type="date" name="end" required>
                    <button type="submit" class="btn-primary" style="margin-top:8px;">
                        Export Excel (Per Tanggal)
                    </button>
                </form>
            </div>

            <!-- KANAN: EXPORT BULANAN -->
            <div class="export-card">
                <h4>Laporan Bulanan Otomatis</h4>

                <form method="get" action="export_transaksi_excel.php">
                    <input type="hidden" name="mode" value="month">
                    <label>Pilih Bulan</label>
                    <input type="month" name="bulan" required>
                    <button type="submit" class="btn-primary" style="margin-top:8px;">
                        Export Excel (Bulanan)
                    </button>
                </form>
            </div>

        </div>

        <div class="footer">
            Dashboard Administrator TI • DLSB-TI FIFO
        </div>

    <?php else: ?>

        <div class="footer">
            Kamu login sebagai Mahasiswa. Gunakan menu "Peminjaman & Pengembalian" untuk mengelola peminjaman barangmu.
        </div>

    <?php endif; ?>

</div>
</body>
</html>
