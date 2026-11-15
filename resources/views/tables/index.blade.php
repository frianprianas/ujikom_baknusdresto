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
            <p class="text-xs text-slate-400">Status ketersediaan meja makan (Dine-in floor plan)</p>
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
        </div>
    </div>

    <!-- Table Grid By Floor Section -->
    <div class="flex-1 p-6 overflow-y-auto space-y-6">
        @foreach($sections as $section)
            <div>
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider mb-3">{{ $section }}</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    @foreach($tables->where('floor_section', $section) as $table)
                        <div class="bg-white rounded-2xl p-4 border shadow-sm flex flex-col justify-between transition hover:shadow-md
                            {{ $table->status === 'available' ? 'border-emerald-200 hover:border-emerald-400' : ($table->status === 'occupied' ? 'border-rose-200 bg-rose-50/30' : 'border-amber-200 bg-amber-50/30') }}">
                            
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-black text-slate-800">{{ $table->table_number }}</span>
                                <span class="w-2.5 h-2.5 rounded-full 
                                    {{ $table->status === 'available' ? 'bg-emerald-500 shadow-[0_0_6px_rgba(16,185,129,0.6)]' : ($table->status === 'occupied' ? 'bg-rose-500 shadow-[0_0_6px_rgba(244,63,94,0.6)]' : 'bg-amber-500') }}"></span>
                            </div>

                            <div class="my-4 text-center">
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

                            <!-- Quick Action Dropdown -->
                            <div class="pt-2 border-t border-slate-100">
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
</div>

@push('scripts')
<script>
function tablesApp() {
    return {
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
@endsection
