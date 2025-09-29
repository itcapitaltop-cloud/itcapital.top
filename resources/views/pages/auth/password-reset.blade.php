@extends('layouts.app')

@section('content')
    <div class="">
        <livewire:auth.password-reset :token="$token" />
    </div>
@endsection
