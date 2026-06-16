# Tugas Besar Komputasi Awan — LAMP Stack dengan Load Balancer

Panduan implementasi website sederhana berbasis **LAMP Stack** (Linux, Apache, MySQL, PHP) di **2 instance AWS EC2** yang dihubungkan dengan **Load Balancer**.

---

## 📁 Struktur Proyek

```
.
├── database.sql              ← Skema + seed database (1× jalanin)
├── README.md                 ← Panduan ini
├── Server_1/                 ← → Deploy ke EC2 Instance 1
│   ├── db_connect.php
│   ├── index.php             ← badge: "SERVER 1 - [Nama Instance/AZ]"
│   ├── login.php
│   ├── logout.php
│   └── style.css
└── Server_2/                 ← → Deploy ke EC2 Instance 2
    ├── db_connect.php
    ├── index.php             ← badge: "SERVER 2 - [Nama Instance/AZ]"
    ├── login.php
    ├── logout.php
    └── style.css
```

Setiap server punya kode yang **identik** kecuali file `index.php` — badge-nya dibedakan agar mudah diidentifikasi saat load balancer mengarahkan trafik.

---

## 🧱 Arsitektur AWS

```
                         ┌───────────────┐
                         │    Route 53   │ (opsional)
                         └───────┬───────┘
                                 │
                         ┌───────▼───────┐
                         │  ALB / ELB    │
                         │  Load Balancer│
                         └───┬───────┬───┘
                             │       │
                    ┌────────▼──┐ ┌──▼────────┐
                    │ EC2-1     │ │ EC2-2     │
                    │ Server_1  │ │ Server_2  │
                    │ ┌───────┐ │ │ ┌───────┐ │
                    │ │Apache │ │ │ │Apache │ │
                    │ │  PHP  │ │ │ │  PHP  │ │
                    │ └───┬───┘ │ │ └───┬───┘ │
                    └─────┼─────┘ └─────┼─────┘
                          │             │
                          └──────┬──────┘
                                 │
                         ┌───────▼───────┐
                         │  Amazon RDS   │
                         │  (MySQL)      │
                         └───────────────┘
```

Kedua EC2 terhubung ke **satu database yang sama** (RDS) sehingga data konsisten.

---

## ✅ Prasyarat

| No | Kebutuhan | Keterangan |
|----|-----------|------------|
| 1 | Akun AWS | Aktif dan bisa membuat resources |
| 2 | AWS CLI | Terinstall di lokal (opsional) |
| 3 | Key Pair (.pem) | Untuk SSH ke EC2 |
| 4 | Remote Git repository | GitHub / GitLab / CodeCommit untuk push dan pull kode |
| 5 | Terminal | Untuk SSH dan perintah Git |

---

## 📦 Langkah 1 — Setup 2 EC2 Instances (Ubuntu 22.04)

### 1.1 Buat Instance Pertama (EC2-1)

1. Buka **AWS Console** → **EC2** → **Launch Instance**
2. Isi konfigurasi:

   | Parameter | Value |
   |-----------|-------|
   | Name | `server-1` |
   | AMI | **Ubuntu Server 22.04 LTS (HVM)** |
   | Instance type | `t2.micro` (Free Tier) |
   | Key pair | Pilih atau buat key pair baru |
   | Network settings | Allow SSH, HTTP, HTTPS |

3. **Security Group** — buka port:

   | Type | Protocol | Port | Source |
   |------|----------|------|--------|
   | SSH | TCP | 22 | `0.0.0.0/0` (atau IP-mu saja) |
   | HTTP | TCP | 80 | `0.0.0.0/0` |
   | HTTPS | TCP | 443 | `0.0.0.0/0` |

4. Klik **Launch Instance**.

### 1.2 Buat Instance Kedua (EC2-2)

Ulangi langkah 1.1 dengan:
- **Name:** `server-2`
- **Security Group:** sama (bisa pakai SG yang sama)

> 💡 Tempatkan kedua instance di **Availability Zone berbeda** (misal: EC2-1 di `us-east-1a`, EC2-2 di `us-east-1b`) agar terlihat efek AZ di load balancer.

---

## 📦 Langkah 2 — Install LAMP Stack di Kedua Instance

SSH ke masing-masing instance:

```bash
# Cari IP publik instance di AWS Console
ssh -i /path/ke/key.pem ubuntu@<IP-PUBLIK-EC2>
```

Setelah masuk, jalankan:

```bash
# Update package
sudo apt update && sudo apt upgrade -y

# Install Apache, MySQL client, dan PHP dengan ekstensi
sudo apt install -y apache2 mysql-client php php-mysqli php-bcmath

# Enable mod_rewrite
sudo a2enmod rewrite

# Restart Apache
sudo systemctl restart apache2

# Cek status
sudo systemctl status apache2
```

> **Catatan:** Kita install **MySQL client** saja — database-nya pakai Amazon RDS.

---

## 📦 Langkah 3 — Setup Database (Amazon RDS)

### 3.1 Buat RDS Instance

