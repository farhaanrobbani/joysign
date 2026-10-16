<p align="center"><img src="public/images/joysign-logo.png" width="120" alt="Joysign Logo"></p>

<h1 align="center">Joysign — Tanda Tangan Elektronik</h1>

<p align="center">
Aplikasi web TTE (Tanda Tangan Elektronik) kriptografis: penandatanganan PDF multi-penanda, sertifikat digital per pengguna, dan portal verifikasi publik berbasis token/QR Code.
</p>

---

## Fitur Utama

- **Penandatanganan PDF kriptografis** — tanda tangan digital RSA-2048 di atas hash SHA-256 dokumen, deteksi manipulasi (TAMPERED), co-sign paralel multi-penandatangan dalam satu dokumen.
- **Posisi penugasan** — urutan "Penandatangan 1, 2, ..." mengikuti pilihan operator saat menugaskan; penandatanganan sendiri bebas giliran.
- **Sertifikat digital per pengguna** — pasangan kunci OpenSSL dienkripsi *at-rest* dengan AES-256-GCM + Argon2id; passphrase tidak pernah disimpan plain text.
- **QR + Token verifikasi** — setiap tanda tangan menghasilkan QR Code dan token 12 karakter Base32; footer tiap halaman PDF memuat `Token:` milik penanda pertama.
- **Portal verifikasi publik** (`/verify`) — tanpa login: ketik/scan token → status **VALID / TAMPERED / EXPIRED / REVOKED**, daftar penandatangan, dan tautan dokumen; rate-limited 10/menit.
- **Panel admin & operator** — manajemen pengguna (tanpa registrasi publik — akun hanya dibuat admin/operator), pengaturan situs/logo, dan audit trail lengkap (IP, User-Agent, waktu UTC).
- **Keamanan form** — Cloudflare Turnstile pada login & lupa-password (opsional, *fail-closed*), rate limit login bawaan.
- **Dark mode** & branding navy Joysign.

## Tech Stack

| Lapisan | Teknologi |
| :--- | :--- |
| Backend | Laravel 13 · PHP 8.5 |
| Frontend | Livewire 4 · Tailwind CSS 3 · Alpine.js · Vite |
| PDF & QR | TCPDF + FPDI · endroid/qr-code |
| Database | MySQL/MariaDB (dev/test: SQLite in-memory) |

## Persyaratan

- PHP ≥ 8.3 (dikembangkan dengan 8.5) + ekstensi `openssl`, `gd`/`imagick`, `pdo_mysql`
- Composer · Node.js + npm
- MySQL/MariaDB

## Quick Start

```bash
git clone https://github.com/farhaanrobbani/joysign.git
cd joysign
composer install
npm install && npm run build

cp .env.example .env
# sesuaikan DB_*, lalu:
php artisan key:generate
php artisan migrate --seed

php artisan serve --port=7051
```

Buka `http://localhost:7051`.

### Akun demo (dibuat seeder)

| Email | Role | Password |
| :--- | :--- | :--- |
| `admin@tte.test` | Admin | `password` |
| `signer@tte.test` | Penandatangan (sertifikat aktif) | `password` |

Passphrase sertifikat demo: **`password123`**.

> ⚠️ **Sebelum produksi**: ganti semua akun & passphrase demo (tercantum di `DatabaseSeeder`), nonaktifkan/membersihkan seeder, dan isi konfigurasi keamanan di bawah.

## Konfigurasi Opsional

### Cloudflare Turnstile (anti-bot di login & lupa-password)

Buat site gratis di [Cloudflare Dashboard → Turnstile](https://dash.cloudflare.com/?to=/:account/turnstile), lalu isi di `.env`:

```dotenv
TURNSTILE_ENABLED=true
TURNSTILE_SITEKEY=...
TURNSTILE_SECRET=...
```

Saat `enabled=true`, form wajib token; secret salah atau siteverify gagal → form diblokir (*fail-closed*). Default `false` untuk pengembangan.

### SMTP Gmail (lupa-password & verifikasi email)

Di Google Account → Security → 2-Step Verification → **App passwords**, lalu:

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=ssl
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_USERNAME=account@gmail.com
MAIL_PASSWORD=app-password-16-digit
MAIL_FROM_ADDRESS=account@gmail.com
MAIL_FROM_NAME=Joysign
```

Default `MAIL_MAILER=log` (email ditulis ke log, tidak terkirim).

## Pengujian

```bash
php artisan test          # 216 test
vendor/bin/pint           # code style (Laravel Pint)
```

## Dokumentasi Proyek

| Dokumen | Isi |
| :--- | :--- |
| [`PRD.md`](PRD.md) | Persyaratan produk & functional requirements |
| [`ARCHITECTURE.md`](ARCHITECTURE.md) | Arsitektur aplikasi & pola service |
| [`DATABASE.md`](DATABASE.md) | Skema tabel & relasi |
| [`DESIGN.md`](DESIGN.md) | Desain UI, token, footer PDF |
| [`BUSINESS-RULES.md`](BUSINESS-RULES.md) | Aturan bisnis (registrasi, signing, verifikasi, audit) |
| [`AGENTS.md`](AGENTS.md) | Standar coding & alur kerja AI agent |

## License

Proprietary — kecuali disebut lain, seluruh isi repositori ini bagian dari proyek Joysign.
