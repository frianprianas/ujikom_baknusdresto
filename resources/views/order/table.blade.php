<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Self Order — {{ $table->table_number }} | Baknus Dresto</title>

    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            navy: '#0e2946',
                            navylight: '#173c66',
                            gold: '#f59e0b',
                            golddark: '#d97706',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 antialiased font-sans min-h-screen flex justify-center selection:bg-amber-100 selection:text-amber-900">

    <!-- Container Mobile Phone Simulation (Max 480px di desktop, full di HP) -->
    <div class="w-full max-w-md bg-white min-h-screen flex flex-col shadow-2xl relative" x-data="tableOrderApp()" x-cloak>

        <!-- TOP BAR / HEADER -->
        <header class="bg-brand-navy text-white px-4 py-3 sticky top-0 z-30 shadow-md">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <img src="{{ asset('images/logo.png') }}" class="w-9 h-9 rounded-full object-cover border border-amber-400/40 shadow-sm" alt="Baknus Dresto">
                    <div>
                        <h1 class="text-sm font-black tracking-wide leading-tight flex items-center gap-1.5">
                            Baknus Dresto
                            <span class="text-[9px] bg-amber-500 text-brand-navy font-black px-1.5 py-0.5 rounded uppercase">Self Order</span>
                        </h1>
                        <p class="text-[10px] text-slate-300">Pesan langsung dari meja tanpa antre</p>
                    </div>
                </div>

                <!-- BADGE MEJA TERKUNCI -->
                <div class="bg-amber-500/20 border border-amber-400/60 rounded-xl px-2.5 py-1 text-right flex items-center gap-1.5">
                    <i class="fa-solid fa-lock text-amber-400 text-xs"></i>
                    <div>
                        <p class="text-[9px] text-amber-200 font-bold uppercase tracking-wider">Nomor Meja</p>
                        <p class="text-xs font-black text-amber-300 leading-tight">{{ $table->table_number }}</p>
                    </div>
                </div>
            </div>

            <!-- SUB-INFO LOKASI MEJA -->
            <div class="mt-2 pt-2 border-t border-slate-700/60 flex items-center justify-between text-[11px] text-slate-300">
                <span class="flex items-center gap-1">
                    <i class="fa-solid fa-map-pin text-amber-400 text-[10px]"></i>
                    {{ $table->floor_section ?? 'Area Utama' }} (Kapasitas {{ $table->capacity }} Orang)
                </span>
                <span class="text-emerald-400 font-semibold flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    Meja Terhubung
                </span>
            </div>
        </header>

        <!-- ======================================================== -->
        <!-- SCREEN 1: IDENTITAS PEMESAN (Jika Belum Diisi)           -->
        <!-- ======================================================== -->
        <main class="flex-1 p-5 flex flex-col justify-center" x-show="step === 'customer'">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-amber-50 border border-amber-200 text-amber-500 rounded-2xl mx-auto flex items-center justify-center text-2xl shadow-sm mb-3">
                    <i class="fa-solid fa-utensils"></i>
                </div>
                <h2 class="text-lg font-black text-slate-800">Selamat Datang di {{ $table->table_number }}!</h2>
                <p class="text-xs text-slate-500 mt-1 max-w-xs mx-auto">
                    Silakan masukkan nama dan nomor telepon Anda agar staf kami dapat mengantarkan pesanan ke meja ini.
                </p>
            </div>

            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 space-y-4 shadow-sm">
                <div>
                    <label class="block text-xs font-black text-slate-700 mb-1">
                        Nama Pemesan <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <i class="fa-solid fa-user absolute left-3 top-3 text-slate-400 text-xs"></i>
                        <input type="text" x-model="customerName" placeholder="Contoh: Budi Santoso"
                            class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-700 mb-1">
                        Nomor WhatsApp / Telepon <span class="text-slate-400 font-normal">(opsional)</span>
                    </label>
                    <div class="relative">
                        <i class="fa-solid fa-phone absolute left-3 top-3 text-slate-400 text-xs"></i>
                        <input type="tel" x-model="customerPhone" placeholder="Contoh: 08123456789"
                            class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">Dapat digunakan untuk notifikasi status masakan.</p>
                </div>

                <button @click="startOrdering()"
                    :disabled="!customerName.trim()"
                    class="w-full py-3 bg-amber-500 hover:bg-amber-600 active:bg-amber-700 disabled:opacity-50 text-brand-navy font-black text-xs rounded-xl shadow-md transition flex items-center justify-center gap-2">
                    <span>Mulai Pilih Menu Makanan</span>
                    <i class="fa-solid fa-arrow-right text-xs"></i>
                </button>
            </div>
        </main>

        <!-- ======================================================== -->
        <!-- SCREEN 2: DAFTAR MENU & PILIH MAKANAN                     -->
        <!-- ======================================================== -->
        <main class="flex-1 pb-24 overflow-y-auto" x-show="step === 'menu'">
            
            <!-- Salam Pelanggan & Tombol Ganti Nama -->
            <div class="bg-amber-50 border-b border-amber-200/60 px-4 py-2.5 flex items-center justify-between text-xs">
                <span class="font-bold text-slate-700 flex items-center gap-1.5">
                    <i class="fa-regular fa-smile text-amber-500"></i>
                    Halo, <span class="text-amber-600" x-text="customerName"></span>
                </span>
                <button @click="step = 'customer'" class="text-[11px] font-semibold text-slate-500 hover:text-slate-800 underline">
                    Ubah
                </button>
            </div>

            <!-- Search Bar -->
            <div class="p-3 sticky top-0 bg-white/95 backdrop-blur z-20 border-b border-slate-100">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-slate-400 text-xs"></i>
                    <input type="text" x-model="searchQuery" placeholder="Cari makanan, minuman, cemilan..."
                        class="w-full pl-9 pr-8 py-2 bg-slate-100 border border-slate-200 rounded-xl text-xs font-medium focus:outline-none focus:ring-2 focus:ring-amber-500 focus:bg-white transition">
                    <button x-show="searchQuery" @click="searchQuery = ''" class="absolute right-3 top-2.5 text-slate-400 text-xs">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>

            <!-- Category Pills -->
            <div class="px-3 py-2 flex gap-2 overflow-x-auto no-scrollbar border-b border-slate-100 bg-white">
                <button @click="selectedCategory = 'all'"
                    :class="selectedCategory === 'all' ? 'bg-amber-500 text-brand-navy shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition">
                    Semua Menu
                </button>
                @foreach($categories as $cat)
                <button @click="selectedCategory = {{ $cat->id }}"
                    :class="selectedCategory === {{ $cat->id }} ? 'bg-amber-500 text-brand-navy shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition">
                    {{ $cat->name }}
                </button>
                @endforeach
            </div>

            <!-- Product Grid -->
            <div class="p-3 grid grid-cols-2 gap-3">
                <template x-for="item in filteredProducts" :key="item.id">
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition overflow-hidden flex flex-col justify-between">
                        <!-- Product Icon / Image -->
                        <div class="h-28 bg-gradient-to-br from-slate-100 to-amber-50/50 relative flex items-center justify-center p-2">
                            <template x-if="item.image">
                                <img :src="item.image" class="w-full h-full object-cover rounded-xl" :alt="item.name">
                            </template>
                            <template x-if="!item.image">
                                <div class="w-14 h-14 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center text-2xl shadow-inner">
                                    <i class="fa-solid fa-bowl-food"></i>
                                </div>
                            </template>

                            <!-- Stock Badge -->
                            <span class="absolute top-2 right-2 text-[10px] font-black px-1.5 py-0.5 rounded-md shadow-sm"
                                :class="item.stock > 5 ? 'bg-slate-800 text-white' : 'bg-rose-500 text-white animate-pulse'"
                                x-text="'Sisa ' + item.stock">
                            </span>
                        </div>

                        <!-- Content -->
                        <div class="p-2.5 flex-1 flex flex-col justify-between">
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block" x-text="item.category ? item.category.name : 'Menu'"></span>
                                <h3 class="text-xs font-black text-slate-800 leading-snug line-clamp-2 mt-0.5" x-text="item.name"></h3>
                            </div>

                            <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between">
                                <span class="text-xs font-black text-brand-navy" x-text="formatRupiah(item.price)"></span>

                                <!-- Action Add / Stepper -->
                                <div>
                                    <template x-if="getCartQty(item.id) === 0">
                                        <button @click="addToCart(item)"
                                            :disabled="item.stock <= 0"
                                            class="px-2.5 py-1 bg-amber-500 hover:bg-amber-600 active:bg-amber-700 disabled:opacity-40 text-brand-navy text-[11px] font-black rounded-lg shadow-sm transition">
                                            + Tambah
                                        </button>
                                    </template>
                                    <template x-if="getCartQty(item.id) > 0">
                                        <div class="flex items-center gap-1.5 bg-amber-50 border border-amber-300 rounded-lg p-0.5">
                                            <button @click="decreaseCart(item.id)" class="w-5 h-5 rounded bg-white text-slate-700 font-bold flex items-center justify-center text-xs shadow-sm hover:bg-slate-100">-</button>
                                            <span class="text-xs font-black text-brand-navy px-1" x-text="getCartQty(item.id)"></span>
                                            <button @click="addToCart(item)" class="w-5 h-5 rounded bg-amber-500 text-brand-navy font-bold flex items-center justify-center text-xs shadow-sm hover:bg-amber-600">+</button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Empty State -->
            <div x-show="filteredProducts.length === 0" class="py-16 text-center text-slate-400">
                <i class="fa-solid fa-search text-3xl mb-2 text-slate-300"></i>
                <p class="text-xs font-semibold">Tidak ada menu yang cocok</p>
            </div>
        </main>

        <!-- ======================================================== -->
        <!-- FLOATING BOTTOM BAR (Saat Keranjang Berisi)              -->
        <!-- ======================================================== -->
        <div class="fixed bottom-0 left-0 right-0 max-w-md mx-auto p-3 bg-white/95 backdrop-blur border-t border-slate-200 z-30"
            x-show="step === 'menu' && totalCartItems > 0"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-y-full"
            x-transition:enter-end="translate-y-0">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider" x-text="totalCartItems + ' Porsi Dipilih'"></p>
                    <p class="text-sm font-black text-brand-navy" x-text="formatRupiah(cartSubtotal)"></p>
                </div>
                <button @click="step = 'cart'"
                    class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 active:bg-amber-700 text-brand-navy font-black text-xs rounded-xl shadow-lg transition flex items-center gap-2">
                    <span>Lihat Keranjang</span>
                    <i class="fa-solid fa-cart-shopping text-xs"></i>
                </button>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- SCREEN 3: KERANJANG PESANAN & REVIEW                      -->
        <!-- ======================================================== -->
        <main class="flex-1 p-4 pb-28 overflow-y-auto" x-show="step === 'cart'">
            <!-- Header Keranjang -->
            <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                <button @click="step = 'menu'" class="flex items-center gap-1.5 text-xs font-bold text-slate-600 hover:text-slate-900">
                    <i class="fa-solid fa-chevron-left text-xs"></i>
                    <span>Tambah Menu Lain</span>
                </button>
                <h2 class="text-xs font-black text-slate-800 uppercase tracking-wider">Keranjang Pesanan</h2>
            </div>

            <!-- List Item Keranjang -->
            <div class="space-y-3 mb-5">
                <template x-for="item in cart" :key="item.product.id">
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 flex flex-col gap-2">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-xs font-black text-slate-800" x-text="item.product.name"></h4>
                                <p class="text-[11px] font-semibold text-slate-400" x-text="formatRupiah(item.product.price) + ' x ' + item.quantity"></p>
                            </div>
                            <span class="text-xs font-black text-brand-navy" x-text="formatRupiah(item.product.price * item.quantity)"></span>
                        </div>

                        <!-- Stepper & Catatan -->
                        <div class="flex items-center justify-between pt-2 border-t border-slate-200/60">
                            <input type="text" x-model="item.notes" placeholder="Catatan koki (misal: pedas)"
                                class="w-3/5 text-[11px] px-2 py-1 bg-white border border-slate-200 rounded-lg focus:outline-none focus:border-amber-500">
                            
                            <div class="flex items-center gap-1.5 bg-white border border-slate-200 rounded-lg p-0.5">
                                <button @click="decreaseCart(item.product.id)" class="w-6 h-6 rounded bg-slate-100 text-slate-700 font-bold flex items-center justify-center text-xs hover:bg-slate-200">-</button>
                                <span class="text-xs font-black text-brand-navy px-1.5" x-text="item.quantity"></span>
                                <button @click="addToCart(item.product)" class="w-6 h-6 rounded bg-amber-500 text-brand-navy font-bold flex items-center justify-center text-xs hover:bg-amber-600">+</button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Catatan Global Meja -->
            <div class="mb-5">
                <label class="block text-xs font-black text-slate-700 mb-1">Catatan Tambahan untuk Meja</label>
                <textarea x-model="orderNotes" rows="2" placeholder="Contoh: Minta sendok garpu lebih, saus dipisah..."
                    class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500"></textarea>
            </div>

            <!-- Ringkasan Rincian Biaya -->
            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 space-y-2 text-xs">
                <div class="flex justify-between text-slate-500">
                    <span>Subtotal Pesanan</span>
                    <span class="font-bold text-slate-700" x-text="formatRupiah(cartSubtotal)"></span>
                </div>
                <div class="flex justify-between text-slate-500">
                    <span>Pajak Restoran PB1 (10%)</span>
                    <span class="font-bold text-slate-700" x-text="formatRupiah(cartTax)"></span>
                </div>
                <div class="flex justify-between text-slate-800 text-sm font-black pt-2 border-t border-slate-200">
                    <span>Total Pembayaran</span>
                    <span class="text-brand-navy text-base" x-text="formatRupiah(cartTotal)"></span>
                </div>
            </div>

            <!-- Tombol Lanjut Bayar -->
            <button @click="step = 'payment'"
                class="w-full mt-5 py-3 bg-amber-500 hover:bg-amber-600 active:bg-amber-700 text-brand-navy font-black text-xs rounded-xl shadow-lg transition flex items-center justify-center gap-2">
                <span>Lanjut ke Pembayaran</span>
                <i class="fa-solid fa-wallet text-xs"></i>
            </button>
        </main>

        <!-- ======================================================== -->
        <!-- SCREEN 4: SIMULASI PEMBAYARAN                             -->
        <!-- ======================================================== -->
        <main class="flex-1 p-4 pb-20 overflow-y-auto" x-show="step === 'payment'">
            <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                <button @click="step = 'cart'" class="flex items-center gap-1.5 text-xs font-bold text-slate-600 hover:text-slate-900">
                    <i class="fa-solid fa-chevron-left text-xs"></i>
                    <span>Kembali ke Keranjang</span>
                </button>
                <h2 class="text-xs font-black text-slate-800 uppercase tracking-wider">Metode Pembayaran</h2>
            </div>

            <!-- Total Box -->
            <div class="bg-brand-navy text-white rounded-2xl p-4 mb-4 text-center shadow-md">
                <p class="text-[11px] text-slate-300 font-semibold">Total Tagihan {{ $table->table_number }}</p>
                <p class="text-2xl font-black text-amber-400 mt-0.5" x-text="formatRupiah(cartTotal)"></p>
                <p class="text-[10px] text-slate-400 mt-1">Atas Nama: <span class="text-white font-bold" x-text="customerName"></span></p>
            </div>

            <!-- Pilihan Metode Pembayaran -->
            <p class="text-xs font-black text-slate-700 mb-2">Pilih Cara Pembayaran:</p>
            <div class="space-y-2.5 mb-6">
                <!-- QRIS -->
                <label @click="selectedPayment = 'qris'"
                    :class="selectedPayment === 'qris' ? 'border-amber-500 bg-amber-50/50 ring-2 ring-amber-500/20' : 'border-slate-200 bg-white'"
                    class="border rounded-xl p-3 flex items-center justify-between cursor-pointer transition shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-rose-50 border border-rose-200 text-rose-600 flex items-center justify-center text-sm font-bold">
                            <i class="fa-solid fa-qrcode"></i>
                        </div>
                        <div>
                            <p class="text-xs font-black text-slate-800">QRIS Instan</p>
                            <p class="text-[10px] text-slate-400">BCA Mobile, Gopay, OVO, Dana, ShopeePay</p>
                        </div>
                    </div>
                    <span class="w-4 h-4 rounded-full border-2 flex items-center justify-center"
                        :class="selectedPayment === 'qris' ? 'border-amber-500 bg-amber-500' : 'border-slate-300'">
                        <i x-show="selectedPayment === 'qris'" class="fa-solid fa-check text-brand-navy text-[9px]"></i>
                    </span>
                </label>

                <!-- GOPAY -->
                <label @click="selectedPayment = 'gopay'"
                    :class="selectedPayment === 'gopay' ? 'border-amber-500 bg-amber-50/50 ring-2 ring-amber-500/20' : 'border-slate-200 bg-white'"
                    class="border rounded-xl p-3 flex items-center justify-between cursor-pointer transition shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-600 flex items-center justify-center text-sm font-bold">
                            <i class="fa-solid fa-mobile-screen"></i>
                        </div>
                        <div>
                            <p class="text-xs font-black text-slate-800">GoPay</p>
                            <p class="text-[10px] text-slate-400">Pembayaran langsung via GoPay Saldo</p>
                        </div>
                    </div>
                    <span class="w-4 h-4 rounded-full border-2 flex items-center justify-center"
                        :class="selectedPayment === 'gopay' ? 'border-amber-500 bg-amber-500' : 'border-slate-300'">
                        <i x-show="selectedPayment === 'gopay'" class="fa-solid fa-check text-brand-navy text-[9px]"></i>
                    </span>
                </label>

                <!-- DANA / OVO -->
                <label @click="selectedPayment = 'dana'"
                    :class="selectedPayment === 'dana' ? 'border-amber-500 bg-amber-50/50 ring-2 ring-amber-500/20' : 'border-slate-200 bg-white'"
                    class="border rounded-xl p-3 flex items-center justify-between cursor-pointer transition shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-sky-50 border border-sky-200 text-sky-600 flex items-center justify-center text-sm font-bold">
                            <i class="fa-solid fa-wallet"></i>
                        </div>
                        <div>
                            <p class="text-xs font-black text-slate-800">DANA / OVO / ShopeePay</p>
                            <p class="text-[10px] text-slate-400">E-Wallet Indonesia</p>
                        </div>
                    </div>
                    <span class="w-4 h-4 rounded-full border-2 flex items-center justify-center"
                        :class="selectedPayment === 'dana' ? 'border-amber-500 bg-amber-500' : 'border-slate-300'">
                        <i x-show="selectedPayment === 'dana'" class="fa-solid fa-check text-brand-navy text-[9px]"></i>
                    </span>
                </label>

                <!-- BAYAR DI KASIR -->
                <label @click="selectedPayment = 'cashier'"
                    :class="selectedPayment === 'cashier' ? 'border-amber-500 bg-amber-50/50 ring-2 ring-amber-500/20' : 'border-slate-200 bg-white'"
                    class="border rounded-xl p-3 flex items-center justify-between cursor-pointer transition shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-slate-100 border border-slate-300 text-slate-700 flex items-center justify-center text-sm font-bold">
                            <i class="fa-solid fa-cash-register"></i>
                        </div>
                        <div>
                            <p class="text-xs font-black text-slate-800">Bayar di Kasir (Tunai)</p>
                            <p class="text-[10px] text-slate-400">Pesan dulu, bayar ke kasir nanti</p>
                        </div>
                    </div>
                    <span class="w-4 h-4 rounded-full border-2 flex items-center justify-center"
                        :class="selectedPayment === 'cashier' ? 'border-amber-500 bg-amber-500' : 'border-slate-300'">
                        <i x-show="selectedPayment === 'cashier'" class="fa-solid fa-check text-brand-navy text-[9px]"></i>
                    </span>
                </label>
            </div>

            <!-- TAMPILAN QRIS SIMULASI (JIKA PILIH QRIS/E-WALLET) -->
            <div x-show="selectedPayment !== 'cashier'" class="bg-slate-50 border border-slate-200 rounded-2xl p-4 text-center mb-5">
                <p class="text-[11px] font-black uppercase text-slate-500 tracking-wider">Simulasi QRIS Pembayaran</p>
                <div class="w-44 h-44 mx-auto my-3 bg-white p-2.5 rounded-xl border border-slate-300 shadow-sm flex items-center justify-center">
                    <img :src="'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=BAKNUS-DRESTO-QRIS-' + cartTotal" class="w-full h-full object-contain" alt="QRIS">
                </div>
                <p class="text-[10px] text-slate-400">Scan QRIS di atas atau klik tombol simulasi di bawah untuk verifikasi instan.</p>
            </div>

            <!-- TOMBOL EKSEKUSI / SIMULASI PEMBAYARAN -->
            <button @click="processPayment()"
                :disabled="isSubmitting"
                class="w-full py-3.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 disabled:opacity-50 text-white font-black text-xs rounded-xl shadow-lg transition flex items-center justify-center gap-2">
                <i class="fa-solid fa-bolt text-amber-300" x-show="!isSubmitting"></i>
                <i class="fa-solid fa-spinner fa-spin" x-show="isSubmitting"></i>
                <span x-text="selectedPayment === 'cashier' ? 'Kirim Pesanan ke Kasir' : 'Simulasikan Pembayaran Berhasil (Ujikom)'"></span>
            </button>
        </main>

        <!-- ======================================================== -->
        <!-- SCREEN 5: NOTIFIKASI PESANAN BERHASIL & STATUS KOKI      -->
        <!-- ======================================================== -->
        <main class="flex-1 p-5 flex flex-col justify-center text-center" x-show="step === 'success'">
            <div class="w-20 h-20 bg-emerald-100 text-emerald-600 rounded-full mx-auto flex items-center justify-center text-3xl shadow-lg mb-4 animate-bounce">
                <i class="fa-solid fa-check"></i>
            </div>

            <h2 class="text-xl font-black text-slate-800">Pesanan Berhasil!</h2>
            <p class="text-xs text-slate-500 mt-1" x-text="orderResult.message"></p>

            <div class="my-5 bg-slate-50 border border-slate-200 rounded-2xl p-4 text-left space-y-2 text-xs">
                <div class="flex justify-between">
                    <span class="text-slate-400">Nomor Pesanan</span>
                    <span class="font-black text-brand-navy" x-text="'#' + orderResult.order_number"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Meja</span>
                    <span class="font-black text-slate-700">{{ $table->table_number }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Status Pembayaran</span>
                    <span class="font-black" :class="orderResult.is_paid ? 'text-emerald-600' : 'text-amber-600'"
                        x-text="orderResult.is_paid ? 'LUNAS (Digital)' : 'Menunggu Bayar di Kasir'"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Total Bayar</span>
                    <span class="font-black text-brand-navy" x-text="formatRupiah(orderResult.total_amount)"></span>
                </div>
                <div class="pt-2 border-t border-slate-200 flex items-center justify-between text-slate-500">
                    <span class="flex items-center gap-1">
                        <i class="fa-solid fa-fire-burner text-amber-500"></i>
                        Status Dapur:
                    </span>
                    <span class="font-bold text-amber-600">Sedang Dimasak Koki</span>
                </div>
            </div>

            <div class="space-y-2">
                <button @click="resetForNewOrder()"
                    class="w-full py-3 bg-brand-navy hover:bg-brand-navylight text-white font-black text-xs rounded-xl shadow-md transition">
                    Pesan Menu Tambahan
                </button>
            </div>
        </main>

    </div>

    <!-- SCRIPT ALPINE.JS LOGIC -->
    <script>
        function tableOrderApp() {
            return {
                step: 'customer', // 'customer', 'menu', 'cart', 'payment', 'success'
                tableId: {{ $table->id }},
                customerName: localStorage.getItem('baknus_customer_name') || '',
                customerPhone: localStorage.getItem('baknus_customer_phone') || '',
                searchQuery: '',
                selectedCategory: 'all',
                selectedPayment: 'qris',
                orderNotes: '',
                isSubmitting: false,
                orderResult: {
                    order_number: '',
                    message: '',
                    is_paid: false,
                    total_amount: 0
                },

                // Data produk dari backend
                products: @json($allProducts),

                // Keranjang belanja
                cart: [],

                init() {
                    // Jika nama pemesan sudah tersimpan, langsung masuk ke menu
                    if (this.customerName.trim().length > 0) {
                        this.step = 'menu';
                    }
                },

                startOrdering() {
                    if (!this.customerName.trim()) return;
                    localStorage.setItem('baknus_customer_name', this.customerName.trim());
                    if (this.customerPhone) {
                        localStorage.setItem('baknus_customer_phone', this.customerPhone.trim());
                    }
                    this.step = 'menu';
                },

                get filteredProducts() {
                    return this.products.filter(item => {
                        const matchCat = this.selectedCategory === 'all' || item.category_id === this.selectedCategory;
                        const matchSearch = !this.searchQuery.trim() || 
                            item.name.toLowerCase().includes(this.searchQuery.toLowerCase());
                        return matchCat && matchSearch;
                    });
                },

                getCartQty(productId) {
                    const found = this.cart.find(c => c.product.id === productId);
                    return found ? found.quantity : 0;
                },

                addToCart(product) {
                    const existing = this.cart.find(c => c.product.id === product.id);
                    if (existing) {
                        if (existing.quantity + 1 > product.stock) {
                            alert('Pesanan melebihi sisa stok yang tersedia (' + product.stock + ' porsi).');
                            return;
                        }
                        existing.quantity++;
                    } else {
                        if (product.stock <= 0) {
                            alert('Maaf, stok menu ini sedang habis.');
                            return;
                        }
                        this.cart.push({
                            product: product,
                            quantity: 1,
                            notes: ''
                        });
                    }
                },

                decreaseCart(productId) {
                    const idx = this.cart.findIndex(c => c.product.id === productId);
                    if (idx !== -1) {
                        if (this.cart[idx].quantity > 1) {
                            this.cart[idx].quantity--;
                        } else {
                            this.cart.splice(idx, 1);
                        }
                    }
                },

                get totalCartItems() {
                    return this.cart.reduce((sum, item) => sum + item.quantity, 0);
                },

                get cartSubtotal() {
                    return this.cart.reduce((sum, item) => sum + (item.product.price * item.quantity), 0);
                },

                get cartTax() {
                    return Math.round(this.cartSubtotal * 0.10);
                },

                get cartTotal() {
                    return this.cartSubtotal + this.cartTax;
                },

                formatRupiah(num) {
                    return 'Rp ' + (num || 0).toLocaleString('id-ID');
                },

                async processPayment() {
                    if (this.cart.length === 0) return;
                    this.isSubmitting = true;

                    const payload = {
                        customer_name: this.customerName,
                        customer_phone: this.customerPhone,
                        notes: this.orderNotes,
                        payment_method: this.selectedPayment,
                        items: this.cart.map(c => ({
                            product_id: c.product.id,
                            quantity: c.quantity,
                            notes: c.notes
                        }))
                    };

                    try {
                        const res = await fetch(`/order/meja/${this.tableId}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(payload)
                        });

                        const data = await res.json();
                        if (data.success) {
                            this.orderResult = data;
                            this.cart = [];
                            this.orderNotes = '';
                            this.step = 'success';
                        } else {
                            alert(data.message || 'Gagal memproses pesanan.');
                        }
                    } catch (err) {
                        alert('Terjadi kesalahan jaringan saat mengirim pesanan.');
                    } finally {
                        this.isSubmitting = false;
                    }
                },

                resetForNewOrder() {
                    this.step = 'menu';
                }
            }
        }
    </script>
</body>
</html>
