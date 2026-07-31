<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Partner;
use App\Models\PartnerClosure;
use App\Models\User;
use Illuminate\Support\Collection;

final class ReferralTreeService
{
    /**
     * Полная структура рефералов пользователя, развёрнутая в плоский список
     * в порядке обхода дерева: сначала реферал первой линии, сразу под ним —
     * его собственные рефералы и так далее.
     *
     * Линия берётся из `partner_closures` (та же глубина, что и во всей админке),
     * забаненные пользователи не скрываются: карточка показывает структуру целиком.
     *
     * @return list<array{id: int, line: int, name: string}>
     */
    public function flatten(int $userId): array
    {
        $depths = PartnerClosure::query()
            ->where('ancestor_id', $userId)
            ->where('depth', '>', 0)
            ->pluck('depth', 'descendant_id')
            ->mapWithKeys(static fn ($depth, $id): array => [(int) $id => (int) $depth]);

        if ($depths->isEmpty()) {
            return [];
        }

        $ids = $depths->keys()->all();

        $users = User::query()
            ->withoutGlobalScope('notBanned')
            ->whereIn('id', $ids)
            ->get(['id', 'username', 'first_name', 'last_name'])
            ->keyBy('id');

        $parents = Partner::query()
            ->whereIn('user_id', $ids)
            ->pluck('partner_id', 'user_id')
            ->mapWithKeys(static fn ($parentId, $id): array => [(int) $id => (int) $parentId]);

        $children = $this->groupByParent($ids, $parents, $users);

        $rows = [];
        $visited = [];

        $this->walk($userId, 1, $children, $users, $depths, $visited, $rows);

        /*
         * Если ссылка на пригласившего потеряна (партнёр удалён или переназначен),
         * реферал не встретится при обходе дерева — добавляем таких в конец,
         * чтобы карточка не теряла людей из структуры.
         */
        $orphans = array_values(array_filter(
            $ids,
            static fn (int $id): bool => ! isset($visited[$id]) && isset($users[$id])
        ));

        usort(
            $orphans,
            fn (int $a, int $b): int => [$depths[$a], $this->name($users[$a])] <=> [$depths[$b], $this->name($users[$b])]
        );

        foreach ($orphans as $id) {
            $rows[] = $this->row($id, $depths[$id], $users);
        }

        return $rows;
    }

    /**
     * @param list<int> $ids
     * @param Collection<int, int> $parents
     * @param Collection<int, User> $users
     * @return array<int, list<int>>
     */
    private function groupByParent(array $ids, Collection $parents, Collection $users): array
    {
        $children = [];

        foreach ($ids as $id) {
            $parentId = $parents[$id] ?? null;

            if ($parentId === null) {
                continue;
            }

            $children[$parentId][] = $id;
        }

        foreach ($children as $parentId => $childIds) {
            usort(
                $childIds,
                fn (int $a, int $b): int => $this->name($users[$a]) <=> $this->name($users[$b])
            );

            $children[$parentId] = $childIds;
        }

        return $children;
    }

    /**
     * @param array<int, list<int>> $children
     * @param Collection<int, User> $users
     * @param Collection<int, int> $depths
     * @param array<int, true> $visited
     * @param list<array{id: int, line: int, username: string, full_name: string}> $rows
     */
    private function walk(
        int $parentId,
        int $line,
        array $children,
        Collection $users,
        Collection $depths,
        array &$visited,
        array &$rows
    ): void {
        foreach ($children[$parentId] ?? [] as $id) {
            if (isset($visited[$id]) || ! isset($users[$id])) {
                continue;
            }

            $visited[$id] = true;
            $rows[] = $this->row($id, $depths[$id] ?? $line, $users);

            $this->walk($id, $line + 1, $children, $users, $depths, $visited, $rows);
        }
    }

    /**
     * @param Collection<int, User> $users
     * @return array{id: int, line: int, name: string}
     */
    private function row(int $id, int $line, Collection $users): array
    {
        return [
            'id' => $id,
            'line' => $line,
            'name' => $this->name($users[$id]),
        ];
    }

    /**
     * Имя и фамилия пользователя; логин остаётся запасным вариантом,
     * только если ФИО в профиле не заполнено.
     */
    private function name(User $user): string
    {
        $name = trim("{$user->first_name} {$user->last_name}");

        return $name === '' ? (string) $user->username : $name;
    }
}
