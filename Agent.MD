# AI IMPLEMENTATION ROADMAP

> Dokumen ini berisi urutan implementasi project yang WAJIB diikuti oleh AI Agent.
>
> AI tidak diperbolehkan mengerjakan phase berikutnya apabila phase sebelumnya belum selesai.
>
> Setiap phase memiliki Definition of Done (DoD) yang wajib dipenuhi sebelum melanjutkan.

---

# Development Principles

Sebelum memulai implementasi, AI wajib mengikuti aturan berikut.

## General Rules

- Jangan mengubah requirement tanpa persetujuan.
- Jangan menambahkan fitur di luar PRD kecuali meningkatkan maintainability.
- Seluruh kode harus mengikuti Clean Architecture.
- Seluruh business logic harus berada di Service Layer.
- Controller hanya menerima Request dan mengembalikan Response.
- Repository hanya menangani query database.
- Gunakan API Resource untuk seluruh response API.
- Gunakan FormRequest untuk seluruh validasi.
- Gunakan Enum untuk seluruh status.
- Jangan menggunakan magic string.
- Hindari duplicate code.
- Gunakan reusable component.
- Seluruh form CRUD menggunakan modal, bukan halaman form terpisah.
- Seluruh fitur wajib responsive.
- Seluruh commit mengikuti Conventional Commit.

---

# Phase 1 - Project Initialization

## Objective

Menyiapkan pondasi project.

## Task

- Create Laravel Project
- Install Vue 3
- Install Vite
- Install TailwindCSS
- Install DaisyUI
- Install Laravel Sanctum
- Install Spatie Permission
- Install Laravel Pint
- Install Laravel Debugbar
- Install Laravel IDE Helper
- Configure PostgreSQL
- Configure Environment
- Configure CORS
- Configure Storage
- Configure ESLint
- Configure Prettier

## Output

Project dapat dijalankan tanpa error.

## Definition of Done

- Laravel berjalan.
- Vue berjalan.
- DaisyUI aktif.
- Database berhasil terkoneksi.
- Sanctum berhasil terinstall.
- Repository berhasil dibuat.

STOP.

Jangan lanjut sebelum seluruh checklist selesai.

---

# Phase 2 - Folder Architecture

## Objective

Membuat struktur project yang rapi.

## Task

Backend

app/

Actions

DTO

Enums

Events

Exceptions

Helpers

Http

Jobs

Models

Notifications

Policies

Repositories

Services

Traits

Observers

Frontend

src/

assets

components

common

modules

layouts

pages

router

stores

services

types

composables

utils

plugins

constants

## Output

Seluruh folder project telah tersedia.

## Definition of Done

Seluruh folder berhasil dibuat.

STOP.

---

# Phase 3 - Database Design

## Objective

Membangun struktur database.

## Task

Buat seluruh migration.

Gunakan UUID.

Gunakan SoftDelete.

Gunakan Foreign Key.

Gunakan Index.

Gunakan Constraint.

Gunakan Enum.

## Table

users

roles

permissions

projects

project_documents

reviews

review_logs

activity_logs

notifications

failed_jobs

cache

jobs

sessions

password_reset_tokens

## Seeder

Role

Permission

Admin

Reviewer

Applicant

## Factory

UserFactory

ProjectFactory

ReviewFactory

## Definition of Done

Migration berhasil.

Seeder berhasil.

Factory berhasil.

Tidak ada warning.

STOP.

---

# Phase 4 - Authentication

## Objective

Authentication selesai.

## Task

Register

Login

Logout

Profile

Change Password

Forgot Password

Remember Login

Sanctum Authentication

Middleware

Role Middleware

Permission Middleware

Policy

Gate

## Definition of Done

Semua endpoint authentication berhasil.

---

# Phase 5 - User Management

## Module

Admin

CRUD User

Assign Role

Assign Permission

Search

Pagination

Filter

Sort

Soft Delete

Restore

Definition of Done

Semua CRUD berjalan.

---

# Phase 6 - Dashboard

## Dashboard Admin

Total User

Total Project

Pending

Approved

Rejected

Revision

Monthly Chart

Recent Activity

## Dashboard Applicant

My Project

