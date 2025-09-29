<li x-on:click="currentTab = '{{ $tab }}'"
    x-bind:class="'{{ $tab }}' === currentTab ? 'border-white text-white' : 'text-gray-300 border-b-transparent'"
    class="border-b px-4 py-2.5 cursor-auto select-none">
    {{ $slot }}
</li>
