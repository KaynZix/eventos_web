<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Evento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MapaEditorController extends Controller
{
    // Editor
    public function edit(Evento $evento)
    {
        // Si no tienes 'x'/'y' aún, evita romper la vista
        $cols = Schema::getColumnListing('escenario');
        $hasX = in_array('x', $cols);
        $hasY = in_array('y', $cols);

        $query = DB::table('escenario as e')
            ->join('mesa as m', 'm.id', '=', 'e.id_mesa')
            ->where('e.id_evento', $evento->id)
            ->select('e.id', 'e.id_mesa', 'm.sillas');

        if ($hasX) $query->addSelect('e.x');
        else       $query->addSelect(DB::raw('0 as x'));

        if ($hasY) $query->addSelect('e.y');
        else       $query->addSelect(DB::raw('0 as y'));

        $mesas = $query->orderBy('e.id')->get();

        return view('dashboard.mapa-editor', compact('evento', 'mesas'));
    }

    // Guarda TODO el mapa (reemplaza lo existente)
    public function save(Request $request, Evento $evento)
    {
        try {
            $data = $request->validate([
                'mesas'            => 'array',
                'mesas.*.sillas'   => 'required|integer|min:1|max:8',
                'mesas.*.x'        => 'required|integer|min:0',
                'mesas.*.y'        => 'required|integer|min:0',
            ]);

            $mesas = $data['mesas'] ?? [];

            DB::transaction(function () use ($evento, $mesas) {
                // borrar lo anterior del evento
                $mesaIds = DB::table('escenario')
                    ->where('id_evento', $evento->id)
                    ->pluck('id_mesa');

                if ($mesaIds->count()) {
                    DB::table('escenario')->where('id_evento', $evento->id)->delete();
                    DB::table('mesa')->whereIn('id', $mesaIds)->delete();
                }

                // columnas realmente existentes en 'escenario'
                $cols = Schema::getColumnListing('escenario');
                $hasEstado = in_array('estado', $cols);
                $hasX = in_array('x', $cols);
                $hasY = in_array('y', $cols);
                $hasHora = in_array('hora_reserva', $cols);

                foreach ($mesas as $m) {
                    $mesaId = DB::table('mesa')->insertGetId([
                        'sillas' => (int)$m['sillas'],
                    ]);

                    $payload = [
                        'id_evento'    => (int)$evento->id,
                        'id_mesa'      => (int)$mesaId,
                        'id_escenario' => (int)$evento->id_escenario,
                    ];
                    if ($hasX)   $payload['x'] = (int)$m['x'];
                    if ($hasY)   $payload['y'] = (int)$m['y'];
                    if ($hasEstado) $payload['estado'] = 'disponible';
                    if ($hasHora)   $payload['hora_reserva'] = null;

                    DB::table('escenario')->insert($payload);
                }
            });

            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            // Devuelve el detalle para poder verlo en el navegador
            return response()->json([
                'ok'  => false,
                'msg' => $e->getMessage()
            ], 500);
        }
    }
}
