# Setup Meterai Elektronik (Peruri e-Meterai) — Tutorial Lengkap

Dokumen ini untuk menyalakan fitur pembubuhan meterai elektronik otomatis di
FR.AK.01 & FR.AK.14. Ada **tiga bagian** yang semuanya wajib beres, kalau salah
satu terlewat maka meterai tidak akan pernah muncul (biasanya nyangkut di
status "Sedang Diproses" selamanya):

1. **Container Sign Adapter** — program kecil dari Peruri yang jalan di server kita sendiri, tugasnya menempelkan meterai ke PDF secara lokal (dokumen tidak pernah diupload ke Peruri).
2. **File `.env`** — tempat menyimpan username/password/alamat API dari Peruri.
3. **Queue worker (Supervisor)** — proses yang jalan terus-menerus di background, tugasnya memproses "antrian" pembubuhan meterai. Tanpa ini, permintaan meterai akan menumpuk di database dan tidak pernah diproses sama sekali.

Semua langkah di bawah dikerjakan **di server produksi**, lewat SSH/PuTTY
(`PadmaForClient@103.93.129.21`), **bukan** di komputer lokal Anda.

---

## Bagian 1 — Container Sign Adapter (Docker)

### 1.1 Cek Docker sudah terpasang

```bash
docker --version
```

Kalau muncul nomor versi (mis. `Docker version 24.0.x`), lanjut ke langkah
berikutnya. Kalau muncul "command not found", install dulu:

```bash
curl -fsSL https://get.docker.com | sudo sh
sudo systemctl enable --now docker
```

### 1.2 Buat folder bersama ("sharefolder")

Ini folder tempat Laravel menaruh PDF asli + gambar QR, lalu container Sign
Adapter membaca dari situ dan menulis hasil yang sudah dibubuhi meterai balik
ke situ juga. Laravel dan container "ngobrol" lewat folder ini.

```bash
sudo mkdir -p /var/www/peruri-sharefolder/{UNSIGNED,STAMP,SIGNED}
sudo mkdir -p /var/www/peruri-sharefolder/logs
```

Sesuaikan kepemilikan folder dengan user yang menjalankan PHP-FPM aplikasi
Laravel (biasanya `www-data` di Ubuntu/Debian — cek dulu kalau ragu dengan
`ps aux | grep php-fpm`):

```bash
sudo chown -R www-data:www-data /var/www/peruri-sharefolder
```

### 1.3 Ambil ("pull") image Sign Adapter dari Peruri

```bash
docker pull registry.perurica.co.id/e-meterai/signadapter:2.0
```

Kalau muncul error "unauthorized" / "access denied", berarti perlu login dulu
ke registry Peruri — kredensial login registry ini biasanya beda dari
username/password API, cek di dokumen "API On Premis" yang Anda terima. Kalau
ada, jalankan dulu:

```bash
docker login registry.perurica.co.id
```

### 1.4 Buat file konfigurasi container

```bash
mkdir -p ~/signadapter && cd ~/signadapter
nano docker-compose.yml
```

`nano` akan membuka editor teks kosong. Salin-tempel isi berikut, lalu simpan
dengan `Ctrl+O`, Enter, lalu keluar dengan `Ctrl+X`:

```yaml
services:
  signadapter:
    image: registry.perurica.co.id/e-meterai/signadapter:2.0
    container_name: signadapter
    restart: always
    ports:
      - "8080:7777"
    environment:
      ENV: STAGING
      TZ: Asia/Jakarta
    volumes:
      - /var/www/peruri-sharefolder/logs:/app/logs
      - /var/www/peruri-sharefolder:/app/sharefolder
```

> **Catatan `ENV: STAGING`**: mulai dari sini pakai `STAGING` dulu untuk uji
> coba (memakai URL staging di Bagian 2 juga). Baru ganti ke `PROD` setelah
> satu kali uji coba penuh berhasil — jangan langsung produksi dari awal.

### 1.5 Jalankan container

```bash
sudo docker compose up -d
sudo docker ps
```

