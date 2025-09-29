<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use Illuminate\Database\Eloquent\Model;
use App\Models\Transaction;
use App\MoonShine\Pages\Transaction\TransactionIndexPage;
use App\MoonShine\Pages\Transaction\TransactionFormPage;
use App\MoonShine\Pages\Transaction\TransactionDetailPage;

use MoonShine\Resources\ModelResource;
use MoonShine\Pages\Page;

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
    public function pages(): array
    {
        return [
            TransactionIndexPage::make($this->title()),
            TransactionFormPage::make(
                $this->getItemID()
                    ? __('moonshine::ui.edit')
                    : __('moonshine::ui.add')
            ),
            TransactionDetailPage::make(__('moonshine::ui.show')),
        ];
    }

    /**
     * @param Transaction $item
     *
     * @return array<string, string[]|string>
     * @see https://laravel.com/docs/validation#available-validation-rules
     */
    public function rules(Model $item): array
    {
        return [];
    }
}
