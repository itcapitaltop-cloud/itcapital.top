{{-- resources/views/fields/copyable-wallet-address.blade.php --}}
<td {{ $attributes }}>
    <div class="flex items-center">
        {{-- собственно значение --}}
        {{ $item->wallet_address }}
        {{-- Heroicons Clipboard --}}
        <x-heroicon-o-clipboard
            class="w-5 h-5 ml-2 cursor-pointer text-gray-500"
            title="{{ __('copy') }}"
            @click.stop="navigator.clipboard.writeText('{{ $item->wallet_address }}')"
        />
    </div>
</td>