Perintah kedua harus menampilkan baris dengan nama `signadapter` dan status
`Up`. Kalau tidak muncul sama sekali, cek error-nya dengan:

```bash
sudo docker logs signadapter
```

### 1.6 Tes container sudah benar-benar jalan

```bash
curl http://127.0.0.1:8080
```

Harus muncul balasan seperti ini (isinya JSON, versi angkanya boleh beda):

```json
{"message":"welcome to signadapter v2.0.9","documentationUrl":"/docs"}
```

Kalau ini sudah muncul, **Bagian 1 selesai** — container siap dipakai.

---

## Bagian 2 — Isi `.env` Laravel

Buka file `.env` aplikasi (**bukan** `.env.example`) di server:

```bash
cd /var/www/lsp-cbt.sistemedu.com
nano .env
```

Cari baris-baris `PERURI_...` yang sudah ada (isinya kosong), lalu isi persis
seperti ini — **ganti `<...>` dengan kredensial asli** dari dokumen "API On
Premis" yang sudah Anda terima:

```env
PERURI_USERNAME=<username dari Peruri>
PERURI_PASSWORD=<password dari Peruri>
PERURI_SIGN_ADAPTER_URL=http://127.0.0.1:8080
PERURI_SHAREFOLDER=/var/www/peruri-sharefolder
PERURI_FAKE=false

# Staging dulu (cocok dengan ENV: STAGING di docker-compose.yml Bagian 1.4)
PERURI_LOGIN_URL=https://backendservicestg.e-meterai.co.id/api/users/login
PERURI_GENERATE_SN_URL=https://stampv2stg.e-meterai.co.id/chanel/stampv2
PERURI_JENISDOC_URL=https://stampv2stg.e-meterai.co.id/jenisdoc

# Production — aktifkan (hapus tanda #) nanti setelah staging lolos uji,
# dan jangan lupa ganti ENV: STAGING -> PROD di docker-compose.yml juga.
# PERURI_LOGIN_URL=https://backendservice.e-meterai.co.id/api/users/login
# PERURI_GENERATE_SN_URL=https://stampv2.e-meterai.co.id/chanel/stampv2
# PERURI_JENISDOC_URL=https://stampv2.e-meterai.co.id/jenisdoc
```

Simpan (`Ctrl+O`, Enter, `Ctrl+X`), lalu terapkan perubahan:

```bash
php artisan config:clear
```

