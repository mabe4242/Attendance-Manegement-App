@props(['label', 'class' => ''])

<tr class="detail__table--row {{ $class }}">
    <th class="table__label">{{ $label }}</th>
    <td class="table__data">
        {{ $slot }}
    </td>
</tr>
