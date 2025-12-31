<?php
// File: process/proses_voting.php
// UPDATE: Support multiple elections

session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Cek apakah user adalah pemilih
check_user();

// Cek apakah form disubmit
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    redirect('../user/voting.php');
}

// Ambil ID user dari session
$id_user = $_SESSION['user_id'];

// Ambil data dari form
if (!isset($_POST['id_kandidat']) || empty($_POST['id_kandidat']) || 
    !isset($_POST['id_election']) || empty($_POST['id_election'])) {
    set_flash_message('danger', 'Data tidak valid!');
    redirect('../user/voting.php');
}

$id_kandidat = clean_input($_POST['id_kandidat']);
$id_election = clean_input($_POST['id_election']);

// Cek apakah user sudah voting di pemilu ini
if (has_voted_in_election($id_user, $id_election)) {
    set_flash_message('danger', 'Anda sudah memberikan suara di pemilu ini!');
    redirect('../user/hasil.php?id=' . $id_election);
}

// Cek status pemilu
$election_status = get_election_status($id_election);
if (!$election_status || $election_status['status_real'] != 'berlangsung') {
    set_flash_message('danger', 'Pemilu tidak sedang berlangsung!');
    redirect('../user/index.php');
}

// Validasi apakah kandidat ada di database dan di pemilu yang benar
$check_kandidat = "SELECT id_kandidat FROM kandidat WHERE id_kandidat = ? AND id_election = ?";
$stmt_check = mysqli_prepare($conn, $check_kandidat);
mysqli_stmt_bind_param($stmt_check, "ii", $id_kandidat, $id_election);
mysqli_stmt_execute($stmt_check);
$result_check = mysqli_stmt_get_result($stmt_check);

if (mysqli_num_rows($result_check) == 0) {
    set_flash_message('danger', 'Kandidat tidak ditemukan atau tidak terdaftar di pemilu ini!');
    redirect('../user/voting.php');
}

// Ambil IP address pemilih
$ip_address = $_SERVER['REMOTE_ADDR'];

// Insert data voting ke database
$query = "INSERT INTO voting (id_user, id_kandidat, id_election, ip_address) 
          VALUES (?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "iiis", $id_user, $id_kandidat, $id_election, $ip_address);

if (mysqli_stmt_execute($stmt)) {
    set_flash_message('success', '🎉 Terima kasih! Suara Anda berhasil disimpan!');
    redirect('../user/hasil.php?id=' . $id_election);
} else {
    set_flash_message('danger', 'Gagal menyimpan suara! Silakan coba lagi.');
    redirect('../user/voting.php');
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>