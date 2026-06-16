<?php
// =============================================================
// Konfigurasi Koneksi Database MySQL (mysqli)
// =============================================================
//
// NOTE — Sinkronisasi untuk Load Balancer:
// Kedua server (Server_1 dan Server_2) harus mengakses DATABASE
// YANG SAMA. Ada dua opsi:
//
//   Opsi A) Satu Database Server terpisah (RDS):
//           - Set $db_host ke endpoint RDS (mis: mydb.xxxx.us-east-1.rds.amazonaws.com)
//           - Kedua instance EC2 akan connect ke RDS yang sama.
//             File ini boleh identik di kedua server.
//
//   Opsi B) Database di masing-masing instance (tidak disarankan):
//           - Masing-masing server punya database sendiri.
//           - $db_host tetap 'localhost', tapi data di kedua server
//             harus dijaga tetap sinkron secara manual.
//
// Rekomendasi: gunakan Opsi A (Amazon RDS) agar data konsisten
// dan file db_connect.php bisa identik di kedua server.
// =============================================================

$db_host = "localhost"; // host MySQL (ganti ke endpoint RDS jika pakai Opsi A)
$db_user = "root"; // username MySQL
$db_pass = ""; // password MySQL
$db_name = "tugas_cloud"; // nama database

/* Membuat koneksi */
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

/* Cek koneksi */
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

/* Set charset UTF-8 agar mendukung karakter latin */
$conn->set_charset("utf8mb4");
