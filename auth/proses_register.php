<?php
// File: auth/proses_register.php
// Deskripsi: Memproses registrasi pemilih baru

session_start();
require_once '../config/database.php';

// Cek apakah form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Ambil dan bersihkan input
    $username = clean_input($_POST['username']);
    $email = clean_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $nama_lengkap = clean_input($_POST['nama_lengkap']);
    $nik = clean_input($_POST['nik']);
    $tanggal_lahir = clean_input($_POST['tanggal_lahir']);
    $alamat = clean_input($_POST['alamat']);
    
    // Array untuk menampung error
    $errors = array();
    
    // Validasi input kosong
    if (empty($username) || empty($email) || empty($password) || 
        empty($nama_lengkap) || empty($nik) || empty($tanggal_lahir) || empty($alamat)) {
        $errors[] = "Semua field harus diisi!";
    }
    
    // Validasi username (minimal 5 karakter)
    if (strlen($username) < 5) {
        $errors[] = "Username minimal 5 karakter!";
    }
    
    // Validasi email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid!";
    }
    
    // Validasi password (minimal 6 karakter)
    if (strlen($password) < 6) {
        $errors[] = "Password minimal 6 karakter!";
    }
    
    // Validasi konfirmasi password
    if ($password !== $confirm_password) {
        $errors[] = "Password dan konfirmasi password tidak sama!";
    }
    
    // Validasi NIK (harus 16 digit angka)
    if (!preg_match('/^[0-9]{16}$/', $nik)) {
        $errors[] = "NIK harus 16 digit angka!";
    }
    
    // Validasi tanggal lahir (harus minimal 17 tahun)
    $today = new DateTime();
    $birthdate = new DateTime($tanggal_lahir);
    $age = $today->diff($birthdate)->y;
    
    if ($age < 17) {
        $errors[] = "Anda harus berusia minimal 17 tahun untuk mendaftar!";
    }
    
    // Cek apakah username sudah terdaftar
    $check_username = mysqli_query($conn, "SELECT username FROM users WHERE username = '$username'");
    if (mysqli_num_rows($check_username) > 0) {
        $errors[] = "Username sudah terdaftar!";
    }
    
    // Cek apakah email sudah terdaftar
    $check_email = mysqli_query($conn, "SELECT email FROM users WHERE email = '$email'");
    if (mysqli_num_rows($check_email) > 0) {
        $errors[] = "Email sudah terdaftar!";
    }
    
    // Cek apakah NIK sudah terdaftar
    $check_nik = mysqli_query($conn, "SELECT nik FROM users WHERE nik = '$nik'");
    if (mysqli_num_rows($check_nik) > 0) {
        $errors[] = "NIK sudah terdaftar!";
    }
    
    // Jika ada error, redirect kembali dengan pesan error
    if (count($errors) > 0) {
        $error_message = implode('<br>', $errors);
        set_flash_message('danger', $error_message);
        redirect('register.php');
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert data ke database
    $query = "INSERT INTO users (username, email, password, nama_lengkap, nik, tanggal_lahir, alamat, role, status) 
              VALUES (?, ?, ?, ?, ?, ?, ?, 'user', 'aktif')";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "sssssss", $username, $email, $hashed_password, $nama_lengkap, $nik, $tanggal_lahir, $alamat);
    
    if (mysqli_stmt_execute($stmt)) {
        set_flash_message('success', 'Registrasi berhasil! Silakan login dengan akun Anda.');
        redirect('login.php');
    } else {
        set_flash_message('danger', 'Registrasi gagal! Silakan coba lagi. Error: ' . mysqli_error($conn));
        redirect('register.php');
    }
    
    mysqli_stmt_close($stmt);
    
} else {
    // Jika diakses langsung tanpa POST
    redirect('register.php');
}

mysqli_close($conn);
?>
if ($age < 17) {
    $errors[] = "Anda harus berusia minimal 17 tahun untuk mendaftar!";
}

// Cek apakah username sudah terdaftar
$check_username = mysqli_query($conn, "SELECT username FROM users WHERE username = '$username'");
if (mysqli_num_rows($check_username) > 0) {
    $errors[] = "Username sudah terdaftar!";
}

// Cek apakah email sudah terdaftar
$check_email = mysqli_query($conn, "SELECT email FROM users WHERE email = '$email'");
if (mysqli_num_rows($check_email) > 0) {
    $errors[] = "Email sudah terdaftar!";
}

// Cek apakah NIK sudah terdaftar
$check_nik = mysqli_query($conn, "SELECT nik FROM users WHERE nik = '$nik'");
if (mysqli_num_rows($check_nik) > 0) {
    $errors[] = "NIK sudah terdaftar!";
}

// Jika ada error, redirect kembali dengan pesan error
if (count($errors) > 0) {
    $error_message = implode('<br>', $errors);
    set_flash_message('danger', $error_message);
    redirect('register.php');
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert data ke database
$query = "INSERT INTO users (username, email, password, nama_lengkap, nik, tanggal_lahir, alamat, role, status) 
          VALUES (?, ?, ?, ?, ?, ?, ?, 'user', 'aktif')";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "sssssss", $username, $email, $hashed_password, $nama_lengkap, $nik, $tanggal_lahir, $alamat);

if (mysqli_stmt_execute($stmt)) {
    set_flash_message('success', 'Registrasi berhasil! Silakan login dengan akun Anda.');
    redirect('login.php');
} else {
    set_flash_message('danger', 'Registrasi gagal! Silakan coba lagi.');
    redirect('register.php');
}

mysqli_stmt_close($stmt);
} else {
// Jika diakses langsung tanpa POST
redirect('register.php');
}
mysqli_close($conn);
?>