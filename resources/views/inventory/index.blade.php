@extends('layouts.app')

@section('content')
<div class="flex-1 flex flex-col h-full bg-slate-100 overflow-hidden">
    <!-- Header -->
    <div class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between flex-shrink-0">
        <div>
            <h2 class="text-base font-extrabold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-warehouse text-amber-500"></i>
                <span>Manajemen Inventori & Kontrol Stok</span>
            </h2>
            <p class="text-xs text-slate-400">Monitoring sisa stok bahan, pencatatan kulakan/restock, dan nilai aset persediaan</p>
        </div>

        <div class="flex items-center gap-2">
            <!-- Tombol Tambah Stok / Restock -->
            <button type="button" onclick="openRestockModal()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 text-white font-bold rounded-xl text-xs transition flex items-center gap-2 shadow cursor-pointer">
                <i class="fa-solid fa-cart-flatbed"></i>
                <span>+ Restock / Tambah Stok</span>
            </button>

            <!-- Tombol Penyesuaian Stok Opname -->
            <button type="button" onclick="openAdjustModal()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl text-xs transition flex items-center gap-2 shadow-sm cursor-pointer">
                <i class="fa-solid fa-sliders"></i>
                <span>Stok Opname</span>
            </button>
        </div>
    </div>

    <!-- Content Area (Scrollable) -->
    <div class="flex-1 p-6 overflow-y-auto space-y-6">
        
        <!-- 4 Metrics Financial & Stock Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <!-- Total Nilai Aset Stok -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Nilai Aset Persediaan</span>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">Rp {{ number_format($totalStockValue, 0, ',', '.') }}</h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">Total modal barang di dapur</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-500 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
            </div>

            <!-- Total Belanja Restock Bulan Ini -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Restock Bulan Ini</span>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">Rp {{ number_format($monthlyRestockCost, 0, ',', '.') }}</h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">Total pengeluaran kulakan</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-truck-ramp-box"></i>
                </div>
            </div>

            <!-- Total Unit Stok -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Kuantitas Stok</span>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">{{ number_format($totalUnits, 0, ',', '.') }} Porsi</h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">Dari {{ count($products) }} jenis menu</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-500 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-layer-group"></i>
                </div>
            </div>

            <!-- Stok Kritis / Menipis -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Perlu Restock</span>
                    <h3 class="text-2xl font-black {{ ($lowStockCount + $outOfStockCount) > 0 ? 'text-rose-600' : 'text-slate-900' }} mt-1">
                        {{ $lowStockCount + $outOfStockCount }} Menu
                    </h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">{{ $outOfStockCount }} habis, {{ $lowStockCount }} menipis</p>
                </div>
                <div class="w-12 h-12 rounded-xl {{ ($lowStockCount + $outOfStockCount) > 0 ? 'bg-rose-50 text-rose-500' : 'bg-slate-50 text-slate-400' }} flex items-center justify-center text-xl">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
            </div>
        </div>

        <!-- Tabel Stok Per Menu -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <h3 class="text-sm font-extrabold text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-list-check text-slate-500"></i>
                    <span>Daftar Stok Produk</span>
                </h3>
                <span class="text-xs text-slate-500">Klik "Tambah Stok" pada baris menu untuk kulakan instan</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-black text-[10px] tracking-wider">
                        <tr>
                            <th class="py-3 px-4">Kode</th>
                            <th class="py-3 px-4">Nama Menu</th>
                            <th class="py-3 px-4">Kategori</th>
                            <th class="py-3 px-4 text-right">Harga Modal</th>
                            <th class="py-3 px-4 text-right">Harga Jual</th>
                            <th class="py-3 px-4 text-center">Sisa Stok</th>
                            <th class="py-3 px-4 text-right">Nilai Aset Stok</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-center">Aksi Cepat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                        @foreach($products as $p)
                            <tr class="hover:bg-slate-50 transition {{ $p->stock <= 0 ? 'bg-rose-50/20' : ($p->stock <= 10 ? 'bg-amber-50/20' : '') }}">
                                <td class="py-3.5 px-4 font-mono font-bold text-slate-600">{{ $p->code }}</td>
                                <td class="py-3.5 px-4 font-extrabold text-slate-900">{{ $p->name }}</td>
                                <td class="py-3.5 px-4">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                                        {{ $p->category->name ?? '-' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right text-slate-500">Rp {{ number_format($p->cost_price, 0, ',', '.') }}</td>
                                <td class="py-3.5 px-4 text-right font-bold text-slate-800">Rp {{ number_format($p->price, 0, ',', '.') }}</td>
                                <td class="py-3.5 px-4 text-center font-black text-sm {{ $p->stock <= 0 ? 'text-rose-600' : ($p->stock <= 10 ? 'text-amber-600' : 'text-slate-900') }}">
                                    {{ $p->stock }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-extrabold text-slate-900">
                                    Rp {{ number_format($p->stock * $p->cost_price, 0, ',', '.') }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    @if($p->stock <= 0)
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-700">
                                            Habis (0)
                                        </span>
                                    @elseif($p->stock <= 10)
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800">
                                            Menipis
                                        </span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                            Aman
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <button type="button" onclick="quickRestock({{ $p->id }}, '{{ addslashes($p->name) }}', {{ $p->cost_price }})" class="px-3 py-1 bg-emerald-50 hover:bg-emerald-600 text-emerald-700 hover:text-white border border-emerald-200 rounded-lg text-xs font-bold transition inline-flex items-center gap-1 shadow-sm cursor-pointer">
                                        <i class="fa-solid fa-plus text-[10px]"></i> Tambah Stok
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tabel Log Mutasi Stok (Arus Masuk & Keluar) -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                <h3 class="text-sm font-extrabold text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-slate-500"></i>
                    <span>Riwayat Arus Mutasi Stok (Stock Audit Trail)</span>
                </h3>
                <span class="text-xs text-slate-400">Setiap penjualan kasir & restock tercatat otomatis</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-black text-[10px] tracking-wider">
                        <tr>
                            <th class="py-3 px-4">Waktu</th>
                            <th class="py-3 px-4">Menu Makanan</th>
                            <th class="py-3 px-4 text-center">Tipe Mutasi</th>
                            <th class="py-3 px-4 text-center">Perubahan</th>
                            <th class="py-3 px-4 text-center">Stok Sebelum &rarr; Sesudah</th>
                            <th class="py-3 px-4 text-right">Nilai Biaya (Modal)</th>
                            <th class="py-3 px-4">No. Ref / Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                        @forelse($recentMovements as $mov)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="py-3 px-4 text-slate-500 text-[11px]">{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                                <td class="py-3 px-4 font-bold text-slate-900">{{ $mov->product->name ?? '-' }}</td>
                                <td class="py-3 px-4 text-center">
                                    @if($mov->type === 'in')
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-emerald-100 text-emerald-800">
                                            RESTOCK MASUK
                                        </span>
                                    @elseif($mov->type === 'out')
                                        @if(str_contains(strtolower($mov->notes ?? ''), 'mobile') || str_starts_with($mov->reference_no ?? '', 'MOB') || is_null($mov->user_id))
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-purple-100 text-purple-800 border border-purple-200">
                                                <i class="fa-solid fa-mobile-screen mr-1"></i> MOBILE APPS
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-rose-100 text-rose-800">
                                                PENJUALAN KASIR
                                            </span>
                                        @endif
                                    @else
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-sky-100 text-sky-800">
                                            PENYESUAIAN
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center font-black {{ $mov->quantity > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $mov->quantity > 0 ? '+' : '' }}{{ $mov->quantity }}
                                </td>
                                <td class="py-3 px-4 text-center text-slate-600 font-mono">
                                    {{ $mov->stock_before }} &rarr; <strong>{{ $mov->stock_after }}</strong>
                                </td>
                                <td class="py-3 px-4 text-right font-extrabold text-slate-900">
                                    Rp {{ number_format($mov->total_cost, 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-4 text-slate-500 text-[11px]">
                                    <strong class="text-slate-800 block">{{ $mov->reference_no ?? '-' }}</strong>
                                    {{ $mov->notes }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-10 text-center text-slate-400">
                                    Belum ada catatan mutasi stok
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-200">
                {{ $recentMovements->links() }}
            </div>
        </div>
    </div>

    <!-- MODAL RESTOCK / TAMBAH STOK -->
    <div id="modal-restock" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-200">
            <div class="bg-[#0e2a47] text-white p-5 flex items-center justify-between">
                <div>
                    <h3 class="font-extrabold text-base">Restock / Penambahan Stok</h3>
                    <p class="text-xs text-slate-300">Catat pembelian barang & modal untuk kontrol keuangan</p>
                </div>
                <button type="button" onclick="closeRestockModal()" class="text-slate-400 hover:text-white transition cursor-pointer">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form onsubmit="submitRestock(event)" class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Pilih Menu / Produk</label>
                    <select id="restock-product-id" onchange="onRestockProductChange(this.value)" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" data-cost="{{ $p->cost_price }}">{{ $p->name }} (Sisa Stok: {{ $p->stock }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Jumlah Masuk (Porsi)</label>
                        <input type="number" id="restock-qty" min="1" value="20" oninput="calcRestockTotal()" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none font-bold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Harga Beli Satuan (Rp)</label>
                        <input type="number" id="restock-cost" min="0" step="500" value="{{ $products->first()?->cost_price ?? 0 }}" oninput="calcRestockTotal()" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none font-bold">
                    </div>
                </div>

                <!-- Total Pengeluaran Modal Restock -->
                <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center justify-between text-xs">
                    <span class="font-bold text-emerald-800">Total Pengeluaran Kas (Modal):</span>
                    <span class="text-base font-black text-emerald-950" id="restock-total-cost-label">Rp 0</span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">No. Faktur / Nota Pembelian (Opsional)</label>
                    <input type="text" id="restock-ref" placeholder="Contoh: INV-SUPPLIER-098" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Tambahan</label>
                    <input type="text" id="restock-notes" placeholder="Kulakan pasar pagi, supplier langganan..." class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
                </div>

                <button type="submit" id="btn-save-restock" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 text-white font-extrabold rounded-lg text-xs shadow transition mt-2 cursor-pointer">
                    Konfirmasi Penambahan Stok
                </button>
            </form>
        </div>
    </div>


    <!-- MODAL PENYESUAIAN STOK OPNAME -->
    <div id="modal-adjust" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-200">
            <div class="bg-[#0e2a47] text-white p-5 flex items-center justify-between">
                <div>
                    <h3 class="font-extrabold text-base">Penyesuaian Stok Opname</h3>
                    <p class="text-xs text-slate-300">Koreksi fisik stok barang (rusak/basi/selisih hitung)</p>
                </div>
                <button type="button" onclick="closeAdjustModal()" class="text-slate-400 hover:text-white transition cursor-pointer">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form onsubmit="submitAdjust(event)" class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Pilih Menu</label>
                    <select id="adjust-product-id" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        @foreach($products as $p)
                            <option value="{{ $p->id }}">{{ $p->name }} (Stok Sistem: {{ $p->stock }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Jumlah Fisik Sebenarnya (Stok Baru)</label>
                    <input type="number" id="adjust-new-stock" min="0" required placeholder="0" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none font-bold">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Alasan Penyesuaian</label>
                    <input type="text" id="adjust-notes" required placeholder="Contoh: 2 porsi basi, selisih hitung dapur..." class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
                </div>

                <button type="submit" class="w-full py-2.5 bg-slate-800 hover:bg-slate-700 text-white font-extrabold rounded-lg text-xs shadow transition mt-2 cursor-pointer">
                    Simpan Penyesuaian Stok
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function openRestockModal() {
    document.getElementById('modal-restock').classList.remove('hidden');
    calcRestockTotal();
}

function closeRestockModal() {
    document.getElementById('modal-restock').classList.add('hidden');
}

function openAdjustModal() {
    document.getElementById('modal-adjust').classList.remove('hidden');
}

function closeAdjustModal() {
    document.getElementById('modal-adjust').classList.add('hidden');
}

function quickRestock(prodId, name, costPrice) {
    document.getElementById('restock-product-id').value = prodId;
    document.getElementById('restock-cost').value = costPrice;
    openRestockModal();
}

function onRestockProductChange(val) {
    const sel = document.getElementById('restock-product-id');
    const opt = sel.options[sel.selectedIndex];
    const cost = opt.dataset.cost || 0;
    document.getElementById('restock-cost').value = cost;
    calcRestockTotal();
}

function calcRestockTotal() {
    const qty = parseFloat(document.getElementById('restock-qty').value) || 0;
    const cost = parseFloat(document.getElementById('restock-cost').value) || 0;
    const total = qty * cost;
    document.getElementById('restock-total-cost-label').innerText = window.formatRupiah(total);
}

async function submitRestock(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-save-restock');
    btn.disabled = true;
    btn.innerText = 'Menyimpan...';

    const payload = {
        product_id: document.getElementById('restock-product-id').value,
        quantity: document.getElementById('restock-qty').value,
        cost_price: document.getElementById('restock-cost').value,
        reference_no: document.getElementById('restock-ref').value || null,
        notes: document.getElementById('restock-notes').value || null,
    };

    try {
        const res = await fetch("{{ route('inventory.restock') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Gagal menambah stok: ' + (data.message || 'Cek form'));
        }
    } catch (err) {
        alert('Terjadi kesalahan koneksi.');
    } finally {
        btn.disabled = false;
        btn.innerText = 'Konfirmasi Penambahan Stok';
    }
}

async function submitAdjust(e) {
    e.preventDefault();
    const payload = {
        product_id: document.getElementById('adjust-product-id').value,
        new_stock: document.getElementById('adjust-new-stock').value,
        notes: document.getElementById('adjust-notes').value,
    };

    try {
        const res = await fetch("{{ route('inventory.adjust') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Gagal menyesuaikan stok.');
        }
    } catch(e) {
        alert('Terjadi kesalahan koneksi.');
    }
}
</script>
@endsection
