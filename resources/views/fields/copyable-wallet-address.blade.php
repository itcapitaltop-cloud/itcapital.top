{{-- resources/views/fields/copyable-wallet-address.blade.php --}}
<td {{ $attributes }}>
    <div class="flex items-center">
        {{-- собственно значение --}}
        {{ $item->wallet_address }}
        {{-- Heroicons Clipboard --}}
    </div>
</td>
