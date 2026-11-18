# Proposal Pengajuan Aplikasi Co-Balance

## Sistem Informasi Akuntansi dan Operasional Usaha

| Keterangan | Isi |
|---|---|
| Nomor proposal | [Nomor proposal] |
| Tanggal | [Tanggal pengajuan] |
| Ditujukan kepada | [Nama perusahaan / instansi] |
| Disusun oleh | [Nama penyedia / pengembang] |
| Penanggung jawab | [Nama dan jabatan] |
| Kontak | [Nomor telepon dan alamat surel] |
| Masa berlaku penawaran | [Diisi sesuai kesepakatan] |

> **Catatan:** Dokumen ini merupakan rancangan proposal. Nilai investasi,
> persentase termin, jadwal, jumlah pengguna, cakupan pendampingan, dan
> penyesuaian harus dilengkapi serta disepakati sebelum menjadi komitmen kerja.
> Fitur yang dijelaskan mengikuti kondisi aplikasi saat proposal disusun;
> kebutuhan di luar kemampuan tersebut memerlukan kesepakatan tersendiri.

## 1. Ringkasan pengajuan

Co-Balance adalah aplikasi berbasis web yang membantu perusahaan mencatat
transaksi usaha, mengelola persediaan dan tagihan, serta menyusun informasi
akuntansi dalam satu alur kerja. Aplikasi menyediakan ruang kerja berdasarkan
peran, mulai dari petugas penjualan, pembelian, kasir, gudang, keuangan,
akuntansi, hingga manajer dan Admin Perusahaan.

Melalui pengajuan ini, kami mengusulkan penerapan Co-Balance pada
**[nama perusahaan]**. Pekerjaan mencakup persiapan, implementasi,
penyesuaian yang disepakati, pengujian bersama, pelatihan, dan peluncuran.
Pelaksanaan serta pembayaran dibagi menjadi tiga termin: **uang muka (DP)**,
**implementasi dan penyesuaian**, serta **peluncuran (launching)**.

## 2. Latar belakang

Kegiatan usaha melibatkan transaksi yang saling berkaitan: pembelian menambah
persediaan dan dapat menimbulkan utang; penjualan mengurangi persediaan dan
dapat menimbulkan piutang; pembayaran dan penerimaan memengaruhi kas, bank,
serta sisa tagihan. Jika pencatatan dilakukan secara terpisah, pemeriksaan
saldo dan penyusunan laporan dapat membutuhkan lebih banyak waktu.

Co-Balance ditawarkan sebagai sarana untuk menyatukan alur tersebut. Transaksi
disiapkan sesuai tugas pengguna, diperiksa dan diposting oleh pihak berwenang,
kemudian hasilnya dapat ditelusuri melalui jurnal dan laporan. Untuk transaksi
kasir, penyelesaian pembayaran langsung melakukan posting sesuai alur POS.

## 3. Tujuan

1. Memusatkan data pelanggan, pemasok, produk, akun, pajak, dan pengguna.
2. Membantu pencatatan transaksi usaha dan penyusunan jurnal secara terstruktur.
3. Memantau persediaan, utang, piutang, serta saldo kas dan bank berdasarkan
   transaksi yang tercatat.
4. Memisahkan tugas operasional, pemeriksaan, dan pengambilan keputusan
   berdasarkan hak akses.
5. Menyediakan informasi yang dapat ditinjau untuk pengendalian dan pelaporan
   perusahaan.

## 4. Gambaran penggunaan

Alur umum penggunaan aplikasi adalah sebagai berikut:

1. Admin Perusahaan dan Akuntansi menyiapkan akun, pengaturan akun, data
   master, pengguna, serta saldo awal jika diperlukan.
2. Petugas mencatat transaksi sebagai **draft** sesuai tugasnya. Draft belum
   mengubah saldo, stok, tagihan, atau laporan.
3. Akuntansi atau Admin memeriksa dan melakukan **posting**. Transaksi yang
   diposting menghasilkan jurnal dan dampak usaha sesuai jenis transaksinya.
4. Petugas dan manajemen memantau data melalui menu operasional, jurnal, dan
   laporan sesuai hak akses.
5. Pada akhir periode, petugas memeriksa rekonsiliasi dan kesiapan tutup buku.
   Admin Perusahaan melakukan penutupan setelah pemeriksaan terpenuhi.

