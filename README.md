# cobalance

## Logging

CodeIgniter logging is disabled by default (`$config['log_threshold'] = 0`) to
prevent application log files from growing continuously on the server. PHP or
web-server logs configured outside CodeIgniter are not affected. Existing log
files are not removed automatically; archive or remove them separately when safe.
To temporarily diagnose a production issue, set the threshold to `1` for errors
only, then disable it again after investigation.

## Panduan pengguna

Lihat [Panduan Pengguna Co-Balance](docs/PANDUAN_PENGGUNA.md) untuk alur dari
login, pengaturan data awal, pencatatan transaksi, hingga membaca laporan.

Input nominal uang menggunakan format Indonesia, misalnya `1.000.000,50`
(titik untuk ribuan dan koma untuk desimal, maksimal dua angka desimal).
Jumlah barang, umur manfaat, dan persentase pajak tidak memakai format ini.

## Tampilan

Layout perusahaan menggunakan navigasi samping yang menyesuaikan menu setiap role.
Pada layar besar, menu otomatis ditutup dengan animasi pada semua halaman perusahaan
agar area kerja lebih luas. Gunakan tombol menu
di bagian atas untuk menyembunyikan atau menampilkan navigasi kembali. Pada layar
kecil menu ditampilkan sebagai panel.
Halaman login dan system admin
mengikuti palet tampilan yang sama.

Layout ini mengadaptasi pola AdminLTE untuk Co-Balance, termasuk transisi navigasi
samping, melalui
`assets/css/cobalance-layout.css`, bukan memuat CSS/JavaScript AdminLTE 4 secara
global. Komponen aplikasi saat ini masih menggunakan Bootstrap 3, sehingga
memuat Bootstrap 5 yang terkandung dalam AdminLTE 4 secara langsung dapat
mengubah perilaku modal, dropdown, tabel dan komponen lain.

## Gambar produk

Setelah mencadangkan database, jalankan `application/sql/20261113_gambar_produk.sql`
satu kali. Master Produk mendukung gambar opsional JPG, PNG, atau WebP maksimal
2 MB dan 4096 x 4096 piksel. Gambar disimpan dalam BLOB tabel `produk` dan
diakses melalui endpoint yang dibatasi perusahaan; gambar dapat diganti atau
dihapus saat mengedit produk. Pastikan konfigurasi PHP `upload_max_filesize`
dan `post_max_size` serta `max_allowed_packet` database cukup untuk unggahan.

## Keamanan login

Sebelum memperbarui aplikasi, cadangkan database dan jalankan
`application/sql/20261112_login_security.sql` satu kali. Login memerlukan tabel
ini untuk membatasi percobaan; tanpa migrasi, login tidak dapat digunakan. Pastikan
kolom `pengguna.password` bertipe `VARCHAR(255)` agar hash baru tidak terpotong.

Password baru menggunakan `password_hash()` dan diverifikasi dengan
`password_verify()`. Akun dengan hash lama `md5(md5(password))` tetap dapat
login dan otomatis diperbarui setelah password benar. Password maksimal 72 byte;
akun lama dengan password lebih panjang perlu direset oleh administrator.
Login dibatasi menjadi 10 percobaan per alamat IP dalam jendela 15 menit,
termasuk login yang berhasil. Pengguna dalam satu jaringan dapat berbagi batas
ini; pastikan konfigurasi proxy CodeIgniter benar agar alamat IP yang digunakan
sesuai. Catatan IP lama dibersihkan secara berkala saat ada login.

Gunakan HTTPS di server dan aktifkan `$config['cookie_secure'] = TRUE` setelah
HTTPS tersedia. Perlindungan token formulir diterapkan pada login; pengaturan
CSRF global masih perlu disiapkan terpisah untuk seluruh modul.

## System admin dan perusahaan

System admin masuk melalui halaman login biasa dengan username `system`. Akunnya
disimpan di tabel `pengguna` dengan `id_perusahaan` NULL dan role `admin`, tanpa
akses ke modul perusahaan. Cadangkan database dan jalankan
`application/sql/20261112_system_admin_database.sql` untuk mengizinkan nilai NULL.
Jika kolom sudah menerima NULL, migrasi tersebut tidak diperlukan.

