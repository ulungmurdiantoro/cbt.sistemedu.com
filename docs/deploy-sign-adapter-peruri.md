# Deploy Sign Adapter Peruri (On-Premise e-Meterai)

Container ini melakukan pembubuhan e-meterai secara lokal di VPS kita sendiri —
dokumen (FR.AK.01, FR.AK.14) tidak pernah diupload ke server Peruri. Laravel
hanya perlu bisa mencapai container ini lewat HTTP (`localhost`, tidak perlu
expose ke publik).

Dikerjakan di VPS (`PadmaForClient@103.93.129.21`), via SSH/PuTTY, **setelah**
kode aplikasi (PeruriService, jobs, dst) sudah dideploy dan `.env` sudah diisi.

## 1. Pastikan Docker terpasang

```bash
docker --version
```

Kalau belum ada, ikuti https://docs.docker.com/engine/install/ (pilih distro
sesuai OS VPS).

## 2. Buat folder bersama (sharefolder)

Ini folder yang dipakai bergantian oleh Laravel (menulis PDF asli + gambar QR)
dan container Sign Adapter (membaca lalu menulis hasil stempel). Pilih lokasi
di luar `public/` — mis. sejajar dengan folder project:

```bash
sudo mkdir -p /var/www/peruri-sharefolder/{UNSIGNED,STAMP,SIGNED}
sudo mkdir -p /var/www/peruri-sharefolder/logs
sudo chown -R www-data:www-data /var/www/peruri-sharefolder   # sesuaikan user PHP-FPM
```

Set path yang sama persis di `.env` Laravel:

```
PERURI_SHAREFOLDER=/var/www/peruri-sharefolder
```

## 3. Pull image

```bash
docker pull registry.perurica.co.id/e-meterai/signadapter:2.0
```

## 4. Buat `docker-compose.yml`

```bash
mkdir -p ~/signadapter && cd ~/signadapter
nano docker-compose.yml
```

Isi (ganti `ENV` jadi `STAGING` dulu untuk uji coba, baru `PROD` saat sudah
yakin siap produksi):

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

## 5. Jalankan

```bash
sudo docker compose up -d
sudo docker ps   # pastikan container "signadapter" berstatus Up
```

## 6. Verifikasi

```bash
curl http://127.0.0.1:8080
```

Harus muncul:
```json
{"message":"welcome to signadapter v2.0.9","documentationUrl":"/docs"}
```

## 7. Set `.env` Laravel

```
PERURI_SIGN_ADAPTER_URL=http://127.0.0.1:8080
PERURI_USERNAME=<username POS Peruri>
PERURI_PASSWORD=<password POS Peruri>
PERURI_FAKE=false

# Staging (uji coba dulu sebelum production):
PERURI_LOGIN_URL=https://backendservicestg.e-meterai.co.id/api/users/login
PERURI_GENERATE_SN_URL=https://stampv2stg.e-meterai.co.id/chanel/stampv2
PERURI_JENISDOC_URL=https://stampv2stg.e-meterai.co.id/jenisdoc

# Production (pindah ke ini + set ENV: PROD di docker-compose.yml, baru kalau staging sudah lolos uji):
# PERURI_LOGIN_URL=https://backendservice.e-meterai.co.id/api/users/login
# PERURI_GENERATE_SN_URL=https://stampv2.e-meterai.co.id/chanel/stampv2
# PERURI_JENISDOC_URL=https://stampv2.e-meterai.co.id/jenisdoc
```

Jalankan `php artisan config:clear` setelah edit `.env`.

## 8. Uji satu siklus penuh (manual, jangan berulang-ulang — kuota SN staging terbatas)

Dari peserta test (dummy), tandatangani FR.AK.01 atau FR.AK.14, lalu cek:
- `php artisan queue:work --once` (kalau worker belum jalan sebagai service) untuk memproses job stamping-nya.
- Kolom `materai_status` di DB berubah jadi `stamped`.
- Buka `materai_document_path` hasilnya — harus ada QR code e-meterai tertempel di posisi yang sudah dikalibrasi.
- Cek lewat `API Check Status Serial Number` (pakai Postman collection dari Peruri) — serial number yang dipakai harus berstatus `STAMP`.

## 9. Maintenance

- Update image: `docker pull ...` lagi lalu `docker compose up -d` (akan recreate container dengan image baru).
- Cek log kalau ada kegagalan stamping: `docker logs signadapter` dan `storage/logs/laravel.log` (exception dari `PeruriStampingException` tercatat di `failed_jobs` table + kolom `materai_failure_reason`).
- Kalau `failed_jobs` menumpuk untuk job yang sama, cek dulu penyebabnya sebelum retry massal — `php artisan queue:retry <id>` per job, jangan `queue:retry all` sembarangan.
