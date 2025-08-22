<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Eventos — Reservas' }}</title>
    <style>
        :root{ --bg:#0f1220; --panel:#171a2b; --muted:#9aa3b2; --text:#e8ecf1; --accent:#60a5fa; }
        *{ box-sizing:border-box }
        body{ margin:0; font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; background:#0f1220; color:#e8ecf1 }
        a{ color:var(--accent); text-decoration:none }
        .container{ max-width:1200px; margin:0 auto; padding:16px }
        header{ position:sticky; top:0; background:#11152a; border-bottom:1px solid #23263a; z-index:10 }
        .grid{ display:grid; grid-template-columns: repeat(auto-fill, minmax(220px,1fr)); gap:12px }
        .card{ background:#171a2b; border:1px solid #23263a; border-radius:12px; overflow:hidden }
        .thumb{ width:100%; aspect-ratio:3/2; object-fit:cover; display:block; background:#0c0f1f }
        .content{ padding:12px }
        .muted{ color:#9aa3b2; font-size:14px }

        /* Home two-column layout */
        .home-layout{ display:grid; grid-template-columns: 1fr 260px; gap:16px }
        .banners-column{ position:sticky; top:72px; height:fit-content; display:flex; flex-direction:column; gap:12px }
        .banner{ background:#101527; border:1px solid #2b3150; border-radius:12px; overflow:hidden }
        .banner img{ width:100%; height:120px; object-fit:cover; display:block }

        @media (max-width: 900px){ .home-layout{ grid-template-columns: 1fr } .banners-column{ position:static } }

        /* Modal */
        .modal{ position:fixed; inset:0; display:none; align-items:center; justify-content:center; background:rgba(0,0,0,.6); z-index:50 }
        .modal.open{ display:flex }
        .modal-card{ background:#171a2b; border:1px solid #23263a; border-radius:12px; width:min(900px, 92vw); max-height:90vh; overflow:auto }
        .modal-header{ display:flex; align-items:center; justify-content:space-between; padding:12px 16px; border-bottom:1px solid #23263a }
        .modal-body{ display:grid; grid-template-columns: 1fr 1.2fr; gap:16px; padding:16px }
        .modal-body img{ width:100%; aspect-ratio:3/2; object-fit:cover; border-radius:8px; background:#0c0f1f }
        .close{ background:transparent; color:#e8ecf1; border:1px solid #2b3150; border-radius:8px; padding:6px 10px; cursor:pointer }
        @media (max-width: 720px){ .modal-body{ grid-template-columns: 1fr } }
    </style>
</head>
<body>
<header>
    @include('partials.header')
</header>

<main class="container">
    @yield('content')
</main>

<footer class="container" style="padding:24px 16px; color:#9aa3b2">
    <small>© {{ date('Y') }} Eventos & Reservas</small>
</footer>
</body>
</html>
