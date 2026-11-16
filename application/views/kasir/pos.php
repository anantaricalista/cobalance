<style>
.pos { color:#243249; } .pos-top { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:18px; }
.pos-top h2 { margin:0; font-weight:700; color:#183153; } .pos-layout { display:grid; grid-template-columns:minmax(0, 1fr) minmax(320px, 430px); gap:18px; }
.pos-panel { background:#fff; border:1px solid #dfe7ef; border-radius:12px; padding:16px; }
.pos-search { width:100%; height:48px; font-size:16px; } .pos-products { display:grid; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:10px; margin-top:14px; }
.pos-product { text-align:left; border:1px solid #dfe7ef; background:#fff; border-radius:10px; padding:12px; min-height:115px; }
.pos-product:hover,.pos-product:focus { border-color:#287e91; box-shadow:0 3px 12px rgba(24,49,83,.12); }
.pos-product strong,.pos-product small { display:block; } .pos-product strong { color:#183153; margin-bottom:6px; overflow-wrap:anywhere; }
.pos-product small { color:#667b8d; } .pos-product .price { color:#187e68; font-weight:700; margin-top:7px; }
.pos-product img { height:60px; width:100%; object-fit:contain; margin-bottom:8px; }
.pos-cart h3 { margin:0 0 14px; } .pos-lines { max-height:330px; overflow:auto; } .pos-line { padding:10px 0; border-bottom:1px solid #e8edf2; }
.pos-line-head,.pos-line-actions,.pos-total-row { display:flex; justify-content:space-between; gap:8px; align-items:center; }
.pos-line-actions { justify-content:flex-start; margin-top:6px; } .pos-line-actions input { width:85px; text-align:center; }
.pos-line-actions button { min-width:34px; } .pos-cart label { margin-top:10px; } .pos-total-row { padding-top:10px; }
.pos-total { font-size:22px; color:#183153; font-weight:700; } .pos-pay { width:100%; margin-top:15px; padding:13px; font-size:17px; font-weight:700; }
.pos-cash-suggestions { display:flex; flex-wrap:wrap; gap:6px; margin-top:8px; }
.pos-cash-suggestions .btn[aria-pressed="true"] { border-color:#287e91; background:#e4f3f5; color:#183153; }
.pos-muted { color:#687a91; } .pos-message { margin-top:12px; } .pos-empty { padding:16px 0; color:#687a91; }
@media(max-width:900px) { .pos-layout { grid-template-columns:1fr; } }
</style>
<div class="pos">
    <div class="pos-top"><div><h2>Kasir</h2><span class="pos-muted">Pilih barang atau jasa, lalu selesaikan pembayaran.</span></div>
        <a class="btn btn-default" href="<?= site_url('kasir/beranda') ?>">Beranda</a></div>
    <div id="pos-message" class="pos-message" role="alert"></div>
    <div class="pos-layout">
        <section class="pos-panel">
            <label for="pos-search">Cari produk atau pindai kode produk</label>
            <input id="pos-search" class="form-control pos-search" type="search" maxlength="100" placeholder="Nama atau kode produk" autocomplete="off">
            <p class="pos-muted">Pindai kode produk yang sesuai dengan kode yang tersimpan, lalu tekan Enter.</p>
            <div id="pos-products" class="pos-products"></div>
            <p id="pos-more" class="pos-muted"></p>
        </section>
        <section class="pos-panel pos-cart">
            <h3>Keranjang <span id="pos-count" class="badge">0</span></h3>
            <div id="pos-lines" class="pos-lines"></div>
            <label for="pos-customer">Pelanggan</label>
            <select id="pos-customer" class="form-control" required>
                <option value="">Pilih pelanggan</option>
                <?php foreach ($customers as $customer): ?>
                    <option value="<?= (int) $customer->id_pelanggan ?>"><?= html_escape($customer->kode_pelanggan . ' - ' . $customer->nama_pelanggan) ?></option>
                <?php endforeach; ?>
            </select>
            <p class="pos-muted">Untuk pelanggan umum, pilih pelanggan umum yang telah dibuat di menu Pelanggan.</p>
            <label for="pos-tax-mode">Pajak untuk seluruh item</label>
            <select id="pos-tax-mode" class="form-control">
                <option value="tidak_dikenakan">Tidak dikenakan</option><option value="termasuk">Harga termasuk pajak</option><option value="belum_termasuk">Pajak ditambahkan</option>
            </select>
            <select id="pos-tax" class="form-control" aria-label="Pilih pajak" disabled>
                <option value="">Pilih pajak</option>
                <?php foreach ($taxes as $tax): ?>
                    <option value="<?= (int) $tax->id_pajak ?>" data-rate="<?= html_escape($tax->tarif_persen) ?>"><?= html_escape($tax->nama_pajak . ' (' . $tax->tarif_persen . '%)') ?></option>
                <?php endforeach; ?>
            </select>
            <div class="pos-total-row"><span>Pajak</span><strong id="pos-tax-total">Rp 0</strong></div>
            <div class="pos-total-row pos-total"><span>Total</span><span id="pos-total">Rp 0</span></div>
            <label for="pos-method">Metode pembayaran</label>
            <select id="pos-method" class="form-control"><option value="tunai">Tunai</option><option value="qris">QRIS</option><option value="kartu">Kartu</option><option value="transfer">Transfer</option></select>
            <label for="pos-account">Akun penerimaan kas/bank</label>
            <select id="pos-account" class="form-control">
                <option value="">Pilih akun penerimaan</option>
                <?php foreach ($accounts as $account): ?>
                    <option value="<?= (int) $account->id_akun ?>" <?= !$account->selectable ? 'disabled' : '' ?>><?= html_escape(str_repeat('— ', $account->depth) . $account->kode_akun . ' - ' . $account->nama_akun) ?></option>
                <?php endforeach; ?>
            </select>
            <div id="pos-cash-input"><label for="pos-paid">Uang diterima (Rp)</label>
                <input id="pos-paid" class="form-control" inputmode="decimal" placeholder="Contoh: 50.000" autocomplete="off">
                <div id="pos-cash-suggestions" class="pos-cash-suggestions" role="group" aria-label="Pilihan uang diterima"></div>
                <small class="pos-muted">Pilih nominal cepat atau masukkan jumlah lainnya.</small>
                <div class="pos-total-row"><span>Kembalian</span><strong id="pos-change">Rp 0</strong></div>
            </div>
            <p id="pos-noncash-note" class="pos-muted" hidden>Pastikan pembayaran sudah diterima. Aplikasi tidak memproses atau memverifikasi pembayaran elektronik.</p>
            <button type="button" class="btn btn-success pos-pay" id="pos-pay">Bayar dan selesaikan</button>
            <p class="pos-muted">Transaksi yang selesai langsung diposting, membuat jurnal dan mengurangi stok. Pembatalan dilakukan Akuntansi/Admin.</p>
        </section>
    </div>
</div>
<script>
(function () {
    'use strict';
    var config = <?= json_encode(array('productsUrl' => site_url('pos/produk'), 'payUrl' => site_url('pos/bayar'), 'imageUrl' => site_url('produk/gambar/'), 'token' => $token, 'storageKey' => $storage_key), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var $ = function (id) { return document.getElementById(id); };
    var cart = {}, products = [], timer, requestNumber = 0, pending = null, busy = false;
    var zero = BigInt(0), hundred = BigInt(100), qtyScale = BigInt(10000), rateScale = BigInt(1000000);
    function rupiah(value) {
        var integer = (value / hundred).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        var fraction = (value % hundred).toString().padStart(2, '0');
        return 'Rp ' + integer + (fraction === '00' ? '' : ',' + fraction);
    }
    function parseDecimal(value, scale, digits, localized) {
        value = String(value).trim();
        if (localized) { value = value.replace(/\./g, '').replace(',', '.'); }
        var match = value.match(new RegExp('^(\\d{1,' + digits + '})(?:\\.(\\d{1,' + scale + '}))?$'));
        if (!match) { return null; }
        return BigInt(match[1]) * (BigInt(10) ** BigInt(scale)) + BigInt((match[2] || '').padEnd(scale, '0') || '0');
    }
    function decimal(value, scale) {
        var base = BigInt(10) ** BigInt(scale);
        return (value / base).toString() + '.' + (value % base).toString().padStart(scale, '0');
    }
    function round(n, d) { return (n + d / BigInt(2)) / d; }
    function totals() {
        var total = zero, tax = zero, mode = $('pos-tax-mode').value;
        var option = $('pos-tax').selectedOptions[0];
        var rate = option && option.value ? parseDecimal(option.dataset.rate, 4, 3, false) : zero;
        if (rate === null) { return null; }
        for (var id in cart) {
            var item = cart[id], gross = round(item.qty * item.price, qtyScale), net = gross, amount = zero;
            if (mode === 'termasuk') { net = round(gross * rateScale, rateScale + rate); amount = gross - net; }
            if (mode === 'belum_termasuk') { amount = round(gross * rate, rateScale); }
            tax += amount; total += mode === 'belum_termasuk' ? gross + amount : gross;
        }
        return {total: total, tax: tax};
    }
    function message(text, type) {
        var box = $('pos-message'); box.textContent = text; box.className = text ? 'alert alert-' + (type || 'danger') : 'pos-message';
    }
    function make(tag, text, cls) { var el = document.createElement(tag); if (text !== null) el.textContent = text; if (cls) el.className = cls; return el; }
    function renderCart() {
        var lines = $('pos-lines'); lines.textContent = '';
        var ids = Object.keys(cart); $('pos-count').textContent = ids.length;
        if (!ids.length) lines.appendChild(make('p', 'Keranjang masih kosong.', 'pos-empty'));
        ids.forEach(function (id) {
            var item = cart[id], row = make('div', null, 'pos-line'), head = make('div', null, 'pos-line-head');
            head.appendChild(make('strong', item.product.nama_produk));
            head.appendChild(make('span', rupiah(round(item.qty * item.price, qtyScale))));
            row.appendChild(head);
            row.appendChild(make('small', rupiah(item.price) + ' / ' + item.product.satuan, 'pos-muted'));
            var controls = make('div', null, 'pos-line-actions');
            var minus = make('button', '−', 'btn btn-default btn-sm'), plus = make('button', '+', 'btn btn-default btn-sm');
            var input = make('input', null, 'form-control input-sm'); input.type = 'text'; input.inputMode = 'decimal'; input.value = decimal(item.qty, 4).replace(/0+$/, '').replace(/\.$/, '').replace('.', ',');
            input.setAttribute('aria-label', 'Jumlah ' + item.product.nama_produk);
            var remove = make('button', 'Hapus', 'btn btn-link btn-sm');
            minus.onclick = function () { if (item.qty <= qtyScale) delete cart[id]; else item.qty -= qtyScale; renderCart(); };
            plus.onclick = function () { item.qty += qtyScale; renderCart(); };
            input.onchange = function () {
                var qty = parseDecimal(input.value, 4, 14, true);
                if (qty === null || qty <= zero) { message('Jumlah harus lebih besar dari nol, maksimal 4 desimal.'); renderCart(); return; }
                item.qty = qty; message(''); renderCart();
            };
            remove.onclick = function () { delete cart[id]; renderCart(); };
            [minus,input,plus,remove].forEach(function (el) { el.disabled = !!pending || busy; controls.appendChild(el); });
            row.appendChild(controls); lines.appendChild(row);
        });
        updateTotal();
    }
    function renderCashSuggestions(total, paid) {
        var box = $('pos-cash-suggestions');
        var maximum = BigInt('999999999999999');
        var signature = total.toString() + ':' + (!!pending || busy);
        if (box.dataset.signature !== signature) {
            box.dataset.signature = signature;
            box.textContent = '';
            if (total > zero && total <= maximum) {
                var choices = [{value: total, label: 'Uang pas'}];
                var steps = total >= BigInt(10000) * hundred
                    ? [10000, 50000, 100000, 200000, 500000]
                    : (total >= BigInt(1000) * hundred
                        ? [1000, 5000, 10000, 20000, 50000, 100000]
                        : [100, 500, 1000, 2000, 5000, 10000]);
                var suggested = {};
                steps.forEach(function (step) {
                    var unit = BigInt(step) * hundred;
                    var value = (total / unit + BigInt(1)) * unit;
                    var key = value.toString();
                    if (choices.length < 4 && value <= maximum && !suggested[key]) {
                        suggested[key] = true;
                        choices.push({value: value});
                    }
                });
                var seen = {};
                choices.forEach(function (choice) {
                    var key = choice.value.toString();
                    if (choice.value > maximum || seen[key]) return;
                    seen[key] = true;
                    var button = make('button', (choice.label ? choice.label + ' · ' : '') + rupiah(choice.value), 'btn btn-default btn-sm');
                    button.type = 'button';
                    button.disabled = !!pending || busy;
                    button.dataset.amount = key;
                    button.onclick = function () {
                        $('pos-paid').value = rupiah(choice.value).replace(/^Rp /, '');
                        updateTotal();
                    };
                    box.appendChild(button);
                });
            }
        }
        Array.prototype.forEach.call(box.children, function (button) {
            button.setAttribute('aria-pressed', paid !== null && button.dataset.amount === paid.toString() ? 'true' : 'false');
        });
    }
    function updateTotal() {
        var amounts = totals();
        if (!amounts) { message('Tarif pajak tidak valid.'); return; }
        $('pos-total').textContent = rupiah(amounts.total); $('pos-tax-total').textContent = rupiah(amounts.tax);
        var paid = parseDecimal($('pos-paid').value, 2, 13, true);
        $('pos-change').textContent = rupiah(paid !== null && paid > amounts.total ? paid - amounts.total : zero);
        renderCashSuggestions(amounts.total, paid);
    }
    function add(product) {
        if (pending || busy) return;
        var price = parseDecimal(product.harga_jual, 2, 13, false);
        if (price === null) { message('Harga produk tidak valid.'); return; }
        if (cart[product.id_produk]) cart[product.id_produk].qty += qtyScale;
        else cart[product.id_produk] = {product: product, price: price, qty: qtyScale};
        message(''); renderCart();
    }
    function renderProducts() {
        var box = $('pos-products'); box.textContent = '';
        if (!products.length) box.appendChild(make('p', 'Produk tidak ditemukan.', 'pos-empty'));
        products.forEach(function (p) {
            var button = make('button', null, 'pos-product'); button.type = 'button'; button.disabled = !!pending || busy;
            if (Number(p.memiliki_gambar)) { var image = document.createElement('img'); image.src = config.imageUrl + encodeURIComponent(p.id_produk); image.alt = ''; button.appendChild(image); }
            button.appendChild(make('strong', p.nama_produk));
            button.appendChild(make('small', p.kode_produk + ' · ' + p.satuan));
            button.appendChild(make('small', p.tipe_produk === 'barang' ? 'Stok ' + String(p.stok).replace(/(\.\d*?)0+$/, '$1').replace(/\.$/, '').replace('.', ',') : 'Jasa'));
            var price = parseDecimal(p.harga_jual, 2, 13, false);
            button.appendChild(make('span', price === null ? 'Harga tidak valid' : rupiah(price), 'price'));
            button.onclick = function () { add(p); }; box.appendChild(button);
        });
    }
    function search(scan) {
        var query = $('pos-search').value, seq = ++requestNumber;
        fetch(config.productsUrl + '?q=' + encodeURIComponent(query), {credentials:'same-origin'}).then(function (response) {
            if (!response.ok) throw new Error('Produk gagal dimuat.');
            return response.json();
        }).then(function (result) {
            if (seq !== requestNumber) return;
            products = result.products; renderProducts();
            $('pos-more').textContent = result.more ? 'Menampilkan 60 produk pertama. Persempit pencarian.' : '';
            if (scan) {
                var exact = products.filter(function (p) { return p.kode_produk.toLowerCase() === query.trim().toLowerCase(); });
                if (exact.length === 1) { add(exact[0]); $('pos-search').value = ''; search(false); }
                else message('Kode produk tidak ditemukan atau tidak unik.');
            }
        }).catch(function () { if (seq === requestNumber) message('Produk gagal dimuat. Periksa koneksi dan coba lagi.'); });
    }
    function lock(value) {
        ['pos-customer','pos-tax-mode','pos-tax','pos-method','pos-account','pos-paid','pos-search'].forEach(function (id) { $(id).disabled = value; });
        if (!value) $('pos-tax').disabled = $('pos-tax-mode').value === 'tidak_dikenakan';
        $('pos-pay').disabled = busy;
        $('pos-pay').textContent = pending ? 'Periksa / coba ulang pembayaran' : 'Bayar dan selesaikan';
        renderCart(); renderProducts();
    }
    function send() {
        busy = true; lock(true); message('Memproses transaksi. Jangan tutup halaman.', 'info');
        var body = new URLSearchParams({pos_form_token:config.token, kunci:pending.key, data:pending.data});
        <?php if ($this->config->item('csrf_protection')): ?>
        body.append(<?= json_encode($this->security->get_csrf_token_name()) ?>, <?= json_encode($this->security->get_csrf_hash()) ?>);
        <?php endif; ?>
        fetch(config.payUrl, {method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:body.toString()})
            .then(function (response) { return response.json().then(function (result) { return {status:response.status, result:result}; }); })
            .then(function (reply) {
                busy = false;
                if (reply.result.status) { localStorage.removeItem(config.storageKey); location.href = reply.result.receipt_url; return; }
                if (reply.status === 400) { localStorage.removeItem(config.storageKey); pending = null; lock(false); message(reply.result.message); return; }
                lock(true); message((reply.result.message || 'Status transaksi belum dapat dipastikan.') + ' Jangan buat transaksi baru; coba ulang untuk memeriksa.');
            }).catch(function () {
                busy = false; lock(true); message('Koneksi terputus dan status transaksi belum pasti. Jangan buat transaksi baru; coba ulang untuk memeriksa.');
            });
    }
    $('pos-pay').onclick = function () {
        if (busy) return;
        try {
            var stored = localStorage.getItem(config.storageKey);
            if (!pending && stored) { pending = JSON.parse(stored); lock(true); message('Ada pembayaran sebelumnya yang belum dipastikan. Periksa dengan tombol coba ulang.'); return; }
        } catch (e) { message('Penyimpanan lokal tidak tersedia. Transaksi tidak dikirim.'); return; }
        if (pending) { send(); return; }
        var amounts = totals(), mode = $('pos-tax-mode').value, method = $('pos-method').value;
        if (!amounts || amounts.total <= zero || !Object.keys(cart).length) { message('Tambahkan produk dengan total lebih besar dari nol.'); return; }
        if (!$('pos-customer').value || !$('pos-account').value) { message('Pilih pelanggan dan akun penerimaan.'); return; }
        if (mode !== 'tidak_dikenakan' && !$('pos-tax').value) { message('Pilih pajak untuk transaksi.'); return; }
        var paid = method === 'tunai' ? parseDecimal($('pos-paid').value, 2, 13, true) : amounts.total;
        if (paid === null || paid < amounts.total) { message('Uang diterima belum cukup atau tidak valid.'); return; }
        if (!confirm('Pastikan pembayaran sudah diterima. Selesaikan transaksi sebesar ' + rupiah(amounts.total) + '?')) return;
        var details = Object.keys(cart).map(function (id) { return {id_produk:id, jumlah:decimal(cart[id].qty, 4), harga:decimal(cart[id].price, 2)}; });
        var data = {id_pelanggan:$('pos-customer').value, id_akun_kas:$('pos-account').value, metode:method, dibayar:decimal(paid, 2), total:decimal(amounts.total, 2),
            mode_pajak:mode, id_pajak:mode === 'tidak_dikenakan' ? '' : $('pos-tax').value, details:details};
        try {
            var bytes = new Uint8Array(16); crypto.getRandomValues(bytes);
            pending = {key:Array.prototype.map.call(bytes, function (x) { return x.toString(16).padStart(2,'0'); }).join(''), data:JSON.stringify(data)};
            localStorage.setItem(config.storageKey, JSON.stringify(pending));
        } catch (e) { pending = null; message('Penyimpanan lokal atau generator keamanan tidak tersedia. Transaksi tidak dikirim.'); return; }
        send();
    };
    $('pos-tax-mode').onchange = function () { $('pos-tax').disabled = this.value === 'tidak_dikenakan'; updateTotal(); };
    $('pos-tax').onchange = updateTotal;
    $('pos-paid').oninput = updateTotal;
    $('pos-method').onchange = function () { var cash = this.value === 'tunai'; $('pos-cash-input').hidden = !cash; $('pos-noncash-note').hidden = cash; };
    $('pos-search').oninput = function () { clearTimeout(timer); timer = setTimeout(function () { search(false); }, 200); };
    $('pos-search').onkeydown = function (event) { if (event.key === 'Enter') { event.preventDefault(); clearTimeout(timer); search(true); } };
    try { pending = JSON.parse(localStorage.getItem(config.storageKey) || 'null'); } catch (e) { message('Penyimpanan lokal tidak tersedia.'); }
    if (pending) { lock(true); message('Ada pembayaran sebelumnya yang belum dipastikan. Periksa dengan tombol coba ulang sebelum transaksi baru.'); }
    else { renderCart(); $('pos-search').focus(); }
    search(false);
})();
</script>
