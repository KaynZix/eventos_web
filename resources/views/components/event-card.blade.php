@props(['event'])
@php
    $urlMap = url('/events/'.$event->slug.'/map');
    $poster = $event->poster_path ? asset('storage/'.$event->poster_path) : 'data:image/svg+xml;utf8,'.urlencode("<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 600 400'><rect width='600' height='400' fill='#141c31'/><text x='50%' y='50%' dominant-baseline='middle' text-anchor='middle' font-family='Arial' font-size='20' fill='white'>Afiche</text></svg>");
    $bases = $event->bases ?? '';
@endphp
<div class="card">
    <a href="#" class="js-open-event" 
       data-title="{{ e($event->title) }}"
       data-date="{{ $event->date }}"
       data-bases-uri="{{ rawurlencode($bases) }}"
       data-poster="{{ $poster }}"
       data-url-map="{{ $urlMap }}">
        <img class="thumb" src="{{ $poster }}" alt="{{ $event->title }}" loading="lazy">
    </a>
    <div class="content">
        <strong>{{ $event->title }}</strong>
        <div class="muted">{{ $event->date }}</div>
    </div>
</div>