1. AWS Console → **RDS** → **Create database**
2. Pilih **MySQL** → **Free Tier**
3. Isi konfigurasi:

   | Parameter | Value |
   |-----------|-------|
   | DB instance identifier | `tugas-cloud-db` |
   | Master username | `admin` |
   | Master password | `TugasCloud123` |
   | DB instance class | `db.t3.micro` |
   | Storage | 20 GB (gp2) |
   | Public access | **Yes** (biar bisa diakses dari EC2) |
   | VPC security group | Buat baru: allow **MySQL/Aurora (3306)** dari **0.0.0.0/0** |

4. Tunggu sampai status **Available** (sekitar 5 menit).
5. Salin **Endpoint** (contoh: `tugas-cloud-db.xxxxxxx.us-east-1.rds.amazonaws.com`).

### 3.2 Import Skema Database

Dari **salah satu EC2** (cukup sekali):

```bash
# Download database.sql dulu (opsional)
# Atau copy langsung dari lokal via SCP

mysql -h <ENDPOINT-RDS> -u admin -p < database.sql
# Password: TugasCloud123
```

Atau dari lokal pakai MySQL Workbench / Cloud Shell.

---

## 📦 Langkah 4 — Push ke Remote Repository

Sebelum deploy ke EC2, push proyek ke remote repository (GitHub/GitLab):

```bash
git remote add origin <URL-REPO-ANDA>
git push -u origin main
```

---

## 📦 Langkah 5 — Clone & Deploy di Masing-masing EC2

### 5.1 Clone Repository

SSH ke **EC2-1**:

```bash
ssh -i /path/ke/key.pem ubuntu@<IP-PUBLIK-EC2-1>
```

Clone repo dan deploy Server_1:

```bash
cd /home/ubuntu
git clone <URL-REPO-ANDA> tugas-cloud

# Copy isi Server_1 ke document root Apache
sudo cp -r tugas-cloud/Server_1/* /var/www/html/
sudo chown -R www-data:www-data /var/www/html/
sudo chmod -R 755 /var/www/html/
```

### 5.2 Ulangi untuk EC2-2 (dengan Server_2)

SSH ke **EC2-2**:

```bash
ssh -i /path/ke/key.pem ubuntu@<IP-PUBLIK-EC2-2>

cd /home/ubuntu
git clone <URL-REPO-ANDA> tugas-cloud

# Copy isi Server_2 ke document root Apache
sudo cp -r tugas-cloud/Server_2/* /var/www/html/
sudo chown -R www-data:www-data /var/www/html/
sudo chmod -R 755 /var/www/html/
```

> 💡 **Alternatif symlink:** kalau ingin perubahan langsung ter-reflect tanpa copy ulang:
> ```bash
> sudo rm -rf /var/www/html
> sudo ln -s /home/ubuntu/tugas-cloud/Server_1 /var/www/html
> sudo chown -R www-data:www-data /home/ubuntu/tugas-cloud
> ```
> Tapi pastikan `<Directory>` di konfigurasi Apache mengizinkan `FollowSymlinks`.

### 5.3 Import Skema Database

Dari **salah satu EC2** (cukup sekali):

```bash
mysql -h <ENDPOINT-RDS> -u admin -p < /home/ubuntu/tugas-cloud/database.sql
# Password: TugasCloud123
```

---

## 📦 Langkah 6 — Konfigurasi `db_connect.php`

Edit di **kedua server**:

```bash
sudo nano /var/www/html/db_connect.php
```

Ubah baris `$db_host` dari `"localhost"` menjadi **Endpoint RDS**:

```php
$db_host = "tugas-cloud-db.xxxxxxx.us-east-1.rds.amazonaws.com";
$db_user = "admin";
$db_pass = "TugasCloud123";
$db_name = "tugas_cloud";
```

Kedua file boleh **identik** karena keduanya mengakses database yang sama.

---

## 📦 Langkah 7 — Verifikasi Manual

Coba akses langsung tiap instance via IP publik:

```
http://<IP-PUBLIK-EC2-1>/login.php
http://<IP-PUBLIK-EC2-2>/login.php
```

**Login:**
- **Username:** `admin`
- **Password:** `password`

Setelah login, badge di halaman utama:

| Instance | Badge |
|----------|-------|
| EC2-1 | **SERVER 1 - [Nama Instance/AZ]** |
| EC2-2 | **SERVER 2 - [Nama Instance/AZ]** |

---

## ⚖️ Langkah 8 — Setup Load Balancer (ALB)

### 7.1 Buat Target Group

1. AWS Console → **EC2** → **Target Groups** → **Create target group**
2. Isi:

   | Parameter | Value |
   |-----------|-------|
   | Target type | **Instances** |
   | Target group name | `tg-tugas-cloud` |
   | Protocol | **HTTP** |
   | Port | **80** |
   | VPC | Pilih VPC yang sama dengan EC2 |
   | Health check path | `/login.php` |
   | Health check | biarkan default |

3. Klik **Next** → centang **kedua EC2 instance** → **Include as pending below** → **Create target group**

