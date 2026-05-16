<?php
require 'auth.php';
require 'koneksi.php';

$role = $_SESSION['role'] ?? 'mahasiswa';
if ($role !== 'admin') {
    header("Location: index.php");
    exit;
}

$nama_admin = $_SESSION['nama'] ?? 'Administrator TI';
$err  = '';
$msg  = '';

/*
 |--------------------------------------
 | HAPUS MAHASISWA
 |--------------------------------------
*/
if (isset($_GET['hapus'])) {
    $nim_hapus = $_GET['hapus'];
    $nim_safe  = mysqli_real_escape_string($conn, $nim_hapus);

    // Hapus juga riwayat peminjaman miliknya (opsional)
    mysqli_query($conn, "DELETE FROM peminjaman WHERE nim = '$nim_safe'");
    mysqli_query($conn, "DELETE FROM mahasiswa WHERE nim = '$nim_safe'");

    $msg = "Data mahasiswa dengan NIM $nim_hapus telah dihapus.";
}

/*
 |--------------------------------------
 | RESET PASSWORD -> JADI NIM
 |--------------------------------------
*/
if (isset($_GET['reset'])) {
    $nim_reset = $_GET['reset'];
    $nim_safe  = mysqli_real_escape_string($conn, $nim_reset);

    $cek = mysqli_query($conn, "SELECT nim FROM mahasiswa WHERE nim = '$nim_safe' LIMIT 1");
    if ($row = mysqli_fetch_assoc($cek)) {
        $password_baru = $nim_reset; // password baru = NIM
        $hash          = password_hash($password_baru, PASSWORD_DEFAULT);

        $hash_safe   = mysqli_real_escape_string($conn, $hash);
        $plain_safe  = mysqli_real_escape_string($conn, $password_baru);

        mysqli_query($conn,
            "UPDATE mahasiswa
             SET password = '$hash_safe',
                 password_text = '$plain_safe'
             WHERE nim = '$nim_safe'
             LIMIT 1"
        );

        $msg = "Password mahasiswa dengan NIM $nim_reset telah di-reset menjadi NIM ( $nim_reset ).";
    } else {
        $err = "NIM yang akan di-reset tidak ditemukan.";
    }
}

