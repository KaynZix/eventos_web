<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use App\Models\Evento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MapaEditorController extends Controller
{
    /**
     * Editor de mapa (mesas) con overlay visual de zonas de precio.
     * Las zonas se administran en el módulo ZonasPrecioController (CRUD independiente).
     */
    public function edit(Evento $evento)
    {
        // Mesas (como ya lo tienes)
        $cols = Schema::getColumnListing('escenario');
        $hasX  = in_array('x', $cols);
        $hasY  = in_array('y', $cols);
        $hasRot= in_array('rot', $cols);

        $q = DB::table('escenario as e')
            ->join('mesa as m', 'm.id', '=', 'e.id_mesa')
            ->where('e.id_evento', $evento->id)
            ->select('e.id', 'e.id_mesa', 'm.sillas');

        $q->addSelect($hasX ? 'e.x' : DB::raw('0 as x'));
        $q->addSelect($hasY ? 'e.y' : DB::raw('0 as y'));
        $q->addSelect($hasRot ? 'e.rot' : DB::raw('0 as rot'));

        $mesas = $q->orderBy('e.id')->get();

        // ZONAS: todas las zonas del EVENTO (sólo para overlay visual)
        $zonas = DB::table('evento_zona_precio')
            ->where('evento_id', $evento->id)
            ->select('id','nombre','x','y','w','h','factor','color')
            ->orderBy('id')
            ->get();

        return view('dashboard.mapa-editor', compact('evento','mesas','zonas'));
    }


    /**
     * Guarda TODO el mapa (reemplaza layout del evento con las mesas enviadas).
     * Espera: [{sillas:int, x:int, y:int, rot:int}, ...]
     */
    public function save(Request $request, Evento $evento)
    {
        try {
            $data = $request->validate([
                'mesas'            => 'array',
                'mesas.*.sillas'   => 'required|integer|min:1|max:8',
                'mesas.*.x'        => 'required|integer|min:0',
                'mesas.*.y'        => 'required|integer|min:0',
                'mesas.*.rot'      => 'nullable|integer|min:0|max:359',
            ]);

            $mesas = $data['mesas'] ?? [];

            DB::transaction(function () use ($evento, $mesas) {
                // borrar layout anterior del evento
                $mesaIds = DB::table('escenario')
                    ->where('id_evento', $evento->id)
                    ->pluck('id_mesa');

                if ($mesaIds->count()) {
                    DB::table('escenario')->where('id_evento', $evento->id)->delete();
                    DB::table('mesa')->whereIn('id', $mesaIds)->delete();
                }

                // columnas disponibles
                $cols   = Schema::getColumnListing('escenario');
                $hasX   = in_array('x', $cols);
                $hasY   = in_array('y', $cols);
                $hasRot = in_array('rot', $cols);
                $hasHora= in_array('hora_reserva', $cols);
                $hasEst = in_array('estado', $cols);

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
                    if ($hasRot) $payload['rot'] = (int)($m['rot'] ?? 0);
                    if ($hasHora)$payload['hora_reserva'] = null;
                    if ($hasEst) $payload['estado'] = 'disponible';

                    DB::table('escenario')->insert($payload);
                }
            });

            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok'  => false,
                'msg' => $e->getMessage()
            ], 500);
        }
    }
}