**Pengecualian POS:** transaksi kasir yang diselesaikan langsung diposting,
membuat jurnal, dan mengurangi stok barang. Pembatalan dilakukan oleh
Akuntansi atau Admin melalui alur yang tersedia.

## 5. Rincian fitur aplikasi

### 5.1 Data master dan pengaturan

| Fitur | Kemampuan utama |
|---|---|
| Akun | Mengelola kode, nama, tipe, dan hierarki akun; memilih akun rincian sesuai kebutuhan transaksi. |
| Pengaturan akun | Menghubungkan akun yang digunakan oleh proses transaksi, misalnya kas/bank, piutang, utang, persediaan, pajak, pendapatan, dan beban. |
| Pelanggan | Mengelola identitas, kontak, dan status pelanggan. Pilihan pelanggan pada formulir terkait mendukung pencarian berdasarkan kode atau nama. |
| Pemasok | Mengelola identitas, kontak, dan status pemasok sesuai hak akses. |
| Produk dan jasa | Mengelola data barang atau jasa, kode, satuan, harga, akun terkait, dan status. Barang mendukung pengaturan stok minimum; gambar produk bersifat opsional. |
| Pajak | Mengelola data pajak yang digunakan pada transaksi yang mendukungnya. |
| Pengguna | Mengelola akun petugas dan menetapkan role sesuai kewenangan Admin Perusahaan. |

Penggunaan akun dan data master harus disesuaikan dengan kebijakan pembukuan
perusahaan. Ketersediaan pilihan dan tindakan pada setiap menu mengikuti role
dan status data.

### 5.2 Penjualan

- Mencatat penjualan barang dan jasa kepada pelanggan.
- Memilih pembayaran tunai atau kredit; penjualan kredit memerlukan tanggal
  jatuh tempo dan menghasilkan tagihan piutang setelah posting.
- Memasukkan jumlah, harga, potongan, dan perlakuan pajak per rincian:
  tidak dikenakan, termasuk pajak, atau belum termasuk pajak.
- Menyimpan draft, mengubah draft, melampirkan bukti, dan melakukan posting
  sesuai kewenangan.
- Penjualan barang terposting mengurangi stok dan mencatat harga pokok
  penjualan; penjualan jasa tidak mengubah stok.
- Mendukung retur penjualan melalui modul retur, dengan batasan berdasarkan
  transaksi asal dan kondisi tagihan atau stok.

### 5.3 Kasir / point of sale (POS)

- Mencari produk atau jasa melalui nama/kode, termasuk pemindaian kode produk
  yang tersimpan dengan perangkat yang berfungsi sebagai input.
- Menambahkan item dan jumlah ke keranjang serta memilih pelanggan.
- Menentukan mode pajak yang berlaku untuk seluruh item pada transaksi POS.
- Mencatat metode pembayaran tunai, QRIS, kartu, atau transfer dan memilih
  akun penerimaan.
- Menghitung kembalian untuk pembayaran tunai, termasuk pilihan nominal cepat.
- Menyelesaikan transaksi, membuka struk, dan melihat riwayat kasir.
- Menyediakan alur pemeriksaan/coba ulang pada perangkat dan browser yang sama
  jika hasil pembayaran belum pasti akibat koneksi terputus.

**Batasan:** QRIS, kartu, dan transfer hanya dicatat sebagai metode pembayaran.
Aplikasi tidak memproses atau memverifikasi pembayaran elektronik; petugas
harus memastikan pembayaran melalui penyedia terkait.

### 5.4 Pembelian

- Mencatat pembelian barang atau jasa dari pemasok.
- Memilih pembayaran tunai atau kredit, tanggal jatuh tempo, rincian, harga,
  dan pajak sesuai transaksi.
- Menyimpan draft dan bukti transaksi untuk diperiksa sebelum posting.
- Pembelian barang terposting menambah persediaan dengan harga pokok rata-rata;
  pembelian kredit menimbulkan utang.
- Mendukung retur pembelian sesuai transaksi asal dan batasan yang berlaku.
- Role Purchasing menggunakan alur pembelian **barang**; pembelian jasa
  dilakukan oleh Akuntansi atau Admin.

### 5.5 Utang dan piutang