Pending

Approved

Rejected

Revision

Recent Activity

## Dashboard Reviewer

Waiting Review

Approved

Rejected

Revision

Chart

Definition of Done

Dashboard sesuai role.

---

# Phase 7 - Project Module

## CRUD Project

Create

Update

Delete Draft

Submit

Search

Filter

Pagination

Validation

Auto Number

UUID

Slug

Activity Log

Definition of Done

Project dapat dibuat.

---

# Phase 8 - Upload Document

Multiple Upload

Validation

Preview

Download

Delete

Replace

Allowed Extension

PDF

DOC

DOCX

XLSX

Definition of Done

Upload berhasil.

---

# Phase 9 - Review Module

Reviewer dapat

Review

Approve

Reject

Request Revision

Comment

History

Timeline

Definition of Done

Workflow berjalan.

---

# Phase 10 - Revision Module

Applicant dapat

Melihat Catatan

Mengubah Dokumen

Submit Ulang

Timeline

Definition of Done

Revision berhasil.

---

# Phase 11 - Activity Log

Semua aktivitas dicatat.

Create

Update

Delete

Upload

Approve

Reject

Revision

Login

Logout

Definition of Done

Timeline lengkap.

---

# Phase 12 - Notification

Database Notification

Queue

Toast Notification

Definition of Done

Notification berjalan.

---

# Phase 13 - Export

Excel

PDF

Definition of Done

Export berhasil.

---

# Phase 14 - API Documentation

- [x] Generate API Documentation.
- [x] Tambahkan contoh request.
- [x] Tambahkan contoh response.

Definition of Done

- [x] Seluruh endpoint terdokumentasi.

> STATUS: SELESAI — Scramble diaktifkan; route `/docs/api` (UI) & `/docs/api.json` melayani 42 path; SPA catch-all `/{any}` diperbaiki dengan regex `^(?!docs\/).*` agar tidak menelan `/docs/*`.

---

# Phase 15 - Performance Optimization

Tambahkan

- [x] Pagination
- [x] Cursor Pagination
- [x] Database Index
- [x] Eager Loading
- [x] Lazy Loading (prevention)
- [x] Cache
- [x] Redis
- [x] Queue
- [x] Chunk
- [x] Batch Insert
- [x] Observer

Definition of Done

- [x] Tidak ada N+1 Query.
- [x] Dashboard menggunakan cache.

> STATUS: SELESAI — Redis kini didukung via `predis` fallback saat ekstensi `phpredis` tidak tersedia; aktivasi cukup dengan `CACHE_STORE=redis` dan/atau `QUEUE_CONNECTION=redis` pada environment yang menyediakan service Redis. Cache database tetap dipertahankan sebagai default aman. Dashboard tetap memakai versioned cache di `DashboardCache`, di-invalidate via Observer (Project/Review/User/ActivityLog). Index kinerja, batch insert dokumen, export chunk streaming, cursor pagination activity logs, dan `preventLazyLoading` aktif.

---

# Phase 16 - Testing

Unit Test

Feature Test

Seeder Test

Authentication Test

CRUD Test

Approval Test

Definition of Done

Semua test lulus.

---

# Phase 17 - Docker

Dockerfile Backend

Dockerfile Frontend

Docker Compose

Nginx

PostgreSQL

Redis

Definition of Done

docker compose up berhasil.

---

# Phase 18 - Final Review

Checklist

- Semua fitur sesuai PRD.
- UI responsive.
- Tidak ada console error.
- Tidak ada PHP warning.
- Tidak ada N+1 Query.
- Tidak ada hardcoded value.
- Tidak ada duplicate code.
- Semua endpoint menggunakan API Resource.
- Semua validasi menggunakan FormRequest.
- Semua business logic berada di Service.
- Semua query database berada di Repository.
- Semua status menggunakan Enum.
- README selesai.
- Docker berhasil dijalankan.

---

# AI Rules

AI wajib mematuhi aturan berikut selama implementasi.

