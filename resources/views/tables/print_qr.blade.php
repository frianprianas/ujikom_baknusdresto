<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak QR Code Meja Restoran — Baknus Dresto</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; }
            .card-qr { page-break-inside: avoid; }
        }
    </style>
</head>
<body class="bg-slate-100 p-6 min-h-screen font-sans text-slate-800">

    <!-- Top Action Bar (No Print) -->
    <div class="max-w-5xl mx-auto mb-6 flex items-center justify-between no-print bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
        <div>
            <h1 class="text-base font-black text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-qrcode text-amber-500"></i>
                Stiker & Kartu QR Code Meja (Dine-in Self Order)
            </h1>
            <p class="text-xs text-slate-500">Siap dicetak untuk diletakkan di atas meja fisik restoran.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('tables.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition">
                Kembali ke Manajemen Meja
            </a>
            <button onclick="window.print()" class="px-5 py-2 bg-amber-500 hover:bg-amber-600 text-slate-900 text-xs font-black rounded-xl shadow-md transition flex items-center gap-2">
                <i class="fa-solid fa-print"></i>
                Cetak Semua Kartu Meja (Ctrl + P)
            </button>
        </div>
    </div>

    <!-- Printable Cards Grid -->
    <div class="max-w-5xl mx-auto grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
        @foreach($tables as $table)
            @php
                $orderUrl = url('/order/meja/' . $table->id);
                $qrApiUrl = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($orderUrl);
            @endphp
            <div class="card-qr bg-white border-2 border-slate-800 rounded-3xl p-5 shadow-sm flex flex-col items-center text-center relative overflow-hidden">
                <!-- Header Card -->
                <div class="w-full pb-3 border-b-2 border-dashed border-slate-200 flex items-center justify-center gap-2.5">
                    <img src="{{ asset('images/logo.png') }}" class="w-8 h-8 rounded-full border border-amber-400" alt="Logo">
                    <div class="text-left">
                        <h2 class="text-xs font-black text-slate-900 tracking-wider uppercase">Baknus Dresto</h2>
                        <p class="text-[9px] text-amber-600 font-bold">Self-Order & Dining</p>
                    </div>
                </div>

                <!-- Table Number Big -->
                <div class="my-3">
                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Nomor Meja</span>
                    <h3 class="text-2xl font-black text-slate-900">{{ $table->table_number }}</h3>
                    <p class="text-[11px] font-semibold text-slate-500">{{ $table->floor_section ?? 'Area Utama' }} • {{ $table->capacity }} Kursi</p>
                </div>

                <!-- QR Code Box -->
                <div class="w-48 h-48 bg-slate-50 border-2 border-slate-800 rounded-2xl p-2.5 shadow-inner flex items-center justify-center mb-3">
                    <img src="{{ $qrApiUrl }}" alt="QR Code {{ $table->table_number }}" class="w-full h-full object-contain">
                </div>

                <!-- Instructions -->
                <div class="bg-amber-50 border border-amber-200 rounded-xl px-3 py-2 w-full text-center">
                    <p class="text-[11px] font-black text-slate-800 flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-camera text-amber-500"></i>
                        Scan untuk Pesan Makanan
                    </p>
                    <p class="text-[9px] text-slate-500 mt-0.5">Buka kamera HP Anda & arahkan ke kode QR ini</p>
                </div>

                <!-- Direct URL preview -->
                <p class="text-[8px] text-slate-400 font-mono mt-2 truncate max-w-full">
                    {{ $orderUrl }}
                </p>

                <!-- Cut-line guide (no print background) -->
                <div class="absolute -top-1 -right-1 w-6 h-6 border-t-2 border-r-2 border-slate-300 pointer-events-none"></div>
                <div class="absolute -bottom-1 -left-1 w-6 h-6 border-b-2 border-l-2 border-slate-300 pointer-events-none"></div>
            </div>
        @endforeach
    </div>

</body>
</html>
