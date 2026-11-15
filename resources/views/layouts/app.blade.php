<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Baknus Dresto — Restaurant POS</title>
    <!-- Favicon Resmi Baknus Dresto -->
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo.png') }}">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            dark: '#0c233c',
                            sidebar: '#0e2946',
                            sidebarHover: '#173c66',
                            active: '#1e4875',
                            accent: '#f59e0b',
                            accentHover: '#d97706',
                            cardGrad1: '#b47b2b',
                            cardGrad2: '#20556f'
                        }
                    }
                }
            }
        }
    </script>
    <!-- Google Fonts & FontAwesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Chart.js CDN untuk Grafik Manajer -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f1f5f9;
        }
        /* Custom scrollbars */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.15);
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(0, 0, 0, 0.25);
        }
        [x-cloak] { display: none !important; }
    </style>

    <script>
        // Global Rupiah Formatter
        window.formatRupiah = function(number) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(number || 0);
        };
    </script>
</head>
@php
    $activeUserId = session('active_user_id');
    $currentUser = $activeUserId ? \App\Models\User::find($activeUserId) : \App\Models\User::first();
    if (!$currentUser) {
        $currentUser = new \App\Models\User(['name' => 'Alex Morgan', 'role' => 'admin']);
    }
    $allUsers = \App\Models\User::where('is_active', true)->get();
