
@php
    $current = old(
        'balance_type',
        $item->balance_type ?? 'main'
    );
@endphp

<div x-data="{ balance_type: '{{ $current }}' }">
    <div
        class="flex flex-col space-y-2"
    >

        @foreach($enum as $case)
            <label class="inline-flex items-center cursor-pointer space-x-2">
                <x-moonshine::form.input
                    type="radio"
                    name="balance_type"
                    value="{{ $case->value }}"
                    x-on:change="onChangeField($event)"
                    x-bind:checked="balance_type === '{{ $case->value }}'"
                />
                <span class="ml-2">{{ $case->toString() }}</span>
            </label>
        @endforeach
    </div>
</div>
