<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Models\Transaction;
use App\MoonShine\Pages\Transaction\TransactionDetailPage;
use App\MoonShine\Pages\Transaction\TransactionFormPage;
use App\MoonShine\Pages\Transaction\TransactionIndexPage;
use MoonShine\Laravel\Pages\Page;
use MoonShine\Laravel\Resources\ModelResource;

/**
 * @extends ModelResource<Transaction>
 */
class TransactionResource extends ModelResource
{
    protected string $model = Transaction::class;

    protected string $title = 'Транзакции';

    /**
     * @return list<Page>
     */
    protected function pages(): array
    {
        return [
            TransactionIndexPage::class,
            TransactionFormPage::class,
            TransactionDetailPage::class,
        ];
    }

    /**
     * @param Transaction $item
     * @return array<string, string[]|string>
     *
     * @see https://laravel.com/docs/validation#available-validation-rules
     */
    public function rules(mixed $item): array
    {
        return [];
    }
}
