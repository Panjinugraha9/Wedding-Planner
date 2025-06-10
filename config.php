<?php
/**
 * File: config.php (or database.php)
 * Deskripsi: Kelas untuk mengelola koneksi ke database.
 */
class Database {
    // --- SESUAIKAN DENGAN KONFIGURASI DATABASE ANDA ---
    private $host = "localhost";
    private $db_name = "rbplnewdb"; // Ganti dengan nama database Anda
    private $username = "root";     // Ganti dengan username database Anda
    private $password = "";         // Ganti dengan password database Anda
    // --------------------------------------------------

    public $conn;

    /**
     * Membuat dan mengembalikan koneksi database menggunakan PDO.
     * @return PDO|null Objek koneksi PDO jika berhasil, atau null jika gagal.
     */
    public function getConnection() {
        $this->conn = null; // Reset koneksi sebelumnya

        try {
            // Membuat koneksi PDO baru
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            
            // Mengatur mode error PDO ke exception untuk penanganan error yang lebih baik
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Opsi tambahan untuk koneksi yang lebih stabil (penting untuk prepared statements)
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        } catch(PDOException $exception) {
            // Jika koneksi gagal, tampilkan pesan error
            // Di lingkungan produksi, sebaiknya log error ini daripada menampilkannya ke pengguna
            error_log("Connection error: " . $exception->getMessage()); // Log error
            echo "Connection error. Please try again later."; // Pesan yang lebih ramah pengguna
            return null; // Kembalikan null jika koneksi gagal
        }

        return $this->conn; // Kembalikan objek koneksi yang berhasil
    }
}
?>
