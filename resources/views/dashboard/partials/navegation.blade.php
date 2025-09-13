  <aside class="card p-3">
    <nav class="space-y-1">
      <a class="navlink active" href="{{ route('dashboard') }}" title="Inicio">>🏠 Inicio</a>
      <a class="navlink active" href="{{ route('dashboard.eventos.index') }}" title="Eventos">>🎤 Eventos</a>
      <a class="navlink active" href="{{ route('dashboard.reservas.index') }}" title="Reservas">>📋 Reservas</a>
      {{-- módulos futuros --}}
      <!-- <a class="navlink" href="#" title="Próximamente">🧾 Promociones</a> -->
      <!-- <a class="navlink" href="#" title="Próximamente">📢 Publicidad</a> -->
    </nav>
  </aside>