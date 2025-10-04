<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Models\Transaction;
use App\Models\Withdraw;
use App\MoonShine\Handlers\GoogleSheetsExportIndexDataHandler;
use App\MoonShine\Pages\Withdraw\WithdrawDetailPage;
use App\MoonShine\Pages\Withdraw\WithdrawFormPage;
use App\MoonShine\Pages\Withdraw\WithdrawIndexPage;
use App\Traits\Moonshine\CanStatusModifyTrait;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\View\ComponentAttributeBag;
use MoonShine\Fields\Range;
use MoonShine\Handlers\ExportHandler;
use MoonShine\Pages\Page;
use MoonShine\Resources\ModelResource;

/**
 * @extends ModelResource<Withdraw>
 */
class WithdrawResource extends ModelResource
{
    use CanStatusModifyTrait;

    protected string $model = Withdraw::class;

    protected string $title = 'Выводы';

    /**
     * @return list<Page>
     */
    public function pages(): array
    {
        return [
            WithdrawIndexPage::make($this->title()),
            WithdrawFormPage::make(
                $this->getItemID()
                    ? __('moonshine::ui.edit')
                    : __('moonshine::ui.add')
            ),
            WithdrawDetailPage::make(__('moonshine::ui.show')),
        ];
    }

    public function filters(): array
    {
        return [

            /* ───── ДАТА ЗАЯВКИ НА ВЫВОД ───── */
            Range::make('Дата заявки', 'created_at')           // два <input type="date">
                ->fromAttributes(['type' => 'date'])
                ->toAttributes(['type' => 'date'])
                ->onApply(function (Builder $q, array $v) {
                    if ($v['from'] === null && $v['to'] === null) {
                        return;
                    }

                    $q->where(function (Builder $d) use ($v) {
                        if ($v['from'] !== null) {
                            $d->whereDate('withdraws.created_at', '>=', $v['from']);
                        }

                        if ($v['to'] !== null) {
                            $d->whereDate('withdraws.created_at', '<=', $v['to']);
                        }
                    });
                }),

            /* ───── СУММА ЗАЯВКИ (transactions.amount) ───── */
            Range::make('Сумма', 'amount')
                ->fromAttributes(['type' => 'number', 'step' => '0.01'])
                ->toAttributes(['type' => 'number', 'step' => '0.01'])
                ->onApply(function (Builder $q, array $v) {
                    if ($v['from'] === null && $v['to'] === null) {
                        return;
                    }

                    $q->whereHas('transaction', function (Builder $t) use ($v) {
                        if ($v['from'] !== null) {
                            $t->where('amount', '>=', $v['from']);
                        }

                        if ($v['to'] !== null) {
                            $t->where('amount', '<=', $v['to']);
                        }
                    });
                }),

            /* ───── КОМИССИЯ (withdraws.commission) ───── */
            Range::make('Комиссия', 'commission')
                ->fromAttributes(['type' => 'number', 'step' => '0.01'])
                ->toAttributes(['type' => 'number', 'step' => '0.01'])
                ->onApply(function (Builder $q, array $v) {
                    if ($v['from'] === null && $v['to'] === null) {
                        return;
                    }

                    $q->where(function (Builder $d) use ($v) {
                        if ($v['from'] !== null) {
                            $d->where('withdraws.commission', '>=', $v['from']);
                        }

                        if ($v['to'] !== null) {
                            $d->where('withdraws.commission', '<=', $v['to']);
                        }
                    });
                }),

            /* ───── К ВЫВОДУ = amount − commission ───── */
            Range::make('К выводу', 'net')
                ->fromAttributes(['type' => 'number', 'step' => '0.01'])
                ->toAttributes(['type' => 'number', 'step' => '0.01'])
                ->onApply(function (Builder $q, array $v) {
                    if ($v['from'] === null && $v['to'] === null) {
                        return;
                    }

                    $q->whereExists(function ($sq) use ($v) {
                        $sq->select(DB::raw(1))
                            ->from('transactions')
                            ->whereColumn('transactions.uuid', 'withdraws.uuid')
                            ->when($v['from'] !== null, fn ($s) => $s->whereRaw('(transactions.amount - withdraws.commission) >= ?', [$v['from']])
                            )
                            ->when($v['to'] !== null, fn ($s) => $s->whereRaw('(transactions.amount - withdraws.commission) <= ?', [$v['to']])
                            );
                    });
                }),
        ];
    }

