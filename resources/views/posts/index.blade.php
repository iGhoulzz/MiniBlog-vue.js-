<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MiniBlog+ Home</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="bg-gray-100 font-sans">
    @include('partials._flash')
    <div class="container mx-auto mt-10 max-w-3xl">
        <nav class="bg-white p-4 rounded-lg shadow-md flex justify-between items-center mb-8">
            <a href="/" class="text-2xl font-bold text-blue-600">MiniBlog+</a>
            <div class="flex items-center">
                @auth
                    <span class="font-semibold text-gray-700 mr-4">Welcome, {{ auth()->user()->name }}</span>
                    <form action="/logout" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                            class="bg-red-500 text-white font-bold py-2 px-4 rounded-lg hover:bg-red-600 transition">Logout</button>
                    </form>
                @else
                    <a href="/login" class="text-blue-600 font-semibold mr-4 hover:underline">Login</a>
                    <a href="/register"
                        class="bg-blue-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-700 transition">Register</a>
                @endauth
            </div>
        </nav>

        @auth
            <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                <h2 class="text-xl font-semibold mb-4">Create a New Post</h2>
                <form action="/posts" method="POST">
                    @csrf
                    <textarea name="content"
                        class="w-full border border-gray-300 rounded-lg p-4 focus:ring-blue-500 focus:border-blue-500 transition"
                        rows="4" placeholder="What's on your mind, {{ auth()->user()->name }}?"></textarea>
                    @error('content')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                    <div class="flex justify-end mt-4">
                        <button type="submit"
                            class="bg-blue-600 text-white font-bold py-2 px-6 rounded-lg hover:bg-blue-700 transition">Post</button>
                    </div>
                </form>
            </div>
        @endauth

        <div class="space-y-6">
            @forelse ($posts as $post)
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            {{-- Placeholder for user avatar --}}
                            <div
                                class="w-12 h-12 rounded-full bg-gray-300 flex items-center justify-center font-bold text-gray-600">
                                {{ substr($post->user->name, 0, 1) }}
                            </div>
                        </div>
                        <div class="ml-4 flex-grow">
                            <div class="flex justify-between items-center">
                                <div>
                                    <span class="font-bold text-gray-800">{{ $post->user->name }}</span>
                                    <span class="text-gray-500 text-sm"> ·
                                        {{ $post->created_at->diffForHumans() }}</span>
                                </div>
                                @can('update', $post)
                                    <div class="relative" x-data="{ open: false }">
                                        <button @click="open = !open"
                                            class="text-gray-500 hover:text-gray-700 focus:outline-none">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z">
                                                </path>
                                            </svg>
                                        </button>
                                        <div x-show="open" @click.away="open = false"
                                            class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10"
                                            style="display: none;">
                                            <a href="/posts/{{ $post->id }}/edit"
                                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Edit</a>
                                            <form action="/posts/{{ $post->id }}" method="POST"
                                                onsubmit="return confirm('Are you sure you want to delete this post?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                @endcan
                            </div>
                            <a href="/posts/{{ $post->id }}">
                                <p class="mt-2 text-gray-700 whitespace-pre-wrap">{{ $post->content }}</p>
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white p-6 rounded-lg shadow-md text-center">
                    <p class="text-gray-500">No posts yet. Be the first to share something!</p>
                </div>
            @endforelse
        </div>
    </div>
</body>

</html>
