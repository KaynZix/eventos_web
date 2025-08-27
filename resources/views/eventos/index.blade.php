<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cartelera de Eventos — Cacho e’ Cabra</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .vertical { writing-mode: vertical-rl; text-orientation: mixed; letter-spacing: .15em; }
    .card { background: rgba(0,0,0,.55); backdrop-filter: blur(2px); }
    .stroke { text-shadow: 0 2px 6px rgba(0,0,0,.6); }
  </style>
</head>
<body class="bg-neutral-900 text-white">
  @php
    use Illuminate\Support\Carbon;
  @endphp

  <!-- Header -->
  @include('partials.header')

  <!-- CARTELERA -->
  <section class="relative min-h-screen">
    <img
      src="https://images.unsplash.com/photo-1555396273-367ea4eb4db5?q=80&w=1400&auto=format&fit=crop"
      alt="Patio del restobar con iluminación"
      class="absolute inset-0 w-full h-full object-cover"
    >
    <div class="absolute inset-0 bg-black/55"></div>
    <div class="absolute top-0 left-0 right-0 h-[12px] bg-red-600"></div>

    @php
      $destacado = $eventos->first();
      $otros = $eventos->slice(1);
    @endphp

    <div class="relative z-10 grid grid-cols-12 gap-4 px-4 sm:px-6 lg:px-10 py-8 md:py-12">

      {{-- DESTACADO IZQUIERDA --}}
      <div class="col-span-12 md:col-span-4 lg:col-span-4 flex items-end">
        <div class="w-full">
          @if($destacado)
            @php
              $c = Carbon::parse($destacado->fecha)->locale('es');
              $mes = ucfirst($c->translatedFormat('F'));
              $dia = $c->format('d');
              $semana = ucfirst($c->translatedFormat('l'));
            @endphp

            <div class="flex items-center gap-4">
              <img src="{{ $destacado->imagen ?: 'https://i.imgur.com/1zv9j5y.png' }}"
                   alt="{{ $destacado->nombre }}"
                   class="w-28 h-28 sm:w-36 sm:h-36 rounded-full object-cover ring-4 ring-white/10 shadow-xl">
              <div class="hidden sm:block text-5xl font-extrabold stroke">{{ $destacado->nombre }}</div>
            </div>

            <div class="mt-5 max-w-[420px] card rounded-xl px-5 py-4 shadow-lg relative">
              <span class="absolute top-3 right-3 bg-red-600 text-white text-xs font-extrabold uppercase tracking-widest px-3 py-1 rounded">
                {{ $mes }}
              </span>

              <div class="flex items-end gap-3">
                <div class="text-5xl sm:text-6xl font-black leading-none">{{ $dia }}</div>
                <div class="mb-1">
                  <div class="uppercase font-extrabold tracking-widest">{{ $semana }}</div>
                  <div class="text-sm font-semibold opacity-90">{{ substr($destacado->hora_inicio,0,5) }} hrs</div>
                </div>
              </div>
              <div class="mt-3">
                <div class="text-lg font-extrabold uppercase">{{ $destacado->nombre }}</div>
                <div class="text-sm opacity-90 uppercase">
                  {{ $destacado->tributo ? 'Tributo' : ($destacado->genero ?? '') }}
                </div>
              </div>

              <div class="mt-4">
                {{-- Ajusta la ruta cuando tengas la vista pública del mapa/detalle --}}
                <a href="{{ url('/eventos/'.$destacado->id.'/mapa') }}"
                   class="inline-block bg-red-600 hover:bg-red-700 focus:ring-4 focus:ring-red-500/30
                          text-white font-extrabold tracking-widest uppercase px-4 py-2 rounded">
                  Más detalles
                </a>
              </div>
            </div>
          @else
            <div class="text-neutral-200">No hay eventos publicados por ahora.</div>
          @endif
        </div>
      </div>

      {{-- COLUMNA DERECHA (tarjetas) --}}
      <div class="col-span-12 md:col-span-8 grid grid-cols-12 gap-4">
        @forelse($otros as $ev)
          @php
            $c = Carbon::parse($ev->fecha)->locale('es');
            $mes = ucfirst($c->translatedFormat('F'));
            $dia = $c->format('d');
            $semana = ucfirst($c->translatedFormat('l'));
          @endphp

          <article class="col-span-12 md:col-span-6 lg:col-span-6 card rounded-xl p-4 sm:p-5 shadow-lg relative">
            <span class="absolute top-3 right-3 bg-red-600 text-white text-xs font-extrabold uppercase tracking-widest px-3 py-1 rounded">
              {{ $mes }}
            </span>

            <div class="flex items-end gap-3">
              <div class="text-5xl sm:text-6xl font-black leading-none">{{ $dia }}</div>
              <div class="mb-1">
                <div class="uppercase font-extrabold tracking-widest">{{ $semana }}</div>
                <div class="text-sm font-semibold opacity-90">{{ substr($ev->hora_inicio,0,5) }} hrs</div>
              </div>
            </div>
            <div class="mt-2 uppercase font-extrabold">
              {{ $ev->tributo ? 'Tributo' : ($ev->genero ?? '') }}
            </div>
            <div class="text-sm uppercase opacity-90">{{ $ev->nombre }}</div>

            <div class="mt-4">
              {{-- Ajusta la ruta cuando tengas la vista pública del mapa/detalle --}}
              <a href="{{ url('/eventos/'.$ev->id.'/mapa') }}"
                 class="inline-block bg-red-600 hover:bg-red-700 focus:ring-4 focus:ring-red-500/30
                        text-white font-extrabold tracking-widest uppercase px-4 py-2 rounded">
                Más detalles
              </a>
            </div>
          </article>
        @empty
          <div class="col-span-12 text-neutral-200">No hay más eventos por mostrar.</div>
        @endforelse
      </div>
    </div>
  </section>
</body>
</html>
