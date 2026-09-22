{{--
    Tabel data. Kepala kolom dikirim lewat :kepala.
    Tiap kepala boleh string, atau ['teks' => ..., 'kanan' => true] untuk angka.
--}}
@props(['kepala' => []])
<div {{ $attributes->merge(['class' => 'overflow-x-auto']) }}>
    <table class="w-full border-collapse text-left">
        <thead>
            <tr class="border-b border-line">
                @foreach($kepala as $k)
                    @php $k = is_array($k) ? $k : ['teks' => $k]; @endphp
                    <th scope="col" class="px-4 py-3 text-sm font-medium whitespace-nowrap text-ink-2 {{ ($k['kanan'] ?? false) ? 'text-right' : '' }}">
                        {{ $k['teks'] }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-line-soft text-sm text-ink [&_td]:px-4 [&_td]:py-3">
            {{ $slot }}
        </tbody>
        @isset($kaki)
            <tfoot class="border-t border-line text-sm font-medium text-ink [&_td]:px-4 [&_td]:py-3">
                {{ $kaki }}
            </tfoot>
        @endisset
    </table>
</div>