- **Pembayaran Utang:** memilih pemasok, tagihan terposting, akun pembayaran,
  dan jumlah yang dialokasikan ke tagihan.
- **Penerimaan Piutang:** memilih pelanggan, tagihan terposting, akun
  penerimaan, dan jumlah yang dialokasikan ke tagihan.
- Mendukung alokasi terhadap tagihan yang tersedia dari transaksi terkait
  maupun saldo awal yang didukung aplikasi.
- Jumlah alokasi tidak boleh melebihi sisa tagihan. Sisa tagihan diperbarui
  setelah transaksi diposting.
- Pembatalan transaksi terposting mengikuti aturan dan mengembalikan dampak
  alokasi sesuai jurnal pembalik.

### 5.6 Kas dan bank

- Mencatat kas masuk, kas keluar, dan transfer antar akun kas/bank.
- Memilih tanggal, akun terkait, nominal, keterangan, serta lampiran bila
  diperlukan.
- Menyimpan draft untuk ditinjau dan diposting oleh pihak yang berwenang.
- Menampilkan saldo kas/bank berdasarkan jurnal yang tercatat di aplikasi.

Pelunasan utang dan piutang dilakukan melalui modul pembayaran atau penerimaan
agar alokasi tagihan juga diperbarui. Saldo bank pada aplikasi bukan hasil
koneksi langsung ke rekening bank.

### 5.7 Persediaan dan gudang

- Menampilkan saldo jumlah, nilai, harga pokok rata-rata, dan riwayat mutasi
  persediaan pada menu yang memiliki akses nilai.
- Menyediakan daftar stok dan kartu stok bagi petugas gudang; role Gudang melihat
  jumlah barang, bukan nilai rupiah.
- Menampilkan peringatan barang habis dan hampir habis berdasarkan batas stok
  minimum yang ditetapkan.
- Memungkinkan Gudang mengirim usulan stock opname beserta jumlah fisik dan
  catatan, lalu memantau status usulan.
- Memungkinkan Akuntansi/Admin meninjau usulan serta membuat penyesuaian stok
  atau stock opname. Stok dan jurnal berubah setelah penyesuaian diposting,
  bukan saat usulan dikirim.
- Mendukung retur barang sesuai aturan transaksi asal dan riwayat mutasi stok.

### 5.8 Saldo awal

- Menyiapkan saldo akun umum, rincian piutang dan utang per faktur, persediaan
  per produk, serta data aset tetap awal.
- Memilih akun ekuitas penyeimbang dan memeriksa pratinjau debit, kredit,
  serta selisih sebelum posting.
- Menyediakan koreksi saldo awal terposting oleh Admin Perusahaan melalui
  pratinjau dan konfirmasi sesuai ketentuan aplikasi.

Saldo awal dibuat satu kali per perusahaan. Posting mensyaratkan jurnal
perusahaan masih kosong. Ketepatan tanggal dan nilai saldo awal perlu
diverifikasi sebelum transaksi rutin diposting.

### 5.9 Aset tetap

- Mencatat perolehan aset baru secara tunai atau kredit.
- Mendaftarkan aset lama yang nilainya sudah dicatat melalui saldo awal tanpa
  membuat jurnal baru dari pendaftaran tersebut.
- Mengatur nilai perolehan, nilai residu, umur manfaat, tanggal mulai
  penyusutan, dan akun terkait.
- Membuat serta mem-posting penyusutan bulanan secara berurutan menggunakan
  metode garis lurus.
- Mencatat pelepasan aset dan menghitung nilai buku serta laba/rugi sesuai
  data transaksi dan pengaturan akun.

### 5.10 Beban penjualan jasa

- Mencatat biaya baru yang terkait rincian jasa pada penjualan terposting.
- Memilih akun beban, pembayaran tunai atau kredit, dan pihak terkait bila
  diperlukan.
- Menampilkan ringkasan pendapatan sebelum pajak, retur, beban langsung, dan
  selisih per rincian jasa. Selisih ini **bukan** laba bersih perusahaan.

### 5.11 Modal, prive, dan penyesuaian akuntansi

- Mencatat setoran modal dan prive melalui akun yang sesuai.
- Membuat jurnal penyesuaian dengan minimal dua baris akun dan total debit
  serta kredit seimbang.
