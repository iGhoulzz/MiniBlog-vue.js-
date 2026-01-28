<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Post;
use App\Models\Comment;
use App\Traits\HasReactionEmojis;

class ReactRequest extends FormRequest
{
    use HasReactionEmojis;

    /**
     * Determine if the user is authorized to make this request.
     * 
     * Returns true because authorization is handled by auth:sanctum middleware.
     * All authenticated users can react to posts and comments.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reactionable_type' => 'required|string|in:post,comment',
            'reactionable_id' => 'required|integer',
            'type' => 'required|string|in:' . self::getReactionTypesForValidation(),
        ];
    }

    public function getModelClass(): string
    {
        return match ($this->reactionable_type) {
            'post' => Post::class,
            'comment' => Comment::class,
        };
    }
}
