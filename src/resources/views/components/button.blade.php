@props([
    'route',
    'method' => 'post',
    'param' => null,
    'text',
    'class' => 'attendance_btn',
])

<form action="{{ $param ? route($route, $param) : route($route) }}" method="{{ strtolower($method) === 'get' ? 'get' : 'post' }}">
    @csrf
    @if(!in_array(strtolower($method), ['get', 'post']))
        @method($method)
    @endif
    <button type="submit" class="{{ $class }}">{{ $text }}</button>
</form>