Buat akun system secara manual dengan hash yang dihasilkan oleh PHP
`password_hash($password, PASSWORD_DEFAULT)`:

```sql
INSERT INTO pengguna (username, password, nama_lengkap, id_perusahaan, role)
VALUES ('system', 'HASH_PASSWORD', 'System Admin', NULL, 'admin');
```

Ganti `HASH_PASSWORD` dengan hash yang sebenarnya. Username harus unik; jika
`system` sudah digunakan oleh akun perusahaan, ganti username akun tersebut
terlebih dahulu. Jangan memasukkan password atau hash aktual ke repository.
Sesi system admin dari versi sebelumnya harus login ulang. Gunakan HTTPS.

Cadangkan database, lalu jalankan `application/sql/20261112_satu_admin_perusahaan.sql`
setelah migrasi login. Migrasi menegakkan paling banyak satu pengguna ber-role
`admin` per perusahaan; pengguna ber-role lain tidak dibatasi. Jika suatu
perusahaan sudah memiliki lebih dari satu admin, selesaikan duplikatnya dahulu
karena migrasi akan gagal. Perusahaan lama tanpa admin tidak diubah otomatis.

Untuk pilihan role tambahan di Master Pengguna, cadangkan database lalu jalankan
`application/sql/20261112_role_pengguna.sql` pada setiap database setelah migrasi
admin perusahaan. Role lama `admin` dan `user` tetap dipertahankan. Role ini
belum membatasi akses, kecuali role `gudang` dan `purchasing` (lihat di bawah): role lain yang
login masih dapat membuka modul perusahaan, termasuk Master Pengguna.

Modul Gudang (`controllers|models|views/gudang`) menyediakan beranda, stok barang
tanpa nilai rupiah, kartu stok, dan usulan stock opname yang tidak mengubah stok
atau jurnal. Jalankan `application/sql/20261113_gudang_usulan_opname.sql`. Admin (juga role akuntansi/keuangan) menindaklanjuti usulan di Transaksi → Usulan Opname Gudang: tombol Buat penyesuaian membuka form Penyesuaian Persediaan yang terisi, lalu usulan ditandai Selesai atau Tolak.
Pengguna role `gudang` diarahkan ke `gudang/beranda` dan setiap upaya membuka
modul perusahaan lain dialihkan kembali ke sana; modul gudang hanya dapat dibuka
role `gudang` dan `admin`.

Koreksi saldo awal yang sudah diposting hanya tersedia untuk Admin Perusahaan.
Sebelum fitur ini digunakan, cadangkan database lalu jalankan
`application/sql/20261113_koreksi_saldo_awal.sql` satu kali. Koreksi menulis
riwayat nilai sebelum/sesudah dan memperbarui jurnal saldo awal yang asli.
Pengguna harus meninjau pratinjau dampak serta mengonfirmasi secara manual bahwa
periode terkait terbuka. Jurnal HPP historis tidak dihitung ulang, sedangkan
jadwal dan jurnal penyusutan dihitung ulang. Aset yang sudah dilepas tidak dapat
dikoreksi melalui alur ini. Periksa kembali laporan dan buku pembantu setelah
koreksi.

Modul Purchasing (`controllers|models|views/purchasing`) menyediakan beranda,
daftar produk baca-saja (tanpa harga jual), serta melihat, menambah, mengedit,
dan mengubah status pemasok melalui Master Pemasok. Penghapusan permanen tetap
dilakukan oleh pengguna perusahaan lain yang berwenang. Pengguna role `purchasing`
juga memakai modul Pembelian dan Pembayaran Utang yang sudah ada. Pembatasan
role ditetapkan di `MY_Controller::$role_scope`: role `purchasing` hanya dapat
membuka controller `pembelian`, `pembayaran_utang`, dan `pemasok`, selebihnya dialihkan ke
`purchasing/beranda`. Tidak ada migrasi baru.

