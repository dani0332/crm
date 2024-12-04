<x-guest-layout>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">


        <img
            src="{{ getIMLogo() }}"
            alt="IMCRM"
            width="439"
            height="66"
            style="max-width: 300px;"
        />


        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
            <h2 class="text-2xl text-center font-normal mb-6">Welcome</h2>
            <svg class="block mx-auto mb-6" xmlns="http://www.w3.org/2000/svg" width="100" height="2" viewBox="0 0 100 2">
                <path fill="#D8E3EC" d="M0 0h100v2H0z"></path>
            </svg>
            <p class="p-4 text-center">
                You can only sign in with your <a href="https://insurancemarket.ae" class="text-[#4183bd]" target="_blank">insurancemarket.ae</a> account
            </p>
            <a href="{{ url('auth/google') }}" class="bg-[#4183bd] hover:bg-opacity-90 block p-2 rounded text-center text-white">
                <strong>Google Login</strong>
            </a>
            @if (session('status'))
            <div class="mt-4 font-medium text-sm text-red-600 text-center">
                {{ session('status') }}
            </div>
            @endif
        </div>
    </div>
</x-guest-layout>
