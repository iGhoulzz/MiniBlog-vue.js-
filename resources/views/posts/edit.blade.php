<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Post - MiniBlog+</title>
    <script src="https://cdn.tailwindcss.com"></script>
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

        <div class="bg-white p-8 rounded-lg shadow-md">
            <h2 class="text-2xl font-bold mb-6">Edit Your Post</h2>
            <form action="/posts/{{ $post->id }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="mb-6">
                    <textarea name="content"
                        class="w-full border border-gray-300 rounded-lg p-4 focus:ring-blue-500 focus:border-blue-500 transition"
                        rows="6">{{ old('content', $post->content) }}</textarea>
                    @error('content')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex items-center justify-end space-x-4">
                    <a href="/" class="text-gray-600 hover:underline">Cancel</a>
                    <button type="submit"
                        class="bg-green-500 text-white font-bold py-2 px-6 rounded-lg hover:bg-green-600 transition">Update
                        Post</button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>