- Menyediakan pemindahan saldo laba/rugi berjalan untuk tahun yang memenuhi
  ketentuan dan pengaturan akun aplikasi.
- Menggunakan modul khusus untuk transaksi yang berdampak pada buku pembantu
  utang, piutang, persediaan, dan aset.

### 5.12 Rekonsiliasi bank dan tutup buku

- Mengklasifikasikan akun rincian kas dan rekening bank.
- Memasukkan saldo serta mutasi rekening koran secara manual per rekening dan
  bulan, lalu mencocokkannya satu per satu dengan jurnal.
- Menyelesaikan rekonsiliasi setelah seluruh pemeriksaan terpenuhi.
- Memeriksa kesiapan periode, termasuk draft, keseimbangan jurnal, buku
  pembantu, klasifikasi akun, dan rekonsiliasi rekening bank.
- Memungkinkan Admin Perusahaan menutup periode secara berurutan atau membuka
  kembali periode terakhir sesuai aturan aplikasi.

Rekonsiliasi saat ini mensyaratkan seluruh mutasi rekening dan jurnal bank
pada bulan yang sama cocok satu per satu, serta saldo awal dan akhir sama
dengan buku besar. Transaksi dalam perjalanan lintas bulan belum didukung;
input dibatasi maksimal 200 mutasi per rekening per bulan. Rekonsiliasi dan
tutup buku tidak membuat jurnal penyesuaian otomatis.

### 5.13 Jurnal dan laporan

| Informasi | Kegunaan |
|---|---|
| Jurnal transaksi dan jurnal umum | Meninjau rincian jurnal serta akun yang terpengaruh. |
| Buku besar | Meninjau saldo sebelum periode dan mutasi akun dalam rentang tanggal. |
| Laporan akuntansi / neraca saldo | Meninjau akumulasi debit, kredit, dan saldo hingga tanggal pilihan. |
| Laba rugi | Meninjau pendapatan, beban, dan laba/rugi pada periode pilihan. |
| Neraca | Meninjau aset, kewajiban, dan ekuitas pada tanggal pilihan. |
| Bagan akun | Meninjau kode, nama, dan hierarki akun. |
| Mutasi Kas & Bank Manajer | Meninjau saldo awal, perubahan bersih per jurnal, dan saldo akhir pada periode pilihan. |

Sejumlah laporan dan rincian jurnal menyediakan keluaran PDF atau ekspor
sesuai menu yang tersedia. Mutasi Kas & Bank Manajer bukan laporan arus kas
yang terklasifikasi menjadi aktivitas operasi, investasi, dan pendanaan.
Kebenaran laporan bergantung pada data, pengaturan akun, dan transaksi
terposting yang dimasukkan perusahaan.

### 5.14 Lampiran dan antarmuka

- Formulir transaksi yang mendukung lampiran menerima satu bukti PDF, JPG,
  atau PNG dengan ukuran maksimal 5 MB per transaksi, sesuai hak akses.
- Gambar produk opsional mendukung JPG, PNG, atau WebP maksimal 2 MB dan
  ukuran gambar maksimal 4096 × 4096 piksel.
- Nominal uang menggunakan format Indonesia, misalnya `1.000.000,50`.
- Navigasi menyesuaikan role dan ukuran layar. Penggunaan dilakukan melalui
  browser modern.

## 6. Peran pengguna dan kewenangan

| Peran | Tugas utama | Kewenangan penting |
|---|---|---|
| Admin Perusahaan | Mengelola pengguna, master, transaksi, dan laporan perusahaan. | Dapat posting/pembatalan sesuai aturan; koreksi saldo awal terposting dan penutupan/pembukaan periode terakhir khusus Admin. |
| Akuntansi | Menyiapkan pembukuan, memeriksa draft, jurnal, laporan, dan kesiapan periode. | Dapat melakukan posting dan pembatalan sesuai aturan; tidak mengelola Master Pengguna. |
| Keuangan | Kas & Bank, rekonsiliasi bank, pembayaran utang, dan penerimaan piutang. | Menyiapkan draft transaksi dan rekonsiliasi; posting transaksi oleh Akuntansi/Admin. |
| Purchasing | Mengelola pemasok dan produk barang, menyiapkan pembelian barang dan pembayaran utang. | Draft untuk ditinjau; pembelian jasa serta posting dilakukan oleh pihak berwenang. |
| Penjualan | Mengelola pelanggan, melihat katalog, menyiapkan penjualan dan penerimaan piutang. | Draft untuk ditinjau dan diposting Akuntansi/Admin. |
| Kasir | Melayani penjualan melalui POS, melihat riwayat dan struk. | Penyelesaian POS langsung diposting; pembatalan dilakukan Akuntansi/Admin. |
| Gudang | Melihat stok dan kartu stok serta mengirim usulan opname. | Usulan tidak langsung mengubah stok atau jurnal. |
| Manajer / Pemilik | Memantau ringkasan, penjualan, tagihan, persediaan, jurnal, dan laporan. | Akses baca-saja. |

