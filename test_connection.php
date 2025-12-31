<?php
// File: test_connection.php
// Deskripsi: File untuk testing koneksi database

require_once 'config/database.php';

echo "<h2>Test Koneksi Database</h2>";

if ($conn) {
    echo "<p style='color: green;'>✓ Koneksi database berhasil!</p>";
    echo "<p>Host: " . DB_HOST . "</p>";
    echo "<p>Database: " . DB_NAME . "</p>";
    
    // Test query
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        echo "<p>Jumlah user di database: " . $row['total'] . "</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Koneksi database gagal!</p>";
}
?>