@props(['index', 'break', 'errors'])

<tr class="attendance__break--row">
    <th class="table__label">
        {{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}
    </th>
    <td class="table__deta">
        <div class="break">
            <input type="text" name="breaks[{{ $index }}][break_start]" class="break-input"
                   value="{{ old("breaks.$index.break_start", $break->break_start_formatted ?? '') }}">
            <span>~</span>
            <input type="text" name="breaks[{{ $index }}][break_end]" class="break-input"
                   value="{{ old("breaks.$index.break_end", $break->break_end_formatted ?? '') }}">
        </div>
        @if($errors->has("breaks.$index.break_start"))
            <p class="error__message">{{ $errors->first("breaks.$index.break_start") }}</p>
        @endif
        @if($errors->has("breaks.$index.break_end"))
            <p class="error__message">{{ $errors->first("breaks.$index.break_end") }}</p>
        @endif
    </td>
</tr>
