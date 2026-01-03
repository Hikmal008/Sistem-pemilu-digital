<?php
// File: admin/logout.php
// Deskripsi: Logout untuk admin

session_start();
require_once '../config/database.php';

// Hapus semua session
session_unset();
session_destroy();

// Redirect ke halaman login dengan pesan
session_start();
set_flash_message('success', 'Anda berhasil logout!');
redirect('../auth/login.php');
