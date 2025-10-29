<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Comment - MiniBlog+</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
        }

        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }

        .edit-comment-form {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .edit-comment-form h2 {
            margin-top: 0;
        }

        .edit-comment-form textarea {
            width: 100%;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            font-size: 1em;
            box-sizing: border-box;
            resize: vertical;
            min-height: 120px;
        }

        .edit-comment-form button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 10px;
        }

        .edit-comment-form .cancel-link {
            text-decoration: none;
            color: #6c757d;
            margin-left: 10px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="edit-comment-form">
            <h2>Edit Comment</h2>
            <form action="/comments/{{ $comment->id }}" method="POST">
                @csrf
                @method('PATCH') {{-- This tells Laravel to treat this as an UPDATE --}}

                <div class="form-group">
                    <textarea name="content">{{ $comment->content }}</textarea>
                </div>

                <button type="submit">Update Comment</button>
                <a href="/posts/{{ $comment->post->id }}" class="cancel-link">Cancel</a>
            </form>
        </div>
    </div>
</body>

</html>
