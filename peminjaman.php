<?php
require 'auth.php';
require 'koneksi.php';

$role     = $_SESSION['role'] ?? 'mahasiswa';
$is_admin = ($role === 'admin');
$user_nim = $_SESSION['nim'] ?? null;

// Ambil list mahasiswa & barang (untuk admin)
$mhs_list = mysqli_query($conn, "SELECT * FROM mahasiswa ORDER BY nim");
$brg_list = mysqli_query($conn, "SELECT * FROM barang ORDER BY nama_barang");

$error = "";

/*
 |--------------------------------------
 | PROSES PENGEMBALIAN BARANG
 |--------------------------------------
*/
if (isset($_GET['kembali'])) {
    $id_pinjam = (int)$_GET['kembali'];

    if ($is_admin) {
        $where_extra = "";
    } else {
        $nim_safe    = mysqli_real_escape_string($conn, $user_nim);
        $where_extra = " AND nim = '$nim_safe'";
    }

    mysqli_query($conn,
        "UPDATE peminjaman
         SET status = 'dikembalikan',
             tgl_kembali_real = NOW()
         WHERE id_pinjam = $id_pinjam $where_extra"
    );

    header("Location: peminjaman.php");
    exit;
}

/*
 |--------------------------------------
 | SIMPAN PEMINJAMAN BARU
 |--------------------------------------
*/
if (isset($_POST['simpan'])) {

    if ($is_admin) {
        $nim = trim($_POST['nim'] ?? '');
    } else {
        $nim = $user_nim;
    }

    $id_barang   = (int)($_POST['id_barang'] ?? 0);
    $jumlah      = (int)($_POST['jumlah'] ?? 0);
    $kelas_mk    = trim($_POST['kelas_mk'] ?? '');
    $nama_dosen  = trim($_POST['nama_dosen'] ?? '');
    $keterangan  = trim($_POST['keterangan'] ?? '');

    if ($nim === '' || !$id_barang || $jumlah <= 0) {
        $error = "NIM / Barang / Jumlah tidak boleh kosong.";
    } elseif ($kelas_mk === '' || $nama_dosen === '') {
        $error = "Kelas/MK dan Dosen Pengampu wajib diisi.";
    } else {

        // Cek apakah NIM ada di tabel mahasiswa
        $nim_safe = mysqli_real_escape_string($conn, $nim);
        $cek_mhs  = mysqli_query($conn,
            "SELECT nim, nama FROM mahasiswa WHERE nim = '$nim_safe' LIMIT 1"
        );
        $mhs = mysqli_fetch_assoc($cek_mhs);
        if (!$mhs) {
            $error = "NIM tidak terdaftar sebagai mahasiswa. Hubungi Administrator TI.";
        } else {

            // Cek stok barang
            $q_barang = mysqli_query($conn,
                "SELECT id_barang, nama_barang, stok
                 FROM barang
                 WHERE id_barang = $id_barang
                 LIMIT 1"
            );
            $data_barang = mysqli_fetch_assoc($q_barang);

            if (!$data_barang) {
                $error = "Barang tidak ditemukan.";
            } else {
                $stok_total = (int)$data_barang['stok'];
                $nama_brg   = $data_barang['nama_barang'];

                // Hitung total yang sedang dipinjam
                $q_dipinjam = mysqli_query($conn,
                    "SELECT COALESCE(SUM(jumlah),0) AS total_dipinjam
                     FROM peminjaman
                     WHERE id_barang = $id_barang AND status = 'dipinjam'"
                );
                $data_pinjam     = mysqli_fetch_assoc($q_dipinjam);
                $sedang_dipinjam = (int)$data_pinjam['total_dipinjam'];

                $tersedia = $stok_total - $sedang_dipinjam;

                if ($tersedia <= 0) {
                    $error = "Stok \"$nama_brg\" habis. Semua unit sedang dipinjam.";
                } elseif ($jumlah > $tersedia) {
                    $error = "Stok \"$nama_brg\" tidak mencukupi. Tersedia hanya $tersedia dari $stok_total unit (sedang dipinjam $sedang_dipinjam unit).";
                } else {
                    // Stok cukup → simpan peminjaman
                    $kelas_safe   = mysqli_real_escape_string($conn, $kelas_mk);
                    $dosen_safe   = mysqli_real_escape_string($conn, $nama_dosen);
                    $ket_safe     = mysqli_real_escape_string($conn, $keterangan);

                    mysqli_query($conn,
                        "INSERT INTO peminjaman
                         (nim, kelas_mk, nama_dosen, id_barang, jumlah, tgl_pinjam, tgl_kembali_rencana, status, keterangan)
                         VALUES (
                            '$nim_safe',
                            '$kelas_safe',
                            '$dosen_safe',
                            $id_barang,
                            $jumlah,
                            NOW(),
                            DATE_ADD(NOW(), INTERVAL 1 DAY),
                            'dipinjam',
                            '$ket_safe'
                         )"
                    );

                    header("Location: peminjaman.php");
                    exit;
                }
            }
        }
    }
}

/*
 |--------------------------------------
 | QUERY RIWAYAT PEMINJAMAN
 |--------------------------------------
*/
if ($is_admin) {
    $sql = "SELECT
                p.*,
                m.nama AS nama_mhs,
                b.nama_barang
            FROM peminjaman p
            JOIN mahasiswa m ON p.nim = m.nim
            JOIN barang b    ON p.id_barang = b.id_barang
            ORDER BY p.tgl_pinjam DESC, p.id_pinjam DESC";
} else {
    $nim_safe = mysqli_real_escape_string($conn, $user_nim);
    $sql = "SELECT
                p.*,
                m.nama AS nama_mhs,
                b.nama_barang
            FROM peminjaman p
            JOIN mahasiswa m ON p.nim = m.nim
            JOIN barang b    ON p.id_barang = b.id_barang
            WHERE p.nim = '$nim_safe'
            ORDER BY p.tgl_pinjam DESC, p.id_pinjam DESC";
}
$result = mysqli_query($conn, $sql);

