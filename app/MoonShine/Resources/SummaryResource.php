<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Models\PartnerLevelPercent;
use App\Models\PartnerRank;
use App\Models\PartnerRankRequirement;
use App\MoonShine\Handlers\SummarySheetsExportHandler;
use App\MoonShine\Pages\ItcPackage\ItcPackageDepositProfitPage;
use App\MoonShine\Pages\User\UserDetailPage;
use Illuminate\Database\Eloquent\Model;
use App\Models\Summary;
use App\MoonShine\Pages\Summary\SummaryIndexPage;
use App\MoonShine\Pages\Summary\SummaryFormPage;
use App\MoonShine\Pages\Summary\SummaryDetailPage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use MoonShine\ActionButtons\ActionButton;
use MoonShine\Handlers\ExportHandler;
use MoonShine\Http\Responses\MoonShineJsonResponse;
use MoonShine\MoonShineRequest;
use MoonShine\Resources\ModelResource;
use MoonShine\Pages\Page;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;
use Illuminate\Support\Facades\Artisan;
use Throwable;

/**
 * @extends ModelResource<Summary>
 */
class SummaryResource extends ModelResource
{
    protected string $model = Summary::class;

    protected string $title = 'Сводка';

    /**
     * @return list<Page>
     * @throws \Throwable
     */


    /**
     * @throws \Throwable
     */
    public function actions(): array
    {
        return [
            ActionButton::make('Сменить адрес кошелька')
                ->icon('heroicons.wallet')
                ->toggleModal('edit-wallet-modal')
                ->showInDropdown(),
            ActionButton::make('Проценты регулярной премии')
                ->icon('heroicons.adjustments-horizontal')
                ->toggleModal('edit-global-percents-modal')
                ->showInDropdown(),
            ActionButton::make('Требования к рангам')
            ->icon('heroicons.trophy')
                ->toggleModal('edit-rank-requirements-modal')
                ->showInDropdown(),
            ActionButton::make('Начислить прибыль', to_page(new ItcPackageDepositProfitPage))
                ->icon('heroicons.banknotes')
                ->showInDropdown(),
        ];
//        return [];
    }

    public function pages(): array
    {
        return [
            SummaryIndexPage::make($this->title()),
            SummaryFormPage::make(
                $this->getItemID()
                    ? __('moonshine::ui.edit')
                    : __('moonshine::ui.add')
            ),
            SummaryDetailPage::make(__('moonshine::ui.show')),
        ];
    }

    /**
     * @param Summary $item
     *
     * @return array<string, string[]|string>
     * @see https://laravel.com/docs/validation#available-validation-rules
     */
    public function rules(Model $item): array
    {
        return [];
    }

    public function getActiveActions(): array
    {
        return [];
    }

    public function export(): ?ExportHandler
    {
        return SummarySheetsExportHandler::make('Экспортировать')
            ->spreadsheetId(env('SUMMARY_EXPORT_FILE_KEY'))
            ->disk('public')
            ->filename('summary-'.now()->format('Ymd-His'))
            ->withConfirm();
    }

    /**
     * @throws ValidationException
     */
    public function updateWallet($payload): MoonShineJsonResponse
    {
        $network = $payload->input('network');

        /* адресные шаблоны по сети */
        $patterns = [
            // EVM‑совместимые сети
            'ERC20'     => '/^0x[a-fA-F0-9]{40}$/',
            'BEP20'     => '/^0x[a-fA-F0-9]{40}$/',
            'POLYGON'   => '/^0x[a-fA-F0-9]{40}$/',
            'ARBITRUM'  => '/^0x[a-fA-F0-9]{40}$/',
            'OPTIMISM'  => '/^0x[a-fA-F0-9]{40}$/',
            'AVALANCHE' => '/^0x[a-fA-F0-9]{40}$/',
            'FANTOM'    => '/^0x[a-fA-F0-9]{40}$/',
            'BASE'      => '/^0x[a-fA-F0-9]{40}$/',

            // Tron
            'TRC20'     => '/^T[1-9A-HJ-NP-Za-km-z]{33}$/',

            // Solana Base58 (32–44 симв.)
            'SOLANA'    => '/^[1-9A-HJ-NP-Za-km-z]{32,44}$/',
        ];

        $rules = [
            'network' => ['required', Rule::in(array_keys($patterns))],
            'address' => [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($network, $patterns) {
                    if (! preg_match($patterns[$network] ?? '/.*/', $value)) {
                        $fail('Адрес не соответствует формату сети ' . $network);
                    }
                },
            ],
        ];

        validator($payload->toArray(), $rules)->validate();

        $editor = DotenvEditor::load();

        $editor->setKey('WALLET_DEPOSIT_ADDRESS', $payload->input('address'));
        $editor->setKey('WALLET_NETWORK',         $payload->input('network'));
        $editor->save();

        Artisan::call('config:clear');
        config([
            'wallet.deposit_address' => $payload->input('address'),
            'wallet.network'         => $payload->input('network'),
        ]);

        $url = to_page(
            page:     new SummaryIndexPage(),
            resource: new SummaryResource(),
        );

        return MoonShineJsonResponse::make()
            ->toast('Кошелек обновлен.')
            ->redirect($url);
    }

    public function saveRankRequirements(MoonShineRequest $request): MoonShineJsonResponse
    {
        try {
            $data = $request->all();

            $rows = $data['requirements'] ?? [];

            // Полностью очищаем текущие требования и бонусы
            DB::transaction(function () use ($rows) {
                PartnerRankRequirement::truncate();

                // Для бонусов — обновлять существующие, не трогать id (иначе потеряются связи)
                foreach ($rows as $row) {
                    // Обновить бонус
                    if (isset($row['bonus_usd'])) {
                        PartnerRank::where('id', $row['partner_rank_id'])
                            ->update(['bonus_usd' => $row['bonus_usd']]);
                    }
                }

                $newReqs = [];
                foreach ($rows as $row) {
                    // Персональный депозит (только одна строка на ранг)
                    if (isset($row['personal_deposit']) && $row['personal_deposit'] !== null && $row['personal_deposit'] !== '') {
                        $newReqs[] = [
                            'partner_rank_id'   => $row['partner_rank_id'],
                            'line'              => null,
                            'personal_deposit'  => $row['personal_deposit'],
                            'required_turnover' => null,
                            'created_at'        => now(),
                            'updated_at'        => now(),
                        ];
                    }
                    // Линии
                    foreach (range(1, 5) as $line) {
                        $turnover = $row["line_$line"] ?? null;
                        if ($turnover !== null && $turnover !== '') {
                            $newReqs[] = [
                                'partner_rank_id'   => $row['partner_rank_id'],
                                'line'              => $line,
                                'personal_deposit'  => null,
                                'required_turnover' => $turnover,
                                'created_at'        => now(),
                                'updated_at'        => now(),
                            ];
                        }
                    }
                }
                if ($newReqs) {
                    PartnerRankRequirement::insert($newReqs);
                }
                Artisan::call('users:recalc-rank --no-bonus');
            });

            return MoonShineJsonResponse::make()
                ->toast('Сохранено')
                ->redirect(request()->headers->get('referer') ?? '/');
        } catch (\Throwable $e) {
            return MoonShineJsonResponse::make()
                ->toast('Ошибка: ' . $e->getMessage(), 'error');
        }
    }
}
