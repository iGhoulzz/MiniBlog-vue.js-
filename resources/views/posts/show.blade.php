<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Post - MiniBlog+</title>
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
            <div class="flex items-start mb-4">
                <div class="flex-shrink-0">
                    <div
                        class="w-12 h-12 rounded-full bg-gray-300 flex items-center justify-center font-bold text-gray-600">
                        {{ substr($post->user->name, 0, 1) }}
                    </div>
                </div>
                <div class="ml-4">
                    <span class="font-bold text-gray-800">{{ $post->user->name }}</span>
                    <span class="text-gray-500 text-sm"> · {{ $post->created_at->diffForHumans() }}</span>
                </div>
            </div>
            <p class="text-gray-800 text-lg leading-relaxed whitespace-pre-wrap">{{ $post->content }}</p>

            @can('update', $post)
                <div class="mt-6 pt-4 border-t border-gray-200 flex items-center space-x-4">
                    <a href="/posts/{{ $post->id }}/edit"
                        class="bg-blue-500 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-600 transition">Edit</a>
                    <form action="/posts/{{ $post->id }}" method="POST"
                        onsubmit="return confirm('Are you sure you want to delete this post?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="bg-red-500 text-white font-bold py-2 px-4 rounded-lg hover:bg-red-600 transition">Delete</button>
                    </form>
                </div>
            @endcan
        </div>

        <!-- Comments Section -->
        <div class="bg-white p-8 rounded-lg shadow-md mt-8">
            <h3 class="text-2xl font-bold mb-6">Comments</h3>

            <!-- Display Existing Comments -->
            <div class="space-y-6 mb-8">
                @forelse ($post->comments as $comment)
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div
                                class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center font-bold text-gray-500">
                                {{ substr($comment->user->name, 0, 1) }}
                            </div>
                        </div>
                        <div class="ml-4 flex-grow">
                            <div>
                                <span class="font-bold text-gray-800">{{ $comment->user->name }}</span>
                                <span class="text-gray-500 text-sm"> ·
                                    {{ $comment->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-gray-700 mt-1">{{ $comment->content }}</p>

                            <!-- Comment Actions: Edit and Delete -->
                            <div class="mt-2 flex items-center space-x-3 text-sm">
                                @can('update', $comment)
                                    <a href="/comments/{{ $comment->id }}/edit"
                                        class="text-blue-500 hover:underline">Edit</a>
                                @endcan

                                @can('delete', $comment)
                                    <form action="/comments/{{ $comment->id }}" method="POST"
                                        onsubmit="return confirm('Are you sure you want to delete this comment?');"
                                        class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:underline">Delete</button>
                                    </form>
                                @endcan
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500">No comments yet. Be the first to comment!</p>
                @endforelse
            </div>

            @auth
                <form action="/posts/{{ $post->id }}/comments" method="POST" class="border-t border-gray-200 pt-6">
                    @csrf
                    <div class="mb-4">
                        <textarea name="content" placeholder="Leave a comment..."
                            class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            rows="3"></textarea>
                    </div>
                    <button type="submit"
                        class="bg-blue-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-700 transition">Post
                        Comment</button>
                </form>
            @else
                <p class="text-gray-600 border-t border-gray-200 pt-6">
                    <a href="/login" class="text-blue-600 hover:underline font-semibold">Log in</a> to post a comment.
                </p>
            @endauth
        </div>

        <div class="mt-6 text-center">
            <a href="/" class="text-blue-600 hover:underline">&larr; Back to all posts</a>
        </div>
    </div>
</body>

</html>
