<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cookie;
use App\Models\Evento;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class ReservasPublicController extends Controller
{
    /**
     * Inicia la reserva: valida mesas, crea holds (8 min),
     * guarda en sesión y deja un cookie para reanudar.
     * Recibe: evento_id, llegada (HH:MM), mesas[escenario_id...], flow(promos|pago)
     */
public function start(Request $req)
    {
        $v = $req->validate([
            'evento_id' => 'required|integer',
            'llegada'   => 'required|string',            // 'HH:MM'
            'mesas'     => 'required|array|min:1',       // IDs de tabla 'escenario' (e.id)
            'mesas.*'   => 'integer',
            'flow'      => 'required|in:promos,pago',
        ]);

        // Evento
        $evento = DB::table('eventos')->where('id', $v['evento_id'])->first();
        if (!$evento) {
            return response()->json(['ok'=>false,'msg'=>'Evento no encontrado'], 404);
        }

        // Mesas seleccionadas con estado actual
        $sel = DB::table('escenario as e')
            ->join('mesa as m', 'm.id', '=', 'e.id_mesa')
            ->leftJoin('reserva_mesa as rm', 'rm.escenario_id', '=', 'e.id')
            ->leftJoin('reserva as r', function($q) use ($v) {
                $q->on('r.id', '=', 'rm.reserva_id')
                  ->where('r.id_evento', '=', $v['evento_id']);
            })
            ->leftJoin('mesas_hold as h', function($q) use ($v) {
                $q->on('h.escenario_id', '=', 'e.id')
                  ->where('h.evento_id', '=', $v['evento_id'])
                  ->where('h.expires_at', '>', now());
            })
            ->where('e.id_evento', $v['evento_id'])
            ->whereIn('e.id', $v['mesas'])
            ->select([
                'e.id as escenario_id',
                'm.id as mesa_id',
                'm.sillas',
                'e.x','e.y',
                DB::raw('CASE WHEN r.id IS NULL THEN 0 ELSE 1 END as reservada'),
                DB::raw('CASE WHEN h.id IS NULL THEN 0 ELSE 1 END as bloqueada'),
            ])
            ->get();

        if ($sel->isEmpty() || $sel->count() !== count($v['mesas'])) {
            return response()->json(['ok'=>false,'msg'=>'Selección de mesas inválida'], 422);
        }

        // Validar disponibilidad
        foreach ($sel as $row) {
            if ((int)$row->reservada === 1) {
                return response()->json(['ok'=>false,'msg'=>"La mesa {$row->escenario_id} ya fue reservada"], 409);
            }
            if ((int)$row->bloqueada === 1) {
                return response()->json(['ok'=>false,'msg'=>"La mesa {$row->escenario_id} está temporalmente bloqueada"], 409);
            }
        }

        // Zonas del evento (recargo fijo por silla)
        $zonas = DB::table('evento_zona_precio')
            ->where('evento_id', $v['evento_id'])
            ->get();

        $base  = (int) $evento->precio_silla_base;
        $items = [];
        $total = 0;

        // Si algún día guardas tamaño real de la mesa, podrías usar el centro (x+W/2, y+H/2).
        // Por ahora, usamos (x,y) tal como se guarda en 'escenario'.
        foreach ($sel as $m) {
            $recargo = 0;
            foreach ($zonas as $z) {
                if ($m->x >= $z->x && $m->x < ($z->x + $z->w) &&
                    $m->y >= $z->y && $m->y < ($z->y + $z->h)) {
                    $recargo += (int) $z->factor;  // recargo fijo por silla
                }
            }
            $pp  = $base + $recargo;           // precio por silla final
            $sub = $pp * (int)$m->sillas;      // subtotal mesa

            $items[] = [
                'escenario_id'     => (int)$m->escenario_id,
                'mesa_id'          => (int)$m->mesa_id,
                'sillas'           => (int)$m->sillas,
                'precio_por_silla' => $pp,
                'subtotal'         => $sub,
            ];
            $total += $sub;
        }

        // (Opcional) Crear HOLD temporal para evitar colisiones
        $sessionId = $req->session()->getId();
        $holdToken = (string) Str::uuid();
        $expires   = now()->addMinutes(config('app.reserva_hold_minutes', 8));

        // Limpia holds previos de esta sesión para este evento
        DB::table('mesas_hold')
            ->where('evento_id', $v['evento_id'])
            ->where('session_id', $sessionId)
            ->delete();

        foreach ($sel as $m) {
            DB::table('mesas_hold')->insert([
                'evento_id'   => $v['evento_id'],
                'escenario_id'=> $m->escenario_id,
                'session_id'  => $sessionId,
                'hold_token'  => $holdToken,
                'expires_at'  => $expires,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        // Persistimos pre-reserva para la siguiente página
        session([
            'reserva.pre' => [
                'evento_id'    => (int)$evento->id,
                'llegada'      => $v['llegada'],
                'items'        => $items,
                'total'        => $total,
                'hold_token'   => $holdToken,
                'hold_expires' => $expires->toDateTimeString(),
            ],
        ]);

        // Redirección según flujo
        $redirect = $v['flow'] === 'promos'
            ? route('reservas.promos')    // crea estas rutas a tus vistas reales
            : route('reservas.checkout');

        return response()->json(['ok'=>true, 'redirect'=>$redirect]);
    }
    /** Segundos restantes del hold; <=0 si expiró */
    protected function remainingSecondsOrFail(): int
    {
        $hold = session('reserva.hold');
        if (!$hold) return 0;
        return Carbon::now()->diffInSeconds(Carbon::parse($hold['expires_at']), false);
    }

    /** Página de selección de promos (no suma al total) */
    public function promos()
    {
        $reserva = session('reserva');
        if (!$reserva) return redirect()->route('welcome');

        $left = $this->remainingSecondsOrFail();
        if ($left <= 0) {
            return redirect()->route('mapa.publico', $reserva['evento_id'])
                ->with('err','Tu tiempo para completar la reserva expiró.');
        }

        $promos = [
            ['id'=>1,'nombre'=>'Promo 2 schop + papas'],
            ['id'=>2,'nombre'=>'Promo Nachos + 2 bebestibles'],
            ['id'=>3,'nombre'=>'Cerveza 1L + papas'],
        ];

        return view('publico.promos', [
            'reserva'     => $reserva,
            'promos'      => $promos,
            'secondsLeft' => $left,
        ]);
    }

    public function promosStore(Request $r)
    {
        $reserva = session('reserva');
        if (!$reserva) return redirect()->route('welcome');

        $promos = $r->validate(['promos'=>'array'])['promos'] ?? [];
        $reserva['promos'] = array_values($promos);
        session(['reserva'=>$reserva]);

        return redirect()->route('reservas.checkout');
    }

    /** Checkout: datos del cliente y confirmación SIN pago (por ahora) */
    public function checkout()
    {
        $reserva = session('reserva');
        if (!$reserva) return redirect()->route('welcome');

        $left = $this->remainingSecondsOrFail();
        if ($left <= 0) {
            return redirect()->route('mapa.publico', $reserva['evento_id'])
                ->with('err','Tu tiempo para completar la reserva expiró.');
        }

        $evento = Evento::findOrFail($reserva['evento_id']);
        $total  = 0;

        return view('publico.checkout', [
            'reserva'     => $reserva,
            'evento'      => $evento,
            'total'       => $total,
            'secondsLeft' => $left,
        ]);
    }

    /** Crea la reserva y asocia mesas en pivote reserva_mesa */
    public function checkoutStore(Request $r)
    {
        $reserva = session('reserva');
        if (!$reserva) return redirect()->route('eventos.index');

        // Hold vigente
        $left = $this->remainingSecondsOrFail();
        if ($left <= 0) {
            return redirect()->route('mapa.publico', $reserva['evento_id'])
                ->with('err','El tiempo expiró, vuelve a elegir las mesas.');
        }

        $data = $r->validate([
            'nombre'     => 'required|string|max:80',
            'correo'     => 'required|email|max:120',
            'numero_wsp' => 'required|string|max:20',
        ]);

        DB::beginTransaction();
        try {
            $reservaId = DB::table('reserva')->insertGetId([
                'nombre_cliente' => $data['nombre'],
                'correo'         => $data['correo'],
                'numero_wsp'     => $data['numero_wsp'],
                'hora_reserva'   => $reserva['llegada'],
                'mesa'           => 0,
                'id_evento'      => $reserva['evento_id'],
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            foreach ($reserva['mesas'] as $escenarioId) {
                DB::table('reserva_mesa')->insert([
                    'reserva_id'   => $reservaId,
                    'escenario_id' => $escenarioId,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }

            // Liberar holds de este usuario/token
            DB::table('mesas_hold')->where('hold_token', $reserva['hold']['token'])->delete();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors('No se pudo guardar la reserva: '.$e->getMessage())->withInput();
        }

        // Limpia sesión y cookie
        session()->forget('reserva');
        Cookie::queue(Cookie::forget('reserva_hold'));

        return redirect()->route('reservas.ok')->with('ok', 'Reserva creada #'.$reservaId);
    }

    public function ok()
    {
        return view('publico.ok');
    }

    /** Cancelar: libera holds, borra cookie y limpia sesión */
    public function cancel(Request $r)
    {
        $reserva = session('reserva');

        if ($reserva && isset($reserva['hold']['token'])) {
            DB::table('mesas_hold')->where('hold_token', $reserva['hold']['token'])->delete();
        } else {
            // Si no hay sesión, intenta por cookie
            $cookie = $r->cookie('reserva_hold');
            if ($cookie) {
                $meta = json_decode($cookie, true);
                if (!empty($meta['token'])) {
                    DB::table('mesas_hold')->where('hold_token', $meta['token'])->delete();
                }
            }
        }

        session()->forget('reserva');
        Cookie::queue(Cookie::forget('reserva_hold'));

        return redirect()->route('eventos.index')->with('ok', 'Reserva cancelada.');
    }

    /** Reanudar reserva a partir del cookie si los holds siguen vigentes */
    public function resume(Request $r)
    {
        // 1) Si ya hay reserva en sesión y hold vigente, continuar
        $reserva = session('reserva');
        if ($reserva && isset($reserva['hold']['token'])) {
            $left = $this->remainingSecondsOrFail();
            if ($left > 0) {
                return redirect()->route('reservas.checkout');
            }
        }

        // 2) Intentar por cookie
        $cookie = $r->cookie('reserva_hold');
        if (!$cookie) {
            return redirect()->route('eventos.index')->with('err','No hay una reserva activa para reanudar.');
        }

        $meta = json_decode($cookie, true);
        if (!$meta || empty($meta['token']) || empty($meta['evento_id'])) {
            return redirect()->route('eventos.index')->with('err','No hay una reserva activa para reanudar.');
        }

        // 3) Cargar holds vigentes
        $holds = DB::table('mesas_hold')
            ->where('hold_token', $meta['token'])
            ->where('evento_id', $meta['evento_id'])
            ->where('expires_at', '>', now())
            ->get(['escenario_id','expires_at']);

        if ($holds->isEmpty()) {
            Cookie::queue(Cookie::forget('reserva_hold'));
            return redirect()->route('mapa.publico', $meta['evento_id'])
                ->with('err', 'El tiempo de tu reserva expiró. Vuelve a elegir las mesas.');
        }

        // 4) Reconstruir sesión "reserva"
        $expiresAt = Carbon::parse($holds->min('expires_at'));
        session([
            'reserva' => [
                'evento_id' => (int)$meta['evento_id'],
                'llegada'   => $meta['llegada'] ?? '22:00',
                'mesas'     => $holds->pluck('escenario_id')->map(fn($v)=>(int)$v)->values()->all(),
                'promos'    => [],
                'hold'      => [
                    'token'      => $meta['token'],
                    'expires_at' => $expiresAt->toIso8601String(),
                ],
            ]
        ]);

        // 5) Redirigir según flow original
        if (($meta['flow'] ?? 'pago') === 'promos') {
            return redirect()->route('reservas.promos')->with('ok','Reanudaste tu reserva.');
        }
        return redirect()->route('reservas.checkout')->with('ok','Reanudaste tu reserva.');
    }
}
