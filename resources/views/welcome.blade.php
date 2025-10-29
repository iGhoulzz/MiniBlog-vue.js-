<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="bg-gray-100 text-gray-800 font-sans antialiased">
    @include('partials._flash')
    <div class="min-h-screen flex flex-col items-center justify-center bg-gray-100 dark:bg-gray-900">
        <nav class="w-full max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-4 absolute top-0">
            <div class="flex items-center justify-between">
                <a href="/" class="text-2xl font-bold text-blue-600 dark:text-blue-400">MiniBlog</a>
                <div class="flex items-center gap-4">
                    @auth
                        <a href="{{ url('/') }}"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">Home</a>
                        <form method="POST" action="{{ url('/logout') }}">
                            @csrf
                            <button type="submit"
                                class="px-4 py-2 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                                Log Out
                            </button>
                        </form>
                    @else
                        <a href="{{ url('/login') }}"
                            class="px-4 py-2 text-blue-600 dark:text-blue-400 border border-blue-600 dark:border-blue-400 rounded-md hover:bg-blue-600 hover:text-white dark:hover:bg-blue-400 dark:hover:text-gray-900 transition">
                            Log in
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ url('/register') }}"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                                Register
                            </a>
                        @endif
                    @endauth
                </div>
            </div>
        </nav>

        <main class="flex items-center justify-center w-full flex-grow">
            <div class="text-center">
                <h1 class="text-5xl font-extrabold text-gray-900 dark:text-white mb-4">Welcome to MiniBlog</h1>
                <p class="text-xl text-gray-600 dark:text-gray-400 mb-8">A place to share your thoughts with the world.
                </p>
                <div class="space-x-4">
                    <a href="https://laravel.com/docs" target="_blank" class="text-blue-500 hover:underline">Read the
                        Docs</a>
                    <span class="text-gray-400 dark:text-gray-600">|</span>
                    <a href="https://laracasts.com" target="_blank" class="text-blue-500 hover:underline">Watch
                        Laracasts</a>
                </div>
            </div>
        </main>
    </div>
</body>

</html>