System admin dapat membuat perusahaan beserta satu admin dan menyalin seluruh
master akun perusahaan ID 1, termasuk hubungan induknya, dalam satu transaksi.
Perusahaan ID 1 harus tersedia dan mempunyai hierarki akun yang valid. Salinan
tidak mencakup pengaturan akun, pelanggan, produk, atau data transaksi; admin
perusahaan perlu menyiapkan pengaturan akun dan data master lainnya sebelum
menggunakan modul transaksi. System admin tidak dapat mengakses modul perusahaan:
mencoba membuka modul tersebut akan mengakhiri sesi system admin.

## Fondasi transaksi

`Transaksi_service` adalah layanan internal untuk modul transaksi, bukan endpoint
HTTP. Layanan membutuhkan tabel transaksi dari skema yang telah disiapkan terpisah
dan koneksi MySQL/MariaDB dengan tabel InnoDB. Nomor draft berbentuk
`TRX-0000000001`, unik berdasarkan ID transaksi dan tidak berubah saat posting.

Modul memanggil `buat_draft($jenis, $tanggal, $deskripsi, $simpan_detail)`.
Callback menerima ID transaksi, ID perusahaan, dan koneksi database untuk
menyimpan data draft. `posting($id, $siapkan_jurnal)` mengunci draft dan
memanggil callback dengan transaksi dan koneksi database yang sama. Callback
harus memvalidasi data bisnis, menyimpan perubahan terkait, dan mengembalikan
baris jurnal berbentuk `array('id_akun' => ..., 'debit' => '0.00',
'kredit' => '0.00')`. Nominal wajib berupa string desimal atau integer, bukan
float. Layanan memeriksa kepemilikan akun, akun tanpa anak, dan keseimbangan
debit-kredit sebelum menyimpan jurnal dan mengubah status.

`batalkan($id, $tanggal, $alasan, $batalkan_dampak)` membuat jurnal pembalik
dengan menukar debit dan kredit jurnal posting. Callback pembatalan wajib
memeriksa dokumen terkait dan membalikkan seluruh dampak bisnis (termasuk
alokasi dan mutasi stok) atau melempar exception jika pembatalan tidak aman.
Jangan menyediakan callback kosong untuk transaksi yang mempunyai dampak
tersebut. Semua callback harus memakai koneksi yang diberikan dan tidak boleh
melakukan commit, rollback, atau operasi di luar transaksi database.

Layanan memulai dan menyelesaikan transaksi database sendiri; jangan memanggilnya
dari transaksi database yang sudah aktif. Exception dari callback atau kegagalan
database membatalkan seluruh perubahan. Modul pemanggil wajib menampilkan pesan
kesalahan yang sesuai dan tidak melaporkan keberhasilan. Layanan dasar ini
tidak membuat halaman transaksi atau menghitung pajak dan stok; setiap modul
bertanggung jawab atas handler posting dan pembatalannya sendiri. `atomic()`
mengunci perusahaan selama transaksi, sehingga modul transaksi lain harus
menggunakan layanan ini untuk menjaga konsistensi pembukaan dan jurnal.

Jurnal hasil posting atau pembalik dilindungi dari edit dan hapus langsung.
CRUD jurnal lama tetap tersedia sementara modul transaksi belum selesai.

## Penjualan

Modul Penjualan menyimpan transaksi sebagai draft sebelum posting. Penjualan tunai
mendebit akun kas/bank yang dipilih; penjualan kredit mendebit akun piutang usaha
dan memerlukan tanggal jatuh tempo. Setiap rincian menyimpan harga, potongan
nominal per rincian, serta mode pajak: tidak dikenakan, termasuk pajak, atau belum
termasuk pajak. Akun pendapatan, pajak keluaran, persediaan, dan beban pokok
mengikuti akun pada produk atau pengaturan akun perusahaan.

Posting membuat jurnal otomatis. Penjualan barang memeriksa saldo stok dan
mencatat mutasi keluar dengan harga pokok rata-rata; jasa tidak mengubah stok.
Pembatalan membuat jurnal pembalik dan mengembalikan stok hanya jika penjualan
belum dialokasikan ke pembayaran dan belum ada mutasi stok sesudah penjualan
untuk produk yang sama. Draft penjualan yang belum diposting dapat diubah.

