<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Reserva confirmada</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-neutral-900 text-white">
  <div class="max-w-2xl mx-auto p-6 space-y-4">
    @if(session('ok'))
      <div class="bg-emerald-600/20 border border-emerald-500/40 text-emerald-200 px-4 py-2 rounded">
        {{ session('ok') }}
      </div>
    @else
      <div class="bg-emerald-600/20 border border-emerald-500/40 text-emerald-200 px-4 py-2 rounded">
        ¡Reserva creada correctamente!
      </div>
    @endif

    <a href="{{ route('eventos.index') }}"
       class="inline-block px-4 py-2 rounded bg-neutral-700 hover:bg-neutral-600">
      Volver a eventos
    </a>
  </div>
</body>
</html>
