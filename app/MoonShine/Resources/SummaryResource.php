<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Models\PartnerRank;
use App\Models\PartnerRankRequirement;
use App\Models\Summary;
use App\MoonShine\Handlers\SummarySheetsExportHandler;
use App\MoonShine\Pages\ItcPackage\ItcPackageDepositProfitPage;
use App\MoonShine\Pages\Summary\SummaryDetailPage;
use App\MoonShine\Pages\Summary\SummaryFormPage;
use App\MoonShine\Pages\Summary\SummaryIndexPage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;
use MoonShine\ImportExport\Contracts\HasImportExportContract;
use MoonShine\ImportExport\ExportHandler;
use MoonShine\ImportExport\Traits\ImportExportConcern;
use MoonShine\Laravel\Http\Responses\MoonShineJsonResponse;
use MoonShine\Laravel\MoonShineRequest;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\Enums\ToastType;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\ActionButton;

/**
 * @extends ModelResource<Summary, SummaryIndexPage, SummaryFormPage, SummaryDetailPage>
 */
final class SummaryResource extends ModelResource implements HasImportExportContract
{
    use ImportExportConcern;

    protected string $model = Summary::class;

    protected string $title = 'Сводка';

    /**
     * @return \MoonShine\Support\ListOf<\MoonShine\Contracts\UI\ActionButtonContract>
     */
    protected function topButtons(): ListOf
    {
        return parent::topButtons()
            ->add(
                ActionButton::make('Сменить адрес кошелька')
                    ->icon('wallet')
                    ->toggleModal('edit-wallet-modal')
                    ->showInDropdown()
            )
            ->add(
                ActionButton::make('Проценты регулярной премии')
                    ->icon('adjustments-horizontal')
                    ->toggleModal('edit-global-percents-modal')
                    ->showInDropdown()
            )
            ->add(
                ActionButton::make('Требования к рангам')
                    ->icon('trophy')
                    ->toggleModal('edit-rank-requirements-modal')
                    ->showInDropdown()
            )
            ->add(
                ActionButton::make(
                    'Начислить прибыль',
                    url: fn () => moonshineRouter()->getEndpoints()->toPage(
                        page: ItcPackageDepositProfitPage::class
                    )
                )
                    ->icon('banknotes')
                    ->showInDropdown()
            );
    }

    /**
     * @return array{0: class-string<SummaryIndexPage>, 1: class-string<SummaryFormPage>, 2: class-string<SummaryDetailPage>}
     */
    protected function pages(): array
    {
        return [
            SummaryIndexPage::class,
            SummaryFormPage::class,
            SummaryDetailPage::class,
        ];
    }

    /**
     * @return list<\MoonShine\Laravel\Enums\Action>
     */
    public function getActiveActions(): array
    {
        return [];
    }

    /**
     * @return \MoonShine\ImportExport\ExportHandler
     */
    public function export(): ExportHandler
    {
        return SummarySheetsExportHandler::make('Экспортировать')
            ->spreadsheetId(config('services.export_file.summary'))
            ->disk('public')
            ->dir('exports')
            ->filename('summary-' . now()->format('Ymd-His'))
            ->withConfirm();
    }

    /**
     * @throws ValidationException
     */
    public function updateWallet(MoonShineRequest $payload): MoonShineJsonResponse
    {
        $network = $payload->input('network');

        /* адресные шаблоны по сети */
        $patterns = [
            // EVM‑совместимые сети
            'ERC20' => '/^0x[a-fA-F0-9]{40}$/',
            'BEP20' => '/^0x[a-fA-F0-9]{40}$/',
            'POLYGON' => '/^0x[a-fA-F0-9]{40}$/',
            'ARBITRUM' => '/^0x[a-fA-F0-9]{40}$/',
            'OPTIMISM' => '/^0x[a-fA-F0-9]{40}$/',
            'AVALANCHE' => '/^0x[a-fA-F0-9]{40}$/',
            'FANTOM' => '/^0x[a-fA-F0-9]{40}$/',
            'BASE' => '/^0x[a-fA-F0-9]{40}$/',

            // Tron
            'TRC20' => '/^T[1-9A-HJ-NP-Za-km-z]{33}$/',

            // Solana Base58 (32–44 симв.)
            'SOLANA' => '/^[1-9A-HJ-NP-Za-km-z]{32,44}$/',
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
        $editor->setKey('WALLET_NETWORK', $payload->input('network'));
        $editor->save();

        Artisan::call('config:clear');
        config([
            'wallet.deposit_address' => $payload->input('address'),
            'wallet.network' => $payload->input('network'),
        ]);

        $url = moonshineRouter()->getEndpoints()->toPage(
            page: SummaryIndexPage::class
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
                    if (isset($row['personal_deposit']) && $row['personal_deposit'] !== '') {
                        $newReqs[] = [
                            'partner_rank_id' => $row['partner_rank_id'],
                            'line' => null,
                            'personal_deposit' => $row['personal_deposit'],
                            'required_turnover' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    // Линии
                    foreach (range(1, 5) as $line) {
                        $turnover = $row["line_$line"] ?? null;

                        if ($turnover !== null && $turnover !== '') {
                            $newReqs[] = [
                                'partner_rank_id' => $row['partner_rank_id'],
                                'line' => $line,
                                'personal_deposit' => null,
                                'required_turnover' => $turnover,
                                'created_at' => now(),
                                'updated_at' => now(),
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
                ->toast('Ошибка: ' . $e->getMessage(), ToastType::ERROR);
        }
    }
}
