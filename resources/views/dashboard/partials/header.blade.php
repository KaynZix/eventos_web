<header class="sticky top-0 z-20 bg-neutral-900/80 backdrop-blur border-b border-white/10">
  <div class="max-w-7xl mx-auto px-4 h-14 flex items-center justify-between">
    <div class="font-black tracking-tight">Panel · Cacho e’ Cabra</div>
    <div class="flex items-center gap-3 text-sm">
      <a href="{{ route('eventos.index') }}" class="px-3 py-1.5 rounded bg-neutral-800 hover:bg-neutral-700">Ver sitio</a>
      <form method="POST" action="{{ route('logout') }}"> @csrf
        <button class="px-3 py-1.5 rounded bg-red-600 hover:bg-red-500 text-white">Cerrar sesión</button>
      </form>
    </div>
  </div>
</header>