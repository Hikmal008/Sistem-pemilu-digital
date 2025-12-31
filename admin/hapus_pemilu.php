<?php
// File: admin/hapus_pemilu.php
// Deskripsi: Menghapus data pemilu

session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Cek apakah user adalah admin
check_admin();

// Cek apakah ada ID pemilu
if (!isset($_GET['id']) || empty($_GET['id'])) {
    set_flash_message('danger', 'ID pemilu tidak ditemukan!');
    redirect('pemilu.php');
}

$id_election = clean_input($_GET['id']);

// Ambil data pemilu
$query = "SELECT * FROM elections WHERE id_election = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id_election);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    set_flash_message('danger', 'Pemilu tidak ditemukan!');
    redirect('pemilu.php');
}

$pemilu = mysqli_fetch_assoc($result);

// Cek status - hanya draft yang bisa dihapus
if ($pemilu['status'] != 'draft') {
    set_flash_message('danger', 'Hanya pemilu dengan status Draft yang dapat dihapus!');
    redirect('pemilu.php');
}

// Cek apakah ada kandidat
$query_kandidat = "SELECT COUNT(*) as total FROM kandidat WHERE id_election = ?";
$stmt_kandidat = mysqli_prepare($conn, $query_kandidat);
mysqli_stmt_bind_param($stmt_kandidat, "i", $id_election);
mysqli_stmt_execute($stmt_kandidat);
$total_kandidat = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_kandidat))['total'];

if ($total_kandidat > 0) {
    set_flash_message('danger', 'Tidak dapat menghapus pemilu yang sudah memiliki kandidat!');
    redirect('pemilu.php');
}

// Hapus pemilu
$query_delete = "DELETE FROM elections WHERE id_election = ?";
$stmt_delete = mysqli_prepare($conn, $query_delete);
mysqli_stmt_bind_param($stmt_delete, "i", $id_election);

if (mysqli_stmt_execute($stmt_delete)) {
    set_flash_message('success', 'Pemilu berhasil dihapus!');
} else {
    set_flash_message('danger', 'Gagal menghapus pemilu!');
}

mysqli_stmt_close($stmt_delete);
redirect('pemilu.php');
?>