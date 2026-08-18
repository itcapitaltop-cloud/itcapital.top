<?php

use App\Enums\LogActionTypeEnum;
use App\Models\BusinessActivity;
use App\Models\User;
use App\MoonShine\Resources\UserResource;
use App\Services\ActivityLog\ActivityFeedService;
use MoonShine\MoonShineRequest;

it('allows an admin to update a beneficiary and writes the change to the admin journal', function (): void {
    $admin = User::factory()->create();
    $user = User::factory()->create();
    $beneficiary = $user->beneficiaries()->create([
        'full_name' => 'Старое имя',
        'phone' => '+7 999 000-00-00',
        'social_url' => 'https://vk.com/old',
    ]);
    $this->actingAs($admin);

    $request = MoonShineRequest::create('/admin/beneficiary', 'POST', [
        'beneficiary_id' => $beneficiary->id,
        'user_id' => $user->id,
        'full_name' => 'Новое имя',
        'phone' => '+7 999 111-11-11',
        'social_url' => 'https://vk.com/new',
    ]);
    (new UserResource())->updateBeneficiary($request);

    expect($beneficiary->refresh()->full_name)->toBe('Новое имя');

    $activity = BusinessActivity::query()
        ->where('description', LogActionTypeEnum::UPDATE_BENEFICIARY->value)
        ->sole();

    expect($activity->user_id)->toBe($user->id)
        ->and($activity->getExtraProperty('old_values'))->toBe([
            'full_name' => 'Старое имя',
            'phone' => '+7 999 000-00-00',
            'social_url' => 'https://vk.com/old',
        ])
        ->and($activity->getExtraProperty('new_values'))->toMatchArray([
            'full_name' => 'Новое имя',
            'phone' => '+7 999 111-11-11',
            'social_url' => 'https://vk.com/new',
        ]);

    $journalRow = app(ActivityFeedService::class)->userDetailAdminFeed($user->id)->first();

    expect($journalRow['action'])->toBe('Изменение данных бенефициара: Новое имя')
        ->and($journalRow['old_values'])->toBe("ФИО: Старое имя\nТелефон: +7 999 000-00-00\nСоциальные сети: https://vk.com/old")
        ->and($journalRow['new_values'])->toBe("ФИО: Новое имя\nТелефон: +7 999 111-11-11\nСоциальные сети: https://vk.com/new")
        ->and($journalRow['operation_amount'])->toBe('');
});

it('does not write an admin journal entry when beneficiary data is unchanged', function (): void {
    $admin = User::factory()->create();
    $user = User::factory()->create();
    $beneficiary = $user->beneficiaries()->create([
        'full_name' => 'Иванов Иван Иванович',
        'phone' => '+7 999 000-00-00',
        'social_url' => 'https://vk.com/ivanov',
    ]);
    $this->actingAs($admin);

    $request = MoonShineRequest::create('/admin/beneficiary', 'POST', [
        'beneficiary_id' => $beneficiary->id,
        'user_id' => $user->id,
        'full_name' => $beneficiary->full_name,
        'phone' => $beneficiary->phone,
        'social_url' => $beneficiary->social_url,
    ]);
    (new UserResource())->updateBeneficiary($request);

    expect(BusinessActivity::query()
        ->where('description', LogActionTypeEnum::UPDATE_BENEFICIARY->value)
        ->exists())->toBeFalse();
});

it('allows an admin to delete a beneficiary and writes the deletion to the admin journal', function (): void {
    $admin = User::factory()->create();
    $user = User::factory()->create();
    $beneficiary = $user->beneficiaries()->create([
        'full_name' => 'Иванов Иван Иванович',
        'phone' => '+7 999 000-00-00',
        'social_url' => 'https://vk.com/ivanov',
    ]);
    $this->actingAs($admin);

    $request = MoonShineRequest::create('/admin/beneficiary/delete', 'POST', [
        'beneficiary_id' => $beneficiary->id,
        'user_id' => $user->id,
    ]);
    (new UserResource())->deleteBeneficiary($request);

    $this->assertDatabaseMissing('beneficiaries', ['id' => $beneficiary->id]);

    $activity = BusinessActivity::query()
        ->where('description', LogActionTypeEnum::DELETE_BENEFICIARY->value)
        ->sole();

    expect($activity->user_id)->toBe($user->id)
        ->and($activity->getExtraProperty('old_values'))->toBe([
            'full_name' => 'Иванов Иван Иванович',
            'phone' => '+7 999 000-00-00',
            'social_url' => 'https://vk.com/ivanov',
        ])
        ->and($activity->getExtraProperty('new_values'))->toBe([])
        ->and(app(ActivityFeedService::class)->userDetailAdminFeed($user->id)->first()['action'])
        ->toBe('Удаление бенефициара: Иванов Иван Иванович');
});
