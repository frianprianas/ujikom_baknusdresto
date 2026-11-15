@extends('layouts.app')

@section('content')
<div class="flex-1 flex flex-col h-full bg-slate-100 overflow-hidden" x-data="kitchenApp()">
    <!-- Header -->
    <div class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between flex-shrink-0">
        <div>
            <h2 class="text-base font-extrabold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-fire-burner text-amber-500"></i>
                <span>Kitchen Order Display (KOT)</span>
            </h2>
            <p class="text-xs text-slate-400">Antrean pesanan langsung dari kasir untuk tim juru masak dapur</p>
        </div>

        <div class="flex items-center gap-3">
            <button onclick="window.location.reload()" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition flex items-center gap-1.5">
                <i class="fa-solid fa-rotate"></i> Refresh Tiket
            </button>
            <div class="px-3 py-1 bg-amber-100 text-amber-800 rounded-lg text-xs font-bold">
                Aktif: {{ count($kitchenOrders) }} Tiket
            </div>
        </div>
    </div>

    <!-- Kitchen Orders Board -->
    <div class="flex-1 p-6 overflow-y-auto">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
            @forelse($kitchenOrders as $kot)
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden flex flex-col justify-between">
                    <!-- Ticket Header -->
                    <div class="p-4 border-b border-slate-100 flex items-center justify-between {{ $kot->status === 'pending' ? 'bg-amber-500 text-slate-950' : ($kot->status === 'cooking' ? 'bg-sky-600 text-white' : 'bg-emerald-600 text-white') }}">
                        <div>
                            <span class="text-xs font-black uppercase tracking-wider">{{ $kot->kot_number }}</span>
                            <h3 class="text-sm font-extrabold">
                                {{ $kot->order->table ? $kot->order->table->table_number : 'Takeaway / Bungkus' }}
                            </h3>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full bg-white/20">
                                {{ ucfirst($kot->status) }}
                            </span>
                            <p class="text-[10px] opacity-80 mt-1">
                                {{ $kot->sent_to_kitchen_at->format('H:i') }}
                            </p>
                        </div>
                    </div>

                    <!-- Items List -->
                    <div class="p-4 flex-1 space-y-3 divide-y divide-slate-100">
                        @foreach($kot->items as $item)
                            <div class="pt-2 first:pt-0 flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-xs font-bold text-slate-800">
                                        <span class="inline-block w-6 h-6 rounded bg-slate-100 text-slate-900 text-center font-black leading-6 text-xs mr-1">
                                            {{ $item->quantity }}x
                                        </span>
                                        {{ $item->orderItem->product_name ?? 'Menu' }}
                                    </p>
                                    @if($item->notes || $item->orderItem->notes)
                                        <p class="text-[11px] text-amber-600 font-semibold mt-0.5 italic pl-7">
                                            * {{ $item->notes ?: $item->orderItem->notes }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Status Action Buttons -->
                    <div class="p-4 bg-slate-50 border-t border-slate-100 flex gap-2">
                        @if($kot->status === 'pending')
                            <button @click="updateStatus({{ $kot->id }}, 'cooking')" class="flex-1 py-2 bg-sky-600 hover:bg-sky-500 text-white font-bold rounded-xl text-xs transition flex items-center justify-center gap-1.5 shadow-sm">
                                <i class="fa-solid fa-fire"></i> Mulai Masak
                            </button>
                        @elseif($kot->status === 'cooking')
                            <button @click="updateStatus({{ $kot->id }}, 'ready')" class="flex-1 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl text-xs transition flex items-center justify-center gap-1.5 shadow-sm">
                                <i class="fa-solid fa-check"></i> Siap Saji
                            </button>
                        @elseif($kot->status === 'ready')
                            <button @click="updateStatus({{ $kot->id }}, 'served')" class="flex-1 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl text-xs transition flex items-center justify-center gap-1.5 shadow-sm">
                                <i class="fa-solid fa-utensils"></i> Selesai Antar
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full py-16 text-center text-slate-400">
                    <i class="fa-solid fa-bell-concierge text-5xl mb-3 text-slate-300"></i>
                    <p class="text-base font-bold text-slate-600">Tidak ada pesanan aktif di dapur</p>
                    <p class="text-xs text-slate-400 mt-1">Pesanan baru dari kasir akan muncul di layar ini secara otomatis.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

@push('scripts')
<script>
function kitchenApp() {
    return {
        async updateStatus(id, newStatus) {
            try {
                const res = await fetch(`/kitchen/${id}/status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ status: newStatus })
                });
                const data = await res.json();
                if (data.success) {
                    window.location.reload();
                }
            } catch (err) {
                alert('Gagal memperbarui status masakan');
            }
        }
    }
}
</script>
@endpush
@endsection
