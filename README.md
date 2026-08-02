# DocFlow — Document Submission & Approval Management System

DocFlow adalah aplikasi pengelolaan permohonan dokumen secara digital dengan workflow approval yang
terstruktur. Pemohon dapat mengajukan permohonan, mengunggah dokumen pendukung, memantau status, dan
menerima hasil penilaian. Penilai dapat meninjau dokumen, memberikan catatan, meminta revisi,
menyetujui, atau menolak permohonan. Seluruh aktivitas tercatat dalam histori yang dapat ditelusuri.

## Tech Stack

| Layer      | Teknologi                                              |
| ---------- | ------------------------------------------------------ |
| Backend    | Laravel 12 (REST API)                                  |
| Frontend   | Vue 3 + Vue Router + Pinia                             |
| UI         | TailwindCSS + DaisyUI                                  |
| Database   | PostgreSQL (produksi), SQLite (testing)                |
| Auth       | Laravel Sanctum (token)                                |
| AuthZ      | Spatie Laravel Permission                              |
| Export     | PhpSpreadsheet (Excel), Dompdf (PDF)                   |
| API Docs   | Scramble (`/docs/api`)                                 |

## Fitur

- **Authentication** — register, login, logout, forgot/reset password, profil, ganti password
- **Dashboard** — ringkasan sesuai role (admin, reviewer, applicant) dengan statistik & grafik bulanan
- **User Management** — CRUD user, role, permission (khusus admin)
- **Project Management** — buat, ubah, hapus draft, submit permohonan
- **Document Management** — upload multi-file, download, preview, ganti, dan hapus dokumen
  (format: PDF, DOC, DOCX, XLSX; maks 10 MB per file)
- **Review** — mulai review, catatan/komentar, request revisi, approve, reject
- **Revision** — pemohon mengubah dokumen/project lalu submit kembali
- **History** — timeline approval/revisi dan activity log (dengan cursor pagination)
- **Notification** — notifikasi dalam aplikasi per pengguna
- **Export** — export Excel & PDF untuk project dan review

## Workflow Status

```text
Draft ──► Submitted ──► Under Review ──► Approved
   ▲           ▲             │
   │           │             ├──► Revision ──► (edit) ──► Submitted
   │           │             └──► Rejected
   └─── (edit/hapus draft) ───┘
```

## Requirements

- PHP >= 8.3 (dengan ekstensi `pdo_pgsql` / `pdo_sqlite`, `sqlite3`)
- Composer
- Node.js + npm
- PostgreSQL 14+ (untuk produksi)

## Instalasi

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed        # membuat tabel + seeder role/permission/admin
npm install
npm run build
```

Atau gunakan satu perintah:

```bash
composer run setup
```

## Konfigurasi

Salin `.env.example` ke `.env` lalu sesuaikan:

- `DB_*` — koneksi PostgreSQL
- `QUEUE_CONNECTION` — `database` (default) atau `redis` bila Redis tersedia
- `CACHE_STORE` — `database` (default) atau `redis`
- `SANCTUM_STATEFUL_DOMAINS` / `CORS_ALLOWED_ORIGINS` — domain frontend/backend saat development

### Seeder

```bash
php artisan db:seed
```

Seeder membuat role (`admin`, `reviewer`, `applicant`), seluruh permission, akun admin default, serta **1000 akun pemohon** (`pemohon001@docflow.test` … `pemohon1000@docflow.test`) dan **1000 akun penilai** (`penilai001@docflow.test` … `penilai1000@docflow.test`) dengan **10000 Project Permohonan** yang terhubung ke pemohon dan penilai (`php artisan db:seed` otomatis menjalankan `BulkDataSeeder`).

Bila hanya ingin data bulk (tanpa mengulang semua):

```bash
php artisan db:seed --class=BulkDataSeeder
```

Seluruh akun seeder menggunakan password `password` (kecuali diubah lewat `DatabaseSeeder`).

## Menjalankan (Development)

```bash
composer run dev
```

Perintah tersebut menjalankan secara bersamaan: server Laravel, queue worker, log, dan Vite.
Alternatif manual:

```bash
php artisan serve          # terminal 1
npm run dev                # terminal 2
php artisan queue:work     # terminal 3 (untuk antrian)
```

Frontend diakses melalui `http://localhost:8000` (dengan `APP_URL` sesuai konfigurasi).

### Cara Akses & Verifikasi Request API di Network Browser

Aplikasi diakses lewat `http://localhost:8000` — **bukan** `http://localhost:5173`
(5173 adalah Vite dev server, bukan alamat aplikasi).

- **Mode Production (single-origin):** pastikan `npm run build` sudah dijalankan dan file
  `public/hot` **tidak ada** (`Remove-Item public/hot` bila tersisa dari sesi dev). Dengan begitu
  halaman, aset, dan seluruh request API dilayani dari port 8000 yang sama, sehingga setiap
  response API (`/api/*`) tampil di tab **Network** DevTools.
- **Mode Development (hot reload):** jalankan `npm run dev` (membuat ulang `public/hot`). Halaman
  di 8000 memuat modul JS dari Vite, sedangkan request API tetap same-origin ke 8000 dan tetap
  tampil di Network.

Catatan: halaman login belum melakukan request API apa pun sampai Anda menekan tombol masuk —
request `POST /api/auth/login`, `/api/dashboard`, dst. baru muncul di Network setelah login/navigasi.

> Tips: set `DEBUGBAR_ENABLED=false` di `.env` bila ingin tampilan Network bersih (hanya request
> `/api/*`). Debugbar aktif menghasilkan banyak request ekstra (`/_debugbar/*`) yang bisa memenuhi
> buffer response DevTools sehingga body response API tidak tampil. Navigasi antar-menu pada SPA
> tidak me-reload halaman — buka DevTools **sebelum** mengklik menu agar request terekam.

## API Documentation

Dokumentasi API interaktif tersedia pada `/docs/api` (Scramble) setelah aplikasi berjalan.

## Testing

```bash
composer run test          # php artisan test
```

Suite berisi 117 test (unit + feature) mencakup auth, role & permission, user, project, dokumen,
review & alur revisi, dashboard, notifikasi, activity log, dan export. Testing memakai SQLite
in-memory agar cepat dan tidak membutuhkan PostgreSQL.

## Code Style

```bash
vendor/bin/pint              # PHP Code Style Fixer
npm run lint                 # ESLint (frontend, zero warning)
npm run format:check         # Prettier check
```

## Arsitektur

Proyek menerapkan Clean Architecture dengan lapisan:

- **Controller** — hanya menangani HTTP (`authorize` + delegasi ke Service + Resource)
- **FormRequest** — seluruh validasi input
- **DTO** — transfer data antar lapisan
- **Service** — business logic
- **Repository** — seluruh query database
- **API Resource** — struktur response
- **Enum** — seluruh status domain (`ProjectStatus`, `ReviewStatus`, `ActivityAction`, `ReviewAction`, `Role`, `Permission`)

Alur umum: `Route → Controller → FormRequest → Service → Repository → Model`, response melalui
`Resource`. Status project dan review tidak pernah ditulis sebagai string literal di dalam logic —
selalu melalui enum.