Pemisahan peran membantu pengendalian, tetapi tidak menggantikan pemeriksaan
internal, persetujuan, dan kebijakan akuntansi perusahaan.

## 7. Ruang lingkup pengajuan implementasi

Ruang lingkup berikut merupakan usulan yang perlu dikonfirmasi dalam
kesepakatan kerja:

1. **Analisis kebutuhan:** pembahasan alur usaha, role, modul yang digunakan,
   kondisi data, kebutuhan lingkungan, dan identifikasi perbedaan proses.
2. **Persiapan lingkungan:** menyiapkan aplikasi dan database pada lingkungan
   yang disepakati; memeriksa skema, migrasi, akses, dan konfigurasi.
3. **Konfigurasi awal:** menyiapkan perusahaan, akun, pengaturan akun, data
   master, pengguna, dan hak akses berdasarkan data yang disetujui.
4. **Saldo awal:** mendampingi penyiapan dan pemeriksaan saldo awal jika
   diperlukan, termasuk kesesuaian tanggal pembukaan dan rincian.
5. **Penyesuaian:** mengerjakan perubahan yang telah dirinci dan disetujui
   secara tertulis, beserta dampak biaya dan waktu apabila ada.
6. **Pengujian bersama:** memeriksa skenario kerja yang disepakati dan
   menindaklanjuti temuan dalam cakupan.
7. **Pelatihan:** mengenalkan alur penggunaan kepada perwakilan pengguna
   sesuai role dan menyerahkan panduan pengguna.
8. **Peluncuran dan serah terima:** memeriksa kesiapan, menjalankan perpindahan
   ke penggunaan operasional, dan menyelesaikan berita acara sesuai
   kesepakatan.

Jumlah perusahaan, lokasi, pengguna, volume data, metode pengisian atau
pemindahan data, jumlah sesi pelatihan, serta lingkungan yang termasuk dalam
penawaran: **[isi hasil kesepakatan]**.

## 8. Tahapan pelaksanaan dan hasil kerja

| Tahap | Kegiatan utama | Hasil kerja / bukti penyelesaian |
|---|---|---|
| Persiapan setelah DP | Pertemuan awal; identifikasi proses, kebutuhan, data, dan penanggung jawab; penyusunan rencana kerja. | Dokumen ruang lingkup dan jadwal yang disepakati, daftar kebutuhan serta data awal. |
| Implementasi dan penyesuaian | Persiapan lingkungan, konfigurasi, penyiapan data dan saldo awal, penyesuaian yang disetujui, serta pengujian bersama. | Aplikasi tersedia pada lingkungan yang disepakati; daftar konfigurasi dan penyesuaian; hasil pengujian dan tindak lanjut temuan. |
| Peluncuran | Pemeriksaan kesiapan, pelatihan pengguna, persetujuan penggunaan operasional, peluncuran, dan serah terima. | Panduan pengguna, catatan pelatihan, berita acara peluncuran dan serah terima sesuai kesepakatan. |

### Jadwal pelaksanaan

| Tahap | Estimasi / tanggal |
|---|---|
| Mulai dan persiapan | [Diisi] |
| Implementasi dan penyesuaian | [Diisi] |
| Pengujian dan pelatihan | [Diisi] |
| Peluncuran dan serah terima | [Diisi] |

Jadwal ditetapkan setelah ruang lingkup, ketersediaan akses dan data, serta
penanggung jawab kedua pihak disepakati. Perubahan kebutuhan, keterlambatan
data, temuan pengujian, atau hambatan lingkungan dapat memerlukan penyesuaian
jadwal yang disetujui bersama.