## Penerimaan piutang

Penerimaan piutang dialokasikan ke tagihan pelanggan yang sudah diposting
(penjualan kredit atau saldo awal piutang) dan tidak boleh melebihi sisa tagihan.
Posting mendebit akun kas/bank (kelompok 1100) dan mengkredit piutang usaha.
Pembatalan membuat jurnal pembalik dan mengembalikan sisa piutang; penjualan atau
saldo awal yang sudah punya penerimaan terposting tidak dapat dibatalkan.

## Pembelian

Pembelian disimpan sebagai draft sebelum posting, dengan pemasok, pembayaran tunai
atau kredit (kredit wajib jatuh tempo), serta pajak masukan per rincian. Posting
membuat jurnal otomatis: barang mendebit akun persediaan dan menambah stok dengan
harga pokok rata-rata baru; jasa mendebit akun pembelian/beban pada master produk
(wajib diisi). Akun persediaan dan beban pokok tidak dapat dipilih untuk jasa;
akun lama pada jasa tetap tersimpan tetapi tidak digunakan. Jika barang yang belum
digunakan diubah menjadi jasa, kedua akun tersebut dihapus saat disimpan.
Pajak masukan mendebit akun pajak masukan; lawan jurnal adalah
kas/bank (tunai) atau utang usaha (kredit). Pembatalan membuat jurnal pembalik dan
mengembalikan stok hanya jika belum ada pembayaran utang terposting dan belum ada
mutasi stok sesudah pembelian untuk produk yang sama.

## Pembayaran utang

Pembayaran utang dialokasikan ke tagihan pemasok yang sudah diposting (pembelian
kredit atau saldo awal utang) dan tidak boleh melebihi sisa tagihan. Posting
mendebit utang usaha dan mengkredit akun kas/bank (kelompok 1100). Pembatalan
membuat jurnal pembalik dan mengembalikan sisa utang; pembelian atau saldo awal
yang sudah punya pembayaran terposting tidak dapat dibatalkan.

## Kas dan bank

Modul ini mencatat kas masuk, kas keluar, dan transfer antar akun kas/bank (kelompok
1100) sebagai draft sebelum posting. Kas masuk mendebit kas/bank dan mengkredit akun
sumber; kas keluar mengkredit kas/bank dan mendebit akun tujuan; transfer mendebit
akun tujuan dan mengkredit akun asal (harus berbeda). Akun piutang usaha dan utang
usaha ditolak sebagai akun lawan karena dikelola modul Penerimaan Piutang dan
Pembayaran Utang. Pembatalan membuat jurnal pembalik.

## Modal dan Prive

Setoran modal mendebit akun kas/bank yang dipilih dan mengkredit akun Modal pada
Pengaturan Akun. Prive mendebit akun ekuitas yang dipilih (gunakan akun Prive
khusus bila tersedia) dan mengkredit akun kas/bank. Akun kas/bank harus berada
di kelompok 1100, akun Modal di kelompok 3100, dan akun Prive harus akun ekuitas
rinci, bukan akun induk atau akun Modal Usaha. Draft dapat diubah; posting membuat
jurnal otomatis, dan pembatalan membuat jurnal pembalik. Transaksi menyimpan
lampiran bukti PDF/JPG/PNG hingga 5 MB.

## Penyesuaian Akuntansi

Penyesuaian akuntansi dicatat sebagai draft dengan dua baris atau lebih. Setiap baris
menggunakan akun rinci, berisi debit atau kredit (tidak keduanya), dan total debit
wajib sama dengan total kredit. Draft dapat diubah beserta lampirannya; posting
membuat jurnal otomatis dan pembatalan membuat jurnal pembalik.

### Pemindahan Saldo Laba (Rugi) Berjalan