@endphp
<body class="bg-slate-100 text-slate-800 antialiased overflow-hidden select-none">
    <div class="flex h-screen w-screen overflow-hidden">
        
        <!-- SIDEBAR (Left) -->
        <aside class="w-64 bg-[#0e2a47] text-slate-300 flex flex-col justify-between flex-shrink-0 z-20 shadow-xl border-r border-[#1a3d63]">
            <!-- Top Logo & Restaurant Info -->
            <div>
                <div class="h-16 flex items-center px-4 gap-3 border-b border-[#1b3e64] bg-[#0c243e]">
                    <img src="{{ asset('images/logo.png') }}" alt="Baknus Dresto Logo" class="w-10 h-10 rounded-xl object-contain shadow-md border border-white/10">
                    <div>
                        <h1 class="font-black text-white text-base tracking-wide leading-tight">Baknus Dresto</h1>
                        <p class="text-[10px] text-amber-400 font-bold tracking-wider uppercase">Restaurant POS</p>
                    </div>
                </div>

                <!-- Navigation Links -->
                <div class="p-3 space-y-6 overflow-y-auto max-h-[calc(100vh-140px)]">
                    <!-- OPERATIONS -->
                    <div>
                        <span class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">Operations</span>
                        <div class="mt-2 space-y-1">
                            <a href="{{ route('pos.index') }}" 
                               class="flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-semibold transition-all {{ request()->routeIs('pos.*') ? 'bg-[#1b436e] text-white shadow' : 'hover:bg-[#16385d] text-slate-300' }}">
                                <div class="flex items-center gap-3">
                                    <i class="fa-solid fa-desktop w-4 text-center {{ request()->routeIs('pos.*') ? 'text-amber-400' : 'text-slate-400' }}"></i>
                                    <span>POS / Billing</span>
                                </div>
                            </a>

                            <button onclick="window.dispatchEvent(new CustomEvent('open-held-modal'))" 
                               class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-semibold hover:bg-[#16385d] text-slate-300 transition-all text-left">
                                <div class="flex items-center gap-3">
                                    <i class="fa-solid fa-clock-rotate-left w-4 text-center text-slate-400"></i>
                                    <span>Held Orders</span>
                                </div>
                                <span class="bg-amber-500 text-slate-950 font-extrabold text-xs px-2 py-0.5 rounded-full" id="sidebar-held-count">
                                    {{ \App\Models\Order::where('status', 'held')->count() }}
                                </span>
                            </button>

                            <a href="{{ route('tables.index') }}" 
                               class="flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-semibold transition-all {{ request()->routeIs('tables.*') ? 'bg-[#1b436e] text-white shadow' : 'hover:bg-[#16385d] text-slate-300' }}">
                                <div class="flex items-center gap-3">
                                    <i class="fa-solid fa-chair w-4 text-center {{ request()->routeIs('tables.*') ? 'text-amber-400' : 'text-slate-400' }}"></i>
                                    <span>Tables</span>
                                </div>
                            </a>

                            <a href="{{ route('kitchen.index') }}" 
                               class="flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-semibold transition-all {{ request()->routeIs('kitchen.*') ? 'bg-[#1b436e] text-white shadow' : 'hover:bg-[#16385d] text-slate-300' }}">
                                <div class="flex items-center gap-3">
                                    <i class="fa-solid fa-fire-burner w-4 text-center {{ request()->routeIs('kitchen.*') ? 'text-amber-400' : 'text-slate-400' }}"></i>
                                    <span>KOT / Kitchen Display</span>
                                </div>
                            </a>

                            <a href="{{ route('bills.index') }}" 
                               class="flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-semibold transition-all {{ request()->routeIs('bills.*') ? 'bg-[#1b436e] text-white shadow' : 'hover:bg-[#16385d] text-slate-300' }}">
                                <div class="flex items-center gap-3">
                                    <i class="fa-solid fa-receipt w-4 text-center {{ request()->routeIs('bills.*') ? 'text-amber-400' : 'text-slate-400' }}"></i>
                                    <span>Bills / Riwayat</span>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- INSIGHTS -->
                    <div>
                        <span class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">Insights</span>
                        <div class="mt-2 space-y-1">
                            <a href="{{ route('dashboard.index') }}" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition-all {{ request()->routeIs('dashboard.*') ? 'bg-[#1b436e] text-white shadow' : 'hover:bg-[#16385d] text-slate-300' }}">
                                <i class="fa-solid fa-chart-pie w-4 text-center {{ request()->routeIs('dashboard.*') ? 'text-amber-400' : 'text-slate-400' }}"></i>
                                <span>Dashboard</span>
                            </a>
                        </div>
                    </div>

                    <!-- CATALOG & STOCK -->
                    <div>
                        <span class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">Catalog & Stock</span>
                        <div class="mt-2 space-y-1">
                            <a href="{{ route('products.index') }}" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition-all {{ request()->routeIs('products.*') ? 'bg-[#1b436e] text-white shadow' : 'hover:bg-[#16385d] text-slate-300' }}">
                                <i class="fa-solid fa-box-open w-4 text-center {{ request()->routeIs('products.*') ? 'text-amber-400' : 'text-slate-400' }}"></i>
                                <span>Products / Menu</span>
                            </a>
                            <a href="{{ route('inventory.index') }}" 
                               class="flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-semibold transition-all {{ request()->routeIs('inventory.*') ? 'bg-[#1b436e] text-white shadow' : 'hover:bg-[#16385d] text-slate-300' }}">
                                <div class="flex items-center gap-3">
                                    <i class="fa-solid fa-warehouse w-4 text-center {{ request()->routeIs('inventory.*') ? 'text-amber-400' : 'text-slate-400' }}"></i>
                                    <span>Inventory & Stok</span>
                                </div>
                                @php
                                    $lowCount = \App\Models\Product::where('stock', '<=', 10)->count();
                                @endphp
                                @if($lowCount > 0)
                                    <span class="bg-amber-500 text-slate-950 font-black text-[10px] px-1.5 py-0.5 rounded-full" title="{{ $lowCount }} menu stok menipis">
                                        {{ $lowCount }}
                                    </span>
                                @endif
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom User & Shift Bar -->
            <div class="p-4 border-t border-[#1b3e64] bg-[#0c243e] flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-slate-950 font-bold text-sm shadow {{ $currentUser->role === 'manager' ? 'bg-indigo-400' : 'bg-amber-500' }}">
                        {{ strtoupper(substr($currentUser->name, 0, 2)) }}
                    </div>
                    <div>
                        <p class="text-xs font-bold text-white leading-tight">{{ $currentUser->name }}</p>
                        <p class="text-[11px] font-semibold uppercase tracking-wider {{ $currentUser->role === 'manager' ? 'text-indigo-300' : 'text-amber-400' }}">
                            {{ $currentUser->role === 'manager' ? 'Resto Manager' : ($currentUser->role === 'admin' ? 'Cashier Admin' : 'Kasir') }}
                        </p>
                    </div>
                </div>
                <div class="w-2.5 h-2.5 rounded-full {{ $currentUser->role === 'manager' ? 'bg-indigo-400 shadow-[0_0_8px_rgba(129,140,248,0.8)]' : 'bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.8)]' }}" title="Akun Aktif"></div>
            </div>

            <!-- Logout Button in Sidebar -->
            <div class="p-3 border-t border-[#1b3e64] bg-[#091d31]">
                <a href="{{ route('logout') }}" 
                   onclick="return confirm('Apakah Anda yakin ingin logout dari sistem?')"
                   class="w-full flex items-center justify-center gap-2 py-2 rounded-lg bg-rose-500/10 hover:bg-rose-500 text-rose-300 hover:text-white transition-all text-xs font-bold border border-rose-500/20 shadow-sm">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    <span>Keluar / Logout</span>
                </a>
            </div>
        </aside>

        <!-- MAIN VIEWPORT (Topbar + Content Area) -->
        <div class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-100">
            <!-- Topbar (Sesuai Screenshot) -->
            <header class="h-16 bg-[#0e2a47] text-white flex items-center justify-between px-6 border-b border-[#1b3e64] shadow-sm flex-shrink-0 z-10">
                <div class="flex items-center gap-4">
                    <button class="text-slate-300 hover:text-white transition">
                        <i class="fa-solid fa-bars text-lg"></i>
                    </button>
                    @if($currentUser->role === 'manager')
                        <div class="hidden sm:flex items-center gap-2 bg-indigo-900/60 px-3 py-1.5 rounded-full text-xs font-semibold text-indigo-200 border border-indigo-700">
                            <span class="w-2 h-2 rounded-full bg-indigo-400"></span>
                            <span>Mode: Manajer (Pemantau & Analitik)</span>
                        </div>
                    @else
                        <div class="hidden sm:flex items-center gap-2 bg-[#16385d] px-3 py-1.5 rounded-full text-xs font-semibold text-slate-200 border border-[#214b78]">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span>Shift: Pagi (Kasir Aktif)</span>
                        </div>
                    @endif
                </div>

                <div class="flex items-center gap-3">
                    <!-- Dropdown Ganti Akun / Switch User Cepat -->
                    <div class="flex items-center gap-1.5 bg-[#16385d] border border-[#214b78] rounded-xl px-2 py-1 text-xs">
                        <i class="fa-solid fa-users text-slate-400 text-[10px]"></i>
                        <span class="text-slate-400 text-[11px] font-medium hidden md:inline">Akun:</span>
                        <select onchange="window.location.href='/switch-user/'+this.value" class="bg-transparent text-white text-xs font-bold focus:outline-none cursor-pointer">
                            @foreach($allUsers as $u)
                                <option value="{{ $u->id }}" {{ $currentUser->id === $u->id ? 'selected' : '' }} class="bg-[#0e2a47] text-white">
                                    {{ $u->name }} ({{ ucfirst($u->role) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button onclick="document.documentElement.requestFullscreen?.()" class="w-9 h-9 rounded-full bg-[#16385d] hover:bg-[#1e4875] flex items-center justify-center text-slate-200 transition text-sm shadow" title="Fullscreen">
                        <i class="fa-solid fa-expand"></i>
                    </button>
                    <button onclick="window.location.reload()" class="w-9 h-9 rounded-full bg-[#16385d] hover:bg-[#1e4875] flex items-center justify-center text-slate-200 transition text-sm shadow" title="Refresh">
                        <i class="fa-solid fa-rotate-right"></i>
                    </button>
                    <div class="w-9 h-9 rounded-full bg-[#16385d] flex items-center justify-center text-slate-200 text-sm shadow cursor-pointer" title="Dark Mode">
                        <i class="fa-solid fa-moon"></i>
                    </div>

                    <div class="flex items-center gap-3 pl-3 border-l border-[#1b3e64]">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs shadow text-slate-950 {{ $currentUser->role === 'manager' ? 'bg-indigo-300' : 'bg-amber-500' }}">
                            {{ strtoupper(substr($currentUser->name, 0, 1)) }}
                        </div>
                        <div class="hidden md:block">
                            <p class="text-xs font-bold leading-tight">{{ $currentUser->name }}</p>
                            <p class="text-[10px] text-slate-400 uppercase font-semibold">{{ $currentUser->role }}</p>
                        </div>
                        <a href="{{ route('logout') }}" 
                           onclick="return confirm('Apakah Anda yakin ingin logout?')"
                           class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-rose-500/20 hover:bg-rose-600 text-rose-200 hover:text-white text-xs font-bold border border-rose-500/30 transition shadow-sm" 
                           title="Logout dari akun">
                            <i class="fa-solid fa-power-off text-[11px]"></i>
                            <span class="hidden sm:inline">Logout</span>
                        </a>
                    </div>
                </div>
            </header>

            <!-- Main Dynamic Page Content -->
            <main class="flex-1 flex overflow-hidden">
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
    <!-- Alpine.js dimuat setelah scripts terdaftar -->
    <script src="{{ asset('js/alpine.min.js') }}"></script>
    <script>
        // Fallback jika script lokal belum ter-load dari asset
        if (typeof Alpine === 'undefined') {
            document.write('<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"><\/script>');
        }
    </script>
</body>
</html>
