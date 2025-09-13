@component('mail::message')
# ¡Mañana es tu evento!

**Evento:** {{ $evento['nombre'] }}  
**Fecha y hora:** {{ $evento['fecha'] }} {{ $evento['hora'] }}

@component('mail::table')
| Mesa | Sillas |
|:---:|:-----:|
@foreach($items as $it)
| {{ $it['escenario_id'] }} | {{ $it['sillas'] }} |
@endforeach
@endcomponent

**Total reservado:** {{ '$'.number_format($total,0,',','.') }}

¡Te esperamos!  
@endcomponent