> **Soal `PERURI_NAMADOC`**: ada satu baris lagi di config (`PERURI_NAMADOC`,
> defaultnya `3`) yang menunjukkan kode "Jenis Dokumen" (mis. "Surat
> Pernyataan") ke Peruri. Sebelum dipakai sungguhan, cek dulu kodenya benar
> lewat endpoint `PERURI_JENISDOC_URL` di atas (butuh login dulu — kalau mau,
> saya bisa buatkan skrip kecil untuk cek ini).

---

## Bagian 3 — Queue Worker (Supervisor)

Ini bagian yang **paling sering terlewat**. Aplikasi memasukkan tugas
"bubuhkan meterai" ke dalam antrian (tabel `jobs` di database) — supaya proses
ini tidak bikin peserta/asesor menunggu lama saat menandatangani. Tapi antrian
itu **tidak akan pernah diproses** kalau tidak ada program yang terus-menerus
mengeceknya. Itulah tugas *queue worker*.

### 3.1 Install Supervisor (kalau belum ada)

```bash
sudo apt-get update
sudo apt-get install -y supervisor
```

### 3.2 Buat file konfigurasi worker

```bash
sudo nano /etc/supervisor/conf.d/lsp-cbt-worker.conf
```

Salin-tempel isi berikut (sesuaikan path `/var/www/lsp-cbt.sistemedu.com`
kalau lokasi project Anda berbeda):

```ini
[program:lsp-cbt-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/lsp-cbt.sistemedu.com/artisan queue:work --sleep=3 --tries=3 --max-time=3600
directory=/var/www/lsp-cbt.sistemedu.com
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/lsp-cbt.sistemedu.com/storage/logs/worker.log
stopwaitsecs=3600
```

Penjelasan singkat: `autostart` & `autorestart` berarti worker ini otomatis
menyala saat server reboot, dan otomatis dihidupkan lagi kalau crash — tidak
perlu dinyalakan manual tiap saat.

### 3.3 Nyalakan worker-nya

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start lsp-cbt-worker:*
```

### 3.4 Pastikan worker benar-benar jalan

```bash
sudo supervisorctl status
```

Harus muncul baris `lsp-cbt-worker:lsp-cbt-worker_00` dengan status
`RUNNING`. Kalau statusnya `FATAL` atau `STOPPED`, cek isi
`storage/logs/worker.log` untuk lihat error-nya.

---

## Bagian 4 — Uji satu siklus penuh

**Jangan diulang-ulang** — kuota Serial Number di staging Peruri terbatas.

1. Pakai satu akun peserta uji coba (dummy), lengkapi sampai tanda tangan
   ketiga pihak selesai: Asesi menandatangani Pakta Integritas, Admin
   menyetujui permohonan (approve), dan Asesor menandatangani AK.01 (lewat
   fitur "TTD AK.01" — admin atau asesor sendiri).
2. Begitu tanda tangan ketiga (siapapun urutannya yang terakhir) selesai,
   sistem otomatis memasukkan tugas ke antrian. Karena worker di Bagian 3
   sudah jalan terus-menerus, seharusnya diproses dalam beberapa detik —
   tidak perlu menjalankan apa pun secara manual.
3. Cek hasilnya:
   - Buka halaman **Status Materai Elektronik** peserta tsb — harus berubah
     jadi "Materai Sudah Dibubuhkan".
   - Atau cek langsung ke database: kolom `materai_status` pada baris
     permohonan itu harus `stamped`.
   - Buka file hasilnya (`materai_document_path`) — harus ada QR code meterai
     tertempel di posisi TTD Asesi.
4. Kalau setelah semenit lebih statusnya masih `pending_payment` (bukan
   `stamped` atau `failed`), berarti worker-nya **tidak benar-benar jalan** —
   ulangi Bagian 3.
5. Kalau statusnya `failed`, buka kolom `materai_failure_reason` di baris
   yang sama — pesan errornya biasanya jelas (mis. kredensial salah, atau
   container Sign Adapter tidak terjangkau).

Setelah staging lolos semua langkah di atas, baru pindah ke mode produksi:
ganti `ENV: STAGING` → `PROD` di `docker-compose.yml` (lalu
`sudo docker compose up -d` lagi untuk menerapkannya), dan aktifkan baris
`PERURI_LOGIN_URL`/`GENERATE_SN_URL`/`JENISDOC_URL` versi production di
`.env` (lalu `php artisan config:clear`).

---

## Bagian 5 — Perawatan & pemecahan masalah

- **Update container**: `docker pull registry.perurica.co.id/e-meterai/signadapter:2.0` lalu `sudo docker compose up -d` lagi (container lama otomatis diganti versi baru).
- **Worker mati/perlu di-restart manual**: `sudo supervisorctl restart lsp-cbt-worker:*`
- **Cek log kalau ada kegagalan pembubuhan meterai**:
  - `sudo docker logs signadapter` (log dari sisi container Peruri)
  - `storage/logs/laravel.log` dan `storage/logs/worker.log` (log dari sisi Laravel)
  - Tabel `failed_jobs` di database — exception `PeruriStampingException` tercatat di sini juga.
- **Kalau `failed_jobs` menumpuk untuk kejadian yang sama**: cari tahu dulu penyebabnya (baca `materai_failure_reason` atau log) sebelum coba ulang massal. Retry satu-satu: `php artisan queue:retry <id>`. Jangan `php artisan queue:retry all` sembarangan — kalau penyebabnya memang salah (kredensial, container mati, dll), retry massal cuma akan gagal lagi semua dan boros kuota Serial Number.
