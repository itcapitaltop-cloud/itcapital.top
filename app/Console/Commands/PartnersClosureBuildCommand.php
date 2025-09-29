<?php

namespace App\Console\Commands;

use App\Models\Partner;
use App\Models\PartnerClosure;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PartnersClosureBuildCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'partners:closure-build';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Собрать таблицу partner_closures из partners';

    public function handle(): int
    {
        $this->info('Строим partner_closures…');

        DB::transaction(function () {
            PartnerClosure::truncate();

            // 1) Все пользователи БЕЗ глобального скоупа notBanned
            $users = User::withoutGlobalScope('notBanned')
                ->get(['id', 'banned_at']);

            // карта банов: id -> banned_at|null
            $banned = $users->pluck('banned_at', 'id');

            // 2) Нулевой уровень для всех (включая забаненных, иначе порвётся восходящая цепочка)
            $bulk0 = $users->map(fn ($u) => [
                'ancestor_id'   => $u->id,
                'descendant_id' => $u->id,
                'depth'         => 0,
            ])->all();
            if (!empty($bulk0)) {
                PartnerClosure::insert($bulk0);
            }

            // 3) Рёбра: partner_id (родитель) -> [user_id...]
            $edges = Partner::query()
                ->get(['partner_id', 'user_id'])
                ->groupBy('partner_id')
                ->map(fn ($rows) => $rows->pluck('user_id')->all());

            // 4) Корни леса: все id без входящих рёбер
            $childrenIds = Partner::query()->pluck('user_id')->all();
            $roots = $users->pluck('id')->diff($childrenIds)->values()->all();

            // 5) BFS по дереву(ьях)
            $queue = collect($roots)->map(fn ($id) => [$id, 0]);

            while ($queue->isNotEmpty()) {
                [$parent, $parentDepth] = $queue->shift();

                foreach ($edges[$parent] ?? [] as $child) {
                    // Берём уже записанных предков parent
                    $ancestors = PartnerClosure::where('descendant_id', $parent)->get(['ancestor_id','depth']);

                    // Вставляем пары ancestor -> child ТОЛЬКО если потомок НЕ забанен
                    if (is_null($banned[$child] ?? null)) {
                        $bulk = [];
                        foreach ($ancestors as $p) {
                            $bulk[] = [
                                'ancestor_id'   => $p->ancestor_id,
                                'descendant_id' => $child,
                                'depth'         => $p->depth + 1,
                            ];
                        }
                        if (!empty($bulk)) {
                            PartnerClosure::insert($bulk);
                        }
                    }

                    // В любом случае продолжаем обход — через забаненных нужно "пройти" к нижним веткам
                    $queue->push([$child, $parentDepth + 1]);
                }
            }
        });

        return Command::SUCCESS;
    }
}