- Jangan mengubah struktur folder tanpa alasan yang jelas.
- Jangan menulis business logic di Controller.
- Jangan melakukan query langsung di Controller.
- Jangan menggunakan string literal untuk status.
- Jangan membuat komponen Vue lebih dari ±250 baris; pecah menjadi komponen yang lebih kecil bila diperlukan.
- Gunakan reusable component sebanyak mungkin.
- Selalu gunakan eager loading jika terdapat relasi.
- Tambahkan index pada kolom yang sering digunakan untuk pencarian atau filter.
- Gunakan transaction untuk proses yang mengubah banyak tabel.
- Selalu validasi file upload.
- Gunakan queue untuk proses berat seperti notifikasi atau upload bila diperlukan.
- Dokumentasikan setiap endpoint yang dibuat.
- Setelah menyelesaikan satu phase, lakukan review singkat sebelum berpindah ke phase berikutnya.

---

# Frontend Rules

# UI/UX Rules

Seluruh antarmuka aplikasi WAJIB menggunakan DaisyUI sebagai design system utama.

## Form Rules

Seluruh proses Create dan Update WAJIB menggunakan Modal.

Jangan membuat halaman Create atau Edit terpisah kecuali memang dibutuhkan oleh requirement bisnis.

Contoh:

✔ User

- Tambah User → Modal
- Edit User → Modal

✔ Project

- Tambah Project → Modal
- Edit Draft → Modal

✔ Role

- Tambah Role → Modal

✔ Permission

- Tambah Permission → Modal

Modal harus memiliki:

- Title
- Subtitle (opsional)
- Form
- Tombol Cancel
- Tombol Submit
- Loading State
- Validation Error
- Close Button

Ukuran modal mengikuti kompleksitas form:

- Small
- Medium
- Large
- Extra Large

Gunakan DaisyUI Modal sebagai standar.

---

## Exception

Halaman terpisah diperbolehkan apabila:

- Form memiliki lebih dari ±15 field.
- Form memiliki Wizard / Stepper.
- Form memiliki Upload Multiple File yang kompleks.
- Form membutuhkan Preview besar.
- Form memiliki Nested Resource.

Contoh:

✔ Project Detail

✔ Review Detail

✔ Approval Detail

✔ History Timeline

Karena halaman tersebut memiliki informasi yang kompleks.

---

## Delete Confirmation

Seluruh proses Delete WAJIB menggunakan Confirmation Modal.

Tidak diperbolehkan menggunakan browser confirm().

---

## Detail View

Detail data tidak menggunakan modal apabila data cukup kompleks.

Gunakan halaman detail.

Contoh:

/projects/{id}

/reviews/{id}

---

## Table Rules

Seluruh data ditampilkan menggunakan Data Table.

Data Table wajib memiliki:

- Search
- Filter
- Sort
- Pagination
- Action Column

Action menggunakan icon button.

---

## Action Button Rules

Primary Action

- Tambah
- Simpan
- Submit

Secondary Action

- Edit

Danger

- Delete

Success

- Approve

Warning

- Revision

Error

- Reject

Gunakan warna bawaan DaisyUI.

---

## Loading Rules

Gunakan Skeleton Loader.

Jangan menggunakan spinner untuk keseluruhan halaman.

Spinner hanya digunakan pada:

- Submit Button
- Upload File
- Export

---

## Empty State

Seluruh halaman wajib memiliki Empty State.

Empty State terdiri dari:

- Icon
- Title
- Description
- Action Button (jika diperlukan)

---

## Notification Rules

Gunakan Toast Notification.

Success

Error

Warning

Info

Jangan menggunakan browser alert().
---

# Final Definition of Done

Project dinyatakan selesai apabila:

- Seluruh requirement pada PRD telah diimplementasikan.
- Workflow pengajuan, review, revisi, approve, dan reject berjalan sesuai kebutuhan.
- Seluruh endpoint REST API berfungsi dengan baik.
- Frontend Vue 3 telah terhubung dengan backend Laravel.
- Seluruh halaman responsive menggunakan DaisyUI.
- Database telah teroptimasi dengan index dan relasi yang tepat.
- Tidak terdapat error kritis pada backend maupun frontend.
- Project dapat dijalankan menggunakan Docker.
- README telah lengkap.
- Kode mengikuti standar Clean Architecture dan siap untuk dipresentasikan.
