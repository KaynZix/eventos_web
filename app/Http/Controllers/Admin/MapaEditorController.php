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
            'mesas'              => 'array',
            'mesas.*.id_mesa'    => 'nullable|integer|exists:mesa,id',   // ← nuevo
            'mesas.*.sillas'     => 'required|integer|min:1|max:8',
            'mesas.*.x'          => 'required|integer|min:0',
            'mesas.*.y'          => 'required|integer|min:0',
            'mesas.*.rot'        => 'nullable|integer|min:0|max:359',
        ]);

        $mesas = $data['mesas'] ?? [];

        DB::transaction(function () use ($evento, $mesas) {
            // mesas ya vinculadas a este evento
            $existentes = DB::table('escenario')
                ->where('id_evento', $evento->id)
                ->pluck('id_mesa')
                ->all();

            $quedan = [];

            foreach ($mesas as $m) {
                $rot = (int)($m['rot'] ?? 0);

                if (!empty($m['id_mesa']) && in_array((int)$m['id_mesa'], $existentes, true)) {
                    // actualizar mesa existente
                    $mesaId = (int)$m['id_mesa'];

                    DB::table('mesa')->where('id', $mesaId)->update([
                        'sillas' => (int)$m['sillas'],
                    ]);

                    DB::table('escenario')
                        ->where('id_evento', $evento->id)
                        ->where('id_mesa', $mesaId)
                        ->update([
                            'id_escenario' => (int)$evento->id_escenario,
                            'x' => (int)$m['x'],
                            'y' => (int)$m['y'],
                            'rot' => $rot,
                        ]);

                    $quedan[] = $mesaId;
                } else {
                    // crear una nueva mesa
                    $mesaId = DB::table('mesa')->insertGetId([
                        'sillas' => (int)$m['sillas'],
                    ]);

                    DB::table('escenario')->insert([
                        'id_evento'    => (int)$evento->id,
                        'id_mesa'      => (int)$mesaId,
                        'id_escenario' => (int)$evento->id_escenario,
                        'x' => (int)$m['x'],
                        'y' => (int)$m['y'],
                        'rot' => $rot,
                    ]);

                    $quedan[] = $mesaId;
                }
            }

            // eliminar sólo las mesas que el usuario quitó del mapa
            if (!empty($existentes)) {
                $eliminar = array_diff($existentes, $quedan);
                if (!empty($eliminar)) {
                    DB::table('escenario')
                        ->where('id_evento', $evento->id)
                        ->whereIn('id_mesa', $eliminar)
                        ->delete();

                    DB::table('mesa')->whereIn('id', $eliminar)->delete();
                }
            }
        });

        return response()->json(['ok' => true]);
    } catch (\Throwable $e) {
        return response()->json(['ok' => false, 'msg' => $e->getMessage()], 500);
        }
    }
}
