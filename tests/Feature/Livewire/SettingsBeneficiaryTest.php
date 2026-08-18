<?php

use App\Livewire\Account\User\SettingsModal;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('allows a user to add a beneficiary from profile settings', function (): void {
    Livewire::test(SettingsModal::class)
        ->set('beneficiaryFullName', 'Иванов Иван Иванович')
        ->set('beneficiaryPhone', '+7 (999) 123-45-67')
        ->set('beneficiarySocialUrl', 'https://t.me/ivanov')
        ->call('saveBeneficiary')
        ->assertHasNoErrors()
        ->assertDispatched('beneficiary-saved');

    $beneficiary = $this->user->beneficiaries()->sole();

    expect($beneficiary->full_name)->toBe('Иванов Иван Иванович')
        ->and($beneficiary->phone)->toBe('+7 (999) 123-45-67')
        ->and($beneficiary->social_url)->toBe('https://t.me/ivanov');
});

it('validates beneficiary details', function (array $values, array $errors): void {
    Livewire::test(SettingsModal::class)
        ->set($values)
        ->call('saveBeneficiary')
        ->assertHasErrors($errors);

    expect($this->user->beneficiaries()->exists())->toBeFalse();
})->with([
    'empty values' => [[], [
        'beneficiaryFullName' => 'required',
        'beneficiaryPhone' => 'required',
        'beneficiarySocialUrl' => 'required',
    ]],
    'invalid phone and social link' => [[
        'beneficiaryFullName' => 'Иванов Иван Иванович',
        'beneficiaryPhone' => 'phone-number',
        'beneficiarySocialUrl' => 'telegram profile',
    ], [
        'beneficiaryPhone' => 'regex',
        'beneficiarySocialUrl' => 'url',
    ]],
]);

it('allows a user to add multiple beneficiaries', function (): void {
    $this->user->beneficiaries()->create([
        'full_name' => 'Первый бенефициар',
        'phone' => '+7 999 000-00-00',
        'social_url' => 'https://vk.com/first',
    ]);

    Livewire::test(SettingsModal::class)
        ->set('beneficiaryFullName', 'Второй бенефициар')
        ->set('beneficiaryPhone', '+7 999 111-11-11')
        ->set('beneficiarySocialUrl', 'https://vk.com/second')
        ->call('saveBeneficiary')
        ->assertHasNoErrors();

    expect($this->user->beneficiaries()->count())->toBe(2)
        ->and($this->user->beneficiaries()->latest('id')->first()->full_name)->toBe('Второй бенефициар');
});

it('allows a user to edit their beneficiary', function (): void {
    $beneficiary = $this->user->beneficiaries()->create([
        'full_name' => 'Старое имя',
        'phone' => '+7 999 000-00-00',
        'social_url' => 'https://vk.com/old',
    ]);

    Livewire::test(SettingsModal::class)
        ->call('startEditingBeneficiary', $beneficiary->id)
        ->assertSet('editingBeneficiaryId', $beneficiary->id)
        ->assertSet('beneficiaryFullName', 'Старое имя')
        ->set('beneficiaryFullName', 'Новое имя')
        ->set('beneficiaryPhone', '+7 999 111-11-11')
        ->set('beneficiarySocialUrl', 'https://vk.com/new')
        ->call('saveBeneficiary')
        ->assertHasNoErrors()
        ->assertDispatched('beneficiary-saved');

    expect($beneficiary->refresh()->full_name)->toBe('Новое имя')
        ->and($this->user->beneficiaries()->count())->toBe(1);
});

it('prevents a user from editing another users beneficiary', function (): void {
    $beneficiary = User::factory()->create()->beneficiaries()->create([
        'full_name' => 'Чужой бенефициар',
        'phone' => '+7 999 000-00-00',
        'social_url' => 'https://vk.com/other',
    ]);

    Livewire::test(SettingsModal::class)
        ->call('startEditingBeneficiary', $beneficiary->id)
        ->assertForbidden();
});

it('allows a user to delete their beneficiary', function (): void {
    $beneficiary = $this->user->beneficiaries()->create([
        'full_name' => 'Иванов Иван Иванович',
        'phone' => '+7 999 000-00-00',
        'social_url' => 'https://vk.com/ivanov',
    ]);

    Livewire::test(SettingsModal::class)
        ->call('startDeletingBeneficiary', $beneficiary->id)
        ->assertSet('deletingBeneficiaryId', $beneficiary->id)
        ->assertSet('deletingBeneficiaryName', 'Иванов Иван Иванович')
        ->assertDispatched('beneficiary-delete-confirmation-opened')
        ->call('deleteBeneficiary')
        ->assertDispatched('beneficiary-deleted')
        ->assertSee('Бенефициары ещё не добавлены');

    $this->assertDatabaseMissing('beneficiaries', ['id' => $beneficiary->id]);
});

it('prevents a user from deleting another users beneficiary', function (): void {
    $beneficiary = User::factory()->create()->beneficiaries()->create([
        'full_name' => 'Чужой бенефициар',
        'phone' => '+7 999 000-00-00',
        'social_url' => 'https://vk.com/other',
    ]);

    Livewire::test(SettingsModal::class)
        ->call('startDeletingBeneficiary', $beneficiary->id)
        ->assertForbidden();

    $this->assertDatabaseHas('beneficiaries', ['id' => $beneficiary->id]);
});

it('shows readable validation messages', function (): void {
    Livewire::test(SettingsModal::class)
        ->set('beneficiarySocialUrl', 'telegram profile')
        ->call('saveBeneficiary')
        ->assertSee('Введите полное ФИО бенефициара')
        ->assertSee('Введите номер телефона')
        ->assertSee('Введите корректную ссылку, начинающуюся с http:// или https://');
});

it('renders profile and beneficiary settings as separate tabs', function (): void {
    Livewire::test(SettingsModal::class)
        ->assertSee('Пользователь')
        ->assertSee('Бенефициары')
        ->assertSee('Бенефициары ещё не добавлены')
        ->assertSee('Добавить бенефициара');
});

it('deletes beneficiaries when their user is deleted', function (): void {
    $beneficiary = $this->user->beneficiaries()->create([
        'full_name' => 'Иванов Иван Иванович',
        'phone' => '+7 999 123-45-67',
        'social_url' => 'https://vk.com/ivanov',
    ]);

    $this->user->forceDelete();

    $this->assertDatabaseMissing('beneficiaries', ['id' => $beneficiary->id]);
});