## 9. Pengujian dan kriteria penerimaan

Sebelum peluncuran, kedua pihak menyepakati daftar skenario pengujian yang
relevan dengan modul yang digunakan. Contoh skenario:

1. Login dan akses menu untuk setiap role yang digunakan.
2. Pengisian data master, akun, dan saldo awal yang telah diverifikasi.
3. Penjualan atau POS, pembelian, serta pemeriksaan dampak jurnal dan stok.
4. Penjualan/pembelian kredit dan alokasi penerimaan piutang/pembayaran utang.
5. Kas dan bank, usulan opname serta tindak lanjut penyesuaian, jika digunakan.
6. Aset tetap, rekonsiliasi bank, tutup buku, dan laporan, jika termasuk
   cakupan penerapan.
7. Pelatihan pengguna dan pemeriksaan kesiapan lingkungan produksi.

Setiap skenario dicatat bersama hasil, penanggung jawab, dan temuan.
**Kriteria penerimaan, prioritas temuan, batas waktu perbaikan, dan cara
persetujuan wajib disepakati sebelum pengujian dimulai.** Temuan di luar
ruang lingkup diproses melalui mekanisme perubahan, bukan otomatis termasuk
dalam penawaran. Hasil pengujian dan persetujuan dicatat dalam berita acara.

## 10. Nilai investasi dan termin pembayaran

### 10.1 Ringkasan biaya

| Komponen | Nilai |
|---|---:|
| Nilai pekerjaan yang disepakati | Rp [nilai] |
| Pajak, apabila berlaku | Rp [nilai / ketentuan] |
| Total pembayaran | Rp [nilai total] |

Rincian komponen biaya, apakah pajak termasuk atau di luar harga, biaya
infrastruktur, serta biaya pihak ketiga ditetapkan dalam penawaran atau
perjanjian yang disetujui. Nilai di atas tidak boleh diisi sebagai penawaran
final sebelum disepakati.

### 10.2 Tiga termin pembayaran

| Termin | Tahap | Persentase | Nominal | Acuan penagihan |
|---|---|---:|---:|---|
| 1 | **Uang muka (DP)** | [ ]% | Rp [ ] | Setelah proposal/perjanjian disetujui dan sebelum pekerjaan dimulai, sesuai ketentuan pembayaran yang disepakati. |
| 2 | **Implementasi dan penyesuaian** | [ ]% | Rp [ ] | Setelah hasil implementasi dan penyesuaian pada tahap ini diserahkan serta diperiksa atau disetujui sesuai kriteria yang disepakati. |
| 3 | **Peluncuran (launching)** | [ ]% | Rp [ ] | Setelah peluncuran dan serah terima sesuai berita acara atau kriteria yang disepakati. |
| **Total** | | **100%** | **Rp [total]** | |

Persentase dan nominal tiap termin ditentukan bersama; jumlah persentase harus
100% dan jumlah nominal harus sama dengan total pembayaran. Jatuh tempo
pembayaran, dokumen penagihan, metode pembayaran, penanganan keterlambatan,
dan ketentuan apabila pekerjaan berubah ditetapkan dalam perjanjian.

## 11. Tanggung jawab para pihak

### Penyedia / pengembang

- Menyusun rencana kerja dan melaksanakan ruang lingkup yang disetujui.
- Menyampaikan kebutuhan akses, data, dan keputusan kepada pihak perusahaan.
- Melakukan konfigurasi, penyesuaian, dan tindak lanjut temuan dalam cakupan
  yang disepakati.
- Menyediakan panduan dan melaksanakan pelatihan sesuai kesepakatan.
- Menjaga kerahasiaan data dan menggunakan akses hanya untuk pekerjaan yang
  disetujui, sesuai ketentuan perjanjian.

### Perusahaan / pengguna

- Menunjuk penanggung jawab proyek dan pengguna kunci untuk setiap bagian.
- Menyediakan akses lingkungan serta data master, saldo awal, dan informasi
  transaksi yang diperlukan secara tepat waktu.
- Memeriksa dan menyetujui bagan akun, pengaturan akun, saldo awal, dan
  kebijakan pembukuan yang diterapkan.
- Mengikuti pengujian dan pelatihan, mencatat temuan, serta memberikan
  keputusan atau persetujuan sesuai jadwal.
