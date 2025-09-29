<?php

namespace App\Livewire\Notifications;

use App\Livewire\Account\Itc\Packages;
use App\Models\PackageProfitWithReinvestLink;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

class Dropdown extends Component
{
    use WithPagination;
    public string $tab = 'unread'; // unread|read
    public int $unreadCount = 0;
    public int $readCount = 0;
    public bool $openNotifications = false;

    public bool $isLanding = false;

    public bool $itemsLoaded = false;

    public function mount(): void
    {
        $this->isLanding = ! request()->is('account*');
        $this->refreshCount();
    }

    public function updatedOpenNotifications($value): void
    {
        if ($value && ! $this->itemsLoaded) {
            $this->itemsLoaded = true;    // включаем загрузку только при первом открытии
        }
    }

    public function refreshCount(): void
    {
        $user = auth()->user();

        if (! $user) {
            $this->unreadCount = 0;
            $this->readCount   = 0;
            $this->dispatch('notifications:count', unread: 0);
            return;
        }

        $this->unreadCount = auth()->user()?->unreadNotifications()->count() ?? 0;
        $this->readCount   = $user->readNotifications()->count();

        $this->dispatch('notifications:count', unread: $this->unreadCount);
    }

    public function switchTab(string $tab): void
    {
        $this->tab = $tab === 'read' ? 'read' : 'unread';
        $this->resetPage();
        $this->refreshCount();
    }

    public function markAllRead(): void
    {
        $user = auth()->user();
        if (!$user) return;

        $user->unreadNotifications->markAsRead();
        $this->refreshCount();
        $this->dispatch('notifications-updated');
    }

    public function markAsRead(string $id): void
    {
        $n = auth()->user()?->notifications()->findOrFail($id);
        $n->markAsRead();
        $this->refreshCount();
    }

    public function performAction(string $id)
    {
        $n = auth()->user()?->notifications()->findOrFail($id);
        $action = $n->data['action'] ?? null;

        if (($action['type'] ?? null) !== 'call') {
            $this->markAsRead($id);
            return;
        }
        switch ($action['name'] ?? '') {
            case 'reinvest':
                $profitUuid = Arr::get($action, 'params.uuid');
                if ($profitUuid) {
                    try {
                        app(Packages::class)->reinvestOneProfit($profitUuid);
                        // опционально: всплывашка об успехе
                        $this->dispatch('new-system-notification', type: 'success', message: 'Реинвест выполнен');
                        $this->dispatch('itc:packages-refresh');

                    } catch (Throwable $e) {
                        report($e);
                        $this->dispatch('new-system-notification', type: 'error', message: $e->getMessage());
                    }
                }
                $this->markAsRead($id);
                break;

            default:
                $this->markAsRead($id);
                break;
        }
    }

    public function getReinvestedMapProperty(): array
    {
        $items = $this->items;

        $uuids = $items
            ->map(function ($n) {
                $action = $n->data['action'] ?? null;
                if (($action['type'] ?? null) !== 'call' || ($action['name'] ?? null) !== 'reinvest') {
                    return null;
                }
                return Arr::get($action, 'params.uuid');
            })
            ->filter()
            ->unique()
            ->values();

        if ($uuids->isEmpty()) {
            return [];
        }

        $reinvested = PackageProfitWithReinvestLink::query()
            ->whereIn('profit_uuid', $uuids)
            ->pluck('profit_uuid')
            ->all();

        $notificationIds = $items->pluck('id');

        $readUuids = DB::table('notification_profit_readeds as npr')
            ->join('notifications as n', 'n.id', '=', 'npr.notification_id')
            ->whereIn('npr.notification_id', $notificationIds)
            ->whereRaw("jsonb_extract_path_text(n.data::jsonb, 'action','type') = ?", ['call'])
            ->whereRaw("jsonb_extract_path_text(n.data::jsonb, 'action','name') = ?", ['reinvest'])
            ->selectRaw("jsonb_extract_path_text(n.data::jsonb, 'action','params','uuid') as uuid")
            ->pluck('uuid')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $keys = array_unique(array_merge($reinvested, $readUuids));

        return array_fill_keys($keys, true);

    }

    public function getItemsProperty(): Collection
    {
        $user = auth()->user();

        if ($this->tab === 'read') {
            return $user->readNotifications()->latest()->get();
        }

        return $user->unreadNotifications()->latest()->get();
    }

    public function render()
    {
        return view('livewire.notifications.dropdown', [
            'reinvestedMap' => $this->reinvestedMap,
        ]);
    }
}
