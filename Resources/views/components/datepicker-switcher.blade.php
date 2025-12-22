<div class="datepicker-switcher" style="display:none">
    <input id="tgl_{{ $dateName }}"
        type="checkbox"
        {{ $active ? 'checked' : '' }}>

    <label class="tgl-btn"
        data-tg-off="{{ trans('jalali-date::general.datepicker_switcher.disable') }}"
        data-tg-on="{{ trans('jalali-date::general.datepicker_switcher.enable') }}"
        for="tgl_{{ $dateName }}">
    </label>
</div>