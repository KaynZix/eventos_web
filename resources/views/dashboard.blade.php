<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Panel de Administración</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .card{ background: rgba(17,24,39,.6); border:1px solid rgba(255,255,255,.08); border-radius:12px; }
    .navlink{ display:flex; align-items:center; gap:.6rem; padding:.55rem .65rem; border-radius:.6rem; color:#cbd5e1; }
    .navlink:hover{ background:#1f2937; color:white; }
    .navlink.active{ background:#111827; color:white; }
    .btn{ padding:.45rem .7rem; border-radius:.5rem; }
  </style>
</head>
<body class="bg-neutral-950 text-neutral-100">

@php
  use Illuminate\Support\Facades\DB;
  use Illuminate\Support\Carbon;

  $counts = [
    'eventos'   => DB::table('eventos')->count(),
    'mesas'     => DB::table('mesa')->count(),
    'escenario' => DB::table('escenario')->count(),
    'reservas'  => DB::table('reserva')->count(),
  ];

  $proximos = DB::table('eventos')
      ->orderBy('fecha')->orderBy('hora_inicio')
      ->limit(8)->get();
@endphp

<!-- Topbar -->

  <!-- Header -->
  @include('dashboard.partials.header')


<div class="max-w-7xl mx-auto p-4 md:p-6 grid grid-cols-1 md:grid-cols-[240px_1fr] gap-6">

  <!-- Sidebar -->
  @include('dashboard.partials.navegation')

  <!-- Main -->
  <main class="space-y-6">
    <section class="card p-4">
      <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">Resumen</h1>
        <a href="{{ route('dashboard.eventos.create') }}" class="btn bg-sky-600 hover:bg-sky-500 text-white">Crear evento</a>
      </div>

      @if(session('ok'))
        <div class="mt-3 px-3 py-2 rounded bg-emerald-600/20 text-emerald-300">{{ session('ok') }}</div>
      @endif

      <!-- Stats -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4">
        <div class="rounded-lg bg-neutral-800/70 p-3">
          <div class="text-xs text-neutral-400">Eventos</div>
          <div class="text-2xl font-extrabold">{{ $counts['eventos'] }}</div>
        </div>
        <div class="rounded-lg bg-neutral-800/70 p-3">
          <div class="text-xs text-neutral-400">Mesas</div>
          <div class="text-2xl font-extrabold">{{ $counts['mesas'] }}</div>
        </div>
        <div class="rounded-lg bg-neutral-800/70 p-3">
          <div class="text-xs text-neutral-400">Posiciones en escenario</div>
          <div class="text-2xl font-extrabold">{{ $counts['escenario'] }}</div>
        </div>
        <div class="rounded-lg bg-neutral-800/70 p-3">
          <div class="text-xs text-neutral-400">Reservas</div>
          <div class="text-2xl font-extrabold">{{ $counts['reservas'] }}</div>
        </div>
      </div>

      <!-- Próximos eventos -->
      <div class="mt-6">
        <div class="flex items-center justify-between mb-2">
          <h2 class="font-semibold">Próximos eventos</h2>
          <a href="{{ route('dashboard.eventos.index') }}" class="text-sm text-sky-400 hover:underline">Ver todos →</a>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left text-neutral-400 border-b border-white/10">
                <th class="py-2 pr-3">Fecha</th>
                <th class="py-2 pr-3">Nombre</th>
                <th class="py-2 pr-3">Género/Tributo</th>
                <th class="py-2 pr-3">Escenario</th>
                <th class="py-2 pr-3">Horario</th>
                <th class="py-2">Acciones</th>
              </tr>
            </thead>
            <tbody>
            @forelse($proximos as $e)
              <tr class="border-b border-white/5">
                <td class="py-2 pr-3">{{ Carbon::parse($e->fecha)->format('d/m/Y') }}</td>
                <td class="py-2 pr-3 font-semibold">{{ $e->nombre }}</td>
                <td class="py-2 pr-3 text-neutral-300">
                  @if($e->tributo) <span class="px-2 py-0.5 text-xs rounded bg-neutral-800">Tributo</span> @endif
                  {{ $e->genero ?? '' }}
                </td>
                <td class="py-2 pr-3">Esc {{ $e->id_escenario }}</td>
                <td class="py-2 pr-3">{{ substr($e->hora_inicio,0,5) }}–{{ substr($e->hora_termino,0,5) }}</td>
                <td class="py-2">
                  <a href="{{ route('dashboard.eventos.edit', $e->id) }}" class="btn bg-neutral-800 hover:bg-neutral-700">Editar</a>
                  <a href="{{ route('dashboard.eventos.mapa', $e->id) }}" class="btn bg-sky-700 hover:bg-sky-600 text-white ml-2">Mapa</a>
                </td>
              </tr>
            @empty
              <tr><td colspan="6" class="py-4 text-neutral-400">Sin eventos.</td></tr>
            @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </main>
</div>

</body>
</html>
