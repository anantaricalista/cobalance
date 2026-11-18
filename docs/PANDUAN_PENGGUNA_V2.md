# Panduan Pengguna Co-Balance — Versi 2

Panduan ini menjelaskan penggunaan aplikasi berdasarkan role dan kegiatan.
Nama menu mengikuti aplikasi; menu yang terlihat dapat berbeda menurut role.
Panduan ini menggambarkan fitur yang sudah tersedia, bukan fitur yang direncanakan.
Untuk pemasangan, skema database, dan migrasi, pengelola teknis perlu membaca
[README](../README.md). Panduan versi sebelumnya tetap tersedia
[di sini](PANDUAN_PENGGUNA.md).

## Daftar isi

- [Mulai menggunakan aplikasi](#1-mulai-menggunakan-aplikasi)
- [Hak akses dan alur kerja](#2-hak-akses-dan-alur-kerja)
- [Admin Perusahaan](#3-admin-perusahaan)
- [Akuntansi](#4-akuntansi)
- [Keuangan](#5-keuangan)
- [Purchasing](#6-purchasing)
- [Penjualan](#7-penjualan)
- [Kasir](#8-kasir)
- [Gudang](#9-gudang)
- [Manajer / Pemilik](#10-manajer--pemilik)
- [Petunjuk transaksi](#11-petunjuk-transaksi)
- [Jurnal dan laporan](#12-jurnal-dan-laporan)
- [Masalah umum dan batasan](#13-masalah-umum-dan-batasan)
- [Persiapan teknis](#14-persiapan-teknis)

## 1. Mulai menggunakan aplikasi

### Masuk dan keluar

1. Buka alamat Co-Balance di browser modern, lalu masukkan username dan password
   yang diberikan pengelola. Aplikasi tidak menyediakan pendaftaran mandiri.
2. Setelah masuk, pastikan pengguna dan perusahaan yang aktif sudah benar.
   Hubungi Admin Perusahaan jika role atau perusahaan tidak sesuai.
3. Gunakan tombol menu untuk membuka atau menyembunyikan navigasi. Pada layar
   kecil, navigasi ditampilkan sebagai panel.
4. Pilih **Keluar** setelah selesai, terutama pada perangkat bersama.

Jika gagal masuk, periksa username dan password. Terlalu banyak percobaan dari
alamat jaringan yang sama dapat membatasi login selama 15 menit. Jika masalah
berlanjut, hubungi pengelola; jangan membagikan password.

### Cara membaca dan mengisi

- **Tanggal:** pilih tanggal pada kontrol browser. Tanggal di tabel dapat
  ditampilkan seperti `11 Nov 2026`; laporan tertentu menampilkan nama bulan lengkap.
- **Nominal rupiah:** umumnya gunakan titik untuk ribuan dan koma untuk desimal.
  Contoh: `1.000` berarti seribu; `12,50` berarti dua belas rupiah lima puluh
  sen. Nominal yang ditempel memakai format Indonesia dan maksimal dua desimal.
- **Jumlah dan tarif:** periksa petunjuk di masing-masing formulir. Formulir
  transaksi umum memakai titik untuk desimal jumlah dan tarif; Gudang serta
  Opname & Penyesuaian memakai koma untuk jumlah, maksimal empat desimal.
  Pada POS, ikuti format yang diminta pada kolom jumlah.
- **Pilihan akun:** buka daftar dan cari kode atau nama. Pilih akun rincian
  yang tersedia; akun induk yang dinonaktifkan tidak dapat dipakai.
- **Pilihan pelanggan:** buka daftar, ketik kode atau nama pelanggan, lalu
  pilih hasil yang sesuai.
- **Lampiran transaksi:** modul yang mendukungnya menerima satu PDF, JPG, atau
  PNG maksimal 5 MB per transaksi. Lampiran diubah ketika masih draft dan
  dapat dibuka dari rincian transaksi sesuai hak akses.

## 2. Hak akses dan alur kerja

| Role | Tugas utama | Posting atau pembatalan |
|---|---|---|
| Admin Perusahaan | Pengguna, data master, transaksi, jurnal, dan laporan perusahaan | Dapat posting dan membatalkan; khusus Admin dapat mengoreksi saldo awal terposting |
| Akuntansi | Master terkait, transaksi, penyesuaian, jurnal, tutup buku, dan laporan | Dapat posting dan membatalkan; menyiapkan pemeriksaan periode |
| Keuangan | Kas & Bank, rekonsiliasi bank, Pembayaran Utang, Penerimaan Piutang | Menyimpan draft dan menyiapkan rekonsiliasi; Akuntansi/Admin melakukan posting |
| Purchasing | Pemasok, produk barang, Pembelian, Pembayaran Utang | Menyimpan draft; Akuntansi/Admin melakukan posting dan pembatalan |
| Penjualan | Pelanggan, katalog, Penjualan, Penerimaan Piutang | Menyimpan draft; Akuntansi/Admin melakukan posting dan pembatalan |
| Kasir | POS, pelanggan, katalog, struk, dan riwayat | POS menyelesaikan dan langsung posting; pembatalan oleh Akuntansi/Admin |
| Gudang | Stok, kartu stok, dan usulan opname | Usulan tidak membuat jurnal atau mengubah stok |
| Manajer / Pemilik | Ringkasan, penjualan, tagihan, stok, jurnal, tutup buku, rekonsiliasi, dan laporan | Baca-saja |

**Draft** adalah transaksi yang disimpan untuk ditinjau; belum mengubah laporan,
stok, atau tagihan. **Posting** membuat jurnal dan dampak bisnis sesuai jenis
transaksi. **Pembatalan** transaksi terposting membuat jurnal pembalik, bukan
menghapus riwayat. Pembatalan dapat ditolak jika sudah ada pembayaran, retur,
mutasi stok, penyusutan, atau transaksi lain yang bergantung padanya.

Alur umum: petugas membuat draft → Akuntansi/Admin memeriksa bukti dan posting →
periksa jurnal dan laporan. POS Kasir merupakan pengecualian karena pembayaran
yang selesai langsung diposting. Jika muncul pesan kesalahan, baca penyebabnya
dan perbaiki data sebelum mencoba lagi; jangan menganggap transaksi berhasil
hanya karena tombol ditekan.

## 3. Admin Perusahaan

**Masuk:** gunakan akun Admin untuk perusahaan yang dituju. Menu utama meliputi
**Master**, **Transaksi**, **Jurnal Transaksi**, dan **Laporan**.

### 3.1 Siapkan perusahaan

1. Buka **Master → Akun**; periksa kode, nama, tipe, dan hubungan akun induk.
   Gunakan akun rincian untuk transaksi yang mensyaratkannya.
2. Buka **Master → Pengaturan Akun**; hubungkan akun yang dibutuhkan, misalnya
   kas/bank, piutang, utang, persediaan, pajak, beban pokok, pendapatan, dan
   modal. Pilih akun yang sesuai dengan kegiatan perusahaan.
3. Lengkapi **Master → Pelanggan**, **Pemasok**, **Produk dan Jasa**, serta
   **Pajak**. Siapkan pelanggan umum jika POS melayani pembeli tanpa identitas.
4. Jika usaha sudah memiliki saldo sebelum memakai aplikasi, catat **Transaksi →
   Saldo Awal** sebelum transaksi rutin; lihat bagian 11.1.
5. Buat akun petugas di **Master → Pengguna** dan berikan role sesuai tugas.
   Sampaikan username dan password awal melalui saluran aman.

### 3.2 Mengelola pengguna

1. Buka **Master → Pengguna**, pilih **Tambah Pengguna**, isi nama, username,
   role, dan password, lalu simpan.
2. Untuk memperbarui pengguna, pilih ikon **Edit**. Password boleh dikosongkan
   jika tidak diganti. Username hanya menerima huruf, angka, titik, garis
   bawah, atau tanda hubung.
3. Gunakan ikon **Hapus** pada pengguna yang dapat dihapus. Admin Perusahaan
   tetap muncul di daftar; nama, username, dan password-nya dapat diubah, tetapi
   admin tidak dapat ditambah, dihapus, atau diganti rolenya di halaman ini.

Satu Admin dibuat saat perusahaan didaftarkan. Role lama **User** tidak tersedia;
jika masih ada akun lama tersebut, pilih role baru untuknya sebelum dapat login.
Lihat README untuk prasyarat migrasi dan penanganan perusahaan lama.

### 3.3 Memeriksa dan mem-posting

Buka transaksi terkait pada menu **Transaksi**, buka draft, periksa tanggal,
pihak, rincian, akun, pajak, total, dan lampiran, lalu pilih **Posting** pada
halaman tersebut. Gunakan **Jurnal Transaksi → Detail** untuk memeriksa
hasilnya. Untuk pembatalan, buka transaksi terposting, isi tanggal dan alasan,
lalu ikuti peringatan yang ditampilkan. Lihat bagian 11 untuk jenis transaksi.

## 4. Akuntansi

**Masuk:** gunakan akun ber-role **Akuntansi**. Menu **Master** tersedia untuk
akun, pelanggan, pemasok, produk/jasa, pajak, dan pengaturan akun; Master
Pengguna tidak tersedia. Menu **Transaksi**, **Jurnal Transaksi**, dan
**Laporan** digunakan untuk pembukuan.

### Rutinitas pemeriksaan

1. Pada **Beranda**, tinjau jumlah draft dan daftar draft terawal. Pilih
   **Tinjau** atau **Buka modul** untuk membuka transaksi terkait.
2. Periksa tanggal, pihak, rincian, akun, total, pajak, dan bukti. Jika draft
   perlu diperbaiki, gunakan fasilitas edit pada modul yang mendukungnya atau
   koordinasikan dengan pembuat.
3. Posting setelah benar. Periksa hasil melalui **Jurnal Transaksi**, buku
   besar, dan laporan terkait.
4. Periksa jumlah usulan opname menunggu. Buka **Transaksi → Opname &
   Penyesuaian**, tinjau stok sekarang dan usulan, kemudian tindak lanjuti
   sebagaimana bagian 11.8.
5. Pada akhir periode, buka **Akuntansi → Tutup Buku**, pilih bulan, lalu
   tinjau seluruh hasil pemeriksaan, termasuk draft, keseimbangan jurnal,
   piutang, utang, persediaan, aset tetap, klasifikasi kas/bank, dan rekonsiliasi
   rekening bank. Akuntansi menyiapkan dan menyelesaikan temuan; hanya Admin
   Perusahaan yang dapat menutup periode atau membuka kembali periode terakhir.
   Tutup periode secara berurutan dan pastikan semua draft selesai.

Beranda menampilkan draft seluruh perusahaan, termasuk tanggal mendatang,
dan sampai 15 draft terawal menurut tanggal. Rincian piutang/utang saldo awal
yang diposting bersama induknya tidak dihitung sebagai draft terpisah. Koreksi
saldo awal terposting tetap khusus Admin.

## 5. Keuangan

**Masuk:** gunakan role **Keuangan**. Menu: **Beranda**, **Kas & Bank**,
**Rekonsiliasi Bank**, **Pembayaran Utang**, dan **Penerimaan Piutang**.

1. Periksa saldo akun kas/bank, tagihan terbuka, tagihan lewat jatuh tempo,
   dan jumlah draft pada **Beranda**.
2. Untuk penerimaan/pengeluaran umum atau transfer, buka **Kas & Bank**; isi
   tanggal, jenis, akun, nominal, keterangan, dan bukti, lalu simpan draft.
   Gunakan alur bagian 11.5.
3. Untuk pembayaran pemasok, buka **Pembayaran Utang**, pilih pemasok dan
   tagihan yang sesuai, isi alokasi dan akun pembayaran, lalu simpan draft.
4. Untuk pelunasan pelanggan, buka **Penerimaan Piutang**, pilih pelanggan dan
   tagihan, isi alokasi dan akun penerimaan, lalu simpan draft.
5. Minta Akuntansi/Admin memeriksa dan mem-posting. Draft belum mengurangi
   tagihan dan belum mengubah saldo kas.
6. Pada **Rekonsiliasi Bank**, pilih rekening dan bulan, masukkan saldo awal,
   saldo akhir, serta setiap mutasi rekening koran. Simpan, lalu cocokkan setiap
   baris ke jurnal dengan arah dan nominal yang sama. Selesaikan rekonsiliasi
   hanya setelah seluruh mutasi rekening dan jurnal cocok. Akuntansi/Admin juga
   dapat meninjau dan menyelesaikan rekonsiliasi.

   Input saldo dan jumlah pada halaman ini memakai format Indonesia: titik
   untuk pemisah ribuan dan koma untuk desimal, misalnya
   `1.000,50`. Maksimal 200 mutasi per
   rekening dan bulan. Menyimpan ulang rekening koran menghapus pencocokan
   sebelumnya. Rekonsiliasi ini belum mendukung transaksi dalam perjalanan:
   semua mutasi rekening dan jurnal pada bulan yang sama harus cocok satu
   per satu, dan saldo awal serta akhir harus sama dengan buku besar.

Saldo pada beranda berasal dari jurnal akun kas/bank hingga hari ini, bukan
koneksi rekening bank. Tagihan mengikuti status saat ini dan dapat mencakup
tanggal mendatang. Pelanggan/pemasok tidak aktif perlu diaktifkan oleh pengguna
berwenang sebelum dipilih.

## 6. Purchasing

**Masuk:** gunakan role **Purchasing**. Menu: **Beranda**, **Pembelian**,
**Pembayaran Utang**, **Pemasok**, dan **Produk**.

1. Tinjau draft, pembelian barang, dan tagihan pembelian pada **Beranda**.
2. Pada **Pemasok**, tambah atau ubah data dan status pemasok. Penghapusan
   permanen dilakukan pengguna berwenang; pemasok yang sudah digunakan tidak
   dapat dihapus.
3. Pada **Produk**, tambah atau ubah produk bertipe **Barang**. Role ini tidak
   menghapus produk dan tidak mengelola jasa.
4. Pada **Pembelian**, pilih pemasok dan barang, isi tanggal, jumlah, harga,
   pembayaran, dan pajak bila ada. Simpan draft untuk diperiksa Akuntansi/Admin.
   Pembelian jasa dilakukan oleh Akuntansi/Admin.
5. Pada **Pembayaran Utang**, pilih pemasok dan tagihan yang sudah terposting,
   isi alokasi pembayaran, dan simpan draft. Posting tetap oleh Akuntansi/Admin.

Ringkasan utang pada beranda berfokus pada pembelian kredit yang hanya berisi
barang; saldo awal dan utang dari transaksi lain tidak termasuk. Jumlah draft
dan pembelian beranda memakai role pembuat **saat ini**, sehingga perubahan
role atau penghapusan akun pembuat dapat mengubah angka ringkasan.

## 7. Penjualan

**Masuk:** gunakan role **Penjualan**. Menu: **Beranda**, **Penjualan**,
**Penerimaan Piutang**, **Pelanggan**, dan **Katalog Produk**.

1. Periksa penjualan bulan berjalan, draft, dan piutang penjualan pada
   **Beranda**. Ringkasan piutang tersebut tidak mencakup saldo awal atau
   piutang dari transaksi lain.
2. Buat atau perbarui pelanggan pada **Pelanggan**. Lihat produk, harga, dan
   stok pada **Katalog Produk**; role ini tidak mengubah produk.
3. Buka **Penjualan**, pilih pelanggan dan barang/jasa, masukkan rincian,
   potongan dan pajak jika ada, serta pilih tunai atau kredit. Untuk kredit,
   isi jatuh tempo; untuk tunai, pilih akun penerimaan. Simpan draft.
4. Setelah Akuntansi/Admin mem-posting penjualan kredit, gunakan
   **Penerimaan Piutang** untuk membuat draft penerimaan dan mengalokasikannya
   ke tagihan yang dipilih. Minta posting setelah diperiksa.

Stok katalog adalah saldo saat ini; draft penjualan tidak mereservasi stok.
Lampiran role Penjualan dibatasi pada transaksi Penjualan dan Penerimaan Piutang.

## 8. Kasir

**Masuk:** gunakan role **Kasir**. Menu: **Beranda**, **Kasir**, **Riwayat
Kasir**, **Pelanggan**, dan **Katalog Produk**.

### 8.1 Melayani pembayaran

1. Buka **Kasir**. Cari nama/kode produk atau pindai kode yang tersimpan,
   lalu tekan Enter. Pilih barang atau jasa dan masukkan jumlah pada keranjang.
2. Pilih **Pelanggan**. Untuk pembeli umum, pilih pelanggan umum yang sudah
   disiapkan; tambahkan pelanggan melalui menu Pelanggan jika diperlukan.
3. Pilih mode **Pajak untuk seluruh item**: tidak dikenakan, harga termasuk,
   atau pajak ditambahkan. Jika dikenakan, pilih pajak yang sesuai.
4. Pilih metode **Tunai**, **QRIS**, **Kartu**, atau **Transfer**, lalu pilih
   akun penerimaan kas/bank.
5. Untuk tunai, masukkan **Uang diterima** atau gunakan pilihan uang pas dan
   nominal cepat. Periksa total dan kembalian. Contoh total Rp125.000 dapat
   menawarkan Rp130.000, Rp150.000, dan Rp200.000.
6. Untuk pembayaran elektronik, pastikan dana benar-benar diterima di alat
   atau aplikasi penyedia. Co-Balance hanya mencatat metode tersebut; tidak
   memproses atau memverifikasi pembayaran.
7. Pilih **Bayar dan selesaikan** setelah data benar. Transaksi langsung
   diposting, membuat jurnal, dan mengurangi stok barang. Buka/cetak struk.

Jika koneksi terputus saat pembayaran, **jangan langsung membuat transaksi
baru**. Gunakan **Periksa / coba ulang pembayaran** pada browser dan perangkat
yang sama untuk memastikan hasil tanpa transaksi ganda.

### 8.2 Riwayat dan koreksi

Buka **Riwayat Kasir** atau transaksi terbaru di beranda untuk melihat transaksi
dan membuka kembali struk. Kasir tidak membatalkan transaksi terposting; minta
Akuntansi/Admin menangani pembatalan atau retur melalui modul yang sesuai.

## 9. Gudang

**Masuk:** gunakan role **Gudang**. Gunakan beranda, menu stok/kartu stok,
dan **Stock Opname**. Gudang melihat jumlah barang, bukan nilai rupiah.

1. Pada beranda, periksa jumlah barang dan peringatan stok. Stok nol ditandai
   habis; stok positif pada atau di bawah batas minimum ditandai hampir habis.
   Batas minimum nol menonaktifkan peringatan hampir habis.
2. Buka daftar stok, cari produk, dan buka kartu stok untuk melihat riwayat
   mutasinya. Saldo adalah kondisi sistem, bukan hasil hitung fisik baru.
3. Hitung barang secara fisik. Pada **Stock Opname**, pilih produk aktif,
   masukkan **Jumlah fisik** memakai koma jika desimal, dan isi catatan wajib.
   Contoh: `12,5` atau `0,25`, maksimal empat angka desimal.
4. Pilih **Kirim usulan**, lalu periksa status di **Riwayat usulan**:
   Menunggu, Selesai, atau Ditolak.

Usulan hanya pemberitahuan; stok dan jurnal **belum berubah**. Akuntansi/Admin
meninjau dan mem-posting penyesuaian. Jika penyesuaian yang terkait dibatalkan,
usulan kembali menunggu.

## 10. Manajer / Pemilik

**Masuk:** gunakan role **Manajer / Pemilik**. Semua halaman role ini
bersifat baca-saja.

1. Pada **Beranda**, lihat saldo kas jurnal sampai hari ini, laba/rugi bulan
   berjalan, utang dan piutang terbuka, penjualan/retur, serta peringatan stok.
2. Buka **Penjualan**, pilih tanggal mulai/akhir, tampilkan hasil, dan gunakan
   navigasi halaman untuk rincian penjualan serta retur yang terposting.
   Ringkasan mengikuti status transaksi saat ini, bukan rekonstruksi status
   historis untuk transaksi yang kemudian dibatalkan.
3. Buka **Utang** atau **Piutang** untuk melihat jumlah, keterlambatan, dan
   rincian tagihan terbuka. Tagihan mengikuti status saat ini dan dapat
   mencakup tanggal mendatang; draft belum mengurangi saldo.
4. Buka **Persediaan** untuk melihat stok serta riwayat barang, atau **Jurnal**
   untuk melihat rincian jurnal. Menu **Laporan** menyediakan laba rugi,
   neraca, laporan akuntansi, buku besar, jurnal umum, dan bagan akun.
5. Pada **Mutasi Kas & Bank**, pilih periode untuk melihat saldo awal, masuk,
   keluar, saldo akhir, dan rincian bersih per jurnal.
6. Gunakan **Tutup Buku** dan **Rekonsiliasi Bank** untuk melihat status
   pemeriksaan dan hasil rekonsiliasi; seluruh akses Manajer bersifat baca-saja.

Mutasi kas adalah nilai **bersih per jurnal**: debit dan kredit kas dalam
jurnal yang sama saling mengurangi. Transfer internal bersih nol tidak
ditampilkan. Ini bukan laporan arus kas operasi/investasi/pendanaan dan bukan
saldo dari koneksi bank. Jika akun induk kas/bank `1100` tidak tersedia,
aplikasi menampilkan peringatan; hubungi Admin.

## 11. Petunjuk transaksi

Bagian ini digunakan terutama oleh Admin dan Akuntansi. Role lain mengikuti
menu dan batas akses pada bagiannya masing-masing. Simpan draft, periksa,
dan posting sesuai kewenangan; nama tombol dapat berbeda antarhalaman.

### 11.1 Saldo awal

**Menu:** Transaksi → Saldo Awal.

1. Tentukan satu tanggal pembukaan. Gunakan hanya jika perusahaan memiliki
   saldo yang perlu dipindahkan ke aplikasi.
2. Isi **Saldo Akun** untuk akun umum. Jangan ulangi akun kontrol piutang,
   utang, persediaan, atau nilai aset yang sudah dimasukkan pada rincian.
3. Isi **Piutang per faktur** dan **Utang per faktur** dengan pihak, nomor,
   jatuh tempo, dan sisa nominal. Isi persediaan per produk dengan jumlah dan
   total nilai, serta aset tetap dengan nilai perolehan dan akumulasi awal.
4. Pilih akun ekuitas penyeimbang, periksa pratinjau debit, kredit, dan
   selisih, lalu simpan draft dan posting.

Hanya satu pembukaan per perusahaan; posting mensyaratkan jurnal perusahaan
masih kosong. Pembukaan yang dibatalkan tetap tersimpan dan tidak dapat dibuat
ulang sebagai pembukaan baru. Akumulasi penyusutan awal mencakup bulan
pembukaan; penyusutan berikutnya dimulai sesudahnya.

Koreksi pembukaan terposting hanya oleh Admin melalui pratinjau, alasan, dan
konfirmasi. Periode yang sudah ditutup harus dibuka kembali terlebih dahulu.
Koreksi memperbarui jurnal awal dan dapat mengubah laporan historis. Jurnal
HPP berikutnya tidak dihitung ulang, sedangkan jadwal dan jurnal penyusutan
dihitung ulang; aset yang sudah dilepas tidak dapat dikoreksi melalui alur ini.
Tinjau kembali laporan dan buku pembantu setelahnya.

### 11.2 Penjualan

**Menu:** Transaksi → Penjualan, atau **Penjualan** pada role Penjualan.

1. Pilih pelanggan, tanggal, produk/jasa, jumlah, harga, dan potongan jika ada.
   Atur pajak per rincian: tidak dikenakan, termasuk, atau belum termasuk.
   Jika pelanggan belum tersedia, tekan tombol **+** di sebelah pilihan
   pelanggan, isi data pada formulir, lalu simpan. Pelanggan baru langsung
   terpilih tanpa meninggalkan penjualan; kode pelanggan harus unik.
2. Pilih tunai dan akun kas/bank, atau kredit dan tanggal jatuh tempo.
   Periksa total, simpan draft, dan tambahkan bukti bila diperlukan.
3. Setelah diperiksa, Akuntansi/Admin mem-posting. Penjualan barang mengurangi
   stok dan mencatat HPP; penjualan jasa tidak mengubah stok. Kredit membuat
   tagihan piutang.

Penjualan yang sudah memiliki alokasi penerimaan, retur, beban jasa terposting,
atau mutasi stok lanjutan dapat memerlukan penyelesaian transaksi terkait
sebelum pembatalan. Periksa pesan yang ditampilkan.

### 11.3 Beban penjualan jasa

**Menu:** Transaksi → Beban Penjualan Jasa.

1. Pilih rincian jasa pada penjualan terposting; masukkan biaya **baru** yang
   belum dicatat di modul lain dan pilih akun beban.
2. Untuk tunai pilih akun kas/bank; untuk kredit pilih pemasok dan jatuh tempo.
   Isi keterangan, simpan draft, lalu posting setelah diperiksa.
3. Utang kredit dibayar melalui **Pembayaran Utang**. Ringkasan menunjukkan
   pendapatan sebelum pajak, retur, beban langsung, dan selisih per rincian.
   Selisih ini bukan laba bersih perusahaan.

Tidak ada rincian pajak atau mutasi stok pada beban ini. Pembayaran terkait
harus dibatalkan terlebih dahulu sebelum membatalkan beban terposting; beban
terposting harus ditangani sebelum penjualan asal dapat dibatalkan.

### 11.4 Pembelian

**Menu:** Transaksi → Pembelian, atau **Pembelian** pada role Purchasing.

1. Pilih pemasok, tanggal, barang/jasa, jumlah, harga, dan pajak masukan
   sesuai transaksi. Purchasing hanya dapat memilih barang.
2. Untuk tunai pilih akun kas/bank; untuk kredit isi jatuh tempo. Periksa
   rincian dan total, tambahkan bukti bila diperlukan, lalu simpan draft.
3. Setelah posting, barang menambah persediaan dengan harga pokok rata-rata;
   jasa menggunakan akun pembelian/beban pada master. Pembelian kredit
   menimbulkan utang.

Pembatalan dapat ditolak jika ada pembayaran terposting atau mutasi stok
berikutnya. Periksa transaksi terkait sebelum meminta pembatalan.

### 11.5 Kas dan bank

**Menu:** Transaksi → Kas dan Bank, atau **Kas & Bank** pada role Keuangan.

1. Pilih **kas masuk**, **kas keluar**, atau **transfer** dan isi tanggal serta
   nominal.
2. Pilih akun kas/bank dan akun lawan yang sesuai. Untuk transfer, pilih dua
   akun kas/bank yang berbeda. Akun kas/bank berada dalam kelompok `1100`.
3. Isi keterangan dan bukti bila diperlukan, simpan draft, lalu minta
   Akuntansi/Admin mem-posting.

Jangan gunakan akun kontrol utang/piutang sebagai akun lawan untuk pelunasan;
gunakan modul **Pembayaran Utang** atau **Penerimaan Piutang** agar alokasi
tagihan ikut diperbarui.

### 11.6 Penerimaan piutang dan pembayaran utang

**Menu:** Transaksi → Penerimaan Piutang / Pembayaran Utang; kedua menu juga
tersedia untuk Keuangan. Penjualan memiliki Penerimaan Piutang dan Purchasing
memiliki Pembayaran Utang.

1. Pilih tanggal, pelanggan atau pemasok, dan akun kas/bank yang digunakan.
2. Pilih tagihan terposting yang sesuai, lalu masukkan jumlah alokasi. Jumlah
   tidak boleh melebihi sisa tagihan; periksa total dan bukti.
3. Simpan draft. Setelah posting, sisa tagihan berkurang; pembatalan
   penerimaan/pembayaran mengembalikan sisa sesuai jurnal pembalik.

Tagihan dapat berasal dari saldo awal atau transaksi kredit yang didukung,
termasuk pelepasan aset kredit untuk piutang serta perolehan aset dan beban
jasa kredit untuk utang.

### 11.7 Persediaan, retur, dan penyesuaian

**Menu:** Transaksi → Persediaan; Transaksi → Retur Transaksi; Transaksi →
Opname & Penyesuaian.

- **Persediaan:** lihat saldo jumlah, nilai, harga pokok rata-rata, dan riwayat
  mutasi produk yang dipilih.
- **Retur:** pilih jenis retur pembelian/penjualan dan satu rincian transaksi
  asal terposting, isi jumlah serta akun selisih bila diperlukan, lalu simpan
  dan posting. Pajak dan nilai dihitung proporsional. Retur kredit mengurangi
  sisa tagihan dan ditolak jika melebihi sisa yang tersedia. Retur tunai
  menggunakan akun kas/bank asal.
- Draft retur tidak dapat diedit; jika salah, buat draft baru. Barang yang
  sudah memiliki mutasi stok lanjutan dapat membatasi pembatalan retur.
  Retur transaksi lama memerlukan riwayat akun saat posting; riwayat yang
  tidak tersedia tidak dapat ditebak dari master saat ini.

### 11.8 Menindaklanjuti stock opname

**Menu:** Transaksi → Opname & Penyesuaian.

1. Pada **Usulan Opname Gudang**, periksa jumlah sistem saat usulan, jumlah
   fisik, selisih, **Stok sekarang**, dan catatan. Stok mungkin telah berubah
   sejak usulan dibuat. Bila tidak sesuai, pilih **Tolak**.
2. Pilih **Tinjau & sesuaikan** untuk memuat produk dan jumlah fisik ke
   formulir. Pilih tanggal, periksa jenis dan jumlah, isi akun selisih
   pendapatan/beban, keterangan, dan bukti jika diperlukan.
3. Isi **Total nilai perolehan** hanya bila diperlukan untuk penambahan stok
   dan harga pokok rata-rata tidak tersedia: gunakan **nilai total seluruh
   tambahan barang**, bukan harga per unit. Contoh: tambahan 5 unit dengan
   total biaya perolehan Rp100.000 diisi `100.000`. Bila harga pokok rata-rata
   tersedia, penambahan memakai harga rata-rata saat posting.
4. Simpan draft. Periksa kembali dan **Posting draft**. Stok serta jurnal
   berubah setelah posting; usulan terkait menjadi Selesai.

Formulir juga mendukung penambahan atau pengurangan stok langsung, satu produk
per draft. Untuk jenis stock opname, masukkan **jumlah fisik keseluruhan**,
bukan jumlah selisih. Selisih dihitung terhadap saldo saat posting. Draft
penyesuaian tidak dapat diedit; jika salah, buat draft baru. Pembatalan hanya
diperbolehkan jika belum ada mutasi stok berikutnya untuk produk tersebut.

### 11.9 Aset tetap

**Menu:** Transaksi → Aset Tetap.

1. Untuk aset baru, buka **Catat perolehan aset baru**. Isi tanggal, kode,
   nama, nilai perolehan, nilai residu, umur manfaat dalam bulan, tanggal
   mulai penyusutan, serta akun aset, akumulasi, dan beban penyusutan.
2. Pilih tunai dan akun kas/bank, atau kredit dan pemasok/jatuh tempo. Isi
   referensi, keterangan, dan lampiran bila perlu. Simpan dan posting draft.
3. Untuk aset lama yang nilainya **sudah tercatat melalui Saldo Awal**, gunakan
   formulir pendaftaran aset lama; pendaftaran ini tidak membuat jurnal baru.
   Jangan mencatat aset yang sama dua kali.
4. Buat dan posting penyusutan bulanan secara berurutan. Metode yang tersedia
   adalah garis lurus dan dihitung satu bulan penuh mulai bulan awal penyusutan.
5. Untuk pelepasan, pilih aset, tanggal, hasil pelepasan dan cara penerimaan
   yang sesuai. Periksa nilai buku dan laba/rugi lalu simpan dan posting.

Bulan pelepasan mencakup penyusutan satu bulan penuh jika belum diposting.
Pembatalan dibatasi bila merusak urutan penyusutan atau tagihan telah dibayar.
Pengaturan akun laba/rugi pelepasan harus disiapkan sesuai kebutuhan.

### 11.10 Modal, prive, dan penyesuaian akuntansi

**Modal dan Prive:** pilih jenis setoran modal atau prive, tanggal, akun
kas/bank, akun ekuitas yang sesuai, dan nominal; simpan draft dan posting.
Prive memakai akun ekuitas rincian, bukan akun induk atau akun Modal Usaha.

**Penyesuaian Akuntansi:** isi tanggal dan keterangan, tambah minimal dua
baris akun rincian, dan isi debit **atau** kredit pada tiap baris. Pastikan
total debit sama dengan total kredit sebelum menyimpan dan mem-posting.
Gunakan modul transaksi khusus untuk utang, piutang, stok, dan aset agar
buku pembantu tidak terlewat.

**Pemindahan saldo laba:** pada Penyesuaian Akuntansi, pilih **Pindahkan Saldo
Laba (Rugi) Berjalan**, pilih tahun sebelum tahun berjalan, tinjau status,
buat draft, lalu posting setelah diperiksa. Aplikasi menggunakan akun `3103`
dan `3102` yang disyaratkan. Fitur ini bukan tutup buku atau penguncian periode.

## 12. Jurnal dan laporan

**Akses:** Admin dan Akuntansi menggunakan menu **Jurnal Transaksi** dan
**Laporan**. Manajer menggunakan **Jurnal** dan **Laporan** baca-saja.
Role operasional lain tidak memiliki seluruh menu ini.

| Menu | Cara memakai | Yang diperiksa |
|---|---|---|
| Jurnal Transaksi / Jurnal | Buka daftar, pilih **Detail** atau **PDF** | Nomor, jenis, akun debit/kredit, pihak, pelaku posting/pembatalan, dan keterangan |
| Laporan → Jurnal Umum | Buka daftar dan rincian | Rincian jurnal dan akun |
| Laporan → Buku Besar | Pilih akun serta tanggal mulai/selesai, tampilkan atau ekspor | Saldo sebelum periode dan mutasi akun |
| Laporan → Laporan Akuntansi | Pilih **Tanggal neraca saldo**, tekan **Tampilkan**, atau buka PDF | Akumulasi debit, kredit, dan saldo hingga tanggal pilihan, termasuk jurnal pembalik |
| Laporan → Laba Rugi | Pilih tanggal mulai/selesai, tampilkan atau ekspor | Pendapatan, beban, dan laba/rugi bersih selama periode |
| Laporan → Neraca | Pilih tanggal neraca, tampilkan atau ekspor | Aset, kewajiban, dan ekuitas pada tanggal pilihan |
| Laporan → Bagan Akun | Buka daftar atau PDF | Kode, nama, dan hierarki akun |
| Akuntansi → Tutup Buku | Pilih bulan dan tinjau kesiapan; Admin menutup/membuka kembali periode terakhir | Semua pemeriksaan wajib lulus; penutupan harus berurutan |
| Rekonsiliasi Bank (Keuangan) / Akuntansi → Rekonsiliasi Bank | Input saldo dan mutasi rekening koran, cocokkan ke jurnal, lalu selesaikan | Saldo awal/akhir harus sama dengan buku besar dan seluruh mutasi harus cocok |

Draft tidak masuk laporan sampai diposting. Neraca saldo bukan laporan laba
rugi; total debit/kredit dapat digunakan untuk memeriksa keseimbangan, tetapi
tidak membuktikan setiap transaksi sudah diklasifikasikan dengan benar.
Pada neraca, saldo kredit kewajiban/ekuitas ditampilkan positif dan saldo
debit negatif; aset menggunakan tanda sebaliknya. Data jurnal lama yang tidak
tersedia ditandai **Tidak tercatat**; nama pelaku mengikuti data pengguna saat ini.

## 13. Masalah umum dan batasan

| Situasi | Tindakan |
|---|---|
| Menu tidak terlihat | Pastikan login dengan role yang tepat; tidak semua role memiliki menu yang sama. |
| Akun tidak dapat dipilih | Periksa perusahaan, pengaturan akun, kelompok akun, dan apakah akun merupakan rincian, bukan induk. |
| Produk/pihak tidak tersedia | Periksa status aktif, tipe barang/jasa, dan hak akses role. |
| Draft tidak muncul dalam laporan | Minta Akuntansi/Admin memeriksa dan mem-posting; draft belum mengubah saldo. |
| Tagihan tidak dapat dipilih atau alokasi ditolak | Pastikan pihak dan transaksi asal tepat, sudah terposting, dan masih memiliki sisa yang cukup. |
| Posting atau pembatalan ditolak | Baca pesan, periksa stok, akun, transaksi terkait, pembayaran, retur, dan mutasi berikutnya. Jangan mengulang tanpa memperbaiki penyebab. |
| Selisih opname tidak sesuai | Bandingkan stok saat usulan dengan stok saat ini; hasil opname menggunakan saldo saat posting. |
| Laporan tidak sesuai harapan | Periksa perusahaan, tanggal filter, status posting, akun terkait, jurnal pembalik, dan transaksi lanjutan. |
| Struk POS dibutuhkan kembali | Buka **Riwayat Kasir** atau transaksi terbaru pada beranda Kasir. |

Tutup Buku mencakup pemeriksaan rekonsiliasi buku pembantu piutang, utang,
persediaan, dan aset tetap. Penguncian transaksi memerlukan migrasi
`application/sql/20261116_pengendalian_periode_rekonsiliasi.sql`; cadangkan
database dan jalankan satu kali sebelum menggunakan menu Tutup Buku atau
Rekonsiliasi Bank. Rekening koran diinput manual. Laporan arus kas
terklasifikasi tetap belum tersedia; Mutasi Kas & Bank Manajer adalah ringkasan
bersih jurnal.

Jika register aset perolehan lama sudah terhapus karena pembatalan, pemeriksaan
aset untuk periode sebelum pembatalan dapat ditandai tidak tersedia dan perlu
ditelusuri; aplikasi tidak mengarang data yang hilang. Tutup buku dan
rekonsiliasi tidak membuat jurnal otomatis.

## 14. Persiapan teknis

Pengelola teknis harus mencadangkan database dan menyiapkan skema serta migrasi
yang relevan sebelum fitur digunakan. Jangan menjalankan migrasi berulang tanpa
memeriksa kondisi database. Skema dasar tidak disertakan di repository; lihat
[README](../README.md) untuk prasyarat, urutan, dan batasan lengkap.

Migrasi yang secara langsung terkait penggunaan:

- Keamanan login dan role: `20261112_login_security.sql`,
  `20261112_satu_admin_perusahaan.sql`,
  `20261112_role_pengguna.sql`, dan `20261115_hapus_role_user.sql`.
- Retur dan riwayat akun: `20261109_riwayat_akun_transaksi.sql`.
- Gambar produk, usulan opname, dan stok minimum:
  `20261113_gambar_produk.sql`, `20261113_gudang_usulan_opname.sql`, dan
  `20261114_stok_minimum_produk.sql`.
- Koreksi saldo awal dan pemindahan laba:
  `20261113_koreksi_saldo_awal.sql` dan `20261114_pemindahan_laba.sql`.
- Beban penjualan jasa dan POS:
  `20261115_beban_penjualan_jasa.sql` dan `20261115_pembayaran_kasir.sql`.

Saldo awal, aset tetap, lampiran, dan fitur lain juga dapat memerlukan skema
atau perubahan yang dijelaskan dalam README. Gunakan HTTPS dan browser modern;
pastikan pengaturan unggahan dan database memadai untuk lampiran.
