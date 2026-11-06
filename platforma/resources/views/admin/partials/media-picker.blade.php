<dialog class="picker" data-picker aria-labelledby="picker-title">
    <div class="page-title"><h2 id="picker-title">Alege imagine</h2><button class="btn btn--line btn--sm" type="button" data-picker-close>Închide</button></div>
    <div class="filters"><div class="field"><label for="picker-q">Caută</label><input class="input" id="picker-q" data-picker-q></div>
    <a class="btn btn--line btn--sm" href="{{ route('admin.media.index') }}" target="_blank">Încarcă imagini noi</a></div>
    <div class="media-grid" data-picker-grid data-src="{{ route('admin.media.json') }}"></div>
</dialog>
