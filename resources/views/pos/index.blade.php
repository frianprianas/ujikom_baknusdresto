@extends('layouts.app')

@section('content')
<div class="flex-1 flex w-full h-full overflow-hidden">

    <!-- ========================================== -->
    <!-- CENTER: PRODUCT CATALOG & CATEGORIES       -->
    <!-- ========================================== -->
    <div class="flex-1 flex flex-col h-full bg-[#f8fafc] border-r border-slate-200 overflow-hidden relative">
        
        @if($currentUser->role === 'manager')
            <!-- Warning Banner Manajer -->
            <div class="bg-indigo-50 border-b border-indigo-200 px-5 py-2.5 flex items-center justify-between text-xs text-indigo-900 flex-shrink-0">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-shield-halved text-indigo-600 text-sm"></i>
                    <span><strong>Mode Pemantau Manajer:</strong> Anda masuk sebagai Manajer. Halaman ini hanya dalam mode tinjau dan tidak dapat membuat/memproses transaksi kasir.</span>
                </div>
                <a href="{{ route('dashboard.index') }}" class="font-bold underline text-indigo-700 hover:text-indigo-900">
                    Ke Dashboard & Analitik &rarr;
                </a>
            </div>
        @endif

        <!-- Action Header Bar -->
        <div class="p-4 bg-white border-b border-slate-200 flex flex-wrap items-center justify-between gap-3 flex-shrink-0">
            <div class="flex items-center gap-2">
                <!-- + Add New Item Button -->
                <button type="button" 
                        onclick="window.pos.openAddModal()" 
                        class="bg-[#1b436e] hover:bg-[#153457] active:bg-[#0e2a47] text-white px-4 py-2 rounded-lg font-bold text-xs flex items-center gap-2 transition shadow-sm cursor-pointer">
                    <i class="fa-solid fa-plus text-xs"></i>
                    <span>+ ADD NEW ITEM</span>
                </button>

                <!-- Held Button -->
                <button type="button" 
                        onclick="window.pos.openHeldModal()" 
                        class="bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 px-3.5 py-2 rounded-lg font-bold text-xs flex items-center gap-2 transition shadow-sm cursor-pointer">
                    <i class="fa-solid fa-pause text-amber-600"></i>
                    <span>Held:</span>
                    <span class="bg-amber-500 text-slate-950 px-2 py-0.5 rounded-full text-[11px] font-black" id="held-badge-count">
                        {{ count($heldOrders) }}
                    </span>
                </button>
            </div>

            <!-- Search Bar -->
            <div class="relative flex-1 max-w-md">
                <div class="relative flex items-center">
                    <input type="text" 
                           id="pos-search-input"
                           oninput="window.pos.onSearch(this.value)" 
                           placeholder="Search items, code or barcode..." 
                           class="w-full bg-slate-50 border border-slate-300 rounded-lg pl-10 pr-12 py-2 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-amber-500 focus:bg-white transition placeholder-slate-400">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 text-slate-400 text-xs"></i>
                    <button type="button" class="absolute right-1 top-1 bottom-1 px-3 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold rounded-md flex items-center justify-center transition">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Subheader: Last refreshed -->
        <div class="px-5 py-2 bg-slate-50/70 border-b border-slate-200/60 flex items-center justify-between text-[11px] text-slate-500 flex-shrink-0">
            <div class="flex items-center gap-2">
                <span>Last refreshed: <strong class="text-slate-700" id="last-refresh-label">2026-11-14 21:55</strong></span>
                <button type="button" onclick="window.location.reload()" class="text-sky-600 hover:text-sky-800 font-semibold flex items-center gap-1 transition">
                    <i class="fa-solid fa-rotate-right text-[10px]"></i> Refresh
                </button>
            </div>
            <div class="text-[11px] text-slate-400">
                Menampilkan <span class="font-bold text-slate-700" id="visible-products-count">{{ count($products) }}</span> menu
            </div>
        </div>

        <!-- Product Cards Grid (Tengah) -->
        <div class="flex-1 p-5 overflow-y-auto">
            <div id="product-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                @foreach($products as $product)
                    <div onclick="window.pos.addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $product->price }}, {{ $product->stock }})"
                         data-category="{{ $product->category_id }}"
                         data-name="{{ strtolower($product->name) }}"
                         data-code="{{ strtolower($product->code) }}"
                         data-barcode="{{ strtolower($product->barcode ?? '') }}"
                         class="product-card group bg-white rounded-xl shadow-sm hover:shadow-md border border-slate-200 hover:border-amber-400 cursor-pointer overflow-hidden transition-all duration-200 transform hover:-translate-y-0.5 flex flex-col justify-between {{ $product->stock <= 0 ? 'opacity-60 grayscale' : '' }}">
                        
                        <!-- Card Banner (Gradient Gold-Teal Sesuai Mockup) -->
                        <div class="h-24 w-full bg-gradient-to-r from-[#9e702c] via-[#4d6c6a] to-[#1c536c] flex items-center justify-center relative overflow-hidden group-hover:brightness-105 transition">
                            <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:12px_12px]"></div>

                            <!-- Icon Food -->
                            <div class="w-12 h-12 rounded-full bg-white/10 backdrop-blur-sm flex items-center justify-center border border-white/20 text-white shadow-inner">
                                @if($product->icon_type === 'cutlery')
                                    <i class="fa-solid fa-utensils text-xl"></i>
                                @elseif($product->icon_type === 'soup')
                                    <i class="fa-solid fa-mug-hot text-xl"></i>
                                @elseif($product->icon_type === 'salad')
                                    <i class="fa-solid fa-bowl-food text-xl"></i>
                                @elseif($product->icon_type === 'pizza')
                                    <i class="fa-solid fa-pizza-slice text-xl"></i>
                                @elseif($product->icon_type === 'pasta')
                                    <i class="fa-solid fa-bowl-rice text-xl"></i>
                                @elseif($product->icon_type === 'burger')
                                    <i class="fa-solid fa-burger text-xl"></i>
                                @elseif($product->icon_type === 'drink')
                                    <i class="fa-solid fa-wine-glass text-xl"></i>
                                @else
                                    <i class="fa-solid fa-utensils text-xl"></i>
                                @endif
                            </div>

                            <!-- Code badge -->
                            <span class="absolute top-2 left-2 text-[9px] font-bold bg-slate-900/60 text-white/90 px-1.5 py-0.5 rounded">
                                {{ $product->code }}
                            </span>

                            <!-- Stock Badge -->
                            <span class="absolute top-2 right-2 text-[9px] font-black px-1.5 py-0.5 rounded shadow-sm {{ $product->stock <= 0 ? 'bg-rose-600 text-white' : ($product->stock <= 10 ? 'bg-amber-500 text-slate-950' : 'bg-slate-900/70 text-slate-200') }}">
                                {{ $product->stock <= 0 ? 'HABIS' : 'Stok: ' . $product->stock }}
                            </span>
                        </div>

                        <!-- Card Info (White bottom) -->
                        <div class="p-3 text-center flex-1 flex flex-col justify-between bg-white">
                            <h3 class="text-xs font-bold text-slate-800 line-clamp-1 group-hover:text-amber-600 transition">{{ $product->name }}</h3>
                            <div class="flex items-center justify-between mt-1.5 pt-1 border-t border-slate-100">
                                <span class="text-[10px] text-slate-400 font-semibold">{{ $product->stock > 0 ? 'Tersedia' : 'Kosong' }}</span>
                                <p class="text-xs font-extrabold text-slate-900">Rp {{ number_format($product->price, 0, ',', '.') }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Empty Search State -->
            <div id="empty-search-state" class="hidden h-64 flex flex-col items-center justify-center text-slate-400">
                <i class="fa-solid fa-utensils text-4xl mb-3 text-slate-300"></i>
                <p class="text-sm font-bold text-slate-600">Menu tidak ditemukan</p>
                <p class="text-xs text-slate-400 mt-1">Coba kata kunci lain atau pilih kategori Semua.</p>
            </div>
        </div>

        <!-- Bottom Category Filter Bar -->
        <div class="h-16 bg-white border-t border-slate-200 px-4 flex items-center gap-2 overflow-x-auto flex-shrink-0 shadow-sm">
            <!-- Button: All -->
            <button type="button" 
                    id="cat-btn-all"
                    onclick="window.pos.filterCategory(null, this)" 
                    class="category-btn active flex items-center gap-2 px-4 py-2 rounded-xl border text-xs font-bold whitespace-nowrap transition shadow-sm bg-amber-50 border-amber-400 text-amber-900 ring-2 ring-amber-400">
                <i class="fa-solid fa-layer-group text-sm"></i>
                <span>All</span>
            </button>

            <!-- Dynamic Categories -->
            @foreach($categories as $cat)
                <button type="button" 
                        onclick="window.pos.filterCategory({{ $cat->id }}, this)" 
                        class="category-btn flex items-center gap-2 px-4 py-2 rounded-xl border text-xs font-bold whitespace-nowrap transition shadow-sm bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100">
                    @if($cat->slug === 'starters')
                        <i class="fa-solid fa-utensils text-sm"></i>
                    @elseif($cat->slug === 'soups')
                        <i class="fa-solid fa-mug-hot text-sm"></i>
                    @elseif($cat->slug === 'salads')
                        <i class="fa-solid fa-bowl-food text-sm"></i>
                    @elseif($cat->slug === 'main-course')
                        <i class="fa-solid fa-bell-concierge text-sm"></i>
                    @elseif($cat->slug === 'pizza')
                        <i class="fa-solid fa-pizza-slice text-sm"></i>
                    @elseif($cat->slug === 'pasta')
                        <i class="fa-solid fa-bowl-rice text-sm"></i>
                    @elseif($cat->slug === 'burgers-sandwiches')
                        <i class="fa-solid fa-burger text-sm"></i>
                    @elseif($cat->slug === 'beverages')
                        <i class="fa-solid fa-wine-glass text-sm"></i>
                    @else
                        <i class="fa-solid fa-utensils text-sm"></i>
                    @endif
                    <span>{{ $cat->name }}</span>
                </button>
            @endforeach
        </div>
    </div>


    <!-- ========================================== -->
    <!-- RIGHT: CHECKOUT & CART PANEL               -->
    <!-- ========================================== -->
    <div class="w-96 bg-white flex flex-col h-full border-l border-slate-200 flex-shrink-0 shadow-lg z-10">
        
        <!-- Top Checkout Header -->
        <div class="p-4 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-base font-extrabold text-slate-800">Checkout</h2>
            <div class="flex items-center gap-1.5 text-xs font-semibold text-slate-600 bg-slate-100 px-2.5 py-1 rounded-full border border-slate-200">
                <i class="fa-regular fa-user text-slate-400"></i>
                <span id="label-customer-name">Walk-in</span>
            </div>
        </div>

        <!-- Order Type & Table Selector -->
        <div class="p-4 bg-slate-50 border-b border-slate-200 space-y-3">
            <div class="flex gap-2">
                <!-- Order Type Dropdown -->
                <div class="flex-1">
                    <select id="order-type-select" onchange="window.pos.onOrderTypeChange(this.value)" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-amber-500 focus:outline-none shadow-sm">
                        <option value="dine-in">Dine-in (Makan di Tempat)</option>
                        <option value="takeaway">Takeaway (Bungkus)</option>
                        <option value="delivery">Delivery (Antar)</option>
                    </select>
                </div>

                <!-- Table Selector -->
                <div class="flex-1" id="table-select-container">
                    <select id="table-select" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-amber-500 focus:outline-none shadow-sm">
                        <option value="">Pilih Meja...</option>
                        @foreach($tables as $table)
                            <option value="{{ $table->id }}">
                                {{ $table->table_number }} ({{ $table->capacity }} Kursi) - {{ ucfirst($table->status) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Customer Name Input -->
            <div>
                <input type="text" id="customer-name-input" oninput="document.getElementById('label-customer-name').innerText = this.value || 'Walk-in'" placeholder="Nama Pelanggan (opsional)" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-1.5 text-xs text-slate-700 placeholder-slate-400 focus:ring-2 focus:ring-amber-500 focus:outline-none">
            </div>
        </div>

        <!-- Cart Table Header -->
        <div class="px-4 py-2 bg-slate-100 border-b border-slate-200 grid grid-cols-12 text-[10px] font-black uppercase tracking-wider text-slate-500">
            <div class="col-span-6">NAME</div>
            <div class="col-span-3 text-center">QTY</div>
            <div class="col-span-3 text-right">PRICE</div>
        </div>

        <!-- Cart Items List (Scrollable) -->
        <div class="flex-1 overflow-y-auto divide-y divide-slate-100 p-2" id="cart-container">
            <!-- Empty Cart State -->
            <div id="cart-empty-state" class="h-full flex flex-col items-center justify-center text-slate-400 py-12">
                <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center text-slate-300 mb-3">
                    <i class="fa-solid fa-cart-shopping text-2xl"></i>
                </div>
                <p class="text-sm font-bold text-slate-600">Cart is empty.</p>
                <p class="text-xs text-slate-400 mt-1">Tap a product card to add it.</p>
            </div>

            <!-- Items rendered by JS -->
            <div id="cart-items-list" class="space-y-1"></div>
        </div>

        <!-- Calculations Summary (Rupiah) -->
        <div class="p-4 bg-slate-50 border-t border-slate-200 space-y-2 text-xs">
            <!-- Discount -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 text-slate-600 font-semibold">
                    <span>Discount</span>
                    <select id="discount-type" onchange="window.pos.recalculate()" class="bg-white border border-slate-300 rounded px-1.5 py-0.5 text-[11px] font-medium text-slate-700 focus:outline-none">
                        <option value="percent">Percent (%)</option>
                        <option value="fixed">Nominal (Rp)</option>
                    </select>
                </div>
                <div class="w-24">
                    <input type="number" id="discount-val" min="0" oninput="window.pos.recalculate()" placeholder="0" class="w-full bg-white border border-slate-300 rounded px-2 py-0.5 text-right font-bold text-xs focus:ring-1 focus:ring-amber-500 focus:outline-none">
                </div>
            </div>

            <!-- Sub Total -->
            <div class="flex items-center justify-between text-slate-600">
                <span class="font-semibold">Sub Total</span>
                <span class="font-bold text-slate-800" id="summary-subtotal">Rp 0</span>
            </div>

            <!-- Tax 10% -->
            <div class="flex items-center justify-between text-slate-600">
                <div class="flex items-center gap-2">
                    <span class="font-semibold">Tax</span>
                    <span class="bg-amber-100 text-amber-800 text-[10px] font-black px-1.5 py-0.5 rounded border border-amber-200">10%</span>
                </div>
                <span class="font-bold text-slate-800" id="summary-tax">Rp 0</span>
            </div>

            <!-- Service Charge -->
            <div class="flex items-center justify-between text-slate-600">
                <span class="font-semibold">Service charge (Rp)</span>
                <div class="w-24">
                    <input type="number" id="service-charge-val" min="0" step="1000" oninput="window.pos.recalculate()" placeholder="0" class="w-full bg-white border border-slate-300 rounded px-2 py-0.5 text-right font-bold text-xs focus:ring-1 focus:ring-amber-500 focus:outline-none">
                </div>
            </div>

            <!-- Tip -->
            <div class="flex items-center justify-between text-slate-600">
                <span class="font-semibold">Tip (Rp)</span>
                <div class="w-24">
                    <input type="number" id="tip-val" min="0" step="1000" oninput="window.pos.recalculate()" placeholder="0" class="w-full bg-white border border-slate-300 rounded px-2 py-0.5 text-right font-bold text-xs focus:ring-1 focus:ring-amber-500 focus:outline-none">
                </div>
            </div>

            <!-- Grand Total -->
            <div class="pt-2 border-t border-slate-200 flex items-center justify-between">
                <span class="text-sm font-black text-slate-900">Total</span>
                <span class="text-lg font-black text-slate-950" id="summary-total">Rp 0</span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="p-4 border-t border-slate-200 bg-white space-y-2 flex-shrink-0">
            @if($currentUser->role === 'manager')
                <div class="p-3 bg-indigo-50 border border-indigo-200 rounded-xl text-center space-y-1">
                    <p class="text-xs font-bold text-indigo-900 flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-shield-halved text-indigo-600"></i> Mode Pemantauan Manajer Aktif
                    </p>
                    <p class="text-[11px] text-indigo-700">Aksi kasir dinonaktifkan. Silakan gunakan akun Kasir/Admin jika ingin bertransaksi.</p>
                </div>
            @else
                <div class="grid grid-cols-2 gap-2">
                    <!-- Cancel Order Button -->
                    <button type="button" 
                            onclick="window.pos.clearCart()" 
                            class="py-2.5 px-3 rounded-lg font-bold text-xs text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition flex items-center justify-center gap-1.5 cursor-pointer">
                        <i class="fa-solid fa-xmark"></i>
                        <span>Cancel Order</span>
                    </button>

                    <!-- Hold Order Button -->
                    <button type="button" 
                            onclick="window.pos.holdOrder()" 
                            class="py-2.5 px-3 rounded-lg font-bold text-xs text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 shadow-sm transition flex items-center justify-center gap-1.5 cursor-pointer">
                        <i class="fa-solid fa-pause text-slate-500"></i>
                        <span>Hold Order</span>
                    </button>
                </div>

                <!-- Pay Button -->
                <button type="button" 
                        onclick="window.pos.openPaymentModal()" 
                        class="w-full py-3.5 px-4 rounded-xl font-black text-sm text-slate-950 bg-amber-500 hover:bg-amber-400 active:bg-amber-600 transition shadow-md flex items-center justify-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-check text-base"></i>
                    <span id="pay-btn-label">Pay (Rp 0)</span>
                </button>
            @endif
        </div>
    </div>


    <!-- ========================================== -->
    <!-- MODAL: ADD NEW ITEM (VANILLA JS MODAL)     -->
    <!-- ========================================== -->
    <div id="modal-add-product" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-200">
            <div class="bg-[#0e2a47] text-white p-5 flex items-center justify-between">
                <div>
                    <h3 class="font-extrabold text-base">Tambah Menu Baru</h3>
                    <p class="text-xs text-slate-300">Tambahkan makanan / minuman ke katalog Baknus Dresto</p>
                </div>
                <button type="button" onclick="window.pos.closeAddModal()" class="text-slate-400 hover:text-white transition cursor-pointer">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form onsubmit="window.pos.saveNewProduct(event)" class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Menu</label>
                    <input type="text" id="new-item-name" required placeholder="Contoh: Nasi Goreng Spesial" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Kode Menu</label>
                        <input type="text" id="new-item-code" required placeholder="NGS-01" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Kategori</label>
                        <select id="new-item-category" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Harga Jual (Rp)</label>
                        <input type="number" id="new-item-price" step="500" required placeholder="25000" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none font-bold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Ikon Tampilan</label>
                        <select id="new-item-icon" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
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


    <!-- ========================================== -->
    <!-- MODAL: PEMBAYARAN KASIR                    -->
    <!-- ========================================== -->
    <div id="modal-payment" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-200">
            <div class="bg-[#0e2a47] text-white p-5 flex items-center justify-between">
                <div>
                    <h3 class="font-extrabold text-base">Proses Pembayaran</h3>
                    <p class="text-xs text-slate-300">Pilih metode pembayaran kasir</p>
                </div>
                <button type="button" onclick="window.pos.closePaymentModal()" class="text-slate-400 hover:text-white transition cursor-pointer">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="p-6 space-y-5">
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-center">
                    <p class="text-xs font-bold text-amber-800 uppercase tracking-wider">Total yang Harus Dibayar</p>
                    <p class="text-3xl font-black text-slate-900 mt-1" id="pay-modal-total">Rp 0</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">Metode Pembayaran</label>
                    <div class="grid grid-cols-4 gap-2" id="pay-methods-group">
                        <button type="button" onclick="window.pos.setPayMethod('cash')" id="btn-pm-cash" class="py-2.5 px-2 rounded-xl border text-xs flex flex-col items-center gap-1 transition bg-amber-500 text-slate-950 font-bold border-amber-500 shadow cursor-pointer">
                            <i class="fa-solid fa-money-bill-wave text-base"></i>
                            <span>Tunai</span>
                        </button>
                        <button type="button" onclick="window.pos.setPayMethod('qris')" id="btn-pm-qris" class="py-2.5 px-2 rounded-xl border text-xs flex flex-col items-center gap-1 transition bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100 cursor-pointer">
                            <i class="fa-solid fa-qrcode text-base"></i>
                            <span>QRIS</span>
                        </button>
                        <button type="button" onclick="window.pos.setPayMethod('debit')" id="btn-pm-debit" class="py-2.5 px-2 rounded-xl border text-xs flex flex-col items-center gap-1 transition bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100 cursor-pointer">
                            <i class="fa-solid fa-credit-card text-base"></i>
                            <span>Debit</span>
                        </button>
                        <button type="button" onclick="window.pos.setPayMethod('credit')" id="btn-pm-credit" class="py-2.5 px-2 rounded-xl border text-xs flex flex-col items-center gap-1 transition bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100 cursor-pointer">
                            <i class="fa-solid fa-id-card text-base"></i>
                            <span>Kredit</span>
                        </button>
                    </div>
                </div>

                <!-- Cash input section -->
                <div id="cash-payment-section" class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Jumlah Uang Diterima (Rp)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 font-bold text-slate-400 text-xs">Rp</span>
                            <input type="number" id="cash-amount-paid" step="1000" oninput="window.pos.onCashChange()" class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-9 pr-4 py-2 text-base font-extrabold text-slate-900 focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>
                    </div>

                    <!-- Quick buttons -->
                    <div class="flex flex-wrap gap-2">
                        <button type="button" onclick="window.pos.setCashQuick('exact')" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition">Uang Pas</button>
                        <button type="button" onclick="window.pos.setCashQuick(50000)" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition">Rp 50.000</button>
                        <button type="button" onclick="window.pos.setCashQuick(100000)" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition">Rp 100.000</button>
                        <button type="button" onclick="window.pos.setCashQuick(200000)" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition">Rp 200.000</button>
                        <button type="button" onclick="window.pos.setCashQuick(500000)" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition">Rp 500.000</button>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-100 flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-600">Kembalian:</span>
                        <span class="text-base font-black text-emerald-600" id="pay-change-due">Rp 0</span>
                    </div>
                </div>

                <!-- QRIS Section -->
                <div id="qris-payment-section" class="hidden text-center py-4 bg-slate-50 border border-slate-200 rounded-xl">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=BAKNUSDRESTO-PAYMENT" alt="QRIS" class="mx-auto rounded-lg shadow-sm">
                    <p class="text-xs text-slate-500 mt-2 font-medium">Scan QRIS menggunakan BCA, Mandiri, BRI, GoPay, OVO, ShopeePay, DANA</p>
                </div>

                <button type="button" 
                        onclick="window.pos.submitPayment()" 
                        id="btn-confirm-pay"
                        class="w-full py-3.5 bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 text-white font-extrabold rounded-xl shadow-lg transition flex items-center justify-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-receipt"></i>
                    <span>Konfirmasi & Selesaikan Transaksi</span>
                </button>
            </div>
        </div>
    </div>


    <!-- ========================================== -->
    <!-- MODAL: HELD ORDERS                         -->
    <!-- ========================================== -->
    <div id="modal-held" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full overflow-hidden border border-slate-200">
            <div class="bg-[#0e2a47] text-white p-5 flex items-center justify-between">
                <div>
                    <h3 class="font-extrabold text-base">Held Orders (Pesanan Ditahan)</h3>
                    <p class="text-xs text-slate-300">Panggil kembali pesanan ke kasir atau batalkan</p>
                </div>
                <button type="button" onclick="window.pos.closeHeldModal()" class="text-slate-400 hover:text-white transition cursor-pointer">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="p-6 max-h-[60vh] overflow-y-auto space-y-3" id="held-orders-container">
                @forelse($heldOrders as $held)
                    <div class="p-4 border border-slate-200 hover:border-amber-400 rounded-xl bg-slate-50 flex items-center justify-between gap-4 transition" id="held-row-{{ $held->id }}">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-black text-slate-900">{{ $held->order_number }}</span>
                                <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800">{{ $held->order_type }}</span>
                                <span class="text-[11px] text-slate-500">{{ $held->table ? $held->table->table_number : 'Walk-in' }}</span>
                            </div>
                            <p class="text-xs text-slate-600 mt-1">
                                Pelanggan: <strong class="text-slate-800">{{ $held->customer_name }}</strong> | 
                                {{ count($held->items) }} menu dipesan
                            </p>
                            <p class="text-xs font-black text-amber-600 mt-1">
                                Total: Rp {{ number_format($held->total_amount, 0, ',', '.') }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="window.pos.recallOrder({{ $held->id }})" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black rounded-lg text-xs shadow transition cursor-pointer">
                                Recall to Cart
                            </button>
                            <button type="button" onclick="window.pos.cancelHeldOrder({{ $held->id }})" class="px-3 py-2 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg text-xs font-bold transition cursor-pointer">
                                Batal
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center text-slate-400">
                        <i class="fa-regular fa-folder-open text-4xl mb-2 text-slate-300"></i>
                        <p class="text-sm font-bold text-slate-600">Tidak ada pesanan yang ditahan</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>


    <!-- ========================================== -->
    <!-- MODAL: RECEIPT PRINT                       -->
    <!-- ========================================== -->
    <div id="modal-receipt" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full overflow-hidden border border-slate-200">
            <div class="p-6 text-center border-b border-dashed border-slate-300" id="receipt-print-area">
                <img src="{{ asset('images/logo.png') }}" alt="Baknus Dresto Logo" class="w-14 h-14 rounded-2xl mx-auto mb-2 object-contain shadow-sm border border-slate-100">
                <h2 class="text-base font-black text-slate-900 uppercase tracking-wider">Baknus Dresto</h2>
                <p class="text-[11px] text-slate-500">Jl. Raya Resto No. 88, Jakarta</p>
                <p class="text-[10px] text-slate-400">Telp: 021-8889999</p>

                <div class="my-3 py-2 border-y border-dashed border-slate-300 text-left text-[11px] space-y-1">
                    <div class="flex justify-between"><span class="text-slate-500">Invoice:</span><strong id="rcpt-invoice">-</strong></div>
                    <div class="flex justify-between"><span class="text-slate-500">Order:</span><span id="rcpt-order">-</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Kasir:</span><span>Alex Morgan</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Tipe / Meja:</span><span id="rcpt-table">-</span></div>
                </div>

                <div class="text-left text-xs space-y-1.5 my-3" id="rcpt-items-list"></div>

                <div class="border-t border-dashed border-slate-300 pt-2 text-[11px] space-y-1 text-left">
                    <div class="flex justify-between text-slate-600"><span>Sub Total:</span><span id="rcpt-subtotal">Rp 0</span></div>
                    <div class="flex justify-between text-slate-600"><span>Discount:</span><span id="rcpt-discount">-Rp 0</span></div>
                    <div class="flex justify-between text-slate-600"><span>Pajak (10%):</span><span id="rcpt-tax">Rp 0</span></div>
                    <div class="flex justify-between text-base font-black text-slate-950 pt-1 border-t border-slate-200">
                        <span>Total:</span><span id="rcpt-total">Rp 0</span>
                    </div>
                    <div class="flex justify-between text-slate-600 pt-1"><span id="rcpt-pay-method-label">Bayar (TUNAI):</span><span id="rcpt-paid">Rp 0</span></div>
                    <div class="flex justify-between text-slate-600"><span>Kembalian:</span><span id="rcpt-change">Rp 0</span></div>
                </div>

                <p class="text-[10px] text-slate-400 mt-4 italic">Terima kasih atas kunjungan Anda!</p>
            </div>

            <div class="p-4 bg-slate-50 flex gap-2">
                <button type="button" onclick="window.print()" class="flex-1 py-2.5 bg-[#0e2a47] hover:bg-[#153a60] text-white font-bold rounded-xl text-xs transition flex items-center justify-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-print"></i> Cetak Struk
                </button>
                <button type="button" onclick="document.getElementById('modal-receipt').classList.add('hidden')" class="px-4 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold rounded-xl text-xs transition cursor-pointer">
                    Tutup
                </button>
            </div>
        </div>
    </div>

</div>

<!-- JAVASCRIPT POS ENGINE (MURNI TANPA DEPENDENSI, 100% RELIABLE) -->
<script>
window.pos = {
    userRole: '{{ $currentUser->role }}',
    cart: [],
    paymentMethod: 'cash',
    activeCategoryId: null,

    init() {
        const now = new Date();
        const pad = (n) => String(n).padStart(2, '0');
        const str = `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())} ${pad(now.getHours())}:${pad(now.getMinutes())}`;
        const lbl = document.getElementById('last-refresh-label');
        if (lbl) lbl.innerText = str;
    },

    // 1. ADD TO CART
    addToCart(id, name, price, maxStock) {
        if (this.userRole === 'manager') {
            alert('Akses Terkunci: Anda sedang masuk dengan peran MANAJER. Akun manajer hanya untuk pemantauan laporan, analitik & stok, dan tidak diizinkan bertindak sebagai kasir.');
            return;
        }

        if (maxStock <= 0) {
            alert(`Maaf, stok ${name} sudah habis! Silakan lakukan restock di menu Inventory.`);
            return;
        }

        const existing = this.cart.find(i => i.id === id);
        if (existing) {
            if (existing.qty + 1 > maxStock) {
                alert(`Jumlah pesanan untuk ${name} melebihi sisa stok yang ada (Tersedia: ${maxStock} porsi).`);
                return;
            }
            existing.qty += 1;
        } else {
            this.cart.push({
                id: id,
                name: name,
                price: parseFloat(price),
                maxStock: maxStock,
                qty: 1,
                notes: ''
            });
        }
        this.renderCart();
    },

    changeQty(id, delta) {
        const item = this.cart.find(i => i.id === id);
        if (!item) return;

        if (delta > 0 && item.maxStock && (item.qty + delta > item.maxStock)) {
            alert(`Jumlah pesanan untuk ${item.name} melebihi sisa stok (Tersedia: ${item.maxStock} porsi).`);
            return;
        }

        item.qty += delta;
        if (item.qty <= 0) {
            this.cart = this.cart.filter(i => i.id !== id);
        }
        this.renderCart();
    },

    removeItem(id) {
        this.cart = this.cart.filter(i => i.id !== id);
        this.renderCart();
    },

    updateNotes(id, val) {
        const item = this.cart.find(i => i.id === id);
        if (item) item.notes = val;
    },

    clearCart() {
        if (this.cart.length === 0) return;
        if (confirm('Yakin ingin mengosongkan keranjang kasir?')) {
            this.cart = [];
            document.getElementById('discount-val').value = '';
            document.getElementById('service-charge-val').value = '';
            document.getElementById('tip-val').value = '';
            this.renderCart();
        }
    },

    // 2. RENDER CART & RECALCULATE
    renderCart() {
        const container = document.getElementById('cart-items-list');
        const emptyState = document.getElementById('cart-empty-state');

        if (this.cart.length === 0) {
            container.innerHTML = '';
            emptyState.classList.remove('hidden');
        } else {
            emptyState.classList.add('hidden');
            let html = '';
            this.cart.forEach(item => {
                const sub = item.qty * item.price;
                html += `
                    <div class="py-2.5 px-2 hover:bg-slate-50 rounded-lg transition border-b border-slate-100 last:border-b-0">
                        <div class="grid grid-cols-12 items-center gap-1">
                            <div class="col-span-6">
                                <p class="text-xs font-bold text-slate-800 leading-tight">${item.name}</p>
                                <p class="text-[10px] text-slate-400 mt-0.5">${window.formatRupiah(item.price)} / item</p>
                            </div>
                            <div class="col-span-3 flex items-center justify-center gap-1.5">
                                <button type="button" onclick="window.pos.changeQty(${item.id}, -1)" class="w-5 h-5 rounded bg-slate-200 hover:bg-slate-300 text-slate-700 flex items-center justify-center text-[10px] font-bold transition cursor-pointer">-</button>
                                <span class="text-xs font-bold text-slate-800 w-4 text-center">${item.qty}</span>
                                <button type="button" onclick="window.pos.changeQty(${item.id}, 1)" class="w-5 h-5 rounded bg-slate-200 hover:bg-slate-300 text-slate-700 flex items-center justify-center text-[10px] font-bold transition cursor-pointer">+</button>
                            </div>
                            <div class="col-span-3 text-right">
                                <span class="text-xs font-extrabold text-slate-900">${window.formatRupiah(sub)}</span>
                            </div>
                        </div>
                        <div class="mt-1.5 flex items-center gap-1 text-[11px]">
                            <i class="fa-regular fa-comment-dots text-slate-400 text-[10px]"></i>
                            <input type="text" value="${item.notes}" oninput="window.pos.updateNotes(${item.id}, this.value)" placeholder="Catatan: pedas/tanpa es..." class="flex-1 bg-transparent border-b border-dashed border-slate-200 text-[11px] text-slate-600 focus:outline-none focus:border-amber-400 px-1 py-0.5">
                            <button type="button" onclick="window.pos.removeItem(${item.id})" class="text-rose-400 hover:text-rose-600 text-xs px-1 cursor-pointer" title="Hapus">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
        }

        this.recalculate();
    },

    recalculate() {
        const subtotal = this.cart.reduce((sum, i) => sum + (i.qty * i.price), 0);
        
        const discType = document.getElementById('discount-type').value;
        const discVal = parseFloat(document.getElementById('discount-val').value) || 0;
        let discountAmount = 0;
        if (discType === 'percent') {
            discountAmount = (subtotal * discVal) / 100;
        } else {
            discountAmount = Math.min(subtotal, discVal);
        }

        const taxable = Math.max(0, subtotal - discountAmount);
        const taxAmount = (taxable * 10) / 100;
        const serviceCharge = parseFloat(document.getElementById('service-charge-val').value) || 0;
        const tipAmount = parseFloat(document.getElementById('tip-val').value) || 0;
        const grandTotal = taxable + taxAmount + serviceCharge + tipAmount;

        document.getElementById('summary-subtotal').innerText = window.formatRupiah(subtotal);
        document.getElementById('summary-tax').innerText = window.formatRupiah(taxAmount);
        document.getElementById('summary-total').innerText = window.formatRupiah(grandTotal);
        document.getElementById('pay-btn-label').innerText = `Pay (${window.formatRupiah(grandTotal)})`;

        this.calculated = { subtotal, discountAmount, taxAmount, serviceCharge, tipAmount, grandTotal };
        return this.calculated;
    },

    onOrderTypeChange(val) {
        const tblContainer = document.getElementById('table-select-container');
        if (val === 'dine-in') {
            tblContainer.classList.remove('hidden');
        } else {
            tblContainer.classList.add('hidden');
        }
    },

    // 3. FILTER & SEARCH
    filterCategory(catId, btn) {
        this.activeCategoryId = catId;
        
        document.querySelectorAll('.category-btn').forEach(b => {
            b.className = 'category-btn flex items-center gap-2 px-4 py-2 rounded-xl border text-xs font-bold whitespace-nowrap transition shadow-sm bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100 cursor-pointer';
        });
        if (btn) {
            btn.className = 'category-btn active flex items-center gap-2 px-4 py-2 rounded-xl border text-xs font-bold whitespace-nowrap transition shadow-sm bg-amber-50 border-amber-400 text-amber-900 ring-2 ring-amber-400 cursor-pointer';
        }

        this.applyFilter();
    },

    onSearch(query) {
        this.searchQuery = (query || '').toLowerCase().trim();
        this.applyFilter();
    },

    applyFilter() {
        const cards = document.querySelectorAll('.product-card');
        let visibleCount = 0;

        cards.forEach(card => {
            const cat = card.dataset.category;
            const name = card.dataset.name;
            const code = card.dataset.code;
            const barcode = card.dataset.barcode;

            const matchCat = (!this.activeCategoryId || cat == this.activeCategoryId);
            const matchSearch = (!this.searchQuery || name.includes(this.searchQuery) || code.includes(this.searchQuery) || barcode.includes(this.searchQuery));

            if (matchCat && matchSearch) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        document.getElementById('visible-products-count').innerText = visibleCount;
        const empty = document.getElementById('empty-search-state');
        if (visibleCount === 0) {
            empty.classList.remove('hidden');
        } else {
            empty.classList.add('hidden');
        }
    },

    // 4. MODALS
    openAddModal() {
        document.getElementById('modal-add-product').classList.remove('hidden');
    },

    closeAddModal() {
        document.getElementById('modal-add-product').classList.add('hidden');
    },

    openHeldModal() {
        document.getElementById('modal-held').classList.remove('hidden');
    },

    closeHeldModal() {
        document.getElementById('modal-held').classList.add('hidden');
    },

    openPaymentModal() {
        if (this.userRole === 'manager') {
            alert('Akses Terkunci: Akun Manajer tidak diperbolehkan memproses pembayaran transaksi.');
            return;
        }

        if (this.cart.length === 0) {
            alert('Keranjang belanja masih kosong! Silakan klik salah satu menu makanan.');
            return;
        }
        const calc = this.recalculate();
        document.getElementById('pay-modal-total').innerText = window.formatRupiah(calc.grandTotal);
        document.getElementById('cash-amount-paid').value = calc.grandTotal;
        this.setPayMethod('cash');
        this.onCashChange();
        document.getElementById('modal-payment').classList.remove('hidden');
    },

    closePaymentModal() {
        document.getElementById('modal-payment').classList.add('hidden');
    },

    setPayMethod(method) {
        this.paymentMethod = method;
        const methods = ['cash', 'qris', 'debit', 'credit'];
        methods.forEach(m => {
            const btn = document.getElementById(`btn-pm-${m}`);
            if (m === method) {
                btn.className = 'py-2.5 px-2 rounded-xl border text-xs flex flex-col items-center gap-1 transition bg-amber-500 text-slate-950 font-bold border-amber-500 shadow cursor-pointer';
            } else {
                btn.className = 'py-2.5 px-2 rounded-xl border text-xs flex flex-col items-center gap-1 transition bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100 cursor-pointer';
            }
        });

        const cashSec = document.getElementById('cash-payment-section');
        const qrisSec = document.getElementById('qris-payment-section');

        if (method === 'cash') {
            cashSec.classList.remove('hidden');
            qrisSec.classList.add('hidden');
        } else if (method === 'qris') {
            cashSec.classList.add('hidden');
            qrisSec.classList.remove('hidden');
        } else {
            cashSec.classList.add('hidden');
            qrisSec.classList.add('hidden');
        }
    },

    setCashQuick(val) {
        const calc = this.recalculate();
        if (val === 'exact') {
            document.getElementById('cash-amount-paid').value = calc.grandTotal;
        } else {
            document.getElementById('cash-amount-paid').value = val;
        }
        this.onCashChange();
    },

    onCashChange() {
        const calc = this.recalculate();
        const paid = parseFloat(document.getElementById('cash-amount-paid').value) || 0;
        const change = paid - calc.grandTotal;
        const dueLabel = document.getElementById('pay-change-due');

        dueLabel.innerText = window.formatRupiah(Math.max(0, change));
        if (change < 0) {
            dueLabel.className = 'text-base font-black text-rose-600';
            dueLabel.innerText = `Kurang ${window.formatRupiah(Math.abs(change))}`;
        } else {
            dueLabel.className = 'text-base font-black text-emerald-600';
        }
    },

    // 5. SUBMIT TO BACKEND
    async saveNewProduct(e) {
        e.preventDefault();
        const payload = {
            name: document.getElementById('new-item-name').value,
            code: document.getElementById('new-item-code').value,
            category_id: document.getElementById('new-item-category').value,
            price: document.getElementById('new-item-price').value,
            icon_type: document.getElementById('new-item-icon').value,
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
                alert('Gagal menambah menu: ' + (data.message || 'Cek kelengkapan form'));
            }
        } catch (err) {
            alert('Terjadi kesalahan koneksi.');
        }
    },

    async holdOrder() {
        if (this.userRole === 'manager') {
            alert('Akses Terkunci: Akun Manajer tidak diperbolehkan menahan atau membuat pesanan.');
            return;
        }

        if (this.cart.length === 0) {
            alert('Pilih minimal satu menu makanan sebelum hold order.');
            return;
        }

        const calc = this.recalculate();
        const payload = {
            order_type: document.getElementById('order-type-select').value,
            table_id: document.getElementById('table-select').value || null,
            customer_name: document.getElementById('customer-name-input').value || 'Walk-in',
            discount_type: document.getElementById('discount-type').value,
            discount_value: parseFloat(document.getElementById('discount-val').value) || 0,
            service_charge: calc.serviceCharge,
            tip_amount: calc.tipAmount,
            items: this.cart.map(i => ({ product_id: i.id, quantity: i.qty, unit_price: i.price, notes: i.notes })),
            is_held: true
        };

        try {
            const res = await fetch("{{ route('orders.store') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.success) {
                alert('Pesanan berhasil disimpan di Held Orders!');
                window.location.reload();
            }
        } catch (err) {
            alert('Gagal menyimpan pesanan.');
        }
    },

    async recallOrder(orderId) {
        try {
            const res = await fetch(`/orders/recall/${orderId}`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            });
            const data = await res.json();
            if (data.success) {
                const order = data.order;
                document.getElementById('order-type-select').value = order.order_type;
                this.onOrderTypeChange(order.order_type);
                if (order.table_id) document.getElementById('table-select').value = order.table_id;
                document.getElementById('customer-name-input').value = order.customer_name;
                document.getElementById('label-customer-name').innerText = order.customer_name;
                document.getElementById('discount-type').value = order.discount_type;
                document.getElementById('discount-val').value = parseFloat(order.discount_value) || '';

                this.cart = order.items.map(i => ({
                    id: i.product_id,
                    name: i.product_name,
                    price: parseFloat(i.unit_price),
                    qty: i.quantity,
                    notes: i.notes || ''
                }));

                await fetch(`/orders/cancel/${orderId}`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                });

                this.closeHeldModal();
                this.renderCart();
            }
        } catch (err) {
            alert('Gagal memanggil pesanan.');
        }
    },

    async cancelHeldOrder(orderId) {
        if (!confirm('Yakin ingin membatalkan pesanan ini?')) return;
        await fetch(`/orders/cancel/${orderId}`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        });
        window.location.reload();
    },

    async submitPayment() {
        const calc = this.recalculate();
        const paid = this.paymentMethod === 'cash' ? (parseFloat(document.getElementById('cash-amount-paid').value) || 0) : calc.grandTotal;

        if (this.paymentMethod === 'cash' && paid < calc.grandTotal) {
            alert('Jumlah uang tunai yang dibayar masih kurang!');
            return;
        }

        const btn = document.getElementById('btn-confirm-pay');
        btn.disabled = true;
        btn.innerText = 'Memproses Transaksi...';

        try {
            // 1. Buat pesanan
            const orderPayload = {
                order_type: document.getElementById('order-type-select').value,
                table_id: document.getElementById('table-select').value || null,
                customer_name: document.getElementById('customer-name-input').value || 'Walk-in',
                discount_type: document.getElementById('discount-type').value,
                discount_value: parseFloat(document.getElementById('discount-val').value) || 0,
                service_charge: calc.serviceCharge,
                tip_amount: calc.tipAmount,
                items: this.cart.map(i => ({ product_id: i.id, quantity: i.qty, unit_price: i.price, notes: i.notes })),
                is_held: false
            };

            const orderRes = await fetch("{{ route('orders.store') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(orderPayload)
            });
            const orderData = await orderRes.json();

            if (!orderData.success) {
                alert(orderData.message || 'Gagal menyimpan pesanan');
                btn.disabled = false;
                btn.innerText = 'Konfirmasi & Selesaikan Transaksi';
                return;
            }

            // 2. Bayar
            const payRes = await fetch(`/payments/${orderData.order.id}/pay`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    payment_method: this.paymentMethod,
                    amount_paid: paid
                })
            });
            const payData = await payRes.json();

            if (payData.success) {
                this.closePaymentModal();
                this.showReceipt(payData);

                // Reset cart
                this.cart = [];
                document.getElementById('discount-val').value = '';
                document.getElementById('service-charge-val').value = '';
                document.getElementById('tip-val').value = '';
                document.getElementById('table-select').value = '';
                document.getElementById('customer-name-input').value = '';
                document.getElementById('label-customer-name').innerText = 'Walk-in';
                this.renderCart();
            } else {
                alert(payData.message || 'Pembayaran gagal');
            }
        } catch (err) {
            alert('Terjadi kesalahan saat memproses pembayaran.');
        } finally {
            btn.disabled = false;
            btn.innerText = 'Konfirmasi & Selesaikan Transaksi';
        }
    },

    showReceipt(data) {
        document.getElementById('rcpt-invoice').innerText = data.payment.invoice_number;
        document.getElementById('rcpt-order').innerText = data.order.order_number;
        document.getElementById('rcpt-table').innerText = `${data.order.order_type.toUpperCase()} ${data.order.table ? '('+data.order.table.table_number+')' : ''}`;
        
        let itemsHtml = '';
        data.order.items.forEach(i => {
            itemsHtml += `
                <div class="flex justify-between items-start">
                    <div>
                        <span class="font-bold text-slate-800">${i.product_name}</span>
                        <span class="text-[10px] text-slate-500 block">${i.quantity} x ${window.formatRupiah(i.unit_price)}</span>
                    </div>
                    <span class="font-bold text-slate-900">${window.formatRupiah(i.subtotal)}</span>
                </div>
            `;
        });
        document.getElementById('rcpt-items-list').innerHTML = itemsHtml;

        document.getElementById('rcpt-subtotal').innerText = window.formatRupiah(data.order.subtotal);
        document.getElementById('rcpt-discount').innerText = `-${window.formatRupiah(data.order.discount_amount)}`;
        document.getElementById('rcpt-tax').innerText = window.formatRupiah(data.order.tax_amount);
        document.getElementById('rcpt-total').innerText = window.formatRupiah(data.order.total_amount);
        document.getElementById('rcpt-pay-method-label').innerText = `Bayar (${data.payment.payment_method.toUpperCase()}):`;
        document.getElementById('rcpt-paid').innerText = window.formatRupiah(data.payment.amount_paid);
        document.getElementById('rcpt-change').innerText = window.formatRupiah(data.payment.change_due);

        document.getElementById('modal-receipt').classList.remove('hidden');
    }
};

// Jalankan inisialisasi saat DOM siap
document.addEventListener('DOMContentLoaded', () => {
    window.pos.init();
});
</script>
@endsection
