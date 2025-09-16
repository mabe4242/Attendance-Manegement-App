@props(['status'])

@php use App\Enums\RequestStatus; @endphp
<nav class="request__nav">
    <a href="{{ route('attendance_requests.index') }}?status={{ RequestStatus::PENDING }}"
       class="{{ $status == RequestStatus::PENDING ? 'active' : '' }}">
        {{ RequestStatus::label(RequestStatus::PENDING) }}
    </a>
    <a href="{{ route('attendance_requests.index') }}?status={{ RequestStatus::APPROVED }}"
       class="{{ $status == RequestStatus::APPROVED ? 'active' : '' }}">
        {{ RequestStatus::label(RequestStatus::APPROVED) }}
    </a>
</nav>
