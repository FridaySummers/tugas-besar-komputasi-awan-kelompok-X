<?php
session_start();
require_once "db_connect.php";

// Proteksi halaman — redirect ke login jika belum login
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Ambil data anggota dari database
$result = $conn->query("SELECT nim, nama FROM anggota ORDER BY id ASC");
$anggota = [];
while ($row = $result->fetch_assoc()) {
    $anggota[] = $row;
}

// Indikator server untuk pengujian Load Balancer.
// Ganti [Nama Instance/AZ] dengan Availability Zone atau nama instance EC2.
define("SERVER_LABEL", "SERVER 2 - [Nama Instance/AZ]");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Tugas Cloud</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <span class="brand">Tugas Cloud</span>
        <span class="user-info">Halo, <?= htmlspecialchars(
            $_SESSION["username"],
        ) ?> |
            <a href="logout.php" class="logout-link">Logout</a>
        </span>
    </nav>

    <main class="container">
        <!-- Indikator Load Balancer -->
        <div class="server-badge"><?= SERVER_LABEL ?></div>

        <!-- Tabel Anggota Kelompok -->
        <section class="card">
            <h2>Data Anggota Kelompok</h2>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>NIM</th>
                            <th>Nama</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($anggota) > 0): ?>
                            <?php $no = 1; ?>
                            <?php foreach ($anggota as $a): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($a["nim"]) ?></td>
                                    <td><?= htmlspecialchars($a["nama"]) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="empty">Belum ada data anggota.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
