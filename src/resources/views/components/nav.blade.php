@php use App\Enums\RequestStatus; @endphp

@props([
    'status',
    'routeName' => 'attendance_requests.index',
])

<nav class="request__nav">
    <a href="{{ route($routeName) }}?status={{ RequestStatus::PENDING }}"
       class="{{ $status == RequestStatus::PENDING ? 'active' : '' }}">
        {{ RequestStatus::label(RequestStatus::PENDING) }}
    </a>
    <a href="{{ route($routeName) }}?status={{ RequestStatus::APPROVED }}"
       class="{{ $status == RequestStatus::APPROVED ? 'active' : '' }}">
        {{ RequestStatus::label(RequestStatus::APPROVED) }}
    </a>
</nav>
