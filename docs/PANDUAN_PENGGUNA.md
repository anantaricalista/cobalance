# Panduan Pengguna Co-Balance

Panduan singkat ini menjelaskan alur dari login pertama sampai membaca laporan
akuntansi. Nama menu mengikuti navigasi aplikasi.

Tanggal pada tabel dan rincian ditampilkan dengan singkatan nama bulan Indonesia,
misalnya **11 Nov 2026**; tanggal pada laporan PDF menggunakan nama bulan lengkap.
Kolom input tanggal mengikuti format browser, sementara nilai yang dikirim tetap
menggunakan format tahun-bulan-hari.

## 1. Login

1. Buka alamat Co-Balance dan masuk menggunakan username serta kata sandi yang
   diberikan administrator.
2. Setelah berhasil, periksa nama pengguna dan perusahaan pada Dashboard.
3. Jika belum memiliki akun atau tidak dapat login, hubungi administrator
   perusahaan. Halaman aplikasi menyediakan login, bukan pendaftaran mandiri.

## 2. Siapkan data perusahaan

Sebelum mencatat transaksi, buka menu **Master** dan lengkapi data berikut:

1. **Akun** — periksa Bagan Akun. Tambahkan akun yang diperlukan dengan tipe dan
   akun induk yang sesuai. Transaksi memakai akun rincian, bukan akun induk.
2. **Pengguna** — kelola akun yang dapat masuk ke perusahaan ini dan pilih role
   **Admin Perusahaan**, **User**, **Kasir**, **Penjualan**, **Purchasing**,
   **Gudang**, **Keuangan**, **Akuntansi**, atau **Manajer / Pemilik**. Role
   Gudang dan Purchasing memiliki tampilan serta pembatasan akses khusus;
   role lainnya belum dibatasi pada tingkat modul. Hanya Admin Perusahaan dan
   Akuntansi yang dapat mem-posting atau membatalkan transaksi. Perusahaan hanya
   memiliki satu admin;
   memilih Admin untuk pengguna lain akan memindahkan role admin saat ini menjadi
   User. Password baru minimal 8 karakter; password saat mengedit boleh dikosongkan
   agar tidak berubah.
3. **Pelanggan** dan **Pemasok** — masukkan pihak yang akan muncul pada penjualan,
   pembelian, penerimaan piutang, atau pembayaran utang.
4. **Produk dan Jasa** — buat item barang atau jasa, satuan, harga jual, dan akun
   terkait. Barang memerlukan akun persediaan dan beban pokok; jasa memerlukan
   akun pembelian/beban. Akun pendapatan dapat diatur per item. Gambar opsional
   dapat ditambah, diganti, atau dihapus dari formulir produk; gunakan JPG, PNG,
   atau WebP maksimal 2 MB dan 4096 x 4096 piksel.
   Untuk jasa, akun persediaan dan beban pokok tidak dapat dipilih. Akun lama
   yang sudah tersimpan pada jasa tetap dipertahankan, tetapi tidak digunakan.
   Jika barang yang belum digunakan diubah menjadi jasa, kedua akun tersebut
   dihapus dari produk saat perubahan disimpan.
5. **Pajak** — masukkan kode, nama, tarif, serta akun pajak masukan dan/atau
   keluaran jika digunakan.
6. **Pengaturan Akun** — hubungkan jenis pengaturan ke akun rincian yang sesuai,
   misalnya Kas dan Bank, Piutang Usaha, Utang Usaha, Persediaan, Pajak,
   Beban Pokok Penjualan, Pendapatan Default, dan Modal.

Pastikan semua pilihan akun benar dan aktif sebelum transaksi diposting. Jika
pilihan akun belum tersedia, periksa kembali akun dan pengaturannya.
Pada pilihan akun, buka daftar lalu ketik kode atau nama pada kolom pencarian
di dalamnya. Pilih akun yang sesuai; kosongkan pencarian untuk menampilkan semua pilihan.
Akun induk yang tidak boleh dipakai tetap tidak dapat dipilih.

Pengguna Purchasing dapat membuka menu **Pemasok** untuk menambah dan mengedit
pemasok, termasuk mengubah statusnya menjadi aktif atau tidak aktif. Penghapusan
permanen dilakukan oleh pengguna perusahaan lain yang berwenang; pemasok yang
sudah digunakan dalam transaksi tidak dapat dihapus.

