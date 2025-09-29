@php use App\Models\Partner;use Illuminate\Support\Facades\Auth;use Illuminate\Support\Facades\Log; @endphp
@props([
    'name',
    'list'        => [],
    'placeholder' => '',
])
<div
    x-data="{
        open  : false,
        query : '',
        list  : @js($list),
        filtered() {
            return this.list.filter(n =>
                n.toLowerCase().startsWith(this.query.toLowerCase())
            )
        },
        /* записываем выбранное значение в Livewire‑форму */
        choose(nick) {
            this.query = nick;
            this.open  = false;
            $wire.set('{{ $name }}', nick);
        }
    }"
    @click.outside="open = false"
    :class="open && query ? 'suggest-open' : ''"
    class="relative w-full group overflow-visible">

    <x-ui.input
        input-class="
            py-[5px] px-[12px]
            group-[.suggest-open]:rounded-b-none
        "
        :name="$name"
        x-bind:value="query"
        x-on:input="
            query = $event.target.value;
            $wire.set('{{ $name }}', query);
        "
        x-on:focusin="open = true"
        placeholder="{{ $placeholder }}">
        {{ $slot }}
    </x-ui.input>

    <ul
        x-show="open && query"
        x-transition

        class="absolute z-30 left-0 top-full w-full
               bg-[#17162D] border border-[#4731AC] rounded-b-[8px]
               divide-y divide-[#2E1D78] max-h-48 overflow-y-scroll text-white">

        <template x-for="nick in filtered()" :key="nick">
            <li>
                <button type="button"
                        class="w-full text-left py-[5px] px-[12px] hover:bg-[#232347]"
                        @click="choose(nick)"
                        x-text="nick"></button>
            </li>
        </template>

        <li x-show="filtered().length === 0">
            <span class="block py-[5px] px-[12px] text-white/60">
                {{ __('not_found') }}
            </span>
        </li>
    </ul>
</div>
