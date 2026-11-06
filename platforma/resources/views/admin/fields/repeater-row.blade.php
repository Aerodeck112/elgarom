<div class="repeater__row" data-row>
    <div class="repeater__tools">
        <button class="btn btn--line btn--sm" type="button" data-up aria-label="Mută mai sus">Sus</button>
        <button class="btn btn--line btn--sm" type="button" data-down aria-label="Mută mai jos">Jos</button>
        <button class="btn btn--danger btn--sm" type="button" data-remove>Elimină</button>
    </div>
    @foreach ($f['fields'] as $sf)
        @include('admin.fields.field', ['f' => $sf, 'name' => $name.'['.$i.']['.$sf['name'].']', 'value' => $row[$sf['name']] ?? null, 'errKey' => str_replace(['[', ']'], ['.', ''], $name).'.'.$i.'.'.$sf['name']])
    @endforeach
</div>
