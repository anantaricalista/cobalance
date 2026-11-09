# cobalance

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

## Saldo awal

Menu Transaksi → Saldo Awal mencatat satu pembukaan per perusahaan dengan
tanggal yang sama untuk seluruh rincian. Masukkan saldo akun umum, faktur
piutang dan utang, serta jumlah dan total nilai per produk barang. Akun di
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
faktur belum dialokasikan dan persediaan belum memiliki mutasi lanjutan.
Pembukaan yang dibatalkan tetap tersimpan sebagai riwayat; modul tidak
mengizinkan pembuatan pembukaan baru setelah pembatalan.
Lakukan pencadangan dan terapkan migrasi saldo awal yang disediakan terpisah
sebelum menggunakan modul ini. SQL dan data database tidak disimpan di
repository.