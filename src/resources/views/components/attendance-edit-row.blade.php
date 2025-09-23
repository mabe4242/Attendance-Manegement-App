@props(['label'])

<tr class="detail__table--row">
    <th class="table__label">{{ $label }}</th>
    <td class="table__data">
        {{ $slot }}
    </td>
</tr>