Jalankan `application/sql/20261114_pemindahan_laba.sql` satu kali setelah backup.
Perusahaan wajib memiliki akun 3103 Saldo Laba (Rugi) Berjalan dan 3102 Saldo Laba
(Rugi) Ditahan (Ekuitas, 3102 tanpa turunan). Tombol **Pindahkan Saldo Laba (Rugi)
Berjalan** (admin/akuntansi) membuka modal: pilih tahun sebelum tahun berjalan, lihat
transaksi beserta status Belum/Draft/Diposting, lalu buat satu draft jurnal bertanggal
1 Januari tahun berikutnya. Transaksi susulan memperbarui draft terbuka, atau membuat
draft selisih bila jurnal sebelumnya sudah diposting. Posting ditolak bila masih ada
transaksi yang belum tercakup. Jurnal pemindahan tidak memengaruhi Laba Rugi.

## Jurnal Transaksi

Menu Jurnal Transaksi bersifat baca-saja: jurnal hasil posting dapat dilihat dan
detail jurnal dapat dibuka tanpa mengunduh PDF, dan dokumen dapat diekspor. Daftar
menampilkan transaksi asal, total debit dan pelaku; detail dan PDF juga menampilkan
pihak, jenis jurnal, status, deskripsi, pembuat draft serta pengguna dan waktu posting dan
pembatalan. Jurnal pembalik mencantumkan pembatal sebagai pelaku, dan informasi
yang tidak tercatat tidak direka untuk jurnal lama. Nama mengikuti data pengguna saat ini; jurnal
tidak dapat dibuat, diubah, atau dihapus langsung. Gunakan modul
transaksi untuk mencatat aktivitas bisnis dan Penyesuaian Akuntansi untuk jurnal
koreksi manual agar perubahan tetap melalui alur posting dan pembatalan.

## Persediaan

Menu Persediaan menampilkan saldo dan riwayat mutasi barang. Penyesuaian
persediaan menyimpan satu produk per draft; stock opname membandingkan jumlah
fisik dengan saldo saat posting. Penambahan menggunakan harga pokok rata-rata
saat posting, atau nilai perolehan total yang diisi jika harga rata-rata tidak
tersedia. Penyesuaian hanya dapat dibatalkan bila tidak ada mutasi stok berikutnya.

Retur pembelian dan penjualan mencakup barang dan jasa, satu rincian transaksi
asal per draft. Pajak dan nilai retur dihitung secara proporsional dari rincian
asal; retur penjualan barang mengembalikan harga pokok historis, sementara
retur pembelian barang menggunakan harga pokok rata-rata saat posting. Pilih
akun pendapatan/beban untuk selisih antara harga pokok dan nilai faktur.
Retur tunai menggunakan akun kas/bank asal; retur kredit mengurangi sisa
tagihan dan ditolak bila nilainya melebihi sisa setelah pembayaran dan retur.
Pembatalan menghasilkan jurnal pembalik dan, untuk barang, hanya diperbolehkan
jika tidak ada mutasi stok berikutnya. Draft tidak dapat diedit; buat draft baru
jika salah. Keduanya mendukung lampiran PDF/JPG/PNG hingga 5 MB.

Retur membutuhkan tabel riwayat akun yang mencatat akun pada saat posting
pembelian/penjualan. Sebelum menggunakan versi kode ini, jalankan
`application/sql/20261109_riwayat_akun_transaksi.sql` satu kali pada database
perusahaan, setelah mencadangkan database. Tanpa tabel tersebut, posting
pembelian dan penjualan gagal dan seluruh perubahan transaksi dibatalkan.
Riwayat hanya terbentuk untuk transaksi yang diposting setelah tabel dan
kode ini tersedia; transaksi lama tanpa riwayat akun tidak dapat diretur melalui
modul ini karena akun historis per rincian tidak dapat dipastikan dari master.

## Aset tetap

Perolehan baru disimpan sebagai draft, mendukung pembayaran tunai atau kredit,
dan mencatat aset pada kelompok 1800. Aset lama dapat didaftarkan tanpa jurnal
tambahan jika sudah tercermin pada Saldo Awal; masukkan akumulasi penyusutan
sampai bulan Saldo Awal agar jadwal berikutnya dimulai pada bulan sesudahnya.
Penyusutan menggunakan metode garis lurus dan diakui satu bulan penuh mulai
bulan tanggal mulai penyusutan. Buat dan posting periode secara berurutan.

