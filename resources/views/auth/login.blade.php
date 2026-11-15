<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - Baknus Dresto</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            navy: '#0e2a47',
                            navylight: '#1b436e',
                            gold: '#f59e0b',
                            golddark: '#b47b2b',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-900 min-h-screen flex items-center justify-center p-4 selection:bg-amber-500 selection:text-black">
    <div class="max-w-xl w-full">
        <!-- BRAND LOGO & TITLE -->
        <div class="text-center mb-6">
            <div class="inline-block p-2 rounded-2xl bg-white shadow-xl mb-3 border-2 border-amber-400">
                <img src="{{ asset('images/logo.png') }}" alt="Baknus Dresto Logo" class="w-16 h-16 rounded-xl object-contain">
            </div>
            <h1 class="text-2xl font-black text-white tracking-tight">Baknus Dresto</h1>
            <p class="text-xs text-amber-400 font-bold uppercase tracking-wider mt-0.5">Restaurant POS & Management System</p>
        </div>

        <!-- NOTIFICATION / STATUS -->
        @if(session('status'))
            <div class="mb-4 p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs flex items-center gap-2.5">
                <i class="fa-solid fa-circle-check text-emerald-400 text-base"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if(session('warning'))
            <div class="mb-4 p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs flex items-center gap-2.5">
                <i class="fa-solid fa-triangle-exclamation text-amber-400 text-base"></i>
                <span>{{ session('warning') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs flex items-center gap-2.5">
                <i class="fa-solid fa-circle-exclamation text-rose-400 text-base"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <!-- MAIN CARD -->
        <div class="bg-[#0e2a47] rounded-3xl p-6 sm:p-8 shadow-2xl border border-slate-700">
            <!-- SECTION 1: SIMULASI AKUN CEPAT (1-KLIK) -->
            <div class="mb-6">
                <div class="flex items-center gap-2 mb-3">
                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                    <h2 class="text-xs font-black uppercase text-amber-400 tracking-wider">Simulasi Akun Cepat (1-Klik Masuk)</h2>
                </div>
                <p class="text-xs text-slate-300 mb-4">Pilih peran akun di bawah ini untuk langsung masuk ke sistem sesuai hak akses:</p>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @foreach($simulatedUsers as $u)
                        @php
                            $roleName = match($u->role) {
                                'manager' => 'Manajer Resto',
                                'cashier' => 'Kasir',
                                'admin' => 'Admin Kasir',
                                default => ucfirst($u->role)
                            };
                            $roleBadgeColor = match($u->role) {
                                'manager' => 'bg-indigo-500/20 text-indigo-300 border-indigo-500/30',
                                'cashier' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
                                'admin' => 'bg-amber-500/20 text-amber-300 border-amber-500/30',
                                default => 'bg-slate-500/20 text-slate-300 border-slate-500/30'
                            };
                            $roleIcon = match($u->role) {
                                'manager' => 'fa-chart-pie',
                                'cashier' => 'fa-cash-register',
                                'admin' => 'fa-user-shield',
                                default => 'fa-user'
                            };
                        @endphp
                        <a href="{{ route('login.quick', $u->id) }}" 
                           class="group p-3.5 rounded-2xl bg-[#16385d] hover:bg-[#1b436e] border border-[#214b78] hover:border-amber-400 transition-all flex flex-col justify-between text-left shadow-sm">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-black border uppercase {{ $roleBadgeColor }}">
                                        <i class="fa-solid {{ $roleIcon }}"></i> {{ $roleName }}
                                    </span>
                                </div>
                                <h3 class="font-bold text-white text-sm group-hover:text-amber-300 transition-colors">{{ $u->name }}</h3>
                                <p class="text-[11px] text-slate-400 truncate mt-0.5">{{ $u->email }}</p>
                            </div>
                            <div class="mt-3 pt-2.5 border-t border-slate-700/60 flex items-center justify-between text-[11px] font-bold text-amber-400 group-hover:translate-x-0.5 transition-transform">
                                <span>Masuk &rarr;</span>
                                <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- DIVIDER -->
            <div class="relative my-6 text-center">
                <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-slate-700"></div></div>
                <span class="relative bg-[#0e2a47] px-3 text-[11px] font-bold uppercase text-slate-400 tracking-wider">Atau Masuk Manual</span>
            </div>

            <!-- SECTION 2: FORM LOGIN STANDARD -->
            <form action="{{ route('login.submit') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1.5">Alamat Email</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 text-xs">
                            <i class="fa-solid fa-envelope"></i>
                        </span>
                        <input type="email" name="email" value="{{ old('email', 'alex@baknusdresto.com') }}" required 
                               placeholder="nama@baknusdresto.com"
                               class="w-full pl-9 pr-3 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white text-xs placeholder:text-slate-500 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1.5">Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 text-xs">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input type="password" name="password" value="password" required 
                               placeholder="Password akun"
                               class="w-full pl-9 pr-3 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white text-xs placeholder:text-slate-500 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">Hint simulasi: Password default adalah <code class="text-amber-400 bg-slate-900 px-1 py-0.5 rounded">password</code></p>
                </div>

                <button type="submit" 
                        class="w-full py-3 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs uppercase tracking-wider transition-all shadow-lg shadow-amber-500/20 flex items-center justify-center gap-2 cursor-pointer mt-2">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    <span>Masuk ke Sistem</span>
                </button>
            </form>
        </div>

        <p class="text-center text-xs text-slate-500 mt-6 font-medium">
            &copy; {{ date('Y') }} Baknus Dresto. All rights reserved.
        </p>
    </div>
</body>
</html>
