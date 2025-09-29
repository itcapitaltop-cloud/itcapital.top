<?php

namespace App\Repositories;

use App\Models\PartnerClosure;

class PartnerClosureRepository
{
    public static function add(int $parentId, int $childId): void
    {
        PartnerClosure::firstOrCreate([
            'ancestor_id'   => $parentId,
            'descendant_id' => $parentId,
            'depth'         => 0,
        ]);

        $paths = PartnerClosure::where('descendant_id', $parentId)->get();

        $bulk = $paths->map(fn ($p) => [
            'ancestor_id'   => $p->ancestor_id,
            'descendant_id' => $childId,
            'depth'         => $p->depth + 1,
        ])->all();

        $bulk[] = [
            'ancestor_id'   => $childId,
            'descendant_id' => $childId,
            'depth'         => 0,
        ];

        PartnerClosure::upsert(
            $bulk,
            ['ancestor_id', 'descendant_id'],
            ['depth']
        );
    }
}
