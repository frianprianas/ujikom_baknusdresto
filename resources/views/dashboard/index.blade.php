@extends('layouts.app')

@section('content')
<div class="flex-1 flex flex-col h-full bg-slate-100 overflow-hidden">
    <!-- Header -->
    <div class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between flex-shrink-0">
        <div>
            <h2 class="text-base font-extrabold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-chart-line text-indigo-600"></i>
                <span>Dashboard & Pemantauan Manajer</span>
            </h2>
            <p class="text-xs text-slate-400">Ringkasan transaksi, grafik harian, stok bahan, dan best seller Baknus Dresto</p>
        </div>
        <div class="flex items-center gap-3">
            @if($currentUser->role === 'manager')
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 shadow-sm">
                    <i class="fa-solid fa-shield-halved text-indigo-600"></i>
                    <span>Hak Akses: Pengawas Manajer (Non-Kasir)</span>
                </span>
            @endif
            <a href="{{ route('inventory.index') }}" class="px-3.5 py-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 rounded-lg text-xs font-bold shadow-sm transition flex items-center gap-1.5 cursor-pointer">
                <i class="fa-solid fa-boxes-stacked"></i>
                <span>Kelola Stok & Restock</span>
            </a>
            <a href="{{ route('bills.index') }}" class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
                <i class="fa-solid fa-receipt"></i>
                <span>Riwayat Transaksi</span>
            </a>
        </div>
    </div>

    <!-- Dashboard Content -->
    <div class="flex-1 p-6 overflow-y-auto space-y-6">

        @if($currentUser->role === 'manager')
            <!-- Info Box Manager -->
            <div class="p-4 rounded-2xl bg-gradient-to-r from-indigo-900 to-[#0e2a47] text-white shadow-md flex items-center justify-between">
                <div class="flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-white/10 flex items-center justify-center text-xl text-amber-400 flex-shrink-0">
                        <i class="fa-solid fa-user-tie"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-extrabold text-white">Selamat Datang, {{ $currentUser->name }} (Manajer Restoran)</h4>
                        <p class="text-xs text-slate-200 mt-0.5">
                            Anda memiliki akses penuh untuk memantau grafik omzet, volume transaksi, perputaran stok, HPP laba kotor, dan menu terlaris.
                            Hak akses pembuatan transaksi kasir dinonaktifkan untuk menjaga integritas pembukuan kasir.
                        </p>
                    </div>
                </div>
                <div class="hidden md:flex items-center gap-2">
                    <span class="text-[11px] font-semibold bg-white/10 px-3 py-1 rounded-full text-slate-200 border border-white/10">
                        <i class="fa-solid fa-check-circle text-emerald-400 mr-1"></i> Audit Real-Time Aktif
                    </span>
                </div>
            </div>
        @endif

        <!-- Financial & Profit Overview -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Omzet Penjualan Hari Ini</span>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">Rp {{ number_format($totalSalesToday, 0, ',', '.') }}</h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">Total penerimaan kasir</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-500 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-coins"></i>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Modal Bahan (HPP)</span>
                    <h3 class="text-2xl font-black text-rose-600 mt-1">Rp {{ number_format($todayCostOfGoods, 0, ',', '.') }}</h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">Modal porsi yang terjual</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-500 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Estimasi Laba Kotor</span>
                    <h3 class="text-2xl font-black text-emerald-600 mt-1">Rp {{ number_format($todayGrossProfit, 0, ',', '.') }}</h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">Omzet &minus; Modal HPP</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-arrow-trend-up"></i>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Nilai Aset Stok Bahan</span>
                    <h3 class="text-2xl font-black text-sky-600 mt-1">Rp {{ number_format($totalStockAsset, 0, ',', '.') }}</h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">Modal tersimpan di dapur</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-500 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
            </div>
        </div>

        <!-- 3 Operational Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Transaksi Hari Ini</span>
                    <h3 class="text-xl font-black text-slate-900 mt-0.5">{{ $totalOrdersCount }} Pesanan</h3>
                </div>
                <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-receipt"></i>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Pesanan Aktif / Held</span>
                    <h3 class="text-xl font-black text-slate-900 mt-0.5">{{ $activeOrdersCount }} Pesanan</h3>
                </div>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-500 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Okupansi Meja</span>
                    <h3 class="text-xl font-black text-slate-900 mt-0.5">{{ $occupiedTablesCount }} / {{ $totalTablesCount }} Meja Terisi</h3>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-500 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-chair"></i>
                </div>
            </div>
        </div>

        <!-- VISUAL CHART: Grafik Pesanan & Omzet 7 Hari Terakhir -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-800 flex items-center gap-2">
                        <i class="fa-solid fa-chart-area text-indigo-600"></i>
                        <span>Grafik Pesanan & Omzet Harian (7 Hari Terakhir)</span>
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5">Analisis tren frekuensi pesanan vs total omzet restoran</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200">
                        <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span> Omzet Penjualan (Rp)
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Jumlah Pesanan
                    </span>
                </div>
            </div>
            <div class="w-full h-80 relative">
                <canvas id="dailyOrdersChart"></canvas>
            </div>
        </div>

        <!-- 3 Columns: Top Selling + Low Stock Alert + Recent Orders -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- 1. Top Selling Products (Best Seller) -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-extrabold text-slate-800 flex items-center gap-2">
                        <i class="fa-solid fa-fire text-amber-500"></i>
                        <span>Menu Terlaris (Best Seller)</span>
                    </h3>
                    <span class="text-[11px] font-bold text-slate-400">Total Penjualan</span>
                </div>
                <div class="space-y-2.5 flex-1 overflow-y-auto max-h-80">
                    @forelse($topProducts as $index => $top)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100 hover:border-amber-200 transition">
                            <div class="flex items-center gap-3">
                                <span class="w-6 h-6 rounded-lg flex items-center justify-center text-xs font-black {{ $index === 0 ? 'bg-amber-400 text-slate-950 shadow-sm' : ($index === 1 ? 'bg-slate-300 text-slate-900' : ($index === 2 ? 'bg-amber-700 text-white' : 'bg-slate-200 text-slate-600')) }}">
                                    #{{ $index + 1 }}
                                </span>
                                <div>
                                    <span class="text-xs font-bold text-slate-800 block">{{ $top->product_name }}</span>
                                    <span class="text-[11px] text-slate-500">
                                        Terjual: <strong class="text-slate-800">{{ $top->total_qty }}</strong> porsi
                                    </span>
                                </div>
                            </div>
                            <span class="text-xs font-black text-slate-900">Rp {{ number_format($top->total_revenue, 0, ',', '.') }}</span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 py-8 text-center">Belum ada riwayat penjualan</p>
                    @endforelse
                </div>
            </div>

            <!-- 2. Low Stock & Kitchen Warning -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-extrabold text-slate-800 flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation text-rose-500"></i>
                        <span>Pemantauan Stok Kritis</span>
                    </h3>
                    <a href="{{ route('inventory.index') }}" class="text-[11px] font-bold text-indigo-600 hover:underline">
                        Lihat Semua &rarr;
                    </a>
                </div>
                <div class="space-y-2.5 flex-1 overflow-y-auto max-h-80">
                    @forelse($lowStockProducts as $low)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <div>
                                <span class="text-xs font-bold text-slate-800 block">{{ $low->name }}</span>
                                <span class="text-[11px] text-slate-400">Modal: Rp {{ number_format($low->cost_price, 0, ',', '.') }}</span>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-black {{ $low->stock <= 5 ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-800' }}">
                                    Sisa {{ $low->stock }} {{ $low->unit }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center">
                            <i class="fa-solid fa-circle-check text-2xl text-emerald-500 mb-2"></i>
                            <p class="text-xs font-bold text-slate-600">Semua stok bahan aman</p>
                            <p class="text-[11px] text-slate-400">Tidak ada stok yang berada di bawah batas minimum.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- 3. Recent Orders Monitoring -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-extrabold text-slate-800 flex items-center gap-2">
                        <i class="fa-solid fa-clock-rotate-left text-sky-500"></i>
                        <span>Transaksi Terkini</span>
                    </h3>
                    <a href="{{ route('bills.index') }}" class="text-[11px] font-bold text-sky-600 hover:underline">
                        Semua Bill &rarr;
                    </a>
                </div>
                <div class="space-y-2.5 flex-1 overflow-y-auto max-h-80">
                    @forelse($recentOrders as $ro)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-slate-800">{{ $ro->order_number }}</span>
                                    <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full {{ $ro->status === 'completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                        {{ $ro->status }}
                                    </span>
                                </div>
                                <span class="text-[11px] text-slate-400 mt-0.5 block">
                                    {{ $ro->customer_name }} • {{ $ro->table ? $ro->table->table_number : 'Takeaway' }}
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-black text-slate-900 block">Rp {{ number_format($ro->total_amount, 0, ',', '.') }}</span>
                                <span class="text-[10px] text-slate-400">{{ $ro->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 py-8 text-center">Belum ada pesanan terbaru</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</div>

<!-- SCRIPT INISIALISASI CHART.JS GRAFIK HARIAN MANAJER -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('dailyOrdersChart');
    if (!ctx) return;

    const labels = @json($chartLabels);
    const orderCounts = @json($chartOrderCounts);
    const revenues = @json($chartRevenues);

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Omzet Penjualan (Rp)',
                    data: revenues,
                    type: 'line',
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    borderWidth: 3,
                    tension: 0.35,
                    fill: true,
                    yAxisID: 'yRevenues',
                    pointBackgroundColor: '#4f46e5',
                    pointRadius: 5,
                    pointHoverRadius: 7
                },
                {
                    label: 'Jumlah Pesanan',
                    data: orderCounts,
                    type: 'bar',
                    backgroundColor: 'rgba(245, 158, 11, 0.85)',
                    hoverBackgroundColor: '#f59e0b',
                    borderRadius: 8,
                    yAxisID: 'yOrders',
                    barThickness: 28
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (context.dataset.yAxisID === 'yRevenues') {
                                return label + ': Rp ' + new Intl.NumberFormat('id-ID').format(context.raw);
                            }
                            return label + ': ' + context.raw + ' transaksi';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false }
                },
                yRevenues: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    grid: { color: 'rgba(0,0,0,0.05)' },
                    ticks: {
                        callback: function(val) {
                            if (val >= 1000000) return 'Rp ' + (val / 1000000).toFixed(1) + ' jt';
                            if (val >= 1000) return 'Rp ' + (val / 1000).toFixed(0) + ' rb';
                            return 'Rp ' + val;
                        }
                    }
                },
                yOrders: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: { drawOnChartArea: false },
                    ticks: {
                        precision: 0,
                        stepSize: 1
                    }
                }
            }
        }
    });
});
</script>
@endsection
