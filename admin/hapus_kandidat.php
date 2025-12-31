<?php
// File: admin/hapus_kandidat.php
// Deskripsi: Menghapus data kandidat

session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Cek apakah user adalah admin
check_admin();

// Cek apakah ada ID kandidat
if (!isset($_GET['id']) || empty($_GET['id'])) {
    set_flash_message('danger', 'ID kandidat tidak ditemukan!');
    redirect('kandidat.php');
}

$id_kandidat = clean_input($_GET['id']);

// Ambil data kandidat untuk mendapatkan nama file foto
$query = "SELECT foto FROM kandidat WHERE id_kandidat = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id_kandidat);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    set_flash_message('danger', 'Kandidat tidak ditemukan!');
    redirect('kandidat.php');
}

$kandidat = mysqli_fetch_assoc($result);

// Hapus data kandidat dari database
$query_delete = "DELETE FROM kandidat WHERE id_kandidat = ?";
$stmt_delete = mysqli_prepare($conn, $query_delete);
mysqli_stmt_bind_param($stmt_delete, "i", $id_kandidat);

if (mysqli_stmt_execute($stmt_delete)) {
    // Hapus foto dari folder jika bukan default.jpg
    if ($kandidat['foto'] != 'default.jpg' && file_exists('../assets/img/kandidat/' . $kandidat['foto'])) {
        unlink('../assets/img/kandidat/' . $kandidat['foto']);
    }
    
    set_flash_message('success', 'Kandidat berhasil dihapus!');
} else {
    set_flash_message('danger', 'Gagal menghapus kandidat!');
}

mysqli_stmt_close($stmt_delete);
redirect('kandidat.php');
?>