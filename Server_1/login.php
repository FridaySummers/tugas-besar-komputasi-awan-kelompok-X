<?php
session_start();
require_once "db_connect.php";

// Redirect ke index jika sudah login
if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

$error = "";
$server_theme = "theme-server-1";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username !== "" && $password !== "") {
        // Prepared statement untuk mencegah SQL Injection
        $stmt = $conn->prepare(
            "SELECT id, username, password FROM users WHERE username = ?",
        );
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row["password"])) {
                // Login berhasil — buat session
                $_SESSION["user_id"] = $row["id"];
                $_SESSION["username"] = $row["username"];
                header("Location: index.php");
                exit();
            }
        }
        $error = "Username atau password salah.";
        $stmt->close();
    } else {
        $error = "Silakan isi username dan password.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Tugas Besar Komputasi Awan</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-body <?= $server_theme ?>">
    <div class="login-card">
        <div class="login-icon">☁️</div>
        <h1>Server 1 へ ようこそ!!!</h1>
        <p class="subtitle">Tugas Besar Komputasi Awan </p>

        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" placeholder="Masukkan username" required>
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" placeholder="Masukkan password" required>
            </div>

            <button type="submit">Masuk</button>
        </form>

        <p class="login-footer">&copy; 2026 — Kelompok X</p>
    </div>
</body>
</html>
