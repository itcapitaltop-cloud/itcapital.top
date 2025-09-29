@extends('layouts.app')

@section('content')
    <div class="min-h-screen bg-[#17162d] flex flex-col">
        <livewire:account.layout.header />
        <main class="flex-1 sm:ml-[340px] px-4 pt-4 pb-16 md:px-8 md:pt-8 md:pb-16">
            @yield('main')
        </main>
    </div>
@endsection

