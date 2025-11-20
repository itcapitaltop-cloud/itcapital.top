<?php

test('проверка требований к повышению ранга', function () {
    \App\Models\PartnerRank::factory()->create();
    \App\Models\PartnerRankRequirement::factory()->withDefaultRequirements()->create();

    $service = app(\App\Contracts\Repositories\PartnerRepositoryContract::class);

    $result = $service->requirements();

    expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($result->first())->toBeInstanceOf(\App\Dto\Partners\PartnerRankDataTransferObject::class)
        ->and($result->toArray())->toHaveCount(8)
        ->and($result->first()->bonus)->toBe('500.00')
        ->and($result->first()->requirements)->toBeIterable()
        ->and($result->first()->requirements->first())->toBeInstanceOf(\App\Dto\Partners\PartnerRequirementDataTransferObject::class);

});
