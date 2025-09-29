<?php

namespace App\Console\Commands;

use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use App\Models\ItcPackage;
use App\Models\PartnerClosure;
use App\Models\PartnerRank;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UsersMaxRankCommand extends Command
{
    protected $signature   = 'users:max-rank {--top=1 : вывести N лидеров}';
    protected $description = 'Показать пользователя(‑ей) с максимальным достигнутым рангом';

    /** @var Carbon */
    private Carbon $now;

    public function handle(): int
    {
        $this->now = now();

        $ranks = PartnerRank::with('requirements')
            ->orderByDesc('rank')
            ->get();

        $userBestRank = [];

        User::chunk(500, function ($users) use (&$userBestRank, $ranks) {

            foreach ($users as $user) {

                $personal = $this->personalDeposit($user->id);

                $lineTurnover = [];

                $getTurnover = function (int $line) use ($user, &$lineTurnover) {

                    if (isset($lineTurnover[$line])) {
                        return $lineTurnover[$line];
                    }

                    $ids = PartnerClosure::where('ancestor_id', $user->id)
                        ->where('depth', $line)
                        ->pluck('descendant_id');

                    $buy = DB::table('transactions')
                        ->whereIn('user_id', $ids)
                        ->where('trx_type', TrxTypeEnum::BUY_PACKAGE)
                        ->whereNotNull('accepted_at')
                        ->sum('amount');

                    $reinv = ItcPackage::join('transactions', 'itc_packages.uuid', '=', 'transactions.uuid')
                        ->whereIn('transactions.user_id', $ids)
                        ->withSum('reinvestProfitsAll', 'amount')
                        ->get()
                        ->sum('reinvest_profits_all_sum_amount');

                    return $lineTurnover[$line] = $buy + $reinv;
                };

                $rankAchieved = 0;

                foreach ($ranks as $rank) {

                    $fit = true;

                    foreach ($rank->requirements as $req) {

                        if (is_null($req->line)) {
                            $fit &= $personal >= $req->personal_deposit;
                        } else {
                            $fit &= $getTurnover($req->line) >= $req->required_turnover;
                        }

                        if (!$fit) break;
                    }

                    if ($fit) {
                        $rankAchieved = $rank->rank;
                        break;
                    }
                }

                $userBestRank[$user->id] = $rankAchieved;
            }
        });

        if (empty($userBestRank)) {
            $this->warn('Пользователи не найдены.');
            return self::SUCCESS;
        }

        $maxRank = max($userBestRank);
        $topN    = max(1, intval($this->option('top')));

        $leaders = collect($userBestRank)
            ->filter(fn ($r) => $r === $maxRank)
            ->take($topN);

        $this->line("Максимальный достигнутый ранг: {$maxRank}");
        $this->line(str_pad('ID', 8) . str_pad('Username', 18) . 'Email');
        $this->line(str_repeat('-', 48));

        foreach ($leaders as $uid => $rank) {
            $u = User::select('id', 'username', 'email')->find($uid);
            $this->line(
                str_pad($u->id, 8)
                . str_pad($u->username, 18)
                . $u->email
            );
        }

        return self::SUCCESS;
    }

    private function personalDeposit(int $userId): float
    {
        $packages = ItcPackage::join('transactions', 'itc_packages.uuid', '=', 'transactions.uuid')
            ->where('transactions.user_id', $userId)
            ->where('itc_packages.type', '!=', PackageTypeEnum::ARCHIVE)
            ->whereNull('itc_packages.closed_at')
            ->withSum('reinvestProfitsAll', 'amount')
            ->get();

        return $packages->sum(fn ($p) =>
            (float) $p->transaction->amount + (float) $p->reinvest_profits_all_sum_amount
        );
    }
}
