<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Reservas</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-neutral-900 text-white">
      <header class="sticky top-0 z-20 bg-neutral-900/80 backdrop-blur border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 h-14 flex items-center justify-between">
            <div class="font-black tracking-tight"><h1 class="text-2xl font-bold">Reservas</h1></div>
            <div class="text-sm text-neutral-300">
                <a href="{{ route('dashboard') }}"
                class="btn bg-neutral-800 hover:bg-neutral-700">← Volver al inicio</a>
            </div>
        </div>
    </header>
    <div class="max-w-6xl mx-auto p-6 space-y-6">

        

        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-sm mb-1 text-neutral-400">Buscar</label>
                <input name="q" value="{{ $q ?? '' }}" placeholder="cliente, correo, WhatsApp"
                    class="bg-neutral-800 border border-white/10 rounded px-3 py-2 w-64">
            </div>
            <div>
                <label class="block text-sm mb-1 text-neutral-400">Evento</label>
                <select name="evento_id" class="bg-neutral-800 border border-white/10 rounded px-3 py-2">
                    <option value="">Todos</option>
                    @foreach($eventos as $ev)
                    <option value="{{ $ev->id }}" @selected(($eventoId ?? null)==$ev->id)>{{ $ev->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <button class="px-3 py-2 rounded bg-green-600 text-black hover:bg-green-500">Filtrar</button>
            <a href="{{ route('dashboard.reservas.index') }}"
                class="px-3 py-2 rounded bg-neutral-700 hover:bg-neutral-600">Limpiar</a>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-neutral-300">
                    <tr class="border-b border-white/10">
                        <th class="py-2 pr-3">#</th>
                        <th class="py-2 pr-3">Evento</th>
                        <th class="py-2 pr-3">Cliente</th>
                        <th class="py-2 pr-3">Llegada</th>
                        <th class="py-2 pr-3">Mesas</th>
                        <th class="py-2 pr-3 text-right">Total</th>
                        <th class="py-2 pr-3">Creada</th>
                        <th class="py-2 pr-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $r)
                    <tr class="border-b border-white/5 hover:bg-white/5">
                        <td class="py-2 pr-3">{{ $r->id }}</td>
                        <td class="py-2 pr-3">{{ $r->evento }}</td>
                        <td class="py-2 pr-3">
                            <div class="font-medium">{{ $r->nombre_cliente }}</div>
                            <div class="text-neutral-400">{{ $r->correo }} · {{ $r->numero_wsp }}</div>
                        </td>
                        <td class="py-2 pr-3">{{ \Illuminate\Support\Str::of($r->hora_reserva)->substr(0,5) }}</td>
                        <td class="py-2 pr-3">
                            {{ (int)$r->cant_mesas }}<span class="text-neutral-400"> [{{ $r->mesas }}]</span>
                        </td>
                        <td class="py-2 pr-3 text-right">${{ number_format($r->total, 0, ',', '.') }}</td>
                        <td class="py-2 pr-3 text-neutral-400">{{ $r->created_at }}</td>
                        <td class="py-2 pr-3">
                            <a href="{{ route('dashboard.reservas.show', $r->id) }}"
                                class="px-2 py-1 rounded bg-neutral-700 hover:bg-neutral-600">Ver</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $rows->links() }}
        </div>

    </div>
</body>

</html>