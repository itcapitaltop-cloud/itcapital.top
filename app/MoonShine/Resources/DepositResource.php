<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Enums\Transactions\TransactionStatusEnum;
use App\MoonShine\Handlers\GoogleSheetsExportIndexDataHandler;
use App\Traits\Moonshine\CanStatusModifyTrait;
use Closure;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Deposit;
use App\Models\Transaction;
use App\MoonShine\Pages\Deposit\DepositIndexPage;
use App\MoonShine\Pages\Deposit\DepositFormPage;
use App\MoonShine\Pages\Deposit\DepositDetailPage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\View\ComponentAttributeBag;
use MoonShine\ActionButtons\ActionButton;
use MoonShine\Fields\Date;
use MoonShine\Fields\Range;
use MoonShine\Fields\Select;
use MoonShine\Fields\Text;
use MoonShine\Handlers\ExportHandler;
use MoonShine\Http\Responses\MoonShineJsonResponse;
use MoonShine\Resources\ModelResource;
use MoonShine\Pages\Page;

/**
 * @extends ModelResource<Deposit>
 */
class DepositResource extends ModelResource
{
    use CanStatusModifyTrait;

    protected string $model = Deposit::class;

    protected string $title = 'Ввод';

    public function filters(): array
    {
        return [
            Date::make('Дата первого депозита', 'first_date')
                ->onApply(function(Builder $q, $value) {
                    if (! $value) {
                        return;
                    }

                    $q->whereHas('transaction', function(Builder $tq) use ($value) {
                        $tq->whereRaw("
                            DATE((
                                SELECT MIN(d2.created_at)
                                  FROM deposits AS d2
                                  JOIN transactions AS t2
                                    ON t2.uuid = d2.uuid
                                 WHERE t2.user_id = transactions.user_id
                            )) = ?
                        ", [$value]);
                                });
                            }),

            Date::make('Дата последнего депозита', 'last_date')
                ->onApply(function(Builder $q, $value) {
                    if (! $value) {
                        return;
                    }

                    $q->whereHas('transaction', function(Builder $tq) use ($value) {
                        $tq->whereRaw("
                            DATE((
                                SELECT MAX(d2.created_at)
                                  FROM deposits AS d2
                                  JOIN transactions AS t2
                                    ON t2.uuid = d2.uuid
                                 WHERE t2.user_id = transactions.user_id
                            )) = ?
                        ", [$value]);
                                });
                            }),

            Range::make('Сумма депозита', 'amount')
                ->fromAttributes(['type' => 'number', 'step' => '0.01'])   // оба поля — <input type="number">
                ->toAttributes(['type' => 'number', 'step' => '0.01'])
                ->onApply(function (Builder $q, array $value) {
                    if (is_null($value['from']) && is_null($value['to'])) {
                        return;
                    }

                    $q->whereHas('transaction', function (Builder $tq) use ($value) {
                        if (! is_null($value['from'])) {
                            $tq->where('amount', '>=', $value['from']);
                        }
                        if (! is_null($value['to'])) {
                            $tq->where('amount', '<=', $value['to']);
                        }
                    });
                }),

            Range::make('Дата депозита', 'created_at')
                ->fromAttributes(['type' => 'date'])
                ->toAttributes(['type' => 'date'])
                ->onApply(function (Builder $q, array $value) {
                    if (is_null($value['from']) && is_null($value['to'])) {
                        return;
                    }

                    $q->where(function (Builder $dq) use ($value) {
                        if (! is_null($value['from'])) {
                            $dq->whereDate('created_at', '>=', $value['from']);
                        }
                        if (! is_null($value['to'])) {
                            $dq->whereDate('created_at', '<=', $value['to']);
                        }
                    });
                }),

            Text::make('Никнейм пользователя', 'username')
                ->onApply(function(Builder $q, $value) {
                    if (! $value) {
                        return;
                    }

                    $q->whereHas('transaction.user', function(Builder $q2) use ($value) {
                        $q2->where('username', 'like', "%{$value}%");
                    });
                }),

            Select::make('Статус заявки', 'status')
                ->nullable()
                ->options([
                    TransactionStatusEnum::MODERATE->value  => TransactionStatusEnum::MODERATE->getName(),
                    TransactionStatusEnum::ACCEPTED->value  => TransactionStatusEnum::ACCEPTED->getName(),
                    TransactionStatusEnum::REJECTED->value  => TransactionStatusEnum::REJECTED->getName(),
                ])
                ->onApply(function(Builder $q, $value) {
                    if (! $value) {
                        return;
                    }

                    $q->whereHas('transaction', function(Builder $q2) use ($value) {
                        match ($value) {
                            TransactionStatusEnum::MODERATE->value  => $q2->whereNull('accepted_at')->whereNull('rejected_at'),
                            TransactionStatusEnum::ACCEPTED->value  => $q2->whereNotNull('accepted_at')->whereNull('rejected_at'),
                            TransactionStatusEnum::REJECTED->value  => $q2->whereNotNull('rejected_at'),
                        };
                    });
                }),
        ];
    }

    /**
     * @return list<Page>
     */
    public function pages(): array
    {
        return [
            DepositIndexPage::make($this->title()),
            DepositFormPage::make(
                $this->getItemID()
                    ? __('moonshine::ui.edit')
                    : __('moonshine::ui.add')
            ),
            DepositDetailPage::make(__('moonshine::ui.show')),
        ];
    }



    /**
     * @param Deposit $item
     *
     * @return array<string, string[]|string>
     * @see https://laravel.com/docs/validation#available-validation-rules
     */
    public function rules(Model $item): array
    {
        return [];
    }

    public function query(): Builder
    {
        return parent::query()
            ->with([
                'transaction',
                'transaction.user' => fn($q) => $q->withoutGlobalScope('notBanned')
            ]);
    }

    protected function searchQuery(string $terms): void
    {
        $query = $this->getQuery();

        /* базовые параметры -------------------------------------------------- */
        $exact   = false;                  // «=значение»?
        $value   = "%{$terms}%";           // для LIKE

        if (preg_match('/^=(.+)$/u', $terms, $m)) {
            $exact = true;
            $value = trim($m[1]);          // без «=»
        }

        $plain = $exact ? $value : $terms; // строка без «=»
        $token = mb_strtolower($plain);    // в нижний регистр

        /* определяем доп. смысл --------------------------------------------- */
        $contains = static function (string $word) use ($token): bool {
            return mb_stripos($word, $token) !== false;   // token встречается в word
        };

        $isCrypto = $contains('крипто')     || $contains('crypto')   || $contains('usdt');
        $isFiat   = $contains('фиат')       || $contains('fiat');

        $isModer  = $contains('на модерации') || $contains('модерац') || $contains('moder');
        $isReject = $contains('отклонено')    || $contains('reject');
        $isAccept = $contains('исполнено')    || $contains('accepted');

        /* точная дата / число ------------------------------------------------- */
        $exactDate = null;
        if ($exact) {
            try {
                $exactDate = \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {
                $exactDate = null;
            }
        }
        $exactNum = $exact && is_numeric($value) ? $value : null;

        /* построение запроса -------------------------------------------------- */
        $query->where(function ($q) use (
            $exact, $value, $token,
            $exactDate, $exactNum,
            $isCrypto, $isFiat,
            $isModer, $isReject, $isAccept
        ) {
            /* created_at ----------------------------------------------------- */
            if ($exact && $exactDate) {
                $q->orWhere('created_at', '=', $exactDate);
            } elseif (!$exact) {
                $q->orWhere('created_at', 'ilike', "%{$token}%");
            }

            /* transaction_hash ---------------------------------------------- */
            $q->orWhere(
                'transaction_hash',
                $exact ? '=' : 'ilike',
                $exact ? $value : "%{$token}%"
            );

            /* amount --------------------------------------------------------- */
            if ($exactNum !== null) {
                $q->orWhereHas('transaction', fn ($t) =>
                $t->where('amount', '=', $exactNum)
                );
            } elseif (!$exact) {
                $q->orWhereHas('transaction', fn ($t) =>
                $t->where('amount', 'ilike', "%{$token}%")
                );
            }

            /* username ------------------------------------------------------- */
            $q->orWhereHas('transaction.user', fn ($u) =>
            $u->where('username', $exact ? '=' : 'ilike', $exact ? $value : "%{$token}%")
            );

            /* крипто / фиат -------------------------------------------------- */
            if ($isCrypto) {
                $q->orWhere('transaction_hash', '~',  '^[A-Fa-f0-9]{64}$');   // крипто
            }
            if ($isFiat) {
                $q->orWhere('transaction_hash', '!~', '^[A-Fa-f0-9]{64}$');   // фиат
            }

            /* статусы -------------------------------------------------------- */
            if ($isModer) {
                $q->orWhereHas('transaction', fn ($t) =>
                $t->whereNull('accepted_at')->whereNull('rejected_at')
                );
            }
            if ($isReject) {
                $q->orWhereHas('transaction', fn ($t) =>
                $t->whereNotNull('rejected_at')
                );
            }
            if ($isAccept) {
                $q->orWhereHas('transaction', fn ($t) =>
                $t->whereNotNull('accepted_at')
                );
            }
        });
    }

    public function search(): array
    {
        return [
            'created_at',
            'transaction_hash',
        ];
    }

    public function getActiveActions(): array
    {
        return ['view'];
    }

    public function export(): ?ExportHandler
    {
        return GoogleSheetsExportIndexDataHandler::make('Экспортировать')
            ->spreadsheetId(config('services.export_file.deposit'))
            ->disk('public')
            ->filename('deposits-'.now()->format('Ymd-His'))
            ->withConfirm();
    }

    public function tdAttributes(): Closure
    {
        return function(Deposit $item, int $row, int $cell, ComponentAttributeBag $attr) {
            if ($cell === 3) {
                $existing = $attr->get('class', '');
                $hash = $item->transaction_hash ?? '';

                // Проверяем, что это хеш USDT (TRC-20): 64 символа hex
                if (preg_match('/^[A-Fa-f0-9]{64}$/', $hash)) {
                    $attr->setAttributes([
                        'class'        => trim($existing . ' has-copy-tooltip cursor-pointer'),
                        'data-copy'    => $hash,
                        'data-tooltip' => 'Кликните, чтобы скопировать',
                        'style'        => 'max-width:340px;overflow:auto;text-overflow:ellipsis;white-space:nowrap;',
                    ]);
                } else {
                    $attr->setAttributes([
                        'class' => $existing,
                        'style' => 'max-width:340px;overflow:auto;text-overflow:ellipsis;white-space:nowrap;',
                    ]);
                }
            }
            if ($cell === 6) {
                $existing = $attr->get('class', '');
                $attr->setAttributes([
                    'class' => $existing . ' flex items-center gap-2',
                ]);
            }
            return $attr;
        };
    }

}
