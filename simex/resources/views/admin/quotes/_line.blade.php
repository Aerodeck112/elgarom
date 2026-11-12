@php
    $v = fn ($k, $d = '') => $it[$k] ?? $d;
    $n = fn ($k, $dec = 4) => isset($it[$k]) && $it[$k] !== '' && $it[$k] !== null ? rtrim(rtrim(number_format((float) $it[$k], $dec, ',', ''), '0'), ',') : '';
    $p = "items[$i]";
@endphp
<tr data-line>
    <td class="mono" data-pos></td>
    <td><input type="hidden" name="{{ $p }}[id]" value="{{ $v('id') }}"><input type="hidden" name="{{ $p }}[calculation_id]" value="{{ $v('calculation_id') }}"><input type="hidden" name="{{ $p }}[tool_project_id]" value="{{ $v('tool_project_id') }}">
        <select class="select" name="{{ $p }}[kind]" aria-label="Tip">@foreach (\App\Support\Vocab::ITEM_KINDS as $k => $l)<option value="{{ $k }}" @selected($v('kind', 'piesa') === $k)>{{ \Illuminate\Support\Str::limit($l, 16) }}</option>@endforeach</select></td>
    <td><input class="input" name="{{ $p }}[code]" value="{{ $v('code') }}" size="10" aria-label="Cod"></td>
    <td><input class="input" name="{{ $p }}[name]" value="{{ $v('name') }}" size="26" aria-label="Denumire"><textarea class="textarea" name="{{ $p }}[description]" rows="1" style="min-height:32px;margin-top:4px" placeholder="Descriere tehnică (opțional)" aria-label="Descriere">{{ $v('description') }}</textarea></td>
    <td><input class="input input--num" data-f="quantity" name="{{ $p }}[quantity]" value="{{ $n('quantity', 3) ?: '1' }}" size="7" inputmode="decimal" aria-label="Cantitate"></td>
    <td><input class="input" name="{{ $p }}[unit]" value="{{ $v('unit', 'buc') }}" size="4" aria-label="U.M."></td>
    @if (can('costs.view'))<td><input class="input input--num" name="{{ $p }}[unit_cost]" value="{{ $n('unit_cost') }}" size="8" inputmode="decimal" aria-label="Cost unitar intern"></td>@endif
    <td><input class="input input--num" data-f="unit_price" name="{{ $p }}[unit_price]" value="{{ $n('unit_price') }}" size="9" inputmode="decimal" aria-label="Preț unitar"></td>
    <td><input class="input input--num" data-f="discount_percent" name="{{ $p }}[discount_percent]" value="{{ $n('discount_percent', 2) }}" size="4" inputmode="decimal" aria-label="Discount %" @disabled(! can('quotes.discount'))></td>
    <td class="num" data-total>0,00</td>
    <td><label class="check small"><input type="checkbox" data-f="is_optional" name="{{ $p }}[is_optional]" value="1" @checked($v('is_optional'))> opț.</label></td>
    <td class="actions"><button class="link-btn link-btn--danger" type="button" data-remove-line>Șterge</button></td>
</tr>
