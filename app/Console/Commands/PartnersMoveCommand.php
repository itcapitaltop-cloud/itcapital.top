<?php

namespace App\Console\Commands;

use App\Models\Partner;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PartnersMoveCommand extends Command
{
    protected $signature = 'partners:move
        {from : ID пользователя, у которого забираем первую линию}
        {to   : ID пользователя, к которому переносим}
        {--line=1 : номер линии, на которую будут записаны перенесённые партнёры}
        {--dry    : только показать, что будет сделано, без изменений}';

    protected $description = 'Перенести партнёров первой линии одного пользователя к другому пользователю на заданную линию';

    public function handle(): int
    {
        /* ---------- валидация входных данных -------------------------- */
        $fromId = intval($this->argument('from'));
        $toId   = intval($this->argument('to'));
        $line   = max(1, intval($this->option('line')));
        $dryRun = $this->option('dry');

        if ($fromId === $toId) {
            $this->error('from и to не могут совпадать.');
            return self::FAILURE;
        }
        if (!User::find($fromId) || !User::find($toId)) {
            $this->error('from или to‑пользователь не найден.');
            return self::FAILURE;
        }

        /* ---------- выбираем партнёров первой линии from -------------- */
        $partners = Partner::where('partner_id', $fromId)->pluck('user_id');

        if ($partners->isEmpty()) {
            $this->warn("У пользователя {$fromId} нет партнёров первой линии.");
            return self::SUCCESS;
        }

        $this->info("Найдено {$partners->count()} партнёров первой линии пользователя {$fromId}.");

        /* ---------- dry‑run вывод ------------------------------------- */
        if ($dryRun) {
            $this->line("Режим --dry: никакие данные не изменены.");
            $this->table(
                ['user_id', 'будет partner_id', 'будет line'],
                $partners->map(fn ($uid) => [$uid, $toId, $line])->all()
            );
            return self::SUCCESS;
        }

        /* ---------- обновляем partners -------------------------------- */
        DB::transaction(function () use ($partners, $toId, $line) {

            Partner::whereIn('user_id', $partners)
                ->update([
                    'partner_id' => $toId,
                    'line'       => $line,
                ]);
        });

        $this->info('Перенос завершён. Теперь запустите “php artisan partners:closure-build” для перестройки дерева.');

        return self::SUCCESS;
    }
}
