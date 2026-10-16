# Panduan Deploy Produksi — Joysign

Runbook langkah demi langkah untuk men-deploy Joysign ke server produksi (Linux + nginx/aaPanel + PHP-FPM).

> ⚠️ **Pelajaran penting:** pernah terjadi kode sudah benar & ter-deploy, tetapi aplikasi masih menyajikan **state basi** (cache `config`/`route`/`view` + **opcache PHP-FPM** lama) sehingga tombol Livewire tidak bereaksi. Karena itu langkah **`optimize:clear` + `optimize` + restart PHP-FPM** di bawah **wajib**, bukan opsional.

## 1. Prasyarat Server

- PHP ≥ 8.3 (disarankan **8.5**) dengan ekstensi: `openssl`, `gd`/`imagick`, `pdo_mysql`, `mbstring`, `ctype`, `json`, `bcmath`, `tokenizer`, `xml`, `curl`, `fileinfo`.
- Composer 2, Node.js + npm (untuk build aset), MySQL/MariaDB.
- Web server (nginx) mengarah ke `<root>/public` sebagai document root.

## 2. Konfigurasi `.env` Produksi

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<domain>

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database   # lihat §6

# Wajib bila di belakang Cloudflare Tunnel / reverse proxy (lihat §5)
# (nilai di kode: trustProxies(at: '*') — cukup, tidak perlu env)

TURNSTILE_ENABLED=true
TURNSTILE_SITEKEY=...
TURNSTILE_SECRET=...

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_SCHEME=ssl
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=...
```

## 3. Deploy Pertama Kali

```bash
git clone https://github.com/farhaanrobbani/joysign.git && cd joysign

composer install --no-dev --optimize-autoloader
cp .env.example .env            # lalu isi sesuai §2
php artisan key:generate

npm ci && npm run build         # WAJIB: public/build tidak di-commit

php artisan migrate --force
php artisan storage:link        # bila memakai storage publik

php artisan optimize:clear
php artisan optimize            # cache config/route/view/events
```

Pastikan `storage/` dan `bootstrap/cache/` dapat ditulis oleh user web (mis. `www`).

## 4. Deploy Update (rutin)

```bash
git pull --ff-only origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build          # jalankan bila ada perubahan resources/ atau dependency frontend
php artisan migrate --force      # bila ada migrasi baru
php artisan optimize:clear
php artisan optimize
```

Lalu **restart PHP-FPM** (reset opcache):

```bash
sudo systemctl restart php-fpm-85   # sesuaikan nama service/versi
```

Checklist singkat setiap deploy:

- [ ] `git pull` berhasil (HEAD sesuai commit yang diinginkan)
- [ ] `composer install --no-dev -o` (bila `composer.lock` berubah)
- [ ] `npm ci && npm run build` (bila aset frontend berubah)
- [ ] `php artisan migrate --force` (bila ada migrasi)
- [ ] `php artisan optimize:clear && php artisan optimize`
- [ ] restart PHP-FPM
- [ ] smoke test (lihat §7)

## 5. Trusted Proxies (Cloudflare Tunnel / Reverse Proxy)

Aplikasi memanggil `$middleware->trustProxies(at: '*')` di `bootstrap/app.php` agar `X-Forwarded-*` dari Cloudflare/reverse proxy dipercaya. Efeknya:

- `$request->ip()` = **IP klien asli** (bukan IP mesin tunnel), sehingga audit log, throttle login, dan `remoteip` Turnstile benar.
- Deteksi skema HTTPS mengikuti `X-Forwarded-Proto`.

Verifikasi IP tercatat benar: buka satu sesi lewat domain publik, lalu cek tabel `sessions` (kolom `ip_address`) atau `audit_logs`.

> Jika origin tidak boleh diakses langsung (hanya via tunnel), `at: '*'` aman. Bila origin juga terekspos publik, pertimbangkan membatasi ke rentang IP Cloudflare.

## 6. Queue & Scheduler

Saat ini aplikasi **tidak memiliki Job/`ShouldQueue`**, sehingga web berjalan tanpa worker. Namun `QUEUE_CONNECTION=database` **tanpa worker** adalah jebakan sunyi: job apa pun yang ditambahkan kelak tidak akan dijalankan.

Pilih salah satu:

- **Set `QUEUE_CONNECTION=sync`** bila tidak butuh background job, **atau**
- Jalankan worker via Supervisor:

```bash
php artisan queue:work --sleep=3 --tries=3
# setelah deploy yang mengubah kode queue:
php artisan queue:restart
```

Tidak ada task terjadwal (`routes/console.php` kosong dari `schedule()`), jadi tidak perlu cron `schedule:run` kecuali ditambahkan nanti.

## 7. Smoke Test Setelah Deploy

```bash
curl -s -o /dev/null -w "%{http_code}\n" https://<domain>/login          # 200
curl -s -o /dev/null -w "%{http_code}\n" https://<domain>/up             # 200
# endpoint Livewire harus hidup (419 = CSRF, bukan 404):
curl -s -o /dev/null -w "%{http_code}\n" -X POST -H "Content-Type: application/json" \
  -H "X-Livewire: true" --data '{"components":[]}' https://<domain>/livewire-*/update
```

Lalu di browser: login → `/admin/users` → klik **+ Buat Pengguna** → form muncul (Livewire POST `livewire-*/update` terkirim di Network tab).

## 8. Rollback

```bash
git reset --hard <commit-sebelumnya>
composer install --no-dev --optimize-autoloader
npm ci && npm run build          # bila perlu
php artisan optimize:clear
php artisan optimize
sudo systemctl restart php-fpm-85
```

## 9. Checklist Keamanan Sebelum Go-Live

- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] `APP_KEY` tergenerate (jangan pernah commit `.env`)
- [ ] Ganti/rotasi akun & passphrase demo (`DatabaseSeeder`), atau jangan jalankan seeder di produksi
- [ ] Kredensial DB & `MAIL_PASSWORD` dari secret manager, bukan repo
- [ ] `TURNSTILE_ENABLED=true` dengan key produksi
- [ ] HTTPS aktif + HSTS (di nginx/Cloudflare)
- [ ] Backup DB terjadwal
- [ ] Log rotation (`storage/logs`)
