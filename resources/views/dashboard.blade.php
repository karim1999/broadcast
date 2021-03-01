<style>
    video {
        width: 300px;
    }
    .blur{
        -o-filter: blur(15px);
        filter: blur(30px);
    }
</style>
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

{{--    <div class="py-12">--}}
{{--        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">--}}
{{--            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">--}}
{{--                <div class="p-6 bg-white border-b border-gray-200">--}}
{{--                    You're logged in!--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </div>--}}
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div id="app" class="p-6 bg-white border-b border-gray-200">
                    @isset ($is_admin)
                        <pusher-admin :user='@json(auth()->user())'></pusher-admin>
                    @else
                        <pusher-test :user='@json(auth()->user())'></pusher-test>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
