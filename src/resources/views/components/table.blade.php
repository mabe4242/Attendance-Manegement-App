@props([
    'headers' => [],
])

<table class="table__menu">
    <thead>
        <tr>
            @foreach ($headers as $header)
                <th class="attendance__column">{{ $header }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        {{ $slot }}
    </tbody>
</table>
