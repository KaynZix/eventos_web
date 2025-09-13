@component('mail::message')
# ¡Reserva confirmada!

**Evento:** {{ $evento['nombre'] }}  
**Llegada:** {{ $llegada }}  
**N° de reserva:** {{ $reservaId }}

@component('mail::table')
| Mesa | Sillas | Precio |
|:---:|:-----:|------:|
@foreach($items as $it)
| {{ $it['escenario_id'] }} | {{ $it['sillas'] }} | {{ '$'.number_format($it['precio'],0,',','.') }} |
@endforeach
@endcomponent

**Total:** {{ '$'.number_format($total,0,',','.') }}

**A nombre de:** {{ $cliente['nombre'] }}  
**Correo:** {{ $cliente['correo'] }}  
**WhatsApp:** {{ $cliente['numero_wsp'] }}

Gracias por tu reserva.  
@endcomponent