/*
 |--------------------------------------
 | UPDATE PASSWORD DARI TABEL
 |--------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $nim_upd       = trim($_POST['nim'] ?? '');
    $password_baru = trim($_POST['password_baru'] ?? '');

    if ($nim_upd === '' || $password_baru === '') {
        $err = "NIM dan Password baru harus diisi untuk update.";
    } else {
        $nim_safe  = mysqli_real_escape_string($conn, $nim_upd);
        $cek = mysqli_query($conn, "SELECT nim FROM mahasiswa WHERE nim = '$nim_safe' LIMIT 1");

        if (!mysqli_fetch_assoc($cek)) {
            $err = "NIM yang akan diubah password-nya tidak ditemukan.";
        } else {
            $hash        = password_hash($password_baru, PASSWORD_DEFAULT);
            $hash_safe   = mysqli_real_escape_string($conn, $hash);
            $plain_safe  = mysqli_real_escape_string($conn, $password_baru);

            mysqli_query($conn,
                "UPDATE mahasiswa
                 SET password = '$hash_safe',
                     password_text = '$plain_safe'
                 WHERE nim = '$nim_safe'
                 LIMIT 1"
            );

            $msg = "Password mahasiswa dengan NIM $nim_upd berhasil diupdate.";
        }
    }
}

/*
 |--------------------------------------
 | TAMBAH MAHASISWA BARU
 |--------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah'])) {
    $nim      = trim($_POST['nim'] ?? '');
    $nama     = trim($_POST['nama'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $prodi    = trim($_POST['prodi'] ?? '');
    $no_hp    = trim($_POST['no_hp'] ?? '');

    if ($nim === '' || $nama === '' || $password === '') {
        $err = "NIM, Nama, dan Password wajib diisi.";
    } else {
        $nim_safe = mysqli_real_escape_string($conn, $nim);

        // Cek apakah NIM sudah ada
        $cek = mysqli_query($conn, "SELECT nim FROM mahasiswa WHERE nim = '$nim_safe' LIMIT 1");
        if (mysqli_fetch_assoc($cek)) {
            $err = "NIM sudah terdaftar. Silakan gunakan NIM lain atau hapus data lama.";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $nama_safe  = mysqli_real_escape_string($conn, $nama);
            $prodi_safe = mysqli_real_escape_string($conn, $prodi);
            $nohp_safe  = mysqli_real_escape_string($conn, $no_hp);
            $hash_safe  = mysqli_real_escape_string($conn, $password_hash);
            $plain_safe = mysqli_real_escape_string($conn, $password);

            mysqli_query($conn,
                "INSERT INTO mahasiswa (nim, nama, password, password_text, prodi, no_hp)
                 VALUES (
                    '$nim_safe',
                    '$nama_safe',
                    '$hash_safe',
                    '$plain_safe',
                    '$prodi_safe',
                    '$nohp_safe'
                 )"
            );

            $msg = "Mahasiswa dengan NIM $nim berhasil ditambahkan.";
        }
    }
}

/*
 |--------------------------------------
 | AMBIL SEMUA MAHASISWA
 |--------------------------------------
*/
$list = mysqli_query($conn, "SELECT * FROM mahasiswa ORDER BY nim ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Mahasiswa • DLSB-TI FIFO</title>
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
                <div class="header-title">Data Mahasiswa Peminjam</div>
                <div class="header-subtitle">
                    Administrator TI mengelola akun mahasiswa yang dapat login ke sistem.
                </div>
            </div>
        </div>
        <div>
            <span class="badge">
                <?php echo 'Login: '.htmlspecialchars($nama_admin).' • ADMIN'; ?>
            </span>
            <a href="index.php" class="btn-ghost" style="margin-left:8px;">← Kembali</a>
        </div>
    </div>

    <!-- NOTIFIKASI -->
    <?php if ($err): ?>
        <div class="login-error"><?php echo htmlspecialchars($err); ?></div>
    <?php endif; ?>

    <?php if ($msg && !$err): ?>
        <div class="login-error" style="background:#c8e6c9; border-color:#1b5e20; color:#1b5e20;">
            <?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <!-- FORM TAMBAH MAHASISWA -->
    <h3>Tambah Mahasiswa Baru</h3>
    <form method="post">
        <input type="hidden" name="tambah" value="1">

        <label>NIM Mahasiswa</label>
        <input type="text" name="nim" placeholder="Contoh: 240170030" required>

        <label>Nama Mahasiswa</label>
        <input type="text" name="nama" placeholder="Nama lengkap" required>

        <label>Password Login</label>
        <input type="password" name="password" placeholder="Password akun mahasiswa" required>

        <label>Program Studi</label>
        <input type="text" name="prodi" placeholder="Contoh: S1 Teknik Informatika">

        <label>Nomor HP</label>
        <input type="text" name="no_hp" placeholder="08xxxxxxxxxx">

        <button type="submit" class="btn-primary" style="margin-top:10px;">
            Simpan Mahasiswa
        </button>
    </form>

    <!-- TABEL DAFTAR MAHASISWA -->
    <h3>Daftar Mahasiswa Terdaftar</h3>
    <div class="table-wrapper">
        <table>
            <tr>
                <th>NIM</th>
                <th>Nama</th>
                <th>Prodi</th>
                <th>No HP</th>
                <th>Password (Teks)</th>
                <th>Aksi</th>
            </tr>

            <?php if (mysqli_num_rows($list) > 0): ?>
                <?php while ($m = mysqli_fetch_assoc($list)) { ?>
                    <tr>
                        <td><?php echo $m['nim']; ?></td>
                        <td><?php echo $m['nama']; ?></td>
                        <td><?php echo $m['prodi']; ?></td>
                        <td><?php echo $m['no_hp']; ?></td>

                        <!-- FORM EDIT PASSWORD LANGSUNG DI TABEL -->
                        <td>
                            <form method="post" style="display:flex; gap:4px; align-items:center;">
                                <input type="hidden" name="update_password" value="1">
                                <input type="hidden" name="nim" value="<?php echo $m['nim']; ?>">
                                <input type="text"
                                       name="password_baru"
                                       value="<?php echo htmlspecialchars($m['password_text']); ?>"
                                       style="width:120px; padding:2px 4px; font-size:12px;">
                                <button type="submit" class="btn-ghost" style="padding:2px 6px; font-size:12px;">
                                    Simpan
                                </button>
                            </form>
                        </td>

                        <td>
                            <!-- RESET PASSWORD ke NIM -->
                            <a href="mahasiswa.php?reset=<?php echo $m['nim']; ?>"
                               onclick="return confirm('Reset password mahasiswa ini menjadi NIM (<?php echo $m['nim']; ?>)?')">
                                Reset
                            </a>
                            &nbsp;|&nbsp;
                            <!-- HAPUS -->
                            <a href="mahasiswa.php?hapus=<?php echo $m['nim']; ?>"
                               onclick="return confirm('Hapus mahasiswa ini beserta riwayat peminjamannya?')">
                                Hapus
                            </a>
                        </td>
                    </tr>
                <?php } ?>
            <?php else: ?>
                <tr><td colspan="6">Belum ada mahasiswa terdaftar.</td></tr>
            <?php endif; ?>

        </table>
    </div>

    <div class="footer">
        Modul Data Mahasiswa • DLSB-TI FIFO
    </div>

</div>
</body>
</html>