### 7.2 Buat Application Load Balancer (ALB)

1. AWS Console → **EC2** → **Load Balancers** → **Create Load Balancer** → pilih **Application Load Balancer**
2. Isi:

   | Parameter | Value |
   |-----------|-------|
   | Name | `alb-tugas-cloud` |
   | Scheme | **Internet-facing** |
   | IP address type | **IPv4** |
   | VPC | Pilih yang sama dengan EC2 |
   | Mappings | Pilih **kedua AZ** tempat EC2 berada |
   | Security group | Buat baru: allow **HTTP (80)** dari **0.0.0.0/0** |
   | Listener | **HTTP:80** → forward ke **`tg-tugas-cloud`** |

3. Klik **Create load balancer** (tunggu 2-5 menit sampai status **Active**).

### 7.3 Testing Load Balancer

Salin **DNS name** ALB (contoh: `alb-tugas-cloud-xxxxxx.us-east-1.elb.amazonaws.com`), buka di browser:

```
http://alb-tugas-cloud-xxxxxx.us-east-1.elb.amazonaws.com/login.php
```

Login lalu **refresh halaman berkali-kali**. Badge akan bergantian:

| Request ke- | Badge |
|-------------|-------|
| 1 | **SERVER 1 - us-east-1a** |
| 2 | **SERVER 2 - us-east-1b** |
| 3 | **SERVER 1 - us-east-1a** |
| 4 | **SERVER 2 - us-east-1b** |

> ALB menggunakan algoritma **round-robin** secara default. Jika salah satu server mati, ALB otomatis mengarahkan semua trafik ke server yang sehat.

---

## 🔄 Sinkronisasi Kode (Saat Update)

Karena pakai Git, update kode cukup dua langkah:

**Lokal —** commit dan push perubahan:

```bash
git add .
git commit -m "Deskripsi perubahan"
git push
```

**EC2-1 dan EC2-2 —** pull dan deploy ulang:

```bash
cd /home/ubuntu/tugas-cloud
git pull origin main

# Jika pakai symlink: cukup git pull, perubahan otomatis ter-reflect
# Jika pakai copy: jalankan ulang perintah copy
sudo cp -r Server_1/* /var/www/html/   # di EC2-1
sudo cp -r Server_2/* /var/www/html/   # di EC2-2
sudo chown -R www-data:www-data /var/www/html/
```

Atau buat script deploy di masing-masing EC2 (`/home/ubuntu/deploy.sh`):

```bash
#!/bin/bash
cd /home/ubuntu/tugas-cloud
git pull origin main

HOSTNAME=$(hostname)
if [[ "$HOSTNAME" == *"server-1"* || "$HOSTNAME" == *"ec2-1"* ]]; then
  sudo cp -r Server_1/* /var/www/html/
elif [[ "$HOSTNAME" == *"server-2"* || "$HOSTNAME" == *"ec2-2"* ]]; then
  sudo cp -r Server_2/* /var/www/html/
fi
sudo chown -R www-data:www-data /var/www/html/
```

Jalankan: `bash /home/ubuntu/deploy.sh`

---

## 🧹 Clean Up (Agar Tidak Kena Tagihan)

Hapus semua resources setelah selesai:

```bash
# 1. Hapus Load Balancer
aws elbv2 delete-load-balancer --load-balancer-arn <arn-alb>

# 2. Hapus Target Group
aws elbv2 delete-target-group --target-group-arn <arn-tg>

# 3. Terminate EC2
aws ec2 terminate-instances --instance-ids <id-ec2-1> <id-ec2-2>

# 4. Hapus RDS
aws rds delete-db-instance --db-instance-identifier tugas-cloud-db --skip-final-snapshot
```

Atau hapus manual via **AWS Console**.

---

## 🐛 Troubleshooting

| Masalah | Kemungkinan Penyebab | Solusi |
|---------|----------------------|--------|
| Cannot connect ke RDS | Security Group RDS | Tambah inbound rule **MySQL (3306)** dari SG EC2 |
| 403 Forbidden | Permission file | `sudo chown -R www-data:www-data /var/www/html/` |
| 500 Internal Server Error | PHP error | Cek log: `sudo tail -f /var/log/apache2/error.log` |
| 502 Bad Gateway (ALB) | Health check gagal | Pastikan `/login.php` bisa diakses |
| Blank page | Ekstensi mysqli tidak aktif | `sudo apt install php-mysqli && sudo systemctl restart apache2` |
| Session hilang | Tiap server simpan session sendiri | Wajar untuk testing — user login ulang jika kena server beda. Untuk production, gunakan session shared (Redis/ElastiCache) |

---

## 📚 Referensi

- [AWS EC2 User Guide](https://docs.aws.amazon.com/ec2/)
- [AWS RDS User Guide](https://docs.aws.amazon.com/rds/)
- [AWS ALB Documentation](https://docs.aws.amazon.com/elasticloadbalancing/)
- [PHP MySQLi Documentation](https://www.php.net/manual/en/book.mysqli.php)