Pada Master Produk, isi **Batas Minimum Stok** untuk setiap barang. Beranda
Gudang memberi notifikasi untuk stok positif yang sama dengan atau di bawah
batas tersebut; nilai 0 menonaktifkan peringatan hampir habis. Stok 0 tetap
ditandai habis. Role Gudang dapat melihat stok dan kartu mutasi, serta mengirim
usulan hitung fisik. Usulan tidak mengubah stok atau jurnal sampai Akuntansi/Admin
memprosesnya melalui **Transaksi → Opname & Penyesuaian**. Di halaman yang sama,
Akuntansi/Admin dapat meninjau usulan, membuat draft penyesuaian dengan jumlah
fisik terisi otomatis, lalu mem-postingnya. Status usulan baru menjadi selesai
setelah posting berhasil; pembatalan penyesuaian terkait mengembalikannya menjadi
menunggu. Jumlah di modul persediaan menggunakan koma desimal dan ditampilkan
tanpa nol desimal yang tidak diperlukan.

Pengguna Purchasing juga dapat membuka menu **Produk** untuk menambah dan mengedit
produk bertipe **Barang** (termasuk gambar). Tipe Jasa tidak tersedia bagi Purchasing,
baik di Master Produk maupun di Pembelian; pembelian jasa dilakukan oleh Akuntansi atau
Admin Perusahaan. Penghapusan produk dilakukan oleh pengguna yang berwenang.

Beranda Purchasing menampilkan draft, pembelian barang yang diposting bulan ini,
utang pembelian yang belum lunas dan yang lewat jatuh tempo, serta daftar tagihan
terbuka terdekat. Utang mencakup pembelian kredit perusahaan yang hanya berisi
barang, sudah diposting dan memiliki jurnal posting, tanpa membedakan pembuat
transaksi. Sisa utang dihitung setelah pembayaran dan retur yang diposting;
saldo awal utang dan utang dari transaksi lain tidak termasuk.

Jumlah draft dan pembelian bulan ini mencakup pembelian yang hanya berisi barang
dan dibuat oleh pengguna yang saat ini memiliki role Purchasing. Riwayat role saat
transaksi dibuat tidak disimpan, sehingga perubahan role atau penghapusan akun
pembuat dapat mengubah jumlahnya.

## 3. Masukkan saldo awal (perusahaan yang sudah memiliki saldo)

Lakukan langkah ini sebelum mencatat transaksi rutin. Menu **Transaksi → Saldo
Awal** hanya mendukung satu transaksi pembukaan untuk perusahaan.

1. Tentukan satu tanggal pembukaan untuk semua saldo.
2. Masukkan saldo akun umum pada bagian **Saldo Akun**. Jangan masukkan akun
   kontrol Piutang Usaha, Utang Usaha, atau Persediaan di sini.
3. Masukkan setiap faktur yang belum lunas pada bagian **Piutang per faktur** atau
   **Utang per faktur**, berikut pihak, nomor faktur, tanggal jatuh tempo, dan
   jumlah yang masih tersisa.
4. Masukkan jumlah dan total nilai per produk barang pada bagian **Persediaan per
   produk**.
5. Masukkan aset tetap pada bagian **Aset Tetap**. Nilai perolehan dan akumulasi
   penyusutan awal akan dijurnal otomatis. Jangan mencatat nilai yang sama lagi
   pada Saldo Akun.
6. Pilih akun ekuitas penyeimbang. Pratinjau pada halaman membantu memeriksa
   debit, kredit, dan selisih sebelum disimpan.
7. Simpan draft, periksa kembali rincian, lalu pilih **Posting saldo awal**.

Posting saldo awal hanya dapat dilakukan jika jurnal perusahaan masih kosong.
Jika perusahaan benar-benar baru dan belum memiliki saldo yang perlu dimasukkan,
Anda dapat melewati tahap ini. Draft dapat diperbaiki sebelum diposting. Setelah
saldo awal diposting, hanya Admin Perusahaan yang dapat mengoreksinya melalui
pratinjau dampak dan konfirmasi alasan. Koreksi memperbarui jurnal saldo awal
asli pada tanggal pembukaan dan menyimpan nilai sebelum/sesudah, dampak, alasan,
pengguna, serta waktu ke riwayat audit. Admin harus memastikan sendiri bahwa
periode terkait terbuka; aplikasi belum memiliki penguncian periode.

