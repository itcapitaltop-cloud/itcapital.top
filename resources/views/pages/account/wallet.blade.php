@extends('layouts.account')

@section('main')
    <x-account.wallet.header />
    <div class="mt-6">
        <h2 class="text-white text-xl">{{ __('deposit') }}</h2>
        <livewire:account.wallet.deposits-list />
    </div>
    <div class="mt-6">
        <h2 class="text-white text-xl">{{ __('conclusions') }}</h2>
        <livewire:account.wallet.withdraws-list />
    </div>
@endsection
