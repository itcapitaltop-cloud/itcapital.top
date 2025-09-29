@extends('layouts.account')

@section('main')
    <x-account.common-fund.header />
    <div class="mt-6">
        <livewire:account.common-fund.packages-list />
    </div>
@endsection
