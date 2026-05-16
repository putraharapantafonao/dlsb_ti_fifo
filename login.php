<?php
require 'koneksi.php';
session_start();

// Jika sudah login, arahkan ke halaman sesuai role
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: index.php");
    } else {
        header("Location: peminjaman.php");
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipe_login = $_POST['tipe_login'] ?? 'mahasiswa';
    $username   = trim($_POST['username'] ?? '');
    $password   = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Username dan Password wajib diisi.';
    } else {

        // ============ LOGIN ADMIN ============
        if ($tipe_login === 'admin') {
            // Admin hardcode sederhana
            if ($username === 'admin_ti' && $password === 'admin123') {
                $_SESSION['role'] = 'admin';
                $_SESSION['nama'] = 'Administrator TI';
                $_SESSION['nim']  = null;

                header("Location: index.php");
                exit;
            } else {
                $error = "Username atau password admin salah.";
            }
        }

        // ============ LOGIN MAHASISWA ============
        else {
            // Username = NIM
            $nim_input = $username;
            $nim_safe  = mysqli_real_escape_string($conn, $nim_input);

            $q = mysqli_query($conn,
                "SELECT nim, nama, password
                 FROM mahasiswa
                 WHERE nim = '$nim_safe'
                 LIMIT 1"
            );

            $mhs = mysqli_fetch_assoc($q);

            if (!$mhs) {
                $error = "NIM tidak terdaftar. Silakan hubungi Administrator TI.";
            } else {
                // Cek password hash
                if (password_verify($password, $mhs['password'])) {
                    // Sukses login
                    $_SESSION['role'] = 'mahasiswa';
                    $_SESSION['nim']  = $mhs['nim'];
                    $_SESSION['nama'] = $mhs['nama'];

                    header("Location: peminjaman.php"); // atau biodata_mahasiswa.php kalau mau
                    exit;
                } else {
                    $error = "Password salah. Silakan coba lagi.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - DLSB-TI FIFO</title>
    <link rel="stylesheet" href="style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<div class="container login-container">

    <div class="header">
        <div class="header-brand">
            <div class="logo-group">
                <img src="img/logo-ti.png" alt="Logo Teknik Informatika" class="logo-square">
            </div>
            <div class="header-main">
                <div class="app-name">DLSB-TI FIFO</div>
                <div class="header-title">Digitalisasi Layanan Sirkulasi Barang</div>
                <div class="header-subtitle">
                    Jurusan Teknik Informatika • Universitas Malikussaleh
                </div>
                <div class="app-tagline">
                    Masuk sebagai Administrator TI atau Mahasiswa terdaftar.
                </div>
            </div>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="login-error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <label>Masuk sebagai</label>
        <div class="radio-group" style="margin-bottom:10px;">
            <label style="margin-right:10px;">
                <input type="radio" name="tipe_login" value="admin"
                    <?php echo (($_POST['tipe_login'] ?? '') === 'admin') ? 'checked' : ''; ?>>
                Administrator TI
            </label>
            <label>
                <input type="radio" name="tipe_login" value="mahasiswa"
                    <?php echo (!isset($_POST['tipe_login']) || ($_POST['tipe_login'] ?? '') === 'mahasiswa') ? 'checked' : ''; ?>>
                Mahasiswa
            </label>
        </div>

        <label>Username</label>
        <input type="text" name="username"
               placeholder="Admin: admin_ti • Mahasiswa: NIM"
               required>

        <label>Password</label>
        <input type="password" name="password"
               placeholder="Password akun Anda"
               required>

        <button type="submit" class="btn-primary" style="margin-top:10px;">
            Login
        </button>
    </form>

    <div class="footer">
        DLSB-TI FIFO • Sistem Peminjaman & Pengembalian Barang
    </div>

</div>
</body>
</html>
