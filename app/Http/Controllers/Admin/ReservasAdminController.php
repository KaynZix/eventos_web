<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

class ReservasAdminController extends Controller
{
    /** Lista de reservas con filtros simples */
    public function index(Request $r)
    {
        $q        = trim((string)$r->input('q', ''));
        $eventoId = $r->input('evento_id');

        // Subconsulta agregada por reserva para evitar ONLY_FULL_GROUP_BY
        $agg = DB::table('reserva_mesa as rm')
            ->join('escenario as es', 'es.id', '=', 'rm.escenario_id')
            ->join('mesa as m', 'm.id', '=', 'es.id_mesa')
            ->select(
                'rm.reserva_id',
                DB::raw('COUNT(*) as cant_mesas'),
                DB::raw('GROUP_CONCAT(es.id ORDER BY es.id SEPARATOR ", ") as mesas'),
                DB::raw('SUM(m.precio) as suma_mesas')
            )
            ->groupBy('rm.reserva_id');

        $rows = DB::table('reserva as r')
            ->join('eventos as ev', 'ev.id', '=', 'r.id_evento')
            ->leftJoinSub($agg, 'x', fn($j) => $j->on('x.reserva_id', '=', 'r.id'))
            ->when($q, function ($sql) use ($q) {
                $like = "%{$q}%";
                $sql->where(function ($w) use ($like) {
                    $w->where('r.nombre_cliente', 'like', $like)
                      ->orWhere('r.correo', 'like', $like)
                      ->orWhere('r.numero_wsp', 'like', $like);
                });
            })
            ->when($eventoId, fn($sql) => $sql->where('r.id_evento', (int)$eventoId))
            ->select(
                'r.id',
                'r.nombre_cliente',
                'r.correo',
                'r.numero_wsp',
                'r.hora_reserva',
                'r.total',
                'r.created_at',
                'ev.id as evento_id',
                'ev.nombre as evento',
                DB::raw('COALESCE(x.cant_mesas,0) as cant_mesas'),
                DB::raw('COALESCE(x.mesas,"") as mesas'),
                DB::raw('COALESCE(x.suma_mesas,0) as suma_mesas')
            )
            ->orderByDesc('r.id')
            ->paginate(20)
            ->appends($r->query());

        $eventos = DB::table('eventos')->select('id','nombre')->orderBy('nombre')->get();

        return view('dashboard.reservas.index', compact('rows','eventos','q','eventoId'));
    }

    /** Detalle de una reserva con sus mesas */
    public function show(int $id)
    {
        $reserva = DB::table('reserva as r')
            ->join('eventos as ev', 'ev.id', '=', 'r.id_evento')
            ->where('r.id', $id)
            ->select(
                'r.*',
                'ev.nombre as evento',
                'ev.fecha',
                'ev.hora_inicio',
                'ev.hora_termino'
            )->first();

        abort_if(!$reserva, 404);

        $mesas = DB::table('reserva_mesa as rm')
            ->join('escenario as es', 'es.id', '=', 'rm.escenario_id')
            ->join('mesa as m', 'm.id', '=', 'es.id_mesa')
            ->where('rm.reserva_id', $id)
            ->select('es.id as escenario_id', 'm.id as mesa_id', 'm.sillas', 'm.precio')
            ->orderBy('es.id')
            ->get();

        $sum = (int)$mesas->sum('precio'); // verificación opcional contra r.total

        return view('reservas.show', [
            'reserva' => $reserva,
            'mesas'   => $mesas,
            'sum'     => $sum,
        ]);
    }
}
