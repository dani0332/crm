<x-guest-layout>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
        <img src='{{ asset("image/new_logo.png") }}' />


        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
            <h2 class="text-2xl text-center font-normal mb-6">Welcome</h2>
            <svg class="block mx-auto mb-6" xmlns="http://www.w3.org/2000/svg" width="100" height="2" viewBox="0 0 100 2">
                <path fill="#D8E3EC" d="M0 0h100v2H0z"></path>
            </svg>
            <form method="POST" action="{{ route('alternate_login') }}">
                @csrf
                <div class="input-group mb-4">
                    <label>Email</label>
                    <input type="text" name="email" class="appearance-none block w-full placeholder-gray-400 dark:placeholder-gray-500 outline-transparent outline outline-2 outline-offset-[-1px] transition-all duration-150 ease-in-out border-gray-300 dark:border-gray-700 border shadow-sm rounded-md hover:border-gray-400 dark:hover:border-gray-500 px-3 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 focus:outline-[color:var(--x-input-border)] w-full" />
                </div>
                <div class="input-group mb-4">
                    <label>Password</label>
                    <input type="password"  name="password" class="appearance-none block w-full placeholder-gray-400 dark:placeholder-gray-500 outline-transparent outline outline-2 outline-offset-[-1px] transition-all duration-150 ease-in-out border-gray-300 dark:border-gray-700 border shadow-sm rounded-md hover:border-gray-400 dark:hover:border-gray-500 px-3 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 focus:outline-[color:var(--x-input-border)] w-full" />
                </div>
                <button class="bg-[#4183bd] hover:bg-opacity-90 block p-2 rounded text-center text-white">
                    Login
                </button>
            </form>
            @if (session('status'))
            <div class="mt-4 font-medium text-sm text-red-600 text-center">
                {{ session('status') }}
            </div>
            @endif
        </div>
    </div>
</x-guest-layout>
