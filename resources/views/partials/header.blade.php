<!-- HEADER fijo con links siempre visibles -->
<header class="fixed top-0 inset-x-0 z-[2147483646] bg-neutral-950/80 backdrop-blur ring-1 ring-white/10">
    <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 h-14 flex items-center justify-between">
        <a href="/" class="flex items-center gap-2">
            <img src="{{ asset('images/logo.png') }}" alt="Cacho e’ Cabra" class="h-8 w-auto">
            <span class="sr-only">Inicio</span>
        </a>

        <!-- Nav SIEMPRE visible (sin hamburguesa) -->

        @if (Auth::check())
        <nav class="flex items-center gap-4 sm:gap-6 text-[11px] sm:text-xs uppercase tracking-widest font-semibold
        overflow-x-auto whitespace-nowrap scrollbar-none">
            <a href="{{ route('dashboard') }}"
                class="px-3 py-1.5 rounded bg-neutral-800 hover:bg-neutral-700">Dashboard</a>
            <a class="px-2 py-1 rounded hover:bg-white/10" href="{{ route('eventos.index')}}">Eventos</a>
            <a class="px-2 py-1 rounded hover:bg-white/10" href="{{ route('contacto')}}">Contacto</a>
            <form method="POST" action="{{ route('logout') }}"> @csrf
                <button class="px-3 py-1.5 rounded bg-red-600 hover:bg-red-500 text-white">Cerrar sesión</button>
            </form>
        </nav>
        @else
        <nav class="flex items-center gap-4 sm:gap-6 text-[11px] sm:text-xs uppercase tracking-widest font-semibold
        overflow-x-auto whitespace-nowrap scrollbar-none">
            <a class="px-2 py-1 rounded hover:bg-white/10" href="{{ route('eventos.index')}}">Eventos</a>
            <a class="px-2 py-1 rounded hover:bg-white/10" href="{{ route('contacto')}}">Contacto</a>
        </nav>
        @endif
    </div>
</header>

<!-- Spacer para que el contenido no quede bajo el header fijo -->
<div class="h-14"></div>

@if(request()->cookie('reserva_hold'))
<div class="bg-amber-600/20 border border-amber-500/40 text-amber-100 px-3 py-2 rounded mb-3">
    Tienes una reserva en curso.
    <a href="{{ route('reservas.resume') }}" class="underline font-semibold">Continuar</a>
</div>
@endif