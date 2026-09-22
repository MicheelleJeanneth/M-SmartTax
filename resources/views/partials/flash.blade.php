@if(session('sukses'))
    <div role="status" class="mb-6 flex items-center gap-3 rounded-field bg-ok-bg px-4 py-3 text-sm text-ok-ink">
        <x-icon name="circle-check" :size="18" /> {{ session('sukses') }}
    </div>
@endif
@if(session('galat'))
    <div role="alert" class="mb-6 flex items-center gap-3 rounded-field bg-warn-bg px-4 py-3 text-sm text-warn-ink">
        <x-icon name="triangle-alert" :size="18" /> {{ session('galat') }}
    </div>
@endif
