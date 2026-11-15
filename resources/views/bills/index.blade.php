@extends('layouts.app')

@section('content')
<div class="flex-1 flex flex-col h-full bg-slate-100 overflow-hidden">
    <!-- Header -->
    <div class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between flex-shrink-0">
        <div>
            <h2 class="text-base font-extrabold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-receipt text-amber-500"></i>
                <span>Bills & Riwayat Transaksi</span>
            </h2>
            <p class="text-xs text-slate-400">Daftar struk, nota, dan faktur yang telah selesai dibayar</p>
        </div>
    </div>

    <!-- Bills Table -->
    <div class="flex-1 p-6 overflow-y-auto">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-black text-[10px] tracking-wider">
                    <tr>
                        <th class="py-3 px-4">Invoice / Order</th>
                        <th class="py-3 px-4">Waktu</th>
                        <th class="py-3 px-4">Tipe & Meja</th>
                        <th class="py-3 px-4">Kasir</th>
                        <th class="py-3 px-4">Metode Bayar</th>
                        <th class="py-3 px-4 text-right">Total</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($orders as $order)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3.5 px-4">
                                <span class="font-extrabold text-slate-900 block">{{ $order->payment->invoice_number ?? '-' }}</span>
                                <span class="text-[10px] text-slate-400">{{ $order->order_number }}</span>
                            </td>
                            <td class="py-3.5 px-4 text-slate-500">
                                {{ $order->created_at->format('d M Y, H:i') }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="uppercase text-[10px] font-black px-2 py-0.5 rounded-full bg-slate-100 text-slate-700">
                                    {{ $order->order_type }}
                                </span>
                                <span class="text-slate-500 text-xs ml-1">
                                    {{ $order->table ? $order->table->table_number : 'Walk-in' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-slate-800">
                                @if(str_starts_with($order->order_number, 'MOB-') || is_null($order->user_id))
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold bg-purple-100 text-purple-800">
                                        <i class="fa-solid fa-mobile-screen text-[10px]"></i> Mobile Apps
                                    </span>
                                @else
                                    {{ $order->user->name ?? 'Kasir' }}
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                @php
                                    $pm = strtolower($order->payment->payment_method ?? 'cash');
                                    $badgeClass = match($pm) {
                                        'gopay' => 'bg-emerald-100 text-emerald-800 border border-emerald-200',
                                        'ovo' => 'bg-purple-100 text-purple-800 border border-purple-200',
                                        'dana' => 'bg-sky-100 text-sky-800 border border-sky-200',
                                        'shopeepay' => 'bg-amber-100 text-amber-800 border border-amber-200',
                                        'qris' => 'bg-teal-100 text-teal-800 border border-teal-200',
                                        'cash' => 'bg-emerald-100 text-emerald-800',
                                        default => 'bg-indigo-100 text-indigo-800'
                                    };
                                @endphp
                                <span class="uppercase text-[10px] font-bold px-2 py-0.5 rounded-md {{ $badgeClass }}">
                                    {{ strtoupper(str_replace('_', ' ', $pm)) }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right font-black text-slate-900 text-sm">
                                Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <a href="{{ route('payments.receipt', $order->id) }}" target="_blank" class="px-3 py-1.5 bg-slate-100 hover:bg-amber-500 hover:text-slate-950 text-slate-700 rounded-lg text-xs font-bold transition inline-flex items-center gap-1 shadow-sm">
                                    <i class="fa-solid fa-print"></i> Struk
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <i class="fa-solid fa-receipt text-3xl mb-2 text-slate-300"></i>
                                <p class="text-xs font-bold text-slate-600">Belum ada transaksi terselesaikan</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $orders->links() }}
        </div>
    </div>
</div>
@endsection
