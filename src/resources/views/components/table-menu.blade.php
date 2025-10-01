@props([
    'prevUrl',
    'main',
    'nextUrl',
    'prev',
    'next',
])

<div class="attendance__month">
    <a href="{{ $prevUrl }}" class="prev__month">
        <img src="{{ asset('icons/arrow.png') }}" alt="矢印形の画像" class="arrow__left"> {{ $prev }}
    </a>
    <span class="current__month">
        <img src="{{ asset('icons/month.png') }}" alt="カレンダーの画像" class="month__icon"> {{ $main }}
    </span>
    <a href="{{ $nextUrl }}" class="next__month">
        {{ $next }} <img src="{{ asset('icons/arrow.png') }}" alt="矢印形の画像" class="arrow__right">
    </a>
</div>