Pelepasan aset mencatat penyusutan satu bulan penuh pada bulan pelepasan (jika
belum diposting untuk bulan itu), lalu menghitung nilai buku dan laba/rugi.
Hasil pelepasan dapat diterima tunai, secara kredit, atau tanpa hasil. Transaksi
perolehan, penyusutan, dan pelepasan mendukung lampiran serta pembatalan dengan
jurnal pembalik; pembatalan ditolak jika akan merusak rangkaian penyusutan atau
tagihan sudah memiliki pembayaran/penerimaan. Tambahkan pengaturan akun Laba
Pelepasan Aset (kelompok 8000) di Pengaturan Akun untuk pelepasan yang menghasilkan
laba; pengaturan Rugi Pelepasan Aset harus mengarah ke kelompok 9000.

Sebelum menggunakan modul, tambahkan kolom akumulasi penyusutan awal:

```sql
ALTER TABLE aset_tetap
    ADD COLUMN akumulasi_penyusutan_awal DECIMAL(15,2) NOT NULL DEFAULT 0.00;
```

## Lampiran

Bukti transaksi (PDF/JPG/PNG, maks. 5 MB, satu file per transaksi) disimpan di
database pada kolom `transaksi.lampiran` (LONGBLOB) beserta `lampiran_nama` dan
`lampiran_tipe`. Lampiran hanya dapat diubah saat transaksi masih draft dan
diunduh melalui `lampiran/unduh/{id_transaksi}`. Pastikan `max_allowed_packet`
MariaDB cukup besar (misalnya 16M) dan query `ALTER TABLE` lampiran sudah dijalankan.

## Saldo awal

Menu Transaksi → Saldo Awal mencatat satu pembukaan per perusahaan dengan
tanggal yang sama untuk seluruh rincian. Masukkan saldo akun umum, faktur
piutang dan utang, jumlah dan total nilai per produk barang, serta aset tetap.
Nilai perolehan dan akumulasi penyusutan aset pada rincian aset otomatis
dijurnal; jangan memasukkan akun yang sama lagi pada saldo akun umum. Akumulasi
yang dimasukkan mencakup bulan Saldo Awal, sehingga penyusutan berikutnya
dimulai pada bulan sesudahnya. Akun di
kelompok piutang, utang usaha, dan persediaan tidak boleh dimasukkan lagi
sebagai saldo akun umum. Pilih akun ekuitas tanpa anak untuk mencatat selisih
debit dan kredit. Pengaturan akun piutang usaha, utang usaha, dan persediaan
harus sesuai dengan akun di perusahaan.

Simpan draft sebelum melakukan posting. Posting hanya dapat dilakukan jika
jurnal perusahaan masih kosong; jurnal pembukaan dibuat otomatis dan tidak
dapat diedit secara langsung. Faktur pembukaan merupakan rincian transaksi
induk dan dapat diidentifikasi berdasarkan nomor dan jatuh tempo; jurnal
dibuat untuk pembukaan sebagai satu kesatuan. Nilai stok awal menghasilkan
saldo dan mutasi persediaan dengan harga pokok rata-rata.

Pembatalan menghasilkan jurnal pembalik dan hanya diperbolehkan jika rincian
faktur belum dialokasikan, persediaan belum memiliki mutasi lanjutan, dan aset
belum memiliki penyusutan atau pelepasan.
Pembukaan yang dibatalkan tetap tersimpan sebagai riwayat; modul tidak
mengizinkan pembuatan pembukaan baru setelah pembatalan.
Lakukan pencadangan dan terapkan migrasi saldo awal yang disediakan terpisah
sebelum menggunakan modul ini. Skema dasar dan data database tidak disimpan di
repository; migrasi riwayat akun untuk pengembangan retur tersedia terpisah pada
`application/sql/`.