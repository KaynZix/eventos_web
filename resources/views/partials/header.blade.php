<!-- HEADER fijo con links siempre visibles -->
<header class="fixed top-0 inset-x-0 z-[2147483646] bg-neutral-950/80 backdrop-blur ring-1 ring-white/10">
  <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 h-14 flex items-center justify-between">
    <a href="/" class="flex items-center gap-2">
      <img src="{{ asset('images/logo.png') }}" alt="Cacho e’ Cabra" class="h-8 w-auto">
      <span class="sr-only">Inicio</span>
    </a>

    <!-- Nav SIEMPRE visible (sin hamburguesa) -->
    <nav class="flex items-center gap-4 sm:gap-6 text-[11px] sm:text-xs uppercase tracking-widest font-semibold
                overflow-x-auto whitespace-nowrap scrollbar-none">
      <a class="px-2 py-1 rounded hover:bg-white/10" href="{{ route('eventos')}}">Eventos</a>
      <a class="px-2 py-1 rounded hover:bg-white/10" href="{{ route('contacto')}}">Contacto</a>
    </nav>
  </div>
</header>

<!-- Spacer para que el contenido no quede bajo el header fijo -->
<div class="h-14"></div>
