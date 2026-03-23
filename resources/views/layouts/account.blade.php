@extends('layouts.app')

@section('content')
    <div class="min-h-screen overflow-x-hidden bg-[#17162d] flex flex-col pt-[176px] md:pt-[96px]">
        <livewire:account.layout.header />
        <livewire:quotes.marquee class="hidden md:block" />
        <div class="flex flex-1">
            <livewire:account.layout.sidebar />
            <main class="min-w-0 flex-1 px-4 pt-4 pb-16 md:px-8 md:pt-8 md:pb-16">
                @yield('main')
            </main>
        </div>
    </div>
@endsection
