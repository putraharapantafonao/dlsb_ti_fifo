<?php
require 'auth.php';
include 'koneksi.php';

// hapus
if (isset($_GET['hapus'])) {
    $id_barang = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM peminjaman WHERE id_barang = $id_barang");
    mysqli_query($conn, "DELETE FROM barang WHERE id_barang = $id_barang");
    header("Location: barang.php");
    exit;
}

// simpan
if (isset($_POST['simpan'])) {
    $mode        = $_POST['mode'];
    $kode        = $_POST['kode_barang'];
    $nama        = $_POST['nama_barang'];
    $kategori    = $_POST['kategori'];
    $lokasi      = $_POST['lokasi'];
    $stok        = (int)$_POST['stok'];
    $satuan      = $_POST['satuan'];

    if ($mode == 'tambah') {
        mysqli_query($conn, "INSERT INTO barang
            (kode_barang, nama_barang, kategori, lokasi, stok, satuan)
            VALUES('$kode','$nama','$kategori','$lokasi',$stok,'$satuan')");
    } else {
        $id_lama = (int)$_POST['id_lama'];
        mysqli_query($conn, "UPDATE barang SET
            kode_barang='$kode',
            nama_barang='$nama',
            kategori='$kategori',
            lokasi='$lokasi',
            stok=$stok,
            satuan='$satuan'
            WHERE id_barang=$id_lama");
    }
    header("Location: barang.php");
    exit;
}

// edit
$mode = "tambah";
$id_edit = "";
$kode_edit = "";
$nama_edit = "";
$kategori_edit = "";
$lokasi_edit = "";
$stok_edit = "";
$satuan_edit = "";

if (isset($_GET['edit'])) {
    $id_edit = (int)$_GET['edit'];
    $q = mysqli_query($conn, "SELECT * FROM barang WHERE id_barang=$id_edit");
    $data = mysqli_fetch_assoc($q);
    if ($data) {
        $mode = "edit";
        $kode_edit = $data['kode_barang'];
        $nama_edit = $data['nama_barang'];
        $kategori_edit = $data['kategori'];
        $lokasi_edit = $data['lokasi'];
        $stok_edit = $data['stok'];
        $satuan_edit = $data['satuan'];
    }
}

$result = mysqli_query($conn, "SELECT * FROM barang ORDER BY nama_barang");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Barang - DLSB-TI</title>
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
                <div class="app-name">DLSB-TI</div>
                <div class="header-title">Data Barang</div>
                <div class="header-subtitle">
                    Daftar barang yang dapat dipinjam di Jurusan Teknik Informatika.
                </div>
            </div>
        </div>
        <a href="index.php" class="btn btn-ghost">← Kembali ke Dashboard</a>
    </div>

    <h3>Form Barang (<?php echo strtoupper($mode); ?>)</h3>
    <form method="post">
        <input type="hidden" name="mode" value="<?php echo $mode; ?>">
        <input type="hidden" name="id_lama" value="<?php echo $id_edit; ?>">

        <label>Kode Barang</label>
        <input type="text" name="kode_barang" required value="<?php echo $kode_edit; ?>">

        <label>Nama Barang</label>
        <input type="text" name="nama_barang" required value="<?php echo $nama_edit; ?>">

        <label>Kategori</label>
        <input type="text" name="kategori" value="<?php echo $kategori_edit; ?>">

        <label>Lokasi Penyimpanan</label>
        <input type="text" name="lokasi" value="<?php echo $lokasi_edit; ?>">

        <label>Stok</label>
        <input type="number" name="stok" required value="<?php echo $stok_edit; ?>">

        <label>Satuan</label>
        <input type="text" name="satuan" value="<?php echo $satuan_edit; ?>">

        <button type="submit" name="simpan" class="btn btn-primary">
            Simpan Data
        </button>
    </form>

    <h3>Daftar Barang</h3>
    <div class="table-wrapper">
        <table>
            <tr>
                <th>ID</th>
                <th>Kode</th>
                <th>Nama</th>
                <th>Kategori</th>
                <th>Lokasi</th>
                <th>Stok</th>
                <th>Satuan</th>
                <th>Aksi</th>
            </tr>
            <?php while($row = mysqli_fetch_assoc($result)) { ?>
            <tr>
                <td><?php echo $row['id_barang']; ?></td>
                <td><?php echo $row['kode_barang']; ?></td>
                <td><?php echo $row['nama_barang']; ?></td>
                <td><?php echo $row['kategori']; ?></td>
                <td><?php echo $row['lokasi']; ?></td>
                <td><?php echo $row['stok']; ?></td>
                <td><?php echo $row['satuan']; ?></td>
                <td>
                    <a href="barang.php?edit=<?php echo $row['id_barang']; ?>">Edit</a>
                    <a href="barang.php?hapus=<?php echo $row['id_barang']; ?>"
                       onclick="return confirm('Hapus data barang dan histori peminjaman barang ini?')">Hapus</a>
                </td>
            </tr>
            <?php } ?>
        </table>
    </div>

    <div class="footer">
        Modul Barang • DLSB-TI
    </div>

</div>
</body>
</html>