/*
 |--------------------------------------
 | INFO MAHASISWA LOGIN
 |--------------------------------------
*/
$mhs_login = null;
if (!$is_admin && $user_nim) {
    $qm = mysqli_query($conn,
        "SELECT nim, nama, prodi, no_hp
         FROM mahasiswa
         WHERE nim = '".mysqli_real_escape_string($conn, $user_nim)."'
         LIMIT 1"
    );
    $mhs_login = mysqli_fetch_assoc($qm);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Peminjaman & Pengembalian • DLSB-TI FIFO</title>
    <link rel="stylesheet" href="style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<div class="container">

    <!-- HEADER -->
    <div class="header">
        <div class="header-brand">
            <div class="logo-group">
                <img src="img/logo-ti.png" alt="Logo TI" class="logo-square">
            </div>
            <div class="header-main">
                <div class="app-name">DLSB-TI FIFO</div>
                <div class="header-title">Peminjaman & Pengembalian Barang</div>
                <div class="header-subtitle">
                    <?php if ($is_admin): ?>
                        Kelola seluruh transaksi peminjaman & pengembalian barang dengan kontrol stok.
                    <?php else: ?>
                        Ajukan peminjaman dan kembalikan barang yang kamu pinjam.
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div>
            <a href="index.php" class="btn-ghost">← Kembali ke Dashboard</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="login-error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- FORM PEMINJAMAN -->
    <h3>Form Peminjaman Barang</h3>
    <form method="post">
        <?php if ($is_admin): ?>
            <label>Mahasiswa</label>
            <select name="nim" required>
                <option value="">-- Pilih Mahasiswa --</option>
                <?php
                mysqli_data_seek($mhs_list, 0);
                while ($m = mysqli_fetch_assoc($mhs_list)) {
                    echo "<option value='".$m['nim']."'>".$m['nim']." - ".$m['nama']."</option>";
                }
                ?>
            </select>
        <?php else: ?>
            <label>Mahasiswa</label>
            <input type="text" disabled
                   value="<?php
                       echo $mhs_login
                           ? ($mhs_login['nim'].' - '.$mhs_login['nama'])
                           : $user_nim;
                   ?>">
        <?php endif; ?>

        <label>Barang</label>
        <select name="id_barang" required>
            <option value="">-- Pilih Barang --</option>
            <?php
            mysqli_data_seek($brg_list, 0);
            while ($b = mysqli_fetch_assoc($brg_list)) {
                echo "<option value='".$b['id_barang']."'>[".$b['kode_barang']."] ".$b['nama_barang']."</option>";
            }
            ?>
        </select>

        <label>Jumlah</label>
        <input type="number" name="jumlah" min="1" required>

        <label>Kelas / Mata Kuliah</label>
        <input type="text" name="kelas_mk" placeholder="Contoh: TI A6 / Praktikum RPL" required>

        <label>Dosen Pengampu</label>
        <input type="text" name="nama_dosen" placeholder="Contoh: Bapak/Ibu ..." required>

        <label>Keterangan (opsional)</label>
        <input type="text" name="keterangan" placeholder="Keperluan praktikum / kegiatan">

        <button type="submit" name="simpan" class="btn-primary">
            Simpan Peminjaman
        </button>
    </form>

    <!-- RIWAYAT PEMINJAMAN -->
    <h3>Riwayat Peminjaman</h3>
    <div class="table-wrapper">
        <table>
            <tr>
                <th>ID</th>
                <?php if ($is_admin): ?>
                    <th>NIM</th>
                <?php endif; ?>
                <th>Nama Mahasiswa</th>
                <th>Kelas / MK</th>
                <th>Dosen Pengampu</th>
                <th>Barang</th>
                <th>Jumlah</th>
                <th>Waktu Pinjam</th>
                <th>Waktu Kembali Real</th>
                <th>Status</th>
                <th>Keterangan</th>
                <th>Detail</th>
                <th>Aksi</th>
            </tr>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
            <tr>
                <td><?php echo $row['id_pinjam']; ?></td>

                <?php if ($is_admin): ?>
                    <td><?php echo $row['nim']; ?></td>
                <?php endif; ?>

                <td><?php echo $row['nama_mhs']; ?></td>
                <td><?php echo $row['kelas_mk']; ?></td>
                <td><?php echo $row['nama_dosen']; ?></td>
                <td><?php echo $row['nama_barang']; ?></td>
                <td><?php echo $row['jumlah']; ?></td>
                <td><?php echo $row['tgl_pinjam']; ?></td>
                <td><?php echo $row['tgl_kembali_real'] ?: '—'; ?></td>
                <td><?php echo strtoupper($row['status']); ?></td>
                <td><?php echo $row['keterangan']; ?></td>

                <td>
                    <a href="detail_transaksi.php?id=<?php echo $row['id_pinjam']; ?>">
                        Lihat
                    </a>
                </td>

                <td>
                    <?php if ($row['status'] === 'dipinjam'): ?>
                        <a href="peminjaman.php?kembali=<?php echo $row['id_pinjam']; ?>"
                           onclick="return confirm('Konfirmasi pengembalian barang?')">
                           Kembalikan
                        </a>
                    <?php else: ?>
                        ✔ Kembali
                    <?php endif; ?>
                </td>
            </tr>
            <?php } ?>
        </table>
    </div>

    <div class="footer">
        Modul Peminjaman & Pengembalian • DLSB-TI FIFO
    </div>

</div>
</body>
</html>
