-- ============================================================
-- Database Schema untuk Tugas Besar Komputasi Awan (LAMP Stack)
-- ============================================================

CREATE DATABASE IF NOT EXISTS tugas_cloud
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE tugas_cloud;

-- -----------------------------------------------------------
-- Tabel users: menyimpan akun untuk login
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50)  NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,          -- hash bcrypt
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- Tabel anggota: menyimpan data anggota kelompok
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS anggota (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama   VARCHAR(100)  NOT NULL,
    nim  VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- Seed: akun default (password: password)
-- -----------------------------------------------------------
INSERT INTO users (username, password) VALUES
    ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- -----------------------------------------------------------
-- Seed: contoh data anggota kelompok
-- -----------------------------------------------------------
INSERT INTO anggota (nama, nim) VALUES
    ('Alimuddin', '102022300026'),
    ('Naufal Rakadilah Hermawan', '102022300074'),
    ('Sultan Afdan Zamzami', '102022300135'),
    ('Muhammad Helmi Afriza', '102022300292'),
    ('Sanjaya Fathur Rahman', '102022330361');
