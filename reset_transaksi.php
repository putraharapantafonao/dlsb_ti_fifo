<?php
// reset_transaksi.php
require 'auth.php';
require 'koneksi.php';

// Pastikan hanya ADMIN yang boleh akses
$role = $_SESSION['role'] ?? '';
if ($role !== 'admin') {
    die("Akses ditolak. Hanya Administrator TI yang dapat mereset data transaksi.");
}

// Proses reset hanya jika datang dari POST (lebih aman)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Hapus semua data di tabel peminjaman
    $delete = mysqli_query($conn, "DELETE FROM peminjaman");

    if (!$delete) {
        die("Gagal menghapus data peminjaman: " . mysqli_error($conn));
    }

    // Reset AUTO_INCREMENT ke 1
    $resetAI = mysqli_query($conn, "ALTER TABLE peminjaman AUTO_INCREMENT = 1");

    if (!$resetAI) {
        die("Data terhapus, tapi gagal reset AUTO_INCREMENT: " . mysqli_error($conn));
    }

    // Kembali ke halaman peminjaman dengan pesan sukses
    header("Location: peminjaman.php?reset=ok");
    exit;
} else {
    // Jika bukan POST, tolak
    die("Metode tidak diizinkan.");
}
