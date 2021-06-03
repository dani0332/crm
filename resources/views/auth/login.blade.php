<style>
.btn-warning {
    background-color:#4099de;
    color:#fff;
}
</style>
<x-guest-layout>
    <x-jet-authentication-card>
        <x-slot name="logo">
            <img src='{{ asset("image/logo.png") }}' />
        </x-slot>

        <x-jet-validation-errors class="mb-4" />

        @if (session('status'))
            <div class="mb-4 font-medium text-sm text-green-600">
                {{ session('status') }}
            </div>
        @endif

        <h2 class="text-2xl text-center font-normal mb-6 text-90">Welcome</h2>
        <svg class="block mx-auto mb-6" xmlns="http://www.w3.org/2000/svg" width="100" height="2" viewBox="0 0 100 2">
            <path fill="#D8E3EC" d="M0 0h100v2H0z"></path>
        </svg>
        <p class="m-4">
            You can only sign in with a afia.ae account.
        </p>
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <a href="{{ url('auth/google') }}"  class="btn btn-block" style="background: #4183bd;color:fff;">
                <strong>Google Login</strong>
            </a>
        </form>
    </x-jet-authentication-card>
</x-guest-layout>
