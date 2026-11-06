{{-- Generic field. Vars: $f (definition), $name (input name), $value, $errKey (dot notation) --}}
@php
    $id = 'f-'.preg_replace('/[^a-z0-9]+/i', '-', $name);
    $errKey = $errKey ?? str_replace(['[', ']'], ['.', ''], $name);
    $invalid = $errors->has($errKey);
@endphp
@switch($f['type'])
    @case('checkbox')
        <div class="field">
            <input type="hidden" name="{{ $name }}" value="0">
            <label class="check"><input type="checkbox" id="{{ $id }}" name="{{ $name }}" value="1" @checked($value)> <span>{{ $f['label'] }}</span></label>
            @if (! empty($f['hint']))<p class="hint">{{ $f['hint'] }}</p>@endif
        </div>
        @break
    @case('repeater')
        <div class="field">
            <span class="label">{{ $f['label'] }}</span>
            <div class="repeater" data-repeater data-name="{{ $name }}">
                @foreach (array_values((array) $value) as $i => $row)
                    @include('admin.fields.repeater-row', ['f' => $f, 'name' => $name, 'i' => $i, 'row' => $row])
                @endforeach
                <template>@include('admin.fields.repeater-row', ['f' => $f, 'name' => $name, 'i' => '__i__', 'row' => []])</template>
                <div><button class="btn btn--line btn--sm" type="button" data-repeater-add>Adaugă element</button></div>
            </div>
        </div>
        @break
    @case('media')
        @php $m = media_item($value); @endphp
        <div class="field">
            <span class="label" id="{{ $id }}-l">{{ $f['label'] }}</span>
            <div class="media-pick" data-media-pick>
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                <img src="{{ $m ? $m->variantUrl(480) : '' }}" alt="" @if (! $m) hidden @endif>
                <button class="btn btn--line btn--sm" type="button" data-media-open aria-describedby="{{ $id }}-l">Alege imagine</button>
                <button class="link-btn small" type="button" data-media-clear @if (! $m) hidden @endif>Elimină</button>
            </div>
            @if ($invalid)<p class="err">{{ $errors->first($errKey) }}</p>@endif
        </div>
        @break
    @case('media_multi')
        <div class="field">
            <span class="label">{{ $f['label'] }}</span>
            <div data-media-multi data-name="{{ $name }}[]">
                <div class="media-multi" data-list>
                    @foreach ((array) $value as $mid)
                        @if ($mm = media_item($mid))
                            <figure data-id="{{ $mm->id }}"><img src="{{ $mm->variantUrl(480) }}" alt="{{ $mm->alt }}"><input type="hidden" name="{{ $name }}[]" value="{{ $mm->id }}"><div class="tools"><button type="button" data-left aria-label="Mută la stânga">‹</button><button type="button" data-remove aria-label="Elimină">×</button><button type="button" data-right aria-label="Mută la dreapta">›</button></div></figure>
                        @endif
                    @endforeach
                </div>
                <button class="btn btn--line btn--sm" type="button" data-media-open style="margin-top:.5rem">Adaugă imagini</button>
            </div>
        </div>
        @break
    @case('select')
        <div class="field">
            <label for="{{ $id }}">{{ $f['label'] }}</label>
            <select class="select" id="{{ $id }}" name="{{ $name }}" @if ($invalid) aria-invalid="true" @endif>
                @if (! str_contains(is_array($f['rules'] ?? '') ? '' : ($f['rules'] ?? ''), 'required'))<option value="">—</option>@endif
                @foreach ($f['options'] as $k => $label)<option value="{{ $k }}" @selected((string) $value === (string) $k)>{{ $label }}</option>@endforeach
            </select>
            @if ($invalid)<p class="err">{{ $errors->first($errKey) }}</p>@endif
        </div>
        @break
    @case('file')
        <div class="field">
            <label for="{{ $id }}">{{ $f['label'] }}</label>
            @if (! empty($item?->file_name))<p class="small">Fișier curent: <a href="{{ route('admin.documents.file', $item->id) }}">{{ $item->file_name }}</a> ({{ number_format(($item->file_size ?? 0) / 1048576, 2, ',', '') }} MB)</p>@endif
            <input class="input" type="file" id="{{ $id }}" name="{{ $name }}" accept=".pdf,.jpg,.jpeg,.png">
            @if ($invalid)<p class="err">{{ $errors->first($errKey) }}</p>@endif
        </div>
        @break
    @default
        <div class="field">
            <label for="{{ $id }}">{{ $f['label'] }}</label>
            @if (in_array($f['type'], ['textarea', 'markdown'], true))
                <textarea class="textarea" id="{{ $id }}" name="{{ $name }}" rows="{{ $f['rows'] ?? ($f['type'] === 'markdown' ? 10 : 4) }}" @if ($invalid) aria-invalid="true" @endif>{{ $value }}</textarea>
                @if ($f['type'] === 'markdown')<p class="hint">Formatare: **îngroșat**, liste cu „- ”, titluri cu „## ”, linkuri [text](https://…). HTML-ul nu este permis.</p>@endif
            @else
                <input class="input" id="{{ $id }}" name="{{ $name }}" value="{{ $value instanceof \Carbon\CarbonInterface ? $value->format('Y-m-d') : $value }}"
                       type="{{ ['number' => 'number', 'date' => 'date', 'url' => 'text'][$f['type']] ?? 'text' }}"
                       @if (! empty($f['max'])) maxlength="{{ $f['max'] }}" @endif
                       @if ($f['type'] === 'slug') data-slug-from="f-{{ preg_replace('/[^a-z0-9]+/i', '-', str_replace($f['name'], $f['from'], $name)) }}" @endif
                       @if ($invalid) aria-invalid="true" @endif>
            @endif
            @if (! empty($f['hint']))<p class="hint">{{ $f['hint'] }}</p>@endif
            @if ($invalid)<p class="err">{{ $errors->first($errKey) }}</p>@endif
        </div>
@endswitch
