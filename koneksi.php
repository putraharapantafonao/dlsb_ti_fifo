<?php
// koneksi.php - versi InfinityFree

$host = "localhost";
$user = "root";
$pass = "";
$db   = "dlsb_ti_fifo";


$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}
// Tambahkan ini:
date_default_timezone_set('Asia/Jakarta');
mysqli_query($conn, "SET time_zone = '+07:00'");
