<?php

namespace App\Traits;

trait HasReactionEmojis
{
    public static function getReactionEmojis(): array
    {
        return [
            'like'  => '👍',
            'love'  => '❤️',
            'haha'  => '😂',
            'wow'   => '😮',
            'sad'   => '😢',
            'angry' => '😡',
        ];
    }

    public static function getReactionEmoji(string $type): string
    {
        return self::getReactionEmojis()[$type] ?? '👍';
    }

    public static function getReactionTypes(): array
    {
        return array_keys(self::getReactionEmojis());
    }

    public static function getReactionTypesForValidation(): string
    {
        return implode(',', self::getReactionTypes());
    }
}
