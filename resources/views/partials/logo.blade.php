{{-- Logo M-SmartTax. $susun = true menaruh tulisan di bawah gambar. --}}
@php $susun = $susun ?? false; @endphp
<div class="flex items-center justify-center {{ $susun ? 'flex-col gap-2' : 'gap-3' }}">
    <img src="{{ asset('images/logo.png') }}" alt="" class="{{ $susun ? 'h-16' : 'h-14' }} w-auto">
    <span class="{{ $susun ? 'text-2xl' : 'text-[30px]' }} leading-none font-medium tracking-tight text-merek">M-SmartTax</span>
</div>
