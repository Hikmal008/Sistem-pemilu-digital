<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('register.php');
}

// Ambil input
$username = clean_input($_POST['username']);
$email = clean_input($_POST['email']);
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];
$nama_lengkap = clean_input($_POST['nama_lengkap']);
$nik = clean_input($_POST['nik']);
$tanggal_lahir = clean_input($_POST['tanggal_lahir']);
$alamat = clean_input($_POST['alamat']);

$errors = [];

// Validasi kosong
if (
    empty($username) || empty($email) || empty($password) ||
    empty($confirm_password) || empty($nama_lengkap) ||
    empty($nik) || empty($tanggal_lahir) || empty($alamat)
) {
    $errors[] = "Semua field harus diisi!";
}

// Username
if (strlen($username) < 5) {
    $errors[] = "Username minimal 5 karakter!";
}

// Email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Format email tidak valid!";
}

// Password
if (strlen($password) < 6) {
    $errors[] = "Password minimal 6 karakter!";
}

if ($password !== $confirm_password) {
    $errors[] = "Password dan konfirmasi password tidak sama!";
}

// NIK
if (!preg_match('/^[0-9]{16}$/', $nik)) {
    $errors[] = "NIK harus 16 digit angka!";
}

// Umur
$birthdate = new DateTime($tanggal_lahir);
$today = new DateTime();
$age = $today->diff($birthdate)->y;

if ($age < 17) {
    $errors[] = "Anda harus berusia minimal 17 tahun untuk mendaftar!";
}

// Cek duplikat (AMAN)
$stmt = mysqli_prepare($conn, "SELECT id_user FROM users WHERE username = ? OR email = ? OR nik = ?");
mysqli_stmt_bind_param($stmt, "sss", $username, $email, $nik);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    $errors[] = "Username, email, atau NIK sudah terdaftar!";
}

// Jika error
if (!empty($errors)) {
    set_flash_message('danger', implode('<br>', $errors));
    redirect('register.php');
}

// Insert user
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$query = "INSERT INTO users 
(username, email, password, nama_lengkap, nik, tanggal_lahir, alamat, role, status)
VALUES (?, ?, ?, ?, ?, ?, ?, 'user', 'aktif')";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param(
    $stmt,
    "sssssss",
    $username,
    $email,
    $hashed_password,
    $nama_lengkap,
    $nik,
    $tanggal_lahir,
    $alamat
);

if (mysqli_stmt_execute($stmt)) {
    set_flash_message('success', 'Registrasi berhasil! Silakan login.');
    redirect('login.php');
} else {
    set_flash_message('danger', 'Registrasi gagal! Silakan coba lagi.');
    redirect('register.php');
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