- Memastikan pembayaran dilakukan sesuai termin dan perjanjian.

## 12. Prasyarat dan batasan

1. **Lingkungan:** dibutuhkan server dan browser yang sesuai, PHP dan database
   MySQL/MariaDB dengan konfigurasi yang mendukung aplikasi, serta koneksi yang
   memadai. Spesifikasi, penanggung jawab, domain, HTTPS, dan biaya lingkungan
   ditetapkan saat pembahasan teknis.
2. **Database:** skema dasar database tidak disertakan dalam repository.
   Ketersediaan skema yang sesuai dan penerapan migrasi yang relevan harus
   dipastikan sebelum fitur digunakan. Pencadangan dilakukan sebelum migrasi.
3. **Keamanan:** penggunaan HTTPS, pengelolaan akses, pengaturan keamanan,
   pencadangan, dan pemulihan perlu direncanakan serta diuji sesuai lingkungan.
   Pengaturan CSRF global masih perlu disiapkan terpisah; tidak semua kontrol
   keamanan dapat dianggap aktif tanpa konfigurasi dan pemeriksaan.
4. **Data awal:** perusahaan bertanggung jawab atas kelengkapan dan kebenaran
   data sumber. Metode impor, pembersihan data, dan jumlah data yang ditangani
   harus disepakati; aplikasi tidak diasumsikan memiliki impor otomatis untuk
   semua data.
5. **Bank dan pembayaran:** rekening koran diinput manual; tidak tersedia
   koneksi bank atau verifikasi pembayaran elektronik otomatis pada alur yang
   dijelaskan dalam proposal ini.
6. **Penyesuaian:** fitur baru, integrasi pihak ketiga, laporan tambahan,
   perubahan alur, atau perubahan di luar rincian yang disetujui memerlukan
   analisis dan persetujuan terpisah.
7. **Operasional dan kepatuhan:** aplikasi membantu pencatatan berdasarkan
   data yang dimasukkan, tetapi tidak menjamin kepatuhan perpajakan atau
   standar akuntansi secara otomatis. Pemeriksaan dan keputusan tetap menjadi
   tanggung jawab perusahaan dan pihak profesional yang ditunjuk.

## 13. Pengelolaan perubahan dan dukungan

Permintaan perubahan dicatat dengan penjelasan kebutuhan, alasan, dan hasil
yang diharapkan. Penyedia meninjau dampak terhadap proses, biaya, jadwal,
data, dan pengujian. Pekerjaan tambahan dimulai setelah kedua pihak menyetujui
perubahannya.

Ketentuan dukungan setelah peluncuran harus ditetapkan dalam perjanjian,
meliputi **[jangka waktu]**, **[jam dan kanal layanan]**, **[cakupan
perbaikan]**, **[target respons]**, serta **[biaya dan ketentuan layanan
lanjutan]**. Hosting, domain, sertifikat, pencadangan operasional, pembaruan,
dan layanan pihak ketiga **[termasuk/tidak termasuk: isi sesuai kesepakatan]**.
Hak penggunaan, kepemilikan data, akses kode sumber, dan ketentuan
pengakhiran juga ditetapkan secara tertulis sebelum pekerjaan dimulai.

## 14. Penutup

Demikian proposal pengajuan Co-Balance ini disampaikan sebagai dasar
pembahasan penerapan aplikasi pada **[nama perusahaan]**. Kami berharap solusi
ini dapat membantu perusahaan menata proses operasional dan pencatatan
akuntansi secara lebih terstruktur.

Ruang lingkup akhir, nilai investasi, tiga termin pembayaran, jadwal, dan
ketentuan lain dituangkan dalam perjanjian yang disetujui kedua pihak.

## 15. Persetujuan

| Pihak penyedia / pengembang | Pihak perusahaan |
|---|---|
| Nama: [................................] | Nama: [................................] |
| Jabatan: [..............................] | Jabatan: [..............................] |
| Tanggal: [..............................] | Tanggal: [..............................] |
| Tanda tangan: __________________ | Tanda tangan: __________________ |

**Referensi penggunaan:** [Panduan Pengguna Co-Balance Versi 2](PANDUAN_PENGGUNA_V2.md)
dan [README teknis](../README.md).
