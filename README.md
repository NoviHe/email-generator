# 📧 Disposable / Temporary Email Generator + Super Admin Control Center

Aplikasi web temporary/disposable email service modern berbasis **Laravel 10** + **Inertia.js** + **Vue 3** + **Tailwind CSS** dalam satu kesatuan folder project (monorepo). Dirancang khusus untuk menerima email verifikasi, OTP media sosial (Facebook, Instagram, Google, dll.), cepat, stabil, dan fully compatible dengan provider email dari **cPanel Shared Hosting**.

---

## 🚀 Fitur Utama

- 🛡️ **Super Admin Control Center (`/admin`)**:
  - Monitoring total pengguna, total email temporary, status aktif vs expired, dan volume email masuk.
  - Manajemen akun pengguna: Lihat detail, promote ke Super Admin, demote, atau hapus pengguna.
  - Pantau semua alamat email sementara yang dibuat oleh seluruh pengguna secara real-time.
  - Log inspeksi email masuk global (baca isi email, subjek, pengirim, dan penerima).
  - Tombol manual **Sync IMAP Now** dan **Cleanup Expired**.
- ⚡ **Instant Email Generator**: Generate alamat email acak unik tanpa batas (*unlimited*) hanya dengan 1 klik.
- 📬 **Real-time Inbox View**: Tampilan inbox responsif dengan **auto-refresh polling** setiap 10 detik dan tombol refresh instan.
- 🔑 **Smart Verification Code / OTP Detector**: Otomatis mendeteksi 4–8 digit kode OTP dari email (Facebook, Instagram, Google, TikTok, dll.) dan menampilkan banner tombol **Copy Code** dengan sekali klik.
- 🖼️ **Dual View (HTML & Plain Text)**: Menampilkan email HTML dalam sandboxed iframe yang aman serta opsi Plain Text.
- 👥 **Multi-User Authentication**: Sistem registrasi, login, dan profile bawaan Laravel Breeze + Inertia Vue 3.
- ⏱️ **Auto Expiry & Cleanup**: Email sementara memiliki masa aktif dan otomatis dinonaktifkan.
- 🧹 **Auto-delete dari cPanel Mailbox**: Opsi `IMAP_DELETE_AFTER_FETCH=true` menjaga kuota hosting cPanel selalu 0 MB (bebas penuh).
- 🧪 **Simulate Incoming Email Tool**: Command bawaan untuk melakukan simulasi email verifikasi Facebook/Instagram/Google secara lokal.

---

## 🔑 Akun Bawaan (Default Credentials)

Database MySQL lokal telah di-seed dengan akun awal:

| Role | Email | Password | URL Akses |
|------|-------|----------|-----------|
| **Super Admin** | `admin@admin.com` | `password` | `/admin` atau klik menu **🛡️ Super Admin** di navbar |
| **Demo User** | `user@example.com` | `password` | `/dashboard` |

> Ingin menjadikan user lain sebagai Super Admin? Jalankan command:
> ```bash
> php artisan admin:promote email_user_anda@example.com
> ```

---

## 🛠️ Tech Stack & Konfigurasi Database

- **Backend**: Laravel 10 (PHP 8.1+)
- **Frontend**: Inertia.js + Vue 3 (Composition API) + Tailwind CSS
- **Database**: MySQL (`127.0.0.1:3306`, Database: `email-generator`, User: `root`, Password: `""`)
- **Protocol Email**: PHP IMAP Extension dengan SSL/TLS novalidate cert & catch-all header inspection

---

## ⚙️ Cara Setup di cPanel Shared Hosting

> **Status Saat Ini:** Server cPanel Anda (`mail.movcuan.biz.id`, port 993 SSL) **sudah 100% terhubung dan berhasil ditest**.

### Langkah 1: Buat Akun Email Catch-All di cPanel
1. Masuk ke **cPanel** hosting Anda.
2. Buka menu **Email Accounts** -> buat akun email penampung: `atmint@movcuan.biz.id`.
3. Simpan password-nya.

### Langkah 2: Aktifkan Fitur Catch-All (Default Address)
1. Di cPanel, cari dan buka menu **Default Address** (Catch-all).
2. Pilih domain Anda (`movcuan.biz.id`).
3. Pilih opsi **"Forward to Email Address"**.
4. Masukkan alamat email penampung: `atmint@movcuan.biz.id`.
5. Klik **Change**.

### Langkah 3: Konfigurasi File `.env` di Laravel
File `.env` sudah terisi dan siap digunakan:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=email-generator
DB_USERNAME=root
DB_PASSWORD=

# Konfigurasi IMAP cPanel
IMAP_HOST=mail.movcuan.biz.id
IMAP_PORT=993
IMAP_ENCRYPTION=ssl
IMAP_USERNAME=atmint@movcuan.biz.id
IMAP_PASSWORD="your_email_password"
IMAP_DOMAIN=movcuan.biz.id

# Pengaturan Generator
EMAIL_EXPIRE_MINUTES=60
EMAIL_MAX_PER_USER=0           # 0 = Unlimited
IMAP_DELETE_AFTER_FETCH=true    # Menghapus dari cPanel agar kuota hosting tidak pernah penuh
```

---

## 💻 Menjalankan Aplikasi di Komputer Anda

### 1. Jalankan Web Server Laravel
```bash
php artisan serve
```
Buka browser di: **`http://127.0.0.1:8000`**

### 2. Login Sebagai Super Admin
1. Masuk ke menu **Log in**.
2. Masukkan email: `admin@admin.com` | password: `password`.
3. Klik tombol **🛡️ Super Admin** di navbar atas untuk masuk ke pusat kendali.

### 3. Testing Test Koneksi & Simulasi Email
```bash
# Test koneksi ke server cPanel:
php artisan emails:test-connection

# Simulasi email masuk (FB / IG / Google):
php artisan emails:simulate
```
