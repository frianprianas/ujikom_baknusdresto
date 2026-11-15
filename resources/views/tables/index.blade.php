@extends('layouts.app')

@section('content')
<div class="flex-1 flex flex-col h-full bg-slate-100 overflow-hidden" x-data="tablesApp()">
    <!-- Header -->
    <div class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between flex-shrink-0">
        <div>
            <h2 class="text-base font-extrabold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-chair text-amber-500"></i>
                <span>Manajemen Meja Restoran (Tables)</span>
            </h2>
            <p class="text-xs text-slate-400">Status ketersediaan meja makan & Barcode Self-Order</p>
        </div>

        <div class="flex items-center gap-4 text-xs font-semibold">
            <div class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                <span class="text-slate-600">Available</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-full bg-rose-500"></span>
                <span class="text-slate-600">Occupied</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                <span class="text-slate-600">Reserved</span>
            </div>

            <!-- Tombol Cetak Semua QR Meja -->
            <a href="{{ route('tables.print_qr') }}" target="_blank"
                class="ml-2 px-3.5 py-2 bg-amber-500 hover:bg-amber-600 text-slate-900 rounded-xl text-xs font-black shadow-sm transition flex items-center gap-1.5">
                <i class="fa-solid fa-print"></i>
                <span>Cetak Semua QR Meja</span>
            </a>
        </div>
    </div>

    <!-- Table Grid By Floor Section -->
    <div class="flex-1 p-6 overflow-y-auto space-y-6">
        @foreach($sections as $section)
            <div>
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider mb-3">{{ $section }}</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    @foreach($tables->where('floor_section', $section) as $table)
                        @php
                            $orderUrl = url('/order/meja/' . $table->id);
                        @endphp
                        <div class="bg-white rounded-2xl p-4 border shadow-sm flex flex-col justify-between transition hover:shadow-md
                            {{ $table->status === 'available' ? 'border-emerald-200 hover:border-emerald-400' : ($table->status === 'occupied' ? 'border-rose-200 bg-rose-50/30' : 'border-amber-200 bg-amber-50/30') }}">
                            
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-black text-slate-800">{{ $table->table_number }}</span>
                                <span class="w-2.5 h-2.5 rounded-full 
                                    {{ $table->status === 'available' ? 'bg-emerald-500 shadow-[0_0_6px_rgba(16,185,129,0.6)]' : ($table->status === 'occupied' ? 'bg-rose-500 shadow-[0_0_6px_rgba(244,63,94,0.6)]' : 'bg-amber-500') }}"></span>
                            </div>

                            <div class="my-3 text-center">
                                <div class="w-12 h-12 rounded-xl mx-auto flex items-center justify-center text-xl font-bold
                                    {{ $table->status === 'available' ? 'bg-emerald-50 text-emerald-600' : ($table->status === 'occupied' ? 'bg-rose-100 text-rose-600' : 'bg-amber-100 text-amber-600') }}">
                                    <i class="fa-solid fa-utensils"></i>
                                </div>
                                <p class="text-xs font-bold text-slate-700 mt-2">{{ $table->capacity }} Kursi</p>
                                <p class="text-[11px] font-semibold uppercase tracking-wider mt-0.5
                                    {{ $table->status === 'available' ? 'text-emerald-600' : ($table->status === 'occupied' ? 'text-rose-600' : 'text-amber-600') }}">
                                    {{ $table->status }}
                                </p>
                            </div>

                            <!-- Quick Action Buttons -->
                            <div class="pt-2 border-t border-slate-100 flex flex-col gap-1.5">
                                <button type="button"
                                    @click="openQrModal('{{ $table->id }}', '{{ $table->table_number }}', '{{ $table->floor_section ?? 'Area Utama' }}', '{{ $orderUrl }}')"
                                    class="w-full py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300/80 rounded-lg text-[11px] font-black flex items-center justify-center gap-1.5 transition">
                                    <i class="fa-solid fa-qrcode text-amber-600 text-xs"></i>
                                    <span>QR Barcode Meja</span>
                                </button>

                                <select @change="changeStatus({{ $table->id }}, $event.target.value)" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-2 py-1 text-[11px] font-semibold text-slate-600 focus:outline-none">
                                    <option value="available" {{ $table->status === 'available' ? 'selected' : '' }}>Set Available</option>
                                    <option value="occupied" {{ $table->status === 'occupied' ? 'selected' : '' }}>Set Occupied</option>
                                    <option value="reserved" {{ $table->status === 'reserved' ? 'selected' : '' }}>Set Reserved</option>
                                </select>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <!-- MODAL POPUP QR CODE MEJA -->
    <div x-show="showQrModal" style="display: none;"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100">
        
        <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-slate-200 text-center relative"
            @click.away="showQrModal = false">
            
            <button @click="showQrModal = false" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 w-8 h-8 rounded-full flex items-center justify-center bg-slate-100">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>

            <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center text-xl mx-auto mb-3 shadow-sm">
                <i class="fa-solid fa-qrcode"></i>
            </div>

            <h3 class="text-base font-black text-slate-800">QR Code Self-Order Meja</h3>
            <p class="text-xs font-bold text-amber-600 mt-0.5" x-text="activeTableNumber + ' (' + activeTableFloor + ')'"></p>
            <p class="text-[11px] text-slate-400 mt-1">Pelanggan cukup scan QR ini dengan kamera HP untuk langsung memesan makanan.</p>

            <!-- Gambar QR Code Dinamis -->
            <div class="w-52 h-52 mx-auto my-4 bg-slate-50 border-2 border-slate-800 rounded-2xl p-3 flex items-center justify-center shadow-inner">
                <img :src="'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' + encodeURIComponent(activeOrderUrl)"
                    class="w-full h-full object-contain" alt="QR Meja">
            </div>

            <!-- URL Text Box -->
            <div class="bg-slate-100 rounded-xl p-2.5 flex items-center justify-between text-left text-xs mb-4">
                <span class="truncate font-mono text-[11px] text-slate-600 mr-2" x-text="activeOrderUrl"></span>
                <button @click="copyUrl()" class="px-2.5 py-1 bg-white hover:bg-slate-200 text-slate-700 font-bold rounded-lg border border-slate-200 shadow-xs text-[10px] whitespace-nowrap">
                    <span x-text="copied ? 'Tersalin!' : 'Copy Link'"></span>
                </button>
            </div>

            <!-- Action Buttons -->
            <div class="space-y-2">
                <a :href="activeOrderUrl" target="_blank"
                    class="w-full py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-900 font-black text-xs rounded-xl shadow-md transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                    <span>Buka Tampilan Pelanggan (Test di Browser)</span>
                </a>
                <button @click="showQrModal = false"
                    class="w-full py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs rounded-xl transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function tablesApp() {
    return {
        showQrModal: false,
        activeTableId: null,
        activeTableNumber: '',
        activeTableFloor: '',
        activeOrderUrl: '',
        copied: false,

        openQrModal(id, number, floor, url) {
            this.activeTableId = id;
            this.activeTableNumber = number;
            this.activeTableFloor = floor;
            this.activeOrderUrl = url;
            this.copied = false;
            this.showQrModal = true;
        },

        copyUrl() {
            navigator.clipboard.writeText(this.activeOrderUrl);
            this.copied = true;
            setTimeout(() => this.copied = false, 2000);
        },

        async changeStatus(id, newStatus) {
            try {
                const res = await fetch(`/tables/${id}/status`, {
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
                alert('Gagal mengubah status meja');
            }
        }
    }
}
</script>
@endpush
