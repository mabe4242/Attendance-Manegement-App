@props([
    'prevMonthUrl',
    'month',
    'nextMonthUrl',
])

<div class="attendance__month">
    <a href="{{ $prevMonthUrl }}" class="prev__month">
        <img src="{{ asset('icons/arrow.png') }}" alt="矢印形の画像" class="arrow__left"> 前月
    </a>
    <span class="current__month">
        <img src="{{ asset('icons/month.png') }}" alt="カレンダーの画像" class="month__icon"> {{ $month }}
    </span>
    <a href="{{ $nextMonthUrl }}" class="next__month">
        翌月 <img src="{{ asset('icons/arrow.png') }}" alt="矢印形の画像" class="arrow__right">
    </a>
</div>