Koreksi bukan penghitungan ulang penuh atas semua transaksi setelah tanggal
pembukaan. Mutasi stok dan saldo berjalan diperbarui, tetapi jurnal HPP
transaksi berikutnya tidak dihitung ulang. Jadwal dan jurnal penyusutan akan
dihitung ulang; aset yang sudah dilepas tidak dapat dikoreksi melalui alur ini.
Karena itu laporan historis dapat berubah atau menjadi tidak konsisten dengan
aktivitas lanjutan. Pratinjau menampilkan kategori dan jumlah catatan terdampak;
tinjau jurnal, buku besar, stok, tagihan, serta aset setelah koreksi. Sistem
menolak penghapusan tagihan yang sudah dipakai, nominal faktur di bawah
pembayaran/retur, penghapusan aset yang sudah digunakan, dan perubahan stok
yang membuat saldo lanjutan tidak valid.

## 4. Catat dan posting transaksi

Gunakan modul pada menu **Transaksi** sesuai kegiatan:

| Kegiatan | Menu |
|---|---|
| Menjual barang atau jasa | Penjualan |
| Mencatat biaya baru yang berkaitan dengan jasa terjual | Beban Penjualan Jasa |
| Menerima pelunasan tagihan pelanggan | Penerimaan Piutang |
| Membeli barang atau jasa | Pembelian |
| Membayar tagihan pemasok | Pembayaran Utang |
| Mencatat penerimaan, pengeluaran, atau transfer kas | Kas dan Bank |
| Mencatat setoran modal atau prive | Modal dan Prive |
| Mencatat perolehan, penyusutan, atau pelepasan | Aset Tetap |
| Memeriksa dan menyesuaikan jumlah/nilai stok | Transaksi → Opname & Penyesuaian |
| Mencatat retur pembelian atau penjualan | Retur Transaksi |
| Membuat jurnal penyesuaian manual | Penyesuaian Akuntansi |
| Memindahkan Laba (Rugi) Berjalan tahun lalu ke Laba Ditahan | Penyesuaian Akuntansi → Pindahkan Saldo Laba (Rugi) Berjalan (jalankan `20261114_pemindahan_laba.sql` dahulu) |

Untuk **Beban Penjualan Jasa**, cadangkan database dan jalankan
`application/sql/20261115_beban_penjualan_jasa.sql` terlebih dahulu. Pilih rincian
jasa pada penjualan terposting, masukkan biaya baru dan akun beban, lalu simpan
draft dan posting. Jangan masukkan kembali biaya yang sudah dicatat di modul
lain. Pembayaran tunai memakai akun kas/bank; kredit memerlukan pemasok, jatuh
tempo, dan pengaturan Utang Usaha, serta dilunasi melalui Pembayaran Utang.
Biaya dicatat tanpa rincian pajak. Ringkasan memperhitungkan retur dan beban yang
terposting, tetapi bukan laporan laba bersih. Batalkan pembayaran terposting
sebelum membatalkan beban; batalkan beban terposting sebelum membatalkan penjualan.

Alur transaksi umumnya:

1. Pilih **Transaksi baru**, isi tanggal, pihak/akun, rincian, dan keterangan yang
   diperlukan.
2. Periksa jumlah, pajak, akun, dan total. Nominal uang menggunakan format
   Indonesia: titik untuk ribuan dan koma untuk desimal, misalnya `1.000.000,50`.
   Untuk Rp 12,50 masukkan `12,50`, bukan `12.50`; `1.000` berarti seribu,
   bukan satu. Pemisah ribuan ditambahkan otomatis saat mengetik; gunakan koma
   untuk desimal. Nilai yang ditempel harus menggunakan format Indonesia dan
   maksimal dua angka desimal.
   Kolom jumlah barang dan tarif pajak pada transaksi umum menggunakan titik
   sebagai pemisah desimal. Untuk role Gudang, jumlah stok dan opname memakai
   koma sebagai pemisah desimal dan mendukung maksimal empat angka desimal.
   Gunakan browser modern yang mendukung JavaScript `BigInt` untuk perhitungan
   nominal yang akurat.
