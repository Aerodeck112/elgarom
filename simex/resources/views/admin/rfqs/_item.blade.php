@php $v = fn ($k) => $it[$k] ?? ''; $n = fn ($k) => isset($it[$k]) && $it[$k] !== '' && $it[$k] !== null ? rtrim(rtrim(number_format((float) $it[$k], 3, ',', ''), '0'), ',') : ''; @endphp
<tr data-row>
    <td><select class="select" name="items[{{ $i }}][service]">@foreach (\App\Support\Vocab::SERVICES as $k => $l)<option value="{{ $k }}" @selected($v('service') === $k)>{{ \Illuminate\Support\Str::limit($l, 18) }}</option>@endforeach</select></td>
    <td><input class="input" name="items[{{ $i }}][part_code]" value="{{ $v('part_code') }}" size="10"></td><td><input class="input" name="items[{{ $i }}][part_name]" value="{{ $v('part_name') }}" size="24"></td>
    <td><input class="input" name="items[{{ $i }}][drawing_no]" value="{{ $v('drawing_no') }}" size="10"></td><td><input class="input" name="items[{{ $i }}][revision]" value="{{ $v('revision') }}" size="3"></td>
    <td><input class="input" name="items[{{ $i }}][material]" value="{{ $v('material') }}" size="12"></td>
    <td><input class="input input--num" name="items[{{ $i }}][quantity]" value="{{ $n('quantity') }}" size="7" inputmode="decimal"></td>
    <td><input class="input input--num" name="items[{{ $i }}][annual_quantity]" value="{{ $n('annual_quantity') }}" size="7" inputmode="decimal"></td>
    <td><input class="input input--num" name="items[{{ $i }}][lot_size]" value="{{ $n('lot_size') }}" size="6" inputmode="decimal"></td>
    <td class="actions"><button class="link-btn link-btn--danger" type="button" data-remove-row>Șterge</button></td>
</tr>
