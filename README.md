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
kesalahan yang sesuai dan tidak melaporkan keberhasilan. Layanan ini belum
membuat halaman transaksi, menghitung pajak atau stok, atau menyediakan handler
posting dan pembatalan untuk tiap jenis transaksi.

Jurnal hasil posting atau pembalik dilindungi dari edit dan hapus langsung.
CRUD jurnal lama tetap tersedia sementara modul transaksi belum selesai.