# Release Runbook

Dokumen ini adalah checklist release aplikasi SDM. Merge dan deployment adalah dua approval terpisah.

## Preflight wajib

Catat sebelum deployment:

- `ENVIRONMENT`
- `PREVIOUS_DEPLOY_COMMIT`
- `TARGET_DEPLOY_COMMIT`
- `WORKTREE_CLEAN=PASS/FAIL`
- `PHP_VERSION` (minimum sesuai composer)
- `DATABASE=PostgreSQL`
- `DATABASE_BACKUP=PASS/NOT VERIFIED`
- `DEPLOY_METHOD`
- `ROLLBACK_TARGET`

Jika production worktree dirty, hentikan deployment sampai drift dipahami.

## Required runtime configuration

Jangan menyimpan secret di repository.

Pastikan secara tersanitasi:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_KEY_SET=YES`
- `DB_CONNECTION=pgsql`
- `DB_TIMEZONE=Asia/Jakarta`
- `DB_CREDENTIALS_SET=YES`
- document root mengarah ke `public/`
- `storage/` dan `bootstrap/cache/` writable oleh runtime user
- session/database/cache settings sesuai environment

## Release preparation

Untuk target commit yang sudah disetujui:

1. install dependency dari `composer.lock` dengan mode production;
2. pastikan konfigurasi environment tersedia tanpa mencetak secret;
3. jalankan migration production hanya setelah backup/rollback path diverifikasi;
4. lakukan optimize/cache Laravel setelah configuration final;
5. reload/restart runtime hanya jika metode deployment memang memerlukannya.

Jangan melakukan `migrate:rollback` sebagai langkah rollback default.

## Migration policy

Migration saat ini bersifat additive: pembuatan tabel baru dan penambahan kolom/foreign key.

Preferred rollback untuk release ini adalah:

1. rollback code/release ke commit sebelumnya;
2. biarkan schema additive tetap ada jika aplikasi versi sebelumnya dapat mengabaikannya;
3. rollback migration hanya setelah blast radius dan data-loss risk diperiksa.

Production migration tetap berstatus `NOT VERIFIED` sampai benar-benar dijalankan pada environment target.

## Verification ladder setelah deployment

1. verify runtime menjalankan target commit/release;
2. verify process/socket sesuai metode deployment;
3. check `GET /up`;
4. smoke login;
5. smoke attendance kiosk;
6. smoke HR attendance/report access;
7. smoke Manager approval boundary;
8. smoke Finance payroll/report access;
9. smoke Employee own payslip access;
10. review bounded application/runtime logs.

`HTTP 200` pada `/up` tidak membuktikan business flow sehat; changed-flow smoke tetap diperlukan.

## Rollback trigger

Rollback release dipertimbangkan jika terjadi salah satu berikut:

- migration gagal dan release tidak dapat start;
- login/auth boundary rusak;
- attendance punch utama gagal;
- payroll/finalization menghasilkan behavior yang tidak sesuai;
- error rate/runtime failure setelah release;
- security boundary lintas role gagal.

## Rollback verification

Setelah code rollback:

- verify release identity;
- verify `/up`;
- smoke login;
- smoke attendance;
- smoke HR/Manager/Finance critical access;
- cek log terbatas untuk error baru.

## Current CI expectations

Pull request ke `main` harus melewati:

- Feature tests SQLite
- PostgreSQL migration verification
- Feature tests PostgreSQL

Production deployment bukan bagian dari CI dan membutuhkan approval terpisah.