    protected function searchQuery(string $terms): void
    {
        $query = $this->getQuery();

        $exact = false;
        $value = "%{$terms}%";

        if (preg_match('/^=(.+)$/u', $terms, $m)) {
            $exact = true;
            $value = trim($m[1]);
        }

        $plain = $exact ? $value : $terms;
        $token = mb_strtolower($plain);

        /* тематические подсказки -------------------------------------------- */
        $contains = static fn (string $w): bool => mb_stripos($w, $token) !== false;

        $isCrypto = $contains('крипто') || $contains('crypto') || $contains('usdt');
        $isFiat = $contains('фиат') || $contains('fiat');

        $isModer = $contains('на модерации') || $contains('модерац') || $contains('moder');
        $isReject = $contains('отклонено') || $contains('reject');
        $isAccept = $contains('исполнено') || $contains('accepted');

        /* точная дата / число ------------------------------------------------ */
        $exactDate = null;

        if ($exact) {
            try {
                $exactDate = \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
            } catch (\Throwable) { /* не дата */
            }
        }
        $exactNum = $exact && is_numeric($value) ? $value : null;

        /* построение запроса ------------------------------------------------- */
        $query->where(function ($q) use (
            $exact, $value, $token,
            $exactDate, $exactNum,
            $isCrypto, $isFiat,
            $isModer, $isReject, $isAccept
        ) {
            /* created_at (дата заявки) ------------------------------------ */
            if ($exact && $exactDate) {
                $q->orWhere('withdraws.created_at', '=', $exactDate);
            } elseif (! $exact) {
                $q->orWhere('withdraws.created_at', 'ilike', "%{$token}%");
            }

            /* wallet_address ---------------------------------------------- */
            $q->orWhere(
                'withdraws.wallet_address',
                $exact ? '=' : 'ilike',
                $exact ? $value : "%{$token}%"
            );

            /* комиссия ----------------------------------------------------- */
            if ($exactNum !== null) {
                $q->orWhere('withdraws.commission', '=', $exactNum);
            } elseif (! $exact) {
                $q->orWhereRaw('withdraws.commission::text ilike ?', ["%{$token}%"]);
            }

            /* сумма (transaction.amount) ---------------------------------- */
            if ($exactNum !== null) {
                $q->orWhereHas('transaction', fn ($t) => $t->where('amount', '=', $exactNum)
                );
            } elseif (! $exact) {
                $q->orWhereHas('transaction', fn ($t) => $t->whereRaw('amount::text ilike ?', ["%{$token}%"])
                );
            }

            /* К ВЫВОДУ  = amount − commission ----------------------------- */
            if ($exactNum !== null) {
                $q->orWhereExists(function ($sq) use ($exactNum) {
                    $sq->select(DB::raw(1))
                        ->from('transactions')
                        ->whereColumn('transactions.uuid', 'withdraws.uuid')
                        ->whereRaw('ROUND((transactions.amount - withdraws.commission)::numeric, 2) = ?', [$exactNum]);
                });
            } elseif (! $exact) {
                $q->orWhereExists(function ($sq) use ($token) {
                    $sq->select(DB::raw(1))
                        ->from('transactions')
                        ->whereColumn('transactions.uuid', 'withdraws.uuid')
                        ->whereRaw('(transactions.amount - withdraws.commission)::text ilike ?', ["%{$token}%"]);
                });
            }

            /* username ----------------------------------------------------- */
            $q->orWhereHas('transaction.user', fn ($u) => $u->where('username', $exact ? '=' : 'ilike', $exact ? $value : "%{$token}%")
            );

            /* крипто / фиат ------------------------------------------------ */
            $cryptoRegex =
                '^(0x[0-9A-Fa-f]{40}'           // ETH
                . '|T[a-zA-Z0-9]{33}'             // TRC‑20 USDT
                . '|[13][a-km-zA-HJ-NP-Z1-9]{25,34}'      // BTC Legacy/P2SH
                . '|bc1[qpzry9x8gf2tvdw0s3jn54khce6mua7l]{39})$'; // BTC Bech32

            if ($isCrypto) {
                $q->orWhere('withdraws.wallet_address', '~', $cryptoRegex);
            }

            if ($isFiat) {
                $q->orWhere('withdraws.wallet_address', '!~', $cryptoRegex);
            }

            /* статусы ------------------------------------------------------ */
            if ($isModer) {
                $q->orWhereHas('transaction', fn ($t) => $t->whereNull('accepted_at')->whereNull('rejected_at')
                );
            }

            if ($isReject) {
                $q->orWhereHas('transaction', fn ($t) => $t->whereNotNull('rejected_at')
                );
            }

            if ($isAccept) {
                $q->orWhereHas('transaction', fn ($t) => $t->whereNotNull('accepted_at')
                );
            }
        });
    }

    public function getActiveActions(): array
    {
        return ['view'];
    }

    /**
     * @param Withdraw $item
     * @return array<string, string[]|string>
     *
     * @see https://laravel.com/docs/validation#available-validation-rules
     */
    public function rules(Model $item): array
    {
        return [];
    }

    public function tdAttributes(): Closure
    {
        return function (Withdraw $item, int $row, int $cell, ComponentAttributeBag $attr) {
            // если это колонка с wallet_address (индекс 5)
            if ($cell === 5) {
                // сохраняем существующие классы и добавляем pointer

                $copyValue = $item->payment_source_id === 2 && $item->fiatDetail
                    ? collect([
                        $item->fiatDetail->sbp_phone,
                        $item->fiatDetail->bank_name,
                        $item->fiatDetail->recipient_name,
                    ])->filter()->implode(' · ')
                    : $item->wallet_address;

                $existing = $attr->get('class', '');
                $attr->setAttributes([
                    // чтобы можно было стилизовать псевдо-элемент
                    'class' => trim($existing . ' has-copy-tooltip cursor-pointer'),
                    'data-copy' => $copyValue,
                    'data-tooltip' => 'Кликните, чтобы скопировать',
                    'style' => 'max-width:250px;overflow:auto;text-overflow:ellipsis;white-space:nowrap;',
                ]);
            }

            if ($cell === 7) {
                $existing = $attr->get('class', '');
                $attr->setAttributes([
                    'class' => $existing . ' flex items-center gap-2',
                ]);
            }

            return $attr;
        };
    }

    public function export(): ?ExportHandler
    {
        return GoogleSheetsExportIndexDataHandler::make('Экспортировать')
            ->spreadsheetId(config('services.export_file.withdraw'))
            ->disk('public')
            ->filename('users-' . now()->format('Ymd-His'))
            ->withConfirm();
    }
}
