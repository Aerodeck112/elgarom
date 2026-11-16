@php
    /** @var array $f */
    $name = $name ?? $f['name'];
    $id = 'f-'.str_replace(['[', ']', '.'], '-', $name);
    $value = old($name, $value ?? null);
    $locked = isset($f['perm']) && ! can($f['perm']);
    $span = $f['span'] ?? ($f['type'] === 'textarea' ? 12 : 6);
    $required = is_array($f['rules'] ?? null) ? in_array('required', $f['rules'], true) : str_contains((string) ($f['rules'] ?? ''), 'required');
@endphp
<div class="field span-{{ $span }}">
    @if ($f['type'] === 'checkbox')
        <label class="check"><input type="hidden" name="{{ $name }}" value="0"><input type="checkbox" id="{{ $id }}" name="{{ $name }}" value="1" @checked($value) @disabled($locked)> <span>{{ $f['label'] }}</span></label>
    @else
        <label for="{{ $id }}">{{ $f['label'] }} @if ($required)<span class="req" aria-hidden="true">*</span>@endif</label>
        @if ($f['type'] === 'select')
            <select class="select" id="{{ $id }}" name="{{ $name }}" @disabled($locked) @required($required)>
                @unless ($required)<option value="">—</option>@endunless
                @foreach (\App\Admin\Form::options($f) as $k => $label)<option value="{{ $k }}" @selected((string) $value === (string) $k)>{{ $label }}</option>@endforeach
            </select>
        @elseif ($f['type'] === 'textarea' || $f['type'] === 'lines')
            <textarea class="textarea" id="{{ $id }}" name="{{ $name }}" rows="{{ $f['rows'] ?? 4 }}" @disabled($locked) @required($required)>{{ is_array($value) ? implode("\n", $value) : $value }}</textarea>
        @else
            <input class="input {{ in_array($f['type'], ['number', 'decimal'], true) ? 'input--num' : '' }}" id="{{ $id }}" name="{{ $name }}"
                type="{{ ['email' => 'email', 'url' => 'url', 'date' => 'date', 'number' => 'number'][$f['type']] ?? 'text' }}"
                @if ($f['type'] === 'decimal') inputmode="decimal" @endif
                value="{{ $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : ($f['type'] === 'decimal' && $value !== null && $value !== '' ? rtrim(rtrim(number_format((float) str_replace(',', '.', (string) $value), 4, ',', ''), '0'), ',') : $value) }}"
                @isset($f['placeholder']) placeholder="{{ $f['placeholder'] }}" @endisset @disabled($locked) @required($required)>
        @endif
    @endif
    @if ($locked)<p class="hint">Câmp rezervat rolurilor cu permisiunea «{{ config('permissions.labels.'.$f['perm']) }}».</p>
    @elseif (! empty($f['hint']))<p class="hint">{{ $f['hint'] }}</p>@endif
    @error($name)<p class="error-text">{{ $message }}</p>@enderror
</div>
