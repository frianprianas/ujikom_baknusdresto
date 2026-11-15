@extends('layouts.app')

@section('content')
<div class="flex-1 flex flex-col h-full bg-slate-100 overflow-hidden">
    <!-- Header -->
    <div class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between flex-shrink-0">
        <div>
            <h2 class="text-base font-extrabold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-box-open text-amber-500"></i>
                <span>Katalog Menu & Produk</span>
            </h2>
            <p class="text-xs text-slate-400">Kelola daftar menu makanan, minuman, dan harga jual Baknus Dresto</p>
        </div>

        <div class="flex items-center gap-2">
            <!-- Tombol Tambah Menu -->
            <button type="button" onclick="document.getElementById('modal-add-product-page').classList.remove('hidden')" class="px-4 py-2 bg-[#1b436e] hover:bg-[#153457] text-white font-bold rounded-xl text-xs transition flex items-center gap-2 shadow-sm cursor-pointer">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>+ Tambah Menu Baru</span>
            </button>

            <!-- Tombol Ke POS -->
            <a href="{{ route('pos.index') }}" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black rounded-xl text-xs transition flex items-center gap-2 shadow cursor-pointer">
                <i class="fa-solid fa-desktop"></i> Ke Layar Kasir (POS)
            </a>
        </div>
    </div>

    <!-- Products Content -->
    <div class="flex-1 p-6 overflow-y-auto">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <span class="text-xs font-bold text-slate-600">Total: {{ count($products) }} Menu Aktif</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-black text-[10px] tracking-wider">
                        <tr>
                            <th class="py-3 px-4">Kode</th>
                            <th class="py-3 px-4">Nama Menu</th>
                            <th class="py-3 px-4">Kategori</th>
                            <th class="py-3 px-4 text-right">Harga Pokok (Modal)</th>
                            <th class="py-3 px-4 text-right">Harga Jual</th>
                            <th class="py-3 px-4 text-center">Stok</th>
                            <th class="py-3 px-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                        @foreach($products as $product)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="py-3 px-4 font-mono font-bold text-slate-600">{{ $product->code }}</td>
                                <td class="py-3 px-4 font-bold text-slate-900">{{ $product->name }}</td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                                        {{ $product->category->name ?? '-' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right text-slate-500">Rp {{ number_format($product->cost_price, 0, ',', '.') }}</td>
                                <td class="py-3 px-4 text-right font-black text-slate-900">Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                                <td class="py-3 px-4 text-center font-bold text-slate-700">{{ $product->stock }}</td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        Tersedia
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL TAMBAH MENU BARU -->
    <div id="modal-add-product-page" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-200">
            <div class="bg-[#0e2a47] text-white p-5 flex items-center justify-between">
                <div>
                    <h3 class="font-extrabold text-base">Tambah Menu Baru</h3>
                    <p class="text-xs text-slate-300">Masukkan detail menu makanan/minuman</p>
                </div>
                <button type="button" onclick="document.getElementById('modal-add-product-page').classList.add('hidden')" class="text-slate-400 hover:text-white transition cursor-pointer">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form onsubmit="saveProductFromPage(event)" class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Menu</label>
                    <input type="text" id="page-item-name" required placeholder="Contoh: Nasi Goreng Spesial" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Kode Menu</label>
                        <input type="text" id="page-item-code" required placeholder="NGS-01" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Kategori</label>
                        <select id="page-item-category" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Harga Pokok / Modal (Rp)</label>
                        <input type="number" id="page-item-cost" step="500" placeholder="15000" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Harga Jual (Rp)</label>
                        <input type="number" id="page-item-price" step="500" required placeholder="28000" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none font-bold">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Jumlah Stok</label>
                        <input type="number" id="page-item-stock" value="100" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Ikon Tampilan</label>
                        <select id="page-item-icon" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            <option value="cutlery">Alat Makan (Cutlery)</option>
                            <option value="dish">Main Course</option>
                            <option value="soup">Soup</option>
                            <option value="salad">Salad</option>
                            <option value="pizza">Pizza</option>
                            <option value="pasta">Pasta</option>
                            <option value="burger">Burger</option>
                            <option value="drink">Beverage / Minuman</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="w-full py-2.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black rounded-lg text-xs shadow transition mt-2 cursor-pointer">
                    Simpan Menu Baru
                </button>
            </form>
        </div>
    </div>
</div>

<script>
async function saveProductFromPage(e) {
    e.preventDefault();
    const payload = {
        name: document.getElementById('page-item-name').value,
        code: document.getElementById('page-item-code').value,
        category_id: document.getElementById('page-item-category').value,
        cost_price: document.getElementById('page-item-cost').value || 0,
        price: document.getElementById('page-item-price').value,
        stock: document.getElementById('page-item-stock').value || 100,
        icon_type: document.getElementById('page-item-icon').value,
    };

    try {
        const res = await fetch("{{ route('products.store') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
            alert('Menu baru berhasil ditambahkan!');
            window.location.reload();
        } else {
            alert('Gagal menambah menu: ' + (data.message || 'Periksa input'));
        }
    } catch(err) {
        alert('Terjadi kesalahan koneksi.');
    }
}
</script>
@endsection
