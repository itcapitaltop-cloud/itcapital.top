@props([
    'type'         => 'text',
    'name',
    'placeholder'  => '',
    'required' => false,
    'readonly' => false,
    'validate'     => null,
    'confirmWith'  => null,
    'notice'       => null,
    'value'        => '',
    'inputClass'  => '',
    'step'       => null,
    'autocomplete' => null,
    'icon' => null,
    'network' => strtoupper(config('wallet.network'))
])

<label
    {{ $attributes->class(['relative block', 'pt-[22px]' => ! $slot->isEmpty(),]) }}
    x-data="fieldValidator('{{ $validate }}', '{{ $confirmWith }}', '{{ $notice }}')"
    x-on:submit.window="
        if (visible) {
            visible = false;
        }
    "
>
    <input
        x-ref="input"
        x-model="value"
        x-init="console.log(visible)"
        @input="validateField($event)"
        @invalid.prevent
        :class="errorText ? 'border-red-500' : ''"
        type="{{ $type }}"
        name="{{ $name }}"
        @if ($readonly) readonly @endif
        value="{{ $value }}"
        @unless ($readonly)
            wire:model.defer="{{ $name }}"
        @endunless
        placeholder="{{ $placeholder }}"
        @if ($required) required @endif

        @if($validate === 'wallet')
            data-network="{{ $network }}"
        @endif
        autocomplete="{{ $autocomplete }}"
        @if($type === 'text' && $step)
            step="{{ $step }}"
        @endif

        @isset($attributes['x-bind:placeholder'])
            x-bind:placeholder="{{ $attributes['x-bind:placeholder'] }}"
        @endisset
        @isset($attributes['x-bind:readonly'])
            x-bind:readonly="{{ $attributes['x-bind:readonly'] }}"
        @endisset

        class="peer w-full rounded-[8px] border border-[#2E1D78] bg-[#17162D]
               {{ $type === 'password' ? ' pr-[46px]' : '' }}
               {{ $type === 'number' ? ' no-spinner' : '' }}
               {{ $icon ? ' pl-[32px]' : '' }}
               text-[16px] text-white transition
               placeholder:text-white/30 placeholder:text-[16px]
               focus:border-[#4731AC] focus-visible:outline-none
               disabled:opacity-60 disabled:pointer-events-none {{ $inputClass }}">

    @if ($icon)
        <img src="{{ $icon }}"
             class="absolute left-[12px] top-[38px] -translate-y-1/2 pointer-events-none"
             alt="">
    @endif

    <span
        class="absolute left-0 top-[-10px] text-[16px] font-medium transition-all select-none peer-focus:text-white"
        :class="errorText ? 'text-red-500' : 'text-white'">
        {{ $slot }}
    </span>

    {{-- кнопка‑глаз --}}
    @if ($type === 'password')
        <button type="button"
                @click="toggleVisibility"
                class="absolute right-[12px] top-[70%] -translate-y-1/2 group">
            <img :src="visible
                   ? '{{ vite()->icon('/actions/eye.svg') }}'
                   : '{{ vite()->icon('/actions/eye-slash.svg') }}'"
                 class="w-[18px] select-none" alt="">
        </button>
    @endif

    {{-- текст ошибки / подсказки --}}
    <p x-show="errorText" x-text="errorText"
       class="absolute left-0 top-full mt-[3px] pl-1 text-[12px] text-red-500"></p>

    <p x-show="!errorText && noticeText && !serverErr" x-text="noticeText"
       class="absolute left-0 top-full mt-[3px] pl-1 text-[12px] text-[#8b8a96]"></p>

    {{-- fallback‑ошибка Livewire --}}
    @error($name)
        <p x-ref="serverError"
           x-init="serverErr = true; noticeText = ''"
            class="absolute left-0 top-full mt-[3px] pl-1 text-[12px] text-red-500 leading-none">
            {{ $message }}
        </p>
    @enderror
</label>


@script
    <script>
        const i18n = {
            field_validator_username_latin_only: @json(__('field_validator_username_latin_only')),
            field_validator_email_invalid_format: @json(__('field_validator_email_invalid_format')),
            field_validator_password_requirements: @json(__('field_validator_password_requirements')),
            field_validator_passwords_mismatch: @json(__('field_validator_passwords_mismatch')),
            field_validator_wallet_invalid_format: @json(__('field_validator_wallet_invalid_format')),
            field_validator_number_step_multiple: @json(__('field_validator_number_step_multiple')),
            field_validator_telegram_format: @json(__('field_validator_telegram_format')),
            field_validator_username_notice: @json(__('field_validator_username_notice')),
            field_validator_password_notice: @json(__('field_validator_password_notice'))
        };
        /*  регистрация компонента ------------------------------------------- */
        Alpine.data('fieldValidator', (kind, confirmWith, notice) => ({
            value: '',
            noticeText: notice,
            originalNotice: notice,
            errorText: '',
            visible: false,
            serverErr: false,

            patterns: {
                ERC20:/^0x[a-fA-F0-9]{40}$/,
                BEP20:/^0x[a-fA-F0-9]{40}$/,
                POLYGON:/^0x[a-fA-F0-9]{40}$/,
                ARBITRUM:/^0x[a-fA-F0-9]{40}$/,
                OPTIMISM:/^0x[a-fA-F0-9]{40}$/,
                AVALANCHE:/^0x[a-fA-F0-9]{40}$/,
                FANTOM:/^0x[a-fA-F0-9]{40}$/,
                BASE:/^0x[a-fA-F0-9]{40}$/,
                TRC20:/^T[1-9A-HJ-NP-Za-km-z]{33}$/,
                SOLANA:/^[1-9A-HJ-NP-Za-km-z]{32,44}$/,
            },

            init() {
                this.value = this.$refs.input.value;
                if (this.$refs.serverError) {
                    this.serverErr  = true;
                    this.noticeText = '';
                }
            },

            validateField(event) {
                this.errorText = '';
                if (event?.isTrusted && this.$refs.serverError) {
                    this.$refs.serverError.remove();
                    this.serverErr  = false;
                    this.noticeText = this.originalNotice;
                }

                switch (kind) {
                    case 'username':
                        if (this.value && !/^[A-Za-z]+$/.test(this.value))
                            this.errorText = i18n.field_validator_username_latin_only
                        break

                    case 'email':
                        if (this.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.value))
                            this.errorText = i18n.field_validator_email_invalid_format
                        break

                    case 'password': {
                        if (this.value && !/^(?=.*\d).{8,}$/.test(this.value)) {
                            this.errorText = i18n.field_validator_password_requirements;
                            break;
                        }

                        if (confirmWith) {
                            const other = this.$root.closest('form')?.querySelector(`[name="${confirmWith}"]`);
                            const otherVal = other ? other.value : '';
                            console.log(otherVal);
                            console.log(this.value);
                            if (this.value && otherVal && this.value !== otherVal) {
                                this.errorText = i18n.field_validator_passwords_mismatch;
                            } else {
                                this.errorText = '';
                            }

                            if (event?.isTrusted) {
                                other?.dispatchEvent(new Event('input', { bubbles: true }));
                            }
                        }
                        break;
                    }

                    case 'wallet': {
                        const net = (this.$refs.input.dataset.network || '').toUpperCase();
                        const re  = this.patterns[net] || /.*/;
                        if (this.value && !re.test(this.value.trim())) {
                            this.errorText = `${i18n.field_validator_wallet_invalid_format} ${net}`;
                        }
                        break;
                    }

                    case 'number': {
                        /* предыдущее валидное значение храним во внутреннем поле */
                        if (this.lastValid === undefined) this.lastValid = '';
                        /* 1. очищаем ввод одним replace */
                        const cleaned = this.value.replace(
                            /[^\d.,]|(?!^)[.,](?=.*[.,])|^[.,]|(?<=^0)[^.,]/g,
                            ''
                        );

                        /* 2. если результат пустой — возвращаем предыдущее валидное,
                              иначе принимаем новое значение */
                        if (this.value !== '') {
                            if (cleaned === '') {
                                this.value = this.lastValid;
                                this.$refs.input.value = this.lastValid;
                            } else {
                                if (cleaned !== this.value) {
                                    this.value = cleaned;
                                    this.$refs.input.value = cleaned;
                                }
                                this.lastValid = cleaned;
                            }
                        }

                        /* 3. проверяем кратность step ---------------------------------- */
                        const step = parseFloat(this.$refs.input.step || 0);
                        const num  = parseFloat(this.value.replace(',', '.'));

                        this.errorText = '';
                        if (step > 0 && Math.abs(num / step - Math.round(num / step)) > 1e-8) {
                            this.errorText = `${i18n.field_validator_number_step_multiple} ${step}`;
                        }
                        break;
                    }

                    case 'phone': {
                        /* всегда оставляем «+7 » в начале */
                        if (!this.value.startsWith('+7')) this.value = '+7 ' + this.value;

                        /* удаляем всё, кроме цифр, берём первые 10 */
                        const digits = this.value.replace(/\D/g, '').replace(/^7/, '').slice(0, 10);

                        /* одна (!) replace‑форматировка               *
                         * +7 900 123 45‑67                            */
                        const formatted = ('+7 ' + digits)
                            .replace(/^(\+7\s)(\d{0,3})(\d{0,3})(\d{0,2})(\d{0,2}).*$/, (
                                    _,
                                    p7, g1, g2, g3, g4
                                ) =>
                                    p7 +
                                    (g1 ? g1 : '') +
                                    (g2 ? ' ' + g2 : '') +
                                    (g3 ? ' ' + g3 : '') +
                                    (g4 ? '-' + g4 : '')
                            );

                        this.value = formatted;
                        this.$refs.input.value = formatted;

                        setTimeout(() => {
                            this.$refs.input.dispatchEvent(
                                new Event('input', { bubbles: true })
                            );
                        });
                        break;
                    }
                    case 'telegram': {
                        const re = /^@[A-Za-z][A-Za-z0-9_]{4,31}$/;
                        if (this.value && !re.test(this.value)) {
                            this.errorText = i18n.field_validator_telegram_format;
                        }
                        break;
                    }
                }

                this.$refs.input.setCustomValidity(this.errorText);
            },

            toggleVisibility() {
                const i = this.$refs.input
                i.type = i.type === 'password' ? 'text' : 'password'
                this.visible = !this.visible;
            }
        }))

        /*  дефолтные подсказки ---------------------------------------------- */
        function defaultNotice(kind) {
            switch (kind) {
                case 'username': return i18n.field_validator_username_notice
                case 'password': return i18n.field_validator_password_notice
                default:         return ''
            }
        }
    </script>
@endscript