3. Tambahkan lampiran bukti bila perlu (PDF, JPG, atau PNG, maksimal 5 MB).
4. Simpan sebagai **draft**. Buka draft dari riwayat untuk memeriksa atau
   memperbaikinya sebelum diposting.
5. Pilih **Posting** setelah data benar. Aplikasi membuat jurnal secara otomatis
   untuk transaksi yang mendukung posting. Hanya Admin Perusahaan dan Akuntansi
   yang dapat melakukan posting atau pembatalan; role lain menyimpan draft untuk
   ditinjau dan diposting oleh salah satu role tersebut.

Penjualan kredit dan pembelian kredit membuat tagihan. Catat penerimaan piutang
atau pembayaran utang melalui modul alokasi yang sesuai; pilih tagihan dan
masukkan jumlah yang dibayar/diterima, tidak melebihi sisanya. Retur kredit juga
mengurangi sisa tagihan.

Transaksi yang sudah diposting tidak diedit seperti draft. Jika pembatalan
tersedia, aplikasi membuat jurnal pembalik; pembatalan dapat ditolak jika transaksi
telah dipakai atau memengaruhi transaksi berikutnya. Periksa tanggal, alasan, dan
peringatan di layar sebelum melanjutkan.

## 5. Periksa jurnal

1. Buka **Jurnal Transaksi** untuk melihat jurnal beserta nomor dan jenis transaksi,
   total debit, serta pelaku jurnal. Pilih **Detail** atau **PDF** untuk melihat
   pihak, status, jenis dan deskripsi jurnal, pembuat draft, pengguna dan waktu posting, serta
   informasi pembatalan jika ada. Jurnal pembalik menampilkan pembatal sebagai
   pelakunya; jurnal lama tanpa informasi transaksi ditandai **Tidak tercatat**.
   Nama dan username mengikuti data pengguna saat ini, bukan riwayat nama saat posting.
2. Gunakan **Jurnal Umum** untuk melihat jurnal dan rincian akun debit/kredit.
3. Pastikan total debit dan kredit seimbang. Jurnal dari posting dan pembatalan
   otomatis tidak boleh diedit langsung; koreksi dilakukan melalui alur transaksi
   yang sesuai.

## 6. Lihat laporan

Buka menu **Laporan** setelah transaksi diposting:

- **Laporan Akuntansi (Trial Balance)** merangkum debit, kredit, dan saldo akun.
  Gunakan untuk memeriksa keseimbangan pembukuan; tersedia ekspor PDF.
- **Laba Rugi** menampilkan pendapatan, beban, dan laba/rugi bersih untuk rentang
  tanggal. Pilih tanggal mulai dan selesai, lalu tekan **Tampilkan**. Periode bulan
  berjalan dimuat sebagai nilai awal; tersedia ekspor PDF.
- **Neraca** menampilkan aset, liabilitas, dan ekuitas pada tanggal yang dipilih.
  Atur **Tanggal Neraca**, lalu tekan **Tampilkan**; tersedia ekspor PDF. Saldo
  kredit pada liabilitas dan ekuitas ditampilkan positif, sedangkan saldo debit
  ditampilkan negatif. Aset menggunakan tanda sebaliknya.
- **Buku Besar** menampilkan saldo awal periode dan mutasi akun. Pilih akun serta
  tanggal mulai dan selesai, lalu tekan **Tampilkan**. Akun wajib dipilih sebelum
  melihat atau mengekspor laporan.

Untuk memperoleh laporan yang benar, pastikan transaksi terkait sudah diposting
dan filter tanggal sesuai periode yang ingin diperiksa. Simpan atau ekspor PDF
setelah meninjau hasilnya.

## Catatan penting

- Draft belum masuk ke laporan sampai diposting.
- Stok barang, saldo tagihan, serta nilai aset dapat berubah karena transaksi
  lanjutan; ikuti modul khususnya, bukan jurnal manual.
- Pemasangan atau pembaruan aplikasi dapat memerlukan perubahan skema database.
  Minta administrator menyiapkan database dan migrasi yang tercantum di README
  sebelum fitur terkait digunakan.
- Sebelum memakai koreksi saldo awal, jalankan migrasi
  `application/sql/20261113_koreksi_saldo_awal.sql`.
